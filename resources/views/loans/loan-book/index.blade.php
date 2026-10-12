@extends('layouts.finance.template')

@section('title', 'Generate Loan Book')

@include('loans.partials.heading-font')

@php
    $pmsCount = $draftRun ? (int) $draftRun->pms_staging_count : 0;
    $detailsCount = $draftRun ? (int) $draftRun->details_staging_count : 0;
    $pmsDone = $pmsCount > 0;
    $detailsDone = $detailsCount > 0;
    $readyToProcess = $pmsDone && $detailsDone;
@endphp

@push('styles')
    <style>
        .loan-step {
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            display: grid;
            place-items: center;
            color: var(--finance-muted);
            background: var(--finance-surface-soft);
            border: 1px solid var(--finance-border-strong);
            border-radius: 50%;
            font-size: 12.5px;
            font-weight: 800;
        }

        .loan-step.is-done {
            color: #ffffff;
            background: var(--finance-green);
            border-color: var(--finance-green);
        }

        .loan-step.is-current {
            color: #ffffff;
            background: var(--finance-primary);
            border-color: var(--finance-primary);
            box-shadow: 0 0 0 4px var(--finance-primary-soft);
        }

        .loan-progress .col + .col {
            border-left: 1px solid var(--finance-border);
        }

        .loan-col-tag {
            display: inline-block;
            margin: 0 4px 4px 0;
            padding: 3px 8px;
            color: var(--finance-muted);
            background: var(--finance-surface-soft);
            border: 1px solid var(--finance-border);
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
        }

        .loan-staged {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            padding: 9px 12px;
            color: var(--finance-muted);
            background: var(--finance-surface-soft);
            border: 1px solid var(--finance-border);
            border-radius: var(--finance-radius-sm);
            font-size: 12.5px;
        }

        .loan-staged strong {
            overflow: hidden;
            color: var(--finance-text-strong);
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 767.98px) {
            .loan-progress .col + .col {
                border-left: 0;
                border-top: 1px solid var(--finance-border);
            }
        }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h4 class="page-title loan-script-title">Generate Loan Book</h4>
            <p class="text-muted small mb-0">
                Stage the PMS and Loans Details reports, then attach the Portfolio, Credit Cards and Digital Lending
                reports to build the final Loan Book.
            </p>
        </div>

        @if ($draftRun)
            <span class="finance-badge finance-badge-info">
                <i class="ti ti-file-upload"></i> Draft {{ $draftRun->batch_reference }}
            </span>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @elseif ($draftRun && $draftRun->failure_reason)
        <div class="alert alert-warning py-2 small" role="alert">
            <strong><i class="ti ti-alert-triangle"></i> The last attempt to generate this draft failed.</strong>
            <div>{{ \Illuminate\Support\Str::limit($draftRun->failure_reason, 300) }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2 small" role="alert">
            <strong>Please correct the following:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Progress --}}
    <div class="card mb-4">
        <div class="row g-0 loan-progress">
            <div class="col-md">
                <div class="d-flex align-items-center gap-3 p-3">
                    <span class="loan-step {{ $pmsDone ? 'is-done' : 'is-current' }}">
                        @if ($pmsDone) <i class="ti ti-check"></i> @else 1 @endif
                    </span>
                    <div>
                        <div class="fw-bold">PMS Loan Proofing</div>
                        <div class="text-muted small">
                            {{ $pmsDone ? number_format($pmsCount) . ' rows staged' : 'Waiting for upload' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md">
                <div class="d-flex align-items-center gap-3 p-3">
                    <span class="loan-step {{ $detailsDone ? 'is-done' : ($pmsDone ? 'is-current' : '') }}">
                        @if ($detailsDone) <i class="ti ti-check"></i> @else 2 @endif
                    </span>
                    <div>
                        <div class="fw-bold">Loans Details</div>
                        <div class="text-muted small">
                            {{ $detailsDone ? number_format($detailsCount) . ' rows staged' : 'Waiting for upload' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md">
                <div class="d-flex align-items-center gap-3 p-3">
                    <span class="loan-step {{ $readyToProcess ? 'is-current' : '' }}">3</span>
                    <div>
                        <div class="fw-bold">Generate Loan Book</div>
                        <div class="text-muted small">
                            {{ $readyToProcess ? 'Ready to generate' : 'Complete steps 1 and 2 first' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Steps 1 & 2 --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="loan-step {{ $pmsDone ? 'is-done' : '' }}">
                            @if ($pmsDone) <i class="ti ti-check"></i> @else 1 @endif
                        </span>
                        <div>
                            <h6 class="card-title mb-0">PMS Loan Proofing Report</h6>
                            <div class="text-muted small">Provides the outstanding amounts.</div>
                        </div>
                    </div>
                    @if ($pmsDone)
                        <span class="finance-badge finance-badge-success">{{ number_format($pmsCount) }} rows</span>
                    @else
                        <span class="finance-badge finance-badge-warning">Not uploaded</span>
                    @endif
                </div>

                <div class="card-body d-flex flex-column">
                    <form action="{{ route('loans.loan-book.upload-pms') }}" method="POST" enctype="multipart/form-data"
                        class="d-flex flex-column flex-grow-1">
                        @csrf

                        <label class="form-label" for="pms_report">Report file <span class="text-danger">*</span></label>
                        @include('loans.partials.file-drop', ['id' => 'pms_report', 'name' => 'pms_report'])

                        <div class="text-muted small mt-3 mb-1">Required columns</div>
                        <div>
                            @foreach (['GL Code', 'Related Account', 'Related Customer Id', 'Name', 'Outstanding Amount'] as $col)
                                <span class="loan-col-tag">{{ $col }}</span>
                            @endforeach
                        </div>

                        @if ($draftRun && $draftRun->pms_original_filename)
                            <div class="loan-staged mt-3">
                                <i class="ti ti-file-spreadsheet"></i>
                                <span>Staged:</span>
                                <strong title="{{ $draftRun->pms_original_filename }}">{{ $draftRun->pms_original_filename }}</strong>
                            </div>
                        @endif

                        <div class="mt-auto pt-3">
                            <button type="submit" class="btn {{ $pmsDone ? 'btn-outline-primary' : 'btn-primary' }}">
                                <i class="ti ti-upload"></i>
                                {{ $pmsDone ? 'Replace & re-stage PMS' : 'Upload & stage PMS' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="loan-step {{ $detailsDone ? 'is-done' : '' }}">
                            @if ($detailsDone) <i class="ti ti-check"></i> @else 2 @endif
                        </span>
                        <div>
                            <h6 class="card-title mb-0">Loans Details Report</h6>
                            <div class="text-muted small">Provides the base Loan Book structure.</div>
                        </div>
                    </div>
                    @if ($detailsDone)
                        <span class="finance-badge finance-badge-success">{{ number_format($detailsCount) }} rows</span>
                    @else
                        <span class="finance-badge finance-badge-warning">Not uploaded</span>
                    @endif
                </div>

                <div class="card-body d-flex flex-column">
                    <form action="{{ route('loans.loan-book.upload-loan-details') }}" method="POST"
                        enctype="multipart/form-data" class="d-flex flex-column flex-grow-1">
                        @csrf

                        <label class="form-label" for="loan_details_report">Report file <span class="text-danger">*</span></label>
                        @include('loans.partials.file-drop', ['id' => 'loan_details_report', 'name' => 'loan_details_report'])

                        <div class="text-muted small mt-3 mb-1">Required columns include</div>
                        <div>
                            @foreach (['Related Account', 'Customer Id', 'Name', 'Currency', 'Product Type', '+ other loan details'] as $col)
                                <span class="loan-col-tag">{{ $col }}</span>
                            @endforeach
                        </div>

                        @if ($draftRun && $draftRun->loan_details_original_filename)
                            <div class="loan-staged mt-3">
                                <i class="ti ti-file-spreadsheet"></i>
                                <span>Staged:</span>
                                <strong title="{{ $draftRun->loan_details_original_filename }}">{{ $draftRun->loan_details_original_filename }}</strong>
                            </div>
                        @endif

                        <div class="mt-auto pt-3">
                            <button type="submit" class="btn {{ $detailsDone ? 'btn-outline-primary' : 'btn-primary' }}">
                                <i class="ti ti-upload"></i>
                                {{ $detailsDone ? 'Replace & re-stage Loan Details' : 'Upload & stage Loan Details' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 3: full-width card, outside the Step 1/2 row --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="loan-step {{ $readyToProcess ? 'is-current' : '' }}">3</span>
                <div>
                    <h6 class="card-title mb-0">Attach Supplementary Reports &amp; Generate</h6>
                    <div class="text-muted small">
                        Read in memory during processing to extract qualifying exposures. Raw rows are not stored.
                    </div>
                </div>
            </div>
            @if ($readyToProcess)
                <span class="finance-badge finance-badge-success">Ready</span>
            @else
                <span class="finance-badge finance-badge-warning">Needs steps 1 &amp; 2</span>
            @endif
        </div>

        <div class="card-body">
            <form action="{{ route('loans.loan-book.process') }}" method="POST" enctype="multipart/form-data"
                id="loanGenerateForm">
                @csrf

                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label" for="portfolio_report">Portfolio Account Report <span class="text-danger">*</span></label>
                        @include('loans.partials.file-drop', ['id' => 'portfolio_report', 'name' => 'portfolio_report'])
                        <div class="text-muted small mt-2">Overdrafts and write-offs. Checks GL Name and picks LCY Curr Balance.</div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label" for="credit_cards_report">Credit Cards Report <span class="text-danger">*</span></label>
                        @include('loans.partials.file-drop', ['id' => 'credit_cards_report', 'name' => 'credit_cards_report'])
                        <div class="text-muted small mt-2">Credit card accounts with a negative Outstanding Amount.</div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label" for="lms_report">Digital Lending Report <span class="text-danger">*</span></label>
                        @include('loans.partials.file-drop', ['id' => 'lms_report', 'name' => 'lms_report'])
                        <div class="text-muted small mt-2">Only ACTIVE loans are extracted, using Total Outstanding.</div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-3 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label" for="loan_book_date">Loan Book Date</label>
                        <input type="date" id="loan_book_date" name="loan_book_date" class="form-control"
                            value="{{ old('loan_book_date', date('Y-m-d')) }}">
                        <div class="text-muted small mt-1">Official as-at date for this run.</div>
                    </div>

                    <div class="col-md d-flex flex-column flex-md-row align-items-md-center justify-content-md-end gap-3">
                        <span class="text-muted small text-md-end">
                            @if ($readyToProcess)
                                Generating can take a few minutes for large files. Please keep this page open.
                            @else
                                Upload and stage the PMS and Loans Details reports to enable generation.
                            @endif
                        </span>
                        <button type="submit" class="btn btn-primary px-4" id="loanGenerateBtn"
                            @unless ($readyToProcess) disabled @endunless>
                            <i class="ti ti-player-play"></i> Generate Loan Book
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Recent runs --}}
    <div class="card">
        <div class="card-header">
            <h6 class="card-title mb-0">Recent Loan Books</h6>
            <div class="text-muted small">Your most recent completed runs (up to 20).</div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Batch Reference</th>
                        <th>Loan Book Date</th>
                        <th>Status</th>
                        <th class="text-end">Rows</th>
                        <th class="text-end">Exceptions</th>
                        <th class="text-end">Total Outstanding</th>
                        <th>Processed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($runs as $run)
                        <tr>
                            <td class="fw-semibold">{{ $run->batch_reference }}</td>
                            <td>{{ $run->loan_book_date ? $run->loan_book_date->format('d M Y') : '—' }}</td>
                            <td>
                                <span class="finance-badge {{ $run->status === 'completed' ? 'finance-badge-success' : 'finance-badge-info' }}">
                                    {{ ucfirst($run->status) }}
                                </span>
                            </td>
                            <td class="text-end">{{ number_format($run->total_final_loan_book_rows) }}</td>
                            <td class="text-end">
                                @if ($run->total_exceptions > 0)
                                    <span class="finance-badge finance-badge-danger">{{ number_format($run->total_exceptions) }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">{{ number_format($run->total_loan_book_outstanding, 2) }}</td>
                            <td class="text-muted small">{{ $run->processed_at ? $run->processed_at->format('d M Y, H:i') : '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('loans.loan-book.show', $run->id) }}" class="btn btn-sm btn-outline-primary">
                                    View <i class="ti ti-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="ti ti-book-2 d-block mb-2" style="font-size: 26px;"></i>
                                No Loan Books yet. Completed runs will appear here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function() {
            var form = document.getElementById('loanGenerateForm');
            var btn = document.getElementById('loanGenerateBtn');
            if (!form || !btn) return;

            // Prevent double submits while the server processes the reports.
            form.addEventListener('submit', function() {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating…';
            });
        })();
    </script>
@endpush
