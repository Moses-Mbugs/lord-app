@extends('layouts.loans.template')

@section('title', 'Generate Loan Book')
@section('page_title', 'Generate Loan Book')
@section('page_subtitle', 'Stage PMS and Loan Details reports, then process the Loan Book with Portfolio, Credit Card, and Digital Lending extracts')


@section('content')

    <div class="loan-page-hero">
        <h1>Loan Book Generator</h1>
        <p>
            Upload PMS and Loans Details reports for staging. During processing, attach Portfolio Account, Credit Cards,
            and Digital Lending reports so the system can extract qualifying overdraft, write-off, credit card, and
            digital loan exposures into the final Loan Book.
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

    @if ($errors->any())
        <div class="alert alert-danger loan-alert">
            <strong>Please correct the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="loan-metric-grid">
        <div class="loan-metric-card">
            <div class="loan-metric-label">PMS Staged Rows</div>
            <div class="loan-metric-value">
                {{ $draftRun ? number_format($draftRun->pms_staging_count) : '0' }}
            </div>
        </div>

        <div class="loan-metric-card">
            <div class="loan-metric-label">Loan Details Staged Rows</div>
            <div class="loan-metric-value">
                {{ $draftRun ? number_format($draftRun->details_staging_count) : '0' }}
            </div>
        </div>

        <div class="loan-metric-card">
            <div class="loan-metric-label">Draft Batch</div>
            <div class="loan-metric-value" style="font-size: 15px;">
                {{ $draftRun ? $draftRun->batch_reference : 'No Draft' }}
            </div>
        </div>

        <div class="loan-metric-card">
            <div class="loan-metric-label">Ready To Process</div>
            <div class="loan-metric-value">
                @if ($draftRun && $draftRun->pms_staging_count > 0 && $draftRun->details_staging_count > 0)
                    Yes
                @else
                    No
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="loan-card">
                <div class="loan-card-header">
                    <div>
                        <h3>Step 1: Upload PMS Loan Proofing Report</h3>
                        <div class="loan-card-subtitle">
                            This report provides the outstanding amounts.
                        </div>
                    </div>
                </div>

                <form action="{{ route('loans.loan-book.upload-pms') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label class="form-label">PMS Loan Proofing Report</label>
                        <input type="file" name="pms_report" class="form-control" required>

                        <div class="loan-help-text">
                            Required columns: GL Code, Related Account, Related Customer Id, Name, Outstanding Amount.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-loan-primary">
                        Upload & Stage PMS
                    </button>
                </form>

                @if ($draftRun && $draftRun->pms_original_filename)
                    <hr>
                    <div class="loan-help-text">
                        Current PMS file:
                        <strong>{{ $draftRun->pms_original_filename }}</strong>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-md-6">
            <div class="loan-card">
                <div class="loan-card-header">
                    <div>
                        <h3>Step 2: Upload Loans Details Report</h3>
                        <div class="loan-card-subtitle">
                            This report provides the base Loan Book structure.
                        </div>
                    </div>
                </div>

                <form action="{{ route('loans.loan-book.upload-loan-details') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label class="form-label">Loans Details Report</label>
                        <input type="file" name="loan_details_report" class="form-control" required>

                        <div class="loan-help-text">
                            Required columns include Related Account, Customer Id, Name, Currency, Product Type and other
                            loan details.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-loan-primary">
                        Upload & Stage Loan Details
                    </button>
                </form>

                @if ($draftRun && $draftRun->loan_details_original_filename)
                    <hr>
                    <div class="loan-help-text">
                        Current Loan Details file:
                        <strong>{{ $draftRun->loan_details_original_filename }}</strong>
                    </div>
                @endif
            </div>
        </div>
        <div class="loan-card">
            <div class="loan-card-header">
                <div>
                    <h3>Step 3: Attach Supplementary Reports</h3>
                    <div class="loan-card-subtitle">
                        Attach the Portfolio Account, Credit Cards, and Digital Lending reports. These are read during
                        processing to extract qualifying overdraft, write-off, credit card, and digital loan exposures.
                        Raw rows are not saved.
                    </div>
                </div>
            </div>

            <form action="{{ route('loans.loan-book.process') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Portfolio Account Report <span class="text-danger">*</span></label>
                        <input type="file" name="portfolio_report" class="form-control" accept=".xls,.xlsx,.csv" required>

                        <div class="loan-help-text">
                            Used for overdrafts and write-offs. The system checks GL Name and picks LCY Curr Balance.
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Credit Cards Report <span class="text-danger">*</span></label>
                        <input type="file" name="credit_cards_report" class="form-control" accept=".xls,.xlsx,.csv" required>

                        <div class="loan-help-text">
                            Used for credit card accounts with a negative Outstanding Amount.
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Digital Lending Report <span class="text-danger">*</span></label>
                        <input type="file" name="lms_report" class="form-control" accept=".xls,.xlsx,.csv" required>

                        <div class="loan-help-text">
                            Digital loan portfolio export. Only ACTIVE loans are extracted using Total Outstanding.
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Loan Book Date</label>
                        <input type="date" name="loan_book_date" class="form-control"
                            value="{{ old('loan_book_date', date('Y-m-d')) }}">

                        <div class="loan-help-text">Official as-at date for this Loan Book run.</div>
                    </div>
                </div>

                <div class="loan-help-text" style="margin-bottom: 15px;">
                    <strong>Steps 1 and 2 must be completed before generating the Loan Book.</strong>
                    Portfolio, Credit Cards, and Digital Lending reports are read in memory only and are not stored.
                </div>

                <button type="submit" class="btn btn-loan-primary"
                    @if (!$draftRun || $draftRun->pms_staging_count == 0 || $draftRun->details_staging_count == 0) disabled @endif>
                    Generate Loan Book
                </button>
            </form>
        </div>
    </div>

    <div class="loan-card">
        <div class="loan-card-header">
            <div>
                <h3>Recent Completed Loan Book Runs</h3>
                <div class="loan-card-subtitle">
                    View generated Loan Books, summaries and exceptions.
                </div>
            </div>
        </div>

        <div class="loan-table-wrap">
            <table class="loan-table">
                <thead>
                    <tr>
                        <th>Batch Reference</th>
                        <th>Loan Book Date</th>
                        <th>Status</th>
                        <th>Loan Book Rows</th>
                        <th>Exceptions</th>
                        <th>Total Outstanding</th>
                        <th>Processed At</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td>{{ $run->batch_reference }}</td>
                            <td>{{ $run->loan_book_date ? $run->loan_book_date->format('Y-m-d') : '-' }}</td>
                            <td>
                                <span class="loan-badge loan-badge-success">
                                    {{ $run->status }}
                                </span>
                            </td>
                            <td>{{ number_format($run->total_final_loan_book_rows) }}</td>
                            <td>{{ number_format($run->total_exceptions) }}</td>
                            <td>{{ number_format($run->total_loan_book_outstanding, 2) }}</td>
                            <td>{{ $run->processed_at ? $run->processed_at->format('Y-m-d H:i') : '-' }}</td>
                            <td>
                                <a href="{{ route('loans.loan-book.show', $run->id) }}"
                                    class="btn btn-sm btn-loan-primary">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="loan-empty">
                                No completed Loan Book runs yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
