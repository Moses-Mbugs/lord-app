@extends('layouts.loans.template')

@section('title', 'Generate Loan Book')
@section('page_title', 'Generate Loan Book')
@section('page_subtitle', 'Stage PMS and Loan Details reports, then process the Loan Book with Portfolio, Credit Card, and Digital Lending extracts')

@include('loans.loan-book.partials.styles')

@php
    $pmsCount = $draftRun ? (int) $draftRun->pms_staging_count : 0;
    $detailsCount = $draftRun ? (int) $draftRun->details_staging_count : 0;
    $pmsDone = $pmsCount > 0;
    $detailsDone = $detailsCount > 0;
    $readyToProcess = $pmsDone && $detailsDone;
@endphp

@section('content')
    <div class="lb">

        <div class="lb-header">
            <div>
                <div class="lb-eyebrow">Loan Book Generator</div>
                <h1>Build a new Loan Book</h1>
                <p>
                    Stage the PMS and Loans Details reports, then attach the Portfolio, Credit Cards and Digital Lending
                    reports to extract overdraft, write-off, credit card and digital loan exposures into the final book.
                </p>
            </div>

            @if ($draftRun)
                <span class="lb-chip lb-chip-info">Draft <span class="mono">{{ $draftRun->batch_reference }}</span></span>
            @endif
        </div>

        @if (session('success'))
            <div class="lb-alert lb-alert-success" role="status">
                <span class="lb-alert-icon">✓</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="lb-alert lb-alert-danger" role="alert">
                <span class="lb-alert-icon">!</span>
                <div>{{ session('error') }}</div>
            </div>
        @elseif ($draftRun && $draftRun->failure_reason)
            <div class="lb-alert lb-alert-warning" role="alert">
                <span class="lb-alert-icon">!</span>
                <div>
                    <strong>The last attempt to generate this draft failed.</strong>
                    <div>{{ \Illuminate\Support\Str::limit($draftRun->failure_reason, 300) }}</div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="lb-alert lb-alert-danger" role="alert">
                <span class="lb-alert-icon">!</span>
                <div>
                    <strong>Please correct the following:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Progress tracker --}}
        <div class="lb-progress">
            <div class="lb-progress-step {{ $pmsDone ? 'is-done' : 'is-current' }}">
                <span class="lb-step-dot">{{ $pmsDone ? '✓' : '1' }}</span>
                <div style="min-width: 0;">
                    <strong>PMS Loan Proofing</strong>
                    <span class="num">{{ $pmsDone ? number_format($pmsCount) . ' rows staged' : 'Waiting for upload' }}</span>
                </div>
            </div>

            <div class="lb-progress-step {{ $detailsDone ? 'is-done' : ($pmsDone ? 'is-current' : '') }}">
                <span class="lb-step-dot">{{ $detailsDone ? '✓' : '2' }}</span>
                <div style="min-width: 0;">
                    <strong>Loans Details</strong>
                    <span class="num">{{ $detailsDone ? number_format($detailsCount) . ' rows staged' : 'Waiting for upload' }}</span>
                </div>
            </div>

            <div class="lb-progress-step {{ $readyToProcess ? 'is-current' : '' }}">
                <span class="lb-step-dot">3</span>
                <div style="min-width: 0;">
                    <strong>Generate Loan Book</strong>
                    <span>{{ $readyToProcess ? 'Ready to generate' : 'Complete steps 1 and 2 first' }}</span>
                </div>
            </div>
        </div>

        {{-- Steps 1 & 2 --}}
        <div class="lb-steps">
            <section class="lb-panel">
                <div class="lb-panel-head">
                    <div class="lb-step-title">
                        <span class="lb-step-dot {{ $pmsDone ? 'is-done' : '' }}">{{ $pmsDone ? '✓' : '1' }}</span>
                        <div>
                            <h3>PMS Loan Proofing Report</h3>
                            <p>Provides the outstanding amounts.</p>
                        </div>
                    </div>
                    @if ($pmsDone)
                        <span class="lb-chip lb-chip-success num">{{ number_format($pmsCount) }} rows</span>
                    @else
                        <span class="lb-chip lb-chip-pending">Not uploaded</span>
                    @endif
                </div>

                <div class="lb-panel-body">
                    <form action="{{ route('loans.loan-book.upload-pms') }}" method="POST" enctype="multipart/form-data"
                        style="display: contents;">
                        @csrf

                        <span class="lb-field-label">Report file <span class="req">*</span></span>
                        @include('loans.loan-book.partials.dropzone', [
                            'id' => 'pms_report',
                            'name' => 'pms_report',
                            'accept' => '.xls,.xlsx,.csv',
                        ])

                        <div class="lb-help">
                            Required columns:
                            <div style="margin-top: 6px;">
                                @foreach (['GL Code', 'Related Account', 'Related Customer Id', 'Name', 'Outstanding Amount'] as $col)
                                    <span class="lb-tag">{{ $col }}</span>
                                @endforeach
                            </div>
                        </div>

                        @if ($draftRun && $draftRun->pms_original_filename)
                            <div class="lb-current-file">
                                <span>Staged:</span>
                                <strong title="{{ $draftRun->pms_original_filename }}">{{ $draftRun->pms_original_filename }}</strong>
                            </div>
                        @endif

                        <div class="lb-form-foot">
                            <button type="submit" class="lb-btn {{ $pmsDone ? 'lb-btn-ghost' : 'lb-btn-primary' }}">
                                {{ $pmsDone ? 'Replace & re-stage PMS' : 'Upload & stage PMS' }}
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="lb-panel">
                <div class="lb-panel-head">
                    <div class="lb-step-title">
                        <span class="lb-step-dot {{ $detailsDone ? 'is-done' : '' }}">{{ $detailsDone ? '✓' : '2' }}</span>
                        <div>
                            <h3>Loans Details Report</h3>
                            <p>Provides the base Loan Book structure.</p>
                        </div>
                    </div>
                    @if ($detailsDone)
                        <span class="lb-chip lb-chip-success num">{{ number_format($detailsCount) }} rows</span>
                    @else
                        <span class="lb-chip lb-chip-pending">Not uploaded</span>
                    @endif
                </div>

                <div class="lb-panel-body">
                    <form action="{{ route('loans.loan-book.upload-loan-details') }}" method="POST"
                        enctype="multipart/form-data" style="display: contents;">
                        @csrf

                        <span class="lb-field-label">Report file <span class="req">*</span></span>
                        @include('loans.loan-book.partials.dropzone', [
                            'id' => 'loan_details_report',
                            'name' => 'loan_details_report',
                            'accept' => '.xls,.xlsx,.csv',
                        ])

                        <div class="lb-help">
                            Required columns include:
                            <div style="margin-top: 6px;">
                                @foreach (['Related Account', 'Customer Id', 'Name', 'Currency', 'Product Type'] as $col)
                                    <span class="lb-tag">{{ $col }}</span>
                                @endforeach
                                <span class="lb-tag">+ other loan details</span>
                            </div>
                        </div>

                        @if ($draftRun && $draftRun->loan_details_original_filename)
                            <div class="lb-current-file">
                                <span>Staged:</span>
                                <strong title="{{ $draftRun->loan_details_original_filename }}">{{ $draftRun->loan_details_original_filename }}</strong>
                            </div>
                        @endif

                        <div class="lb-form-foot">
                            <button type="submit" class="lb-btn {{ $detailsDone ? 'lb-btn-ghost' : 'lb-btn-primary' }}">
                                {{ $detailsDone ? 'Replace & re-stage Loan Details' : 'Upload & stage Loan Details' }}
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        {{-- Step 3 (now its own full-width section, outside the Step 1/2 grid) --}}
        <section class="lb-panel">
            <div class="lb-panel-head">
                <div class="lb-step-title">
                    <span class="lb-step-dot {{ $readyToProcess ? 'is-ready' : '' }}">3</span>
                    <div>
                        <h3>Attach supplementary reports &amp; generate</h3>
                        <p>Read in memory during processing to extract qualifying exposures. Raw rows are not stored.</p>
                    </div>
                </div>
                @if ($readyToProcess)
                    <span class="lb-chip lb-chip-success">Ready</span>
                @else
                    <span class="lb-chip lb-chip-warning">Needs steps 1 &amp; 2</span>
                @endif
            </div>

            <div class="lb-panel-body">
                <form action="{{ route('loans.loan-book.process') }}" method="POST" enctype="multipart/form-data"
                    id="lbGenerateForm">
                    @csrf

                    <div class="lb-supp-grid">
                        <div>
                            <label class="lb-field-label" for="portfolio_report">Portfolio Account Report <span class="req">*</span></label>
                            @include('loans.loan-book.partials.dropzone', [
                                'id' => 'portfolio_report',
                                'name' => 'portfolio_report',
                            ])
                            <div class="lb-help">Overdrafts and write-offs — checks GL Name and picks LCY Curr Balance.</div>
                        </div>

                        <div>
                            <label class="lb-field-label" for="credit_cards_report">Credit Cards Report <span class="req">*</span></label>
                            @include('loans.loan-book.partials.dropzone', [
                                'id' => 'credit_cards_report',
                                'name' => 'credit_cards_report',
                            ])
                            <div class="lb-help">Credit card accounts with a negative Outstanding Amount.</div>
                        </div>

                        <div>
                            <label class="lb-field-label" for="lms_report">Digital Lending Report <span class="req">*</span></label>
                            @include('loans.loan-book.partials.dropzone', [
                                'id' => 'lms_report',
                                'name' => 'lms_report',
                            ])
                            <div class="lb-help">Only ACTIVE loans are extracted, using Total Outstanding.</div>
                        </div>
                    </div>

                    <div class="lb-generate-bar">
                        <div class="lb-date">
                            <label class="lb-field-label" for="loan_book_date">Loan Book date</label>
                            <input type="date" id="loan_book_date" name="loan_book_date" class="lb-input"
                                value="{{ old('loan_book_date', date('Y-m-d')) }}">
                            <div class="lb-help" style="margin-top: 6px;">Official as-at date for this run.</div>
                        </div>

                        <div class="lb-generate-cta">
                            <small>
                                @if ($readyToProcess)
                                    Generating can take a few minutes for large files. Please keep this page open.
                                @else
                                    Upload and stage the PMS and Loans Details reports above to enable generation.
                                @endif
                            </small>
                            <button type="submit" class="lb-btn lb-btn-primary lb-btn-lg" id="lbGenerateBtn"
                                @unless ($readyToProcess) disabled @endunless>
                                Generate Loan Book
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        {{-- Recent runs --}}
        <section class="lb-panel">
            <div class="lb-panel-head">
                <div>
                    <h3>Recent Loan Books</h3>
                    <p>Your most recent completed runs (up to 20).</p>
                </div>
            </div>

            <div class="lb-table-wrap">
                <table class="lb-table">
                    <thead>
                        <tr>
                            <th>Batch reference</th>
                            <th>Loan Book date</th>
                            <th>Status</th>
                            <th class="text-right">Rows</th>
                            <th class="text-right">Exceptions</th>
                            <th class="text-right">Total outstanding</th>
                            <th>Processed</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($runs as $run)
                            <tr>
                                <td class="mono">{{ $run->batch_reference }}</td>
                                <td class="num">{{ $run->loan_book_date ? $run->loan_book_date->format('d M Y') : '—' }}</td>
                                <td>
                                    <span class="lb-chip {{ $run->status === 'completed' ? 'lb-chip-success' : 'lb-chip-pending' }}">
                                        {{ ucfirst($run->status) }}
                                    </span>
                                </td>
                                <td class="num text-right">{{ number_format($run->total_final_loan_book_rows) }}</td>
                                <td class="num text-right">
                                    @if ($run->total_exceptions > 0)
                                        <span class="lb-text-danger" style="font-weight: 700;">{{ number_format($run->total_exceptions) }}</span>
                                    @else
                                        <span class="lb-text-muted">0</span>
                                    @endif
                                </td>
                                <td class="num text-right" style="font-weight: 700;">{{ number_format($run->total_loan_book_outstanding, 2) }}</td>
                                <td class="num lb-text-muted">{{ $run->processed_at ? $run->processed_at->format('d M Y, H:i') : '—' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('loans.loan-book.show', $run->id) }}" class="lb-btn lb-btn-ghost lb-btn-sm">
                                        View →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="lb-empty">
                                    <strong>No Loan Books yet</strong>
                                    Completed runs will appear here after you generate one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            var form = document.getElementById('lbGenerateForm');
            var btn = document.getElementById('lbGenerateBtn');
            if (!form || !btn) return;

            // Prevent double submits while the server processes the reports.
            form.addEventListener('submit', function() {
                btn.disabled = true;
                btn.textContent = 'Generating…';
            });
        })();
    </script>
@endpush
