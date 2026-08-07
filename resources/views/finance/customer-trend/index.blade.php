@extends('layouts.finance.template')

@section('title', 'Customer Financial 360')

@push('styles')
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=DM+Mono:wght@400;500;600&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --eco-blue: #0082BB;
            --eco-blue-dark: #005B82;
            --eco-blue-deep: #003F5C;
            --eco-blue-soft: #EAF6FB;

            --eco-green: #BED600;
            --eco-green-dark: #669438;

            --eco-bg: #F4F7FA;
            --eco-card: #FFFFFF;

            --eco-text: #1F2937;
            --eco-muted: #6B7280;
            --eco-border: #E2E8F0;

            --eco-success: #16A34A;
            --eco-success-soft: #DCFCE7;

            --eco-danger: #D92D20;
            --eco-danger-soft: #FEE4E2;

            --eco-warning: #B7791F;
            --eco-warning-soft: #FEF3C7;

            --shadow-soft: 0 8px 25px rgba(0, 43, 71, .06);
            --shadow-lg: 0 18px 40px rgba(0, 43, 71, .08);

            --radius-lg: 18px;
            --radius-md: 14px;
            --radius-sm: 10px;
        }

        body {
            background: var(--eco-bg);
            color: var(--eco-text);
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .c360-page {
            max-width: 1560px;
            margin: 0 auto;
            padding: 16px 20px 32px;
        }

        /* ============================================================
         * HEADER
         * ============================================================ */

        .c360-topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 14px;
        }

        .c360-kicker {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 5px 10px;
            margin-bottom: 8px;

            border-radius: 999px;

            background: var(--eco-blue-soft);
            color: var(--eco-blue-dark);

            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .c360-title {
            margin: 0;

            color: var(--eco-blue-dark);

            font-size: clamp(1.35rem, 2vw, 1.9rem);
            font-weight: 800;
            line-height: 1.15;
        }

        .c360-subtitle {
            max-width: 760px;
            margin: 6px 0 0;

            color: var(--eco-muted);

            font-size: .88rem;
            line-height: 1.55;
        }

        .refresh-info {
            min-width: 190px;
            padding-top: 6px;

            text-align: right;

            color: var(--eco-muted);
            font-size: .74rem;
        }

        .refresh-info strong {
            display: block;

            margin-top: 2px;

            color: var(--eco-blue-dark);
            font-weight: 800;
        }

        /* ============================================================
         * SEARCH
         * ============================================================ */

        .search-card {
            padding: 12px;

            background: var(--eco-card);
            border: 1px solid var(--eco-border);
            border-radius: var(--radius-lg);

            box-shadow: var(--shadow-soft);

            margin-bottom: 14px;
        }

        .search-grid {
            display: grid;

            grid-template-columns:
                minmax(240px, 1.4fr)
                minmax(150px, .6fr)
                minmax(150px, .6fr)
                auto
                auto;

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

            margin-bottom: 6px;

            color: var(--eco-blue-dark);

            font-size: .7rem;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .field-control {
            width: 100%;
            height: 42px;

            padding: 0 12px;

            border: 1px solid #CBD7E3;
            border-radius: 11px;

            background: #FFFFFF;

            color: var(--eco-text);

            font-size: .86rem;

            transition:
                border-color .18s ease,
                box-shadow .18s ease;
        }

        .field-control:focus {
            outline: none;

            border-color: var(--eco-blue);

            box-shadow: 0 0 0 4px rgba(0, 130, 187, .10);
        }

        .field-control.is-invalid {
            border-color: var(--eco-danger);

            box-shadow: 0 0 0 4px rgba(217, 45, 32, .08);
        }

        .mono-input {
            font-family: 'DM Mono', ui-monospace, monospace;
        }

        .field-error {
            display: none;

            margin-top: 5px;

            color: var(--eco-danger);

            font-size: .72rem;
            font-weight: 600;
        }

        .field-error.show {
            display: block;
        }

        .btn-c360 {
            height: 42px;

            padding: 0 17px;

            border: 0;
            border-radius: 11px;

            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;

            font-size: .82rem;
            font-weight: 800;

            white-space: nowrap;
            cursor: pointer;

            transition:
                background .15s ease,
                transform .15s ease,
                box-shadow .15s ease;
        }

        .btn-c360:active {
            transform: translateY(1px);
        }

        .btn-primary-c360 {
            background: var(--eco-blue-dark);
            color: #FFFFFF;

            box-shadow: 0 7px 16px rgba(0, 91, 130, .18);
        }

        .btn-primary-c360:hover {
            background: #004D6F;
        }

        .btn-primary-c360:disabled {
            opacity: .7;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-ghost-c360 {
            background: #F3F7FA;
            color: var(--eco-blue-dark);

            border: 1px solid var(--eco-border);
        }

        .btn-ghost-c360:hover {
            background: var(--eco-blue-soft);
        }

        .quick-range {
            display: flex;
            align-items: center;
            gap: 6px;

            flex-wrap: wrap;

            margin-top: 10px;
        }

        .quick-label {
            margin-right: 3px;

            color: var(--eco-muted);

            font-size: .72rem;
            font-weight: 700;
        }

        .range-chip {
            padding: 5px 10px;

            border: 1px solid var(--eco-border);
            border-radius: 999px;

            background: #FFFFFF;
            color: var(--eco-blue-dark);

            font-size: .7rem;
            font-weight: 800;

            cursor: pointer;
        }

        .range-chip:hover,
        .range-chip.active {
            background: var(--eco-blue-soft);
            border-color: rgba(0, 130, 187, .3);
        }

        /* ============================================================
         * MESSAGES
         * ============================================================ */

        .alert-c360 {
            display: none;

            align-items: flex-start;
            gap: 10px;

            padding: 11px 14px;
            margin-bottom: 14px;

            border: 1px solid transparent;
            border-radius: 12px;

            font-size: .82rem;
            font-weight: 600;
        }

        .alert-c360.show {
            display: flex;
        }

        .alert-c360.error {
            background: var(--eco-danger-soft);
            color: #991B1B;

            border-color: #FDA29B;
        }

        .alert-c360.info {
            background: var(--eco-blue-soft);
            color: var(--eco-blue-dark);

            border-color: rgba(0, 130, 187, .18);
        }

        /* ============================================================
         * LOADING / EMPTY
         * ============================================================ */

        .empty-state,
        .loading-state {
            padding: 40px 20px;

            background: #FFFFFF;
            border: 1px dashed #C9D6E2;
            border-radius: var(--radius-lg);

            text-align: center;

            box-shadow: var(--shadow-soft);
        }

        .loading-state {
            display: none;
            padding: 18px;
        }

        .loading-state.show {
            display: block;
        }

        .empty-icon {
            width: 58px;
            height: 58px;

            margin: 0 auto 13px;

            display: flex;
            justify-content: center;
            align-items: center;

            border-radius: 16px;

            background: var(--eco-blue-soft);
            color: var(--eco-blue-dark);

            font-size: 1.35rem;
        }

        .empty-state h3 {
            margin: 0 0 5px;

            color: var(--eco-blue-dark);

            font-size: 1rem;
            font-weight: 800;
        }

        .empty-state p {
            margin: 0;

            color: var(--eco-muted);

            font-size: .83rem;
        }

        .skeleton-grid {
            display: grid;

            grid-template-columns: repeat(5, 1fr);

            gap: 10px;
        }

        .skeleton {
            position: relative;
            overflow: hidden;

            height: 95px;

            border-radius: 14px;

            background: #EEF3F7;
        }

        .skeleton.large {
            grid-column: 1 / -1;
            height: 300px;
        }

        .skeleton::after {
            content: "";

            position: absolute;
            inset: 0;

            transform: translateX(-100%);

            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(255, 255, 255, .75),
                    transparent
                );

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

            display: inline-block;

            border: 2px solid rgba(255,255,255,.45);
            border-top-color: #FFFFFF;
            border-radius: 50%;

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

        /* ============================================================
         * CUSTOMER IDENTITY
         * ============================================================ */

        .customer-hero {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;

            padding: 16px 18px;
            margin-bottom: 12px;

            background:
                linear-gradient(
                    105deg,
                    #FFFFFF 0%,
                    #FFFFFF 60%,
                    #F1FAFD 100%
                );

            border: 1px solid var(--eco-border);
            border-radius: var(--radius-lg);

            box-shadow: var(--shadow-soft);
        }

        .customer-main {
            display: flex;
            align-items: center;
            gap: 14px;

            min-width: 0;
        }

        .customer-avatar {
            width: 58px;
            height: 58px;

            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 17px;

            background:
                linear-gradient(
                    135deg,
                    var(--eco-blue-dark),
                    var(--eco-blue)
                );

            color: #FFFFFF;

            font-size: 1.35rem;

            box-shadow: 0 10px 20px rgba(0, 91, 130, .18);
        }

        .customer-name {
            margin: 0;

            color: var(--eco-blue-deep);

            font-size: 1.08rem;
            font-weight: 850;
            line-height: 1.25;
        }

        .customer-cif {
            margin-top: 4px;

            color: var(--eco-muted);

            font-family: 'DM Mono', ui-monospace, monospace;

            font-size: .76rem;
        }

        .customer-meta {
            display: flex;
            gap: 7px;

            flex-wrap: wrap;

            margin-top: 8px;
        }

        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            padding: 4px 9px;

            border-radius: 999px;

            background: #F4F7FA;
            color: var(--eco-text);

            border: 1px solid var(--eco-border);

            font-size: .7rem;
            font-weight: 700;
        }

        .meta-badge i {
            color: var(--eco-blue);
        }

        .customer-side {
            display: flex;
            gap: 22px;

            align-items: center;
        }

        .customer-side-item {
            min-width: 90px;
        }

        .customer-side-label {
            margin-bottom: 4px;

            color: var(--eco-muted);

            font-size: .66rem;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .customer-side-value {
            color: var(--eco-blue-dark);

            font-size: .82rem;
            font-weight: 800;
        }

        /* ============================================================
         * KPI RELATIONSHIP SUMMARY
         * ============================================================ */

        .relationship-grid {
            display: grid;

            grid-template-columns: repeat(5, minmax(150px, 1fr));

            gap: 10px;

            margin-bottom: 12px;
        }

        .relationship-card {
            min-height: 112px;

            padding: 13px;

            display: flex;
            flex-direction: column;
            justify-content: space-between;

            background: #FFFFFF;

            border: 1px solid var(--eco-border);
            border-radius: var(--radius-lg);

            box-shadow: var(--shadow-soft);
        }

        .relationship-card.primary {
            background:
                linear-gradient(
                    135deg,
                    var(--eco-blue-deep),
                    var(--eco-blue)
                );

            border-color: transparent;

            color: #FFFFFF;
        }

        .relationship-card.loan {
            background:
                linear-gradient(
                    135deg,
                    #8A420E,
                    var(--eco-warning)
                );

            border-color: transparent;

            color: #FFFFFF;
        }

        .relationship-card.net-positive {
            border-color: rgba(22, 163, 74, .25);
        }

        .relationship-card.net-negative {
            border-color: rgba(217, 45, 32, .25);
        }

        .relationship-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;

            color: var(--eco-muted);

            font-size: .68rem;
            font-weight: 850;

            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .relationship-card.primary .relationship-label,
        .relationship-card.loan .relationship-label {
            color: rgba(255,255,255,.78);
        }

        .relationship-value {
            margin-top: 10px;

            color: var(--eco-blue-dark);

            font-family: 'DM Mono', ui-monospace, monospace;

            font-size: 1.18rem;
            font-weight: 800;
            line-height: 1.15;

            word-break: break-word;
        }

        .relationship-card.primary .relationship-value,
        .relationship-card.loan .relationship-value {
            color: #FFFFFF;
        }

        .relationship-value.pos {
            color: var(--eco-success);
        }

        .relationship-value.neg {
            color: var(--eco-danger);
        }

        .relationship-sub {
            margin-top: 7px;

            color: var(--eco-muted);

            font-size: .7rem;
            font-weight: 600;
        }

        .relationship-card.primary .relationship-sub,
        .relationship-card.loan .relationship-sub {
            color: rgba(255,255,255,.72);
        }

        .trend-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;

            padding: 3px 7px;

            border-radius: 999px;

            font-size: .65rem;
            font-weight: 850;
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
            background: #EDF2F7;
            color: var(--eco-muted);
        }

        /* ============================================================
         * PANELS
         * ============================================================ */

        .panel {
            background: #FFFFFF;

            border: 1px solid var(--eco-border);
            border-radius: var(--radius-lg);

            box-shadow: var(--shadow-soft);

            overflow: hidden;
        }

        .panel-header {
            min-height: 46px;

            padding: 10px 14px;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;

            border-bottom: 1px solid var(--eco-border);

            background:
                linear-gradient(
                    90deg,
                    rgba(0,91,130,.04),
                    rgba(255,255,255,0)
                );
        }

        .panel-title {
            margin: 0;

            display: inline-flex;
            align-items: center;
            gap: 8px;

            color: var(--eco-blue-dark);

            font-size: .88rem;
            font-weight: 850;
        }

        .panel-subtle {
            margin-top: 3px;

            color: var(--eco-muted);

            font-size: .7rem;
            font-weight: 600;
        }

        .panel-body {
            padding: 13px;
        }

        /* ============================================================
         * ANALYTICS
         * ============================================================ */

        .analysis-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 2.2fr)
                minmax(280px, .8fr);

            gap: 12px;

            margin-bottom: 12px;
        }

        .chart-toolbar {
            display: flex;
            align-items: center;
            gap: 6px;

            flex-wrap: wrap;
        }

        .analytics-tabs {
            display: inline-flex;

            padding: 3px;

            background: #F2F6F9;

            border: 1px solid var(--eco-border);
            border-radius: 999px;
        }

        .analytics-tab {
            padding: 5px 10px;

            border: 0;
            border-radius: 999px;

            background: transparent;
            color: var(--eco-blue-dark);

            font-size: .69rem;
            font-weight: 800;

            cursor: pointer;
        }

        .analytics-tab.active {
            background: var(--eco-blue-dark);
            color: #FFFFFF;
        }

        .mini-btn {
            height: 30px;

            padding: 0 10px;

            display: inline-flex;
            align-items: center;
            gap: 6px;

            background: #FFFFFF;
            color: var(--eco-blue-dark);

            border: 1px solid var(--eco-border);
            border-radius: 999px;

            font-size: .69rem;
            font-weight: 800;

            cursor: pointer;
        }

        .mini-btn:hover {
            background: var(--eco-blue-soft);
        }

        .chart-stats {
            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 8px;

            margin-bottom: 10px;
        }

        .chart-stat {
            padding: 8px 10px;

            border: 1px solid #EDF2F7;
            border-radius: 12px;

            background: #F8FAFC;
        }

        .chart-stat span {
            display: block;

            margin-bottom: 3px;

            color: var(--eco-muted);

            font-size: .62rem;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .chart-stat strong {
            color: var(--eco-blue-dark);

            font-family: 'DM Mono', ui-monospace, monospace;

            font-size: .78rem;
        }

        .chart-wrap {
            position: relative;

            height: 300px;
        }

        .analytics-pane {
            display: none;
        }

        .analytics-pane.active {
            display: block;
        }

        .chart-empty {
            min-height: 260px;

            display: none;
            justify-content: center;
            align-items: center;

            padding: 20px;

            text-align: center;

            color: var(--eco-muted);

            background: #FAFCFE;

            border: 1px dashed #D9E3EC;
            border-radius: 14px;

            font-size: .82rem;
        }

        .chart-empty.show {
            display: flex;
        }

        .panel-notice {
            display: none;

            margin-bottom: 10px;
            padding: 9px 11px;

            border-radius: 10px;

            background: var(--eco-warning-soft);
            color: var(--eco-warning);

            border: 1px solid #F6D68A;

            font-size: .76rem;
            font-weight: 600;
        }

        .panel-notice.show {
            display: block;
        }

        /* ============================================================
         * INSIGHTS
         * ============================================================ */

        .insight-list {
            display: grid;
            gap: 8px;
        }

        .insight-item {
            display: flex;
            gap: 10px;
            align-items: flex-start;

            padding: 10px;

            background: #F8FAFC;

            border: 1px solid #EDF2F7;
            border-radius: 12px;
        }

        .insight-icon {
            width: 30px;
            height: 30px;

            flex-shrink: 0;

            display: flex;
            justify-content: center;
            align-items: center;

            border-radius: 9px;

            background: var(--eco-blue-soft);
            color: var(--eco-blue-dark);

            font-size: .72rem;
        }

        .insight-item.success .insight-icon {
            background: var(--eco-success-soft);
            color: var(--eco-success);
        }

        .insight-item.warning .insight-icon {
            background: var(--eco-warning-soft);
            color: var(--eco-warning);
        }

        .insight-item.danger .insight-icon {
            background: var(--eco-danger-soft);
            color: var(--eco-danger);
        }

        .insight-title {
            margin-bottom: 2px;

            color: var(--eco-text);

            font-size: .76rem;
            font-weight: 800;
        }

        .insight-text {
            color: var(--eco-muted);

            font-size: .7rem;
            line-height: 1.45;
        }

        .movement-section {
            margin-top: 14px;
            padding-top: 12px;

            border-top: 1px solid var(--eco-border);
        }

        .movement-title {
            margin: 0 0 8px;

            color: var(--eco-blue-dark);

            font-size: .72rem;
            font-weight: 850;

            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .movement-row {
            display: grid;

            grid-template-columns: 72px 1fr auto;

            gap: 8px;

            align-items: center;

            padding: 8px 0;

            border-bottom: 1px solid #EEF3F7;
        }

        .movement-row:last-child {
            border-bottom: 0;
        }

        .movement-label {
            color: var(--eco-muted);

            font-size: .7rem;
            font-weight: 700;
        }

        .movement-value {
            color: var(--eco-text);

            font-family: 'DM Mono', ui-monospace, monospace;

            font-size: .73rem;
            font-weight: 700;
        }

        /* ============================================================
         * TABLES
         * ============================================================ */

        .section-stack {
            display: grid;
            gap: 12px;
        }

        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;

            margin-bottom: 10px;
        }

        .table-search {
            width: 280px;
            height: 35px;

            padding: 0 12px;

            border: 1px solid #CBD7E3;
            border-radius: 999px;

            font-size: .78rem;
            outline: none;
        }

        .table-search:focus {
            border-color: var(--eco-blue);

            box-shadow: 0 0 0 4px rgba(0,130,187,.08);
        }

        .table-wrap {
            overflow-x: auto;

            border: 1px solid var(--eco-border);
            border-radius: 13px;
        }

        .c360-table {
            width: 100%;
            min-width: 800px;

            border-collapse: collapse;

            font-size: .77rem;
        }

        .c360-table th {
            padding: 8px 10px;

            background: #F5F8FB;
            color: var(--eco-blue-dark);

            border-bottom: 1px solid var(--eco-border);

            text-align: left;

            font-size: .65rem;
            font-weight: 900;

            text-transform: uppercase;
            letter-spacing: .05em;

            white-space: nowrap;
        }

        .c360-table td {
            padding: 8px 10px;

            border-bottom: 1px solid #EEF3F7;

            color: var(--eco-text);

            vertical-align: middle;
        }

        .c360-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .c360-table tbody tr:hover td {
            background: #F9FBFD;
        }

        .account-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            padding: 4px 8px;

            border-radius: 999px;

            background: var(--eco-blue-soft);
            color: var(--eco-blue-dark);

            font-family: 'DM Mono', ui-monospace, monospace;

            font-size: .68rem;
            font-weight: 800;

            white-space: nowrap;
        }

        .copy-account {
            padding: 0;

            border: 0;

            background: transparent;
            color: var(--eco-blue);

            cursor: pointer;

            font-size: .68rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;

            padding: 4px 8px;

            border-radius: 999px;

            font-size: .66rem;
            font-weight: 850;

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
        .status-badge.inactive,
        .status-badge.loan-yes {
            background: var(--eco-warning-soft);
            color: var(--eco-warning);
        }

        .status-badge.loan-no,
        .status-badge.unknown {
            background: #EEF3F7;
            color: var(--eco-muted);
        }

        .empty-table {
            padding: 20px !important;

            text-align: center;

            color: #94A3B8 !important;
        }

        /* ============================================================
         * RESPONSIVE
         * ============================================================ */

        @media (max-width: 1250px) {
            .relationship-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .analysis-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 980px) {
            .search-grid {
                grid-template-columns: 1fr 1fr;
            }

            .search-grid .btn-c360 {
                width: 100%;
            }

            .c360-topbar,
            .customer-hero {
                flex-direction: column;
                align-items: flex-start;
            }

            .refresh-info {
                min-width: 0;
                padding-top: 0;

                text-align: left;
            }

            .customer-side {
                width: 100%;

                padding-top: 12px;

                border-top: 1px solid var(--eco-border);
            }
        }

        @media (max-width: 700px) {
            .c360-page {
                padding: 12px;
            }

            .search-grid,
            .relationship-grid,
            .chart-stats,
            .skeleton-grid {
                grid-template-columns: 1fr;
            }

            .customer-side {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .panel-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .chart-toolbar {
                justify-content: flex-start;
            }

            .table-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .table-search {
                width: 100%;
            }

            .chart-wrap {
                height: 265px;
            }
        }
    </style>
@endpush


@section('content')

    <div class="c360-page">

        {{-- ============================================================
             PAGE HEADER
        ============================================================ --}}
        <div class="c360-topbar">

            <div>
                <div class="c360-kicker">
                    <i class="fa-solid fa-user-chart"></i>
                    Customer Analytics
                </div>

                <h1 class="c360-title">
                    Customer Financial 360
                </h1>

                <p class="c360-subtitle">
                    Review a customer's relationship position, deposits, credit exposure,
                    balance behaviour, movements and linked accounts from one view.
                </p>
            </div>

            <div class="refresh-info">
                Last refreshed
                <strong id="last-refreshed">
                    Not loaded yet
                </strong>
            </div>

        </div>


        {{-- ============================================================
             SEARCH
        ============================================================ --}}
        <div class="search-card">

            <div class="search-grid">

                <div class="field-group">

                    <label class="field-label" for="cif-input">
                        <i class="fa-solid fa-fingerprint"></i>
                        CIF Number
                    </label>

                    <input
                        type="text"
                        id="cif-input"
                        class="field-control mono-input"
                        placeholder="Enter CIF number"
                        autocomplete="off">

                    <div id="cif-error" class="field-error">
                        Please enter a CIF number before searching.
                    </div>

                </div>


                <div class="field-group">

                    <label class="field-label" for="from-date">
                        <i class="fa-regular fa-calendar"></i>
                        From
                    </label>

                    <input
                        type="date"
                        id="from-date"
                        class="field-control">

                </div>


                <div class="field-group">

                    <label class="field-label" for="to-date">
                        <i class="fa-regular fa-calendar-check"></i>
                        To
                    </label>

                    <input
                        type="date"
                        id="to-date"
                        class="field-control">

                </div>


                <button
                    type="button"
                    id="search-btn"
                    class="btn-c360 btn-primary-c360"
                    onclick="searchCustomer()">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <span>
                        Search
                    </span>

                </button>


                <button
                    type="button"
                    class="btn-c360 btn-ghost-c360"
                    onclick="resetSearch()">

                    <i class="fa-solid fa-rotate-left"></i>

                    Clear

                </button>

            </div>


            <div class="quick-range">

                <span class="quick-label">
                    Quick range:
                </span>

                <button
                    type="button"
                    class="range-chip"
                    onclick="setQuickRange(this, '30d')">
                    30D
                </button>

                <button
                    type="button"
                    class="range-chip"
                    onclick="setQuickRange(this, '90d')">
                    90D
                </button>

                <button
                    type="button"
                    class="range-chip"
                    onclick="setQuickRange(this, '6m')">
                    6M
                </button>

                <button
                    type="button"
                    class="range-chip active"
                    onclick="setQuickRange(this, '1y')">
                    1Y
                </button>

                <button
                    type="button"
                    class="range-chip"
                    onclick="setQuickRange(this, 'ytd')">
                    YTD
                </button>

            </div>

        </div>


        <div
            id="message-area"
            class="alert-c360">
        </div>


        {{-- ============================================================
             LOADING
        ============================================================ --}}
        <div
            id="loading-area"
            class="loading-state">

            <div class="skeleton-grid">

                <div class="skeleton"></div>
                <div class="skeleton"></div>
                <div class="skeleton"></div>
                <div class="skeleton"></div>
                <div class="skeleton"></div>

                <div class="skeleton large"></div>

            </div>

        </div>


        {{-- ============================================================
             EMPTY STATE
        ============================================================ --}}
        <div
            id="empty-state"
            class="empty-state">

            <div class="empty-icon">
                <i class="fa-solid fa-magnifying-glass-chart"></i>
            </div>

            <h3>
                Search for a customer
            </h3>

            <p>
                Enter a CIF number and select a reporting period to load the customer's financial relationship.
            </p>

        </div>


        {{-- ============================================================
             RESULTS
        ============================================================ --}}
        <div id="results-area">


            {{-- ========================================================
                 CUSTOMER IDENTITY
            ======================================================== --}}
            <div class="customer-hero">

                <div class="customer-main">

                    <div class="customer-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>

                    <div>

                        <h2
                            class="customer-name"
                            id="customer-name">
                            —
                        </h2>

                        <div
                            class="customer-cif"
                            id="customer-cif">
                            CIF: —
                        </div>


                        <div class="customer-meta">

                            <span class="meta-badge">
                                <i class="fa-solid fa-user-tie"></i>
                                RM:
                                <span id="customer-rm">—</span>
                            </span>

                            <span class="meta-badge">
                                <i class="fa-solid fa-layer-group"></i>
                                <span id="customer-segment">—</span>
                            </span>

                            <span class="meta-badge">
                                <i class="fa-solid fa-briefcase"></i>
                                <span id="customer-business">—</span>
                            </span>

                        </div>

                    </div>

                </div>


                <div class="customer-side">

                    <div class="customer-side-item">

                        <div class="customer-side-label">
                            Linked Accounts
                        </div>

                        <div
                            id="customer-account-count"
                            class="customer-side-value">
                            —
                        </div>

                    </div>


                    <div class="customer-side-item">

                        <div class="customer-side-label">
                            Loan Customer
                        </div>

                        <div class="customer-side-value">

                            <span
                                id="customer-loan-badge"
                                class="status-badge loan-no">
                                —
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ========================================================
                 RELATIONSHIP SUMMARY
            ======================================================== --}}
            <div class="relationship-grid">

                {{-- DEPOSITS --}}
                <div class="relationship-card primary">

                    <div>

                        <div class="relationship-label">
                            <span>Total Deposits</span>
                            <i class="fa-solid fa-wallet"></i>
                        </div>

                        <div
                            id="kpi-deposits"
                            class="relationship-value">
                            —
                        </div>

                    </div>

                    <div
                        id="kpi-deposits-sub"
                        class="relationship-sub">
                        As of —
                    </div>

                </div>


                {{-- LOANS --}}
                <div class="relationship-card loan">

                    <div>

                        <div class="relationship-label">
                            <span>Loan Exposure</span>
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>

                        <div
                            id="kpi-loans"
                            class="relationship-value">
                            —
                        </div>

                    </div>

                    <div
                        id="kpi-loans-sub"
                        class="relationship-sub">
                        No loans on record
                    </div>

                </div>


                {{-- NET POSITION --}}
                <div
                    id="net-position-card"
                    class="relationship-card">

                    <div>

                        <div class="relationship-label">

                            <span>
                                Net Position
                            </span>

                            <i class="fa-solid fa-scale-balanced"></i>

                        </div>

                        <div
                            id="kpi-net"
                            class="relationship-value">
                            —
                        </div>

                    </div>

                    <div
                        id="kpi-net-sub"
                        class="relationship-sub">
                        Deposits less loan exposure
                    </div>

                </div>


                {{-- 30 DAY --}}
                <div class="relationship-card">

                    <div>

                        <div class="relationship-label">

                            <span>
                                30D Change
                            </span>

                            <span
                                id="pill-30d"
                                class="trend-pill neutral">
                                —
                            </span>

                        </div>

                        <div
                            id="kpi-30d"
                            class="relationship-value">
                            —
                        </div>

                    </div>

                    <div
                        id="kpi-30d-sub"
                        class="relationship-sub">
                        Compared with approximately 30 days ago
                    </div>

                </div>


                {{-- YTD --}}
                <div class="relationship-card">

                    <div>

                        <div class="relationship-label">

                            <span>
                                YTD Change
                            </span>

                            <span
                                id="pill-ytd"
                                class="trend-pill neutral">
                                —
                            </span>

                        </div>

                        <div
                            id="kpi-ytd"
                            class="relationship-value">
                            —
                        </div>

                    </div>

                    <div
                        id="kpi-ytd-sub"
                        class="relationship-sub">
                        From start of year
                    </div>

                </div>

            </div>


            {{-- ========================================================
                 ANALYTICS
            ======================================================== --}}
            <div class="analysis-grid">


                {{-- MAIN ANALYTICS --}}
                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <h3 class="panel-title">

                                <i class="fa-solid fa-chart-line"></i>

                                Financial Trend

                            </h3>

                            <div
                                id="trend-period-label"
                                class="panel-subtle">
                                Showing selected date range
                            </div>

                        </div>


                        <div class="chart-toolbar">

                            <div class="analytics-tabs">

                                <button
                                    type="button"
                                    class="analytics-tab active"
                                    data-pane="balance"
                                    onclick="switchAnalyticsTab(this, 'balance')">

                                    Balance

                                </button>

                                <button
                                    type="button"
                                    class="analytics-tab"
                                    data-pane="movement"
                                    onclick="switchAnalyticsTab(this, 'movement')">

                                    Movement

                                </button>

                            </div>


                            <button
                                type="button"
                                class="mini-btn"
                                onclick="downloadTrendCsv()">

                                <i class="fa-solid fa-download"></i>

                                CSV

                            </button>

                        </div>

                    </div>


                    <div class="panel-body">

                        <div
                            id="trend-notice"
                            class="panel-notice">
                        </div>


                        <div class="chart-stats">

                            <div class="chart-stat">

                                <span>
                                    Minimum
                                </span>

                                <strong id="stat-min">
                                    —
                                </strong>

                            </div>


                            <div class="chart-stat">

                                <span>
                                    Average
                                </span>

                                <strong id="stat-avg">
                                    —
                                </strong>

                            </div>


                            <div class="chart-stat">

                                <span>
                                    Maximum
                                </span>

                                <strong id="stat-max">
                                    —
                                </strong>

                            </div>


                            <div class="chart-stat">

                                <span>
                                    Data Points
                                </span>

                                <strong id="stat-points">
                                    —
                                </strong>

                            </div>

                        </div>


                        {{-- BALANCE --}}
                        <div
                            id="analytics-balance"
                            class="analytics-pane active">

                            <div
                                id="trend-empty"
                                class="chart-empty">

                                No balance trend data is available for the selected date range.

                            </div>


                            <div
                                id="trend-chart-wrap"
                                class="chart-wrap">

                                <canvas id="trend-chart"></canvas>

                            </div>

                        </div>


                        {{-- MOVEMENT --}}
                        <div
                            id="analytics-movement"
                            class="analytics-pane">

                            <div
                                id="movement-empty"
                                class="chart-empty">

                                At least two balance points are required to calculate movements.

                            </div>


                            <div
                                id="movement-chart-wrap"
                                class="chart-wrap">

                                <canvas id="movement-chart"></canvas>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- INSIGHTS --}}
                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <h3 class="panel-title">

                                <i class="fa-solid fa-lightbulb"></i>

                                Relationship Insights

                            </h3>

                            <div class="panel-subtle">
                                Derived from available customer data
                            </div>

                        </div>

                    </div>


                    <div class="panel-body">

                        <div
                            id="insight-list"
                            class="insight-list">

                            <div class="insight-item">

                                <div class="insight-icon">
                                    <i class="fa-solid fa-circle-info"></i>
                                </div>

                                <div>

                                    <div class="insight-title">
                                        Waiting for customer
                                    </div>

                                    <div class="insight-text">
                                        Customer insights will appear after a successful search.
                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="movement-section">

                            <h4 class="movement-title">
                                Movement Snapshot
                            </h4>


                            <div class="movement-row">

                                <span class="movement-label">
                                    Daily
                                </span>

                                <span
                                    id="movement-daily"
                                    class="movement-value">
                                    —
                                </span>

                                <span
                                    id="movement-daily-pill"
                                    class="trend-pill neutral">
                                    —
                                </span>

                            </div>


                            <div class="movement-row">

                                <span class="movement-label">
                                    MTD
                                </span>

                                <span
                                    id="movement-mtd"
                                    class="movement-value">
                                    —
                                </span>

                                <span
                                    id="movement-mtd-pill"
                                    class="trend-pill neutral">
                                    —
                                </span>

                            </div>


                            <div class="movement-row">

                                <span class="movement-label">
                                    YTD
                                </span>

                                <span
                                    id="movement-ytd"
                                    class="movement-value">
                                    —
                                </span>

                                <span
                                    id="movement-ytd-pill"
                                    class="trend-pill neutral">
                                    —
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ========================================================
                 ACCOUNTS
            ======================================================== --}}
            <div class="section-stack">


                {{-- LINKED ACCOUNTS --}}
                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <h3 class="panel-title">

                                <i class="fa-solid fa-building-columns"></i>

                                Deposit & Linked Accounts

                            </h3>

                            <div class="panel-subtle">
                                Accounts associated with this CIF
                            </div>

                        </div>

                        <span
                            id="account-count"
                            class="panel-subtle">
                            —
                        </span>

                    </div>


                    <div class="panel-body">

                        <div class="table-toolbar">

                            <input
                                type="text"
                                id="account-filter"
                                class="table-search"
                                placeholder="Search account, class, branch..."
                                oninput="filterAccounts()">

                            <span class="panel-subtle">
                                Use the copy icon beside an account number.
                            </span>

                        </div>


                        <div class="table-wrap">

                            <table class="c360-table">

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


                                <tbody id="account-tbody">

                                    <tr>

                                        <td
                                            colspan="6"
                                            class="empty-table">

                                            No account records loaded.

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                {{-- LOANS --}}
                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <h3 class="panel-title">

                                <i class="fa-solid fa-hand-holding-dollar"></i>

                                Credit Exposure

                            </h3>

                            <div class="panel-subtle">
                                Loan facilities associated with this customer
                            </div>

                        </div>

                        <span
                            id="loan-count"
                            class="panel-subtle">
                            —
                        </span>

                    </div>


                    <div class="panel-body">

                        <div class="table-wrap">

                            <table class="c360-table">

                                <thead>

                                    <tr>
                                        <th>Loan Account</th>
                                        <th>Product</th>
                                        <th>Status</th>
                                        <th>Outstanding LCY</th>
                                        <th>Currency</th>
                                        <th>Branch</th>
                                        <th>As At</th>
                                    </tr>

                                </thead>


                                <tbody id="loan-tbody">

                                    <tr>

                                        <td
                                            colspan="7"
                                            class="empty-table">

                                            No loan records loaded.

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection


