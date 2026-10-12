@extends('layouts.loans.app')

@section('title', 'Loan Book Details')
@section('subtitle', 'Batch ' . $run->batch_reference)

@php
    $controlDifference = (float) ($run->control_difference ?? 0);
    $hasControlDifference = abs($controlDifference) >= 0.01;
    $activeTab = request()->has('exceptions_page') ? 'exceptions' : 'entries';

    $sources = [
        ['label' => 'PMS', 'icon' => 'fa-file-invoice-dollar', 'file' => $run->pms_original_filename, 'read' => $run->total_pms_rows, 'selected' => null],
        ['label' => 'Loan Details', 'icon' => 'fa-file-lines', 'file' => $run->loan_details_original_filename, 'read' => $run->total_loan_details_rows, 'selected' => null],
        ['label' => 'Portfolio', 'icon' => 'fa-briefcase', 'file' => $run->portfolio_original_filename, 'read' => $run->total_portfolio_rows_read, 'selected' => $run->total_portfolio_rows_selected],
        ['label' => 'Credit Cards', 'icon' => 'fa-credit-card', 'file' => $run->credit_cards_original_filename, 'read' => $run->total_credit_card_rows_read, 'selected' => $run->total_credit_card_rows_selected],
        ['label' => 'Digital Lending', 'icon' => 'fa-mobile-screen', 'file' => $run->lms_original_filename, 'read' => $run->total_lms_rows_read, 'selected' => $run->total_lms_rows_selected],
    ];
@endphp

