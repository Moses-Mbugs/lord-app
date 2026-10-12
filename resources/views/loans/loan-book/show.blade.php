@extends('layouts.finance.template')

@section('title', 'Loan Book ' . $run->batch_reference)

@include('loans.partials.heading-font')

@php
    $controlDifference = (float) ($run->control_difference ?? 0);
    $hasControlDifference = abs($controlDifference) >= 0.01;
    $activeTab = request()->has('exceptions_page') ? 'exceptions' : 'entries';

    $sources = [
        ['label' => 'PMS', 'file' => $run->pms_original_filename, 'read' => $run->total_pms_rows, 'selected' => null],
        ['label' => 'Loan Details', 'file' => $run->loan_details_original_filename, 'read' => $run->total_loan_details_rows, 'selected' => null],
        ['label' => 'Portfolio', 'file' => $run->portfolio_original_filename, 'read' => $run->total_portfolio_rows_read, 'selected' => $run->total_portfolio_rows_selected],
        ['label' => 'Credit Cards', 'file' => $run->credit_cards_original_filename, 'read' => $run->total_credit_card_rows_read, 'selected' => $run->total_credit_card_rows_selected],
        ['label' => 'Digital Lending', 'file' => $run->lms_original_filename, 'read' => $run->total_lms_rows_read, 'selected' => $run->total_lms_rows_selected],
    ];

    $statusBadge = match ($run->status) {
        'completed' => 'finance-badge-success',
        'failed' => 'finance-badge-danger',
        'draft' => 'finance-badge-warning',
        default => 'finance-badge-info',
    };
@endphp

