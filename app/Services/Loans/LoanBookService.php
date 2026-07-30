<?php

namespace App\Services\Loans;

use App\Models\Loans\LoanBookDetailsStaging;
use App\Models\Loans\LoanBookEntry;
use App\Models\Loans\LoanBookException;
use App\Models\Loans\LoanBookPmsStaging;
use App\Models\Loans\LoanBookRun;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class LoanBookService
{
    protected $normalizer;
    protected $supplementaryReports;

    public function __construct(
        LoanBookNormalizerService $normalizer,
        LoanBookSupplementaryReportService $supplementaryReports
    ) {
        $this->normalizer = $normalizer;
        $this->supplementaryReports = $supplementaryReports;
    }

    public function processDraftRun($run, $loanBookDate, $userId, $portfolioReport = null, $creditCardsReport = null, $lmsReport = null)
    {
        /*
         * Credit Cards and Portfolio reports are NOT staged/saved.
         * They are read here, filtered in memory, and only selected final records
         * are merged into loan_book_entries.
         */
        $portfolioResult = $this->supplementaryReports->extractPortfolioAccounts($portfolioReport);
        $creditCardsResult = $this->supplementaryReports->extractCreditCards($creditCardsReport);
        $lmsResult = $this->supplementaryReports->extractLmsLoans($lmsReport);

        return DB::transaction(function () use (
            $run,
            $loanBookDate,
            $userId,
            $portfolioResult,
            $creditCardsResult,
            $lmsResult
        ) {
            $pmsCount = LoanBookPmsStaging::where('loan_book_run_id', $run->id)->count();
            $detailsCount = LoanBookDetailsStaging::where('loan_book_run_id', $run->id)->count();

            if ($pmsCount === 0) {
                throw new Exception('Please upload the PMS Loan Proofing Report before processing.');
            }

            if ($detailsCount === 0) {
                throw new Exception('Please upload the Loans Details Report before processing.');
            }

            $now = Carbon::now();

            $run->update([
                'status' => 'processing',
                'loan_book_date' => $loanBookDate ?: $run->loan_book_date,
                'failure_reason' => null,
            ]);

            LoanBookEntry::where('loan_book_run_id', $run->id)->delete();
            LoanBookException::where('loan_book_run_id', $run->id)->delete();

            $exceptions = [];
            $entries = [];
            $supplementaryEntries = [];

            $pmsRows = LoanBookPmsStaging::where('loan_book_run_id', $run->id)->get();
            $detailsRows = LoanBookDetailsStaging::where('loan_book_run_id', $run->id)->get();

            $pmsGrouped = $this->groupPmsRows($run, $pmsRows, $exceptions, $now);

            $detailsSeen = [];
            $usedPmsKeys = [];

            foreach ($detailsRows as $row) {
                $relatedAccount = $row->related_account;
                $relatedCustomerId = $row->related_customer_id;
                $name = $row->name;

                if (!$relatedAccount) {
                    $this->addException(
                        $exceptions,
                        $run->id,
                        'BLANK_RELATED_ACCOUNT',
                        'LOAN_DETAILS',
                        null,
                        $relatedCustomerId,
                        $name,
                        null,
                        'Related Account is blank in Loans Details Report.',
                        $row->toArray(),
                        $now
                    );
                    continue;
                }

                if (!$relatedCustomerId) {
                    $this->addException(
                        $exceptions,
                        $run->id,
                        'BLANK_CUSTOMER_ID',
                        'LOAN_DETAILS',
                        $relatedAccount,
                        null,
                        $name,
                        null,
                        'Related Customer Id is blank in Loans Details Report.',
                        $row->toArray(),
                        $now
                    );
                    continue;
                }

                $key = $this->normalizer->key($relatedAccount, $relatedCustomerId);

                if (isset($detailsSeen[$key])) {
                    $this->addException(
                        $exceptions,
                        $run->id,
                        'DUPLICATE_LOAN_DETAILS',
                        'LOAN_DETAILS',
                        $relatedAccount,
                        $relatedCustomerId,
                        $name,
                        null,
                        'Duplicate Related Account + Related Customer Id found in Loans Details Report. This duplicate was excluded.',
                        $row->toArray(),
                        $now
                    );
                    continue;
                }

                $detailsSeen[$key] = true;

                if (!isset($pmsGrouped[$key])) {
                    $this->addException(
                        $exceptions,
                        $run->id,
                        'MISSING_IN_PMS',
                        'LOAN_DETAILS',
                        $relatedAccount,
                        $relatedCustomerId,
                        $name,
                        null,
                        'Loan exists in Loans Details Report but has no matching PMS outstanding amount.',
                        $row->toArray(),
                        $now
                    );
                    continue;
                }

                $pms = $pmsGrouped[$key];
                $usedPmsKeys[$key] = true;

                $netOutstanding = round($pms['net_outstanding_amount'], 2);

                $detailsName = $this->normalizer->comparisonName($name);
                $pmsName = $this->normalizer->comparisonName($pms['name']);

                if ($detailsName && $pmsName && $detailsName !== $pmsName) {
                    $this->addException(
                        $exceptions,
                        $run->id,
                        'NAME_MISMATCH',
                        'BOTH',
                        $relatedAccount,
                        $relatedCustomerId,
                        $name,
                        $netOutstanding,
                        'Name differs between PMS and Loans Details Report. PMS Name: ' . $pms['name'],
                        [
                            'loan_details' => $row->toArray(),
                            'pms' => $pms,
                        ],
                        $now
                    );
                }

                if ($netOutstanding < 0) {
                    $entries[] = $this->buildEntry($run->id, $row, $pms, $netOutstanding, $now);
                    continue;
                }

                if ($netOutstanding == 0.00) {
                    $this->addException(
                        $exceptions,
                        $run->id,
                        'ZERO_OUTSTANDING',
                        'BOTH',
                        $relatedAccount,
                        $relatedCustomerId,
                        $name,
                        $netOutstanding,
                        'Net outstanding amount is zero after PMS positive and negative balances cancelled out.',
                        [
                            'loan_details' => $row->toArray(),
                            'pms' => $pms,
                        ],
                        $now
                    );
                    continue;
                }

                if ($netOutstanding > 0) {
                    $this->addException(
                        $exceptions,
                        $run->id,
                        'POSITIVE_OUTSTANDING',
                        'BOTH',
                        $relatedAccount,
                        $relatedCustomerId,
                        $name,
                        $netOutstanding,
                        'Net outstanding amount is positive and was excluded from the Loan Book.',
                        [
                            'loan_details' => $row->toArray(),
                            'pms' => $pms,
                        ],
                        $now
                    );
                    continue;
                }
            }

            foreach ($pmsGrouped as $key => $pms) {
                if (!isset($usedPmsKeys[$key])) {
                    $this->addException(
                        $exceptions,
                        $run->id,
                        'MISSING_IN_LOAN_DETAILS',
                        'PMS',
                        $pms['related_account'],
                        $pms['related_customer_id'],
                        $pms['name'],
                        $pms['net_outstanding_amount'],
                        'PMS outstanding balance exists but there is no matching row in Loans Details Report.',
                        $pms,
                        $now
                    );
                }
            }

            /*
             * Merge Portfolio overdraft/write-off records into final Loan Book.
             */
            foreach ($portfolioResult['entries'] as $portfolioEntry) {
                $supplementaryEntries[] = $this->buildSupplementaryEntry(
                    $run->id,
                    $portfolioEntry,
                    $now
                );
            }

            /*
             * Merge Credit Card negative outstanding records into final Loan Book.
             */
            foreach ($creditCardsResult['entries'] as $creditCardEntry) {
                $supplementaryEntries[] = $this->buildSupplementaryEntry(
                    $run->id,
                    $creditCardEntry,
                    $now
                );
            }

            /*
             * Merge LMS digital/micro-staff loan records into final Loan Book.
             */
            foreach ($lmsResult['entries'] as $lmsEntry) {
                $supplementaryEntries[] = $this->buildSupplementaryEntry(
                    $run->id,
                    $lmsEntry,
                    $now
                );
            }

            $this->insertInChunks('loan_book_entries', $entries);
            $this->insertInChunks('loan_book_entries', $supplementaryEntries);
            $this->insertInChunks('loan_book_exceptions', $exceptions);

            $totalPmsNetOutstanding = array_sum(array_map(function ($item) {
                return $item['net_outstanding_amount'];
            }, $pmsGrouped));

            $totalPmsNegativeOutstanding = array_sum(array_map(function ($item) {
                return $item['net_outstanding_amount'] < 0 ? abs($item['net_outstanding_amount']) : 0;
            }, $pmsGrouped));

            $allEntries = array_merge($entries, $supplementaryEntries);

            $totalLoanBookOutstanding = array_sum(array_map(function ($item) {
                return isset($item['loan_book_outstanding']) ? (float) $item['loan_book_outstanding'] : 0;
            }, $allEntries));

            /*
             * Control difference compares PMS negative balance only against total final loan book.
             * Since Portfolio/Credit Cards are now added, this control difference may no longer be zero.
             */
            $controlDifference = round($totalPmsNegativeOutstanding - $totalLoanBookOutstanding, 2);

            $controlSummary = [
                'total_pms_rows_imported' => $pmsCount,
                'total_loan_details_rows_imported' => $detailsCount,

                'portfolio_file' => $portfolioResult['original_filename'],
                'portfolio_rows_read' => $portfolioResult['total_rows_read'],
                'portfolio_rows_selected' => $portfolioResult['total_rows_selected'],

                'credit_cards_file' => $creditCardsResult['original_filename'],
                'credit_card_rows_read' => $creditCardsResult['total_rows_read'],
                'credit_card_rows_selected' => $creditCardsResult['total_rows_selected'],

                'lms_file' => $lmsResult['original_filename'],
                'lms_rows_read' => $lmsResult['total_rows_read'],
                'lms_rows_selected' => $lmsResult['total_rows_selected'],

                'total_pms_unique_loans' => count($pmsGrouped),
                'total_final_loan_book_rows' => count($allEntries),
                'total_exceptions' => count($exceptions),
                'total_pms_net_outstanding' => round($totalPmsNetOutstanding, 2),
                'total_pms_negative_outstanding' => round($totalPmsNegativeOutstanding, 2),
                'total_loan_book_outstanding' => round($totalLoanBookOutstanding, 2),
                'control_difference' => $controlDifference,
            ];

            $run->update([
                'total_pms_rows' => $pmsCount,
                'total_loan_details_rows' => $detailsCount,

                'portfolio_original_filename' => $portfolioResult['original_filename'],
                'credit_cards_original_filename' => $creditCardsResult['original_filename'],

                'total_portfolio_rows_read' => $portfolioResult['total_rows_read'],
                'total_portfolio_rows_selected' => $portfolioResult['total_rows_selected'],

                'total_credit_card_rows_read' => $creditCardsResult['total_rows_read'],
                'total_credit_card_rows_selected' => $creditCardsResult['total_rows_selected'],

                'lms_original_filename' => $lmsResult['original_filename'],
                'total_lms_rows_read' => $lmsResult['total_rows_read'],
                'total_lms_rows_selected' => $lmsResult['total_rows_selected'],

                'total_final_loan_book_rows' => count($allEntries),
                'total_exceptions' => count($exceptions),
                'total_pms_net_outstanding' => round($totalPmsNetOutstanding, 2),
                'total_pms_negative_outstanding' => round($totalPmsNegativeOutstanding, 2),
                'total_loan_book_outstanding' => round($totalLoanBookOutstanding, 2),
                'control_difference' => $controlDifference,
                'control_summary' => $controlSummary,
                'status' => 'completed',
                'processed_at' => $now,
                'processed_by' => $userId ?: $run->processed_by,
            ]);

            return $run->fresh();
        });
    }

    protected function groupPmsRows(LoanBookRun $run, $pmsRows, array &$exceptions, Carbon $now)
    {
        $grouped = [];
        $deferredBlankAccount = [];

        foreach ($pmsRows as $row) {
            $relatedAccount = $row->related_account;
            $relatedCustomerId = $row->related_customer_id;
            $name = $row->name;
            $glCode = $row->gl_code;
            $amount = $row->outstanding_amount;

            if (!$relatedAccount) {
                // If we also have no CID, it's unresolvable — log immediately.
                if (!$relatedCustomerId) {
                    $this->addException(
                        $exceptions,
                        $run->id,
                        'BLANK_RELATED_ACCOUNT',
                        'PMS',
                        null,
                        null,
                        $name,
                        $amount,
                        'Related Account is blank in PMS Loan Proofing Report.',
                        $row->toArray(),
                        $now
                    );
                    continue;
                }
                // Has a CID — defer so we can try to match it against grouped rows below.
                $deferredBlankAccount[] = $row;
                continue;
            }

            if (!$relatedCustomerId) {
                $this->addException(
                    $exceptions,
                    $run->id,
                    'BLANK_CUSTOMER_ID',
                    'PMS',
                    $relatedAccount,
                    null,
                    $name,
                    $amount,
                    'Related Customer Id is blank in PMS Loan Proofing Report.',
                    $row->toArray(),
                    $now
                );
                continue;
            }

            if ($amount === null) {
                $this->addException(
                    $exceptions,
                    $run->id,
                    'INVALID_AMOUNT',
                    'PMS',
                    $relatedAccount,
                    $relatedCustomerId,
                    $name,
                    null,
                    'Outstanding Amount is blank, invalid, or N/A in PMS Loan Proofing Report.',
                    $row->toArray(),
                    $now
                );
                continue;
            }

            $key = $this->normalizer->key($relatedAccount, $relatedCustomerId);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'related_account' => $relatedAccount,
                    'related_customer_id' => $relatedCustomerId,
                    'name' => $name,
                    'net_outstanding_amount' => 0,
                    'gl_codes' => [],
                    'row_count' => 0,
                ];
            }

            $grouped[$key]['net_outstanding_amount'] += (float) $amount;
            $grouped[$key]['row_count']++;

            if ($glCode) {
                $grouped[$key]['gl_codes'][$glCode] = true;
            }

            if (!$grouped[$key]['name'] && $name) {
                $grouped[$key]['name'] = $name;
            }
        }

        // Resolve deferred blank-account rows (have a CID but no Related Account).
        // Strategy:
        //   1. One group exists for that CID  → net the amount straight in.
        //   2. Multiple groups exist           → find the group whose running net is
        //      exactly offset by this amount (i.e. they would net to zero); if found
        //      net it in, otherwise log BLANK_RELATED_ACCOUNT.
        //   3. No group exists for that CID   → log BLANK_RELATED_ACCOUNT.
        foreach ($deferredBlankAccount as $row) {
            $cid    = $row->related_customer_id;
            $amount = $row->outstanding_amount;
            $glCode = $row->gl_code;
            $name   = $row->name;

            $matchingKeys = array_keys(
                array_filter($grouped, fn($g) => $g['related_customer_id'] === $cid)
            );

            if (count($matchingKeys) === 1) {
                $key = $matchingKeys[0];
                $grouped[$key]['net_outstanding_amount'] += (float) $amount;
                $grouped[$key]['row_count']++;
                if ($glCode) {
                    $grouped[$key]['gl_codes'][$glCode] = true;
                }
            } elseif (count($matchingKeys) > 1) {
                $matched = false;
                foreach ($matchingKeys as $key) {
                    if (round($grouped[$key]['net_outstanding_amount'] + (float) $amount, 2) === 0.0) {
                        $grouped[$key]['net_outstanding_amount'] += (float) $amount;
                        $grouped[$key]['row_count']++;
                        if ($glCode) {
                            $grouped[$key]['gl_codes'][$glCode] = true;
                        }
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    $this->addException(
                        $exceptions,
                        $run->id,
                        'BLANK_RELATED_ACCOUNT',
                        'PMS',
                        null,
                        $cid,
                        $name,
                        $amount,
                        'Related Account is blank in PMS Loan Proofing Report and amount could not be matched to a single loan account.',
                        $row->toArray(),
                        $now
                    );
                }
            } else {
                $this->addException(
                    $exceptions,
                    $run->id,
                    'BLANK_RELATED_ACCOUNT',
                    'PMS',
                    null,
                    $cid,
                    $name,
                    $amount,
                    'Related Account is blank in PMS Loan Proofing Report.',
                    $row->toArray(),
                    $now
                );
            }
        }

        foreach ($grouped as $key => $item) {
            $grouped[$key]['net_outstanding_amount'] = round($item['net_outstanding_amount'], 2);
            $grouped[$key]['gl_codes'] = array_keys($item['gl_codes']);
        }

        return $grouped;
    }

    protected function buildEntry($runId, LoanBookDetailsStaging $row, array $pms, $netOutstanding, Carbon $now)
    {
        $exchRate = $row->exch_rate;

        if ($exchRate === null || $exchRate == 0) {
            $exchRate = 1;
        }

        $loanBookOutstanding = abs($netOutstanding);
        $outstandingAmountLcy = round($loanBookOutstanding * $exchRate, 2);

        return [
            'loan_book_run_id' => $runId,

            'source_report' => 'PMS_LOAN_PROOFING_REPORT',
            'source_type' => 'PMS_LOAN',

            'related_account' => $row->related_account,
            'related_customer_id' => $row->related_customer_id,
            'name' => $row->name,

            'frr' => $row->frr,
            'orr' => $row->orr,
            'account_status' => $row->account_status,

            'value_dt' => $row->value_dt,
            'maturity_date' => $row->maturity_date,

            'linecode' => $row->linecode,
            'branch' => $row->branch,
            'branch_name' => $row->branch,

            'product_type' => $row->product_type,
            'currency' => $row->currency,
            'industrycode' => $row->industrycode,
            'status' => $row->status,

            'interest_rate' => $row->interest_rate,
            'exch_rate' => $exchRate,

            'tenor' => $row->tenor,

            'limit' => $row->limit_amount,
            'limit_lcy' => $row->limit_lcy,

            'group_code' => $row->group_code,
            'sub_sic_code' => $row->sub_sic_code,
            'business_segment' => $row->business_segment,
            'product_code' => $row->product_code,

            'latest_status_change' => $row->latest_status_change,

            'rm_officer' => $row->rm_officer,
            'collateral_code' => $row->collateral_code,

            'pms_gl_codes' => implode(', ', $pms['gl_codes']),

            'net_outstanding_amount' => $netOutstanding,
            'loan_book_outstanding' => $loanBookOutstanding,
            'outstanding_amount_lcy' => $outstandingAmountLcy,

            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    protected function buildSupplementaryEntry($runId, array $row, Carbon $now)
    {
        $rawOutstanding = isset($row['outstanding_amount']) && $row['outstanding_amount'] !== null
            ? (float) $row['outstanding_amount']
            : 0;

        $loanBookOutstanding = abs($rawOutstanding);

        $lcyBalance = isset($row['lcy_curr_balance']) && $row['lcy_curr_balance'] !== null
            ? (float) $row['lcy_curr_balance']
            : null;

        $outstandingAmountLcy = $lcyBalance !== null
            ? abs($lcyBalance)
            : $loanBookOutstanding;

        return [
            'loan_book_run_id' => $runId,

            'source_report' => isset($row['source_report']) ? $row['source_report'] : null,
            'source_type' => isset($row['source_type']) ? $row['source_type'] : null,
            'source_row_number' => isset($row['source_row_number']) ? $row['source_row_number'] : null,

            'related_account' => isset($row['related_account']) ? $row['related_account'] : null,
            'related_customer_id' => isset($row['related_customer_id']) ? $row['related_customer_id'] : null,
            'name' => isset($row['name']) ? $row['name'] : null,

            'branch' => isset($row['branch_name']) ? $row['branch_name'] : null,
            'branch_name' => isset($row['branch_name']) ? $row['branch_name'] : null,

            'frr' => isset($row['frr']) ? $row['frr'] : null,
            'orr' => isset($row['orr']) ? $row['orr'] : null,

            'currency' => isset($row['currency']) ? $row['currency'] : null,
            'contract_currency' => isset($row['contract_currency']) ? $row['contract_currency'] : null,

            'gl_name' => isset($row['gl_name']) ? $row['gl_name'] : null,

            'status' => isset($row['status']) ? $row['status'] : null,
            'status_since' => isset($row['status_since']) ? $row['status_since'] : null,

            'card_account' => isset($row['card_account']) ? $row['card_account'] : null,

            'outstanding_amount' => $rawOutstanding,
            'net_outstanding_amount' => $rawOutstanding,
            'loan_book_outstanding' => $loanBookOutstanding,
            'outstanding_amount_lcy' => $outstandingAmountLcy,

            'lcy_curr_balance' => $lcyBalance,

            'amount_arrears' => isset($row['amount_arrears']) ? $row['amount_arrears'] : null,
            'days_in_arrears' => isset($row['days_in_arrears']) ? $row['days_in_arrears'] : null,
            'pdo_days' => isset($row['pdo_days']) ? $row['pdo_days'] : null,

            'interest_rate' => isset($row['interest_rate']) ? $row['interest_rate'] : null,

            'description' => isset($row['description']) ? $row['description'] : null,

            'lms_loan_account_no' => isset($row['lms_loan_account_no']) ? $row['lms_loan_account_no'] : null,
            'application_ref'     => isset($row['application_ref']) ? $row['application_ref'] : null,

            'principal_outstanding' => isset($row['principal_outstanding']) ? $row['principal_outstanding'] : null,
            'interest_outstanding'  => isset($row['interest_outstanding']) ? $row['interest_outstanding'] : null,
            'penalty_outstanding'   => isset($row['penalty_outstanding']) ? $row['penalty_outstanding'] : null,
            'total_repaid'          => isset($row['total_repaid']) ? $row['total_repaid'] : null,
            'total_fee_revenue'     => isset($row['total_fee_revenue']) ? $row['total_fee_revenue'] : null,
            'dl_fee'                => isset($row['dl_fee']) ? $row['dl_fee'] : null,
            'processing_fee'        => isset($row['processing_fee']) ? $row['processing_fee'] : null,
            'insurance_fee'         => isset($row['insurance_fee']) ? $row['insurance_fee'] : null,
            'excise_duty'           => isset($row['excise_duty']) ? $row['excise_duty'] : null,

            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    protected function addException(array &$exceptions, $runId, $type, $source, $relatedAccount, $relatedCustomerId, $name, $amount, $remarks, $payload, Carbon $now)
    {
        $exceptions[] = [
            'loan_book_run_id' => $runId,
            'exception_type' => $type,
            'source' => $source,
            'related_account' => $relatedAccount,
            'related_customer_id' => $relatedCustomerId,
            'name' => $name,
            'amount' => $amount,
            'remarks' => $remarks,
            'payload' => json_encode($payload),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    protected function insertInChunks($table, array $rows, $chunkSize = 500)
    {
        if (empty($rows)) {
            return;
        }

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
