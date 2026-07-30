<?php

namespace App\Http\Controllers\Finance\customer_profitability;

use App\Http\Controllers\Controller;
use App\Models\customer_profitability\CustomerProfitabilityRecord;
use App\Models\customer_profitability\UploadBatch;
use App\Services\customer_profitability\ExcelParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function __construct(private ExcelParserService $parser) {}

    public function index()
    {
        $batches = UploadBatch::latest()->get();
        return view('finance.customer_profitability.upload.index', compact('batches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ]);

        $file         = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $storedPath   = $file->store('excel/customer_profitability', 'local');
        $fullPath     = Storage::disk('local')->path($storedPath);

        try {
            $parsed = $this->parser->parse($fullPath);

            $batch = DB::transaction(function () use ($parsed, $storedPath, $originalName) {
                $batch = UploadBatch::create([
                    'filename'      => $storedPath,
                    'original_name' => $originalName,
                    'ytd_label'     => $parsed['ytd_label'] ?? null,
                ]);

                $now  = now()->toDateTimeString();

                $ytdRows = array_map(
                    fn($r) => array_merge($r, [
                        'upload_batch_id' => $batch->id,
                        'record_type'     => CustomerProfitabilityRecord::TYPE_YTD,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]),
                    $parsed['ytd']
                );

                $monthlyRows = array_map(
                    fn($r) => array_merge($r, [
                        'upload_batch_id' => $batch->id,
                        'record_type'     => CustomerProfitabilityRecord::TYPE_MONTHLY,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]),
                    $parsed['monthly']
                );

                foreach (array_chunk(array_merge($ytdRows, $monthlyRows), 200) as $chunk) {
                    CustomerProfitabilityRecord::insert($chunk);
                }

                return $batch;
            });

            return redirect()
                ->route('finance.customer_profitability.dashboard', $batch->id)
                ->with('success', "File processed — {$batch->original_name} loaded successfully.");

        } catch (\Throwable $e) {
            Storage::disk('local')->delete($storedPath);
            return redirect()
                ->route('finance.customer_profitability.upload')
                ->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }
}
