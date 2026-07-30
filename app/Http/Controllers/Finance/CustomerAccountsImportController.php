<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CustomerAccountsImport;
use App\Services\DataQueryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerAccountsImportController extends Controller
{
    public function index()
    {
        return view('finance.customer_data');
    }

    public function data(Request $request): JsonResponse
    {
        $columns = [
            0 => 'id',
            1 => 'cust_category',
            2 => 'f12_cif',
            3 => 'cust_ac_no',
            4 => 'ac_desc',
            5 => 'branch_code',
            6 => 'telephone',
            7 => 'e_mail',
            8 => 'lcy_curr_balance',
            9 => 'acy_withdrawable_bal',
            10 => 'record_stat',
            11 => 'ac_open_date',
            12 => 'updated_at',
        ];

        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $searchValue = trim((string) data_get($request->input('search', []), 'value', ''));
        $orderColumnIndex = (int) data_get($request->input('order', []), '0.column', 12);
        $orderDirection = strtolower((string) data_get($request->input('order', []), '0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $columns[$orderColumnIndex] ?? 'updated_at';

        $query = CustomerAccountsImport::query();

        $recordsTotal = CustomerAccountsImport::count();

        if ($searchValue !== '') {
            $query->where(function ($q) use ($searchValue) {
                $q->where('cust_category', 'like', "%{$searchValue}%")
                    ->orWhere('f12_cif', 'like', "%{$searchValue}%")
                    ->orWhere('f12_ac_no', 'like', "%{$searchValue}%")
                    ->orWhere('cust_ac_no', 'like', "%{$searchValue}%")
                    ->orWhere('ac_desc', 'like', "%{$searchValue}%")
                    ->orWhere('branch_code', 'like', "%{$searchValue}%")
                    ->orWhere('telephone', 'like', "%{$searchValue}%")
                    ->orWhere('e_mail', 'like', "%{$searchValue}%")
                    ->orWhere('record_stat', 'like', "%{$searchValue}%")
                    ->orWhere('account_class', 'like', "%{$searchValue}%")
                    ->orWhere('acc_ofcr', 'like', "%{$searchValue}%")
                    ->orWhere('address_line1', 'like', "%{$searchValue}%");
            });
        }

        $recordsFiltered = $query->count();

        $rows = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'id' => $row->id,
                'cust_category' => $row->cust_category,
                'f12_cif' => $row->f12_cif,
                'cust_ac_no' => $row->cust_ac_no,
                'ac_desc' => $row->ac_desc,
                'branch_code' => $row->branch_code,
                'telephone' => $row->telephone,
                'e_mail' => $row->e_mail,
                'lcy_curr_balance' => $row->lcy_curr_balance !== null ? number_format((float) $row->lcy_curr_balance, 2) : '',
                'acy_withdrawable_bal' => $row->acy_withdrawable_bal !== null ? number_format((float) $row->acy_withdrawable_bal, 2) : '',
                'record_stat' => $row->record_stat,
                'ac_open_date' => $row->ac_open_date ? Carbon::parse($row->ac_open_date)->format('Y-m-d') : '',
                'updated_at' => $row->updated_at ? Carbon::parse($row->updated_at)->format('Y-m-d H:i:s') : '',
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function runImport(Request $request, DataQueryService $dataQueryService): JsonResponse
    {

        Log::info('Customer import endpoint hit', [
            'script_name' => $request->input('script_name'),
            'payload' => $request->all(),
        ]);

        $validated = $request->validate([
            'script_name' => 'required|in:INDI_CUSTOMERS,CORP_CUSTOMERS,ALL',
        ]);

        try {
            if ($validated['script_name'] === 'ALL') {
                $individual = $this->runSingleScriptImport('INDI_CUSTOMERS', $dataQueryService);
                $corporate  = $this->runSingleScriptImport('CORP_CUSTOMERS', $dataQueryService);

                $success = $individual['success'] || $corporate['success'];

                return response()->json([
                    'success' => $success,
                    'message' => 'Bulk import completed.',
                    'results' => [
                        $individual,
                        $corporate,
                    ],
                    'inserted' => ($individual['inserted'] ?? 0) + ($corporate['inserted'] ?? 0),
                    'updated' => ($individual['updated'] ?? 0) + ($corporate['updated'] ?? 0),
                    'skipped' => ($individual['skipped'] ?? 0) + ($corporate['skipped'] ?? 0),
                    'total_rows' => ($individual['total_rows'] ?? 0) + ($corporate['total_rows'] ?? 0),
                ]);
            }

            $result = $this->runSingleScriptImport($validated['script_name'], $dataQueryService);



            return response()->json($result, $result['success'] ? 200 : 422);
        } catch (\Throwable $e) {
            Log::error('Customer import failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'details' => $e->getTraceAsString(),
            ], 500);
        }
    }

    private function runSingleScriptImport(string $scriptName, DataQueryService $dataQueryService): array
    {
        try {
            $response = method_exists($dataQueryService, 'getCustomerAccountsImportData')
                ? $dataQueryService->getCustomerAccountsImportData($scriptName)
                : $dataQueryService->executeGtpScript($scriptName, []);

            if ($response instanceof JsonResponse) {
                $payload = $response->getData(true);

                Log::warning('Customer import script returned JsonResponse error', [
                    'script_name' => $scriptName,
                    'payload' => $payload,
                ]);

                return [
                    'success' => false,
                    'script_name' => $scriptName,
                    'message' => $payload['message'] ?? $payload['error'] ?? 'Script execution failed.',
                    'inserted' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'total_rows' => 0,
                ];
            }

            Log::info('Customer import raw response received', [
                'script_name' => $scriptName,
                'response_type' => gettype($response),
                'is_array' => is_array($response),
                'sample' => is_array($response) ? array_slice($response, 0, 2) : $response,
            ]);

            $rows = $this->extractRows($response);

            if (empty($rows)) {
                Log::warning('Customer import returned no extractable rows', [
                    'script_name' => $scriptName,
                    'raw_response' => $response,
                ]);

                return [
                    'success' => false,
                    'script_name' => $scriptName,
                    'message' => 'No valid rows returned from script.',
                    'inserted' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'total_rows' => 0,
                ];
            }

            $inserted = 0;
            $updated = 0;
            $skipped = 0;

            DB::beginTransaction();

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    $skipped++;
                    continue;
                }

                $normalized = $this->normalizeRow($row, $scriptName);
                $identity = $this->resolveIdentity($normalized);

                if (empty($identity)) {
                    Log::warning('Skipped row due to missing identity', [
                        'script_name' => $scriptName,
                        'row' => $row,
                        'normalized' => $normalized,
                    ]);

                    $skipped++;
                    continue;
                }

                $existing = CustomerAccountsImport::where($identity)->first();

                if ($existing) {
                    $existing->fill($normalized);
                    $existing->save();
                    $updated++;
                } else {
                    CustomerAccountsImport::create($normalized);
                    $inserted++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'script_name' => $scriptName,
                'message' => "{$scriptName} imported successfully.",
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped' => $skipped,
                'total_rows' => count($rows),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error("Error importing {$scriptName}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'script_name' => $scriptName,
                'message' => $e->getMessage(),
                'inserted' => 0,
                'updated' => 0,
                'skipped' => 0,
                'total_rows' => 0,
            ];
        }
    }

    private function extractRows($response): array
    {
        if (!is_array($response)) {
            return [];
        }

        // direct array of rows
        if (isset($response[0]) && is_array($response[0])) {
            return $response;
        }

        // common wrappers
        foreach (
            [
                'data',
                'result',
                'results',
                'customers',
                'rows',
                'response',
                'payload',
                'DATA',
                'RESULT',
                'RESULTS',
                'CUSTOMERS',
                'ROWS',
                'RESPONSE',
                'PAYLOAD'
            ] as $key
        ) {
            if (isset($response[$key]) && is_array($response[$key])) {
                if (isset($response[$key][0]) && is_array($response[$key][0])) {
                    return $response[$key];
                }
            }
        }

        // recursive search for first nested list of row arrays
        foreach ($response as $value) {
            if (is_array($value)) {
                $rows = $this->extractRows($value);
                if (!empty($rows)) {
                    return $rows;
                }
            }
        }

        return [];
    }

    private function normalizeRow(array $row, string $scriptName): array
    {
        // make keys case-insensitive
        $row = array_change_key_case($row, CASE_LOWER);

        return [
            'introducer' => $this->firstNonEmpty($row, [
                'introducer',
            ]),

            'cust_category' => $this->firstNonEmpty($row, [
                'cust_category',
                'customer_category',
                'cust_type',
                'category',
            ]) ?? ($scriptName === 'INDI_CUSTOMERS' ? 'INDIVIDUAL' : 'CORPORATE'),

            'eti_cif_class_category' => $this->firstNonEmpty($row, [
                'eti_cif_class_category',
                'cif_class_category',
            ]),

            'etibiseg2' => $this->firstNonEmpty($row, [
                'etibiseg2',
                'segment',
                'customer_segment',
            ]),

            'acc_ofcr' => $this->firstNonEmpty($row, [
                'acc_ofcr',
                'account_officer',
                'rm_code',
                'relationship_manager',
            ]),

            'f12_cif' => $this->firstNonEmpty($row, [
                'f12_cif',
                'cif',
                'cust_id',
                'customer_id',
                'customer_no',
            ]),

            'f12_ac_no' => $this->firstNonEmpty($row, [
                'f12_ac_no',
                'account_no',
                'account_number',
                'ac_no',
            ]),

            'branch_code' => $this->firstNonEmpty($row, [
                'branch_code',
                'branch',
                'sol_id',
            ]),

            'acy_withdrawable_bal' => $this->parseDecimal($this->firstNonEmpty($row, [
                'acy_withdrawable_bal',
                'withdrawable_balance',
                'available_balance',
            ])),

            'cust_ac_no' => $this->firstNonEmpty($row, [
                'cust_ac_no',
                'customer_account_no',
                'cust_account_no',
                'customer_account',
            ]),

            'record_stat' => $this->firstNonEmpty($row, [
                'record_stat',
                'status',
                'record_status',
            ]),

            'account_class' => $this->firstNonEmpty($row, [
                'account_class',
                'ac_class',
                'account_type',
            ]),

            'ac_desc' => $this->firstNonEmpty($row, [
                'ac_desc',
                'account_description',
                'customer_name',
                'customer_desc',
                'name',
                'full_name',
                'company_name',
            ]),

            'ac_open_date' => $this->parseDate($this->firstNonEmpty($row, [
                'ac_open_date',
                'account_open_date',
                'open_date',
            ])),

            'dormancy_date' => $this->parseDate($this->firstNonEmpty($row, [
                'dormancy_date',
                'dormant_date',
            ])),

            'ac_stat_dormant' => $this->firstNonEmpty($row, [
                'ac_stat_dormant',
                'dormant_status',
            ]),

            'address_line1' => $this->firstNonEmpty($row, [
                'address_line1',
                'address',
                'address1',
            ]),

            'lcy_curr_balance' => $this->parseDecimal($this->firstNonEmpty($row, [
                'lcy_curr_balance',
                'lcy_balance',
                'current_balance',
                'ledger_balance',
            ])),

            'cheque_book_facility' => $this->firstNonEmpty($row, [
                'cheque_book_facility',
                'cheque_facility',
            ]),

            'atm_facility' => $this->firstNonEmpty($row, [
                'atm_facility',
            ]),

            'telephone' => $this->firstNonEmpty($row, [
                'telephone',
                'phone',
                'phone_number',
                'mobile',
                'mobile_no',
            ]),

            'e_mail' => $this->firstNonEmpty($row, [
                'e_mail',
                'email',
                'email_address',
            ]),
        ];
    }

    private function resolveIdentity(array $normalized): array
    {
        if (!empty($normalized['cust_ac_no'])) {
            return ['cust_ac_no' => $normalized['cust_ac_no']];
        }

        if (!empty($normalized['f12_ac_no'])) {
            return ['f12_ac_no' => $normalized['f12_ac_no']];
        }

        if (
            !empty($normalized['f12_cif']) &&
            !empty($normalized['branch_code']) &&
            !empty($normalized['account_class'])
        ) {
            return [
                'f12_cif' => $normalized['f12_cif'],
                'branch_code' => $normalized['branch_code'],
                'account_class' => $normalized['account_class'],
            ];
        }

        return [];
    }

    private function firstNonEmpty(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $lookup = strtolower($key);

            if (array_key_exists($lookup, $row) && $row[$lookup] !== null && $row[$lookup] !== '') {
                if (is_scalar($row[$lookup])) {
                    return trim((string) $row[$lookup]);
                }

                return json_encode($row[$lookup]);
            }
        }

        return null;
    }

    private function parseDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = str_replace([',', ' '], '', (string) $value);

        if (!is_numeric($clean)) {
            return null;
        }

        return (float) $clean;
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim((string) $value);

        $formats = [
            'Y-m-d',
            'd-m-Y',
            'd/m/Y',
            'm/d/Y',
            'd-M-Y',
            'd-M-y',
            'Y/m/d',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