@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>

    const BASE = '/finance/customer-trend';

    const $ = id => document.getElementById(id);


    let trendChart = null;
    let movementChart = null;

    let fullTrend = null;

    let currentProfile = null;
    let currentSummary = null;

    let currentAccounts = [];


    /* ================================================================
     * HELPERS
     * ================================================================ */

    function escapeHtml(value) {

        return String(value ?? '—')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

    }


    function fmt(value) {

        if (
            value === null ||
            value === undefined ||
            value === ''
        ) {
            return '—';
        }

        const number = Number(value);

        if (!Number.isFinite(number)) {
            return '—';
        }

        const absolute = Math.abs(number);

        const prefix =
            number < 0
                ? '-KES '
                : 'KES ';


        if (absolute >= 1e9) {
            return prefix + (absolute / 1e9).toFixed(2) + 'B';
        }

        if (absolute >= 1e6) {
            return prefix + (absolute / 1e6).toFixed(2) + 'M';
        }

        if (absolute >= 1e3) {
            return prefix + (absolute / 1e3).toFixed(2) + 'K';
        }


        return prefix + absolute.toLocaleString('en-KE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    }


    function fmtFull(value) {

        const number = Number(value);

        if (!Number.isFinite(number)) {
            return '—';
        }

        return (
            number < 0
                ? '-KES '
                : 'KES '
        ) + Math.abs(number).toLocaleString('en-KE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    }


    function fmtPercent(value) {

        const number = Number(value);

        if (!Number.isFinite(number)) {
            return '—';
        }

        const prefix =
            number > 0
                ? '+'
                : '';

        return prefix + number.toFixed(1) + '%';

    }


    function fmtDate(value) {

        if (!value) {
            return '—';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return '—';
        }

        return date.toLocaleDateString('en-GB', {
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


    function round2(value) {

        return Math.round(Number(value || 0) * 100) / 100;

    }


    function toDateInputValue(date) {

        const local = new Date(
            date.getTime() -
            date.getTimezoneOffset() * 60000
        );

        return local.toISOString().slice(0, 10);

    }


    /* ================================================================
     * LOADING / MESSAGES
     * ================================================================ */

    function setLoading(isLoading) {

        const button = $('search-btn');


        if (isLoading) {

            button.disabled = true;

            button.innerHTML =
                '<span class="spinner-mini"></span><span>Searching</span>';


            $('loading-area').classList.add('show');

            $('empty-state').style.display = 'none';

            $('results-area').classList.remove('visible');

        } else {

            button.disabled = false;

            button.innerHTML =
                '<i class="fa-solid fa-magnifying-glass"></i><span>Search</span>';


            $('loading-area').classList.remove('show');

        }

    }


    function showMessage(message, type = 'error') {

        const area = $('message-area');

        area.className =
            'alert-c360 show ' + type;


        area.innerHTML = `
            <i class="fa-solid ${
                type === 'error'
                    ? 'fa-circle-exclamation'
                    : 'fa-circle-info'
            }"></i>

            <span>
                ${escapeHtml(message)}
            </span>
        `;

    }


    function clearMessage() {

        const area = $('message-area');

        area.className = 'alert-c360';

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


    function showPanelNotice(message) {

        const element = $('trend-notice');

        element.textContent = message;

        element.classList.add('show');

    }


    function hidePanelNotice() {

        const element = $('trend-notice');

        element.textContent = '';

        element.classList.remove('show');

    }


    /* ================================================================
     * FETCH
     * ================================================================ */

    async function fetchJson(url) {

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });


        const data = await response
            .json()
            .catch(() => ({}));


        if (!response.ok) {

            const error = new Error(
                data.message ||
                `Request failed with status ${response.status}`
            );

            error.status = response.status;

            error.data = data;

            throw error;

        }


        return data;

    }


    /* ================================================================
     * MAIN SEARCH
     * ================================================================ */

    async function searchCustomer() {

        clearMessage();

        clearFieldError();


        const cif =
            $('cif-input').value.trim();

        const from =
            $('from-date').value;

        const to =
            $('to-date').value;


        if (!cif) {

            setFieldError(
                'Please enter a CIF number before searching.'
            );

            $('cif-input').focus();

            return;

        }


        if (
            from &&
            to &&
            new Date(from) > new Date(to)
        ) {

            showMessage(
                'The From date cannot be later than the To date.'
            );

            return;

        }


        setLoading(true);


        try {

            /*
             * Profile is loaded first because it determines whether
             * the CIF actually exists.
             */

            const profileResponse =
                await fetchJson(
                    `${BASE}/profile?cif=${encodeURIComponent(cif)}`
                );


            currentProfile =
                profileResponse.profile ||
                profileResponse;


            renderProfile(currentProfile);


            const query = new URLSearchParams({
                cif,
                from,
                to
            });


            const [
                summaryResult,
                trendResult
            ] = await Promise.allSettled([

                fetchJson(
                    `${BASE}/summary?cif=${encodeURIComponent(cif)}`
                ),

                fetchJson(
                    `${BASE}/trend?${query.toString()}`
                )

            ]);


            /* SUMMARY */

            if (
                summaryResult.status === 'fulfilled'
            ) {

                currentSummary =
                    summaryResult.value.summary ||
                    summaryResult.value;


                renderSummary(currentSummary);

            } else {

                currentSummary = null;

                renderSummaryUnavailable();

            }


            /* TREND */

            if (
                trendResult.status === 'fulfilled'
            ) {

                fullTrend = normalizeTrend(
                    trendResult.value.trend ||
                    trendResult.value
                );


                renderTrend(fullTrend);

                hidePanelNotice();

            } else {

                fullTrend = null;

                clearCharts();

                showPanelNotice(
                    'Customer profile loaded, but balance trend data could not be loaded.'
                );

            }


            /*
             * These metrics require profile + summary + trend.
             */

            renderRelationshipMetrics();

            renderInsights();


            $('last-refreshed').textContent =
                nowLabel();


            $('results-area')
                .classList
                .add('visible');


            $('empty-state').style.display =
                'none';


        } catch (error) {

            const notFound =
                error.status === 404;


            showMessage(

                notFound

                    ? (
                        error.data?.message ||
                        'CIF not found. Please confirm the number and try again.'
                    )

                    : 'We could not load the customer financial information. Please try again.'

            );


            $('empty-state').style.display =
                'block';


            $('results-area')
                .classList
                .remove('visible');

        } finally {

            setLoading(false);

        }

    }


    /* ================================================================
     * CUSTOMER PROFILE
     * ================================================================ */

    function renderProfile(profile) {

        const accounts =
            Array.isArray(profile.accounts)
                ? profile.accounts
                : [];


        const loans =
            Array.isArray(profile.loans)
                ? profile.loans
                : [];


        currentAccounts = accounts;


        $('customer-name').textContent =
            profile.customer_name ||
            profile.name ||
            profile.cif ||
            'Unknown Customer';


        $('customer-cif').textContent =
            'CIF: ' + (profile.cif || '—');


        $('customer-rm').textContent =
            profile.rm_code ||
            profile.relationship_manager ||
            'N/A';


        $('customer-segment').textContent =
            profile.segment ||
            'Unknown Segment';


        $('customer-business').textContent =
            profile.business ||
            profile.code_desc ||
            'Unknown Business';


        $('customer-account-count').textContent =
            accounts.length.toLocaleString();


        $('account-count').textContent =
            accounts.length +
            ' account' +
            (
                accounts.length === 1
                    ? ''
                    : 's'
            );


        renderLoanBadge(
            !!profile.has_loan
        );


        renderAccounts(accounts);

        renderLoans(loans);


        $('kpi-loans').textContent =
            fmt(profile.loan_balance || 0);


        $('kpi-loans-sub').textContent =
            profile.loan_as_of_date

                ? 'As of ' +
                    fmtDate(profile.loan_as_of_date)

                : 'No loans on record';

    }


    function renderLoanBadge(hasLoan) {

        const badge =
            $('customer-loan-badge');


        badge.className =
            'status-badge ' +
            (
                hasLoan
                    ? 'loan-yes'
                    : 'loan-no'
            );


        badge.textContent =
            hasLoan
                ? 'Yes'
                : 'No';

    }


    /* ================================================================
     * SUMMARY
     * ================================================================ */

    function renderSummary(summary) {

        $('kpi-deposits').textContent =
            fmt(summary.current_balance);


        $('kpi-deposits-sub').textContent =
            summary.as_of_date

                ? 'As of ' +
                    fmtDate(summary.as_of_date)

                : 'As of —';


        renderMovementSnapshot(
            'daily',
            summary.daily_movement
        );


        renderMovementSnapshot(
            'mtd',
            summary.mtd_movement
        );


        renderMovementSnapshot(
            'ytd',
            summary.ytd_movement
        );


        renderMainMovementKpi(
            'ytd',
            summary.ytd_movement
        );

    }


    function renderSummaryUnavailable() {

        $('kpi-deposits').textContent =
            '—';


        $('kpi-deposits-sub').textContent =
            'Summary unavailable';


        [
            'daily',
            'mtd',
            'ytd'
        ].forEach(key => {

            $(`movement-${key}`).textContent =
                '—';


            updateTrendPill(
                `movement-${key}-pill`,
                null
            );

        });


        $('kpi-ytd').textContent =
            '—';


        updateTrendPill(
            'pill-ytd',
            null
        );

    }


    function renderMovementSnapshot(
        key,
        value
    ) {

        const number =
            Number(value || 0);


        $(`movement-${key}`).textContent =
            fmt(number);


        updateTrendPill(
            `movement-${key}-pill`,
            number
        );

    }


    function renderMainMovementKpi(
        key,
        value
    ) {

        const number =
            Number(value || 0);


        const element =
            $(`kpi-${key}`);


        element.textContent =
            fmt(number);


        element.className =
            'relationship-value ' +
            (
                number > 0
                    ? 'pos'
                    : number < 0
                        ? 'neg'
                        : ''
            );


        updateTrendPill(
            `pill-${key}`,
            number
        );

    }


    /* ================================================================
     * RELATIONSHIP METRICS
     * ================================================================ */

    function renderRelationshipMetrics() {

        const deposits =
            Number(
                currentSummary?.current_balance || 0
            );


        const loans =
            Number(
                currentProfile?.loan_balance || 0
            );


        const net =
            round2(
                deposits - loans
            );


        const netElement =
            $('kpi-net');


        const netCard =
            $('net-position-card');


        netElement.textContent =
            fmt(net);


        netElement.className =
            'relationship-value ' +
            (
                net > 0
                    ? 'pos'
                    : net < 0
                        ? 'neg'
                        : ''
            );


        netCard.classList.remove(
            'net-positive',
            'net-negative'
        );


        if (net > 0) {

            netCard.classList.add(
                'net-positive'
            );

            $('kpi-net-sub').textContent =
                'Customer is net funded';

        } else if (net < 0) {

            netCard.classList.add(
                'net-negative'
            );

            $('kpi-net-sub').textContent =
                'Loan exposure exceeds deposits';

        } else {

            $('kpi-net-sub').textContent =
                'Deposits less loan exposure';

        }


        const movement30 =
            calculatePeriodMovement(
                fullTrend,
                30
            );


        render30DayMetric(
            movement30
        );

    }


    function calculatePeriodMovement(
        trend,
        days
    ) {

        if (
            !trend ||
            !Array.isArray(trend.dates) ||
            !Array.isArray(trend.balances)
        ) {
            return null;
        }


        const valid = [];


        trend.dates.forEach(
            (date, index) => {

                const balance =
                    Number(
                        trend.balances[index]
                    );


                if (
                    date &&
                    Number.isFinite(balance)
                ) {

                    valid.push({
                        date: new Date(date),
                        balance
                    });

                }

            }
        );


        if (valid.length < 2) {
            return null;
        }


        valid.sort(
            (a, b) =>
                a.date - b.date
        );


        const current =
            valid[valid.length - 1];


        const target =
            new Date(
                current.date
            );


        target.setDate(
            target.getDate() - days
        );


        /*
         * Find the closest point on or before target date.
         */

        let previous =
            valid[0];


        for (
            const point of valid
        ) {

            if (
                point.date <= target
            ) {
                previous = point;
            } else {
                break;
            }

        }


        if (
            previous === current
        ) {
            return null;
        }


        const amount =
            round2(
                current.balance -
                previous.balance
            );


        const percent =
            previous.balance !== 0

                ? (
                    amount /
                    Math.abs(previous.balance)
                ) * 100

                : null;


        return {
            amount,
            percent,
            fromDate: previous.date,
            toDate: current.date
        };

    }


    function render30DayMetric(result) {

        const element =
            $('kpi-30d');


        if (!result) {

            element.textContent =
                '—';


            element.className =
                'relationship-value';


            $('kpi-30d-sub').textContent =
                'Insufficient historical data';


            updateTrendPill(
                'pill-30d',
                null
            );


            return;

        }


        element.textContent =
            fmt(result.amount);


        element.className =
            'relationship-value ' +
            (
                result.amount > 0
                    ? 'pos'
                    : result.amount < 0
                        ? 'neg'
                        : ''
            );


        $('kpi-30d-sub').textContent =
            result.percent !== null

                ? `${fmtPercent(result.percent)} since ${fmtDate(result.fromDate)}`

                : `From ${fmtDate(result.fromDate)}`;


        updateTrendPill(
            'pill-30d',
            result.amount
        );

    }


    function updateTrendPill(
        id,
        value
    ) {

        const pill =
            $(id);


        if (
            value === null ||
            value === undefined ||
            !Number.isFinite(
                Number(value)
            )
        ) {

            pill.className =
                'trend-pill neutral';


            pill.textContent =
                '—';


            return;

        }


        const number =
            Number(value);


        if (number > 0) {

            pill.className =
                'trend-pill pos';


            pill.innerHTML =
                '<i class="fa-solid fa-arrow-up"></i> Up';

        } else if (number < 0) {

            pill.className =
                'trend-pill neg';


            pill.innerHTML =
                '<i class="fa-solid fa-arrow-down"></i> Down';

        } else {

            pill.className =
                'trend-pill neutral';


            pill.textContent =
                'Flat';

        }

    }


    /* ================================================================
     * TREND
     * ================================================================ */

    function normalizeTrend(trend) {

        return {

            labels:
                Array.isArray(trend.labels)
                    ? trend.labels
                    : [],


            dates:
                Array.isArray(trend.dates)
                    ? trend.dates
                    : [],


            balances:
                Array.isArray(trend.balances)

                    ? trend.balances.map(
                        value =>
                            value === null ||
                            value === undefined

                                ? null
                                : Number(value)
                    )

                    : [],


            loans:
                Array.isArray(trend.loans)

                    ? trend.loans.map(
                        value =>
                            value === null ||
                            value === undefined

                                ? null
                                : Number(value)
                    )

                    : []

        };

    }


    function extractBalanceOnly(trend) {

        const labels = [];
        const dates = [];
        const balances = [];


        trend.dates.forEach(
            (date, index) => {

                const balance =
                    trend.balances[index];


                if (
                    balance !== null &&
                    balance !== undefined &&
                    Number.isFinite(
                        Number(balance)
                    )
                ) {

                    labels.push(
                        trend.labels[index]
                    );


                    dates.push(
                        date
                    );


                    balances.push(
                        Number(balance)
                    );

                }

            }
        );


        return {
            labels,
            dates,
            balances
        };

    }


    function renderTrend(trend) {

        const safeTrend =
            normalizeTrend(trend);


        const balanceOnly =
            extractBalanceOnly(safeTrend);


        updateTrendStats(
            balanceOnly
        );


        renderTrendChart(
            safeTrend
        );


        renderMovementChart(
            balanceOnly
        );


        updateTrendPeriodLabel(
            balanceOnly
        );

    }


    function updateTrendStats(trend) {

        const balances =
            trend.balances.filter(
                value =>
                    Number.isFinite(
                        Number(value)
                    )
            );


        $('stat-points').textContent =
            balances.length.toLocaleString();


        if (!balances.length) {

            $('stat-min').textContent =
                '—';

            $('stat-avg').textContent =
                '—';

            $('stat-max').textContent =
                '—';

            return;

        }


        const min =
            Math.min(...balances);


        const max =
            Math.max(...balances);


        const avg =
            balances.reduce(
                (total, value) =>
                    total + value,
                0
            ) / balances.length;


        $('stat-min').textContent =
            fmt(min);


        $('stat-avg').textContent =
            fmt(avg);


        $('stat-max').textContent =
            fmt(max);

    }


    function updateTrendPeriodLabel(trend) {

        if (
            !trend?.dates?.length
        ) {

            $('trend-period-label').textContent =
                'No balance period available';

            return;

        }


        const first =
            trend.dates[0];


        const last =
            trend.dates[
                trend.dates.length - 1
            ];


        $('trend-period-label').textContent =
            `${fmtDate(first)} — ${fmtDate(last)}`;

    }


    /* ================================================================
     * BALANCE CHART
     * ================================================================ */

    function renderTrendChart(trend) {

        const hasBalanceData =
            trend.balances.some(
                value =>
                    value !== null &&
                    Number.isFinite(
                        Number(value)
                    )
            );


        const hasLoanData =
            trend.loans.some(
                value =>
                    value !== null &&
                    value !== undefined &&
                    Number.isFinite(
                        Number(value)
                    )
            );


        const hasData =
            trend.labels.length &&
            (
                hasBalanceData ||
                hasLoanData
            );


        $('trend-empty')
            .classList
            .toggle(
                'show',
                !hasData
            );


        $('trend-chart-wrap')
            .style
            .display =
                hasData
                    ? 'block'
                    : 'none';


        if (trendChart) {

            trendChart.destroy();

            trendChart = null;

        }


        if (!hasData) {
            return;
        }


        const datasets = [];


        if (hasBalanceData) {

            datasets.push({

                label: 'Deposit Balance',

                data: trend.balances,

                borderColor: '#005B82',

                backgroundColor:
                    'rgba(0,130,187,.08)',

                borderWidth: 2.4,

                pointRadius:
                    trend.labels.length <= 45
                        ? 2.5
                        : 0,

                pointHoverRadius: 5,

                fill: true,

                tension: .3,

                spanGaps: true,

                yAxisID: 'y'

            });

        }


        if (hasLoanData) {

            datasets.push({

                label: 'Loan Exposure',

                data: trend.loans,

                borderColor: '#B7791F',

                backgroundColor:
                    'rgba(183,121,31,.06)',

                borderWidth: 2.2,

                borderDash: [5, 3],

                pointRadius:
                    trend.labels.length <= 45
                        ? 2.5
                        : 0,

                pointHoverRadius: 5,

                fill: false,

                tension: .3,

                spanGaps: true,

                yAxisID: 'y1'

            });

        }


        trendChart =
            new Chart(
                $('trend-chart').getContext('2d'),
                {

                    type: 'line',

                    data: {

                        labels:
                            trend.labels,

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
                                display:
                                    datasets.length > 1,

                                position: 'top',

                                align: 'end',

                                labels: {
                                    boxWidth: 10,
                                    font: {
                                        size: 10
                                    }
                                }
                            },

                            tooltip: {

                                filter:
                                    item =>
                                        item.raw !== null &&
                                        item.raw !== undefined,

                                callbacks: {

                                    label:
                                        item =>
                                            `${item.dataset.label}: ${fmtFull(item.raw)}`

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
                                        size: 9
                                    }
                                }

                            },

                            y: {

                                border: {
                                    display: false
                                },

                                ticks: {

                                    font: {
                                        size: 9
                                    },

                                    callback:
                                        value =>
                                            fmt(value)

                                }

                            },

                            y1: {

                                display:
                                    hasLoanData,

                                position:
                                    'right',

                                border: {
                                    display: false
                                },

                                grid: {
                                    drawOnChartArea: false
                                },

                                ticks: {

                                    font: {
                                        size: 9
                                    },

                                    callback:
                                        value =>
                                            fmt(value)

                                }

                            }

                        }

                    }

                }
            );

    }


    /* ================================================================
     * MOVEMENT CHART
     * ================================================================ */

    function renderMovementChart(trend) {

        const balances =
            trend.balances || [];


        const labels =
            trend.labels || [];


        const hasData =
            balances.length > 1;


        $('movement-empty')
            .classList
            .toggle(
                'show',
                !hasData
            );


        $('movement-chart-wrap')
            .style
            .display =
                hasData
                    ? 'block'
                    : 'none';


        if (movementChart) {

            movementChart.destroy();

            movementChart = null;

        }


        if (!hasData) {
            return;
        }


        const movementValues =
            balances
                .slice(1)
                .map(
                    (value, index) =>
                        round2(
                            value -
                            balances[index]
                        )
                );


        const chartLabels =
            labels.slice(1);


        const colors =
            movementValues.map(
                value =>
                    value >= 0
                        ? '#16A34A'
                        : '#D92D20'
            );


        movementChart =
            new Chart(
                $('movement-chart').getContext('2d'),
                {

                    type: 'bar',

                    data: {

                        labels:
                            chartLabels,

                        datasets: [{

                            label:
                                'Daily Movement',

                            data:
                                movementValues,

                            backgroundColor:
                                colors,

                            borderRadius:
                                5,

                            barPercentage:
                                .78,

                            categoryPercentage:
                                .76

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

                                    label:
                                        item =>
                                            'Movement: ' +
                                            fmtFull(item.raw)

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
                                        size: 9
                                    }
                                }

                            },

                            y: {

                                border: {
                                    display: false
                                },

                                ticks: {

                                    font: {
                                        size: 9
                                    },

                                    callback:
                                        value =>
                                            fmt(value)

                                }

                            }

                        }

                    }

                }
            );

    }


    /* ================================================================
     * ANALYTICS TABS
     * ================================================================ */

    function switchAnalyticsTab(
        button,
        pane
    ) {

        document
            .querySelectorAll(
                '.analytics-tab'
            )
            .forEach(
                tab =>
                    tab.classList.remove(
                        'active'
                    )
            );


        button.classList.add(
            'active'
        );


        document
            .querySelectorAll(
                '.analytics-pane'
            )
            .forEach(
                element =>
                    element.classList.remove(
                        'active'
                    )
            );


        $(`analytics-${pane}`)
            .classList
            .add('active');

    }


    /* ================================================================
     * CUSTOMER INSIGHTS
     * ================================================================ */

    function renderInsights() {

        const container =
            $('insight-list');


        const insights = [];


        const deposits =
            Number(
                currentSummary?.current_balance || 0
            );


        const loans =
            Number(
                currentProfile?.loan_balance || 0
            );


        const ytd =
            Number(
                currentSummary?.ytd_movement || 0
            );


        const movement30 =
            calculatePeriodMovement(
                fullTrend,
                30
            );


        /*
         * NET RELATIONSHIP
         */

        if (
            loans > 0 &&
            loans > deposits
        ) {

            insights.push({

                type: 'warning',

                icon:
                    'fa-scale-unbalanced',

                title:
                    'Credit exposure exceeds deposits',

                text:
                    `Loan exposure is ${fmt(loans)} compared with deposits of ${fmt(deposits)}.`

            });

        } else if (
            deposits > 0 &&
            deposits > loans
        ) {

            insights.push({

                type: 'success',

                icon:
                    'fa-circle-check',

                title:
                    'Customer is net funded',

                text:
                    `Deposits exceed current loan exposure by ${fmt(deposits - loans)}.`

            });

        }


        /*
         * YTD DIRECTION
         */

        if (ytd < 0) {

            insights.push({

                type: 'danger',

                icon:
                    'fa-arrow-trend-down',

                title:
                    'Balance has declined YTD',

                text:
                    `Year-to-date movement is ${fmt(ytd)}.`

            });

        } else if (ytd > 0) {

            insights.push({

                type: 'success',

                icon:
                    'fa-arrow-trend-up',

                title:
                    'Positive YTD balance movement',

                text:
                    `Year-to-date balance movement is ${fmt(ytd)}.`

            });

        }


        /*
         * RECENT DIRECTION
         */

        if (
            movement30 &&
            movement30.amount < 0
        ) {

            insights.push({

                type: 'warning',

                icon:
                    'fa-calendar-minus',

                title:
                    'Recent balance contraction',

                text:
                    `Balance reduced by ${fmt(Math.abs(movement30.amount))} over the recent 30-day comparison period.`

            });

        } else if (
            movement30 &&
            movement30.amount > 0
        ) {

            insights.push({

                type: 'success',

                icon:
                    'fa-calendar-plus',

                title:
                    'Recent balance growth',

                text:
                    `Balance increased by ${fmt(movement30.amount)} over the recent 30-day comparison period.`

            });

        }


        /*
         * NO LOANS
         */

        if (
            !currentProfile?.has_loan
        ) {

            insights.push({

                type: '',

                icon:
                    'fa-hand-holding-dollar',

                title:
                    'No active loan relationship identified',

                text:
                    'No loan exposure was returned for this customer profile.'

            });

        }


        /*
         * FALLBACK
         */

        if (!insights.length) {

            insights.push({

                type: '',

                icon:
                    'fa-circle-info',

                title:
                    'Limited relationship signals',

                text:
                    'The available data does not currently indicate a significant relationship movement.'

            });

        }


        container.innerHTML =
            insights
                .slice(0, 3)
                .map(
                    insight => `

                        <div class="insight-item ${escapeHtml(insight.type)}">

                            <div class="insight-icon">

                                <i class="fa-solid ${escapeHtml(insight.icon)}"></i>

                            </div>

                            <div>

                                <div class="insight-title">
                                    ${escapeHtml(insight.title)}
                                </div>

                                <div class="insight-text">
                                    ${escapeHtml(insight.text)}
                                </div>

                            </div>

                        </div>

                    `
                )
                .join('');

    }


    /* ================================================================
     * ACCOUNTS
     * ================================================================ */

    function renderAccounts(accounts) {

        const tbody =
            $('account-tbody');


        if (!accounts.length) {

            tbody.innerHTML = `

                <tr>

                    <td
                        colspan="6"
                        class="empty-table">

                        No linked account records found.

                    </td>

                </tr>

            `;

            return;

        }


        tbody.innerHTML =
            accounts
                .map(account => {

                    const status =
                        account.status ||
                        'Unknown';


                    const normalizedStatus =
                        String(status)
                            .trim()
                            .toLowerCase()
                            .replace(/\s+/g, '-');


                    const allowed = [
                        'active',
                        'open',
                        'closed',
                        'close',
                        'dormant',
                        'inactive'
                    ];


                    const statusClass =
                        allowed.includes(
                            normalizedStatus
                        )

                            ? normalizedStatus

                            : 'unknown';


                    const searchValue = [

                        account.account_number,
                        account.description,
                        account.account_class,
                        account.branch_code,
                        status

                    ]
                        .join(' ')
                        .toLowerCase();


                    return `

                        <tr
                            data-search="${escapeHtml(searchValue)}">

                            <td>

                                <span class="account-badge">

                                    ${escapeHtml(
                                        account.account_number ||
                                        '—'
                                    )}

                                    ${
                                        account.account_number

                                            ? `

                                                <button
                                                    type="button"
                                                    class="copy-account"
                                                    data-account="${escapeHtml(account.account_number)}"
                                                    title="Copy account number">

                                                    <i class="fa-regular fa-copy"></i>

                                                </button>

                                            `

                                            : ''
                                    }

                                </span>

                            </td>


                            <td>
                                ${escapeHtml(account.description || '—')}
                            </td>


                            <td>
                                ${escapeHtml(account.account_class || '—')}
                            </td>


                            <td>
                                ${escapeHtml(account.branch_code || '—')}
                            </td>


                            <td>

                                ${
                                    account.open_date

                                        ? escapeHtml(
                                            fmtDate(
                                                account.open_date
                                            )
                                        )

                                        : '—'
                                }

                            </td>


                            <td>

                                <span class="status-badge ${statusClass}">

                                    ${escapeHtml(status)}

                                </span>

                            </td>

                        </tr>

                    `;

                })
                .join('');

    }


    function filterAccounts() {

        const term =
            $('account-filter')
                .value
                .trim()
                .toLowerCase();


        document
            .querySelectorAll(
                '#account-tbody tr'
            )
            .forEach(row => {

                const search =
                    row.getAttribute(
                        'data-search'
                    ) || '';


                row.style.display =
                    search.includes(term)
                        ? ''
                        : 'none';

            });

    }


    /* ================================================================
     * LOANS
     * ================================================================ */

    function renderLoans(loans) {

        const tbody =
            $('loan-tbody');


        $('loan-count').textContent =
            loans.length +
            ' loan' +
            (
                loans.length === 1
                    ? ''
                    : 's'
            );


        if (!loans.length) {

            tbody.innerHTML = `

                <tr>

                    <td
                        colspan="7"
                        class="empty-table">

                        No loan records found for this customer.

                    </td>

                </tr>

            `;

            return;

        }


        tbody.innerHTML =
            loans
                .map(loan => `

                    <tr>

                        <td>

                            <span class="account-badge">

                                ${escapeHtml(
                                    loan.account ||
                                    '—'
                                )}

                            </span>

                        </td>


                        <td>
                            ${escapeHtml(
                                loan.product_code ||
                                '—'
                            )}
                        </td>


                        <td>

                            <span class="status-badge unknown">

                                ${escapeHtml(
                                    loan.status_bucket ||
                                    loan.loan_status ||
                                    '—'
                                )}

                            </span>

                        </td>


                        <td>
                            ${fmt(
                                loan.outstanding_lcy
                            )}
                        </td>


                        <td>
                            ${escapeHtml(
                                loan.currency ||
                                '—'
                            )}
                        </td>


                        <td>
                            ${escapeHtml(
                                loan.branch ||
                                '—'
                            )}
                        </td>


                        <td>

                            ${
                                loan.as_at_date

                                    ? escapeHtml(
                                        fmtDate(
                                            loan.as_at_date
                                        )
                                    )

                                    : '—'
                            }

                        </td>

                    </tr>

                `)
                .join('');

    }


    /* ================================================================
     * QUICK DATE RANGES
     * ================================================================ */

    function setQuickRange(
        button,
        range
    ) {

        document
            .querySelectorAll(
                '.range-chip'
            )
            .forEach(
                chip =>
                    chip.classList.remove(
                        'active'
                    )
            );


        button.classList.add(
            'active'
        );


        const today =
            new Date();


        let from =
            new Date(today);


        switch (range) {

            case '30d':

                from.setDate(
                    today.getDate() - 30
                );

                break;


            case '90d':

                from.setDate(
                    today.getDate() - 90
                );

                break;


            case '6m':

                from =
                    new Date(
                        today.getFullYear(),
                        today.getMonth() - 6,
                        today.getDate()
                    );

                break;


            case 'ytd':

                from =
                    new Date(
                        today.getFullYear(),
                        0,
                        1
                    );

                break;


            case '1y':

            default:

                from =
                    new Date(
                        today.getFullYear() - 1,
                        today.getMonth(),
                        today.getDate()
                    );

                break;

        }


        $('from-date').value =
            toDateInputValue(from);


        $('to-date').value =
            toDateInputValue(today);

    }


    /* ================================================================
     * RESET
     * ================================================================ */

    function resetSearch() {

        clearMessage();

        clearFieldError();


        $('cif-input').value = '';

        $('account-filter').value = '';

        $('last-refreshed').textContent =
            'Not loaded yet';


        currentProfile = null;

        currentSummary = null;

        currentAccounts = [];

        fullTrend = null;


        clearCharts();


        const defaultChip =
            document.querySelector(
                '.range-chip.active'
            );


        document
            .querySelectorAll(
                '.range-chip'
            )
            .forEach(
                chip =>
                    chip.classList.remove(
                        'active'
                    )
            );


        const oneYearChip =
            Array.from(
                document.querySelectorAll(
                    '.range-chip'
                )
            )
            .find(
                chip =>
                    chip.textContent.trim() === '1Y'
            );


        if (oneYearChip) {

            setQuickRange(
                oneYearChip,
                '1y'
            );

        }


        $('results-area')
            .classList
            .remove('visible');


        $('empty-state').style.display =
            'block';

    }


    function clearCharts() {

        if (trendChart) {

            trendChart.destroy();

            trendChart = null;

        }


        if (movementChart) {

            movementChart.destroy();

            movementChart = null;

        }


        $('trend-empty')
            .classList
            .add('show');


        $('movement-empty')
            .classList
            .add('show');


        $('trend-chart-wrap')
            .style
            .display =
                'none';


        $('movement-chart-wrap')
            .style
            .display =
                'none';


        $('stat-min').textContent =
            '—';


        $('stat-avg').textContent =
            '—';


        $('stat-max').textContent =
            '—';


        $('stat-points').textContent =
            '—';

    }


    /* ================================================================
     * CSV EXPORT
     * ================================================================ */

    function downloadTrendCsv() {

        if (
            !fullTrend ||
            !fullTrend.labels?.length
        ) {

            showMessage(
                'There is no trend data to export yet.',
                'info'
            );

            return;

        }


        const rows = [[
            'Date',
            'Label',
            'Deposit Balance',
            'Loan Exposure'
        ]];


        fullTrend.labels.forEach(
            (label, index) => {

                rows.push([

                    fullTrend.dates[index] || '',

                    label || '',

                    fullTrend.balances[index] ?? '',

                    fullTrend.loans[index] ?? ''

                ]);

            }
        );


        const csv =
            rows
                .map(
                    row =>
                        row
                            .map(
                                cell =>
                                    `"${String(cell).replaceAll('"', '""')}"`
                            )
                            .join(',')
                )
                .join('\n');


        const blob =
            new Blob(
                [csv],
                {
                    type:
                        'text/csv;charset=utf-8;'
                }
            );


        const url =
            URL.createObjectURL(blob);


        const link =
            document.createElement('a');


        link.href =
            url;


        link.download =
            `customer-financial-360-${$('cif-input').value.trim() || 'export'}.csv`;


        document.body.appendChild(link);

        link.click();

        link.remove();


        URL.revokeObjectURL(url);

    }


    /* ================================================================
     * INITIALIZATION
     * ================================================================ */

    document.addEventListener(
        'DOMContentLoaded',
        () => {

            /*
             * Default date range = 1 year.
             */

            const today =
                new Date();


            const lastYear =
                new Date(
                    today.getFullYear() - 1,
                    today.getMonth(),
                    today.getDate()
                );


            $('from-date').value =
                toDateInputValue(
                    lastYear
                );


            $('to-date').value =
                toDateInputValue(
                    today
                );


            /*
             * Search on Enter.
             */

            $('cif-input')
                .addEventListener(
                    'keydown',
                    event => {

                        if (
                            event.key === 'Enter'
                        ) {

                            searchCustomer();

                        }

                    }
                );


            $('cif-input')
                .addEventListener(
                    'input',
                    clearFieldError
                );


            /*
             * Account number copy.
             */

            $('account-tbody')
                .addEventListener(
                    'click',
                    async event => {

                        const button =
                            event.target.closest(
                                '.copy-account'
                            );


                        if (!button) {
                            return;
                        }


                        const account =
                            button.getAttribute(
                                'data-account'
                            );


                        try {

                            await navigator
                                .clipboard
                                .writeText(account);


                            button.innerHTML =
                                '<i class="fa-solid fa-check"></i>';


                            setTimeout(
                                () => {

                                    button.innerHTML =
                                        '<i class="fa-regular fa-copy"></i>';

                                },
                                900
                            );

                        } catch (error) {

                            showMessage(
                                'Could not copy the account number. Please copy it manually.',
                                'info'
                            );

                        }

                    }
                );


            /*
             * Automatically load CIF when provided in query string.
             */

            const urlCif =
                new URLSearchParams(
                    window.location.search
                )
                .get('cif');


            if (urlCif) {

                $('cif-input').value =
                    urlCif;


                searchCustomer();

            }

        }
    );

</script>

@endpush