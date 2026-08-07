@extends('layouts.finance.template')

@section('title', 'Customer Trend')

@push('styles')
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --eco-blue: #0082BB;
            --eco-blue-dark: #005B82;
            --eco-blue-soft: #EAF6FB;
            --eco-green: #BED600;
            --eco-green-dark: #669438;
            --eco-bg: #F4F7FA;
            --eco-text: #1F2937;
            --eco-muted: #6B7280;
            --eco-border: #E3EAF1;
            --eco-card: #FFFFFF;
            --eco-danger: #D92D20;
            --eco-danger-soft: #FEE4E2;
            --eco-success: #16A34A;
            --eco-success-soft: #DCFCE7;
            --eco-warning: #B7791F;
            --eco-warning-soft: #FEF3C7;
            --shadow-soft: 0 14px 35px rgba(0, 43, 71, .07);
            --shadow-light: 0 6px 18px rgba(0, 43, 71, .05);
            --radius-lg: 18px;
            --radius-md: 14px;
            --radius-sm: 10px;
        }

        body {
            background: var(--eco-bg);
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--eco-text);
        }

        .ct-page {
            padding: 14px 20px 24px;
            max-width: 1500px;
            margin: 0 auto;
        }

        /* Page heading */
        .ct-topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 10px;
        }

        .ct-kicker {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 10px;
            border-radius: 999px;
            background: var(--eco-blue-soft);
            color: var(--eco-blue-dark);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 8px;
        }

        .ct-title {
            margin: 0;
            color: var(--eco-blue-dark);
            font-size: clamp(1.35rem, 2vw, 1.85rem);
            line-height: 1.15;
            font-weight: 850;
        }

        .ct-subtitle {
            margin: 6px 0 0;
            color: var(--eco-muted);
            max-width: 720px;
            font-size: .9rem;
        }

        .ct-refresh {
            min-width: 210px;
            text-align: right;
            color: var(--eco-muted);
            font-size: .76rem;
            padding-top: 8px;
        }

        .ct-refresh strong {
            color: var(--eco-blue-dark);
            font-weight: 800;
        }

        /* Search */
        .search-card {
            background: var(--eco-card);
            border: 1px solid var(--eco-border);
            box-shadow: var(--shadow-soft);
            border-radius: var(--radius-lg);
            padding: 12px;
            margin-bottom: 12px;
        }

        .search-grid {
            display: grid;
            grid-template-columns: minmax(240px, 1.25fr) minmax(155px, .55fr) minmax(155px, .55fr) auto auto;
            gap: 10px;
            align-items: end;
        }

        .field-group {
            min-width: 0;
        }

        .field-label {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--eco-blue-dark);
            font-size: .74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }

        .field-control {
            width: 100%;
            height: 42px;
            border: 1px solid #CBD7E3;
            background: #FFFFFF;
            border-radius: 12px;
            color: var(--eco-text);
            font-size: .9rem;
            padding: 0 12px;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .field-control:focus {
            outline: none;
            border-color: var(--eco-blue);
            box-shadow: 0 0 0 4px rgba(0, 130, 187, .12);
        }

        .field-control.is-invalid {
            border-color: var(--eco-danger);
            box-shadow: 0 0 0 4px rgba(217, 45, 32, .10);
        }

        .mono-input {
            font-family: 'DM Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
            letter-spacing: .03em;
        }

        .field-error {
            display: none;
            color: var(--eco-danger);
            font-size: .76rem;
            margin-top: 6px;
            font-weight: 600;
        }

        .field-error.show {
            display: block;
        }

        .btn-ct {
            height: 42px;
            border: 0;
            border-radius: 12px;
            padding: 0 18px;
            font-size: .86rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
            white-space: nowrap;
        }

        .btn-ct:active {
            transform: translateY(1px);
        }

        .btn-primary-ct {
            background: var(--eco-blue-dark);
            color: #FFFFFF;
            box-shadow: 0 8px 18px rgba(0, 91, 130, .18);
        }

        .btn-primary-ct:hover {
            background: #004E70;
        }

        .btn-primary-ct:disabled {
            opacity: .72;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-ghost-ct {
            background: #F3F7FA;
            color: var(--eco-blue-dark);
            border: 1px solid var(--eco-border);
        }

        .btn-ghost-ct:hover {
            background: var(--eco-blue-soft);
        }

        .quick-range {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .quick-range span {
            color: var(--eco-muted);
            font-size: .76rem;
            font-weight: 700;
            margin-right: 2px;
        }

        .range-chip {
            border: 1px solid var(--eco-border);
            background: #FFFFFF;
            color: var(--eco-blue-dark);
            font-size: .75rem;
            font-weight: 800;
            border-radius: 999px;
            padding: 5px 10px;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease;
        }

        .range-chip:hover,
        .range-chip.active {
            background: var(--eco-blue-soft);
            border-color: rgba(0, 130, 187, .28);
        }

        /* Alerts / states */
        .alert-ct {
            display: none;
            border-radius: var(--radius-md);
            padding: 12px 14px;
            margin-bottom: 14px;
            font-size: .86rem;
            font-weight: 650;
            border: 1px solid transparent;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-ct.show {
            display: flex;
        }

        .alert-ct.error {
            background: var(--eco-danger-soft);
            color: #991B1B;
            border-color: #FDA29B;
        }

        .alert-ct.info {
            background: var(--eco-blue-soft);
            color: var(--eco-blue-dark);
            border-color: rgba(0, 130, 187, .18);
        }

        .empty-card,
        .loading-card {
            background: var(--eco-card);
            border: 1px dashed #C9D6E2;
            border-radius: var(--radius-lg);
            padding: 32px 18px;
            text-align: center;
            color: var(--eco-muted);
            box-shadow: var(--shadow-light);
        }

        .empty-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 14px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--eco-blue-soft);
            color: var(--eco-blue-dark);
            font-size: 1.45rem;
        }

        .empty-card h3 {
            margin: 0 0 6px;
            color: var(--eco-blue-dark);
            font-size: 1.02rem;
            font-weight: 850;
        }

        .empty-card p {
            margin: 0;
            font-size: .88rem;
        }

        .loading-card {
            display: none;
            padding: 18px;
        }

        .loading-card.show {
            display: block;
        }

        .skeleton-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr 1fr;
            gap: 12px;
        }

        .skeleton {
            position: relative;
            overflow: hidden;
            border-radius: 14px;
            height: 92px;
            background: #EEF3F7;
        }

        .skeleton.large {
            height: 170px;
            grid-column: 1 / -1;
        }

        .skeleton::after {
            content: "";
            position: absolute;
            inset: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .75), transparent);
            animation: shimmer 1.2s infinite;
        }

        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }

        .spinner-mini {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, .45);
            border-top-color: #FFFFFF;
            display: inline-block;
            animation: spin .65s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        #results-area {
            display: none;
        }

        #results-area.visible {
            display: block;
        }

        /* Result layout */
        .result-layout {
            display: grid;
            grid-template-columns: minmax(280px, 340px) 1fr;
            gap: 12px;
            align-items: start;
        }

        .side-stack,
        .main-stack {
            display: grid;
            gap: 12px;
        }

        .kpi-pair {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .panel {
            background: var(--eco-card);
            border: 1px solid var(--eco-border);
            box-shadow: var(--shadow-light);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .panel-header {
            min-height: 44px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--eco-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: linear-gradient(90deg, rgba(0, 91, 130, .045), rgba(255, 255, 255, 0));
        }

        .panel-title {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: var(--eco-blue-dark);
            font-size: .91rem;
            font-weight: 850;
            margin: 0;
        }

        .panel-subtle {
            color: var(--eco-muted);
            font-size: .76rem;
            font-weight: 650;
        }

        .panel-body {
            padding: 13px;
        }

        /* Profile */
        .profile-card {
            padding: 14px;
        }

        .avatar-row {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 12px;
        }

        .profile-avatar {
            width: 54px;
            height: 54px;
            border-radius: 17px;
            background: linear-gradient(135deg, var(--eco-blue-dark), var(--eco-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-size: 1.25rem;
            box-shadow: 0 10px 22px rgba(0, 91, 130, .20);
            flex-shrink: 0;
        }

        .profile-name {
            margin: 0;
            color: var(--eco-blue-dark);
            font-size: 1.02rem;
            font-weight: 850;
            line-height: 1.25;
        }

        .profile-cif {
            font-family: 'DM Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
            color: var(--eco-muted);
            margin-top: 3px;
            font-size: .78rem;
        }

        .profile-list {
            display: grid;
            gap: 7px;
            margin-top: 8px;
        }

        .profile-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border-top: 1px solid #EEF3F7;
            padding-top: 7px;
            font-size: .83rem;
        }

        .profile-item span:first-child {
            color: var(--eco-muted);
            font-weight: 650;
        }

        .profile-item span:last-child {
            color: var(--eco-text);
            font-weight: 800;
            text-align: right;
        }

        .badge-soft {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 4px 9px;
            background: var(--eco-blue-soft);
            color: var(--eco-blue-dark);
            font-size: .72rem;
            font-weight: 850;
        }

        /* KPIs */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(150px, 1fr));
            gap: 12px;
        }

        .kpi-card {
            background: var(--eco-card);
            border: 1px solid var(--eco-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-light);
            padding: 12px;
            min-height: 92px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .kpi-card.primary {
            background: linear-gradient(135deg, var(--eco-blue-dark), var(--eco-blue));
            color: #FFFFFF;
            grid-column: span 2;
        }

        .kpi-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            color: var(--eco-muted);
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 850;
        }

        .kpi-card.primary .kpi-label {
            color: rgba(255, 255, 255, .78);
        }

        .kpi-value {
            margin-top: 10px;
            font-size: 1.22rem;
            line-height: 1.1;
            color: var(--eco-blue-dark);
            font-weight: 900;
            font-family: 'DM Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
            word-break: break-word;
        }

        .kpi-card.primary .kpi-value {
            color: #FFFFFF;
            font-size: 1.35rem;
        }

        .kpi-value.pos {
            color: var(--eco-success);
        }

        .kpi-value.neg {
            color: var(--eco-danger);
        }

        .kpi-sub {
            margin-top: 8px;
            color: var(--eco-muted);
            font-size: .74rem;
            font-weight: 650;
        }

        .kpi-card.primary .kpi-sub {
            color: rgba(255, 255, 255, .75);
        }

        .kpi-card.loan {
            background: linear-gradient(135deg, #92400E, var(--eco-warning));
            color: #FFFFFF;
        }

        .kpi-card.loan .kpi-label {
            color: rgba(255, 255, 255, .78);
        }

        .kpi-card.loan .kpi-value {
            color: #FFFFFF;
            font-size: 1.35rem;
        }

        .kpi-card.loan .kpi-sub {
            color: rgba(255, 255, 255, .75);
        }

        .trend-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 7px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 900;
        }

        .trend-pill.pos {
            background: var(--eco-success-soft);
            color: var(--eco-success);
        }

        .trend-pill.neg {
            background: var(--eco-danger-soft);
            color: var(--eco-danger);
        }

        .trend-pill.neutral {
            background: #EEF3F7;
            color: var(--eco-muted);
        }

        /* Charts */
        .chart-wrap {
            height: 270px;
            position: relative;
        }

        .chart-actions {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .period-toggle {
            display: inline-flex;
            gap: 4px;
            padding: 4px;
            background: #F3F7FA;
            border-radius: 999px;
            border: 1px solid var(--eco-border);
        }

        .period-btn {
            background: transparent;
            color: var(--eco-blue-dark);
            border: 0;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: .72rem;
            font-weight: 850;
            cursor: pointer;
            transition: background .15s ease, color .15s ease;
        }

        .period-btn.active {
            background: var(--eco-blue-dark);
            color: #FFFFFF;
        }

        .mini-btn {
            height: 31px;
            border: 1px solid var(--eco-border);
            background: #FFFFFF;
            color: var(--eco-blue-dark);
            border-radius: 999px;
            padding: 0 10px;
            font-size: .72rem;
            font-weight: 850;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .mini-btn:hover {
            background: var(--eco-blue-soft);
        }

        .chart-stat-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 10px;
        }

        .chart-stat {
            background: #F8FAFC;
            border: 1px solid #EDF2F7;
            border-radius: 13px;
            padding: 8px 10px;
        }

        .chart-stat span {
            display: block;
            color: var(--eco-muted);
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 850;
            margin-bottom: 3px;
        }

        .chart-stat strong {
            color: var(--eco-blue-dark);
            font-size: .86rem;
            font-family: 'DM Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .chart-empty {
            min-height: 230px;
            display: none;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--eco-muted);
            border: 1px dashed #D9E3EC;
            border-radius: 14px;
            background: #FAFCFE;
            padding: 20px;
            font-size: .88rem;
        }

        .chart-empty.show {
            display: flex;
        }

        .panel-notice {
            display: none;
            margin-bottom: 12px;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: .82rem;
            font-weight: 650;
            background: var(--eco-warning-soft);
            color: var(--eco-warning);
            border: 1px solid #F6D68A;
        }

        .panel-notice.show {
            display: block;
        }

        /* Accounts */
        .account-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .account-filter {
            max-width: 280px;
            height: 36px;
            border: 1px solid #CBD7E3;
            border-radius: 999px;
            padding: 0 13px;
            font-size: .82rem;
            outline: none;
        }

        .account-filter:focus {
            border-color: var(--eco-blue);
            box-shadow: 0 0 0 4px rgba(0, 130, 187, .10);
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: 14px;
            border: 1px solid var(--eco-border);
        }

        .acc-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
            font-size: .81rem;
        }

        .acc-table th {
            background: #F5F8FB;
            color: var(--eco-blue-dark);
            padding: 8px 10px;
            text-align: left;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 900;
            border-bottom: 1px solid var(--eco-border);
            white-space: nowrap;
        }

        .acc-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #EEF3F7;
            color: var(--eco-text);
            vertical-align: middle;
        }

        .acc-table tbody tr:hover td {
            background: #F8FBFD;
        }

        .acc-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .badge-mono {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--eco-blue-soft);
            color: var(--eco-blue-dark);
            border-radius: 999px;
            padding: 5px 9px;
            font-size: .73rem;
            font-weight: 900;
            font-family: 'DM Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
            white-space: nowrap;
        }

        .copy-acct {
            border: 0;
            background: transparent;
            color: var(--eco-blue);
            cursor: pointer;
            padding: 0;
            font-size: .75rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: .72rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .status-badge.active,
        .status-badge.open {
            background: var(--eco-success-soft);
            color: var(--eco-success);
        }

        .status-badge.closed,
        .status-badge.close {
            background: var(--eco-danger-soft);
            color: var(--eco-danger);
        }

        .status-badge.dormant,
        .status-badge.inactive {
            background: var(--eco-warning-soft);
            color: var(--eco-warning);
        }

        .status-badge.unknown {
            background: #EEF3F7;
            color: var(--eco-muted);
        }

        .status-badge.loan-yes {
            background: var(--eco-warning-soft);
            color: var(--eco-warning);
        }

        .status-badge.loan-no {
            background: #EEF3F7;
            color: var(--eco-muted);
        }

        @media (max-width: 1180px) {
            .result-layout {
                grid-template-columns: 1fr;
            }

            .kpi-grid {
                grid-template-columns: repeat(2, minmax(150px, 1fr));
            }
        }

        @media (max-width: 980px) {
            .search-grid {
                grid-template-columns: 1fr 1fr;
            }

            .search-grid .btn-ct {
                width: 100%;
            }

            .ct-topbar {
                flex-direction: column;
            }

            .ct-refresh {
                text-align: left;
                min-width: auto;
                padding-top: 0;
            }
        }

        @media (max-width: 680px) {
            .ct-page {
                padding: 14px 12px 26px;
            }

            .search-grid,
            .kpi-grid,
            .kpi-pair,
            .chart-stat-row,
            .skeleton-grid {
                grid-template-columns: 1fr;
            }

            .kpi-card.primary {
                grid-column: span 1;
            }

            .panel-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .chart-actions {
                justify-content: flex-start;
            }

            .chart-wrap {
                height: 260px;
            }

            .account-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .account-filter {
                max-width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="ct-page">

        <div class="ct-topbar">
            <div>
                <div class="ct-kicker">
                    <i class="fa-solid fa-chart-line"></i>
                    Finance Dashboard
                </div>
                <h1 class="ct-title">Customer Trend</h1>
                <p class="ct-subtitle">Search a CIF to review balance history, daily movements, profile details and linked
                    accounts.</p>
            </div>
            <div class="ct-refresh">
                <div>Last refreshed</div>
                <strong id="last-refreshed">Not loaded yet</strong>
            </div>
        </div>

        <div class="search-card">
            <div class="search-grid">
                <div class="field-group">
                    <label class="field-label" for="cif-input">
                        <i class="fa-solid fa-fingerprint"></i>
                        CIF Number
                    </label>
                    <input type="text" id="cif-input" class="field-control mono-input" placeholder="Enter CIF number"
                        autocomplete="off">
                    <div id="cif-error" class="field-error">Please enter a CIF number before searching.</div>
                </div>

                <div class="field-group">
                    <label class="field-label" for="from-date">
                        <i class="fa-regular fa-calendar"></i>
                        From
                    </label>
                    <input type="date" id="from-date" class="field-control">
                </div>

                <div class="field-group">
                    <label class="field-label" for="to-date">
                        <i class="fa-regular fa-calendar-check"></i>
                        To
                    </label>
                    <input type="date" id="to-date" class="field-control" value="{{ now()->toDateString() }}">
                </div>

                <button type="button" id="search-btn" class="btn-ct btn-primary-ct" onclick="searchCustomer()">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Search</span>
                </button>

                <button type="button" class="btn-ct btn-ghost-ct" onclick="resetSearch()">
                    <i class="fa-solid fa-rotate-left"></i>
                    Clear
                </button>
            </div>

            {{--  <div class="quick-range">
                <span>Quick range:</span>
                <button type="button" class="range-chip" data-months="1" onclick="setQuickRange(this, 1)">1M</button>
                <button type="button" class="range-chip" data-months="3" onclick="setQuickRange(this, 3)">3M</button>
                <button type="button" class="range-chip" data-months="6" onclick="setQuickRange(this, 6)">6M</button>
                <button type="button" class="range-chip active" data-months="12"
                    onclick="setQuickRange(this, 12)">12M</button>
            </div>  --}}
        </div>

        <div id="message-area" class="alert-ct"></div>

        <div id="loading-area" class="loading-card">
            <div class="skeleton-grid">
                <div class="skeleton"></div>
                <div class="skeleton"></div>
                <div class="skeleton"></div>
                <div class="skeleton"></div>
                <div class="skeleton large"></div>
            </div>
        </div>

        <div id="empty-state" class="empty-card">
            <div class="empty-icon"><i class="fa-solid fa-magnifying-glass-chart"></i></div>
            <h3>Search for a customer</h3>
            <p>Enter a CIF number and select a date range to view customer trend details.</p>
        </div>

        <div id="results-area">

            <div class="result-layout">

                <aside class="side-stack">
                    <div class="panel profile-card">
                        <div class="avatar-row">
                            <div class="profile-avatar"><i class="fa-solid fa-user"></i></div>
                            <div>
                                <h2 class="profile-name" id="p-name">—</h2>
                                <div class="profile-cif" id="p-cif">CIF: —</div>
                            </div>
                        </div>

                        <div class="profile-list">
                            <div class="profile-item">
                                <span>Relationship Manager</span>
                                <span id="p-rm">—</span>
                            </div>
                            <div class="profile-item">
                                <span>Segment</span>
                                <span id="p-seg">—</span>
                            </div>
                            <div class="profile-item">
                                <span>Business</span>
                                <span><span class="badge-soft" id="p-business">—</span></span>
                            </div>
                            <div class="profile-item">
                                <span>Linked Accounts</span>
                                <span id="p-account-count">—</span>
                            </div>
                            <div class="profile-item">
                                <span>Loan Customer</span>
                                <span><span class="status-badge loan-no" id="p-has-loan">—</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-pair">
                        <div class="kpi-card primary">
                            <div>
                                <div class="kpi-label">
                                    <span>Current Balance</span>
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                                <div class="kpi-value" id="kpi-balance">—</div>
                            </div>
                            <div class="kpi-sub" id="kpi-as-of">As of —</div>
                        </div>

                        <div class="kpi-card loan">
                            <div>
                                <div class="kpi-label">
                                    <span>Loan Balance</span>
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                </div>
                                <div class="kpi-value" id="kpi-loan-balance">—</div>
                            </div>
                            <div class="kpi-sub" id="kpi-loan-as-of">As of —</div>
                        </div>
                    </div>
                </aside>

                <main class="main-stack">

                    <div class="kpi-grid">
                        <div class="kpi-card">
                            <div>
                                <div class="kpi-label">
                                    <span>Daily Movement</span>
                                    <span id="pill-daily" class="trend-pill neutral">—</span>
                                </div>
                                <div class="kpi-value" id="kpi-daily">—</div>
                            </div>
                            <div class="kpi-sub" id="kpi-daily-from">—</div>
                        </div>

                        <div class="kpi-card">
                            <div>
                                <div class="kpi-label">
                                    <span>MTD Movement</span>
                                    <span id="pill-mtd" class="trend-pill neutral">—</span>
                                </div>
                                <div class="kpi-value" id="kpi-mtd">—</div>
                            </div>
                            <div class="kpi-sub" id="kpi-mtd-from">—</div>
                        </div>

                        <div class="kpi-card">
                            <div>
                                <div class="kpi-label">
                                    <span>YTD Movement</span>
                                    <span id="pill-ytd" class="trend-pill neutral">—</span>
                                </div>
                                <div class="kpi-value" id="kpi-ytd">—</div>
                            </div>
                            <div class="kpi-sub" id="kpi-ytd-from">—</div>
                        </div>

                        <div class="kpi-card">
                            <div>
                                <div class="kpi-label">
                                    <span>Data Points</span>
                                    <i class="fa-solid fa-database"></i>
                                </div>
                                <div class="kpi-value" id="kpi-points">—</div>
                            </div>
                            <div class="kpi-sub">Balance snapshots</div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <div>
                                <h3 class="panel-title"><i class="fa-solid fa-chart-area"></i> Balance Trend</h3>
                                <div class="panel-subtle" id="trend-period-label">Showing selected date range</div>
                            </div>
                            <div class="chart-actions">
                                <div class="period-toggle">
                                    <button type="button" class="period-btn active"
                                        onclick="setPeriod(this,'all')">All</button>
                                    <button type="button" class="period-btn" onclick="setPeriod(this,'1y')">1Y</button>
                                    <button type="button" class="period-btn" onclick="setPeriod(this,'6m')">6M</button>
                                    <button type="button" class="period-btn" onclick="setPeriod(this,'3m')">3M</button>
                                    <button type="button" class="period-btn" onclick="setPeriod(this,'1m')">1M</button>
                                </div>
                                <button type="button" class="mini-btn" onclick="downloadTrendCsv()">
                                    <i class="fa-solid fa-download"></i>
                                    CSV
                                </button>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div id="trend-notice" class="panel-notice"></div>
                            <div class="chart-stat-row">
                                <div class="chart-stat">
                                    <span>Minimum</span>
                                    <strong id="stat-min">—</strong>
                                </div>
                                <div class="chart-stat">
                                    <span>Average</span>
                                    <strong id="stat-avg">—</strong>
                                </div>
                                <div class="chart-stat">
                                    <span>Maximum</span>
                                    <strong id="stat-max">—</strong>
                                </div>
                            </div>
                            <div id="trend-empty" class="chart-empty">
                                No balance trend data is available for the selected date range.
                            </div>
                            <div class="chart-wrap" id="trend-chart-wrap">
                                <canvas id="trend-chart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <div>
                                <h3 class="panel-title"><i class="fa-solid fa-chart-column"></i> Daily Movements</h3>
                                <div class="panel-subtle">Positive movements are green; negative movements are red.</div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div id="movement-empty" class="chart-empty">
                                Daily movement data will appear once at least two balance points are available.
                            </div>
                            <div class="chart-wrap" id="movement-chart-wrap">
                                <canvas id="movement-chart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title"><i class="fa-solid fa-building-columns"></i> Linked Accounts</h3>
                            <span id="acct-count" class="panel-subtle">—</span>
                        </div>
                        <div class="panel-body">
                            <div class="account-toolbar">
                                <input type="text" id="account-filter" class="account-filter"
                                    placeholder="Filter accounts..." oninput="filterAccounts()">
                                <span class="panel-subtle">Tip: use the copy icon beside an account number.</span>
                            </div>

                            <div class="table-wrap">
                                <table class="acc-table">
                                    <thead>
                                        <tr>
                                            <th>Account Number</th>
                                            <th>Description</th>
                                            <th>Class</th>
                                            <th>Branch</th>
                                            <th>Open Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="acct-tbody">
                                        <tr>
                                            <td colspan="6" style="text-align:center;color:#999;padding:20px;">No
                                                account records loaded.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <h3 class="panel-title"><i class="fa-solid fa-hand-holding-dollar"></i> Loan Accounts</h3>
                            <span id="loan-count" class="panel-subtle">—</span>
                        </div>
                        <div class="panel-body">
                            <div class="table-wrap">
                                <table class="acc-table">
                                    <thead>
                                        <tr>
                                            <th>Loan Account</th>
                                            <th>Product</th>
                                            <th>Status</th>
                                            <th>Outstanding (LCY)</th>
                                            <th>Currency</th>
                                            <th>Branch</th>
                                            <th>As At</th>
                                        </tr>
                                    </thead>
                                    <tbody id="loan-tbody">
                                        <tr>
                                            <td colspan="7" style="text-align:center;color:#999;padding:20px;">No
                                                loan records loaded.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </main>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const BASE = '/finance/customer-trend';

        let trendChart = null;
        let movementChart = null;
        let fullTrend = null;
        let currentFilteredTrend = null;
        let currentPeriod = 'all';
        let currentAccounts = [];

        const $ = id => document.getElementById(id);

        function escapeHtml(value) {
            return String(value ?? '—')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function fmt(n) {
            if (n === null || n === undefined || n === '') return '—';

            const num = Number(n);
            if (!Number.isFinite(num)) return '—';

            const abs = Math.abs(num);
            const pfx = num < 0 ? '-KES ' : 'KES ';

            if (abs >= 1e9) return pfx + (abs / 1e9).toFixed(2) + 'B';
            if (abs >= 1e6) return pfx + (abs / 1e6).toFixed(2) + 'M';
            if (abs >= 1e3) return pfx + (abs / 1e3).toFixed(2) + 'K';

            return pfx + abs.toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function fmtFull(n) {
            const num = Number(n);
            if (!Number.isFinite(num)) return '—';

            return (num < 0 ? '-KES ' : 'KES ') + Math.abs(num).toLocaleString('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function fmtDate(d) {
            if (!d) return '—';

            const dt = new Date(d);
            if (Number.isNaN(dt.getTime())) return '—';

            return dt.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        function nowLabel() {
            return new Date().toLocaleString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function setLoading(isLoading) {
            const btn = $('search-btn');

            if (isLoading) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-mini"></span><span>Searching</span>';
                $('loading-area').classList.add('show');
                $('empty-state').style.display = 'none';
                $('results-area').classList.remove('visible');
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i><span>Search</span>';
                $('loading-area').classList.remove('show');
            }
        }

        function showMessage(message, type = 'error') {
            const area = $('message-area');
            area.className = 'alert-ct show ' + type;
            area.innerHTML =
                `<i class="fa-solid ${type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info'}"></i><span>${escapeHtml(message)}</span>`;
        }

        function clearMessage() {
            const area = $('message-area');
            area.className = 'alert-ct';
            area.innerHTML = '';
        }

        function setFieldError(message) {
            $('cif-input').classList.add('is-invalid');
            $('cif-error').textContent = message;
            $('cif-error').classList.add('show');
        }

        function clearFieldError() {
            $('cif-input').classList.remove('is-invalid');
            $('cif-error').classList.remove('show');
        }

        async function fetchJson(url) {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                const error = new Error(data.message || `Request failed with status ${res.status}`);
                error.status = res.status;
                error.data = data;
                throw error;
            }

            return data;
        }

        async function searchCustomer() {
            clearMessage();
            clearFieldError();

            const cif = $('cif-input').value.trim();
            const from = $('from-date').value;
            const to = $('to-date').value;

            if (!cif) {
                setFieldError('Please enter a CIF number before searching.');
                $('cif-input').focus();
                return;
            }

            if (from && to && new Date(from) > new Date(to)) {
                showMessage('The From date cannot be later than the To date.', 'error');
                return;
            }

            setLoading(true);

            try {
                const profileUrl = `${BASE}/profile?cif=${encodeURIComponent(cif)}`;
                const profileData = await fetchJson(profileUrl);
                const profile = profileData.profile || profileData;

                renderProfile(profile);

                const query = new URLSearchParams({
                    cif,
                    from,
                    to
                });

                const [summaryResult, trendResult] = await Promise.allSettled([
                    fetchJson(`${BASE}/summary?cif=${encodeURIComponent(cif)}`),
                    fetchJson(`${BASE}/trend?${query}`)
                ]);

                if (summaryResult.status === 'fulfilled') {
                    renderSummary(summaryResult.value.summary || summaryResult.value);
                } else {
                    renderSummaryUnavailable();
                    showPanelNotice('trend-notice', 'Customer profile loaded, but summary data could not be loaded.');
                }

                if (trendResult.status === 'fulfilled') {
                    const trend = normalizeTrend(trendResult.value.trend || trendResult.value);
                    fullTrend = trend;
                    currentPeriod = 'all';
                    markActivePeriod('all');
                    renderTrend(trend);
                    hidePanelNotice('trend-notice');
                } else {
                    fullTrend = null;
                    currentFilteredTrend = null;
                    clearCharts();
                    showPanelNotice('trend-notice',
                        'Customer profile loaded, but balance trend data could not be loaded.');
                }

                $('last-refreshed').textContent = nowLabel();
                $('results-area').classList.add('visible');
                $('empty-state').style.display = 'none';

            } catch (e) {
                const notFound = e.status === 404;
                showMessage(notFound ? (e.data?.message || 'CIF not found. Please confirm the number and try again.') :
                    'We could not load customer trend data. Please try again.');
                $('empty-state').style.display = 'block';
                $('results-area').classList.remove('visible');
            } finally {
                setLoading(false);
            }
        }

        function renderProfile(p) {
            const accounts = Array.isArray(p.accounts) ? p.accounts : [];
            currentAccounts = accounts;

            $('p-name').textContent = p.customer_name || p.name || p.cif || 'Unknown Customer';
            $('p-cif').textContent = 'CIF: ' + (p.cif || '—');
            $('p-rm').textContent = p.rm_code || p.relationship_manager || 'N/A';
            $('p-seg').textContent = p.segment || 'Unknown';
            $('p-business').textContent = p.business || p.code_desc || '—';
            $('p-account-count').textContent = accounts.length.toLocaleString();
            $('acct-count').textContent = accounts.length + ' account' + (accounts.length === 1 ? '' : 's');

            renderAccounts(accounts);
            renderLoanBadge(!!p.has_loan);
            renderLoans(Array.isArray(p.loans) ? p.loans : []);

            $('kpi-loan-balance').textContent = fmt(p.loan_balance || 0);
            $('kpi-loan-as-of').textContent = p.loan_as_of_date ? 'As of ' + fmtDate(p.loan_as_of_date) : 'No loans on record';
        }

        function renderLoanBadge(hasLoan) {
            const el = $('p-has-loan');
            el.className = 'status-badge ' + (hasLoan ? 'loan-yes' : 'loan-no');
            el.textContent = hasLoan ? 'Yes' : 'No';
        }

        function renderLoans(loans) {
            const tbody = $('loan-tbody');
            $('loan-count').textContent = loans.length + ' loan' + (loans.length === 1 ? '' : 's');

            if (!loans.length) {
                tbody.innerHTML =
                    '<tr><td colspan="7" style="text-align:center;color:#999;padding:18px;">No loan records found for this customer.</td></tr>';
                return;
            }

            tbody.innerHTML = loans.map(loan => `
            <tr>
                <td><span class="badge-mono">${escapeHtml(loan.account || '—')}</span></td>
                <td>${escapeHtml(loan.product_code || '—')}</td>
                <td><span class="status-badge unknown">${escapeHtml(loan.status_bucket || loan.loan_status || '—')}</span></td>
                <td>${fmt(loan.outstanding_lcy)}</td>
                <td>${escapeHtml(loan.currency || '—')}</td>
                <td>${escapeHtml(loan.branch || '—')}</td>
                <td>${loan.as_at_date ? escapeHtml(fmtDate(loan.as_at_date)) : '—'}</td>
            </tr>
        `).join('');
        }

        function renderSummary(s) {
            $('kpi-balance').textContent = fmt(s.current_balance);
            $('kpi-as-of').textContent = s.as_of_date ? 'As of ' + fmtDate(s.as_of_date) : 'As of —';
            $('kpi-points').textContent = Number(s.data_points || 0).toLocaleString();

            renderMovementKpi('daily', s.daily_movement, s.daily_from ? 'From ' + fmtDate(s.daily_from) : 'No prior date');
            renderMovementKpi('mtd', s.mtd_movement, s.mtd_from ? 'From ' + fmtDate(s.mtd_from) : 'From start of month');
            renderMovementKpi('ytd', s.ytd_movement, s.ytd_from ? 'From ' + fmtDate(s.ytd_from) : 'From start of year');
        }

        function renderSummaryUnavailable() {
            $('kpi-balance').textContent = '—';
            $('kpi-as-of').textContent = 'Summary unavailable';
            $('kpi-points').textContent = '—';

            ['daily', 'mtd', 'ytd'].forEach(key => {
                $(`kpi-${key}`).textContent = '—';
                $(`kpi-${key}`).className = 'kpi-value';
                $(`kpi-${key}-from`).textContent = 'Unavailable';
                updateTrendPill(`pill-${key}`, null);
            });
        }

        function renderMovementKpi(key, value, subtitle) {
            const el = $(`kpi-${key}`);
            const num = Number(value || 0);

            el.textContent = fmt(num);
            el.className = 'kpi-value ' + (num > 0 ? 'pos' : num < 0 ? 'neg' : '');

            $(`kpi-${key}-from`).textContent = subtitle;
            updateTrendPill(`pill-${key}`, num);
        }

        function updateTrendPill(id, value) {
            const pill = $(id);

            if (value === null || value === undefined || !Number.isFinite(Number(value))) {
                pill.className = 'trend-pill neutral';
                pill.textContent = '—';
                return;
            }

            const num = Number(value);

            if (num > 0) {
                pill.className = 'trend-pill pos';
                pill.innerHTML = '<i class="fa-solid fa-arrow-up"></i> Up';
            } else if (num < 0) {
                pill.className = 'trend-pill neg';
                pill.innerHTML = '<i class="fa-solid fa-arrow-down"></i> Down';
            } else {
                pill.className = 'trend-pill neutral';
                pill.textContent = 'Flat';
            }
        }

        function normalizeTrend(trend) {
            return {
                labels: Array.isArray(trend.labels) ? trend.labels : [],
                dates: Array.isArray(trend.dates) ? trend.dates : [],
                balances: Array.isArray(trend.balances) ? trend.balances.map(v => v === null || v === undefined ? null : Number(v)) : [],
                loans: Array.isArray(trend.loans) ? trend.loans.map(v => v === null || v === undefined ? null : Number(v)) : []
            };
        }

        function renderTrend(trend) {
            const safeTrend = normalizeTrend(trend);
            currentFilteredTrend = safeTrend;

            const balanceOnly = extractBalanceOnly(safeTrend);

            updateTrendStats(balanceOnly);
            renderTrendChart(safeTrend);
            renderMovementChart(balanceOnly);
            updateTrendPeriodLabel();
        }

        function extractBalanceOnly(trend) {
            const labels = [],
                dates = [],
                balances = [];

            trend.dates.forEach((date, index) => {
                if (trend.balances[index] !== null && trend.balances[index] !== undefined) {
                    labels.push(trend.labels[index]);
                    dates.push(date);
                    balances.push(trend.balances[index]);
                }
            });

            return {
                labels,
                dates,
                balances,
                loans: []
            };
        }

        function updateTrendStats(trend) {
            const balances = (trend.balances || []).filter(v => Number.isFinite(Number(v)));

            if (!balances.length) {
                $('stat-min').textContent = '—';
                $('stat-avg').textContent = '—';
                $('stat-max').textContent = '—';
                return;
            }

            const min = Math.min(...balances);
            const max = Math.max(...balances);
            const avg = balances.reduce((sum, value) => sum + value, 0) / balances.length;

            $('stat-min').textContent = fmt(min);
            $('stat-avg').textContent = fmt(avg);
            $('stat-max').textContent = fmt(max);
        }

        function renderTrendChart(trend) {
            const hasData = trend.labels.length && (trend.balances.length || trend.loans.length);

            $('trend-empty').classList.toggle('show', !hasData);
            $('trend-chart-wrap').style.display = hasData ? 'block' : 'none';

            if (trendChart) trendChart.destroy();
            if (!hasData) return;

            const hasLoanData = (trend.loans || []).some(v => v !== null && v !== undefined);

            const datasets = [{
                label: 'Balance',
                data: trend.balances,
                borderColor: '#005B82',
                backgroundColor: 'rgba(0, 130, 187, .08)',
                borderWidth: 2.5,
                pointRadius: trend.labels.length <= 45 ? 3 : 0,
                pointHoverRadius: 6,
                fill: true,
                tension: .32,
                spanGaps: true,
                yAxisID: 'y'
            }];

            if (hasLoanData) {
                datasets.push({
                    label: 'Loan Balance',
                    data: trend.loans,
                    borderColor: '#B7791F',
                    backgroundColor: 'rgba(183, 121, 31, .08)',
                    borderWidth: 2.5,
                    borderDash: [5, 3],
                    pointRadius: trend.labels.length <= 45 ? 3 : 0,
                    pointHoverRadius: 6,
                    fill: false,
                    tension: .32,
                    spanGaps: true,
                    yAxisID: hasLoanData ? 'y1' : 'y'
                });
            }

            const ctx = $('trend-chart').getContext('2d');

            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trend.labels,
                    datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: hasLoanData,
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 10,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            filter: item => item.raw !== null && item.raw !== undefined,
                            callbacks: {
                                title: items => items?.[0]?.label || '',
                                label: item => `${item.dataset.label}: ${fmtFull(item.raw)}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxTicksLimit: 10,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            border: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 10
                                },
                                callback: value => fmt(value)
                            }
                        },
                        y1: {
                            display: hasLoanData,
                            position: 'right',
                            border: {
                                display: false
                            },
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                font: {
                                    size: 10
                                },
                                callback: value => fmt(value)
                            }
                        }
                    }
                }
            });
        }

        function renderMovementChart(trend) {
            const balances = trend.balances || [];
            const labels = trend.labels || [];
            const hasData = balances.length > 1;

            $('movement-empty').classList.toggle('show', !hasData);
            $('movement-chart-wrap').style.display = hasData ? 'block' : 'none';

            if (movementChart) movementChart.destroy();
            if (!hasData) return;

            const movements = balances.map((value, index) => index === 0 ? 0 : round2(value - balances[index - 1]));
            const chartLabels = labels.slice(1);
            const chartValues = movements.slice(1);
            const colors = chartValues.map(value => value >= 0 ? '#16A34A' : '#D92D20');

            const ctx = $('movement-chart').getContext('2d');

            movementChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Daily Movement',
                        data: chartValues,
                        backgroundColor: colors,
                        borderRadius: 6,
                        barPercentage: .78,
                        categoryPercentage: .76
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: item => 'Movement: ' + fmtFull(item.raw)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxTicksLimit: 10,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            border: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 10
                                },
                                callback: value => fmt(value)
                            }
                        }
                    }
                }
            });
        }

        function filterByPeriod(trend, period) {
            if (period === 'all' || !trend?.dates?.length) return trend;

            const last = new Date(trend.dates[trend.dates.length - 1]);
            const cutoffs = {
                '1m': new Date(last.getFullYear(), last.getMonth() - 1, last.getDate()),
                '3m': new Date(last.getFullYear(), last.getMonth() - 3, last.getDate()),
                '6m': new Date(last.getFullYear(), last.getMonth() - 6, last.getDate()),
                '1y': new Date(last.getFullYear() - 1, last.getMonth(), last.getDate())
            };

            const cutoff = cutoffs[period];
            const filtered = {
                labels: [],
                dates: [],
                balances: [],
                loans: []
            };

            trend.dates.forEach((date, index) => {
                if (new Date(date) >= cutoff) {
                    filtered.labels.push(trend.labels[index]);
                    filtered.dates.push(date);
                    filtered.balances.push(trend.balances[index]);
                    filtered.loans.push(trend.loans[index] ?? null);
                }
            });

            return filtered;
        }

        function setPeriod(btn, period) {
            currentPeriod = period;
            markActivePeriod(period);

            if (!fullTrend) return;

            const filtered = filterByPeriod(fullTrend, period);
            currentFilteredTrend = filtered;
            renderTrend(filtered);
        }

        function markActivePeriod(period) {
            document.querySelectorAll('.period-btn').forEach(button => {
                const buttonPeriod = button.textContent.trim().toLowerCase();
                const matches =
                    (period === 'all' && buttonPeriod === 'all') ||
                    (period === '1y' && buttonPeriod === '1y') ||
                    (period === '6m' && buttonPeriod === '6m') ||
                    (period === '3m' && buttonPeriod === '3m') ||
                    (period === '1m' && buttonPeriod === '1m');

                button.classList.toggle('active', matches);
            });
        }

        function updateTrendPeriodLabel() {
            const labels = {
                all: 'Showing selected date range',
                '1y': 'Showing last 1 year within available data',
                '6m': 'Showing last 6 months within available data',
                '3m': 'Showing last 3 months within available data',
                '1m': 'Showing last 1 month within available data'
            };

            $('trend-period-label').textContent = labels[currentPeriod] || labels.all;
        }

        function renderAccounts(accounts) {
            const tbody = $('acct-tbody');

            if (!accounts.length) {
                tbody.innerHTML =
                    '<tr><td colspan="6" style="text-align:center;color:#999;padding:18px;">No account records found.</td></tr>';
                return;
            }

            tbody.innerHTML = accounts.map(account => {
                const status = account.status || 'Unknown';
                const normalizedStatus = String(status).trim().toLowerCase().replace(/\s+/g, '-');
                const statusClass = ['active', 'open', 'closed', 'close', 'dormant', 'inactive'].includes(
                        normalizedStatus) ?
                    normalizedStatus :
                    'unknown';

                return `
            <tr data-search="${escapeHtml([
                account.account_number,
                account.description,
                account.account_class,
                account.branch_code,
                status
            ].join(' ').toLowerCase())}">
                <td>
                    <span class="badge-mono">
                        ${escapeHtml(account.account_number || '—')}
                        ${account.account_number ? `<button type="button" class="copy-acct" data-account="${escapeHtml(account.account_number)}" title="Copy account number"><i class="fa-regular fa-copy"></i></button>` : ''}
                    </span>
                </td>
                <td>${escapeHtml(account.description || '—')}</td>
                <td>${escapeHtml(account.account_class || '—')}</td>
                <td>${escapeHtml(account.branch_code || '—')}</td>
                <td>${account.open_date ? escapeHtml(fmtDate(account.open_date)) : '—'}</td>
                <td><span class="status-badge ${statusClass}">${escapeHtml(status)}</span></td>
            </tr>
        `;
            }).join('');
        }

        function filterAccounts() {
            const term = $('account-filter').value.trim().toLowerCase();

            document.querySelectorAll('#acct-tbody tr').forEach(row => {
                const haystack = row.getAttribute('data-search') || '';
                row.style.display = haystack.includes(term) ? '' : 'none';
            });
        }

        function setQuickRange(button, months) {
            document.querySelectorAll('.range-chip').forEach(chip => chip.classList.remove('active'));
            button.classList.add('active');

            const today = new Date();
            const from = new Date(today.getFullYear(), today.getMonth() - months, today.getDate());

            $('from-date').value = toDateInputValue(from);
            $('to-date').value = toDateInputValue(today);
        }

        function toDateInputValue(date) {
            const offsetDate = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
            return offsetDate.toISOString().slice(0, 10);
        }

        function resetSearch() {
            clearMessage();
            clearFieldError();

            $('cif-input').value = '';
            $('account-filter').value = '';
            $('last-refreshed').textContent = 'Not loaded yet';

            const chip12 = document.querySelector('.range-chip[data-months="12"]');
            if (chip12) setQuickRange(chip12, 12);

            fullTrend = null;
            currentFilteredTrend = null;
            currentAccounts = [];
            clearCharts();

            $('results-area').classList.remove('visible');
            $('empty-state').style.display = 'block';
        }

        function clearCharts() {
            if (trendChart) trendChart.destroy();
            if (movementChart) movementChart.destroy();

            trendChart = null;
            movementChart = null;

            $('trend-empty').classList.add('show');
            $('movement-empty').classList.add('show');
            $('trend-chart-wrap').style.display = 'none';
            $('movement-chart-wrap').style.display = 'none';

            updateTrendStats({
                balances: []
            });
        }

        function downloadTrendCsv() {
            if (!currentFilteredTrend || !currentFilteredTrend.labels?.length) {
                showMessage('There is no trend data to export yet.', 'info');
                return;
            }

            const rows = [
                ['Date', 'Label', 'Balance', 'Loan Balance']
            ];

            currentFilteredTrend.labels.forEach((label, index) => {
                rows.push([
                    currentFilteredTrend.dates[index] || '',
                    label || '',
                    currentFilteredTrend.balances[index] ?? '',
                    currentFilteredTrend.loans?.[index] ?? ''
                ]);
            });

            const csv = rows.map(row => row.map(cell => `"${String(cell).replaceAll('"', '""')}"`).join(',')).join('\n');
            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = url;
            link.download = `customer-trend-${$('cif-input').value.trim() || 'export'}.csv`;
            link.click();

            URL.revokeObjectURL(url);
        }

        function showPanelNotice(id, message) {
            const el = $(id);
            if (!el) return;

            el.textContent = message;
            el.classList.add('show');
        }

        function hidePanelNotice(id) {
            const el = $(id);
            if (!el) return;

            el.textContent = '';
            el.classList.remove('show');
        }

        function round2(value) {
            return Math.round(Number(value || 0) * 100) / 100;
        }

        document.addEventListener('DOMContentLoaded', () => {
            const today = new Date();
            const lastYear = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate());

            $('from-date').value = toDateInputValue(lastYear);
            $('to-date').value = toDateInputValue(today);

            $('cif-input').addEventListener('keydown', event => {
                if (event.key === 'Enter') searchCustomer();
            });

            $('cif-input').addEventListener('input', clearFieldError);

            $('acct-tbody').addEventListener('click', async event => {
                const button = event.target.closest('.copy-acct');
                if (!button) return;

                const account = button.getAttribute('data-account');

                try {
                    await navigator.clipboard.writeText(account);
                    button.innerHTML = '<i class="fa-solid fa-check"></i>';
                    setTimeout(() => button.innerHTML = '<i class="fa-regular fa-copy"></i>', 900);
                } catch (e) {
                    showMessage('Could not copy account number. Please copy it manually.', 'info');
                }
            });

            const urlCif = new URLSearchParams(window.location.search).get('cif');
            if (urlCif) {
                $('cif-input').value = urlCif;
                searchCustomer();
            }
        });
    </script>
@endpush






