@extends('layouts.loans.template')

@section('title', 'Loan Book Details')
@section('page_title', 'Loan Book Details')
@section('page_subtitle', 'View generated Loan Book entries and exceptions')

@section('content')

    <div class="loan-page-hero">
        <h1>Loan Book Details</h1>
        <p>
            Batch Reference:
            <strong>{{ $run->batch_reference }}</strong>
        </p>
    </div>

    @if (session('success'))
        <div class="alert alert-success loan-alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger loan-alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="loan-metric-grid">
        <div class="loan-metric-card">
            <div class="loan-metric-label">Loan Book Date</div>
            <div class="loan-metric-value" style="font-size: 15px;">
                {{ $run->loan_book_date ? $run->loan_book_date->format('Y-m-d') : '-' }}
            </div>
        </div>

        <div class="loan-metric-card">
            <div class="loan-metric-label">Status</div>
            <div class="loan-metric-value" style="font-size: 15px;">
                {{ strtoupper($run->status) }}
            </div>
        </div>

        <div class="loan-metric-card">
            <div class="loan-metric-label">Loan Book Rows</div>
            <div class="loan-metric-value">
                {{ number_format($run->total_final_loan_book_rows) }}
            </div>
        </div>

        <div class="loan-metric-card">
            <div class="loan-metric-label">Exceptions</div>
            <div class="loan-metric-value">
                {{ number_format($run->total_exceptions) }}
            </div>
        </div>
    </div>

    <div class="loan-card">
        <div class="loan-card-header">
            <div>
                <h3>Summary</h3>
                <div class="loan-card-subtitle">
                    Final generated Loan Book batch summary.
                </div>
            </div>

            <div>
                <a href="{{ route('loans.loan-book.index') }}" class="btn btn-sm btn-secondary">
                    Back
                </a>

                <a href="{{ route('loans.loan-book.download', $run->id) }}" class="btn btn-sm btn-loan-primary">
                    Download Excel
                </a>
            </div>
        </div>

        <div class="loan-table-wrap">
            <table class="loan-table">
                <tbody>
                    <tr>
                        <th>Batch Reference</th>
                        <td>{{ $run->batch_reference }}</td>
                    </tr>
                    <tr>
                        <th>Total Outstanding</th>
                        <td>{{ number_format($run->total_loan_book_outstanding, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Processed At</th>
                        <td>{{ $run->processed_at ? $run->processed_at->format('Y-m-d H:i') : '-' }}</td>
                    </tr>
                    <tr>
                        <th>PMS File</th>
                        <td>{{ $run->pms_original_filename ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>PMS Rows</th>
                        <td>{{ number_format($run->total_pms_rows ?? 0) }}</td>
                    </tr>
                    <tr>
                        <th>Loan Details File</th>
                        <td>{{ $run->loan_details_original_filename ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Loan Details Rows</th>
                        <td>{{ number_format($run->total_loan_details_rows ?? 0) }}</td>
                    </tr>
                    <tr>
                        <th>Portfolio File</th>
                        <td>{{ $run->portfolio_original_filename ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Portfolio Rows Read</th>
                        <td>{{ number_format($run->total_portfolio_rows_read ?? 0) }}</td>
                    </tr>
                    <tr>
                        <th>Portfolio Rows Selected</th>
                        <td>{{ number_format($run->total_portfolio_rows_selected ?? 0) }}</td>
                    </tr>
                    <tr>
                        <th>Credit Cards File</th>
                        <td>{{ $run->credit_cards_original_filename ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Credit Card Rows Read</th>
                        <td>{{ number_format($run->total_credit_card_rows_read ?? 0) }}</td>
                    </tr>
                    <tr>
                        <th>Credit Card Rows Selected</th>
                        <td>{{ number_format($run->total_credit_card_rows_selected ?? 0) }}</td>
                    </tr>
                    <tr>
                        <th>Digital Lending File</th>
                        <td>{{ $run->lms_original_filename ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Digital Lending Rows Read</th>
                        <td>{{ number_format($run->total_lms_rows_read ?? 0) }}</td>
                    </tr>
                    <tr>
                        <th>Digital Lending Rows Selected</th>
                        <td>{{ number_format($run->total_lms_rows_selected ?? 0) }}</td>
                    </tr>
                    <tr>
                        <th>Control Difference</th>
                        <td>{{ number_format($run->control_difference ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="loan-card">
        <div class="loan-card-header">
            <div>
                <h3>Loan Book Entries</h3>
                <div class="loan-card-subtitle">
                    Final records generated from PMS, Portfolio overdraft/write-off, Credit Card, and Digital Lending exposures.
                </div>
            </div>
        </div>

        <div class="loan-table-wrap">
            <table class="loan-table">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Type</th>
                        <th>Related Account</th>
                        <th>Customer ID</th>
                        <th>Name</th>
                        <th>Branch</th>
                        <th>Currency</th>
                        <th>Product Type</th>
                        <th>GL Name</th>
                        <th>Outstanding Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td>{{ $entry->source_report ?? '-' }}</td>
                            <td>{{ $entry->source_type ?? '-' }}</td>
                            <td>{{ $entry->related_account ?? '-' }}</td>
                            <td>{{ $entry->related_customer_id ?? '-' }}</td>
                            <td>{{ $entry->name ?? '-' }}</td>
                            <td>{{ $entry->branch_name ?? ($entry->branch ?? '-') }}</td>
                            <td>{{ $entry->currency ?? ($entry->contract_currency ?? '-') }}</td>
                            <td>{{ $entry->product_type ?? '-' }}</td>
                            <td>{{ $entry->gl_name ?? '-' }}</td>
                            <td>{{ number_format((float) ($entry->loan_book_outstanding ?? ($entry->outstanding_amount ?? 0)), 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="loan-empty">
                                No Loan Book entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $entries->links() }}
        </div>
    </div>

    <div class="loan-card">
        <div class="loan-card-header">
            <div>
                <h3>Exceptions</h3>
                <div class="loan-card-subtitle">
                    Records that could not be matched or processed cleanly.
                </div>
            </div>
        </div>

        <div class="loan-table-wrap">
            <table class="loan-table">
                <thead>
                    <tr>
                        <th>Exception Type</th>
                        <th>Source</th>
                        <th>Related Account</th>
                        <th>Customer ID</th>
                        <th>Name</th>
                        <th>Amount</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exceptions as $exception)
                        <tr>
                            <td>{{ $exception->exception_type ?? '-' }}</td>
                            <td>{{ $exception->source ?? '-' }}</td>
                            <td>{{ $exception->related_account ?? '-' }}</td>
                            <td>{{ $exception->related_customer_id ?? '-' }}</td>
                            <td>{{ $exception->name ?? '-' }}</td>
                            <td>{{ number_format((float) ($exception->amount ?? 0), 2) }}</td>
                            <td>{{ $exception->remarks ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="loan-empty">
                                No exceptions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $exceptions->links() }}
        </div>
    </div>

@endsection
