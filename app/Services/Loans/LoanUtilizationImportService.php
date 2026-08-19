<?php

namespace App\Services\Loans;

use App\Models\Loans\LoanUtilizationSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoanUtilizationImportService
{
    public function __construct(
        protected ExcelParserService $parser,
        protected LoanBookNormalizerService $normalizer,
        protected LoanUtilizationCategorizationService $categorizer,
    ) {
    }

    public function import($file, ?int $userId): LoanUtilizationSnapshot
    {
        $originalName = $file->getClientOriginalName();

        $snapshot = LoanUtilizationSnapshot::create([
            'batch_reference' => $this->generateBatchReference(),
            'source_filename' => $originalName,
            'as_of_date' => $this->extractAsOfDate($originalName),
            'status' => 'pending',
            'uploaded_by' => $userId,
        ]);

        try {
            $config = config('loan_utilization');

            $parsed = $this->parser->parse($file, $config['aliases'], $config['required']);

            if ($parsed['data_row_count'] === 0) {
                throw new \Exception('No data rows were found in the uploaded file.');
            }

            $now = Carbon::now();
            $rows = [];
            $totalExposure = 0.0;

            foreach ($parsed['rows'] as $row) {
                $contractNo = $this->normalizer->account($row['contract_no'] ?? null);

                if ($contractNo === null) {
                    continue;
                }

                $creditLine = $this->normalizer->text($row['credit_line'] ?? null);
                $glName = $this->normalizer->text($row['gl_name'] ?? null);
                $industrySegment = $this->normalizer->text($row['industry_segment'] ?? null);
                $businessSegment = $this->normalizer->text($row['business_segment'] ?? null);
                $status = $this->normalizer->upperText($row['user_status'] ?? null);
                $frr = $this->normalizer->text($row['frr'] ?? null);
                $orr = $this->normalizer->text($row['orr'] ?? null);

                $productName = $this->categorizer->productName($creditLine, $glName, $industrySegment);
                $stage = $this->categorizer->stage($glName, $status);
                $performanceStatus = $this->categorizer->performanceStatus($stage);
                $classificationLabel = $this->categorizer->classificationLabel($status);
                $business = $this->categorizer->decodeBusiness($businessSegment);

                $exposure = $this->normalizer->amount($row['exposure_lcy'] ?? null) ?? 0.0;
                $dpd = max(0, (int) ($this->normalizer->amount($row['past_due_days'] ?? null) ?? 0));

                $totalExposure += $exposure;

                $rows[] = [
                    'snapshot_id' => $snapshot->id,
                    'account_reference' => $contractNo,
                    'customer_name' => $this->normalizer->text($row['account_name'] ?? null),
                    'product_name' => $productName,
                    'credit_line_code' => $creditLine,
                    'gross_outstanding_lcy' => $exposure,
                    'dpd' => $dpd,
                    'classification_code' => $status,
                    'risk_rating' => trim(($frr ?? '') . '/' . ($orr ?? ''), '/'),
                    'classification_label' => $classificationLabel,
                    'ifrs9_stage' => $stage,
                    'performance_status' => $performanceStatus,
                    'value_date' => $this->normalizer->date($row['value_dt'] ?? null),
                    'business' => $business,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (empty($rows)) {
                throw new \Exception('No valid loan rows (with a Contract No) were found in the uploaded file.');
            }

            DB::transaction(function () use ($snapshot, $rows) {
                $this->insertInChunks('loan_utilization_entries', $rows);
            });

            $snapshot->update([
                'status' => 'completed',
                'total_rows' => count($rows),
                'total_exposure_lcy' => $totalExposure,
                'processed_at' => Carbon::now(),
            ]);

            return $snapshot->fresh();
        } catch (\Throwable $e) {
            $snapshot->update([
                'status' => 'failed',
                'failure_reason' => mb_substr($e->getMessage(), 0, 60000),
            ]);

            throw $e;
        }
    }

    protected function extractAsOfDate(string $filename): string
    {
        if (preg_match('/(\d{1,2})[.\-_](\d{1,2})[.\-_](\d{4})/', $filename, $m)) {
            try {
                return Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->format('Y-m-d');
            } catch (\Throwable $e) {
                // fall through to today
            }
        }

        return Carbon::now()->format('Y-m-d');
    }

    protected function insertInChunks(string $table, array $rows, int $chunkSize = 500): void
    {
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    protected function generateBatchReference(): string
    {
        return 'LU-' . Carbon::now()->format('Ymd-His') . '-' . strtoupper(Str::random(5));
    }
}