@push('styles')
    <style>
        .loan-stat-label {
            margin-bottom: 6px;
            color: var(--finance-muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .055em;
            text-transform: uppercase;
        }

        .loan-stat-value {
            overflow: hidden;
            color: var(--finance-text-strong);
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.2;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .loan-source {
            height: 100%;
            padding: 14px;
            background: var(--finance-surface-soft);
            border: 1px solid var(--finance-border);
            border-radius: var(--finance-radius-sm);
        }

        .loan-source__file {
            overflow: hidden;
            margin: 4px 0 10px;
            color: var(--finance-text-strong);
            font-size: 12.5px;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .loan-source__count {
            display: block;
            color: var(--finance-text-strong);
            font-size: 16px;
            font-weight: 800;
        }

        .table td.text-wrap {
            min-width: 220px;
        }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('loans.loan-book.index') }}">Loan Book</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $run->batch_reference }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="page-title loan-script-title">Loan Book</h4>
                <span class="finance-badge {{ $statusBadge }}">{{ ucfirst($run->status) }}</span>
            </div>
            <p class="text-muted small mb-0">
                <span class="fw-semibold text-body">{{ $run->batch_reference }}</span>
                · As at {{ $run->loan_book_date ? $run->loan_book_date->format('d M Y') : '—' }}
                · Processed {{ $run->processed_at ? $run->processed_at->format('d M Y, H:i') : '—' }}
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('loans.loan-book.index') }}" class="btn btn-light">
                <i class="ti ti-arrow-left"></i> Back
            </a>
            @if ($run->status === 'completed')
                <a href="{{ route('loans.loan-book.download', $run->id) }}" class="btn btn-primary">
                    <i class="ti ti-download"></i> Download Excel
                </a>
            @endif
        </div>
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
    @endif

    {{-- Headline numbers --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="loan-stat-label">Total Outstanding</div>
                <div class="loan-stat-value" title="{{ number_format($run->total_loan_book_outstanding, 2) }}">
                    {{ number_format($run->total_loan_book_outstanding, 2) }}
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="loan-stat-label">Loan Book Rows</div>
                <div class="loan-stat-value">{{ number_format($run->total_final_loan_book_rows) }}</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="loan-stat-label">Exceptions</div>
                <div class="loan-stat-value {{ $run->total_exceptions > 0 ? 'text-danger' : '' }}">
                    {{ number_format($run->total_exceptions) }}
                </div>
                <div class="text-muted small">{{ $run->total_exceptions > 0 ? 'Needs review' : 'All records matched' }}</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="loan-stat-label">Control Difference</div>
                <div class="loan-stat-value {{ $hasControlDifference ? 'text-danger' : 'text-success' }}">
                    {{ number_format($controlDifference, 2) }}
                </div>
                <div class="text-muted small">{{ $hasControlDifference ? 'Does not reconcile' : 'Reconciled' }}</div>
            </div>
        </div>
    </div>

    {{-- Source reports --}}
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="card-title mb-0">Source Reports</h6>
            <div class="text-muted small">Files used for this run and the rows read and selected from each.</div>
        </div>

        <div class="card-body">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-5 g-3">
                @foreach ($sources as $source)
                    <div class="col">
                        <div class="loan-source">
                            <div class="loan-stat-label mb-0">{{ $source['label'] }}</div>
                            <div class="loan-source__file" title="{{ $source['file'] ?? '' }}">
                                <i class="ti ti-file-spreadsheet text-muted"></i> {{ $source['file'] ?? '—' }}
                            </div>
                            <div class="d-flex gap-4 small text-muted">
                                <div>
                                    <span class="loan-source__count">{{ number_format($source['read'] ?? 0) }}</span>
                                    {{ is_null($source['selected']) ? 'rows staged' : 'rows read' }}
                                </div>
                                @unless (is_null($source['selected']))
                                    <div>
                                        <span class="loan-source__count">{{ number_format($source['selected'] ?? 0) }}</span>
                                        selected
                                    </div>
                                @endunless
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Entries / Exceptions --}}
    <div class="card">
        <div class="card-header pb-0 border-bottom-0">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'entries' ? 'active' : '' }}" data-bs-toggle="tab"
                        data-bs-target="#loanEntries" type="button" role="tab">
                        Loan Book Entries
                        <span class="finance-badge finance-badge-info ms-1">{{ number_format($entries->total()) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'exceptions' ? 'active' : '' }}" data-bs-toggle="tab"
                        data-bs-target="#loanExceptions" type="button" role="tab">
                        Exceptions
                        <span class="finance-badge {{ $exceptions->total() > 0 ? 'finance-badge-danger' : 'finance-badge-success' }} ms-1">
                            {{ number_format($exceptions->total()) }}
                        </span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade {{ $activeTab === 'entries' ? 'show active' : '' }}" id="loanEntries" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-nowrap">
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
                                <th class="text-end">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entries as $entry)
                                <tr>
                                    <td><span class="finance-badge finance-badge-info">{{ $entry->source_report ?? '—' }}</span></td>
                                    <td>{{ $entry->source_type ?? '—' }}</td>
                                    <td>{{ $entry->related_account ?? '—' }}</td>
                                    <td>{{ $entry->related_customer_id ?? '—' }}</td>
                                    <td>{{ $entry->name ?? '—' }}</td>
                                    <td>{{ $entry->branch_name ?? ($entry->branch ?? '—') }}</td>
                                    <td>{{ $entry->currency ?? ($entry->contract_currency ?? '—') }}</td>
                                    <td>{{ $entry->product_type ?? '—' }}</td>
                                    <td>{{ $entry->gl_name ?? '—' }}</td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format((float) ($entry->loan_book_outstanding ?? ($entry->outstanding_amount ?? 0)), 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-5">No Loan Book entries found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($entries->hasPages())
                    <div class="card-footer">
                        {{ $entries->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>

            <div class="tab-pane fade {{ $activeTab === 'exceptions' ? 'show active' : '' }}" id="loanExceptions" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-nowrap">
                        <thead>
                            <tr>
                                <th>Exception Type</th>
                                <th>Source</th>
                                <th>Related Account</th>
                                <th>Customer ID</th>
                                <th>Name</th>
                                <th class="text-end">Amount</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($exceptions as $exception)
                                <tr>
                                    <td><span class="finance-badge finance-badge-danger">{{ $exception->exception_type ?? '—' }}</span></td>
                                    <td>{{ $exception->source ?? '—' }}</td>
                                    <td>{{ $exception->related_account ?? '—' }}</td>
                                    <td>{{ $exception->related_customer_id ?? '—' }}</td>
                                    <td>{{ $exception->name ?? '—' }}</td>
                                    <td class="text-end">{{ number_format((float) ($exception->amount ?? 0), 2) }}</td>
                                    <td class="text-wrap text-muted small">{{ $exception->remarks ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="ti ti-circle-check d-block mb-2 text-success" style="font-size: 26px;"></i>
                                        No exceptions. Every record was matched cleanly.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($exceptions->hasPages())
                    <div class="card-footer">
                        {{ $exceptions->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
