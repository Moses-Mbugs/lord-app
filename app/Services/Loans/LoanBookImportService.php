<?php

namespace App\Services\Loans;

use App\Models\Loans\LoanBookDetailsStaging;
use App\Models\Loans\LoanBookPmsStaging;
use App\Models\Loans\LoanBookRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoanBookImportService
{
    protected $parser;
    protected $validator;
    protected $normalizer;

    public function __construct(
        ExcelParserService $parser,
        ColumnValidatorService $validator,
        LoanBookNormalizerService $normalizer
    ) {
        $this->parser = $parser;
        $this->validator = $validator;
        $this->normalizer = $normalizer;
    }

    public function importPms($file, $userId)
    {
        return DB::transaction(function () use ($file, $userId) {
            $run = $this->getOrCreateDraftRun($userId);

            LoanBookPmsStaging::where('loan_book_run_id', $run->id)->delete();

            $config = config('loan_book.pms');

            $parsed = $this->parser->parse(
                $file,
                $config['aliases'],
                $config['required']
            );

            $this->validator->validate(
                $parsed['headers'],
                $config['required'],
                'PMS Loan Proofing Report'
            );

            $now = Carbon::now();
            $rows = [];

            foreach ($parsed['rows'] as $index => $row) {
                $rows[] = [
                    'loan_book_run_id' => $run->id,
                    'processed_by' => $userId,
                    'row_number' => $index + $parsed['header_row_number'] + 1,

                    'gl_code' => $this->normalizer->text(isset($row['gl_code']) ? $row['gl_code'] : null),
                    'related_account' => $this->normalizer->account(isset($row['related_account']) ? $row['related_account'] : null),
                    'related_customer_id' => $this->normalizer->customerId(isset($row['related_customer_id']) ? $row['related_customer_id'] : null),
                    'name' => $this->normalizer->text(isset($row['name']) ? $row['name'] : null),
                    'outstanding_amount' => $this->normalizer->amount(isset($row['outstanding_amount']) ? $row['outstanding_amount'] : null),

                    'raw_payload' => json_encode($row),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->insertInChunks('loan_book_pms_stagings', $rows);

            $run->update([
                'pms_original_filename' => $file->getClientOriginalName(),
                'total_pms_rows' => count($rows),
                'status' => 'draft',
                'failure_reason' => null,
            ]);

            return $run->fresh();
        });
    }

    public function importLoanDetails($file, $userId)
    {
        return DB::transaction(function () use ($file, $userId) {
            $run = $this->getOrCreateDraftRun($userId);

            LoanBookDetailsStaging::where('loan_book_run_id', $run->id)->delete();

            $config = config('loan_book.loan_details');

            $parsed = $this->parser->parse(
                $file,
                $config['aliases'],
                $config['required']
            );

            $this->validator->validate(
                $parsed['headers'],
                $config['required'],
                'Loans Details Report'
            );

            $now = Carbon::now();
            $rows = [];

            foreach ($parsed['rows'] as $index => $row) {
                $rows[] = [
                    'loan_book_run_id' => $run->id,
                    'processed_by' => $userId,
                    'row_number' => $index + $parsed['header_row_number'] + 1,

                    'related_account' => $this->normalizer->account(isset($row['related_account']) ? $row['related_account'] : null),
                    'related_customer_id' => $this->normalizer->customerId(isset($row['related_customer_id']) ? $row['related_customer_id'] : null),
                    'name' => $this->normalizer->text(isset($row['name']) ? $row['name'] : null),

                    'frr' => $this->normalizer->text(isset($row['frr']) ? $row['frr'] : null),
                    'orr' => $this->normalizer->text(isset($row['orr']) ? $row['orr'] : null),
                    'account_status' => $this->normalizer->text(isset($row['account_status']) ? $row['account_status'] : null),

                    'value_dt' => $this->normalizer->date(isset($row['value_dt']) ? $row['value_dt'] : null),
                    'maturity_date' => $this->normalizer->date(isset($row['maturity_date']) ? $row['maturity_date'] : null),

                    'linecode' => $this->normalizer->text(isset($row['linecode']) ? $row['linecode'] : null),
                    'branch' => $this->normalizer->text(isset($row['branch']) ? $row['branch'] : null),
                    'product_type' => $this->normalizer->text(isset($row['product_type']) ? $row['product_type'] : null),
                    'currency' => $this->normalizer->upperText(isset($row['currency']) ? $row['currency'] : null),
                    'industrycode' => $this->normalizer->text(isset($row['industrycode']) ? $row['industrycode'] : null),
                    'status' => $this->normalizer->text(isset($row['status']) ? $row['status'] : null),

                    'interest_rate' => $this->normalizer->decimal(isset($row['interest_rate']) ? $row['interest_rate'] : null, 6),
                    'exch_rate' => $this->normalizer->decimal(isset($row['exch_rate']) ? $row['exch_rate'] : null, 6),

                    'tenor' => $this->normalizer->text(isset($row['tenor']) ? $row['tenor'] : null),

                    'limit_amount' => $this->normalizer->amount(isset($row['limit']) ? $row['limit'] : null),
                    'limit_lcy' => $this->normalizer->amount(isset($row['limit_lcy']) ? $row['limit_lcy'] : null),

                    'group_code' => $this->normalizer->text(isset($row['group_code']) ? $row['group_code'] : null),
                    'sub_sic_code' => $this->normalizer->text(isset($row['sub_sic_code']) ? $row['sub_sic_code'] : null),
                    'business_segment' => $this->normalizer->text(isset($row['business_segment']) ? $row['business_segment'] : null),
                    'product_code' => $this->normalizer->text(isset($row['product_code']) ? $row['product_code'] : null),

                    'latest_status_change' => $this->normalizer->date(isset($row['latest_status_change']) ? $row['latest_status_change'] : null),

                    'rm_officer' => $this->normalizer->text(isset($row['rm_officer']) ? $row['rm_officer'] : null),
                    'collateral_code' => $this->normalizer->text(isset($row['collateral_code']) ? $row['collateral_code'] : null),

                    'raw_payload' => json_encode($row),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->insertInChunks('loan_book_details_stagings', $rows);

            $run->update([
                'loan_details_original_filename' => $file->getClientOriginalName(),
                'total_loan_details_rows' => count($rows),
                'status' => 'draft',
                'failure_reason' => null,
            ]);

            return $run->fresh();
        });
    }

    public function getOrCreateDraftRun($userId)
    {
        $draft = LoanBookRun::where('processed_by', $userId)
            ->where('status', 'draft')
            ->latest()
            ->first();

        if ($draft) {
            return $draft;
        }

        /*
         * Clean previous staging data for this user before a fresh draft starts.
         * This keeps the DB light but preserves completed final Loan Book entries.
         */
        LoanBookPmsStaging::where('processed_by', $userId)->delete();
        LoanBookDetailsStaging::where('processed_by', $userId)->delete();

        return LoanBookRun::create([
            'batch_reference' => $this->generateBatchReference(),
            'loan_book_date' => Carbon::now()->format('Y-m-d'),
            'status' => 'draft',
            'processed_by' => $userId,
        ]);
    }

    protected function insertInChunks($table, array $rows, $chunkSize = 500)
    {
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    protected function generateBatchReference()
    {
        return 'LB-' . Carbon::now()->format('Ymd-His') . '-' . strtoupper(Str::random(5));
    }
}