@push('styles')
    <style>
        .source-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .source {
            min-width: 0;
            padding: 14px;
            border-radius: 11px;
            background: #F8FAFC;
            border: 1px solid var(--border);
        }

        .source-name {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: .6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--gray-3);
        }

        .source-name i {
            color: var(--blue);
        }

        .source-file {
            margin: 6px 0 12px;
            overflow: hidden;
            font-size: .7rem;
            font-weight: 700;
            color: var(--deep-blue);
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .source-counts {
            display: flex;
            gap: 18px;
            font-size: .6rem;
            color: var(--gray-3);
        }

        .source-counts strong {
            display: block;
            font-size: .95rem;
            color: var(--deep-blue);
        }

        @media (max-width: 1350px) {
            .source-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .source-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 520px) {
            .source-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')

    {{-- ======================================================
         HERO
    ======================================================= --}}
    <section class="loan-hero">
        <div class="hero-content">
            <div class="hero-copy">
                <a href="{{ route('loans.loan-book.index') }}" class="hero-eyebrow">
                    <i class="fas fa-arrow-left"></i>
                    Back to Loan Book Generator
                </a>

                <div class="hero-title">Loan Book</div>

                <div class="hero-description">
                    Final generated entries from PMS, Portfolio overdraft/write-off, Credit Card and Digital Lending
                    exposures, with any records that could not be matched listed under Exceptions.
                </div>

                @if ($run->status === 'completed')
                    <div class="hero-actions">
                        <a href="{{ route('loans.loan-book.download', $run->id) }}" class="btn btn-hero">
                            <i class="fas fa-file-excel"></i> Download Excel
                        </a>
                    </div>
                @endif
            </div>

            <div class="hero-panel">
                <div class="hero-panel-label">Batch Reference</div>
                <div class="hero-panel-value">{{ $run->batch_reference }}</div>
                <div class="hero-panel-meta">
                    <i class="far fa-calendar"></i>
                    As at {{ $run->loan_book_date ? $run->loan_book_date->format('d M Y') : '—' }}
                    <span class="badge">{{ $run->status }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================================================
         KPI TILES
    ======================================================= --}}
    <div class="kpi-grid">
        <div class="kpi kpi-blue">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fas fa-sack-dollar"></i></div>
                <span class="kpi-mini-label">Book Total</span>
            </div>
            <div class="kpi-value" title="{{ number_format($run->total_loan_book_outstanding, 2) }}">
                {{ number_format($run->total_loan_book_outstanding, 2) }}
            </div>
            <div class="kpi-label">Total Outstanding</div>
        </div>

        <div class="kpi">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fas fa-list"></i></div>
                <span class="kpi-mini-label">Entries</span>
            </div>
            <div class="kpi-value">{{ number_format($run->total_final_loan_book_rows) }}</div>
            <div class="kpi-label">Loan Book Rows</div>
        </div>

        <div class="kpi {{ $run->total_exceptions > 0 ? 'kpi-red' : 'kpi-green' }}">
            <div class="kpi-top">
                <div class="kpi-icon">
                    <i class="fas {{ $run->total_exceptions > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
                </div>
                <span class="kpi-mini-label">{{ $run->total_exceptions > 0 ? 'Review' : 'Clean' }}</span>
            </div>
            <div class="kpi-value">{{ number_format($run->total_exceptions) }}</div>
            <div class="kpi-label">Exceptions</div>
        </div>

        <div class="kpi {{ $hasControlDifference ? 'kpi-red' : 'kpi-green' }}">
            <div class="kpi-top">
                <div class="kpi-icon"><i class="fas fa-scale-balanced"></i></div>
                <span class="kpi-mini-label">{{ $hasControlDifference ? 'Does not reconcile' : 'Reconciled' }}</span>
            </div>
            <div class="kpi-value {{ $hasControlDifference ? 'text-danger' : '' }}">
                {{ number_format($controlDifference, 2) }}
            </div>
            <div class="kpi-label">Control Difference</div>
        </div>
    </div>

    {{-- ======================================================
         SOURCE REPORTS
    ======================================================= --}}
    <section class="dashboard-card">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title">
                <div class="dashboard-card-title-icon"><i class="fas fa-folder-open"></i></div>
                <div>
                    <h3>Source Reports</h3>
                    <div class="dashboard-card-subtitle">Files used for this run and the rows read and selected from each</div>
                </div>
            </div>
            <span class="text-muted" style="font-size: .64rem;">
                Processed {{ $run->processed_at ? $run->processed_at->format('d M Y, H:i') : '—' }}
            </span>
        </div>

        <div class="source-grid">
            @foreach ($sources as $source)
                <div class="source">
                    <div class="source-name"><i class="fas {{ $source['icon'] }}"></i> {{ $source['label'] }}</div>
                    <div class="source-file" title="{{ $source['file'] ?? '' }}">{{ $source['file'] ?? '—' }}</div>
                    <div class="source-counts">
                        <div>
                            <strong>{{ number_format($source['read'] ?? 0) }}</strong>
                            {{ is_null($source['selected']) ? 'rows staged' : 'rows read' }}
                        </div>
                        @unless (is_null($source['selected']))
                            <div>
                                <strong>{{ number_format($source['selected'] ?? 0) }}</strong>
                                selected
                            </div>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ======================================================
         ENTRIES / EXCEPTIONS
    ======================================================= --}}
    <section class="dashboard-card flush" data-tabs>
        <div class="tabs" role="tablist">
            <button type="button" role="tab" class="tab {{ $activeTab === 'entries' ? 'is-active' : '' }}"
                data-tab="entries" aria-selected="{{ $activeTab === 'entries' ? 'true' : 'false' }}">
                <i class="fas fa-list"></i> Loan Book Entries
                <span class="badge badge-info">{{ number_format($entries->total()) }}</span>
            </button>
            <button type="button" role="tab" class="tab {{ $activeTab === 'exceptions' ? 'is-active' : '' }}"
                data-tab="exceptions" aria-selected="{{ $activeTab === 'exceptions' ? 'true' : 'false' }}">
                <i class="fas fa-triangle-exclamation"></i> Exceptions
                <span class="badge {{ $exceptions->total() > 0 ? 'badge-danger' : 'badge-success' }}">
                    {{ number_format($exceptions->total()) }}
                </span>
            </button>
        </div>

        <div data-tab-pane="entries" @if ($activeTab !== 'entries') hidden @endif>
            @if ($entries->isEmpty())
                <div class="empty">
                    <i class="fas fa-list"></i>
                    No Loan Book entries found.
                </div>
            @else
                <div class="table-responsive">
                    <table class="data">
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
                                <th class="num">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $entry)
                                <tr>
                                    <td><span class="badge badge-info">{{ $entry->source_report ?? '—' }}</span></td>
                                    <td>{{ $entry->source_type ?? '—' }}</td>
                                    <td>{{ $entry->related_account ?? '—' }}</td>
                                    <td>{{ $entry->related_customer_id ?? '—' }}</td>
                                    <td class="strong">{{ $entry->name ?? '—' }}</td>
                                    <td>{{ $entry->branch_name ?? ($entry->branch ?? '—') }}</td>
                                    <td>{{ $entry->currency ?? ($entry->contract_currency ?? '—') }}</td>
                                    <td>{{ $entry->product_type ?? '—' }}</td>
                                    <td>{{ $entry->gl_name ?? '—' }}</td>
                                    <td class="num strong">
                                        {{ number_format((float) ($entry->loan_book_outstanding ?? ($entry->outstanding_amount ?? 0)), 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($entries->hasPages())
                    <div class="card-foot">{{ $entries->links('pagination::bootstrap-4') }}</div>
                @endif
            @endif
        </div>

        <div data-tab-pane="exceptions" @if ($activeTab !== 'exceptions') hidden @endif>
            @if ($exceptions->isEmpty())
                <div class="empty">
                    <i class="fas fa-circle-check" style="color: var(--success);"></i>
                    No exceptions. Every record was matched cleanly.
                </div>
            @else
                <div class="table-responsive">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Exception Type</th>
                                <th>Source</th>
                                <th>Related Account</th>
                                <th>Customer ID</th>
                                <th>Name</th>
                                <th class="num">Amount</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($exceptions as $exception)
                                <tr>
                                    <td><span class="badge badge-danger">{{ $exception->exception_type ?? '—' }}</span></td>
                                    <td>{{ $exception->source ?? '—' }}</td>
                                    <td>{{ $exception->related_account ?? '—' }}</td>
                                    <td>{{ $exception->related_customer_id ?? '—' }}</td>
                                    <td class="strong">{{ $exception->name ?? '—' }}</td>
                                    <td class="num">{{ number_format((float) ($exception->amount ?? 0), 2) }}</td>
                                    <td class="wrap text-muted">{{ $exception->remarks ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($exceptions->hasPages())
                    <div class="card-foot">{{ $exceptions->links('pagination::bootstrap-4') }}</div>
                @endif
            @endif
        </div>
    </section>

@endsection
