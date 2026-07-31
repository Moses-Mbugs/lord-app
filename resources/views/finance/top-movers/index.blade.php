@extends('layouts.finance.template')

@section('title', 'Top Movers')

@push('styles')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap');

        :root {
            --blue: #0082BB;
            --blue-dark: #005B82;
            --blue-dim: rgba(0, 130, 187, 0.1);
            --green: #669438;
            --green-dark: #4d7029;
            --green-dim: rgba(102, 148, 56, 0.12);
            --gray-text: #464646;
            --gray-mid: #979797;
            --gray-light: #EDEDED;
            --gray-bg: #F7F9FB;
            --gain-text: #1b6b20;
            --gain-bg: #eaf6eb;
            --gain-border: #a5d6a7;
            --loss-text: #b71c1c;
            --loss-bg: #fdecea;
            --loss-border: #ef9a9a;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, .06);
            --shadow-md: 0 6px 20px rgba(0, 0, 0, .10);
            --radius: 12px;
            --font: 'DM Sans', sans-serif;
            --font-mono: 'DM Mono', monospace;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: var(--font);
        }

        #toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .toast-msg {
            background: #1e293b;
            color: #fff;
            font-size: .83rem;
            font-weight: 500;
            padding: 10px 16px;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .18);
            display: flex;
            align-items: center;
            gap: 9px;
            pointer-events: auto;
            animation: toastIn .25s ease-out forwards;
        }

        .toast-msg.toast-success {
            border-left: 3px solid #4caf50;
        }

        .toast-msg.toast-info {
            border-left: 3px solid var(--blue);
        }

        .toast-msg.toast-warn {
            border-left: 3px solid #fb8c00;
        }

        .toast-msg.toast-out {
            animation: toastOut .25s ease-in forwards;
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateY(12px) scale(.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes toastOut {
            from {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

            to {
                opacity: 0;
                transform: translateY(8px) scale(.96);
            }
        }

        .pg-header {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--blue) 0%, var(--blue-dark) 100%);
            border-radius: var(--radius);
            padding: 28px 32px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-md);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            min-height: 110px;
        }

        #hero-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            opacity: .5;
        }

        .pg-header-content {
            position: relative;
            z-index: 1;
        }

        .pg-header h4 {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pg-header p {
            margin: 5px 0 0;
            opacity: .82;
            font-size: .88rem;
        }

        .pg-header-icon {
            position: relative;
            z-index: 1;
            width: 56px;
            height: 56px;
            flex-shrink: 0;
            background: rgba(255, 255, 255, .14);
            border: 1.5px solid rgba(255, 255, 255, .22);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            backdrop-filter: blur(4px);
        }

        .pm-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .pm-tab-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 20px;
            border-radius: var(--radius);
            border: 1.5px solid var(--gray-light);
            background: #fff;
            color: var(--gray-mid);
            font-family: var(--font);
            font-weight: 700;
            font-size: .95rem;
            cursor: pointer;
            transition: background .2s, color .2s, border-color .2s, box-shadow .2s;
            box-shadow: var(--shadow-sm);
        }

        .pm-tab-btn i {
            font-size: 1.05rem;
        }

        .pm-tab-btn.pm-tab-deposits:hover {
            color: var(--blue-dark);
            border-color: var(--blue);
        }

        .pm-tab-btn.pm-tab-loans:hover {
            color: var(--green-dark);
            border-color: var(--green);
        }

        .pm-tab-btn.pm-tab-deposits.active {
            background: linear-gradient(135deg, var(--blue) 0%, var(--blue-dark) 100%);
            border-color: var(--blue-dark);
            color: #fff;
            box-shadow: var(--shadow-md);
        }

        .pm-tab-btn.pm-tab-loans.active {
            background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
            border-color: var(--green-dark);
            color: #fff;
            box-shadow: var(--shadow-md);
        }

        .pm-tab-panel {
            display: none;
        }

        .pm-tab-panel.active {
            display: block;
        }

        .filter-card {
            background: #fff;
            border-radius: var(--radius);
            padding: 20px 24px 22px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-light);
            border-top: 3px solid var(--blue);
        }

        /* ── Loans accent — matches the green Loans tab ── */
        .filter-card-loans {
            border-top-color: var(--green);
        }

        .filter-card-loans .filter-title {
            color: var(--green-dark);
        }

        .filter-card-loans .filter-title .ico {
            background: var(--green-dim);
            color: var(--green-dark);
        }

        .filter-card-loans .f-label {
            color: var(--green-dark);
        }

        .filter-card-loans .f-input:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 3px var(--green-dim);
        }

        .filter-card-loans .period-btn:hover {
            color: var(--green-dark);
        }

        .filter-card-loans .period-btn.active {
            color: var(--green-dark);
        }

        .filter-card-loans .period-range i {
            color: var(--green) !important;
        }

        .filter-card-loans .btn-ghost-eco {
            color: var(--green-dark);
            border-color: var(--green);
        }

        .filter-card-loans .btn-ghost-eco:hover {
            background: #f3f8ec;
        }

        .filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .filter-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--blue-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-title .ico {
            width: 26px;
            height: 26px;
            background: var(--blue-dim);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .7rem;
            color: var(--blue-dark);
        }

        .f-label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: var(--blue-dark);
            margin-bottom: 5px;
        }

        .f-input {
            width: 100%;
            border: 1.5px solid var(--gray-light);
            border-radius: 8px;
            padding: 8px 11px;
            font-size: .85rem;
            font-family: var(--font);
            color: var(--gray-text);
            background: #fff;
            transition: border-color .2s, box-shadow .2s;
        }

        .f-input:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(0, 130, 187, .12);
        }

        .btn-ghost-eco {
            background: #fff;
            color: var(--blue);
            border: 1.5px solid var(--blue);
            border-radius: 8px;
            padding: 8px 20px;
            font-size: .875rem;
            font-weight: 600;
            font-family: var(--font);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: background .2s, transform .15s;
            height: 38px;
        }

        .btn-ghost-eco:hover {
            background: #f0f8ff;
            transform: translateY(-1px);
        }

        .btn-export {
            background: var(--gray-bg);
            color: var(--gray-text);
            border: 1.5px solid var(--gray-light);
            border-radius: 8px;
            padding: 8px 16px;
            font-size: .82rem;
            font-weight: 600;
            font-family: var(--font);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background .2s, opacity .2s;
        }

        .btn-export:hover {
            background: var(--gray-light);
        }

        .btn-export.exporting {
            opacity: .7;
            cursor: not-allowed;
            pointer-events: none;
        }

        .btn-export .export-spinner {
            display: none;
            width: 12px;
            height: 12px;
            border: 2px solid rgba(70, 70, 70, .3);
            border-top-color: var(--gray-text);
            border-radius: 50%;
            animation: spin .6s linear infinite;
        }

        .btn-export.exporting .export-spinner {
            display: inline-block;
        }

        .btn-export.exporting .export-icon {
            display: none;
        }

        .filter-action-col {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding-bottom: 1px;
        }

        .period-nav {
            display: flex;
            gap: 6px;
            background: var(--gray-bg);
            border: 1px solid var(--gray-light);
            border-radius: 10px;
            padding: 4px;
        }

        .period-btn {
            border: none;
            background: transparent;
            color: var(--gray-mid);
            font-family: var(--font);
            font-weight: 600;
            font-size: .85rem;
            padding: 8px 18px;
            border-radius: 8px;
            cursor: pointer;
            transition: background .2s, color .2s, box-shadow .2s;
        }

        .period-btn:hover {
            color: var(--blue-dark);
        }

        .period-btn.active {
            background: #fff;
            color: var(--blue-dark);
            box-shadow: var(--shadow-sm);
        }

        .period-range {
            font-size: .78rem;
            color: var(--gray-mid);
            margin-top: 8px;
        }

        .period-range strong {
            color: var(--gray-text);
            font-family: var(--font-mono);
        }

        .currency-section {
            margin-bottom: 20px;
        }

        .currency-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .currency-section-title h5 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--gray-text);
            margin: 0;
        }

        .currency-section-badge {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 20px;
            background: var(--blue-dim);
            color: var(--blue-dark);
            border: 1px solid rgba(0, 130, 187, .2);
        }

        /* ── Loans accent — green identity vs. the blue deposits tab ── */
        .currency-section-accent-loans .currency-section-title h5 {
            color: var(--green-dark);
        }

        .currency-section-accent-loans .currency-section-badge {
            background: var(--green-dim);
            color: var(--green-dark);
            border-color: rgba(102, 148, 56, .35);
        }

        .currency-section-accent-loans .movers-card {
            border-color: rgba(102, 148, 56, .3);
            box-shadow: 0 2px 10px rgba(102, 148, 56, .08);
        }

        .currency-section-accent-loans .tab-nav {
            background: linear-gradient(90deg, rgba(102, 148, 56, .08), rgba(102, 148, 56, .02));
        }

        .currency-section-accent-loans .tab-btn:hover {
            color: var(--green-dark);
            background: rgba(102, 148, 56, .06);
        }

        .currency-section-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 28px 0 14px;
        }

        .currency-section-heading .line {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, rgba(102, 148, 56, .35), transparent);
        }

        .currency-section-heading h4 {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--green-dark);
            margin: 0;
            white-space: nowrap;
        }

        .currency-section-heading h4 i {
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
        }

        .currency-section-heading small {
            color: var(--gray-mid);
            font-weight: 600;
            font-size: .74rem;
        }

        #active-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 14px;
            min-height: 0;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--blue-dim);
            color: var(--blue-dark);
            font-size: .75rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid rgba(0, 130, 187, .2);
        }

        .chip .chip-x {
            cursor: pointer;
            opacity: .6;
            font-size: .8rem;
            border: 0;
            background: transparent;
            color: inherit;
            padding: 0;
        }

        .chip .chip-x:hover {
            opacity: 1;
        }

        .movers-card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid var(--gray-light);
        }

        .tab-nav {
            display: flex;
            border-bottom: 2px solid var(--gray-light);
            background: var(--gray-bg);
            padding: 0 20px;
            gap: 4px;
        }

        .tab-btn {
            padding: 14px 20px;
            border: none;
            background: transparent;
            font-family: var(--font);
            font-weight: 600;
            font-size: .9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: var(--gray-mid);
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: color .2s, border-color .2s, background .2s;
            border-radius: 8px 8px 0 0;
            position: relative;
        }

        .tab-btn:hover {
            color: var(--blue-dark);
            background: rgba(0, 130, 187, .04);
        }

        .tab-btn[aria-selected="true"].gain-tab {
            color: var(--gain-text);
            border-bottom-color: #4caf50;
            background: rgba(76, 175, 80, .06);
        }

        .tab-btn[aria-selected="true"].loss-tab {
            color: var(--loss-text);
            border-bottom-color: #f44336;
            background: rgba(244, 67, 54, .06);
        }

        .tab-badge {
            font-size: .7rem;
            padding: 2px 9px;
            border-radius: 20px;
            font-weight: 700;
            transition: opacity .2s;
        }

        .gain-badge {
            background: var(--gain-bg);
            color: var(--gain-text);
        }

        .loss-badge {
            background: var(--loss-bg);
            color: var(--loss-text);
        }

        .tab-panel {
            display: none;
            padding: 20px;
        }

        .tab-panel.active {
            display: block;
            animation: fadeUp .3s ease-out;
        }

        .tab-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tab-topbar-info {
            font-size: .82rem;
            color: var(--gray-mid);
            font-weight: 500;
        }

        .tab-topbar-info strong {
            color: var(--gray-text);
        }

        .amount-note {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--blue-dim);
            color: var(--blue-dark);
            border: 1px solid rgba(0, 130, 187, .18);
            padding: 5px 10px;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
        }

        .skeleton-table {
            width: 100%;
        }

        .skeleton-row {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .skeleton-cell {
            height: 14px;
            border-radius: 4px;
            background: linear-gradient(90deg, #eee 25%, #f5f5f5 50%, #eee 75%);
            background-size: 200% 100%;
            animation: shimmer 1.2s infinite;
        }

        .empty-state {
            text-align: center;
            padding: 52px 20px;
            display: none;
        }

        .empty-state .empty-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--gray-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin: 0 auto 16px;
            color: var(--gray-mid);
        }

        .empty-state h5 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--gray-text);
            margin-bottom: 6px;
        }

        .empty-state p {
            font-size: .85rem;
            color: var(--gray-mid);
            margin: 0 0 16px;
        }

        .empty-hints {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
            margin-bottom: 16px;
        }

        .empty-hint-chip {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffcc80;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: .74rem;
            font-weight: 600;
        }

        .dataTables_wrapper .dataTables_filter {
            display: none;
        }

        .dataTables_wrapper .dataTables_length {
            display: none;
        }

        table.dataTable thead th {
            background: var(--blue) !important;
            color: #fff !important;
            border: none !important;
            font-weight: 600;
            font-size: .8rem;
            padding: 11px 14px;
            font-family: var(--font);
        }

        table.dataTable tbody tr:hover td {
            background: rgba(0, 130, 187, .03) !important;
        }

        table.dataTable tbody td {
            font-size: .85rem;
            padding: 10px 14px;
            color: var(--gray-text);
            border-bottom: 1px solid var(--gray-light);
            vertical-align: middle;
        }

        .num-cell {
            font-family: var(--font-mono);
            font-size: .82rem;
        }

        .badge-gain,
        .badge-loss {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 6px;
            padding: 3px 9px;
            font-size: .78rem;
            font-weight: 700;
            font-family: var(--font-mono);
        }

        .badge-gain {
            background: var(--gain-bg);
            color: var(--gain-text);
            border: 1px solid var(--gain-border);
        }

        .badge-loss {
            background: var(--loss-bg);
            color: var(--loss-text);
            border: 1px solid var(--loss-border);
        }

        .pct-gain {
            color: var(--gain-text);
            font-size: .78rem;
            font-weight: 600;
        }

        .pct-loss {
            color: var(--loss-text);
            font-size: .78rem;
            font-weight: 600;
        }

        .table-live-wrapper {
            position: relative;
        }

        .mobile-cards {
            display: none;
        }

        .mobile-card {
            border: 1px solid var(--gray-light);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 10px;
            background: #fff;
            box-shadow: var(--shadow-sm);
        }

        .mobile-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            gap: 10px;
        }

        .mobile-card-name {
            font-weight: 700;
            font-size: .9rem;
            color: var(--gray-text);
        }

        .mobile-card-cif {
            font-size: .75rem;
            color: var(--gray-mid);
            margin-top: 2px;
        }

        .mobile-card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 16px;
            font-size: .8rem;
        }

        .mobile-card-lbl {
            color: var(--gray-mid);
            font-size: .7rem;
            margin-bottom: 1px;
        }

        .mobile-card-val {
            font-weight: 600;
            color: var(--gray-text);
            font-family: var(--font-mono);
        }

        @keyframes shimmer {
            from {
                background-position: 200% 0;
            }

            to {
                background-position: -200% 0;
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 767px) {
            .mobile-cards {
                display: block;
            }

            .desktop-table {
                display: none !important;
            }

            .pg-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }

            .pg-header-icon {
                display: none;
            }

            .tab-nav {
                padding: 0 10px;
            }

            .tab-btn {
                padding: 12px 12px;
                font-size: .82rem;
            }

            .filter-action-col {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div id="toast-container" aria-live="polite" aria-atomic="false"></div>

    <div class="container-fluid">

        <div class="pg-header">
            <canvas id="hero-canvas" aria-hidden="true" focusable="false"></canvas>

            <div class="pg-header-content">
                <h4>
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    Top Movers
                </h4>
                <p>Track top customer balance movements across branches, split by Local (LCY) and Foreign (FCY) currency.
                    @if ($lastMovementDate)
                        Data as of <strong>{{ \Carbon\Carbon::parse($lastMovementDate)->format('d M Y') }}</strong>.
                    @endif
                </p>
            </div>

            <div class="pg-header-icon" aria-hidden="true">
                <i class="fas fa-exchange-alt"></i>
            </div>
        </div>

        <div class="pm-tabs" role="tablist" aria-label="Top Movers section">
            <button type="button" class="pm-tab-btn pm-tab-deposits active" id="pm_tab_btn_deposits"
                data-panel="deposits" role="tab" aria-selected="true">
                <i class="fas fa-sack-dollar" aria-hidden="true"></i> Deposits
            </button>
            <button type="button" class="pm-tab-btn pm-tab-loans" id="pm_tab_btn_loans"
                data-panel="loans" role="tab" aria-selected="false">
                <i class="fas fa-hand-holding-dollar" aria-hidden="true"></i> Loans
            </button>
        </div>

        <div class="pm-tab-panel active" id="pm_panel_deposits">
            @include('finance.top-movers._deposits_dashboard', ['deposits' => $deposits])
        </div>

        <div class="pm-tab-panel" id="pm_panel_loans">

        <div class="filter-card filter-card-loans" role="search" aria-label="Filter loan movers">
            <div class="filter-header">
                <span class="filter-title">
                    <span class="ico" aria-hidden="true">
                        <i class="fas fa-sliders-h"></i>
                    </span>
                    Loan Filters
                </span>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-5 col-lg-4">
                    <label class="f-label">Period</label>

                    <div class="period-nav" role="tablist" aria-label="Movement period" id="period-nav-root">
                        <button type="button" class="period-btn active" id="period_btn_daily" data-period="daily"
                            role="tab" aria-selected="true">Daily</button>
                        <button type="button" class="period-btn" id="period_btn_weekly" data-period="weekly"
                            role="tab" aria-selected="false">Weekly</button>
                        <button type="button" class="period-btn" id="period_btn_monthly" data-period="monthly"
                            role="tab" aria-selected="false">Monthly</button>
                    </div>

                    <div class="period-range" id="period-range">
                        <i class="fas fa-info-circle" style="color:var(--blue);font-size:.7rem;" aria-hidden="true"></i>
                        Comparing <span id="period-range-text">—</span>
                    </div>
                </div>

                <div class="col-12 col-md-4 col-lg-4">
                    <label class="f-label" for="filter_branch">Branch</label>
                    <select id="filter_branch" class="f-input">
                        <option value="">All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch }}">{{ $branch }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3 col-lg-4 filter-action-col">
                    <button id="btn_reset" class="btn-ghost-eco" aria-label="Reset all filters">
                        <i class="fas fa-undo" aria-hidden="true"></i>
                        Reset
                    </button>
                </div>
            </div>

            <div id="active-chips" aria-live="polite" aria-label="Active filters"></div>
        </div>

        @include('finance.top-movers._currency_section', [
            'ccy' => 'loans_lcy',
            'ccyLabel' => 'Local Currency (LCY)',
            'ccyNote' => 'Amounts in KES',
            'showCurrencyCol' => false,
            'showSegmentCol' => true,
            'sectionAccent' => 'loans',
        ])

        @include('finance.top-movers._currency_section', [
            'ccy' => 'loans_fcy',
            'ccyLabel' => 'Foreign Currency (FCY)',
            'ccyNote' => 'KES-equivalent loan book outstanding',
            'showCurrencyCol' => false,
            'showSegmentCol' => true,
            'sectionAccent' => 'loans',
        ])

        </div>
    </div>
