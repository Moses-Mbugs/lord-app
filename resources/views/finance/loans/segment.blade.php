@extends('layouts.finance.template')

@section('title', ($segment['label'] ?? 'Segment') . ' Loans')

@push('styles')
    <style>
        :root {
            --eco-blue: #0077A8;
            --eco-blue-dark: #004F71;
            --eco-blue-soft: #E8F4F9;
            --eco-green: #0F766E;
            --eco-green-soft: #E7F6F2;
            --eco-red: #B42318;
            --eco-red-soft: #FDECEC;
            --eco-lime: #BED600;
            --eco-lime-text: #536000;
            --eco-amber: #B86E00;
            --eco-text: #243746;
            --eco-heading: #163046;
            --eco-muted: #5F6F82;
            --eco-border: #D7E1EA;
            --eco-bg: #F3F6F9;
            --eco-panel: #FFFFFF;
            --eco-shadow: 0 2px 10px rgba(16, 24, 40, 0.06);
            --eco-shadow-raised: 0 12px 28px rgba(16, 24, 40, 0.12);
            --eco-focus: 0 0 0 4px rgba(0, 119, 168, 0.22);
            --eco-radius: 16px;
        }

        .finance-home,
        .finance-home * {
            box-sizing: border-box;
        }

        .finance-home {
            min-height: 100vh;
            background: linear-gradient(180deg, #EEF3F7 0%, #F8FAFC 100%);
            color: var(--eco-text);
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            zoom: 0.75;
        }

        [hidden] {
            display: none !important;
        }

        button,
        input {
            font: inherit;
        }

        button,
        a,
        input {
            -webkit-tap-highlight-color: transparent;
        }

        button:focus-visible,
        a:focus-visible,
        input:focus-visible {
            outline: none;
            box-shadow: var(--eco-focus);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
            color: var(--eco-blue-dark);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Banner */
        .dash-banner {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 24px;
            padding: 24px clamp(16px, 2vw, 32px);
            background:
                radial-gradient(circle at 90% 0%, rgba(190, 214, 0, 0.18), transparent 30%),
                linear-gradient(135deg, var(--seg-accent-dark, #004861) 0%, var(--seg-accent, #005F86) 65%, #0087B8 100%);
        }

        .dash-banner::after {
            content: '';
            position: absolute;
            inset: auto -10% -70% 42%;
            z-index: -1;
            height: 260px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            transform: rotate(-8deg);
        }

        .banner-copy,
        .banner-meta {
            position: relative;
            z-index: 2;
        }

        .banner-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            color: rgba(255, 255, 255, 0.78);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .banner-eyebrow-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--eco-lime);
            box-shadow: 0 0 0 4px rgba(190, 214, 0, 0.15);
        }

        .banner-title {
            margin: 0 0 7px;
            color: #FFFFFF;
            font-size: clamp(26px, 3vw, 38px);
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.08;
        }

        .banner-sub {
            max-width: 760px;
            margin: 0;
            color: rgba(255, 255, 255, 0.84);
            font-size: 14px;
            line-height: 1.6;
        }

        .banner-meta {
            min-width: 230px;
            padding: 16px 18px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
        }

        .banner-meta-label {
            margin-bottom: 5px;
            color: rgba(255, 255, 255, 0.72);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .banner-meta-value {
            color: #FFFFFF;
            font-size: 20px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .banner-meta-note {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 6px;
            color: rgba(255, 255, 255, 0.72);
            font-size: 12px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            flex: 0 0 auto;
            border-radius: 999px;
            background: var(--eco-lime);
        }

        .banner-particles {
            position: absolute;
            inset: 0;
            z-index: 1;
            overflow: hidden;
            pointer-events: none;
        }

        .banner-particle {
            position: absolute;
            display: block;
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.24);
            animation: floatParticle linear infinite;
        }

        .banner-particle.alt {
            background: rgba(190, 214, 0, 0.26);
        }

        @keyframes floatParticle {
            0% {
                transform: translate3d(0, 20px, 0) scale(0.8);
                opacity: 0;
            }

            20% {
                opacity: 0.7;
            }

            100% {
                transform: translate3d(0, -120px, 0) scale(1.15);
                opacity: 0;
            }
        }

        /* Page shell */
        .finance-shell {
            width: 100%;
            padding: 18px clamp(12px, 1.6vw, 26px) 28px;
        }

        .summary-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-bottom: 14px;
        }

        .summary-card,
        .panel-card {
            border: 1px solid var(--eco-border);
            border-radius: var(--eco-radius);
            background: var(--eco-panel);
            box-shadow: var(--eco-shadow);
        }

        .summary-card {
            min-width: 0;
            padding: 16px;
            border-left: 4px solid var(--accent, var(--eco-blue));
        }

        .summary-label {
            color: var(--eco-muted);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .summary-value {
            margin: 8px 0 10px;
            color: var(--eco-blue-dark);
            font-size: clamp(22px, 2vw, 28px);
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            line-height: 1.1;
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .summary-value.is-up {
            color: var(--eco-green);
        }

        .summary-value.is-down {
            color: var(--eco-red);
        }

        .summary-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        .summary-range {
            color: var(--eco-muted);
            font-size: 12px;
        }

        .summary-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            min-height: 28px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .summary-badge.up {
            color: #065F46;
            background: var(--eco-green-soft);
        }

        .summary-badge.down {
            color: #991B1B;
            background: var(--eco-red-soft);
        }

        .summary-badge.flat {
            color: var(--eco-blue-dark);
            background: var(--eco-blue-soft);
        }

        /* Sticky toolbar */
        .dashboard-toolbar {
            position: sticky;
            top: 8px;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 14px;
            padding: 8px;
            border: 1px solid var(--eco-border);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 8px 22px rgba(16, 24, 40, 0.08);
            backdrop-filter: blur(10px);
        }

        .period-control {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px;
            border-radius: 12px;
            background: #EFF4F7;
            border: 1px solid #E0E8EF;
        }

        .period-btn,
        .panel-action {
            min-height: 40px;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: var(--eco-muted);
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: background-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
        }

        .period-btn {
            padding: 8px 14px;
            white-space: nowrap;
        }

        .period-btn[aria-pressed='true'] {
            color: #FFFFFF;
            background: linear-gradient(135deg, var(--eco-blue-dark), var(--eco-blue));
            box-shadow: 0 5px 12px rgba(0, 119, 168, 0.20);
        }

        .toolbar-note {
            min-width: 0;
            margin: 0;
            color: var(--eco-muted);
            font-size: 12px;
            line-height: 1.45;
        }

        /* Panels */
        .panel-card {
            margin-bottom: 14px;
            padding: 18px;
        }

        .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
        }

        .panel-heading-group {
            min-width: 0;
        }

        .panel-title {
            margin: 0;
            color: var(--eco-blue-dark);
            font-size: 16px;
            font-weight: 800;
            line-height: 1.3;
        }

        .panel-subtitle {
            margin: 5px 0 0;
            color: var(--eco-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .panel-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 7px;
            flex-wrap: wrap;
        }

        .panel-action {
            min-height: 38px;
            padding: 7px 11px;
            border: 1px solid var(--eco-border);
            background: #FFFFFF;
            color: var(--eco-blue-dark);
        }

        .panel-action:hover {
            background: var(--eco-blue-soft);
            border-color: #B7D9E8;
        }

        .panels-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        /* Charts */
        .chart-shell {
            position: relative;
            width: 100%;
            height: 300px;
            min-height: 220px;
        }

        .chart-shell canvas {
            width: 100% !important;
            height: 100% !important;
            transition: opacity 0.15s ease;
        }

        .chart-shell.updating canvas {
            opacity: 0.35;
        }

        .chart-empty {
            position: absolute;
            inset: 12px;
            display: grid;
            place-items: center;
            padding: 20px;
            border: 1px dashed var(--eco-border);
            border-radius: 12px;
            background: #FAFCFD;
            color: var(--eco-muted);
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .chart-shell.skeleton::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 4;
            border-radius: 12px;
            background: linear-gradient(90deg, #EEF2F6 25%, #DDE6EF 50%, #EEF2F6 75%);
            background-size: 200% 100%;
            animation: shimmer 1.3s ease-in-out infinite;
        }

        /* Top movers */
        .movers-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .movers-subhead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .movers-subtitle {
            margin: 0;
            color: var(--eco-blue-dark);
            font-size: 13px;
            font-weight: 800;
        }

        /* Data tables */
        .data-table-wrap {
            margin-top: 14px;
            overflow-x: auto;
            border: 1px solid var(--eco-border);
            border-radius: 12px;
            background: #FFFFFF;
        }

        .data-table {
            width: 100%;
            min-width: 560px;
            border-collapse: collapse;
            font-size: 12px;
        }

        .data-table caption {
            padding: 12px 14px;
            color: var(--eco-heading);
            font-weight: 800;
            text-align: left;
            background: #F7FAFC;
            border-bottom: 1px solid var(--eco-border);
        }

        .data-table th,
        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #E9EFF4;
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .data-table th:first-child,
        .data-table td:first-child {
            position: sticky;
            left: 0;
            z-index: 1;
            text-align: left;
            background: #FFFFFF;
        }

        .data-table thead th {
            color: var(--eco-heading);
            font-weight: 800;
            background: #F7FAFC;
        }

        .data-table thead th:first-child {
            z-index: 2;
            background: #F7FAFC;
        }

        .data-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .data-table tbody tr:hover td,
        .data-table tbody tr:hover th {
            background: #F8FBFD;
        }

        .data-table tbody tr:hover td:first-child,
        .data-table tbody tr:hover th:first-child {
            background: #F8FBFD;
        }

        .cell-positive {
            color: var(--eco-green);
            font-weight: 700;
        }

        .cell-negative {
            color: var(--eco-red);
            font-weight: 700;
        }

        /* Empty page state */
        .empty-state-wrap {
            padding: 24px;
        }

        .empty-state {
            max-width: 760px;
            margin: 0 auto;
            padding: 44px 24px;
            border: 1px dashed var(--eco-border);
            border-radius: 18px;
            background: #FFFFFF;
            color: var(--eco-muted);
            text-align: center;
            box-shadow: var(--eco-shadow);
        }

        .empty-state-icon {
            display: inline-grid;
            place-items: center;
            width: 52px;
            height: 52px;
            margin-bottom: 14px;
            border-radius: 999px;
            background: var(--eco-blue-soft);
            color: var(--eco-blue-dark);
            font-size: 24px;
            font-weight: 800;
        }

        .empty-state h1 {
            margin: 0 0 8px;
            color: var(--eco-blue-dark);
            font-size: 22px;
        }

        .empty-state p {
            margin: 0;
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 980px) {
            .panels-grid,
            .movers-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-toolbar {
                position: static;
            }
        }

        @media (max-width: 720px) {
            .dash-banner {
                grid-template-columns: 1fr;
                padding: 20px 16px;
            }

            .banner-meta {
                min-width: 0;
                width: 100%;
            }

            .finance-shell {
                padding: 12px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .panel-header {
                display: block;
            }

            .panel-actions {
                justify-content: flex-start;
                margin-top: 10px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .finance-home *,
            .finance-home *::before,
            .finance-home *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

            .banner-particles {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="finance-home" @if (!empty($asOfDate)) data-loan-segment-ready="true" @endif>
        <div style="padding: 14px clamp(12px, 1.6vw, 26px) 0;">
            <a href="{{ route('finance.loans.dashboard') }}" class="back-link">← Back to Loan Movements Dashboard</a>
        </div>

        @if (empty($asOfDate))
            <div class="empty-state-wrap">
                <section class="empty-state" aria-labelledby="emptyLoanSegmentTitle">
                    <div class="empty-state-icon" aria-hidden="true">!</div>
                    <h1 id="emptyLoanSegmentTitle">Loan data is not available</h1>
                    <p>No loan book snapshot data was found for {{ $segment['label'] ?? 'this segment' }}. Confirm that
                        the daily Loan Book file has been imported, then refresh this page.</p>
                </section>
            </div>
        @else
            @php
                $asOfCarbon = \Carbon\Carbon::parse($asOfDate);
                $formatCompactKes = static function ($value) {
                    if ($value === null || !is_numeric($value)) {
                        return '—';
                    }

                    $number = (float) $value;
                    $absolute = abs($number);
                    $sign = $number < 0 ? '−' : '';

                    if ($absolute >= 1_000_000_000) {
                        $formatted = number_format($absolute / 1_000_000_000, 1);
                        $suffix = 'B';
                    } elseif ($absolute >= 1_000_000) {
                        $formatted = number_format($absolute / 1_000_000, 1);
                        $suffix = 'M';
                    } elseif ($absolute >= 1_000) {
                        $formatted = number_format($absolute / 1_000, 1);
                        $suffix = 'K';
                    } else {
                        $formatted = number_format($absolute, 0);
                        $suffix = '';
                    }

                    if ($suffix !== '') {
                        $formatted = rtrim(rtrim($formatted, '0'), '.');
                    }

                    return $sign . 'KES ' . $formatted . $suffix;
                };
            @endphp

            <header class="dash-banner" style="--seg-accent: {{ $segment['color'] }}; --seg-accent-dark: {{ $segment['color'] }};">
                <div class="banner-particles" id="segmentBannerParticles" aria-hidden="true"></div>

                <div class="banner-copy">
                    <div class="banner-eyebrow">
                        <span class="banner-eyebrow-dot" aria-hidden="true"></span>
                        Segment analytics
                    </div>
                    <h1 class="banner-title">{{ $segment['label'] }} Loans</h1>
                    <p class="banner-sub">Movement, asset quality, and top mover accounts within the
                        {{ $segment['label'] }} business segment.</p>
                </div>

                <div class="banner-meta" aria-label="Data date and status">
                    <div class="banner-meta-label">As at date</div>
                    <div class="banner-meta-value">{{ $asOfCarbon->format('d M Y') }}</div>
                    <div class="banner-meta-note">
                        <span class="status-dot" aria-hidden="true"></span>
                        Latest available closing date
                    </div>
                </div>
            </header>

            <main class="finance-shell">
                <section class="summary-grid" aria-label="{{ $segment['label'] }} summary">
                    @foreach ($summaryCards ?? [] as $card)
                        @php
                            $isPlaceholder = !empty($card['is_placeholder']);
                            $hasBadge = !empty($card['badge']);
                            $changePct = $card['change_pct'] ?? null;
                            $direction = $card['direction'] ?? 'flat';
                            $isFlat =
                                $isPlaceholder || is_null($changePct) || !in_array($direction, ['up', 'down'], true);
                            $valueClass = $isFlat ? 'is-flat' : ($direction === 'up' ? 'is-up' : 'is-down');
                            $badgeClass = $isFlat ? 'flat' : ($direction === 'up' ? 'up' : 'down');
                            $arrow = $direction === 'up' ? '▲' : ($direction === 'down' ? '▼' : '');
                        @endphp

                        <article class="summary-card" style="--accent: {{ $card['accent'] ?? '#0077A8' }}">
                            <div class="summary-label">{{ $card['label'] ?? 'Summary' }}</div>
                            <div class="summary-value {{ $valueClass }}">
                                {{ !$isFlat ? $arrow . ' ' : '' }}{{ $card['value'] ?? '—' }}</div>
                            <div class="summary-foot">
                                <div class="summary-range">{{ $card['range'] ?? '' }}</div>

                                @if ($isPlaceholder)
                                    <span class="summary-badge flat">Pending</span>
                                @elseif ($hasBadge)
                                    <span class="summary-badge flat">{{ $card['badge'] }}</span>
                                @elseif ($isFlat)
                                    <span class="summary-badge flat">Balance</span>
                                @else
                                    <span class="summary-badge {{ $badgeClass }}">
                                        {{ $arrow }} {{ number_format(abs((float) $changePct), 1) }}%
                                    </span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </section>

                <div class="dashboard-toolbar" aria-label="Trend controls">
                    <p class="toolbar-note" id="periodDefinition">Each point uses the latest available business-day
                        close.</p>

                    <div class="period-control" role="group" aria-label="Chart granularity">
                        <button type="button" class="period-btn" data-period="daily" aria-pressed="true">Daily</button>
                        <button type="button" class="period-btn" data-period="weekly" aria-pressed="false">Weekly</button>
                        <button type="button" class="period-btn" data-period="monthly"
                            aria-pressed="false">Monthly</button>
                    </div>
                </div>

                <div class="panels-grid">
                    <article class="panel-card" aria-labelledby="closingTrendTitle">
                        <div class="panel-header">
                            <div class="panel-heading-group">
                                <h2 class="panel-title" id="closingTrendTitle">Closing balance trend</h2>
                                <p class="panel-subtitle" id="closingTrendSub">Closing loan book balance for this
                                    segment.</p>
                            </div>
                            <div class="panel-actions">
                                <button type="button" class="panel-action" data-toggle-table="closingTrendTableWrap"
                                    aria-controls="closingTrendTableWrap" aria-expanded="false">View data</button>
                                <button type="button" class="panel-action" data-export-key="closing">Export
                                    CSV</button>
                            </div>
                        </div>

                        <div class="chart-shell skeleton">
                            <canvas id="closingTrendChart" role="img"
                                aria-label="Bar chart of segment closing balance over time"></canvas>
                            <div class="chart-empty" id="closingTrendEmpty" hidden>No closing balance data is
                                available for this period.</div>
                        </div>

                        <div class="data-table-wrap" id="closingTrendTableWrap" hidden>
                            <table class="data-table" id="closingTrendTable">
                                <caption>Closing balance trend</caption>
                            </table>
                        </div>
                    </article>

                    <article class="panel-card" aria-labelledby="movementTrendTitle">
                        <div class="panel-header">
                            <div class="panel-heading-group">
                                <h2 class="panel-title" id="movementTrendTitle">Movement trend</h2>
                                <p class="panel-subtitle" id="movementTrendSub">Net movement between consecutive
                                    closing points.</p>
                            </div>
                            <div class="panel-actions">
                                <button type="button" class="panel-action" data-toggle-table="movementTrendTableWrap"
                                    aria-controls="movementTrendTableWrap" aria-expanded="false">View data</button>
                                <button type="button" class="panel-action" data-export-key="movement">Export
                                    CSV</button>
                            </div>
                        </div>

                        <div class="chart-shell skeleton">
                            <canvas id="movementTrendChart" role="img"
                                aria-label="Line chart of segment movement over time"></canvas>
                            <div class="chart-empty" id="movementTrendEmpty" hidden>No movement data is available for
                                this period.</div>
                        </div>

                        <div class="data-table-wrap" id="movementTrendTableWrap" hidden>
                            <table class="data-table" id="movementTrendTable">
                                <caption>Movement trend</caption>
                            </table>
                        </div>
                    </article>
                </div>

                <article class="panel-card" aria-labelledby="statusBreakdownTitle">
                    <div class="panel-header">
                        <div class="panel-heading-group">
                            <h2 class="panel-title" id="statusBreakdownTitle">Asset quality — {{ $segment['label'] }}
                            </h2>
                            <p class="panel-subtitle">Status-bucket breakdown for this segment, from
                                {{ $summaryCards[0]['range'] ?? '' }}.</p>
                        </div>
                    </div>

                    <div class="data-table-wrap">
                        <table class="data-table">
                            <caption>Status-bucket breakdown</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Status</th>
                                    <th scope="col">Opening balance</th>
                                    <th scope="col">Closing balance</th>
                                    <th scope="col">WoW</th>
                                    <th scope="col">MTD</th>
                                    <th scope="col">YTD</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($statusBreakdown['categories'] ?? []) as $row)
                                    <tr>
                                        <th scope="row">{{ $row['name'] ?? '—' }}</th>
                                        <td>{{ $formatCompactKes($row['startBalance'] ?? null) }}</td>
                                        <td>{{ $formatCompactKes($row['endBalance'] ?? null) }}</td>
                                        <td class="{{ ($row['weekOnWeek'] ?? 0) > 0 ? 'cell-positive' : (($row['weekOnWeek'] ?? 0) < 0 ? 'cell-negative' : '') }}">
                                            {{ $formatCompactKes($row['weekOnWeek'] ?? null) }}</td>
                                        <td class="{{ ($row['mtd'] ?? 0) > 0 ? 'cell-positive' : (($row['mtd'] ?? 0) < 0 ? 'cell-negative' : '') }}">
                                            {{ $formatCompactKes($row['mtd'] ?? null) }}</td>
                                        <td class="{{ ($row['ytd'] ?? 0) > 0 ? 'cell-positive' : (($row['ytd'] ?? 0) < 0 ? 'cell-negative' : '') }}">
                                            {{ $formatCompactKes($row['ytd'] ?? null) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align:center">No status data for this segment.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="panel-card" aria-labelledby="segmentTopMoversTitle">
                    <div class="panel-header">
                        <div class="panel-heading-group">
                            <h2 class="panel-title" id="segmentTopMoversTitle">Top mover accounts —
                                {{ $segment['label'] }}</h2>
                            <p class="panel-subtitle">Individual CIFs within this segment with the largest balance
                                change over the comparison window.</p>
                        </div>
                    </div>

                    <div class="movers-grid">
                        <div>
                            <div class="movers-subhead">
                                <h3 class="movers-subtitle">Top gainers</h3>
                                <button type="button" class="panel-action" data-export-key="topGainers">Export
                                    CSV</button>
                            </div>
                            <div class="data-table-wrap">
                                <table class="data-table">
                                    <caption>Top gainers</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Customer</th>
                                            <th scope="col">Branch</th>
                                            <th scope="col">Start balance</th>
                                            <th scope="col">End balance</th>
                                            <th scope="col">Movement</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse (($topMovers['gainers'] ?? []) as $row)
                                            @php($row = (array) $row)
                                            <tr>
                                                <th scope="row">{{ $row['name'] ?? '—' }}</th>
                                                <td>{{ $row['branch'] ?? '—' }}</td>
                                                <td>{{ $formatCompactKes($row['start_balance'] ?? null) }}</td>
                                                <td>{{ $formatCompactKes($row['end_balance'] ?? null) }}</td>
                                                <td class="cell-positive">
                                                    {{ $formatCompactKes($row['movement'] ?? null) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" style="text-align:center">No gainers for this
                                                    period.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <div class="movers-subhead">
                                <h3 class="movers-subtitle">Top losers</h3>
                                <button type="button" class="panel-action" data-export-key="topLosers">Export
                                    CSV</button>
                            </div>
                            <div class="data-table-wrap">
                                <table class="data-table">
                                    <caption>Top losers</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Customer</th>
                                            <th scope="col">Branch</th>
                                            <th scope="col">Start balance</th>
                                            <th scope="col">End balance</th>
                                            <th scope="col">Movement</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse (($topMovers['losers'] ?? []) as $row)
                                            @php($row = (array) $row)
                                            <tr>
                                                <th scope="row">{{ $row['name'] ?? '—' }}</th>
                                                <td>{{ $row['branch'] ?? '—' }}</td>
                                                <td>{{ $formatCompactKes($row['start_balance'] ?? null) }}</td>
                                                <td>{{ $formatCompactKes($row['end_balance'] ?? null) }}</td>
                                                <td class="cell-negative">
                                                    {{ $formatCompactKes($row['movement'] ?? null) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" style="text-align:center">No losers for this period.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </article>
            </main>
        @endif
    </div>
@endsection

@if (!empty($asOfDate))
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js">
        </script>
        <script>
            (() => {
                'use strict';

                const trend = @json($trend ?? []);
                const accentColor = @json($segment['color'] ?? '#0077A8');
                const asOfDateForFile = @json($asOfDate);

                const state = { period: 'daily' };
                const charts = { closing: null, movement: null };
                const exportData = {};
                const exactNumberFormatter = new Intl.NumberFormat('en-KE', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });

                const tip = {
                    backgroundColor: 'rgba(255,255,255,0.98)',
                    borderWidth: 1,
                    borderColor: 'rgba(0,119,168,0.22)',
                    titleColor: '#004F71',
                    bodyColor: '#243746',
                    padding: 11,
                    cornerRadius: 9,
                    displayColors: true
                };

                function safeArray(value) {
                    return Array.isArray(value) ? value : [];
                }

                function asNumber(value) {
                    if (value === null || value === undefined || value === '') return null;
                    const number = Number(value);
                    return Number.isFinite(number) ? number : null;
                }

                function hasNumericData(values) {
                    return safeArray(values).some(value => asNumber(value) !== null);
                }

                function fKes(value, decimals = 1) {
                    const number = asNumber(value);
                    if (number === null) return '—';

                    const absolute = Math.abs(number);
                    const sign = number < 0 ? '−' : '';
                    let divisor = 1;
                    let suffix = '';

                    if (absolute >= 1e9) { divisor = 1e9; suffix = 'B'; }
                    else if (absolute >= 1e6) { divisor = 1e6; suffix = 'M'; }
                    else if (absolute >= 1e3) { divisor = 1e3; suffix = 'K'; }

                    let formatted = (absolute / divisor).toFixed(suffix ? decimals : 0);
                    formatted = formatted.replace(/(\.\d*?[1-9])0+$/, '$1').replace(/\.0+$/, '');

                    return `${sign}KES ${formatted}${suffix}`;
                }

                function fKesExact(value) {
                    const number = asNumber(value);
                    if (number === null) return '—';
                    const sign = number < 0 ? '−' : '';
                    return `${sign}KES ${exactNumberFormatter.format(Math.abs(number))}`;
                }

                function fAxis(value) {
                    const number = asNumber(value);
                    if (number === null) return '—';
                    const absolute = Math.abs(number);

                    if (absolute >= 1e9) return `${(number / 1e9).toFixed(1)}B`;
                    if (absolute >= 1e6) return `${(number / 1e6).toFixed(1)}M`;
                    if (absolute >= 1e3) return `${(number / 1e3).toFixed(1)}K`;
                    return Number(number).toFixed(0);
                }

                function movementClass(value) {
                    const number = asNumber(value);
                    if (number === null || number === 0) return '';
                    return number > 0 ? 'cell-positive' : 'cell-negative';
                }

                function removeSkeleton(canvasId) {
                    const canvas = document.getElementById(canvasId);
                    canvas?.closest('.chart-shell')?.classList.remove('skeleton');
                }

                function withFade(canvasId, callback) {
                    const canvas = document.getElementById(canvasId);
                    const shell = canvas?.closest('.chart-shell');
                    shell?.classList.add('updating');
                    callback();
                    window.setTimeout(() => shell?.classList.remove('updating'), 170);
                }

                function setChartAvailability(canvasId, emptyId, hasData) {
                    const canvas = document.getElementById(canvasId);
                    const empty = document.getElementById(emptyId);
                    if (canvas) canvas.hidden = !hasData;
                    if (empty) empty.hidden = hasData;
                    removeSkeleton(canvasId);
                }

                function zeroGridColor(context) {
                    return Number(context.tick?.value) === 0 ? 'rgba(36,55,70,0.46)' : 'rgba(36,55,70,0.08)';
                }

                function zeroGridWidth(context) {
                    return Number(context.tick?.value) === 0 ? 2 : 1;
                }

                function buildTable(tableId, caption, headers, rows, cellClasses = []) {
                    const table = document.getElementById(tableId);
                    if (!table) return;

                    table.replaceChildren();

                    const captionElement = document.createElement('caption');
                    captionElement.textContent = caption;
                    table.appendChild(captionElement);

                    const thead = document.createElement('thead');
                    const headRow = document.createElement('tr');
                    headers.forEach(header => {
                        const th = document.createElement('th');
                        th.scope = 'col';
                        th.textContent = String(header);
                        headRow.appendChild(th);
                    });
                    thead.appendChild(headRow);
                    table.appendChild(thead);

                    const tbody = document.createElement('tbody');
                    rows.forEach((row, rowIndex) => {
                        const tr = document.createElement('tr');
                        row.forEach((value, columnIndex) => {
                            const cell = columnIndex === 0 ? document.createElement('th') : document.createElement('td');
                            if (columnIndex === 0) cell.scope = 'row';
                            cell.textContent = value === null || value === undefined ? '—' : String(value);
                            const className = cellClasses[rowIndex]?.[columnIndex];
                            if (className) cell.classList.add(className);
                            tr.appendChild(cell);
                        });
                        tbody.appendChild(tr);
                    });
                    table.appendChild(tbody);
                }

                function csvEscape(value) {
                    const stringValue = value === null || value === undefined ? '' : String(value);
                    return /[",\n]/.test(stringValue) ? `"${stringValue.replace(/"/g, '""')}"` : stringValue;
                }

                function downloadCsv(key) {
                    const payload = exportData[key];
                    if (!payload || !payload.rows?.length) return;

                    const lines = [payload.headers, ...payload.rows].map(row => row.map(csvEscape).join(','));
                    const blob = new Blob([`﻿${lines.join('\n')}`], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = payload.filename;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    URL.revokeObjectURL(url);
                }

                function buildClosingChart() {
                    const canvas = document.getElementById('closingTrendChart');
                    return new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: { labels: [], datasets: [{
                            label: 'Closing balance',
                            data: [],
                            backgroundColor: accentColor,
                            borderColor: accentColor,
                            borderWidth: 1,
                            borderRadius: 6,
                            borderSkipped: false,
                            maxBarThickness: 34
                        }] },
                        options: {
                            maintainAspectRatio: false,
                            responsive: true,
                            plugins: {
                                legend: { display: false },
                                tooltip: Object.assign({}, tip, { callbacks: { label: context => ` ${fKes(context.parsed.y)}` } }),
                                datalabels: { display: false }
                            },
                            scales: {
                                x: { grid: { display: false }, ticks: { color: '#5F6F82', maxRotation: 0, autoSkip: true, maxTicksLimit: 14 } },
                                y: { beginAtZero: true, grid: { color: 'rgba(36,55,70,0.08)' }, ticks: { color: '#5F6F82', callback: fAxis } }
                            }
                        }
                    });
                }

                function buildMovementChart() {
                    const canvas = document.getElementById('movementTrendChart');
                    const context = canvas.getContext('2d');
                    const gradient = context.createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, 'rgba(0,119,168,0.22)');
                    gradient.addColorStop(1, 'rgba(0,119,168,0.01)');

                    return new Chart(context, {
                        type: 'line',
                        data: { labels: [], datasets: [{
                            label: 'Net movement',
                            data: [],
                            borderColor: accentColor,
                            backgroundColor: gradient,
                            fill: true,
                            borderWidth: 2.5,
                            tension: 0.32,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBorderWidth: 2,
                            pointBorderColor: '#FFFFFF',
                            pointBackgroundColor(context) {
                                const value = asNumber(context.raw) ?? 0;
                                return value >= 0 ? '#0F766E' : '#B42318';
                            }
                        }] },
                        options: {
                            maintainAspectRatio: false,
                            responsive: true,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { display: false },
                                tooltip: Object.assign({}, tip, { callbacks: { label: context => ` Movement: ${fKes(context.parsed.y)}` } }),
                                datalabels: { display: false }
                            },
                            scales: {
                                x: { grid: { color: 'rgba(36,55,70,0.06)' }, ticks: { color: '#5F6F82', maxRotation: 35, autoSkip: true, maxTicksLimit: 14 } },
                                y: { grid: { color: zeroGridColor, lineWidth: zeroGridWidth }, ticks: { color: '#5F6F82', callback: fAxis } }
                            }
                        }
                    });
                }

                function updateClosingChart(period) {
                    const payload = trend?.[period]?.closing || {};
                    const labels = safeArray(payload.labels);
                    const data = safeArray(payload.data);
                    const hasData = hasNumericData(data);

                    setChartAvailability('closingTrendChart', 'closingTrendEmpty', hasData);

                    const rawRows = labels.map((label, index) => [label, data[index] ?? null]);
                    buildTable('closingTrendTable', 'Closing balance trend', ['Period', 'Closing balance'],
                        rawRows.map(row => [row[0], fKesExact(row[1])]));
                    exportData.closing = {
                        filename: `loan-segment-closing-${period}-${asOfDateForFile}.csv`,
                        headers: ['Period', 'Closing balance'],
                        rows: rawRows
                    };

                    if (!charts.closing || !hasData) return;

                    withFade('closingTrendChart', () => {
                        charts.closing.data.labels = labels;
                        charts.closing.data.datasets[0].data = data;
                        charts.closing.update();
                    });
                }

                function updateMovementChart(period) {
                    const payload = trend?.[period]?.movement || {};
                    const labels = safeArray(payload.labels);
                    const data = safeArray(payload.data);
                    const hasData = hasNumericData(data);

                    setChartAvailability('movementTrendChart', 'movementTrendEmpty', hasData);

                    const rawRows = labels.map((label, index) => [label, data[index] ?? null]);
                    const cellClasses = rawRows.map(row => ['', movementClass(row[1])]);
                    buildTable('movementTrendTable', 'Movement trend', ['Period', 'Movement'],
                        rawRows.map(row => [row[0], fKesExact(row[1])]), cellClasses);
                    exportData.movement = {
                        filename: `loan-segment-movement-${period}-${asOfDateForFile}.csv`,
                        headers: ['Period', 'Movement'],
                        rows: rawRows
                    };

                    if (!charts.movement || !hasData) return;

                    withFade('movementTrendChart', () => {
                        charts.movement.data.labels = labels;
                        charts.movement.data.datasets[0].data = data;
                        charts.movement.update();
                    });
                }

                function setPeriod(period, focusBtn = true) {
                    if (!['daily', 'weekly', 'monthly'].includes(period)) return;
                    state.period = period;

                    document.querySelectorAll('[data-period]').forEach(button => {
                        button.setAttribute('aria-pressed', String(button.dataset.period === period));
                    });

                    updateClosingChart(period);
                    updateMovementChart(period);
                }

                function bindTableToggles() {
                    document.querySelectorAll('[data-toggle-table]').forEach(button => {
                        button.addEventListener('click', () => {
                            const target = document.getElementById(button.dataset.toggleTable);
                            if (!target) return;
                            const opening = target.hidden;
                            target.hidden = !opening;
                            button.setAttribute('aria-expanded', String(opening));
                            button.textContent = opening ? 'Hide data' : 'View data';
                        });
                    });
                }

                function bindExports() {
                    document.querySelectorAll('[data-export-key]').forEach(button => {
                        button.addEventListener('click', () => downloadCsv(button.dataset.exportKey));
                    });
                }

                function seedTableExport(key, tableSelector, filenamePrefix) {
                    const table = document.querySelector(tableSelector);
                    if (!table) return;

                    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
                    const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr =>
                        Array.from(tr.querySelectorAll('th, td')).map(cell => cell.textContent.trim())
                    );

                    exportData[key] = {
                        filename: `${filenamePrefix}-${asOfDateForFile}.csv`,
                        headers,
                        rows
                    };
                }

                function seedParticles(id, count = 14) {
                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                    const host = document.getElementById(id);
                    if (!host) return;
                    host.innerHTML = '';

                    for (let index = 0; index < count; index += 1) {
                        const particle = document.createElement('span');
                        particle.className = `banner-particle${index % 4 === 0 ? ' alt' : ''}`;
                        particle.style.left = `${Math.random() * 100}%`;
                        particle.style.bottom = `${-20 - Math.random() * 36}px`;
                        particle.style.animationDuration = `${8 + Math.random() * 6}s`;
                        particle.style.animationDelay = `${Math.random() * 5}s`;
                        particle.style.opacity = String(0.15 + Math.random() * 0.45);
                        const size = 4 + Math.random() * 5;
                        particle.style.width = `${size}px`;
                        particle.style.height = `${size}px`;
                        host.appendChild(particle);
                    }
                }

                function handleChartLibraryFailure() {
                    document.querySelectorAll('.chart-shell').forEach(shell => shell.classList.remove('skeleton'));
                    document.querySelectorAll('.chart-empty').forEach(empty => {
                        empty.hidden = false;
                        empty.textContent = 'Charts could not be loaded. Refresh the page or check the Chart.js connection.';
                    });
                    document.querySelectorAll('canvas').forEach(canvas => canvas.hidden = true);
                }

                document.addEventListener('DOMContentLoaded', () => {
                    const root = document.querySelector('[data-loan-segment-ready="true"]');
                    if (!root) return;

                    seedParticles('segmentBannerParticles', 14);
                    bindTableToggles();
                    bindExports();

                    seedTableExport('topGainers', '[aria-labelledby="segmentTopMoversTitle"] table:nth-of-type(1)', 'loan-segment-top-gainers');
                    seedTableExport('topLosers', '[aria-labelledby="segmentTopMoversTitle"] table:nth-of-type(2)', 'loan-segment-top-losers');

                    if (typeof Chart === 'undefined') {
                        handleChartLibraryFailure();
                        return;
                    }

                    Chart.defaults.color = '#5F6F82';
                    Chart.defaults.font.family = 'Montserrat, Segoe UI, Arial, sans-serif';
                    Chart.defaults.font.size = 11;

                    if (typeof ChartDataLabels !== 'undefined') {
                        Chart.register(ChartDataLabels);
                    }

                    charts.closing = buildClosingChart();
                    charts.movement = buildMovementChart();

                    setPeriod('daily');

                    document.querySelectorAll('[data-period]').forEach(button => {
                        button.addEventListener('click', () => setPeriod(button.dataset.period));
                    });

                    let resizeTimer = null;
                    window.addEventListener('resize', () => {
                        window.clearTimeout(resizeTimer);
                        resizeTimer = window.setTimeout(() => Object.values(charts).forEach(c => c?.resize()), 120);
                    });
                });
            })();
        </script>
    @endpush
@endif
