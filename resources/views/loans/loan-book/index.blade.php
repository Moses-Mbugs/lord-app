@extends('layouts.loans.app')

@section('title', 'Generate Loan Book')
@section('subtitle', 'Stage PMS and Loan Details reports, then process with Portfolio, Credit Card and Digital Lending extracts')

@php
    $pmsCount = $draftRun ? (int) $draftRun->pms_staging_count : 0;
    $detailsCount = $draftRun ? (int) $draftRun->details_staging_count : 0;
    $pmsDone = $pmsCount > 0;
    $detailsDone = $detailsCount > 0;
    $readyToProcess = $pmsDone && $detailsDone;
    $latestRun = $runs->first();
@endphp

@section('content')

    {{-- ======================================================
         HERO
    ======================================================= --}}
    <section class="loan-hero">
        <div class="hero-content">
            <div class="hero-copy">
                <div class="hero-eyebrow">
                    <i class="fas fa-book"></i>
                    Loan Book Generator
                </div>

                <div class="hero-title">Build the Loan Book</div>

                <div class="hero-description">
                    Upload the PMS and Loans Details reports for staging. During processing, attach the Portfolio
                    Account, Credit Cards and Digital Lending reports so qualifying overdraft, write-off, credit card
                    and digital loan exposures are extracted into the final Loan Book.
                </div>
            </div>

            <div class="hero-panel">
                <div class="hero-panel-label">Current Draft</div>
                <div class="hero-panel-value">{{ $draftRun ? $draftRun->batch_reference : 'No draft yet' }}</div>
                <div class="hero-panel-meta">
                    @if ($readyToProcess)
                        <i class="fas fa-circle-check"></i> Ready to generate
                    @else
                        <i class="fas fa-hourglass-half"></i> Waiting for steps 1 &amp; 2
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($draftRun && $draftRun->failure_reason && !session('error'))
        <div class="alert alert-warning" role="alert">
            <i class="fas fa-triangle-exclamation"></i>
            <div>
                <strong>The last attempt to generate this draft failed.</strong>
                <div>{{ \Illuminate\Support\Str::limit($draftRun->failure_reason, 300) }}</div>
            </div>
        </div>
    @endif

    {{-- ======================================================
         KPI TILES
    ======================================================= --}}
    <div class="kpi-grid">
        <div class="kpi {{ $pmsDone ? 'kpi-green' : 'kpi-amber' }}">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <span class="kpi-mini-label">Step 1</span>
            </div>
            <div class="kpi-value">{{ number_format($pmsCount) }}</div>
            <div class="kpi-label">PMS Rows Staged</div>
        </div>

        <div class="kpi {{ $detailsDone ? 'kpi-green' : 'kpi-amber' }}">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fas fa-file-lines"></i></div>
                <span class="kpi-mini-label">Step 2</span>
            </div>
            <div class="kpi-value">{{ number_format($detailsCount) }}</div>
            <div class="kpi-label">Loan Details Rows Staged</div>
        </div>

        <div class="kpi kpi-blue">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fas fa-layer-group"></i></div>
                <span class="kpi-mini-label">History</span>
            </div>
            <div class="kpi-value">{{ number_format($runs->count()) }}</div>
            <div class="kpi-label">Completed Runs</div>
        </div>

        <div class="kpi kpi-purple">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fas fa-sack-dollar"></i></div>
                <span class="kpi-mini-label">
                    {{ $latestRun && $latestRun->loan_book_date ? $latestRun->loan_book_date->format('d M Y') : 'Latest' }}
                </span>
            </div>
            <div class="kpi-value" title="{{ $latestRun ? number_format($latestRun->total_loan_book_outstanding, 2) : '' }}">
                {{ $latestRun ? number_format($latestRun->total_loan_book_outstanding, 0) : '—' }}
            </div>
            <div class="kpi-label">Latest Total Outstanding</div>
        </div>
    </div>

    {{-- ======================================================
         PROGRESS
    ======================================================= --}}
    <div class="steps">
        <div class="step {{ $pmsDone ? 'is-done' : 'is-current' }}">
            <span class="step-dot">@if ($pmsDone)<i class="fas fa-check"></i>@else 1 @endif</span>
            <div>
                <strong>PMS Loan Proofing</strong>
                <span>{{ $pmsDone ? number_format($pmsCount) . ' rows staged' : 'Waiting for upload' }}</span>
            </div>
        </div>

        <div class="step {{ $detailsDone ? 'is-done' : ($pmsDone ? 'is-current' : '') }}">
            <span class="step-dot">@if ($detailsDone)<i class="fas fa-check"></i>@else 2 @endif</span>
            <div>
                <strong>Loans Details</strong>
                <span>{{ $detailsDone ? number_format($detailsCount) . ' rows staged' : 'Waiting for upload' }}</span>
            </div>
        </div>

        <div class="step {{ $readyToProcess ? 'is-current' : '' }}">
            <span class="step-dot">3</span>
            <div>
                <strong>Generate Loan Book</strong>
                <span>{{ $readyToProcess ? 'Ready to generate' : 'Complete steps 1 and 2 first' }}</span>
            </div>
        </div>
    </div>

    {{-- ======================================================
         STEPS 1 & 2
    ======================================================= --}}
    <div class="grid-2">
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">
                    <div class="dashboard-card-title-icon {{ $pmsDone ? 'is-done' : '' }}">
                        @if ($pmsDone)<i class="fas fa-check"></i>@else 1 @endif
                    </div>
                    <div>
                        <h3>PMS Loan Proofing Report</h3>
                        <div class="dashboard-card-subtitle">Provides the outstanding amounts</div>
                    </div>
                </div>
                @if ($pmsDone)
                    <span class="badge badge-success">{{ number_format($pmsCount) }} rows</span>
                @else
                    <span class="badge badge-warning">Not uploaded</span>
                @endif
            </div>

            <form action="{{ route('loans.loan-book.upload-pms') }}" method="POST" enctype="multipart/form-data"
                class="card-body">
                @csrf

                <label class="field-label" for="pms_report">Report File <span class="req">*</span></label>
                @include('loans.partials.file-drop', ['id' => 'pms_report', 'name' => 'pms_report'])

                <div class="help">Required columns</div>
                <div style="margin-top: 5px;">
                    @foreach (['GL Code', 'Related Account', 'Related Customer Id', 'Name', 'Outstanding Amount'] as $col)
                        <span class="tag">{{ $col }}</span>
                    @endforeach
                </div>

                @if ($draftRun && $draftRun->pms_original_filename)
                    <div class="staged-file">
                        <i class="fas fa-file-excel"></i>
                        <span>Staged:</span>
                        <strong title="{{ $draftRun->pms_original_filename }}">{{ $draftRun->pms_original_filename }}</strong>
                    </div>
                @endif

                <div class="card-foot">
                    <button type="submit" class="btn {{ $pmsDone ? 'btn-outline' : 'btn-primary' }}">
                        <i class="fas fa-upload"></i>
                        {{ $pmsDone ? 'Replace & Re-stage PMS' : 'Upload & Stage PMS' }}
                    </button>
                </div>
            </form>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div class="dashboard-card-title">
                    <div class="dashboard-card-title-icon {{ $detailsDone ? 'is-done' : '' }}">
                        @if ($detailsDone)<i class="fas fa-check"></i>@else 2 @endif
                    </div>
                    <div>
                        <h3>Loans Details Report</h3>
                        <div class="dashboard-card-subtitle">Provides the base Loan Book structure</div>
                    </div>
                </div>
                @if ($detailsDone)
                    <span class="badge badge-success">{{ number_format($detailsCount) }} rows</span>
                @else
                    <span class="badge badge-warning">Not uploaded</span>
                @endif
            </div>

            <form action="{{ route('loans.loan-book.upload-loan-details') }}" method="POST"
                enctype="multipart/form-data" class="card-body">
                @csrf

                <label class="field-label" for="loan_details_report">Report File <span class="req">*</span></label>
                @include('loans.partials.file-drop', ['id' => 'loan_details_report', 'name' => 'loan_details_report'])

                <div class="help">Required columns include</div>
                <div style="margin-top: 5px;">
                    @foreach (['Related Account', 'Customer Id', 'Name', 'Currency', 'Product Type', '+ other loan details'] as $col)
                        <span class="tag">{{ $col }}</span>
                    @endforeach
                </div>

                @if ($draftRun && $draftRun->loan_details_original_filename)
                    <div class="staged-file">
                        <i class="fas fa-file-excel"></i>
                        <span>Staged:</span>
                        <strong title="{{ $draftRun->loan_details_original_filename }}">{{ $draftRun->loan_details_original_filename }}</strong>
                    </div>
                @endif

                <div class="card-foot">
                    <button type="submit" class="btn {{ $detailsDone ? 'btn-outline' : 'btn-primary' }}">
                        <i class="fas fa-upload"></i>
                        {{ $detailsDone ? 'Replace & Re-stage Loan Details' : 'Upload & Stage Loan Details' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ======================================================
         STEP 3 (full width, outside the Step 1/2 grid)
    ======================================================= --}}
    <section class="dashboard-card">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title">
                <div class="dashboard-card-title-icon {{ $readyToProcess ? 'is-ready' : '' }}">3</div>
                <div>
                    <h3>Attach Supplementary Reports &amp; Generate</h3>
                    <div class="dashboard-card-subtitle">
                        Read in memory during processing to extract qualifying exposures. Raw rows are not stored.
                    </div>
                </div>
            </div>
            @if ($readyToProcess)
                <span class="badge badge-success">Ready</span>
            @else
                <span class="badge badge-warning">Needs steps 1 &amp; 2</span>
            @endif
        </div>

        <form action="{{ route('loans.loan-book.process') }}" method="POST" enctype="multipart/form-data"
            id="loanGenerateForm">
            @csrf

            <div class="grid-3">
                <div>
                    <label class="field-label" for="portfolio_report">Portfolio Account Report <span class="req">*</span></label>
                    @include('loans.partials.file-drop', ['id' => 'portfolio_report', 'name' => 'portfolio_report'])
                    <div class="help">Overdrafts and write-offs. Checks GL Name and picks LCY Curr Balance.</div>
                </div>

                <div>
                    <label class="field-label" for="credit_cards_report">Credit Cards Report <span class="req">*</span></label>
                    @include('loans.partials.file-drop', ['id' => 'credit_cards_report', 'name' => 'credit_cards_report'])
                    <div class="help">Credit card accounts with a negative Outstanding Amount.</div>
                </div>

                <div>
                    <label class="field-label" for="lms_report">Digital Lending Report <span class="req">*</span></label>
                    @include('loans.partials.file-drop', ['id' => 'lms_report', 'name' => 'lms_report'])
                    <div class="help">Only ACTIVE loans are extracted, using Total Outstanding.</div>
                </div>
            </div>

            <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; flex-wrap: wrap; margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--border);">
                <div style="width: 230px; max-width: 100%;">
                    <label class="field-label" for="loan_book_date">Loan Book Date</label>
                    <input type="date" id="loan_book_date" name="loan_book_date"
                        value="{{ old('loan_book_date', date('Y-m-d')) }}">
                    <div class="help">Official as-at date for this run.</div>
                </div>

                <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap; justify-content: flex-end;">
                    <span class="help" style="margin: 0; max-width: 320px; text-align: right;">
                        @if ($readyToProcess)
                            Generating can take a few minutes for large files. Please keep this page open.
                        @else
                            Upload and stage the PMS and Loans Details reports to enable generation.
                        @endif
                    </span>
                    <button type="submit" class="btn btn-primary btn-lg" id="loanGenerateBtn"
                        @unless ($readyToProcess) disabled @endunless>
                        <i class="fas fa-play"></i> Generate Loan Book
                    </button>
                </div>
            </div>
        </form>
    </section>

    {{-- ======================================================
         RECENT RUNS
    ======================================================= --}}
    <section class="dashboard-card flush" id="recent-runs">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title">
                <div class="dashboard-card-title-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <h3>Recent Loan Books</h3>
                    <div class="dashboard-card-subtitle">Your most recent completed runs (up to 20)</div>
                </div>
            </div>
        </div>

        @if ($runs->isEmpty())
            <div class="empty">
                <i class="fas fa-book-open"></i>
                No Loan Books yet. Completed runs will appear here.
            </div>
        @else
            <div class="table-responsive">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Batch Reference</th>
                            <th>Loan Book Date</th>
                            <th>Status</th>
                            <th class="num">Rows</th>
                            <th class="num">Exceptions</th>
                            <th class="num">Total Outstanding</th>
                            <th>Processed</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($runs as $run)
                            <tr>
                                <td class="strong">{{ $run->batch_reference }}</td>
                                <td>{{ $run->loan_book_date ? $run->loan_book_date->format('d M Y') : '—' }}</td>
                                <td>
                                    <span class="badge {{ $run->status === 'completed' ? 'badge-success' : 'badge-info' }}">
                                        {{ $run->status }}
                                    </span>
                                </td>
                                <td class="num">{{ number_format($run->total_final_loan_book_rows) }}</td>
                                <td class="num">
                                    @if ($run->total_exceptions > 0)
                                        <span class="badge badge-danger">{{ number_format($run->total_exceptions) }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="num strong">{{ number_format($run->total_loan_book_outstanding, 2) }}</td>
                                <td class="text-muted">{{ $run->processed_at ? $run->processed_at->format('d M Y, H:i') : '—' }}</td>
                                <td class="num">
                                    <a href="{{ route('loans.loan-book.show', $run->id) }}" class="btn-link">
                                        View <i class="fas fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

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
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating…';
            });
        })();
    </script>
@endpush
