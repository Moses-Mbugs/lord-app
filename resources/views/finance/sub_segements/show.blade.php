@extends('layouts.finance.template')

@section('title', $segment['label'] . ' Segment Dashboard')

@push('styles')
    <style>
        :root {
            --eco-blue: #0082BB;
            --eco-blue-dark: #005B82;
            --eco-green: #10B981;
            --eco-lime: #BED600;
            --eco-muted: #8A96A8;
            --eco-text: #464646;
            --eco-border: #D9E2EC;
            --eco-bg: #EEF2F6;
            --eco-panel: #FFFFFF;
            --eco-shadow: 0 2px 8px rgba(16, 24, 40, 0.06);
            --eco-hover: 0 12px 28px rgba(16, 24, 40, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        .spark-canvas {
            display: block;
            width: 100%;
            height: 36px;
            margin-top: 8px;
            border-radius: 4px;
            opacity: 0.72;
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
            border-radius: 8px;
            background: linear-gradient(90deg, #EEF2F6 25%, #DDE6EF 50%, #EEF2F6 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s ease-in-out infinite;
            z-index: 2;
        }

        .chart-shell canvas {
            transition: opacity 0.15s ease;
        }

        .chart-shell.updating canvas {
            opacity: 0.3;
        }

        .segment-page {
            min-height: 100vh;
            background: linear-gradient(180deg, #EEF2F6 0%, #F8FAFC 100%);
            color: var(--eco-text);
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
        }

        .segment-hero {
            position: relative;
            overflow: hidden;
            padding: 24px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: center;
            background:
                radial-gradient(circle at top right, rgba(190, 214, 0, 0.18), transparent 28%),
                linear-gradient(135deg, {{ $segment['color'] }} 0%, #005B82 100%);
        }

        .hero-particles {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .hero-particle {
            position: absolute;
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.26);
            animation: floatParticle linear infinite;
        }

        .hero-particle.alt {
            background: rgba(190, 214, 0, 0.24);
        }

        @keyframes floatParticle {
            0% {
                transform: translate3d(0, 28px, 0) scale(.8);
                opacity: 0;
            }

            20% {
                opacity: .9;
            }

            100% {
                transform: translate3d(0, -140px, 0) scale(1.25);
                opacity: 0;
            }
        }

        .hero-copy,
        .hero-meta {
            position: relative;
            z-index: 1;
        }

        .hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, 0.86);
            margin-bottom: 10px;
        }

        .hero-title {
            margin: 0 0 6px;
            font-size: clamp(24px, 2vw, 32px);
            font-weight: 800;
            letter-spacing: -.03em;
            color: #fff;
        }

        .hero-sub {
            margin: 0;
            max-width: 740px;
            font-size: 12px;
            line-height: 1.55;
            color: rgba(255, 255, 255, 0.8);
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            min-width: 430px;
        }

        .hero-stat {
            padding: 14px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(8px);
        }

        .hero-stat-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: rgba(255, 255, 255, 0.72);
            margin-bottom: 6px;
        }

        .hero-stat-value {
            font-size: 19px;
            font-weight: 800;
            color: #fff;
            line-height: 1.08;
        }

        .hero-stat-note {
            margin-top: 4px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.62);
        }

        .segment-shell {
            width: 100%;
            padding: 16px clamp(12px, 1.3vw, 24px) 24px;
        }

        .summary-grid,
        .sub-card-grid {
            display: grid;
            gap: 12px;
            margin-bottom: 14px;
        }

        .summary-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .sub-card-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .summary-card,
        .sub-card,
        .panel-card,
        .table-card {
            background: var(--eco-panel);
            border: 1px solid var(--eco-border);
            border-radius: 16px;
            box-shadow: var(--eco-shadow);
        }

        .summary-card {
            padding: 14px;
            border-left: 4px solid var(--accent, var(--eco-blue));
            transition: .2s ease;
        }

        .summary-card:hover,
        .sub-card:hover {
            box-shadow: var(--eco-hover);
            transform: translateY(-1px);
        }

        .summary-label,
        .table-head {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--eco-muted);
        }

        .summary-value {
            margin: 7px 0;
            font-size: 23px;
            font-weight: 800;
            line-height: 1.05;
        }

        .summary-value-lines {
            margin: 7px 0;
            display: grid;
            gap: 5px;
            font-size: 18px;
            font-weight: 800;
            line-height: 1.15;
        }

        .summary-value-line {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .summary-value.is-up {
            color: #059669;
        }

        .summary-value.is-down {
            color: #DC2626;
        }

        .summary-value.is-flat {
            color: var(--eco-blue-dark);
        }

        .summary-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        .summary-range,
        .sub-card-meta,
        .panel-subtitle {
            font-size: 11px;
            color: var(--eco-muted);
        }

        .summary-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
        }

        .summary-badge.up {
            background: rgba(16, 185, 129, 0.14);
            color: #065f46;
        }

        .summary-badge.down {
            background: rgba(220, 38, 38, 0.10);
            color: #991b1b;
        }

        .summary-badge.flat {
            background: rgba(0, 130, 187, 0.10);
            color: var(--eco-blue-dark);
        }

        .sub-card {
            padding: 14px;
            border-top: 3px solid {{ $segment['color'] }};
        }

        .sub-card-title {
            margin: 0 0 10px;
            font-size: 15px;
            font-weight: 800;
            color: #163046;
            line-height: 1.25;
        }

        .sub-card-value {
            font-size: 22px;
            font-weight: 800;
            color: var(--eco-blue-dark);
        }

        .sub-card-share {
            display: inline-flex;
            margin-top: 8px;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(0, 130, 187, 0.08);
            color: {{ $segment['color'] }};
            font-size: 10px;
            font-weight: 700;
        }

        .panel-card,
        .table-card {
            padding: 16px;
            margin-bottom: 14px;
        }

        .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .panel-title {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            color: var(--eco-blue-dark);
        }

        .chart-shell {
            position: relative;
            width: 100%;
            height: 340px;
        }

        .chart-shell canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .chart-mode-switch {
            display: inline-flex;
            gap: 2px;
            padding: 3px;
            border-radius: 12px;
            background: #F1F5F9;
            border: 1px solid var(--eco-border);
        }

        .chart-mode-btn {
            border: 0;
            border-radius: 8px;
            padding: 6px 11px;
            font-size: 10px;
            font-weight: 700;
            color: var(--eco-muted);
            background: transparent;
            cursor: pointer;
        }

        .chart-mode-btn.active {
            background: var(--eco-blue);
            color: #fff;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            text-decoration: none;
            color: var(--eco-blue-dark);
            font-size: 12px;
            font-weight: 700;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #EEF2F6;
        }

        table.segment-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        table.segment-table th,
        table.segment-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #EEF2F6;
            text-align: left;
            white-space: nowrap;
            font-size: 12px;
        }

        table.segment-table th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--eco-muted);
            background: #F8FBFD;
        }

        table.segment-table td.right,
        table.segment-table th.right {
            text-align: right;
        }

        .movement-up {
            color: #059669;
            font-weight: 700;
        }

        .movement-down {
            color: #DC2626;
            font-weight: 700;
        }

        .movement-flat {
            color: var(--eco-blue-dark);
            font-weight: 700;
        }

        .group-pill {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(0, 130, 187, 0.08);
            color: {{ $segment['color'] }};
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .04em;
        }

        .empty-state {
            border-radius: 16px;
            padding: 40px 24px;
            text-align: center;
            color: var(--eco-muted);
            background: #fff;
            border: 1px dashed var(--eco-border);
        }

        .empty-state h4 {
            margin: 0 0 8px;
            color: var(--eco-blue-dark);
        }

        .drivers-btn {
            border: 0;
            border-radius: 999px;
            padding: 7px 12px;
            background: rgba(0, 130, 187, 0.10);
            color: var(--eco-blue-dark);
            font-size: 10px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
            transition: .18s ease;
        }

        .drivers-btn:hover {
            background: var(--eco-blue);
            color: #fff;
        }

        .drivers-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(15, 23, 42, 0.58);
            backdrop-filter: blur(4px);
            padding: 28px;
            overflow-y: auto;
        }

        .drivers-modal {
            max-width: 1180px;
            margin: 0 auto;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
            overflow: hidden;
        }

        .drivers-modal-head {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            padding: 20px 22px;
            background:
                radial-gradient(circle at top right, rgba(190, 214, 0, .18), transparent 30%),
                linear-gradient(135deg, var(--eco-blue-dark), var(--eco-blue));
            color: #fff;
        }

        .drivers-kicker {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
            opacity: .78;
            margin-bottom: 6px;
        }

        .drivers-modal-head h3 {
            margin: 0;
            font-size: 21px;
            font-weight: 800;
        }

        .drivers-modal-head p {
            margin: 5px 0 0;
            font-size: 12px;
            opacity: .78;
        }

        .drivers-close {
            width: 34px;
            height: 34px;
            border: 1px solid rgba(255, 255, 255, .28);
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            color: #fff;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
        }

        .drivers-tabs {
            display: flex;
            gap: 8px;
            padding: 14px 18px;
            border-bottom: 1px solid var(--eco-border);
            background: #F8FBFD;
        }

        .drivers-tab {
            border: 1px solid var(--eco-border);
            background: #fff;
            color: var(--eco-muted);
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
        }

        .drivers-tab.active {
            background: var(--eco-blue);
            color: #fff;
            border-color: var(--eco-blue);
        }

        .drivers-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            padding: 18px;
        }

        .drivers-column {
            border: 1px solid var(--eco-border);
            border-radius: 16px;
            overflow: hidden;
        }

        .drivers-section-title {
            padding: 12px 14px;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .drivers-section-title.gain {
            background: rgba(16, 185, 129, .12);
            color: #065f46;
        }

        .drivers-section-title.loss {
            background: rgba(220, 38, 38, .10);
            color: #991b1b;
        }

        .drivers-table-wrap {
            overflow-x: auto;
        }

        .drivers-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 520px;
        }

        .drivers-table th,
        .drivers-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #EEF2F6;
            font-size: 11px;
            white-space: nowrap;
        }

        .drivers-table th {
            background: #F8FBFD;
            color: var(--eco-muted);
            text-transform: uppercase;
            letter-spacing: .06em;
            font-size: 9px;
        }

        .drivers-table .right {
            text-align: right;
        }

        .drivers-empty,
        .drivers-loading {
            text-align: center;
            color: var(--eco-muted);
            padding: 18px !important;
        }

        @media (max-width: 1100px) {
            .segment-hero {
                grid-template-columns: 1fr;
            }

            .hero-stats {
                min-width: 100%;
            }
        }

        @media (max-width: 900px) {
            .drivers-body {
                grid-template-columns: 1fr;
            }

            .drivers-modal-backdrop {
                padding: 12px;
            }
        }

        @media (max-width: 768px) {
            .segment-shell {
                padding: 12px;
            }

            .summary-grid,
            .sub-card-grid {
                grid-template-columns: 1fr;
            }

            .hero-stats {
                grid-template-columns: repeat(3, 140px);
                overflow-x: auto;
                padding-bottom: 4px;
                gap: 8px;
                min-width: unset;
                width: 100%;
            }

            .hero-stat {
                min-width: 0;
            }

            .chart-shell {
                height: 280px;
            }
        }

        /* ─────────────────────────────────────────
                       Compact segment dashboard density mode
                       Good for users on 125%–150% display scaling
                    ───────────────────────────────────────── */

        .segment-page {
            font-size: 11px;
        }

        .segment-hero {
            padding: 16px 20px;
            gap: 14px;
        }

        .hero-chip {
            padding: 4px 8px;
            font-size: 9px;
            margin-bottom: 7px;
        }

        .hero-title {
            font-size: clamp(20px, 1.7vw, 26px);
            margin-bottom: 4px;
        }

        .hero-sub {
            font-size: 11px;
            line-height: 1.45;
        }

        .hero-stats {
            gap: 8px;
            min-width: 380px;
        }

        .hero-stat {
            padding: 10px 12px;
            border-radius: 13px;
        }

        .hero-stat-label {
            font-size: 9px;
            margin-bottom: 4px;
        }

        .hero-stat-value {
            font-size: 15px;
        }

        .hero-stat-note {
            font-size: 10px;
        }

        .segment-shell {
            padding: 12px clamp(10px, 1vw, 18px) 18px;
        }

        .summary-grid,
        .sub-card-grid {
            gap: 9px;
            margin-bottom: 10px;
        }

        .summary-card,
        .sub-card,
        .panel-card,
        .table-card {
            border-radius: 13px;
        }

        .summary-card,
        .sub-card {
            padding: 10px 11px;
        }

        .panel-card,
        .table-card {
            padding: 12px;
            margin-bottom: 10px;
        }

        .summary-label,
        .table-head {
            font-size: 9px;
            letter-spacing: .065em;
        }

        .summary-value {
            margin: 5px 0;
            font-size: 18px;
            line-height: 1.05;
        }

        .summary-value-lines {
            margin: 5px 0;
            gap: 3px;
            font-size: 15px;
        }

        .summary-range,
        .sub-card-meta,
        .panel-subtitle {
            font-size: 10px;
        }

        .summary-badge {
            padding: 3px 7px;
            font-size: 9px;
        }

        .sub-card-title {
            margin-bottom: 7px;
            font-size: 13px;
        }

        .sub-card-value {
            font-size: 18px;
        }

        .sub-card-share {
            margin-top: 6px;
            padding: 3px 7px;
            font-size: 9px;
        }

        .back-link {
            margin-bottom: 10px;
            font-size: 10px;
        }

        .panel-header {
            gap: 8px;
            margin-bottom: 9px;
        }

        .panel-title {
            font-size: 13px;
        }

        .chart-mode-switch {
            padding: 2px;
            border-radius: 10px;
        }

        .chart-mode-btn {
            padding: 5px 9px;
            font-size: 9px;
            border-radius: 7px;
        }

        .chart-shell {
            height: 315px;
        }

        .spark-canvas {
            height: 30px;
            margin-top: 6px;
        }

        table.segment-table {
            min-width: 700px;
        }

        table.segment-table th,
        table.segment-table td {
            padding: 9px 11px;
            font-size: 10px;
        }

        table.segment-table th {
            font-size: 9px;
        }

        .group-pill {
            padding: 4px 8px;
            font-size: 9px;
        }

        .drivers-btn {
            padding: 5px 9px;
            font-size: 9px;
        }

        .drivers-modal {
            max-width: 1080px;
        }

        .drivers-modal-head {
            padding: 15px 18px;
        }

        .drivers-kicker {
            font-size: 9px;
        }

        .drivers-modal-head h3 {
            font-size: 17px;
        }

        .drivers-modal-head p {
            font-size: 10px;
        }

        .drivers-tabs {
            padding: 10px 14px;
            gap: 6px;
        }

        .drivers-tab {
            padding: 6px 11px;
            font-size: 10px;
        }

        .drivers-body {
            gap: 12px;
            padding: 14px;
        }

        .drivers-section-title {
            padding: 9px 11px;
            font-size: 10px;
        }

        .drivers-table th,
        .drivers-table td {
            padding: 8px 10px;
            font-size: 10px;
        }

        .drivers-table th {
            font-size: 8px;
        }

        @media (max-width: 768px) {
            .chart-shell {
                height: 280px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $fmtKes = function (float $v, bool $sign = false): string {
            $a = abs($v);
            $s = $v < 0 ? '-' : ($sign && $v > 0 ? '+' : '');
            if ($a >= 1_000_000_000) {
                return $s . 'KES ' . number_format($a / 1_000_000_000, 2) . 'B';
            }
            if ($a >= 1_000_000) {
                return $s . 'KES ' . number_format($a / 1_000_000, 2) . 'M';
            }
            if ($a >= 1_000) {
                return $s . 'KES ' . number_format($a / 1_000, 2) . 'K';
            }
            return $s . 'KES ' . number_format($a, 2);
        };
    @endphp

    <div class="segment-page">
        <div class="segment-hero">
            <div class="hero-particles" id="segmentHeroParticles"></div>

            <div class="hero-copy">
                <div class="hero-chip">{{ $segment['label'] }} Segment</div>
                <h1 class="hero-title">{{ $segment['label'] }} Dashboard</h1>
                <p class="hero-sub">
                    Focused drilldown into grouped business segment balances and movement for
                    {{ $segment['business_segment_name'] }}.
                </p>
            </div>

            <div class="hero-meta">
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-label">Total Deposit</div>
                        <div class="hero-stat-value">KES {{ number_format($segment['total_deposit'] / 1_000_000_000, 2) }}B
                        </div>
                        <div class="hero-stat-note">Current segment balance</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-label">% of Total</div>
                        <div class="hero-stat-value">{{ number_format($segment['share_pct'], 1) }}%</div>
                        <div class="hero-stat-note">Share of all deposits</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-label">As At Date</div>
                        <div class="hero-stat-value">
                            {{ $asOfDate ? \Carbon\Carbon::parse($asOfDate)->format('d M Y') : '—' }}
                        </div>
                        <div class="hero-stat-note">Latest reporting date</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="segment-shell">
            <a href="{{ route('finance.dashboard') }}" class="back-link">← Back to Finance Dashboard</a>

            @if (!$asOfDate)
                <div class="empty-state">
                    <h4>No sub-segment data available</h4>
                    <p>Make sure <strong>sub_segment_movers</strong> has records for
                        {{ $segment['business_segment_name'] }}.</p>
                </div>
            @else
                <div class="summary-grid">
                    @foreach ($summaryCards as $card)
                        @php
                            $isPlaceholder = !empty($card['is_placeholder']);
                            $isFlat = $isPlaceholder || is_null($card['change_pct']);
                            $valClass = $isFlat ? 'is-flat' : ($card['direction'] === 'up' ? 'is-up' : 'is-down');
                            $arrow = !$isFlat && !$isPlaceholder ? ($card['direction'] === 'up' ? '▲ ' : '▼ ') : '';
                            $badgeCls = $isFlat ? 'flat' : ($card['direction'] === 'up' ? 'up' : 'down');
                        @endphp
                        <div class="summary-card" style="--accent: {{ $card['accent'] }}">
                            <div class="summary-label">{{ $card['label'] }}</div>
                            @if (!empty($card['value_lines']))
                                <div class="summary-value-lines {{ $valClass }}">
                                    @foreach ($card['value_lines'] as $line)
                                        <div class="summary-value-line">{{ $line }}</div>
                                    @endforeach
                                </div>
                            @else
                                <div class="summary-value {{ $valClass }}">{{ $arrow }}{{ $card['value'] }}
                                </div>
                            @endif
                            <div class="summary-foot">
                                <div class="summary-range">{{ $card['range'] }}</div>
                                @if ($isPlaceholder)
                                    <span class="summary-badge flat">PENDING</span>
                                @elseif ($isFlat)
                                    <span class="summary-badge flat">BALANCE</span>
                                @else
                                    <span class="summary-badge {{ $badgeCls }}">
                                        {{ $card['direction'] === 'up' ? '▲' : '▼' }} {{ abs($card['change_pct']) }}%
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="sub-card-grid">
                    @forelse ($subSegmentCards as $row)
                        <div class="sub-card">
                            <h3 class="sub-card-title">{{ $row['label'] }}</h3>
                            <div class="sub-card-value">{{ $fmtKes((float) $row['closing_balance']) }}</div>
                            <div class="sub-card-meta">{{ number_format($row['cif_count']) }} CIF accounts</div>
                            <span class="sub-card-share">{{ number_format($row['share_pct'], 1) }}% of segment</span>
                        </div>
                    @empty
                        <div class="empty-state" style="grid-column:1/-1;">
                            <h4>No grouped business segments found</h4>
                            <p>No grouped business segment rows are available for this segment yet.</p>
                        </div>
                    @endforelse
                </div>

                <div class="panel-card">
                    <div class="panel-header">
                        <div>
                            <h3 class="panel-title">Total Deposits — {{ $segment['label'] }}</h3>
                            <p class="panel-subtitle" id="segmentDepositSub">
                                Closing balances split by the leading grouped business segments.
                            </p>
                        </div>
                        <div class="chart-mode-switch" data-chart="segmentDeposits">
                            <button class="chart-mode-btn active" data-mode="daily">Daily</button>
                            <button class="chart-mode-btn" data-mode="weekly">Weekly</button>
                            <button class="chart-mode-btn" data-mode="monthly">Monthly</button>
                        </div>
                    </div>
                    <div class="chart-shell skeleton">
                        <canvas id="segmentDepositChart"></canvas>
                    </div>
                </div>

                <div class="table-card">
                    <div class="panel-header">
                        <div>
                            <h3 class="panel-title">Grouped Business Segment Movement Table</h3>
                            <p class="panel-subtitle">
                                Current balances and movement performance grouped by business segment name.
                            </p>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="segment-table">
                            <thead>
                                <tr>
                                    <th>Business Segment</th>
                                    <th class="right">CIF Count</th>
                                    <th class="right">Closing Balance</th>
                                    <th class="right">Daily</th>
                                    <th class="right">MTD</th>
                                    <th class="right">YTD</th>
                                    <th class="right">Share %</th>
                                    <th class="right">Drivers</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tableRows as $row)
                                    @php
                                        $dailyClass =
                                            $row['daily_movement'] > 0
                                                ? 'movement-up'
                                                : ($row['daily_movement'] < 0
                                                    ? 'movement-down'
                                                    : 'movement-flat');
                                        $mtdClass =
                                            $row['mtd_movement'] > 0
                                                ? 'movement-up'
                                                : ($row['mtd_movement'] < 0
                                                    ? 'movement-down'
                                                    : 'movement-flat');
                                        $ytdClass =
                                            $row['ytd_movement'] > 0
                                                ? 'movement-up'
                                                : ($row['ytd_movement'] < 0
                                                    ? 'movement-down'
                                                    : 'movement-flat');
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="group-pill">{{ $row['label'] }}</span>
                                        </td>
                                        <td class="right">{{ number_format($row['cif_count']) }}</td>
                                        <td class="right">{{ $fmtKes((float) $row['closing_balance']) }}</td>
                                        <td class="right {{ $dailyClass }}">
                                            {{ $fmtKes((float) $row['daily_movement'], true) }}
                                        </td>
                                        <td class="right {{ $mtdClass }}">
                                            {{ $fmtKes((float) $row['mtd_movement'], true) }}
                                        </td>
                                        <td class="right {{ $ytdClass }}">
                                            {{ $fmtKes((float) $row['ytd_movement'], true) }}
                                        </td>
                                        <td class="right">{{ number_format($row['share_pct'], 1) }}%</td>
                                        <td class="right">
                                            <button type="button" class="drivers-btn js-open-cif-drivers"
                                                data-group-key="{{ $row['group_key'] }}" data-label="{{ $row['label'] }}">
                                                View Drivers
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" style="text-align:center;color:#8A96A8;">
                                            No grouped business segment rows available.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="drivers-modal-backdrop" id="cifDriversModal" style="display:none;">
        <div class="drivers-modal">
            <div class="drivers-modal-head">
                <div>
                    <div class="drivers-kicker">CIF Movement Drivers</div>
                    <h3 id="driversModalTitle">Movement Drivers</h3>
                    <p id="driversModalSub">Loading CIF contributors...</p>
                </div>
                <button type="button" class="drivers-close" id="closeDriversModal">×</button>
            </div>

            <div class="drivers-tabs" style="display:none;"></div>

            <div class="drivers-body">
                <div class="drivers-column">
                    <div class="drivers-section-title gain">Top Gainers</div>
                    <div class="drivers-table-wrap">
                        <table class="drivers-table">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th class="right">Start</th>
                                    <th class="right">End</th>
                                    <th class="right">Movement</th>
                                </tr>
                            </thead>
                            <tbody id="driversGainersBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="drivers-column">
                    <div class="drivers-section-title loss">Top Losers</div>
                    <div class="drivers-table-wrap">
                        <table class="drivers-table">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th class="right">Start</th>
                                    <th class="right">End</th>
                                    <th class="right">Movement</th>
                                </tr>
                            </thead>
                            </thead>
                            <tbody id="driversLosersBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js">
    </script>
    <script src="{{ asset('js/easter-egg.js') }}"></script>
    <script>
        const chartPayload = @json($chartPayload);

        /* ─── Register datalabels before any chart is built ─── */
        if (typeof ChartDataLabels !== 'undefined') {
            Chart.register(ChartDataLabels);
        }

        function seedParticles(id, count = 18) {
            const host = document.getElementById(id);
            if (!host) return;
            host.innerHTML = '';
            for (let i = 0; i < count; i++) {
                const p = document.createElement('span');
                p.className = 'hero-particle' + (i % 4 === 0 ? ' alt' : '');
                p.style.left = Math.random() * 100 + '%';
                p.style.bottom = (-20 - Math.random() * 40) + 'px';
                p.style.animationDuration = (8 + Math.random() * 8) + 's';
                p.style.animationDelay = (Math.random() * 6) + 's';
                p.style.opacity = String(0.15 + Math.random() * 0.55);
                const sz = 4 + Math.random() * 7;
                p.style.width = p.style.height = sz + 'px';
                host.appendChild(p);
            }
        }

        function fKes(v) {
            const a = Math.abs(v),
                s = v < 0 ? '-' : '';
            if (a >= 1e9) return s + 'KES ' + (a / 1e9).toFixed(2) + 'B';
            if (a >= 1e6) return s + 'KES ' + (a / 1e6).toFixed(2) + 'M';
            if (a >= 1e3) return s + 'KES ' + (a / 1e3).toFixed(2) + 'K';
            return s + 'KES ' + a.toFixed(2);
        }

        function fAxis(v) {
            const a = Math.abs(v);
            if (a >= 1e9) return (v / 1e9).toFixed(1) + 'B';
            if (a >= 1e6) return (v / 1e6).toFixed(1) + 'M';
            if (a >= 1e3) return (v / 1e3).toFixed(1) + 'K';
            return Number(v).toFixed(0);
        }

        const tip = {
            backgroundColor: 'rgba(255,255,255,0.98)',
            borderWidth: 1,
            borderColor: 'rgba(0,130,187,0.20)',
            titleColor: '#005B82',
            bodyColor: '#464646',
            padding: 10,
            cornerRadius: 8,
        };

        Chart.defaults.color = '#8A96A8';
        Chart.defaults.font.family = 'Montserrat, Segoe UI, Arial, sans-serif';
        Chart.defaults.font.size = 10;

        function removeSkeleton(canvasId) {
            const shell = document.getElementById(canvasId)?.closest('.chart-shell');
            if (shell) shell.classList.remove('skeleton');
        }

        function withFade(canvasId, fn) {
            const shell = document.getElementById(canvasId)?.closest('.chart-shell');
            shell?.classList.add('updating');
            fn();
            setTimeout(() => shell?.classList.remove('updating'), 170);
        }

        function drawSparkline(canvas, rawData, hexColor) {
            if (!canvas || !rawData || rawData.length < 2) return;
            const dpr = window.devicePixelRatio || 1;
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * dpr;
            canvas.height = rect.height * dpr;
            const ctx = canvas.getContext('2d');
            ctx.scale(dpr, dpr);

            const W = rect.width,
                H = rect.height;
            const data = rawData.slice(-16);
            const min = Math.min(...data),
                max = Math.max(...data);
            const rng = max - min || 1;
            const pts = data.map((v, i) => [
                (i / (data.length - 1)) * W,
                H - ((v - min) / rng) * (H - 6) - 3
            ]);

            const grad = ctx.createLinearGradient(0, 0, 0, H);
            grad.addColorStop(0, hexColor + '55');
            grad.addColorStop(1, hexColor + '00');

            ctx.beginPath();
            pts.forEach(([x, y], i) => i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y));
            ctx.strokeStyle = hexColor;
            ctx.lineWidth = 1.8;
            ctx.lineJoin = 'round';
            ctx.stroke();

            ctx.lineTo(pts[pts.length - 1][0], H);
            ctx.lineTo(0, H);
            ctx.closePath();
            ctx.fillStyle = grad;
            ctx.fill();
        }

        function populateSparklines() {
            const datasets = chartPayload.deposits?.daily?.datasets || [];
            const sparkData = datasets.reduce((acc, ds) => {
                (ds.data || []).forEach((v, i) => {
                    acc[i] = (acc[i] || 0) + v;
                });
                return acc;
            }, []);

            document.querySelectorAll('.summary-card .spark-canvas').forEach(canvas => {
                const color = canvas.dataset.color || '#0082BB';
                drawSparkline(canvas, sparkData, color);
            });
        }

        let segmentDepositChart;

        function buildStackedBar(canvasId) {
            return new Chart(document.getElementById(canvasId).getContext('2d'), {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 10,
                                boxWidth: 7,
                                font: {
                                    size: 10,
                                    weight: '700',
                                    family: 'Montserrat, Segoe UI, Arial, sans-serif'
                                },
                                generateLabels: function(chart) {
                                    return chart.data.datasets.map((dataset, index) => ({
                                        text: dataset.label,
                                        fillStyle: dataset.segmentColor || dataset.backgroundColor,
                                        strokeStyle: dataset.segmentColor || dataset
                                            .backgroundColor,
                                        lineWidth: 0,
                                        hidden: !chart.isDatasetVisible(index),
                                        datasetIndex: index,
                                        pointStyle: 'circle',
                                    }));
                                }
                            }
                        },
                        tooltip: Object.assign({}, tip, {
                            callbacks: {
                                title: function(items) {
                                    const idx = items?.[0]?.dataIndex ?? 0;
                                    const label = items?.[0]?.chart?.data?.labels?.[idx] || '';
                                    return label;
                                },
                                label: ctx => ' ' + ctx.dataset.label + ': ' + fKes(ctx.parsed.y)
                            }
                        }),
                        datalabels: {
                            display: function(ctx) {
                                const v = ctx.dataset.data[ctx.dataIndex];
                                return v != null && Math.abs(v) >= 1e8;
                            },
                            color: '#ffffff',
                            font: {
                                weight: '700',
                                size: 9,
                                family: 'Montserrat, Segoe UI, Arial, sans-serif'
                            },
                            formatter: (value) => fAxis(value),
                            anchor: 'center',
                            align: 'center',
                            clamp: true,
                            textShadowBlur: 4,
                            textShadowColor: 'rgba(0,0,0,0.35)',
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 9,
                                    weight: '600'
                                },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 14
                            }
                        },
                        y: {
                            stacked: true,
                            grid: {
                                color: 'rgba(0,0,0,0.045)'
                            },
                            ticks: {
                                font: {
                                    size: 9
                                },
                                callback: v => fAxis(v)
                            }
                        }
                    }
                }
            });
        }

        function updateSegmentDeposits(mode) {
            withFade('segmentDepositChart', () => {
                const p = chartPayload.deposits?.[mode] || {
                    labels: [],
                    datasets: []
                };

                const barConfig = {
                    daily: {
                        maxBarThickness: 28,
                        categoryPercentage: 0.72,
                        barPercentage: 0.82,
                        maxTicksLimit: 14
                    },
                    weekly: {
                        maxBarThickness: 44,
                        categoryPercentage: 0.86,
                        barPercentage: 0.92,
                        maxTicksLimit: 13
                    },
                    monthly: {
                        maxBarThickness: 56,
                        categoryPercentage: 0.92,
                        barPercentage: 0.95,
                        maxTicksLimit: 13
                    }
                } [mode] || {
                    maxBarThickness: 32,
                    categoryPercentage: 0.78,
                    barPercentage: 0.86,
                    maxTicksLimit: 14
                };

                segmentDepositChart.data.labels = p.labels || [];

                segmentDepositChart.data.datasets = (p.datasets || []).map(ds => ({
                    label: ds.label,
                    data: ds.data,
                    segmentColor: ds.color,
                    backgroundColor: ds.color,
                    borderColor: ds.color,
                    borderWidth: 0,
                    borderRadius: 5,
                    borderSkipped: false,
                    maxBarThickness: barConfig.maxBarThickness,
                    categoryPercentage: barConfig.categoryPercentage,
                    barPercentage: barConfig.barPercentage,
                }));

                segmentDepositChart.options.scales.x.ticks.maxTicksLimit = barConfig.maxTicksLimit;
                segmentDepositChart.options.scales.x.ticks.autoSkip = mode === 'daily';

                segmentDepositChart.update();
            });

            document.getElementById('segmentDepositSub').textContent = {
                daily: 'Daily closing balances split by the leading grouped business segments.',
                weekly: 'Weekly closing balances split by the leading grouped business segments.',
                monthly: 'Month-end balances split by the leading grouped business segments.',
            } [mode];
        }

        document.addEventListener('DOMContentLoaded', function() {
            seedParticles('segmentHeroParticles', 22);

            segmentDepositChart = buildStackedBar('segmentDepositChart');
            removeSkeleton('segmentDepositChart');
            updateSegmentDeposits('daily');

            populateSparklines();

            document.querySelectorAll('.chart-mode-switch').forEach(switchEl => {
                switchEl.querySelectorAll('.chart-mode-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        switchEl.querySelectorAll('.chart-mode-btn').forEach(b => b
                            .classList.remove('active'));
                        btn.classList.add('active');
                        updateSegmentDeposits(btn.getAttribute('data-mode'));
                    });
                });
            });

            let activeDriverGroupKey = null;
            let activeDriverLabel = null;
            let activeDriverPeriod = 'daily';

            const driversModal = document.getElementById('cifDriversModal');
            const driversModalTitle = document.getElementById('driversModalTitle');
            const driversModalSub = document.getElementById('driversModalSub');
            const driversGainersBody = document.getElementById('driversGainersBody');
            const driversLosersBody = document.getElementById('driversLosersBody');

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function renderDriverRows(target, rows, type) {
                if (!target) return;

                if (!rows || rows.length === 0) {
                    target.innerHTML =
                        `<tr><td colspan="4" class="drivers-empty">No ${type} found for this period.</td></tr>`;
                    return;
                }

                target.innerHTML = rows.map(row => {
                    const movementClass = Number(row.movement) >= 0 ? 'movement-up' : 'movement-down';
                    const customerName = row.customer_name || row.account_name || row
                        .business_segment_name || 'N/A';

                    return `
            <tr>
                <td>${escapeHtml(customerName)}</td>
                <td class="right">${fKes(Number(row.start_balance || 0))}</td>
                <td class="right">${fKes(Number(row.end_balance || 0))}</td>
                <td class="right ${movementClass}">${fKes(Number(row.movement || 0))}</td>
            </tr>
        `;
                }).join('');
            }

            function setDriversLoading() {
                if (driversGainersBody) {
                    driversGainersBody.innerHTML =
                        `<tr><td colspan="4" class="drivers-loading">Loading top 5 gainers...</td></tr>`;
                }

                if (driversLosersBody) {
                    driversLosersBody.innerHTML =
                        `<tr><td colspan="4" class="drivers-loading">Loading top 5 losers...</td></tr>`;
                }
            }

            function loadCifDrivers() {
                if (!activeDriverGroupKey || !driversModal) return;

                setDriversLoading();

                const url = new URL(`{{ route('finance.segment.cif-drivers', ['segment' => $segment['slug']]) }}`,
                    window.location.origin);
                url.searchParams.set('group_key', activeDriverGroupKey);
                url.searchParams.set('period', activeDriverPeriod);
                url.searchParams.set('limit', '5');

                fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        driversModalTitle.textContent = `${data.group_label || activeDriverLabel} Drivers`;
                        driversModalSub.textContent =
                            `${String(activeDriverPeriod).toUpperCase()} movement: ${fKes(Number(data.movement || 0))} | ${data.start_date || '-'} to ${data.end_date || '-'}`;

                        renderDriverRows(driversGainersBody, data.gainers || [], 'gainers');
                        renderDriverRows(driversLosersBody, data.losers || [], 'losers');
                    })
                    .catch(() => {
                        driversModalSub.textContent = 'Unable to load CIF movement drivers.';
                        driversGainersBody.innerHTML =
                            `<tr><td colspan="5" class="drivers-empty">Failed to load gainers.</td></tr>`;
                        driversLosersBody.innerHTML =
                            `<tr><td colspan="5" class="drivers-empty">Failed to load losers.</td></tr>`;
                    });
            }

            document.querySelectorAll('.js-open-cif-drivers').forEach(btn => {
                btn.addEventListener('click', function() {
                    activeDriverGroupKey = this.dataset.groupKey;
                    activeDriverLabel = this.dataset.label;
                    activeDriverPeriod = 'daily';



                    driversModalTitle.textContent = `${activeDriverLabel} Drivers`;
                    driversModalSub.textContent = 'Loading CIF movement contributors...';
                    driversModal.style.display = 'block';

                    loadCifDrivers();
                });
            });



            document.getElementById('closeDriversModal')?.addEventListener('click', function() {
                driversModal.style.display = 'none';
            });

            driversModal?.addEventListener('click', function(e) {
                if (e.target === driversModal) {
                    driversModal.style.display = 'none';
                }
            });

            window.addEventListener('resize', () => {
                segmentDepositChart?.resize();
                populateSparklines();
            });
        });
    </script>
@endpush
