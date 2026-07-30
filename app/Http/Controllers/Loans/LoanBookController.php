<?php

namespace App\Http\Controllers\Loans;

use App\Exports\Loans\LoanBookExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Loans\LoanBookDetailsUploadRequest;
use App\Http\Requests\Loans\LoanBookPmsUploadRequest;
use App\Http\Requests\Loans\LoanBookProcessRequest;
use App\Models\Loans\LoanBookRun;
use App\Services\Loans\LoanBookImportService;
use App\Services\Loans\LoanBookService;
use Exception;
use Illuminate\Support\Facades\Auth;

class LoanBookController extends Controller
{
    public function index()
    {
        $draftRun = LoanBookRun::withCount(['pmsStaging', 'detailsStaging'])
            ->where('processed_by', Auth::id())
            ->where('status', 'draft')
            ->latest()
            ->first();

        $runs = LoanBookRun::orderBy('created_at', 'desc')
            ->where('status', 'completed')
            ->where('processed_by', Auth::id())
            ->limit(20)
            ->get();

        return view('loans.loan-book.index', compact('draftRun', 'runs'));
    }

    public function uploadPms(LoanBookPmsUploadRequest $request, LoanBookImportService $service)
    {
        try {
            $run = $service->importPms(
                $request->file('pms_report'),
                Auth::id()
            );

            return redirect()
                ->route('loans.loan-book.index')
                ->with('success', 'PMS Loan Proofing Report uploaded and staged successfully. Rows staged: ' . number_format($run->total_pms_rows));
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function uploadLoanDetails(LoanBookDetailsUploadRequest $request, LoanBookImportService $service)
    {
        try {
            $run = $service->importLoanDetails(
                $request->file('loan_details_report'),
                Auth::id()
            );

            return redirect()
                ->route('loans.loan-book.index')
                ->with('success', 'Loans Details Report uploaded and staged successfully. Rows staged: ' . number_format($run->total_loan_details_rows));
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function process(LoanBookProcessRequest $request, LoanBookService $service)
    {
        try {
            $run = LoanBookRun::where('processed_by', Auth::id())
                ->where('status', 'draft')
                ->latest()
                ->first();

            if (!$run) {
                return redirect()
                    ->back()
                    ->with('error', 'No staged files found. Please upload PMS and Loans Details reports first.');
            }

            $processedRun = $service->processDraftRun(
                $run,
                $request->input('loan_book_date'),
                Auth::id(),
                $request->file('portfolio_report'),
                $request->file('credit_cards_report'),
                $request->file('lms_report')
            );

            return redirect()
                ->route('loans.loan-book.show', $processedRun->id)
                ->with('success', 'Loan Book generated successfully.');
        } catch (Exception $e) {
            if (isset($run) && $run) {
                $run->update([
                    'status' => 'draft',
                    'failure_reason' => mb_substr($e->getMessage(), 0, 60000),
                ]);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show($runId)
    {
        $run = LoanBookRun::where('processed_by', Auth::id())
            ->findOrFail($runId);

        $entries = $run->entries()
            ->orderBy('related_account')
            ->paginate(50, ['*'], 'entries_page');

        $exceptions = $run->exceptions()
            ->orderBy('exception_type')
            ->orderBy('related_account')
            ->paginate(50, ['*'], 'exceptions_page');

        return view('loans.loan-book.show', compact('run', 'entries', 'exceptions'));
    }

    public function download($runId, LoanBookExport $export)
    {
        $run = LoanBookRun::with(['entries', 'exceptions'])
            ->where('processed_by', Auth::id())
            ->where('status', 'completed')
            ->findOrFail($runId);

        $fileName = 'Loan_Book_' . $run->batch_reference . '.xlsx';

        $filePath = $export->generate($run, $fileName);

        return response()
            ->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }
}
