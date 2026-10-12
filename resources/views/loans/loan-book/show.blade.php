@extends('layouts.loans.template')

@section('title', 'Loan Book Details')
@section('page_title', 'Loan Book Details')
@section('page_subtitle', 'View generated Loan Book entries and exceptions')

@include('loans.loan-book.partials.styles')

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

    $statusChip = match ($run->status) {
        'completed' => 'lb-chip-success',
        'failed' => 'lb-chip-danger',
        'draft' => 'lb-chip-warning',
        default => 'lb-chip-pending',
    };
@endphp

@section('content')
    <div class="lb">

        <div class="lb-header">
            <div>
                <div class="lb-eyebrow">
                    <a href="{{ route('loans.loan-book.index') }}" style="color: inherit;">Loan Books</a>
                    <span class="lb-text-muted">/</span> Details
                </div>
                <h1 class="mono" style="font-size: 22px;">{{ $run->batch_reference }}</h1>
                <p style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <span class="lb-chip {{ $statusChip }}">{{ ucfirst($run->status) }}</span>
                    <span>As at <strong class="num" style="color: var(--lb-ink);">{{ $run->loan_book_date ? $run->loan_book_date->format('d M Y') : '—' }}</strong></span>
                    <span>·</span>
                    <span>Processed {{ $run->processed_at ? $run->processed_at->format('d M Y, H:i') : '—' }}</span>
                </p>
            </div>

            <div class="lb-header-actions">
                <a href="{{ route('loans.loan-book.index') }}" class="lb-btn lb-btn-ghost">← Back</a>
                @if ($run->status === 'completed')
                    <a href="{{ route('loans.loan-book.download', $run->id) }}" class="lb-btn lb-btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" y1="15" x2="12" y2="3" />
                        </svg>
                        Download Excel
                    </a>
                @endif
            </div>
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
        @endif

        {{-- Headline numbers --}}
        <div class="lb-stats">
            <div class="lb-stat is-accent">
                <div class="lb-stat-label">Total outstanding</div>
                <div class="lb-stat-value" title="{{ number_format($run->total_loan_book_outstanding, 2) }}">
                    {{ number_format($run->total_loan_book_outstanding, 2) }}
                </div>
            </div>

            <div class="lb-stat">
                <div class="lb-stat-label">Loan Book rows</div>
                <div class="lb-stat-value">{{ number_format($run->total_final_loan_book_rows) }}</div>
            </div>

            <div class="lb-stat">
                <div class="lb-stat-label">Exceptions</div>
                <div class="lb-stat-value {{ $run->total_exceptions > 0 ? 'lb-text-danger' : '' }}">
                    {{ number_format($run->total_exceptions) }}
                </div>
                <div class="lb-stat-note">{{ $run->total_exceptions > 0 ? 'Needs review' : 'All records matched' }}</div>
            </div>

            <div class="lb-stat">
                <div class="lb-stat-label">Control difference</div>
                <div class="lb-stat-value {{ $hasControlDifference ? 'lb-text-danger' : 'lb-text-success' }}">
                    {{ number_format($controlDifference, 2) }}
                </div>
                <div class="lb-stat-note">{{ $hasControlDifference ? 'Does not reconcile' : 'Reconciled' }}</div>
            </div>
        </div>

        {{-- Source files --}}
        <section class="lb-panel">
            <div class="lb-panel-head">
                <div>
                    <h3>Source reports</h3>
                    <p>Files used for this run and how many rows were read and selected from each.</p>
                </div>
            </div>

            <div class="lb-panel-body">
                <div class="lb-sources">
                    @foreach ($sources as $source)
                        <div class="lb-source">
                            <div class="lb-source-name">{{ $source['label'] }}</div>
                            <div class="lb-source-file" title="{{ $source['file'] ?? '' }}">
                                {{ $source['file'] ?? '—' }}
                            </div>
                            <div class="lb-source-counts">
                                <div>
                                    <b>{{ number_format($source['read'] ?? 0) }}</b>
                                    rows {{ is_null($source['selected']) ? 'staged' : 'read' }}
                                </div>
                                @unless (is_null($source['selected']))
                                    <div>
                                        <b>{{ number_format($source['selected'] ?? 0) }}</b>
                                        selected
                                    </div>
                                @endunless
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Entries / Exceptions tabs --}}
        <section class="lb-panel">
            <div class="lb-tabs" role="tablist">
                <button type="button" role="tab" class="lb-tab {{ $activeTab === 'entries' ? 'is-active' : '' }}"
                    data-lb-tab="entries" aria-selected="{{ $activeTab === 'entries' ? 'true' : 'false' }}">
                    Loan Book entries
                    <span class="lb-tab-count">{{ number_format($entries->total()) }}</span>
                </button>
                <button type="button" role="tab" class="lb-tab {{ $activeTab === 'exceptions' ? 'is-active' : '' }}"
                    data-lb-tab="exceptions" aria-selected="{{ $activeTab === 'exceptions' ? 'true' : 'false' }}">
                    Exceptions
                    <span class="lb-tab-count" @if ($exceptions->total() > 0) style="background: var(--lb-red-tint); color: var(--lb-red); border-color: transparent;" @endif>
                        {{ number_format($exceptions->total()) }}
                    </span>
                </button>
            </div>

            <div class="lb-tab-pane" data-lb-pane="entries" @if ($activeTab !== 'entries') hidden @endif>
                <div class="lb-table-wrap">
                    <table class="lb-table">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Type</th>
                                <th>Related account</th>
                                <th>Customer ID</th>
                                <th>Name</th>
                                <th>Branch</th>
                                <th>Currency</th>
                                <th>Product type</th>
                                <th>GL name</th>
                                <th class="text-right">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entries as $entry)
                                <tr>
                                    <td><span class="lb-tag" style="margin: 0;">{{ $entry->source_report ?? '—' }}</span></td>
                                    <td>{{ $entry->source_type ?? '—' }}</td>
                                    <td class="mono">{{ $entry->related_account ?? '—' }}</td>
                                    <td class="mono">{{ $entry->related_customer_id ?? '—' }}</td>
                                    <td>{{ $entry->name ?? '—' }}</td>
                                    <td>{{ $entry->branch_name ?? ($entry->branch ?? '—') }}</td>
                                    <td>{{ $entry->currency ?? ($entry->contract_currency ?? '—') }}</td>
                                    <td>{{ $entry->product_type ?? '—' }}</td>
                                    <td>{{ $entry->gl_name ?? '—' }}</td>
                                    <td class="num text-right" style="font-weight: 600;">
                                        {{ number_format((float) ($entry->loan_book_outstanding ?? ($entry->outstanding_amount ?? 0)), 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="lb-empty">
                                        <strong>No entries</strong>
                                        This run did not produce any Loan Book entries.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($entries->hasPages())
                    <div class="lb-table-foot">
                        {{ $entries->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </div>

            <div class="lb-tab-pane" data-lb-pane="exceptions" @if ($activeTab !== 'exceptions') hidden @endif>
                <div class="lb-table-wrap">
                    <table class="lb-table">
                        <thead>
                            <tr>
                                <th>Exception type</th>
                                <th>Source</th>
                                <th>Related account</th>
                                <th>Customer ID</th>
                                <th>Name</th>
                                <th class="text-right">Amount</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($exceptions as $exception)
                                <tr>
                                    <td><span class="lb-chip lb-chip-danger">{{ $exception->exception_type ?? '—' }}</span></td>
                                    <td>{{ $exception->source ?? '—' }}</td>
                                    <td class="mono">{{ $exception->related_account ?? '—' }}</td>
                                    <td class="mono">{{ $exception->related_customer_id ?? '—' }}</td>
                                    <td>{{ $exception->name ?? '—' }}</td>
                                    <td class="num text-right">{{ number_format((float) ($exception->amount ?? 0), 2) }}</td>
                                    <td class="wrap lb-text-muted">{{ $exception->remarks ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="lb-empty">
                                        <strong>No exceptions 🎉</strong>
                                        Every record was matched and processed cleanly.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($exceptions->hasPages())
                    <div class="lb-table-foot">
                        {{ $exceptions->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </div>
        </section>

    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            var tabs = document.querySelectorAll('[data-lb-tab]');
            var panes = document.querySelectorAll('[data-lb-pane]');

            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var target = tab.getAttribute('data-lb-tab');

                    tabs.forEach(function(t) {
                        var active = t === tab;
                        t.classList.toggle('is-active', active);
                        t.setAttribute('aria-selected', active ? 'true' : 'false');
                    });

                    panes.forEach(function(pane) {
                        pane.hidden = pane.getAttribute('data-lb-pane') !== target;
                    });
                });
            });
        })();
    </script>
@endpush
