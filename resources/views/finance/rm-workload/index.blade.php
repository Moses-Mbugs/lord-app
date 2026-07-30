@extends('layouts.finance.template')

@section('title', 'RM Workload')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --eco-blue: #0082BB;
            --eco-blue-dark: #005B82;
            --eco-blue-deep: #073B5A;
            --eco-green: #BED600;
            --eco-green-dark: #669438;
            --eco-yellow: #F5B700;

            --wl-bg: #F3F7FA;
            --wl-surface: #FFFFFF;
            --wl-surface-soft: #F8FBFD;
            --wl-text: #172635;
            --wl-muted: #6B7C8F;
            --wl-faint: #94A3B8;

            --wl-border: rgba(7, 59, 90, 0.10);
            --wl-border-strong: rgba(7, 59, 90, 0.16);

            --success: #15803D;
            --success-soft: rgba(21, 128, 61, 0.10);

            --warning: #B7791F;
            --warning-soft: rgba(183, 121, 31, 0.12);

            --danger: #DC2626;
            --danger-soft: rgba(220, 38, 38, 0.10);

            --info-soft: rgba(0, 130, 187, 0.10);

            --shadow-sm: 0 8px 22px rgba(7, 59, 90, 0.06);
            --shadow-md: 0 16px 42px rgba(7, 59, 90, 0.10);
            --shadow-lg: 0 28px 70px rgba(7, 59, 90, 0.20);

            --radius-xl: 28px;
            --radius-lg: 22px;
            --radius-md: 16px;
            --radius-sm: 11px;
        }

        body {
            background:
                radial-gradient(circle at top left, rgba(0, 130, 187, 0.08), transparent 32rem),
                radial-gradient(circle at top right, rgba(190, 214, 0, 0.11), transparent 28rem),
                var(--wl-bg);
            color: var(--wl-text);
        }

        .wl-page,
        .wl-page * {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        /* Guard against the blanket font-family rule above stripping the
           Font Awesome icon font (same CSS specificity, later in source
           order would otherwise win and render every icon blank). */
        .wl-page i[class*="fa-"] {
            font-family: "Font Awesome 6 Free";
        }

        .wl-page i.fa-brands {
            font-family: "Font Awesome 6 Brands";
        }

        .wl-page {
            padding: 18px 22px 44px;
            position: relative;
        }

        .wl-shell {
            max-width: 100%;
        }

        /* =========================================================
                   GLOBAL LOADER
                   Fixed: self-contained variables, stronger overlay,
                   accessible state support and smoother spinner.
                ========================================================= */
        .page-loader {
            display: none;
            position: fixed;
            inset: 0;
            top: 0; right: 0; bottom: 0; left: 0;
            z-index: 9999;
            padding: 24px;
            background:
                radial-gradient(circle at 45% 38%, rgba(0, 130, 187, 0.11), transparent 22rem),
                rgba(243, 247, 250, 0.82);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            /* Reset properties that the layout template injects onto .page-loader */
            width: auto;
            height: auto;
            border-radius: 0;
            border: none;
            animation: none;
        }

        .page-loader.active,
        .page-loader[aria-hidden="false"] {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-loader[aria-hidden="true"] {
            display: none;
        }

        body.is-loading {
            cursor: progress;
        }

        .loader-card {
            position: relative;
            overflow: hidden;
            min-width: min(370px, 92vw);
            border-radius: 22px;
            padding: 24px 26px;
            background:
                linear-gradient(180deg, #FFFFFF 0%, #F8FBFD 100%);
            border: 1px solid var(--wl-border-strong);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .loader-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--eco-blue), var(--eco-green), var(--eco-yellow));
        }

        .loader-text-main {
            font-size: 0.92rem;
            line-height: 1.2;
            font-weight: 950;
            color: var(--eco-blue-deep);
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .loader-text-sub {
            font-size: 0.76rem;
            line-height: 1.45;
            color: var(--wl-muted);
            font-weight: 650;
        }

        .spinner {
            display: inline-block;
            flex: 0 0 auto;
            width: 26px;
            height: 26px;
            border: 3px solid rgba(0, 130, 187, 0.16);
            border-top-color: var(--eco-blue);
            border-right-color: var(--eco-green);
            border-radius: 50%;
            animation: spin .75s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Hero */
        .wl-hero {
            position: relative;
            overflow: hidden;
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            margin-bottom: 16px;
            color: #fff;
            background:
                radial-gradient(circle at 12% 50%, rgba(190, 214, 0, 0.22), transparent 14rem),
                radial-gradient(circle at 85% 10%, rgba(255, 255, 255, 0.15), transparent 14rem),
                linear-gradient(135deg, #063755 0%, #005B82 46%, #0082BB 100%);
            box-shadow: var(--shadow-md);
            isolation: isolate;
        }

        .wl-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.10), transparent);
            transform: translateX(-100%) skewX(-12deg);
            animation: wlHeroSweep 8s ease-in-out infinite;
            z-index: 0;
        }

        @keyframes wlHeroSweep {
            0% {
                transform: translateX(-115%) skewX(-12deg);
            }

            45% {
                transform: translateX(115%) skewX(-12deg);
            }

            100% {
                transform: translateX(115%) skewX(-12deg);
            }
        }

        .wl-hero-row {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .wl-hero-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .wl-hero-icon {
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.20);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #fff;
        }

        .wl-hero-title {
            margin: 0 0 4px;
            font-size: 1.45rem;
            line-height: 1.1;
            letter-spacing: -0.03em;
            font-weight: 950;
        }

        .wl-hero-subtitle {
            margin: 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.83rem;
            line-height: 1.45;
            font-weight: 500;
            max-width: 520px;
        }

        .wl-hero-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .wl-hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.90);
            white-space: nowrap;
        }

        /* Buttons */
        .wl-btn {
            appearance: none;
            border: 0;
            border-radius: 14px;
            min-height: 42px;
            padding: 10px 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 850;
            line-height: 1;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, color 0.18s ease;
            text-decoration: none;
            white-space: nowrap;
        }

        .wl-btn:hover {
            transform: translateY(-1px);
            text-decoration: none;
        }

        .wl-btn-primary {
            color: #082338;
            background: var(--eco-green);
            box-shadow: 0 12px 24px rgba(190, 214, 0, 0.22);
        }

        .wl-btn-primary:hover {
            color: #082338;
            background: #D3EA1D;
        }

        .wl-btn-ghost {
            color: #fff;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .wl-btn-ghost:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.22);
        }

        .wl-btn-soft {
            color: var(--eco-blue-deep);
            background: rgba(0, 91, 130, 0.08);
            border: 1px solid rgba(0, 91, 130, 0.10);
        }

        .wl-btn-soft:hover {
            color: var(--eco-blue-deep);
            background: rgba(0, 91, 130, 0.13);
        }

        .wl-btn-dark {
            color: #fff;
            background: linear-gradient(135deg, var(--eco-blue-deep), var(--eco-blue-dark));
            box-shadow: 0 12px 26px rgba(7, 59, 90, 0.18);
        }

        .wl-btn-dark:hover {
            color: #fff;
            background: linear-gradient(135deg, var(--eco-blue-dark), var(--eco-blue));
        }

        .wl-btn-icon {
            width: 42px;
            padding: 0;
        }

        /* Filters */
        .wl-filter-panel {
            margin-bottom: 18px;
            padding: 16px;
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.88);
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(12px);
        }

        .wl-filter-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
        }

        .wl-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--eco-blue-deep);
            font-size: 0.92rem;
            font-weight: 950;
            letter-spacing: -0.02em;
            margin: 0;
        }

        .wl-section-title i {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            color: var(--eco-blue-dark);
            background: var(--info-soft);
        }

        .wl-filter-grid {
            display: grid;
            grid-template-columns: 1.15fr 1.15fr 1fr 1.4fr auto;
            gap: 12px;
            align-items: end;
        }

        .wl-control {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .wl-label {
            color: var(--wl-muted);
            font-size: 0.68rem;
            font-weight: 850;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .wl-input-wrap {
            position: relative;
        }

        .wl-input-wrap i {
            position: absolute;
            top: 50%;
            left: 13px;
            transform: translateY(-50%);
            color: var(--wl-faint);
            font-size: 0.83rem;
            pointer-events: none;
        }

        .wl-input,
        .wl-select {
            width: 100%;
            min-height: 43px;
            border: 1px solid rgba(7, 59, 90, 0.13);
            border-radius: 14px;
            color: var(--wl-text);
            background: #fff;
            padding: 10px 12px;
            outline: 0;
            font-size: 0.86rem;
            font-weight: 650;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .wl-input.has-icon {
            padding-left: 39px;
        }

        .wl-input:focus,
        .wl-select:focus {
            border-color: rgba(0, 130, 187, 0.62);
            box-shadow: 0 0 0 4px rgba(0, 130, 187, 0.11);
        }

        .wl-filter-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Quick chips */
        .wl-chip-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            padding-top: 14px;
            border-top: 1px solid rgba(7, 59, 90, 0.08);
        }

        .wl-chip-label {
            color: var(--wl-muted);
            font-size: 0.75rem;
            font-weight: 850;
            margin-right: 2px;
        }

        .wl-chip {
            border: 1px solid rgba(7, 59, 90, 0.11);
            background: #fff;
            color: var(--wl-muted);
            padding: 7px 11px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 0.76rem;
            font-weight: 800;
            transition: all 0.18s ease;
        }

        .wl-chip:hover {
            color: var(--eco-blue-deep);
            background: rgba(0, 130, 187, 0.07);
        }

        .wl-chip.active {
            color: #fff;
            background: var(--eco-blue-deep);
            border-color: var(--eco-blue-deep);
            box-shadow: 0 10px 22px rgba(7, 59, 90, 0.16);
        }

        /* KPI cards */
        .wl-kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .wl-kpi-card {
            position: relative;
            overflow: hidden;
            min-height: 145px;
            border-radius: var(--radius-lg);
            background: var(--wl-surface);
            border: 1px solid rgba(255, 255, 255, 0.92);
            box-shadow: var(--shadow-sm);
            padding: 17px;
        }

        .wl-kpi-card::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 96px;
            height: 96px;
            border-radius: 0 0 0 100%;
            background: rgba(0, 130, 187, 0.07);
        }

        .wl-kpi-card.green::after {
            background: rgba(190, 214, 0, 0.15);
        }

        .wl-kpi-card.warn::after {
            background: rgba(183, 121, 31, 0.11);
        }

        .wl-kpi-card.danger::after {
            background: rgba(220, 38, 38, 0.09);
        }

        .wl-kpi-head {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
        }

        .wl-kpi-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            color: var(--eco-blue-dark);
            background: rgba(0, 130, 187, 0.10);
        }

        .wl-kpi-card.green .wl-kpi-icon {
            color: var(--eco-green-dark);
            background: rgba(190, 214, 0, 0.20);
        }

        .wl-kpi-card.warn .wl-kpi-icon {
            color: var(--warning);
            background: var(--warning-soft);
        }

        .wl-kpi-card.danger .wl-kpi-icon {
            color: var(--danger);
            background: var(--danger-soft);
        }

        .wl-kpi-label {
            color: var(--wl-muted);
            font-size: 0.7rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .wl-kpi-value {
            position: relative;
            z-index: 2;
            color: var(--eco-blue-deep);
            font-size: clamp(1.35rem, 1.8vw, 2rem);
            font-weight: 950;
            letter-spacing: -0.055em;
            line-height: 1.05;
            font-variant-numeric: tabular-nums;
        }

        .wl-kpi-sub {
            position: relative;
            z-index: 2;
            margin-top: 8px;
            color: var(--wl-muted);
            font-size: 0.76rem;
            font-weight: 650;
            line-height: 1.35;
        }

        .wl-meter {
            position: relative;
            z-index: 2;
            height: 7px;
            margin-top: 12px;
            border-radius: 999px;
            background: rgba(7, 59, 90, 0.08);
            overflow: hidden;
        }

        .wl-meter span {
            display: block;
            height: 100%;
            width: 0%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--eco-blue), var(--eco-green));
            transition: width 0.35s ease;
        }

        .wl-kpi-card.danger .wl-meter span {
            background: linear-gradient(90deg, var(--danger), var(--warning));
        }

        /* Insights */
        .wl-insight-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 14px;
            margin-bottom: 18px;
        }

        .wl-insight-card {
            border-radius: var(--radius-lg);
            background: var(--wl-surface);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: var(--shadow-sm);
            padding: 18px;
        }

        .wl-insight-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .wl-insight-title {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 9px;
            color: var(--eco-blue-deep);
            font-size: 0.9rem;
            font-weight: 950;
            letter-spacing: -0.02em;
        }

        .wl-insight-title i {
            color: var(--eco-blue);
        }

        .wl-insight-body {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .wl-insight-item {
            padding: 14px;
            border-radius: 16px;
            background: var(--wl-surface-soft);
            border: 1px solid rgba(7, 59, 90, 0.07);
        }

        .wl-insight-item span {
            display: block;
            color: var(--wl-muted);
            font-size: 0.68rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 7px;
        }

        .wl-insight-item strong {
            display: block;
            color: var(--eco-blue-deep);
            font-size: 0.95rem;
            font-weight: 950;
            line-height: 1.25;
        }

        .wl-insight-item small {
            display: block;
            margin-top: 5px;
            color: var(--wl-muted);
            font-size: 0.76rem;
            font-weight: 650;
        }

        .wl-risk-list {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .wl-risk-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 15px;
            background: var(--wl-surface-soft);
            border: 1px solid rgba(7, 59, 90, 0.07);
        }

        .wl-risk-person {
            min-width: 0;
        }

        .wl-risk-person strong {
            display: block;
            color: var(--wl-text);
            font-size: 0.84rem;
            font-weight: 900;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .wl-risk-person small {
            display: block;
            margin-top: 2px;
            color: var(--wl-muted);
            font-size: 0.72rem;
            font-weight: 650;
        }

        /* Table */
        .wl-table-card {
            border-radius: var(--radius-lg);
            background: var(--wl-surface);
            border: 1px solid rgba(255, 255, 255, 0.92);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .wl-table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 16px 18px;
            border-bottom: 1px solid rgba(7, 59, 90, 0.08);
            background:
                linear-gradient(180deg, #fff, rgba(248, 251, 253, 0.88));
        }

        .wl-table-title-block {
            min-width: 0;
        }

        .wl-table-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            color: var(--eco-blue-deep);
            font-size: 0.98rem;
            font-weight: 950;
            letter-spacing: -0.025em;
        }

        .wl-table-title i {
            color: var(--eco-blue);
        }

        .wl-table-subtitle {
            margin: 4px 0 0;
            color: var(--wl-muted);
            font-size: 0.78rem;
            font-weight: 600;
        }

        .wl-table-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .wl-record-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 12px;
            border-radius: 999px;
            background: rgba(0, 130, 187, 0.08);
            color: var(--eco-blue-deep);
            font-size: 0.76rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .wl-table-wrap {
            overflow: auto;
            max-height: 68vh;
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 91, 130, 0.32) transparent;
        }

        .wl-table-wrap::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .wl-table-wrap::-webkit-scrollbar-track {
            background: transparent;
        }

        .wl-table-wrap::-webkit-scrollbar-thumb {
            background: rgba(0, 91, 130, 0.22);
            border-radius: 999px;
        }

        table.wl-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.82rem;
        }

        .wl-table thead th {
            position: sticky;
            top: 0;
            z-index: 12;
            padding: 13px 14px;
            text-align: left;
            color: #5F7285;
            background: #F8FBFD;
            border-bottom: 1px solid rgba(7, 59, 90, 0.10);
            font-size: 0.68rem;
            font-weight: 950;
            letter-spacing: 0.075em;
            text-transform: uppercase;
            white-space: nowrap;
            user-select: none;
        }

        .wl-table thead th.sortable {
            cursor: pointer;
        }

        .wl-table thead th.sortable:hover {
            color: var(--eco-blue-deep);
            background: #F1F7FB;
        }

        .wl-table thead th .sort-arrow {
            margin-left: 5px;
            opacity: 0.4;
        }

        .wl-table thead th.sort-asc .sort-arrow,
        .wl-table thead th.sort-desc .sort-arrow {
            opacity: 1;
            color: var(--eco-blue);
        }

        .wl-table tbody td {
            padding: 12px 14px;
            color: var(--wl-text);
            border-bottom: 1px solid rgba(7, 59, 90, 0.06);
            vertical-align: middle;
            white-space: nowrap;
            background: #fff;
        }

        .wl-table tbody tr:hover td {
            background: #F9FCFE;
        }

        .wl-table tfoot td {
            position: sticky;
            bottom: 0;
            z-index: 10;
            padding: 13px 14px;
            color: var(--eco-blue-deep);
            background: #EEF5F9;
            border-top: 1px solid rgba(7, 59, 90, 0.13);
            font-size: 0.82rem;
            font-weight: 950;
            white-space: nowrap;
        }

        .wl-table .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .wl-table .center {
            text-align: center;
        }

        .wl-table-card.is-compact .wl-table tbody td {
            padding-top: 8px;
            padding-bottom: 8px;
            font-size: 0.78rem;
        }

        .wl-table-card.is-compact .wl-table thead th {
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .rm-cell {
            min-width: 220px;
        }

        .rm-person {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rm-avatar {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            color: #fff;
            background: linear-gradient(135deg, var(--eco-blue-dark), var(--eco-blue));
            font-size: 0.74rem;
            font-weight: 950;
            letter-spacing: 0.03em;
        }

        .rm-name {
            min-width: 0;
        }

        .rm-name strong {
            display: block;
            max-width: 260px;
            color: var(--wl-text);
            font-size: 0.83rem;
            font-weight: 900;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rm-name small {
            display: block;
            margin-top: 2px;
            color: var(--wl-muted);
            font-size: 0.72rem;
            font-weight: 700;
        }

        .rm-code-pill,
        .segment-pill,
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 0.72rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .rm-code-pill {
            color: var(--eco-blue-deep);
            background: rgba(0, 130, 187, 0.09);
        }

        .segment-pill {
            color: var(--eco-blue-deep);
            background: rgba(0, 130, 187, 0.08);
        }

        .segment-pill.green {
            color: var(--eco-green-dark);
            background: rgba(190, 214, 0, 0.18);
        }

        .segment-pill.warn {
            color: var(--warning);
            background: var(--warning-soft);
        }

        .status-pill.good {
            color: var(--success);
            background: var(--success-soft);
        }

        .status-pill.warn {
            color: var(--warning);
            background: var(--warning-soft);
        }

        .status-pill.bad {
            color: var(--danger);
            background: var(--danger-soft);
        }

        .wl-money {
            color: var(--eco-blue-deep);
            font-weight: 950;
            font-variant-numeric: tabular-nums;
        }

        .wl-muted-dash {
            color: var(--wl-faint);
            font-weight: 700;
        }

        .wl-row-action {
            border: 0;
            border-radius: 11px;
            color: var(--eco-blue-deep);
            background: rgba(0, 130, 187, 0.08);
            padding: 8px 10px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 850;
            transition: all 0.18s ease;
        }

        .wl-row-action:hover {
            color: #fff;
            background: var(--eco-blue-deep);
        }

        .empty-row td {
            padding: 56px 20px !important;
            text-align: center;
            color: var(--wl-muted);
            font-size: 0.9rem;
            font-weight: 650;
            background: #fff;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 9px;
        }

        .empty-state i {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            color: var(--eco-blue-dark);
            background: rgba(0, 130, 187, 0.09);
            font-size: 1.1rem;
        }

        .loading-row td {
            text-align: center;
            padding: 24px;
            color: var(--wl-muted);
        }

        .skeleton {
            position: relative;
            overflow: hidden;
            background: #edf3f8;
            border-radius: 8px;
        }

        .skeleton::after {
            content: "";
            position: absolute;
            inset: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.62), transparent);
            animation: shimmer 1.15s infinite;
        }

        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }

        .sk-line {
            height: 12px;
            width: 100%;
        }

        .sk-line.sm {
            height: 10px;
        }

        .sk-line.lg {
            height: 18px;
        }

        /* Drawer */
        .wl-drawer-backdrop {
            position: fixed;
            inset: 0;
            z-index: 2700;
            display: none;
            background: rgba(8, 22, 34, 0.42);
            backdrop-filter: blur(5px);
        }

        .wl-drawer-backdrop.active {
            display: block;
        }

        .wl-drawer {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 2750;
            width: min(520px, 94vw);
            height: 100vh;
            transform: translateX(105%);
            background: #fff;
            box-shadow: -26px 0 70px rgba(7, 59, 90, 0.22);
            transition: transform 0.24s ease;
            display: flex;
            flex-direction: column;
        }

        .wl-drawer.active {
            transform: translateX(0);
        }

        .wl-drawer-header {
            padding: 22px;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(190, 214, 0, 0.24), transparent 18rem),
                linear-gradient(135deg, var(--eco-blue-deep), var(--eco-blue-dark));
        }

        .wl-drawer-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .wl-drawer-title {
            margin: 0;
            font-size: 1.22rem;
            font-weight: 950;
            letter-spacing: -0.04em;
        }

        .wl-drawer-subtitle {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.82rem;
            font-weight: 600;
        }

        .wl-drawer-close {
            width: 40px;
            height: 40px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 14px;
            color: #fff;
            background: rgba(255, 255, 255, 0.13);
            cursor: pointer;
        }

        .wl-drawer-body {
            padding: 18px;
            overflow-y: auto;
        }

        .wl-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .wl-detail-card {
            padding: 15px;
            border-radius: 18px;
            background: var(--wl-surface-soft);
            border: 1px solid rgba(7, 59, 90, 0.08);
        }

        .wl-detail-card span {
            display: block;
            color: var(--wl-muted);
            font-size: 0.68rem;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 8px;
        }

        .wl-detail-card strong {
            display: block;
            color: var(--eco-blue-deep);
            font-size: 1rem;
            font-weight: 950;
            font-variant-numeric: tabular-nums;
        }

        .wl-breakdown {
            margin-top: 16px;
            padding: 16px;
            border-radius: 20px;
            background: #fff;
            border: 1px solid rgba(7, 59, 90, 0.09);
            box-shadow: var(--shadow-sm);
        }

        .wl-breakdown h4 {
            margin: 0 0 12px;
            color: var(--eco-blue-deep);
            font-size: 0.92rem;
            font-weight: 950;
        }

        .wl-stackbar {
            height: 14px;
            display: flex;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(7, 59, 90, 0.08);
        }

        .wl-stackbar .active {
            background: var(--success);
        }

        .wl-stackbar .dormant {
            background: var(--danger);
        }

        .wl-breakdown-legend {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 12px;
            color: var(--wl-muted);
            font-size: 0.78rem;
            font-weight: 750;
        }

        .wl-dot {
            width: 9px;
            height: 9px;
            display: inline-block;
            border-radius: 50%;
            margin-right: 5px;
        }

        .wl-dot.active {
            background: var(--success);
        }

        .wl-dot.dormant {
            background: var(--danger);
        }

        /* Responsive */
        @media (max-width: 1280px) {
            .wl-kpi-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .wl-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .wl-filter-actions {
                grid-column: span 2;
                justify-content: flex-end;
            }
        }

        @media (max-width: 992px) {
            .wl-page {
                padding: 14px 14px 34px;
            }

            .wl-hero-grid {
                grid-template-columns: 1fr;
            }

            .wl-insight-grid {
                grid-template-columns: 1fr;
            }

            .wl-insight-body {
                grid-template-columns: 1fr;
            }

            .wl-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .wl-hero {
                padding: 22px;
                border-radius: 22px;
            }

            .wl-hero-actions,
            .wl-table-actions,
            .wl-filter-actions {
                width: 100%;
            }

            .wl-btn {
                flex: 1;
            }

            .wl-hero-mini-grid,
            .wl-kpi-grid,
            .wl-filter-grid {
                grid-template-columns: 1fr;
            }

            .wl-filter-actions {
                grid-column: auto;
            }

            .wl-filter-top,
            .wl-table-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .wl-table-actions {
                justify-content: flex-start;
            }

            .wl-live-value {
                font-size: 1.25rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .spinner,
            .loader-card::before,
            .wl-hero::before,
            .skeleton::after {
                animation: none !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-loader" id="page-loader" role="status" aria-live="polite" aria-hidden="true">
        <div class="loader-card">
            <span class="spinner" aria-hidden="true"></span>
            <div>
                <div class="loader-text-main">Loading RM Workload</div>
                <div class="loader-text-sub">Preparing portfolio, deposits and dormancy insights...</div>
            </div>
        </div>
    </div>

    <div class="wl-drawer-backdrop" id="wl-drawer-backdrop" onclick="closeRmDrawer()"></div>

    <aside class="wl-drawer" id="wl-drawer" aria-hidden="true">
        <div class="wl-drawer-header">
            <div class="wl-drawer-top">
                <div>
                    <h3 class="wl-drawer-title" id="drawer-rm-name">Relationship Manager</h3>
                    <p class="wl-drawer-subtitle" id="drawer-rm-meta">Portfolio details</p>
                </div>

                <button class="wl-drawer-close" type="button" onclick="closeRmDrawer()" aria-label="Close details">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="wl-drawer-body">
            <div class="wl-detail-grid">
                <div class="wl-detail-card">
                    <span>RM Code</span>
                    <strong id="drawer-rm-code">—</strong>
                </div>
                <div class="wl-detail-card">
                    <span>Segment</span>
                    <strong id="drawer-segment">—</strong>
                </div>
                <div class="wl-detail-card">
                    <span>CIFs</span>
                    <strong id="drawer-cifs">—</strong>
                </div>
                <div class="wl-detail-card">
                    <span>Accounts</span>
                    <strong id="drawer-accounts">—</strong>
                </div>
                <div class="wl-detail-card">
                    <span>Total Deposits</span>
                    <strong id="drawer-deposits">—</strong>
                </div>
                <div class="wl-detail-card">
                    <span>Total Loans <small id="drawer-loans-date" style="font-weight:600;opacity:0.7;"></small></span>
                    <strong id="drawer-loans">—</strong>
                </div>
                <div class="wl-detail-card">
                    <span>Dormancy Rate</span>
                    <strong id="drawer-dormancy">—</strong>
                </div>
            </div>

            <div class="wl-breakdown">
                <h4>Account Health Breakdown</h4>
                <div class="wl-stackbar">
                    <span class="active" id="drawer-active-bar" style="width:0%;"></span>
                    <span class="dormant" id="drawer-dormant-bar" style="width:0%;"></span>
                </div>

                <div class="wl-breakdown-legend">
                    <span><i class="wl-dot active"></i>Active: <strong id="drawer-active">—</strong></span>
                    <span><i class="wl-dot dormant"></i>Dormant: <strong id="drawer-dormant">—</strong></span>
                </div>
            </div>

            <div class="wl-breakdown" style="margin-top:14px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:12px;">
                    <h4 style="margin:0;">Accounts <span id="drawer-accounts-count" style="color:var(--wl-muted);font-weight:700;font-size:0.78rem;"></span></h4>

                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <input type="text" id="drawer-accounts-search" class="wl-input" style="min-height:34px;width:200px;"
                            placeholder="Search account, CIF, name..." oninput="renderDrawerAccounts()">

                        <button type="button" class="wl-btn wl-btn-dark" style="min-height:34px;padding:8px 12px;"
                            onclick="downloadRmAccounts()">
                            <i class="fa-solid fa-file-excel"></i>
                            Download Excel
                        </button>
                    </div>
                </div>

                <div class="wl-table-wrap" style="max-height:320px;">
                    <table class="wl-table">
                        <thead>
                            <tr>
                                <th>Account No.</th>
                                <th>CIF</th>
                                <th>Customer Name</th>
                                <th>Branch</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="drawer-accounts-body">
                            <tr class="loading-row">
                                <td colspan="5"><span class="spinner"></span> Loading accounts...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </aside>

    <div class="wl-page">
        <div class="wl-shell">

            <section class="wl-hero">
                <div class="wl-hero-row">
                    <div class="wl-hero-left">
                        <div class="wl-hero-icon">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <div>
                            <h1 class="wl-hero-title">RM Workload</h1>
                            <p class="wl-hero-subtitle">Relationship Manager portfolios — customers, accounts, deposits
                                &amp; dormancy.</p>
                        </div>
                    </div>

                    <div class="wl-hero-right">
                        <span class="wl-hero-chip">
                            <i class="fa-solid fa-user-tie"></i>
                            <span id="hero-count-val">—</span> RMs
                        </span>
                        <span class="wl-hero-chip">
                            <i class="fa-solid fa-calendar-day"></i>
                            <span id="hero-date-val">—</span>
                        </span>

                        <span class="wl-hero-chip" style="background:rgba(220,38,38,0.22);border-color:rgba(220,38,38,0.30);">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span id="hero-risk-val">—</span> Critical
                        </span>

                        <button type="button" class="wl-btn wl-btn-primary" onclick="refreshData()">
                            <i class="fa-solid fa-rotate"></i>
                            Refresh
                        </button>

                        <button type="button" class="wl-btn wl-btn-ghost" onclick="exportCsv()">
                            <i class="fa-solid fa-file-csv"></i>
                            Export
                        </button>
                    </div>
                </div>
            </section>

            <section class="wl-filter-panel">
                <div class="wl-filter-top">
                    <h2 class="wl-section-title">
                        <i class="fa-solid fa-sliders"></i>
                        Portfolio Filters
                    </h2>

                    <span class="wl-record-pill" id="row-count-chip">
                        <i class="fa-solid fa-database"></i>
                        — records
                    </span>
                </div>

                <div class="wl-filter-grid">
                    <div class="wl-control">
                        <label class="wl-label" for="ctrl-segment">Business Segment</label>
                        <select id="ctrl-segment" class="wl-select">
                            <option value="">All Segments</option>
                        </select>
                    </div>

                    <div class="wl-control">
                        <label class="wl-label" for="ctrl-subsegment">Sub-segment</label>
                        <select id="ctrl-subsegment" class="wl-select">
                            <option value="">All Sub-segments</option>
                        </select>
                    </div>

                    <div class="wl-control">
                        <label class="wl-label" for="ctrl-focus">Focus View</label>
                        <select id="ctrl-focus" class="wl-select">
                            <option value="">All Records</option>
                            <option value="critical">Critical Dormancy ≥ 30%</option>
                            <option value="warning">Warning Dormancy 10% - 29.9%</option>
                            <option value="healthy">Healthy Dormancy &lt; 10%</option>
                            <option value="top">Top 20 Deposit Leaders</option>
                        </select>
                    </div>

                    <div class="wl-control">
                        <label class="wl-label" for="ctrl-search">Search RM</label>
                        <div class="wl-input-wrap">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="ctrl-search" class="wl-input has-icon"
                                placeholder="Search by RM code, name, segment...">
                        </div>
                    </div>

                    <div class="wl-filter-actions">
                        <button type="button" class="wl-btn wl-btn-soft" onclick="resetFilters()">
                            <i class="fa-solid fa-arrow-rotate-left"></i>
                            Reset
                        </button>

                        <button type="button" class="wl-btn wl-btn-dark" onclick="exportCsv()">
                            <i class="fa-solid fa-download"></i>
                            Export
                        </button>
                    </div>
                </div>

                <div class="wl-chip-row">
                    <span class="wl-chip-label">Quick focus:</span>

                    <button type="button" class="wl-chip active" data-focus-chip="" onclick="setFocusChip('')">
                        All
                    </button>

                    <button type="button" class="wl-chip" data-focus-chip="critical"
                        onclick="setFocusChip('critical')">
                        Critical Risk
                    </button>

                    <button type="button" class="wl-chip" data-focus-chip="warning" onclick="setFocusChip('warning')">
                        Warning
                    </button>

                    <button type="button" class="wl-chip" data-focus-chip="healthy" onclick="setFocusChip('healthy')">
                        Healthy
                    </button>

                    <button type="button" class="wl-chip" data-focus-chip="top" onclick="setFocusChip('top')">
                        Deposit Leaders
                    </button>
                </div>
            </section>

            {{-- Ghost elements: keep these IDs so JS renderSummary() / renderInsights() don't throw --}}
            <div hidden aria-hidden="true">
                <span id="stat-rm-count"></span>
                <span id="stat-cif-count"></span>
                <span id="stat-acc-count"></span>
                <span id="stat-deposits"></span>
                <span id="stat-deposits-date"></span>
                <span id="stat-dormancy"></span>
                <span id="stat-dormancy-hint"></span>
                <span id="meter-rms"></span>
                <span id="meter-cifs"></span>
                <span id="meter-accounts"></span>
                <span id="meter-deposits"></span>
                <span id="meter-dormancy"></span>
                <span id="insight-top-rm"></span>
                <span id="insight-top-rm-sub"></span>
                <span id="insight-workload-rm"></span>
                <span id="insight-workload-rm-sub"></span>
                <span id="insight-healthiest-rm"></span>
                <span id="insight-healthiest-rm-sub"></span>
                <div id="risk-watch-list"></div>
                <div id="kpi-dormancy-card" class="wl-kpi-card"></div>
            </div>

            <section class="wl-table-card" id="wl-table-card">
                <div class="wl-table-toolbar">
                    <div class="wl-table-title-block">
                        <h3 class="wl-table-title">
                            <i class="fa-solid fa-table-list"></i>
                            Relationship Manager Portfolio Table
                        </h3>
                        <p class="wl-table-subtitle">
                            Sort columns, apply filters, and open any RM row for a focused portfolio summary.
                        </p>
                    </div>

                    <div class="wl-table-actions">
                        <span class="wl-record-pill" id="table-summary-chip">
                            <i class="fa-solid fa-filter"></i>
                            No filters applied
                        </span>

                        <button type="button" class="wl-btn wl-btn-soft wl-btn-icon" onclick="toggleDensity()"
                            title="Toggle compact table">
                            <i class="fa-solid fa-compress"></i>
                        </button>

                        <button type="button" class="wl-btn wl-btn-soft wl-btn-icon" onclick="refreshData()"
                            title="Refresh">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                    </div>
                </div>

                <div class="wl-table-wrap">
                    <table class="wl-table">
                        <thead>
                            <tr>
                                <th style="width:48px;">#</th>
                                <th class="sortable" onclick="setSort('officer_name')" data-col="officer_name">
                                    RM <span class="sort-arrow">↕</span>
                                </th>
                                <th class="sortable" onclick="setSort('rm_code')" data-col="rm_code">
                                    RM Code <span class="sort-arrow">↕</span>
                                </th>
                                <th class="sortable" onclick="setSort('segment')" data-col="segment">
                                    Segment <span class="sort-arrow">↕</span>
                                </th>
                                <th class="sortable" onclick="setSort('subsegment')" data-col="subsegment">
                                    Sub-segment <span class="sort-arrow">↕</span>
                                </th>
                                <th class="num sortable" onclick="setSort('cif_count')" data-col="cif_count">
                                    CIFs <span class="sort-arrow">↕</span>
                                </th>
                                <th class="num sortable" onclick="setSort('account_count')" data-col="account_count">
                                    Accounts <span class="sort-arrow">↕</span>
                                </th>
                                <th class="num sortable" onclick="setSort('total_deposits')" data-col="total_deposits">
                                    Deposits <span class="sort-arrow">↕</span>
                                </th>
                                <th class="num sortable" id="th-loans" onclick="setSort('loan_value')" data-col="loan_value">
                                    Loans <span class="sort-arrow">↕</span>
                                </th>
                                <th class="num sortable" onclick="setSort('dormancy_rate')" data-col="dormancy_rate">
                                    Dormancy <span class="sort-arrow">↕</span>
                                </th>
                                <th class="num sortable" onclick="setSort('dormant_count')" data-col="dormant_count">
                                    Dormant <span class="sort-arrow">↕</span>
                                </th>
                                <th class="num sortable" onclick="setSort('active_count')" data-col="active_count">
                                    Active <span class="sort-arrow">↕</span>
                                </th>
                                <th class="center">Action</th>
                            </tr>
                        </thead>

                        <tbody id="wl-tbody">
                            <tr class="loading-row">
                                <td colspan="13">
                                    <span class="spinner"></span>
                                    Loading RM portfolios...
                                </td>
                            </tr>
                        </tbody>

                        <tfoot id="wl-tfoot"></tfoot>
                    </table>
                </div>
            </section>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        'use strict';

        const DATA_URL = '{{ route('finance.rm-workload.data') }}';
        const ACCOUNTS_URL = '{{ route('finance.rm-workload.accounts') }}';
        const ACCOUNTS_EXPORT_URL = '{{ route('finance.rm-workload.accounts.export') }}';

        let allRows = [];
        let sortCol = 'total_deposits';
        let sortDir = 'desc';
        let latestBalanceDate = null;
        let latestLoanDate = null;
        let isCompact = false;

        let drawerRmCode = null;
        let drawerAccountsRows = [];

        function el(id) {
            return document.getElementById(id);
        }

        function esc(value) {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function asNum(value) {
            const n = Number(value);
            return Number.isFinite(n) ? n : 0;
        }

        function fmtNum(value) {
            return new Intl.NumberFormat('en-KE').format(asNum(value));
        }

        function fmtFull(value) {
            return new Intl.NumberFormat('en-KE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(asNum(value));
        }

        function fmtMoney(value) {
            const n = asNum(value);
            const abs = Math.abs(n);
            const sign = n < 0 ? '-' : '';

            if (abs >= 1e12) return sign + (abs / 1e12).toFixed(2) + 'T';
            if (abs >= 1e9) return sign + (abs / 1e9).toFixed(2) + 'B';
            if (abs >= 1e6) return sign + (abs / 1e6).toFixed(2) + 'M';
            if (abs >= 1e3) return sign + (abs / 1e3).toFixed(2) + 'K';

            return sign + abs.toFixed(2);
        }

        function initials(name, fallback = 'RM') {
            const parts = String(name || fallback)
                .trim()
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2);

            const value = parts.map(p => p.charAt(0).toUpperCase()).join('');
            return value || fallback;
        }

        function rowLoanValue(row) {
            return asNum(row.loan_value ?? row.total_loans ?? row.loans ?? row.loan_count ?? 0);
        }

        function rowDormancyRate(row) {
            if (row.dormancy_rate !== null && row.dormancy_rate !== undefined && row.dormancy_rate !== '') {
                return asNum(row.dormancy_rate);
            }

            const dormant = asNum(row.dormant_count);
            const active = asNum(row.active_count);
            const total = dormant + active;

            return total > 0 ? (dormant / total) * 100 : 0;
        }

        function riskMeta(rate) {
            const n = asNum(rate);

            if (n >= 30) {
                return {
                    key: 'bad',
                    label: 'Critical',
                    icon: 'fa-circle-exclamation'
                };
            }

            if (n >= 10) {
                return {
                    key: 'warn',
                    label: 'Watch',
                    icon: 'fa-triangle-exclamation'
                };
            }

            return {
                key: 'good',
                label: 'Healthy',
                icon: 'fa-circle-check'
            };
        }

        function dormancyPill(rate) {
            const meta = riskMeta(rate);

            return `
            <span class="status-pill ${meta.key}">
                <i class="fa-solid ${meta.icon}"></i>
                ${asNum(rate).toFixed(1)}%
            </span>
        `;
        }

        function segmentPill(segment) {
            if (!segment) {
                return '<span class="wl-muted-dash">—</span>';
            }

            const upper = String(segment).toUpperCase();
            let cls = '';

            if (upper.includes('CONSUMER')) cls = 'green';
            if (upper.includes('CORPORATE')) cls = 'warn';

            return `<span class="segment-pill ${cls}">${esc(segment)}</span>`;
        }

        function subsegmentPill(subsegment) {
            if (!subsegment) {
                return '<span class="wl-muted-dash">—</span>';
            }

            return `<span class="segment-pill">${esc(subsegment)}</span>`;
        }

        function showLoader(show) {
            const loader = el('page-loader');

            if (!loader) return;

            const isVisible = !!show;

            loader.classList.toggle('active', isVisible);
            loader.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
            document.body.classList.toggle('is-loading', isVisible);
        }

        function setLoadingRows() {
            el('wl-tbody').innerHTML =
                `<tr class="loading-row"><td colspan="13"><span class="spinner"></span> Loading RM portfolios...</td></tr>`;
            el('wl-tfoot').innerHTML = '';
        }

        async function loadData() {
            showLoader(true);
            setLoadingRows();

            try {
                const response = await fetch(DATA_URL, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const json = await response.json();

                if (!response.ok) {
                    const error = new Error(`HTTP ${response.status}`);
                    error.serverMessage = json.error || json.message || `Server error ${response.status}`;
                    throw error;
                }

                allRows = Array.isArray(json) ? json : (json.rows || []);
                latestBalanceDate = Array.isArray(json) ? null : (json.balance_date || json.date || null);
                latestLoanDate = Array.isArray(json) ? null : (json.loan_date || null);

                hydrateComputedFields();
                populateSegmentDropdown();
                renderAll();
            } catch (error) {
                console.error('RM workload fetch error:', error);

                const message = error.serverMessage ||
                    'Failed to load RM workload data. Please check the server response or browser console.';

                el('wl-tbody').innerHTML = `
                <tr class="empty-row">
                    <td colspan="13">
                        <div class="empty-state" style="color:var(--danger);">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <strong>${esc(message)}</strong>
                        </div>
                    </td>
                </tr>
            `;
                el('wl-tfoot').innerHTML = '';
            } finally {
                showLoader(false);
            }
        }

        function hydrateComputedFields() {
            allRows = allRows.map(row => ({
                ...row,
                loan_value: rowLoanValue(row),
                dormancy_rate: rowDormancyRate(row)
            }));
        }

        function refreshData() {
            loadData();
        }

        function populateSegmentDropdown() {
            const select = el('ctrl-segment');
            const current = select.value;

            const segments = [...new Set(
                allRows
                .map(row => String(row.segment || '').trim())
                .filter(Boolean)
            )].sort((a, b) => a.localeCompare(b));

            select.innerHTML = '<option value="">All Segments</option>';

            segments.forEach(segment => {
                const option = document.createElement('option');
                option.value = segment;
                option.textContent = segment;
                if (segment === current) option.selected = true;
                select.appendChild(option);
            });

            populateSubsegmentDropdown();
        }

        function populateSubsegmentDropdown() {
            const segmentValue = el('ctrl-segment').value;
            const select = el('ctrl-subsegment');
            const current = select.value;

            const source = segmentValue ?
                allRows.filter(row => String(row.segment || '') === segmentValue) :
                allRows;

            const subsegments = [...new Set(
                source
                .map(row => String(row.subsegment || '').trim())
                .filter(Boolean)
            )].sort((a, b) => a.localeCompare(b));

            select.innerHTML = '<option value="">All Sub-segments</option>';

            subsegments.forEach(subsegment => {
                const option = document.createElement('option');
                option.value = subsegment;
                option.textContent = subsegment;
                if (subsegment === current) option.selected = true;
                select.appendChild(option);
            });

            if (current && !subsegments.includes(current)) {
                select.value = '';
            }
        }

        function setFocusChip(value) {
            el('ctrl-focus').value = value;

            document.querySelectorAll('[data-focus-chip]').forEach(chip => {
                chip.classList.toggle('active', chip.dataset.focusChip === value);
            });

            renderAll();
        }

        function syncFocusChips() {
            const value = el('ctrl-focus').value;

            document.querySelectorAll('[data-focus-chip]').forEach(chip => {
                chip.classList.toggle('active', chip.dataset.focusChip === value);
            });
        }

        function getFilteredRows() {
            const segment = el('ctrl-segment').value;
            const subsegment = el('ctrl-subsegment').value;
            const focus = el('ctrl-focus').value;
            const search = String(el('ctrl-search').value || '').toLowerCase().trim();

            let rows = allRows.filter(row => {
                const rowSegment = String(row.segment || '');
                const rowSubsegment = String(row.subsegment || '');

                if (segment && rowSegment !== segment) return false;
                if (subsegment && rowSubsegment !== subsegment) return false;

                if (search) {
                    const searchable = [
                        row.rm_code,
                        row.officer_name,
                        row.segment,
                        row.subsegment
                    ].join(' ').toLowerCase();

                    if (!searchable.includes(search)) return false;
                }

                return true;
            });

            if (focus === 'critical') {
                rows = rows.filter(row => rowDormancyRate(row) >= 30);
            }

            if (focus === 'warning') {
                rows = rows.filter(row => rowDormancyRate(row) >= 10 && rowDormancyRate(row) < 30);
            }

            if (focus === 'healthy') {
                rows = rows.filter(row => rowDormancyRate(row) < 10);
            }

            if (focus === 'top') {
                rows = [...rows]
                    .sort((a, b) => asNum(b.total_deposits) - asNum(a.total_deposits))
                    .slice(0, 20);
            }

            return rows;
        }

        function getSortedRows(rows) {
            return [...rows].sort((a, b) => {
                let av = sortCol === 'loan_value' ? rowLoanValue(a) : (a[sortCol] ?? '');
                let bv = sortCol === 'loan_value' ? rowLoanValue(b) : (b[sortCol] ?? '');

                const avNum = Number(av);
                const bvNum = Number(bv);
                const bothNumeric = Number.isFinite(avNum) && Number.isFinite(bvNum) && av !== '' && bv !== '';

                if (bothNumeric) {
                    av = avNum;
                    bv = bvNum;
                } else {
                    av = String(av).toLowerCase();
                    bv = String(bv).toLowerCase();
                }

                if (av < bv) return sortDir === 'asc' ? -1 : 1;
                if (av > bv) return sortDir === 'asc' ? 1 : -1;
                return 0;
            });
        }

        function setSort(column) {
            if (sortCol === column) {
                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                sortCol = column;
                sortDir = ['officer_name', 'rm_code', 'segment', 'subsegment'].includes(column) ? 'asc' : 'desc';
            }

            updateSortHeaders();
            renderAll();
        }

        function updateSortHeaders() {
            document.querySelectorAll('.wl-table thead th[data-col]').forEach(th => {
                th.classList.remove('sort-asc', 'sort-desc');

                const arrow = th.querySelector('.sort-arrow');

                if (th.dataset.col === sortCol) {
                    th.classList.add(sortDir === 'asc' ? 'sort-asc' : 'sort-desc');
                    if (arrow) arrow.textContent = sortDir === 'asc' ? '↑' : '↓';
                } else if (arrow) {
                    arrow.textContent = '↕';
                }
            });
        }

        function renderAll() {
            syncFocusChips();

            const filteredRows = getFilteredRows();
            const sortedRows = getSortedRows(filteredRows);

            renderSummary(sortedRows);
            renderInsights(sortedRows);
            renderTable(sortedRows);
            updateFilterSummary(sortedRows.length);
        }

        function renderSummary(rows) {
            const totalRms = rows.length;
            const totalCifs = rows.reduce((sum, row) => sum + asNum(row.cif_count), 0);
            const totalAccounts = rows.reduce((sum, row) => sum + asNum(row.account_count), 0);
            const totalDeposits = rows.reduce((sum, row) => sum + asNum(row.total_deposits), 0);
            const totalDormant = rows.reduce((sum, row) => sum + asNum(row.dormant_count), 0);
            const totalActive = rows.reduce((sum, row) => sum + asNum(row.active_count), 0);
            // Match the backend's per-row basis (dormant / total accounts), not
            // dormant / (dormant + active) — those disagree whenever an account's
            // status isn't exactly Y/N and gets excluded from both counts.
            const overallDormancy = totalAccounts > 0 ? (totalDormant / totalAccounts) * 100 : 0;
            const highRisk = rows.filter(row => rowDormancyRate(row) >= 30).length;

            el('hero-count-val').textContent = fmtNum(totalRms);
            el('hero-risk-val').textContent = fmtNum(highRisk);
            el('hero-date-val').textContent = latestBalanceDate || 'Not provided';

            el('stat-rm-count').textContent = fmtNum(totalRms);
            el('stat-cif-count').textContent = fmtNum(totalCifs);
            el('stat-acc-count').textContent = fmtNum(totalAccounts);
            el('stat-deposits').textContent = fmtMoney(totalDeposits);
            el('stat-dormancy').textContent = overallDormancy.toFixed(1) + '%';

            el('stat-deposits-date').textContent = latestBalanceDate ? `· ${latestBalanceDate}` : '';

            const thLoans = el('th-loans');
            if (thLoans) thLoans.title = latestLoanDate ? `Loan balances as of ${latestLoanDate}` : 'Loan snapshot date unavailable';
            el('stat-dormancy-hint').textContent = `${fmtNum(totalDormant)} dormant vs ${fmtNum(totalActive)} active`;

            const maxRms = Math.max(allRows.length, 1);
            const allCifs = allRows.reduce((sum, row) => sum + asNum(row.cif_count), 0) || 1;
            const allAccounts = allRows.reduce((sum, row) => sum + asNum(row.account_count), 0) || 1;
            const allDeposits = allRows.reduce((sum, row) => sum + asNum(row.total_deposits), 0) || 1;

            el('meter-rms').style.width = Math.min((totalRms / maxRms) * 100, 100) + '%';
            el('meter-cifs').style.width = Math.min((totalCifs / allCifs) * 100, 100) + '%';
            el('meter-accounts').style.width = Math.min((totalAccounts / allAccounts) * 100, 100) + '%';
            el('meter-deposits').style.width = Math.min((totalDeposits / allDeposits) * 100, 100) + '%';
            el('meter-dormancy').style.width = Math.min(overallDormancy, 100) + '%';

            const dormancyCard = el('kpi-dormancy-card');
            dormancyCard.classList.remove('warn', 'danger', 'green');
            dormancyCard.classList.add(
                overallDormancy >= 30 ? 'danger' : (overallDormancy >= 10 ? 'warn' : 'green')
            );

            el('row-count-chip').innerHTML = `
            <i class="fa-solid fa-database"></i>
            ${fmtNum(totalRms)} record${totalRms === 1 ? '' : 's'}
        `;
        }

        function renderInsights(rows) {
            if (!rows.length) {
                el('insight-top-rm').textContent = '—';
                el('insight-top-rm-sub').textContent = 'No records available';

                el('insight-workload-rm').textContent = '—';
                el('insight-workload-rm-sub').textContent = 'No records available';

                el('insight-healthiest-rm').textContent = '—';
                el('insight-healthiest-rm-sub').textContent = 'No records available';

                el('risk-watch-list').innerHTML = `
                <div class="wl-risk-row">
                    <div class="wl-risk-person">
                        <strong>No risk records</strong>
                        <small>Try changing the filters</small>
                    </div>
                    <span class="status-pill good">Clear</span>
                </div>
            `;
                return;
            }

            const topDeposit = [...rows].sort((a, b) => asNum(b.total_deposits) - asNum(a.total_deposits))[0];
            const topWorkload = [...rows].sort((a, b) => asNum(b.account_count) - asNum(a.account_count))[0];
            const healthiest = [...rows].sort((a, b) => rowDormancyRate(a) - rowDormancyRate(b))[0];

            el('insight-top-rm').textContent = topDeposit?.officer_name || topDeposit?.rm_code || 'Unknown RM';
            el('insight-top-rm-sub').textContent = `KES ${fmtMoney(topDeposit?.total_deposits || 0)} deposits`;

            el('insight-workload-rm').textContent = topWorkload?.officer_name || topWorkload?.rm_code || 'Unknown RM';
            el('insight-workload-rm-sub').textContent = `${fmtNum(topWorkload?.account_count || 0)} accounts`;

            el('insight-healthiest-rm').textContent = healthiest?.officer_name || healthiest?.rm_code || 'Unknown RM';
            el('insight-healthiest-rm-sub').textContent = `${rowDormancyRate(healthiest).toFixed(1)}% dormancy`;

            const risky = [...rows]
                .filter(row => rowDormancyRate(row) >= 10)
                .sort((a, b) => rowDormancyRate(b) - rowDormancyRate(a))
                .slice(0, 3);

            if (!risky.length) {
                el('risk-watch-list').innerHTML = `
                <div class="wl-risk-row">
                    <div class="wl-risk-person">
                        <strong>No major dormancy risk</strong>
                        <small>All visible portfolios are below 10%</small>
                    </div>
                    <span class="status-pill good">
                        <i class="fa-solid fa-circle-check"></i>
                        Healthy
                    </span>
                </div>
            `;
                return;
            }

            el('risk-watch-list').innerHTML = risky.map(row => {
                const rate = rowDormancyRate(row);
                const meta = riskMeta(rate);

                return `
                <div class="wl-risk-row">
                    <div class="wl-risk-person">
                        <strong>${esc(row.officer_name || 'Unknown RM')}</strong>
                        <small>${esc(row.rm_code || '—')} · ${fmtNum(row.dormant_count)} dormant accounts</small>
                    </div>
                    <span class="status-pill ${meta.key}">
                        <i class="fa-solid ${meta.icon}"></i>
                        ${rate.toFixed(1)}%
                    </span>
                </div>
            `;
            }).join('');
        }

        function renderTable(rows) {
            const tbody = el('wl-tbody');
            const tfoot = el('wl-tfoot');

            if (!rows.length) {
                tbody.innerHTML = `
                <tr class="empty-row">
                    <td colspan="13">
                        <div class="empty-state">
                            <i class="fa-solid fa-magnifying-glass-chart"></i>
                            <strong>No records match your filters.</strong>
                            <span>Reset filters or search for another RM, segment, or sub-segment.</span>
                        </div>
                    </td>
                </tr>
            `;
                tfoot.innerHTML = '';
                return;
            }

            tbody.innerHTML = rows.map((row, index) => {
                const name = row.officer_name || 'Unknown RM';
                const rate = rowDormancyRate(row);
                const loans = rowLoanValue(row);
                const loanDisplay = loans > 0 ? fmtMoney(loans) : '<span class="wl-muted-dash">—</span>';

                return `
                <tr>
                    <td class="center">${index + 1}</td>

                    <td class="rm-cell">
                        <div class="rm-person">
                            <div class="rm-avatar">${esc(initials(name, row.rm_code || 'RM'))}</div>
                            <div class="rm-name">
                                <strong title="${esc(name)}">${esc(name)}</strong>
                                <small>${esc(row.rm_code || 'No RM code')}</small>
                            </div>
                        </div>
                    </td>

                    <td>
                        <span class="rm-code-pill">${esc(row.rm_code || '—')}</span>
                    </td>

                    <td>${segmentPill(row.segment)}</td>
                    <td>${subsegmentPill(row.subsegment)}</td>

                    <td class="num">${fmtNum(row.cif_count)}</td>
                    <td class="num">${fmtNum(row.account_count)}</td>

                    <td class="num" title="KES ${fmtFull(row.total_deposits)}">
                        <span class="wl-money">${fmtMoney(row.total_deposits)}</span>
                    </td>

                    <td class="num">${loanDisplay}</td>
                    <td class="num">${dormancyPill(rate)}</td>

                    <td class="num" style="color:var(--danger);font-weight:850;">
                        ${fmtNum(row.dormant_count)}
                    </td>

                    <td class="num" style="color:var(--success);font-weight:850;">
                        ${fmtNum(row.active_count)}
                    </td>

                    <td class="center">
                        <button type="button" class="wl-row-action" onclick="openRmDrawer(${index})">
                            View
                        </button>
                    </td>
                </tr>
            `;
            }).join('');

            window.__visibleRows = rows;

            const totalCifs = rows.reduce((sum, row) => sum + asNum(row.cif_count), 0);
            const totalAccounts = rows.reduce((sum, row) => sum + asNum(row.account_count), 0);
            const totalDeposits = rows.reduce((sum, row) => sum + asNum(row.total_deposits), 0);
            const totalLoans = rows.reduce((sum, row) => sum + rowLoanValue(row), 0);
            const totalDormant = rows.reduce((sum, row) => sum + asNum(row.dormant_count), 0);
            const totalActive = rows.reduce((sum, row) => sum + asNum(row.active_count), 0);
            // Same accounts-based basis as the backend and per-row pill, so this
            // TOTAL row can't disagree with the individual rows above it.
            const overallDormancy = totalAccounts > 0 ? (totalDormant / totalAccounts) * 100 : 0;

            tfoot.innerHTML = `
            <tr>
                <td colspan="5">
                    TOTAL · ${fmtNum(rows.length)} RM${rows.length === 1 ? '' : 's'}
                </td>
                <td class="num">${fmtNum(totalCifs)}</td>
                <td class="num">${fmtNum(totalAccounts)}</td>
                <td class="num" title="KES ${fmtFull(totalDeposits)}">${fmtMoney(totalDeposits)}</td>
                <td class="num">${totalLoans > 0 ? fmtMoney(totalLoans) : '—'}</td>
                <td class="num">${overallDormancy.toFixed(1)}%</td>
                <td class="num" style="color:var(--danger);">${fmtNum(totalDormant)}</td>
                <td class="num" style="color:var(--success);">${fmtNum(totalActive)}</td>
                <td></td>
            </tr>
        `;
        }

        function updateFilterSummary(count) {
            const filters = [];

            if (el('ctrl-segment').value) filters.push(el('ctrl-segment').value);
            if (el('ctrl-subsegment').value) filters.push(el('ctrl-subsegment').value);
            if (el('ctrl-focus').value) filters.push(el('ctrl-focus').selectedOptions[0].textContent);
            if (el('ctrl-search').value.trim()) filters.push(`Search: ${el('ctrl-search').value.trim()}`);

            el('table-summary-chip').innerHTML = `
            <i class="fa-solid fa-filter"></i>
            ${filters.length ? esc(filters.join(' · ')) : 'No filters applied'} · ${fmtNum(count)} rows
        `;
        }

        function resetFilters() {
            el('ctrl-segment').value = '';
            el('ctrl-subsegment').value = '';
            el('ctrl-focus').value = '';
            el('ctrl-search').value = '';

            populateSubsegmentDropdown();
            syncFocusChips();
            renderAll();
        }

        function toggleDensity() {
            isCompact = !isCompact;
            el('wl-table-card').classList.toggle('is-compact', isCompact);
        }

        function openRmDrawer(index) {
            const rows = window.__visibleRows || [];
            const row = rows[index];

            if (!row) return;

            const name = row.officer_name || 'Unknown RM';
            const code = row.rm_code || '—';
            const dormant = asNum(row.dormant_count);
            const active = asNum(row.active_count);
            // Base the bar on total accounts (not dormant+active) so accounts with
            // an unrecognized status show as a gap instead of being silently folded
            // into a bar that falsely reads as 100% of this RM's portfolio.
            const total = asNum(row.account_count);
            const activePct = total > 0 ? (active / total) * 100 : 0;
            const dormantPct = total > 0 ? (dormant / total) * 100 : 0;

            el('drawer-rm-name').textContent = name;
            el('drawer-rm-meta').textContent =
                `${code} · ${row.segment || 'No segment'} · ${row.subsegment || 'No sub-segment'}`;

            el('drawer-rm-code').textContent = code;
            el('drawer-segment').textContent = row.segment || '—';
            el('drawer-cifs').textContent = fmtNum(row.cif_count);
            el('drawer-accounts').textContent = fmtNum(row.account_count);
            el('drawer-deposits').textContent = 'KES ' + fmtMoney(row.total_deposits);
            const loanAmt = rowLoanValue(row);
            el('drawer-loans').textContent = loanAmt > 0 ? 'KES ' + fmtMoney(loanAmt) : '—';
            // Loans and deposits come from independent source feeds — surface the
            // loan snapshot date since it can lag the deposit date shown in the hero.
            el('drawer-loans-date').textContent = latestLoanDate ? `(as of ${latestLoanDate})` : '';
            el('drawer-dormancy').textContent = rowDormancyRate(row).toFixed(1) + '%';

            el('drawer-active').textContent = fmtNum(active);
            el('drawer-dormant').textContent = fmtNum(dormant);

            el('drawer-active-bar').style.width = activePct + '%';
            el('drawer-dormant-bar').style.width = dormantPct + '%';

            el('wl-drawer-backdrop').classList.add('active');
            el('wl-drawer').classList.add('active');
            el('wl-drawer').setAttribute('aria-hidden', 'false');

            drawerRmCode = row.rm_code || null;
            el('drawer-accounts-search').value = '';
            loadRmAccounts(drawerRmCode);
        }

        function closeRmDrawer() {
            el('wl-drawer-backdrop').classList.remove('active');
            el('wl-drawer').classList.remove('active');
            el('wl-drawer').setAttribute('aria-hidden', 'true');
        }

        async function loadRmAccounts(rmCode) {
            drawerAccountsRows = [];
            el('drawer-accounts-count').textContent = '';
            el('drawer-accounts-body').innerHTML =
                `<tr class="loading-row"><td colspan="5"><span class="spinner"></span> Loading accounts...</td></tr>`;

            if (!rmCode) {
                el('drawer-accounts-body').innerHTML =
                    `<tr class="empty-row"><td colspan="5">No RM code for this row.</td></tr>`;
                return;
            }

            try {
                const res = await fetch(`${ACCOUNTS_URL}?rm_code=${encodeURIComponent(rmCode)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const json = await res.json();

                if (!res.ok || !json.success) {
                    throw new Error(json.message || `Server error ${res.status}`);
                }

                // Ignore late responses from a drawer the user has since moved on from.
                if (drawerRmCode !== rmCode) return;

                drawerAccountsRows = Array.isArray(json.rows) ? json.rows : [];
                renderDrawerAccounts();
            } catch (error) {
                console.error('RM accounts fetch error:', error);

                if (drawerRmCode !== rmCode) return;

                el('drawer-accounts-body').innerHTML = `
                <tr class="empty-row">
                    <td colspan="5">
                        <div class="empty-state" style="color:var(--danger);">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            Failed to load accounts.
                        </div>
                    </td>
                </tr>
            `;
            }
        }

        function renderDrawerAccounts() {
            const body = el('drawer-accounts-body');
            const search = String(el('drawer-accounts-search').value || '').toLowerCase().trim();

            let rows = drawerAccountsRows;

            if (search) {
                rows = rows.filter(row => [
                    row.account_number,
                    row.cif,
                    row.customer_name
                ].join(' ').toLowerCase().includes(search));
            }

            el('drawer-accounts-count').textContent = `(${fmtNum(rows.length)})`;

            if (!rows.length) {
                body.innerHTML = `<tr class="empty-row"><td colspan="5">No accounts found.</td></tr>`;
                return;
            }

            body.innerHTML = rows.map(row => {
                const dormant = String(row.dormant_flag || '').toUpperCase() === 'Y';

                return `
                <tr>
                    <td>${esc(row.account_number || '—')}</td>
                    <td>${esc(row.cif || '—')}</td>
                    <td>${esc(row.customer_name || '—')}</td>
                    <td>${esc(row.branch_code || '—')}</td>
                    <td>
                        <span class="status-pill ${dormant ? 'bad' : 'good'}">
                            ${dormant ? 'Dormant' : 'Active'}
                        </span>
                    </td>
                </tr>
            `;
            }).join('');
        }

        function downloadRmAccounts() {
            if (!drawerRmCode) {
                alert('No RM selected.');
                return;
            }

            window.location.href = `${ACCOUNTS_EXPORT_URL}?rm_code=${encodeURIComponent(drawerRmCode)}`;
        }

        function exportCsv() {
            const rows = getSortedRows(getFilteredRows());

            if (!rows.length) {
                alert('No data to export.');
                return;
            }

            const headers = [
                '#',
                'RM Code',
                'RM Name',
                'Segment',
                'Sub-segment',
                'No. of CIFs',
                'No. of Accounts',
                'Total Deposits (KES)',
                'Loans',
                'Dormancy %',
                'Dormant',
                'Active'
            ];

            const csvRows = [headers.join(',')];

            rows.forEach((row, index) => {
                csvRows.push([
                    index + 1,
                    csvSafe(row.rm_code || ''),
                    csvSafe(row.officer_name || ''),
                    csvSafe(row.segment || ''),
                    csvSafe(row.subsegment || ''),
                    asNum(row.cif_count),
                    asNum(row.account_count),
                    asNum(row.total_deposits).toFixed(2),
                    rowLoanValue(row).toFixed(2),
                    rowDormancyRate(row).toFixed(2) + '%',
                    asNum(row.dormant_count),
                    asNum(row.active_count)
                ].join(','));
            });

            const blob = new Blob([csvRows.join('\n')], {
                type: 'text/csv;charset=utf-8;'
            });

            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            const today = new Date().toISOString().slice(0, 10);

            link.href = url;
            link.download = `rm_workload_${today}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            URL.revokeObjectURL(url);
        }

        function csvSafe(value) {
            const text = String(value ?? '').replace(/"/g, '""');
            return `"${text}"`;
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateSortHeaders();

            el('ctrl-segment').addEventListener('change', () => {
                populateSubsegmentDropdown();
                renderAll();
            });

            el('ctrl-subsegment').addEventListener('change', renderAll);

            el('ctrl-focus').addEventListener('change', () => {
                syncFocusChips();
                renderAll();
            });

            el('ctrl-search').addEventListener('input', renderAll);

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') {
                    closeRmDrawer();
                }
            });

            loadData();
        });

        window.setSort = setSort;
        window.resetFilters = resetFilters;
        window.exportCsv = exportCsv;
        window.refreshData = refreshData;
        window.toggleDensity = toggleDensity;
        window.openRmDrawer = openRmDrawer;
        window.closeRmDrawer = closeRmDrawer;
        window.setFocusChip = setFocusChip;
        window.renderDrawerAccounts = renderDrawerAccounts;
        window.downloadRmAccounts = downloadRmAccounts;
    </script>
@endpush