@endsection

@push('datatables-styles')
    <link rel="stylesheet" href="{{ asset('assets/css/datatables/jquery.dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/datatables/buttons.dataTables.min.css') }}">
@endpush

@push('datatables-scripts')
    <script src="{{ asset('assets/js/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/js/datatables/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/js/datatables/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/js/datatables/pdfmake.min.js') }}"></script>
    <script src="{{ asset('assets/js/datatables/vfs_fonts.js') }}"></script>
    <script src="{{ asset('assets/js/datatables/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/js/datatables/buttons.print.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/buttons.server-side.js') }}"></script>
@endpush

@push('scripts')
    <script src="{{ asset('js/easter-egg.js') }}"></script>

    <script>
        $(function() {
            const LOANS_DATA_URL = @json(route('finance.top-movers.loans-data'));

            const ALL_KEYS = ['loans_lcy', 'loans_fcy'];
            const SECTION_KIND = {
                loans_lcy: 'loans',
                loans_fcy: 'loans'
            };
            const DIRECTIONS = ['gain', 'loss'];

            let currentPeriod = 'daily';
            let shouldBuildMissing = true;

            const tables = {}; // e.g. tables['loans_lcy_gain'] = DataTable instance
            const tabInited = {};
            ALL_KEYS.forEach(ccy => tabInited[ccy] = { gain: false, loss: false });

            restoreFromURL();
            initHeroCanvas();
            buildChips();
            updatePeriodButtons();

            $('#pm_tab_btn_deposits, #pm_tab_btn_loans').on('click', function() {
                const panel = $(this).data('panel');

                $('.pm-tab-btn').removeClass('active').attr('aria-selected', 'false');
                $(this).addClass('active').attr('aria-selected', 'true');
                $('.pm-tab-panel').removeClass('active');
                $(`#pm_panel_${panel}`).addClass('active');

                if (panel === 'loans') {
                    // DataTables mis-measures column widths while its container is
                    // display:none — recalculate now that the Loans panel is visible.
                    ALL_KEYS.forEach(function(ccy) {
                        DIRECTIONS.forEach(function(dir) {
                            const key = `${ccy}_${dir}`;
                            if (tables[key] && tabInited[ccy][dir]) {
                                tables[key].columns.adjust().draw(false);
                            }
                        });
                    });
                }
            });

            ALL_KEYS.forEach(function(ccy) {
                tabInited[ccy].gain = true;
                tables[`${ccy}_gain`] = makeTable(ccy, 'gain');

                $(`#tab_btn_${ccy}_gain`).on('click', () => switchTab(ccy, 'gain'));
                $(`#tab_btn_${ccy}_loss`).on('click', () => switchTab(ccy, 'loss'));

                $(`#tab-nav-root-${ccy}`).on('keydown', function(e) {
                    const active = DIRECTIONS.find(d => document.getElementById(`tab_btn_${ccy}_${d}`)
                        .getAttribute('aria-selected') === 'true');

                    const idx = DIRECTIONS.indexOf(active);
                    let next = idx;

                    if (e.key === 'ArrowRight') next = (idx + 1) % DIRECTIONS.length;
                    if (e.key === 'ArrowLeft') next = (idx - 1 + DIRECTIONS.length) % DIRECTIONS.length;
                    if (e.key === 'Home') next = 0;
                    if (e.key === 'End') next = DIRECTIONS.length - 1;

                    if (next !== idx) {
                        e.preventDefault();
                        switchTab(ccy, DIRECTIONS[next]);
                        document.getElementById(`tab_btn_${ccy}_${DIRECTIONS[next]}`).focus();
                    }
                });

                DIRECTIONS.forEach(function(dir) {
                    $(`#export_${ccy}_${dir}`).on('click', function() {
                        exportCSV(ccy, dir.toUpperCase(), $(this));
                    });
                });
            });

            $('#period-nav-root .period-btn').on('click', function() {
                const period = $(this).data('period');

                if (period === currentPeriod) {
                    return;
                }

                currentPeriod = period;
                updatePeriodButtons();
                reloadAll();
            });

            $('#filter_branch').on('change', () => reloadAll());

            $('#btn_reset').on('click', function() {
                currentPeriod = 'daily';
                updatePeriodButtons();
                $('#filter_branch').val('');

                reloadAll(false);
            });

            $(document).on('click', '.chip-x', function() {
                const key = $(this).data('key');

                if (key === 'period') {
                    currentPeriod = 'daily';
                    updatePeriodButtons();
                }

                if (key === 'branch') {
                    $('#filter_branch').val('');
                }

                reloadAll(false);
            });

            function restoreFromURL() {
                const params = new URLSearchParams(window.location.search);
                const period = params.get('period');

                if (period && ['daily', 'weekly', 'monthly'].includes(period)) {
                    currentPeriod = period;
                }

                if (params.get('branch')) $('#filter_branch').val(params.get('branch'));
            }

            function pushState() {
                const params = new URLSearchParams({
                    period: currentPeriod,
                    branch: $('#filter_branch').val()
                });

                [...params.entries()].forEach(([key, value]) => {
                    if (!value) params.delete(key);
                });

                const qs = params.toString();
                history.replaceState(null, '', qs ? '?' + qs : window.location.pathname);
            }

            function updatePeriodButtons() {
                ['daily', 'weekly', 'monthly'].forEach(function(period) {
                    const $btn = $(`#period_btn_${period}`);
                    const isActive = period === currentPeriod;
                    $btn.toggleClass('active', isActive);
                    $btn.attr('aria-selected', String(isActive));
                });
            }

            function reloadAll(buildMissing = true) {
                shouldBuildMissing = buildMissing;

                buildChips();
                pushState();

                ALL_KEYS.forEach(function(ccy) {
                    DIRECTIONS.forEach(function(dir) {
                        const key = `${ccy}_${dir}`;
                        if (tables[key] && tabInited[ccy][dir]) {
                            tables[key].ajax.reload();
                        }
                    });
                });
            }

            window.switchTab = function(ccy, tab) {
                const $root = $(`#tab-nav-root-${ccy}`).closest('.movers-card');

                $root.find('.tab-panel').removeClass('active');
                $root.find(`#panel_${ccy}_${tab}`).addClass('active');

                DIRECTIONS.forEach(function(t) {
                    const btn = document.getElementById(`tab_btn_${ccy}_${t}`);
                    btn.setAttribute('aria-selected', String(t === tab));
                    btn.tabIndex = t === tab ? 0 : -1;
                });

                const key = `${ccy}_${tab}`;

                if (!tabInited[ccy][tab]) {
                    tabInited[ccy][tab] = true;
                    tables[key] = makeTable(ccy, tab);
                } else if (tables[key]) {
                    setTimeout(() => tables[key].columns.adjust().draw(false), 50);
                }
            };

            function getFilters(ccy, direction) {
                return {
                    period: currentPeriod,
                    currency_type: ccy.replace('loans_', '').toUpperCase(),
                    direction: direction.toUpperCase(),
                    branch_code: $('#filter_branch').val(),
                    build_missing: shouldBuildMissing ? 1 : 0
                };
            }

            function buildCols(direction, showCurrencyCol, showSegmentCol) {
                const isGain = direction === 'gain';

                const cols = [{
                        data: 'cif',
                        name: 'cif',
                        title: 'CIF',
                        defaultContent: '—'
                    },
                    {
                        data: 'customer_name',
                        name: 'customer_name',
                        title: 'Customer',
                        defaultContent: '—'
                    },
                    {
                        data: 'branch_code',
                        name: 'branch_code',
                        title: 'Branch',
                        defaultContent: '—'
                    }
                ];

                if (showCurrencyCol) {
                    cols.push({
                        data: 'currency',
                        name: 'currency',
                        title: 'Currency',
                        defaultContent: '—'
                    });
                }

                if (showSegmentCol) {
                    cols.push({
                        data: 'business_segment',
                        name: 'business_segment',
                        title: 'Segment',
                        defaultContent: '—'
                    });
                }

                cols.push({
                        data: 'start_balance_fmt',
                        name: 'start_balance',
                        title: 'Previous Balance',
                        className: 'text-end num-cell',
                        render: function(data) {
                            return `<span class="num-cell">${data || '0.00'}</span>`;
                        }
                    },
                    {
                        data: 'end_balance_fmt',
                        name: 'end_balance',
                        title: 'Current Balance',
                        className: 'text-end num-cell',
                        render: function(data) {
                            return `<span class="num-cell">${data || '0.00'}</span>`;
                        }
                    },
                    {
                        data: 'movement',
                        name: 'movement',
                        title: 'Day Movement',
                        className: 'text-end',
                        render: function(data, type, row) {
                            const value = row.movement_fmt || '0.00';

                            if (isGain) {
                                return `<span class="badge-gain"><i class="fas fa-arrow-up fa-xs" aria-hidden="true"></i>${value}</span>`;
                            }

                            return `<span class="badge-loss"><i class="fas fa-arrow-down fa-xs" aria-hidden="true"></i>${value}</span>`;
                        }
                    },
                    {
                        data: 'pct_change',
                        name: 'pct_change',
                        title: '% Change',
                        className: 'text-end',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            if (data === null || data === undefined || data === '') {
                                return '<span class="text-muted"><abbr title="Not available because previous balance was zero">—</abbr></span>';
                            }

                            const value = parseFloat(data);
                            const cls = value >= 0 ? 'pct-gain' : 'pct-loss';
                            const sign = value >= 0 ? '+' : '';

                            return `<span class="${cls}">${sign}${value.toFixed(2)}%</span>`;
                        }
                    }
                );

                return cols;
            }

            function makeTable(ccy, direction) {
                const key = `${ccy}_${direction}`;
                const kind = SECTION_KIND[ccy];
                const showCurrencyCol = ccy === 'fcy';
                const showSegmentCol = kind === 'loans';
                const movementColIdx = (showCurrencyCol || showSegmentCol) ? 6 : 5;
                const order = direction === 'gain' ? [
                    [movementColIdx, 'desc']
                ] : [
                    [movementColIdx, 'asc']
                ];

                return $(`#table_${key}`).DataTable({
                    processing: false,
                    serverSide: true,
                    searching: false,
                    lengthChange: false,
                    pageLength: 10,
                    order: order,
                    ajax: {
                        url: LOANS_DATA_URL,
                        timeout: 20000,
                        data: function(d) {
                            return $.extend({}, d, getFilters(ccy, direction));
                        },
                        beforeSend: function() {
                            reqStart(key);
                            showSkeleton(key);
                            $(`#${key}_count`).addClass('loading');
                        },
                        complete: function() {
                            reqEnd(key);
                        },
                        error: function(xhr, status) {
                            hideSkeleton(key, false);
                            $(`#${key}_count`).removeClass('loading').text('—');
                            $(`#${key}_info`).html('');

                            const message = status === 'timeout' ?
                                'Loading took too long. Please try again.' :
                                'Unable to load top movers. Please try again.';

                            toast(message, 'warn');
                        }
                    },
                    columns: buildCols(direction, showCurrencyCol, showSegmentCol),
                    drawCallback: function(settings) {
                        const json = settings.json || {};
                        const rows = json.data || [];
                        const total = json.recordsFiltered ?? rows.length;
                        const hasRows = rows.length > 0;

                        hideSkeleton(key, hasRows);
                        renderMobileCards(key, rows, direction);

                        $(`#${key}_count`).removeClass('loading').text(total.toLocaleString());

                        $(`#${key}_info`).html(hasRows ?
                            `Showing <strong>${rows.length}</strong> ${direction === 'gain' ? 'gainers' : 'losers'}` :
                            ''
                        );

                        if (json.period_start && json.period_end) {
                            updatePeriodRangeText(json.period_start, json.period_end);
                        }
                    },
                    language: {
                        processing: '',
                        emptyTable: '',
                        info: '',
                        infoEmpty: '',
                        zeroRecords: '',
                        paginate: {
                            previous: '‹',
                            next: '›'
                        }
                    }
                });
            }

            function updatePeriodRangeText(start, end) {
                const fmt = d => new Date(d).toLocaleDateString(undefined, {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });

                $('#period-range-text').text(`${fmt(start)} → ${fmt(end)}`);
            }

            function showSkeleton(key) {
                $(`#skeleton_${key}`).show();
                $(`#empty_${key}`).hide();
                $(`#mobile_${key}_cards`).empty();
                setTableVisible(key, false);
            }

            function hideSkeleton(key, hasRows) {
                $(`#skeleton_${key}`).hide();

                if (hasRows) {
                    $(`#empty_${key}`).hide();
                    setTableVisible(key, true);
                } else {
                    setTableVisible(key, false);
                    $(`#empty_${key}`).show();
                    buildEmptyHints(key);
                }
            }

            function setTableVisible(key, visible) {
                const $table = $(`#table_${key}`);
                const $wrapper = $table.closest('.dataTables_wrapper');

                if ($wrapper.length) {
                    $wrapper.toggle(visible);
                } else {
                    $table.toggle(visible);
                }
            }

            const pending = new Set();

            function reqStart(id) {
                pending.add(id);
            }

            function reqEnd(id) {
                pending.delete(id);
            }

            function renderMobileCards(key, rows, direction) {
                const $container = $(`#mobile_${key}_cards`).empty();

                if (!rows || !rows.length) {
                    return;
                }

                const isGain = direction === 'gain';

                rows.forEach(function(row) {
                    const badge = isGain ?
                        `<span class="badge-gain"><i class="fas fa-arrow-up fa-xs" aria-hidden="true"></i>${row.movement_fmt || '0.00'}</span>` :
                        `<span class="badge-loss"><i class="fas fa-arrow-down fa-xs" aria-hidden="true"></i>${row.movement_fmt || '0.00'}</span>`;

                    const pct = renderPct(row.pct_change);
                    const currencyRow = row.currency ?
                        `<div>
                            <div class="mobile-card-lbl">Currency</div>
                            <div class="mobile-card-val">${escapeHtml(row.currency)}</div>
                        </div>` : '';
                    const segmentRow = row.business_segment ?
                        `<div>
                            <div class="mobile-card-lbl">Segment</div>
                            <div class="mobile-card-val">${escapeHtml(row.business_segment)}</div>
                        </div>` : '';

                    $container.append(`
                        <div class="mobile-card">
                            <div class="mobile-card-header">
                                <div>
                                    <div class="mobile-card-name">${escapeHtml(row.customer_name || '—')}</div>
                                    <div class="mobile-card-cif">CIF: ${escapeHtml(row.cif || '—')}</div>
                                </div>
                                ${badge}
                            </div>

                            <div class="mobile-card-grid">
                                <div>
                                    <div class="mobile-card-lbl">Branch</div>
                                    <div class="mobile-card-val">${escapeHtml(row.branch_code || '—')}</div>
                                </div>

                                <div>
                                    <div class="mobile-card-lbl">% Change</div>
                                    <div class="mobile-card-val">${pct}</div>
                                </div>

                                ${currencyRow}
                                ${segmentRow}

                                <div>
                                    <div class="mobile-card-lbl">Previous Balance</div>
                                    <div class="mobile-card-val">${row.start_balance_fmt || '0.00'}</div>
                                </div>

                                <div>
                                    <div class="mobile-card-lbl">Current Balance</div>
                                    <div class="mobile-card-val">${row.end_balance_fmt || '0.00'}</div>
                                </div>
                            </div>
                        </div>
                    `);
                });
            }

            function renderPct(value) {
                if (value === null || value === undefined || value === '') {
                    return '<abbr title="Not available because previous balance was zero">—</abbr>';
                }

                const parsed = parseFloat(value);
                const cls = parsed >= 0 ? 'pct-gain' : 'pct-loss';
                const sign = parsed >= 0 ? '+' : '';

                return `<span class="${cls}">${sign}${parsed.toFixed(2)}%</span>`;
            }

            function buildChips() {
                const branch = $('#filter_branch').val();
                const chips = [];

                if (currentPeriod !== 'daily') {
                    chips.push({
                        key: 'period',
                        label: `Period: ${currentPeriod.charAt(0).toUpperCase()}${currentPeriod.slice(1)}`
                    });
                }

                if (branch) {
                    chips.push({
                        key: 'branch',
                        label: `Branch: ${branch}`
                    });
                }

                const $chips = $('#active-chips').empty();

                chips.forEach(function(chip) {
                    $chips.append(`
                        <span class="chip">
                            ${chip.label}
                            <button type="button" class="chip-x" data-key="${chip.key}" aria-label="Remove ${chip.label} filter">
                                <span aria-hidden="true">×</span>
                            </button>
                        </span>
                    `);
                });
            }

            function buildEmptyHints(key) {
                const isLoans = key.startsWith('loans_');
                const branch = $('#filter_branch').val();
                const hints = [];

                if (currentPeriod !== 'daily') {
                    hints.push('Try switching to a shorter period (e.g. Daily)');
                }

                if (branch && !isLoans) {
                    hints.push(`Try removing the branch filter (${branch})`);
                }

                const $hints = $(`#empty_${key}_hints`).empty();

                hints.forEach(function(hint) {
                    $hints.append(`
                        <span class="empty-hint-chip">
                            <i class="fas fa-lightbulb" aria-hidden="true"></i>
                            ${hint}
                        </span>
                    `);
                });

                $(`#empty_${key}_msg`).text(hints.length ? '' : 'No records matched the current filters.');
            }

            function exportCSV(ccy, direction, $button) {
                if ($button.hasClass('exporting')) {
                    return;
                }

                $button.addClass('exporting');
                toast('Preparing export. Your download will begin shortly.', 'info', 4000);

                const url = LOANS_DATA_URL + '?' + $.param($.extend(getFilters(ccy, direction.toLowerCase()), {
                    export: 1
                }));

                const $iframe = $('<iframe>', {
                    src: url,
                    style: 'display:none',
                    id: 'export-iframe-' + ccy + '-' + direction.toLowerCase()
                }).appendTo('body');

                $iframe.on('load', function() {
                    $button.removeClass('exporting');
                    $iframe.remove();
                });

                setTimeout(function() {
                    $button.removeClass('exporting');
                    $iframe.remove();
                }, 10000);
            }

            function toast(message, type = 'info', duration = 3500) {
                const icons = {
                    success: 'fa-check-circle',
                    info: 'fa-info-circle',
                    warn: 'fa-exclamation-triangle'
                };

                const $toast = $(`
                    <div class="toast-msg toast-${type}" role="status" aria-live="assertive">
                        <i class="fas ${icons[type] || icons.info}" aria-hidden="true"></i>
                        <span>${message}</span>
                    </div>
                `).appendTo('#toast-container');

                setTimeout(function() {
                    $toast.addClass('toast-out');
                    setTimeout(() => $toast.remove(), 270);
                }, duration);
            }

            function escapeHtml(value) {
                return String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function initHeroCanvas() {
                const canvas = document.getElementById('hero-canvas');

                if (!canvas) {
                    return;
                }

                const ctx = canvas.getContext('2d');

                let width;
                let height;
                let points;
                let raf;

                const count = 55;
                const distance = 115;
                const speed = 0.32;

                function resize() {
                    const rect = canvas.parentElement.getBoundingClientRect();
                    width = canvas.width = rect.width;
                    height = canvas.height = rect.height;
                }

                function makePoint() {
                    return {
                        x: Math.random() * width,
                        y: Math.random() * height,
                        vx: (Math.random() - .5) * speed,
                        vy: (Math.random() - .5) * speed,
                        r: 1.4 + Math.random() * 1.8
                    };
                }

                function init() {
                    resize();
                    points = Array.from({
                        length: count
                    }, makePoint);
                }

                function frame() {
                    ctx.clearRect(0, 0, width, height);

                    for (let i = 0; i < points.length; i++) {
                        for (let j = i + 1; j < points.length; j++) {
                            const dx = points[i].x - points[j].x;
                            const dy = points[i].y - points[j].y;
                            const d = Math.sqrt(dx * dx + dy * dy);

                            if (d < distance) {
                                ctx.beginPath();
                                ctx.moveTo(points[i].x, points[i].y);
                                ctx.lineTo(points[j].x, points[j].y);
                                ctx.strokeStyle = `rgba(255,255,255,${(1 - d / distance) * .32})`;
                                ctx.lineWidth = .8;
                                ctx.stroke();
                            }
                        }
                    }

                    points.forEach(function(point) {
                        ctx.beginPath();
                        ctx.arc(point.x, point.y, point.r, 0, Math.PI * 2);
                        ctx.fillStyle = 'rgba(255,255,255,.75)';
                        ctx.fill();

                        point.x += point.vx;
                        point.y += point.vy;

                        if (point.x < 0 || point.x > width) point.vx *= -1;
                        if (point.y < 0 || point.y > height) point.vy *= -1;
                    });

                    raf = requestAnimationFrame(frame);
                }

                document.addEventListener('visibilitychange', function() {
                    if (document.hidden) {
                        cancelAnimationFrame(raf);
                    } else {
                        frame();
                    }
                });

                window.addEventListener('resize', function() {
                    resize();

                    points.forEach(function(point) {
                        point.x = Math.min(point.x, width);
                        point.y = Math.min(point.y, height);
                    });
                });

                init();

                if (!document.hidden) {
                    frame();
                }
            }
        });
    </script>
@endpush
