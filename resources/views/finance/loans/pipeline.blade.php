@extends('layouts.finance.template')

@section('title', 'Loan Book Pipeline')

@push('styles')
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --navy: #0f1e38;
            --navy2: #1a3254;
            --teal: #0d9488;
            --amber: #d97706;
            --red: #dc2626;
            --green: #16a34a;
            --slate: #64748b;
            --border: #e2e8f0;
            --bg: #f6f8fb;
            --white: #ffffff;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(15,30,56,.07), 0 4px 16px rgba(15,30,56,.06);
        }
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); }

        .pipe-wrap { max-width: 820px; margin: 0 auto; padding: 2rem 1.25rem 4rem; }

        .page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .page-header h1 { font-size: 1.4rem; font-weight: 600; color: var(--navy); margin: 0 0 .25rem; }
        .page-header p  { font-size: .875rem; color: var(--slate); margin: 0; }

        .card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.25rem; box-shadow: var(--shadow); }
        .card-title { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--slate); margin: 0 0 1.25rem; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media(max-width:580px) { .form-row { grid-template-columns: 1fr; } }

        .field { display: flex; flex-direction: column; gap: .3rem; }
        .field label { font-size: .825rem; font-weight: 500; color: var(--navy); }
        .hint { font-weight: 400; color: #94a3b8; font-size: .73rem; display: block; margin-top: .1rem; line-height: 1.4; }

        .field input[type=text],
        .field input[type=date],
        .field input[type=file] {
            border: 1.5px solid var(--border); border-radius: 8px; padding: .55rem .8rem;
            font-size: .875rem; font-family: inherit; color: var(--navy); background: #fafbfd;
            transition: border-color .15s, box-shadow .15s; width: 100%;
        }
        .field input:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(13,148,136,.12); background: #fff; }
        .field input[type=file] { padding: .4rem .8rem; cursor: pointer; }

        .toggle-row { display: flex; align-items: center; gap: .85rem; padding: .85rem 1rem; background: #f8fafc; border-radius: 8px; border: 1.5px solid var(--border); cursor: pointer; user-select: none; transition: background .15s; }
        .toggle-row:hover { background: #f1f5f9; }
        .sw-wrap { position: relative; width: 40px; height: 23px; flex-shrink: 0; }
        .sw-wrap input { opacity: 0; width: 0; height: 0; position: absolute; }
        .sw-track { position: absolute; inset: 0; background: #cbd5e1; border-radius: 999px; transition: background .2s; pointer-events: none; }
        .sw-thumb { position: absolute; top: 3px; left: 3px; width: 17px; height: 17px; background: #fff; border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); pointer-events: none; }
        .sw-wrap input:checked ~ .sw-track { background: var(--teal); }
        .sw-wrap input:checked ~ .sw-thumb { transform: translateX(17px); }
        .toggle-text strong { font-size: .875rem; color: var(--navy); font-weight: 500; display: block; }
        .toggle-text span { font-size: .775rem; color: var(--slate); }

        .accordion-btn { display: flex; align-items: center; justify-content: space-between; width: 100%; background: none; border: none; cursor: pointer; padding: 0; font-family: inherit; }
        .acc-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; color: var(--slate); }
        .acc-chevron { width: 18px; height: 18px; color: var(--slate); transition: transform .25s; flex-shrink: 0; }
        .acc-chevron.open { transform: rotate(180deg); }
        .accordion-body { display: none; margin-top: 1.25rem; }
        .accordion-body.open { display: block; }
        .email-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media(max-width:580px) { .email-grid { grid-template-columns: 1fr; } }

        .run-row { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-top: .25rem; }
        .btn-run { display: inline-flex; align-items: center; gap: .6rem; padding: .75rem 2rem; background: var(--navy); color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: .9rem; font-family: inherit; cursor: pointer; transition: background .15s, transform .1s; letter-spacing: -.1px; }
        .btn-run:hover:not(:disabled) { background: var(--navy2); transform: translateY(-1px); }
        .btn-run:disabled { background: #94a3b8; cursor: not-allowed; }
        .run-note { font-size: .8rem; color: var(--slate); }

        .result-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.25rem; box-shadow: var(--shadow); }
        .result-card.ok { background: #f0fdf4; border: 1px solid #86efac; }
        .result-card.fail { background: #fef2f2; border: 1px solid #fca5a5; }
        .result-head { display: flex; align-items: center; gap: .8rem; margin-bottom: .5rem; }
        .result-icon { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem; }
        .result-icon.ok { background: #dcfce7; color: var(--green); }
        .result-icon.fail { background: #fee2e2; color: var(--red); }
        .result-title { font-size: .95rem; font-weight: 600; color: var(--navy); }
        .result-meta { font-size: .8rem; color: var(--slate); margin-top: .15rem; }

        .alert-err { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: .8rem 1rem; margin-bottom: 1.25rem; color: var(--red); font-size: .85rem; }
        .alert-err ul { margin: .4rem 0 0 1rem; padding: 0; }

        .divider { border: none; border-top: 1px solid var(--border); margin: 1rem 0; }

        .log-wrap { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; box-shadow: var(--shadow); margin-top: 1.5rem; }
        .log-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: .75rem; }
        .log-head h3 { font-size: .875rem; font-weight: 600; color: var(--navy); margin: 0; }
        .log-head span { font-size: .72rem; color: var(--slate); }
        .log-tail { background: #1e293b; color: #64748b; font-family: 'DM Mono', monospace; font-size: .72rem; line-height: 1.55; padding: .75rem 1rem; border-radius: 8px; max-height: 220px; overflow-y: auto; white-space: pre-wrap; word-break: break-all; }

        .filename-preview { font-family: 'DM Mono', monospace; font-size: .78rem; color: var(--teal); background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 6px; padding: .3rem .6rem; display: none; margin-top: .4rem; }
        .filename-preview.show { display: block; }

        /* ── Modals (upload / send) ── */
        .modal-content { border-radius: var(--radius); border: 1px solid var(--border); font-family: 'DM Sans', sans-serif; }
        .modal-header, .modal-footer { border-color: var(--border); }
        .modal-title { font-size: 1.05rem; font-weight: 600; color: var(--navy); }
        .btn-close:focus { box-shadow: none; }
        #sendLoanEmailModal .modal-body { max-height: 65vh; overflow-y: auto; }
        .btn-run:disabled { background: #94a3b8; cursor: not-allowed; }
    </style>

    {{-- ═══════════ LOAN BOOK DASHBOARD — own namespace, doesn't touch --navy/--teal/.pipe-wrap above ═══════════ --}}
    <style>
        .loan-dash {
            --lb-blue: #0082BB;
            --lb-blue-dark: #005B82;
            --lb-blue-mid: #008FC7;
            --lb-green: #10B981;
            --lb-amber: #F59E0B;
            --lb-red: #EF4444;
            --lb-text: #464646;
            --lb-muted: #8A96A8;
            --lb-border: #D9E2EC;
            --lb-bg: #EEF2F6;
            --lb-panel: #FFFFFF;
            --lb-shadow: 0 2px 8px rgba(16, 24, 40, 0.06);
            --lb-hover: 0 12px 28px rgba(16, 24, 40, 0.12);
            min-height: 0;
            background: linear-gradient(180deg, #EEF2F6 0%, #F8FAFC 100%);
            color: var(--lb-text);
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
        }
        .loan-dash * { box-sizing: border-box; }

        .loan-dash .spark-canvas { display: block; width: 100%; height: 36px; margin-top: 8px; border-radius: 4px; opacity: .72; }

        @keyframes lbShimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        .loan-dash .chart-shell.skeleton::after {
            content: ''; position: absolute; inset: 0; border-radius: 8px;
            background: linear-gradient(90deg, #EEF2F6 25%, #DDE6EF 50%, #EEF2F6 75%);
            background-size: 200% 100%; animation: lbShimmer 1.4s ease-in-out infinite; z-index: 2;
        }
        .loan-dash .chart-shell canvas { transition: opacity .15s ease; width: 100% !important; height: 100% !important; }
        .loan-dash .chart-shell.updating canvas { opacity: .3; }

        .loan-dash .segment-delta { margin-top: 6px; }
        .loan-dash .segment-delta-inner { display: inline-flex; align-items: center; gap: 3px; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px; }
        .loan-dash .delta-up { background: rgba(16, 185, 129, .12); color: #065f46; }
        .loan-dash .delta-down { background: rgba(220, 38, 38, .10); color: #991b1b; }
        .loan-dash .delta-flat { background: rgba(0, 130, 187, .08); color: var(--lb-muted); }

        .loan-dash-shell { max-width: 1400px; margin: 0 auto; padding: 16px clamp(12px, 1.3vw, 24px) 22px; }

        .loan-dash .dash-banner {
            position: relative; overflow: hidden; padding: 22px 24px; display: grid;
            grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: center;
            background: radial-gradient(circle at top right, rgba(190, 214, 0, .16), transparent 28%),
                linear-gradient(135deg, #004C6D 0%, #005B82 32%, #0082BB 70%, #00A4D6 100%);
            border-radius: 18px 18px 0 0;
        }
        .loan-dash .banner-particles { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
        .loan-dash .banner-particle { position: absolute; display: block; width: 7px; height: 7px; border-radius: 999px; background: rgba(255,255,255,.28); animation: lbFloatParticle linear infinite; }
        .loan-dash .banner-particle.alt { background: rgba(190, 214, 0, .25); }
        @keyframes lbFloatParticle {
            0% { transform: translate3d(0, 22px, 0) scale(.8); opacity: 0; }
            20% { opacity: .9; }
            100% { transform: translate3d(0, -140px, 0) scale(1.25); opacity: 0; }
        }
        .loan-dash .banner-copy { position: relative; z-index: 1; }
        .loan-dash .banner-title { margin: 0 0 6px; font-size: clamp(20px, 1.7vw, 26px); font-weight: 800; color: #fff; letter-spacing: -.03em; }
        .loan-dash .banner-sub { margin: 0; max-width: 760px; font-size: 11px; line-height: 1.5; color: rgba(255,255,255,.82); }
        .loan-dash .banner-meta { position: relative; z-index: 1; min-width: 190px; padding: 12px 15px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.16); backdrop-filter: blur(8px); }
        .loan-dash .banner-meta-label { font-size: 9px; text-transform: uppercase; letter-spacing: .12em; color: rgba(255,255,255,.7); margin-bottom: 6px; }
        .loan-dash .banner-meta-value { font-size: 16px; font-weight: 800; color: #fff; }
        .loan-dash .banner-meta-note { margin-top: 4px; font-size: 10px; color: rgba(255,255,255,.62); }

        .loan-dash .summary-grid, .loan-dash .segment-grid, .loan-dash .mix-grid { display: grid; gap: 10px; }
        .loan-dash .summary-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin: 14px 0; }
        .loan-dash .segment-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); margin-bottom: 14px; }
        .loan-dash .mix-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); margin-bottom: 14px; }

        .loan-dash .panel-card, .loan-dash .summary-card, .loan-dash .segment-card, .loan-dash .mix-card {
            background: var(--lb-panel); border: 1px solid var(--lb-border); border-radius: 14px; box-shadow: var(--lb-shadow);
        }
        .loan-dash .summary-card { padding: 12px 13px; border-left: 4px solid var(--accent, var(--lb-blue)); transition: .2s ease; }
        .loan-dash .summary-card:hover, .loan-dash .segment-card:hover, .loan-dash .mix-card:hover { box-shadow: var(--lb-hover); transform: translateY(-1px); }

        .loan-dash .summary-label, .loan-dash .segment-chip, .loan-dash .mix-title { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--lb-muted); }
        .loan-dash .summary-value { margin: 5px 0; font-size: 18px; font-weight: 800; line-height: 1.1; white-space: pre-line; }
        .loan-dash .summary-value.is-up { color: #059669; }
        .loan-dash .summary-value.is-down { color: #DC2626; }
        .loan-dash .summary-value.is-flat { color: var(--lb-blue-dark); }
        .loan-dash .summary-foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .loan-dash .summary-range { font-size: 10px; color: var(--lb-muted); }
        .loan-dash .summary-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 999px; font-size: 9px; font-weight: 700; }
        .loan-dash .summary-badge.up { background: rgba(16,185,129,.14); color: #065f46; }
        .loan-dash .summary-badge.down { background: rgba(220,38,38,.10); color: #991b1b; }
        .loan-dash .summary-badge.flat { background: rgba(0,130,187,.10); color: var(--lb-blue-dark); }

        .loan-dash .tab-strip { display: flex; gap: 6px; padding: 5px; margin-bottom: 12px; overflow-x: auto; border-radius: 14px; border: 1px solid var(--lb-border); background: rgba(255,255,255,.92); backdrop-filter: blur(8px); }
        .loan-dash .tab-btn { border: 0; background: transparent; color: var(--lb-muted); border-radius: 10px; padding: 9px 15px; min-width: 150px; font-weight: 700; font-size: 11px; cursor: pointer; transition: .2s ease; }
        .loan-dash .tab-btn.active { background: linear-gradient(135deg, #005B82, #0082BB); color: #fff; box-shadow: 0 8px 16px rgba(0,130,187,.22); }
        .loan-dash .tab-pane { display: none; }
        .loan-dash .tab-pane.active { display: block; }

        .loan-dash .panel-card { padding: 14px; margin-bottom: 12px; }
        .loan-dash .panel-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 10px; flex-wrap: wrap; }
        .loan-dash .panel-title, .loan-dash .mix-headline { margin: 0; font-size: 13px; font-weight: 800; color: var(--lb-blue-dark); }
        .loan-dash .panel-subtitle, .loan-dash .segment-meta, .loan-dash .mix-subtitle { font-size: 10px; color: var(--lb-muted); }

        .loan-dash .chart-shell { position: relative; width: 100%; height: 280px; }
        .loan-dash .chart-shell.tall { height: 315px; }
        .loan-dash .chart-shell.medium { height: 235px; }

        .loan-dash .chart-mode-switch { display: inline-flex; gap: 2px; padding: 2px; border-radius: 10px; background: #F1F5F9; border: 1px solid var(--lb-border); }
        .loan-dash .chart-mode-btn { border: 0; border-radius: 7px; padding: 5px 9px; font-size: 9px; font-weight: 700; color: var(--lb-muted); background: transparent; cursor: pointer; }
        .loan-dash .chart-mode-btn.active { background: var(--lb-blue); color: #fff; }

        .loan-dash .segment-card { display: block; padding: 12px; border-left: 4px solid var(--seg-accent, var(--lb-blue)); transition: .2s ease; min-height: 100%; }
        .loan-dash .segment-card-top { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-bottom: 8px; }
        .loan-dash .segment-title { margin: 0; font-size: 13px; font-weight: 800; color: #163046; }
        .loan-dash .segment-balance { font-size: 18px; font-weight: 800; color: var(--lb-blue-dark); margin-bottom: 4px; }

        .loan-dash .mix-card { padding: 12px; }
        .loan-dash .mix-card .chart-shell { height: 215px; margin-top: 8px; }

        .loan-dash .empty-state { border-radius: 16px; padding: 40px 24px; text-align: center; color: var(--lb-muted); background: #fff; border: 1px dashed var(--lb-border); margin-bottom: 24px; }
        .loan-dash .empty-state h4 { margin: 0 0 8px; color: var(--lb-blue-dark); }

        .loan-dash .movers-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 900px) { .loan-dash .movers-grid { grid-template-columns: 1fr; } }
        .loan-dash .movers-table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        .loan-dash .movers-table th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .06em; color: var(--lb-muted); padding: 6px 8px; border-bottom: 1px solid var(--lb-border); }
        .loan-dash .movers-table td { padding: 6px 8px; border-bottom: 1px solid #F1F5F9; }
        .loan-dash .movers-table td.num { text-align: right; font-family: 'Courier New', ui-monospace, monospace; white-space: nowrap; }
        .loan-dash .movers-table tr:last-child td { border-bottom: 0; }

        @media (max-width: 1280px) {
            .loan-dash .segment-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .loan-dash .mix-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .loan-dash .dash-banner { grid-template-columns: 1fr; }
            .loan-dash .banner-meta { display: none; }
            .loan-dash-shell { padding: 12px; }
            .loan-dash .summary-grid, .loan-dash .segment-grid, .loan-dash .mix-grid { grid-template-columns: 1fr; }
            .loan-dash .chart-shell { height: 240px; }
            .loan-dash .chart-shell.tall { height: 270px; }
        }
    </style>
@endpush

@section('content')

    {{-- ═══════════════════════════ LOAN BOOK DASHBOARD ═══════════════════════════ --}}
    <div class="loan-dash">
        @php $d = $dashboard ?? ['asOfDate' => null]; @endphp

        @if (empty($d['asOfDate']))
            <div class="loan-dash-shell">
                <div class="empty-state">
                    <h4>No loan book data available</h4>
                    <p>Upload a Loan Book Excel file below to populate the dashboard.</p>
                </div>
            </div>
        @else
            <div class="dash-banner">
                <div class="banner-particles" id="loanDashParticles"></div>
                <div class="banner-copy">
                    <h1 class="banner-title">Loan Book Dashboard</h1>
                    <p class="banner-sub">Performing vs bank-wide totals, business segment breakdown, asset quality mix, and movement trend across the loan book.</p>
                </div>
                <div class="banner-meta">
                    <div class="banner-meta-label">As At Date</div>
                    <div class="banner-meta-value">{{ \Carbon\Carbon::parse($d['asOfDate'])->format('d M Y') }}</div>
                    <div class="banner-meta-note">Latest imported snapshot</div>
                </div>
            </div>

            <div class="loan-dash-shell">

                {{-- Summary cards --}}
                <div class="summary-grid">
                    @foreach ($d['summaryCards'] as $card)
                        @php
                            $isPlaceholder = !empty($card['is_placeholder']);
                            $hasBadge = !empty($card['badge']);
                            $isFlat = $isPlaceholder || is_null($card['change_pct']);
                            $valClass = $isFlat ? 'is-flat' : ($card['direction'] === 'up' ? 'is-up' : 'is-down');
                            $arrow = !$isFlat && !$isPlaceholder ? ($card['direction'] === 'up' ? '▲ ' : '▼ ') : '';
                            $badgeCls = $isFlat ? 'flat' : ($card['direction'] === 'up' ? 'up' : 'down');
                        @endphp
                        <div class="summary-card" style="--accent: {{ $card['accent'] }}">
                            <div class="summary-label">{{ $card['label'] }}</div>
                            <div class="summary-value {{ $valClass }}">{{ $arrow }}{{ $card['value'] }}</div>
                            <div class="summary-foot">
                                <div class="summary-range">{{ $card['range'] }}</div>
                                @if ($isPlaceholder)
                                    <span class="summary-badge flat">PENDING</span>
                                @elseif ($hasBadge)
                                    <span class="summary-badge flat">{{ $card['badge'] }}</span>
                                @elseif ($isFlat)
                                    <span class="summary-badge flat">BALANCE</span>
                                @else
                                    <span class="summary-badge {{ $badgeCls }}">
                                        {{ $card['direction'] === 'up' ? '▲' : '▼' }} {{ abs($card['change_pct']) }}%
                                    </span>
                                @endif
                            </div>
                            <canvas class="spark-canvas" data-color="{{ $card['accent'] }}"></canvas>
                        </div>
                    @endforeach
                </div>

                {{-- Tabs --}}
                <div class="tab-strip">
                    <button class="tab-btn active" data-tab="loanbook">Loan Book</button>
                    <button class="tab-btn" data-tab="lbmovement">Movement</button>
                </div>

                {{-- TAB: LOAN BOOK --}}
                <div class="tab-pane active" id="tab-loanbook">

                    @php
                        $segmentCards = [
                            ['label' => 'Corporate', 'key' => 'Corporate', 'color' => '#005B82'],
                            ['label' => 'Commercial', 'key' => 'Commercial', 'color' => '#008FC7'],
                            ['label' => 'Consumer', 'key' => 'Consumer', 'color' => '#10B981'],
                        ];
                        $segmentPie = $d['chartPayload']['segmentPie'] ?? ['labels' => [], 'data' => [], 'colors' => []];
                        $segmentPieMap = collect($segmentPie['labels'] ?? [])->mapWithKeys(
                            fn($label, $idx) => [
                                $label => [
                                    'value' => $segmentPie['data'][$idx] ?? 0,
                                    'color' => $segmentPie['colors'][$idx] ?? '#0082BB',
                                ],
                            ],
                        );
                        $pieTotal = collect($segmentPie['data'] ?? [])->sum();
                    @endphp

                    <div class="segment-grid">
                        @foreach ($segmentCards as $segmentCard)
                            @php
                                $meta = $segmentPieMap[$segmentCard['label']] ?? ['value' => 0, 'color' => $segmentCard['color']];
                                $value = (float) $meta['value'];
                                $pct = $pieTotal > 0 ? round(($value / $pieTotal) * 100, 1) : 0;
                            @endphp
                            <div class="segment-card" style="--seg-accent: {{ $meta['color'] }};" data-segment-label="{{ $segmentCard['label'] }}">
                                <div class="segment-card-top">
                                    <div>
                                        <div class="segment-chip">{{ $segmentCard['label'] }}</div>
                                        <h3 class="segment-title">{{ $segmentCard['label'] }} Loan Book</h3>
                                    </div>
                                </div>
                                <div class="segment-balance">KES {{ number_format($value / 1_000_000_000, 2) }}B</div>
                                <div class="segment-delta">
                                    <span class="segment-delta-inner delta-flat js-seg-delta">—</span>
                                </div>
                                <div class="segment-meta" style="margin-top:6px;">{{ $pct }}% of performing loan book</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="panel-card">
                        <div class="panel-header">
                            <div>
                                <h3 class="panel-title">Loan Book — Overall</h3>
                                <p class="panel-subtitle" id="lbOverallSub">Performing closing balances split by business segment.</p>
                            </div>
                            <div class="chart-mode-switch" data-chart="lbOverall">
                                <button class="chart-mode-btn active" data-mode="daily">Daily</button>
                                <button class="chart-mode-btn" data-mode="weekly">Weekly</button>
                                <button class="chart-mode-btn" data-mode="monthly">Monthly</button>
                            </div>
                        </div>
                        <div class="chart-shell tall skeleton">
                            <canvas id="lbOverallChart"></canvas>
                        </div>
                    </div>

                    <div class="panel-card">
                        <div class="panel-header">
                            <div>
                                <h3 class="panel-title">Mix Overview</h3>
                                <p class="panel-subtitle">Business segment, currency, and asset-quality mix.</p>
                            </div>
                        </div>
                        <div class="mix-grid">
                            <div class="mix-card">
                                <div class="mix-title">Business Segments</div>
                                <h4 class="mix-headline">Contribution Mix</h4>
                                <div class="mix-subtitle">Performing balance share by business segment.</div>
                                <div class="chart-shell medium skeleton"><canvas id="lbSegmentPieChart"></canvas></div>
                            </div>
                            <div class="mix-card">
                                <div class="mix-title">Currency</div>
                                <h4 class="mix-headline">Currency Mix</h4>
                                <div class="mix-subtitle">LCY vs FCY, KES equivalent.</div>
                                <div class="chart-shell medium skeleton"><canvas id="lbCurrencyMixPieChart"></canvas></div>
                            </div>
                            <div class="mix-card">
                                <div class="mix-title">Asset Quality</div>
                                <h4 class="mix-headline">NPL / Status Mix</h4>
                                <div class="mix-subtitle">Performing, Watch, Substandard, Doubtful, Loss.</div>
                                <div class="chart-shell medium skeleton"><canvas id="lbStatusPieChart"></canvas></div>
                            </div>
                        </div>
                    </div>

                </div>{{-- /tab-loanbook --}}

                {{-- TAB: MOVEMENT --}}
                <div class="tab-pane" id="tab-lbmovement">

                    <div class="panel-card">
                        <div class="panel-header">
                            <div>
                                <h3 class="panel-title">Overall Movement Trend</h3>
                                <p class="panel-subtitle" id="lbMovementSub">Net performing loan book movement between comparison points.</p>
                            </div>
                            <div class="chart-mode-switch" data-chart="lbMovement">
                                <button class="chart-mode-btn active" data-mode="daily">Daily</button>
                                <button class="chart-mode-btn" data-mode="weekly">Weekly</button>
                                <button class="chart-mode-btn" data-mode="monthly">Monthly</button>
                            </div>
                        </div>
                        <div class="chart-shell tall skeleton">
                            <canvas id="lbMovementChart"></canvas>
                        </div>
                    </div>

                    <div class="panel-card">
                        <div class="panel-header">
                            <div>
                                <h3 class="panel-title">Segment Movement Trend</h3>
                                <p class="panel-subtitle">Corporate, Commercial and Consumer Banking.</p>
                            </div>
                            <div class="chart-mode-switch" data-chart="lbSegment">
                                <button class="chart-mode-btn active" data-mode="daily">Daily</button>
                                <button class="chart-mode-btn" data-mode="weekly">Weekly</button>
                                <button class="chart-mode-btn" data-mode="monthly">Monthly</button>
                            </div>
                        </div>
                        <div class="chart-shell tall skeleton">
                            <canvas id="lbSegmentChart"></canvas>
                        </div>
                    </div>

                    <div class="panel-card">
                        <div class="panel-header">
                            <div>
                                <h3 class="panel-title">MTD vs YTD by Segment</h3>
                                <p class="panel-subtitle">Grouped bar comparison across business segments.</p>
                            </div>
                        </div>
                        <div class="chart-shell medium skeleton">
                            <canvas id="lbMtdYtdChart"></canvas>
                        </div>
                    </div>

                    <div class="panel-card">
                        <div class="panel-header">
                            <div>
                                <h3 class="panel-title">Top CIF Movers</h3>
                                <p class="panel-subtitle">Largest day-on-day increases and decreases, KES equivalent.</p>
                            </div>
                        </div>
                        <div class="movers-grid">
                            <div>
                                <table class="movers-table">
                                    <thead><tr><th>Gainers</th><th style="text-align:right;">Movement</th></tr></thead>
                                    <tbody>
                                        @forelse (($d['topMovers']['gainers'] ?? []) as $g)
                                            @php $g = (array) $g; @endphp
                                            <tr>
                                                <td>{{ $g['name'] ?? ($g['cif'] ?? '—') }}</td>
                                                <td class="num" style="color:#059669;">+{{ number_format((float)($g['movement'] ?? 0)) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" style="color:var(--lb-muted);">No gainers for this period.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div>
                                <table class="movers-table">
                                    <thead><tr><th>Losers</th><th style="text-align:right;">Movement</th></tr></thead>
                                    <tbody>
                                        @forelse (($d['topMovers']['losers'] ?? []) as $l)
                                            @php $l = (array) $l; @endphp
                                            <tr>
                                                <td>{{ $l['name'] ?? ($l['cif'] ?? '—') }}</td>
                                                <td class="num" style="color:#DC2626;">{{ number_format((float)($l['movement'] ?? 0)) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" style="color:var(--lb-muted);">No losers for this period.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>{{-- /tab-lbmovement --}}

            </div>{{-- /loan-dash-shell --}}
        @endif
    </div>
    {{-- ═══════════════════════════ END LOAN BOOK DASHBOARD ═══════════════════════════ --}}

    <div class="pipe-wrap">

        {{-- Header --}}
        <div class="page-header">
            <div>
                <h1><i class="fa-solid fa-money-bill-trend-up" style="color:#0d9488;margin-right:.4rem;"></i>Loan Book Pipeline</h1>
                <p>Upload the daily Loan Book Excel file to import data and send the movement report.</p>
            </div>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="alert-err">
                <strong>Please fix the following:</strong>
                <ul>
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Success banner --}}
        @if (session('success'))
            <div class="result-card ok">
                <div class="result-head">
                    <div class="result-icon ok"><i class="fa-solid fa-check"></i></div>
                    <div>
                        <div class="result-title">{{ session('success') }}</div>
                        @if (session('importedDate'))
                            <div class="result-meta">Date: <strong>{{ session('importedDate') }}</strong></div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- ═══════════ ACTIONS ═══════════ --}}
        <div class="run-row" style="margin-bottom:1.5rem;">
            <button type="button" class="btn-run" data-bs-toggle="modal" data-bs-target="#uploadLoanModal">
                <i class="fa-solid fa-upload"></i>
                <span>Upload Loan Book</span>
            </button>
            <button type="button" class="btn-run" style="background:var(--teal);" data-bs-toggle="modal" data-bs-target="#sendLoanEmailModal">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Send Report Only</span>
            </button>
            <span class="run-note">Upload to import a new date, or send the report for data already loaded.</span>
        </div>

        {{-- Log --}}
        @if (!empty($logLines))
            <div class="log-wrap">
                <div class="log-head">
                    <h3>Activity Log</h3>
                    <span>Last 200 entries</span>
                </div>
                <div class="log-tail" id="logTail">{{ implode("\n", $logLines) }}</div>
            </div>
        @endif

    </div>

    {{-- ═══════════ UPLOAD MODAL ═══════════ --}}
    <div class="modal fade" id="uploadLoanModal" tabindex="-1" aria-labelledby="uploadLoanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('finance.loans.pipeline.upload') }}" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <input type="hidden" name="form_name" value="upload">

                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadLoanModalLabel">Upload Loan Book</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="form-row">
                            <div class="field">
                                <label>
                                    Loan Book Excel (.xlsx) <span style="color:#ef4444">*</span>
                                    <span class="hint">Must contain a sheet named "Loan Book"</span>
                                </label>
                                <input type="file" name="loan_file" id="loan_file" accept=".xlsx,.xls" required
                                       onchange="handleFileChange(this)">
                                <div class="filename-preview" id="filePreview"></div>
                            </div>

                            <div class="field">
                                <label>
                                    Report date
                                    <span class="hint">Auto-detected from filename. Override if needed.</span>
                                </label>
                                <input type="date" name="loan_date" id="loan_date" value="{{ old('loan_date', $defaultDate) }}">
                            </div>
                        </div>

                        <div style="margin-top:1rem;">
                            <label class="toggle-row" for="send_email">
                                <div class="sw-wrap">
                                    <input type="hidden" name="send_email" value="0">
                                    <input type="checkbox" id="send_email" name="send_email" value="1"
                                        {{ old('send_email') ? 'checked' : '' }}
                                        onchange="toggleEmailSection(this)">
                                    <div class="sw-track"></div>
                                    <div class="sw-thumb"></div>
                                </div>
                                <div class="toggle-text">
                                    <strong>Send movement email after import</strong>
                                    <span>Builds LCY &amp; FCY loan book movement and emails the report immediately</span>
                                </div>
                            </label>
                        </div>

                        {{-- Email section (shown when toggle is on) --}}
                        <div id="emailSection" style="display:none;margin-top:1rem;">
                            <hr class="divider" style="margin:0 0 1rem;">

                            <div class="field" style="margin-bottom:1rem;">
                                <label>
                                    Compare against (start date)
                                    <span class="hint">Leave blank to use the previous day automatically</span>
                                </label>
                                <input type="date" name="start_date" value="{{ old('start_date') }}">
                            </div>

                            @include('finance.loans._recipient_picker', [
                                'configTo'  => $configTo,
                                'configCc'  => $configCc,
                                'oldTo'     => old('to', []),
                                'oldCc'     => old('cc', []),
                                'oldToExtra'=> old('to_extra', ''),
                                'oldCcExtra'=> old('cc_extra', ''),
                            ])
                        </div>
                    </div>

                    <div class="modal-footer">
                        <span class="run-note" style="margin-right:auto;">Large files may take up to a minute to process.</span>
                        <button type="submit" class="btn-run" id="uploadBtn">
                            <i class="fa-solid fa-upload"></i>
                            <span>Import File</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════ SEND EMAIL MODAL (no re-import) ═══════════ --}}
    <div class="modal fade" id="sendLoanEmailModal" tabindex="-1" aria-labelledby="sendLoanEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('finance.loans.pipeline.send') }}" id="sendForm">
                    @csrf
                    <input type="hidden" name="form_name" value="send">

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="sendLoanEmailModalLabel">Send Report Only</h5>
                            <div class="hint" style="margin-top:.15rem;">Use this if the data is already loaded and you just need to send the email for a date range.</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="form-row" style="margin-bottom:1rem;">
                            <div class="field">
                                <label>Start date <span style="color:#ef4444">*</span></label>
                                <input type="date" name="start_date" value="{{ old('start_date') }}" required>
                            </div>
                            <div class="field">
                                <label>End date <span style="color:#ef4444">*</span></label>
                                <input type="date" name="end_date" value="{{ old('end_date', $defaultDate) }}" required>
                            </div>
                        </div>
                        @include('finance.loans._recipient_picker', [
                            'configTo'  => $configTo,
                            'configCc'  => $configCc,
                            'oldTo'     => old('to', []),
                            'oldCc'     => old('cc', []),
                            'oldToExtra'=> old('to_extra', ''),
                            'oldCcExtra'=> old('cc_extra', ''),
                        ])
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn-run" style="background:var(--teal);" id="sendBtn">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Send Email</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function handleFileChange(input) {
            const preview = document.getElementById('filePreview');
            const dateInput = document.getElementById('loan_date');

            if (!input.files || !input.files[0]) {
                preview.classList.remove('show');
                return;
            }

            const filename = input.files[0].name;
            preview.textContent = filename;
            preview.classList.add('show');

            // Try to extract YYYYMMDD from filename
            const match = filename.match(/(\d{4})(\d{2})(\d{2})/);
            if (match) {
                const [, y, m, d] = match;
                const dateStr = `${y}-${m}-${d}`;
                // Only auto-fill if the date looks valid (month 01-12, day 01-31)
                if (+m >= 1 && +m <= 12 && +d >= 1 && +d <= 31) {
                    dateInput.value = dateStr;
                }
            }
        }

        function toggleEmailSection(checkbox) {
            document.getElementById('emailSection').style.display = checkbox.checked ? 'block' : 'none';
        }

        function toggleAcc(btn) {
            const body = btn.nextElementSibling;
            const chevron = btn.querySelector('.acc-chevron');
            const open = body.classList.toggle('open');
            chevron.classList.toggle('open', open);
        }

        // Disable the submit button on click so a double-click (or an impatient
        // second click while the slow synchronous import/email request is still
        // in flight) can't fire a second POST — this was the cause of the
        // loan movement email being sent twice.
        function guardSubmit(formId, btnId, busyLabel) {
            const form = document.getElementById(formId);
            const btn = document.getElementById(btnId);
            if (!form || !btn) return;
            form.addEventListener('submit', function () {
                btn.disabled = true;
                const label = btn.querySelector('span');
                if (label) label.textContent = busyLabel;
            });
        }

        // Auto-scroll log to bottom
        document.addEventListener('DOMContentLoaded', function () {
            const log = document.getElementById('logTail');
            if (log) log.scrollTop = log.scrollHeight;

            // Show email section if checkbox was checked (after validation error redirect)
            const cb = document.getElementById('send_email');
            if (cb && cb.checked) {
                document.getElementById('emailSection').style.display = 'block';
            }

            guardSubmit('uploadForm', 'uploadBtn', 'Uploading…');
            guardSubmit('sendForm', 'sendBtn', 'Sending…');

            // Re-open whichever modal failed validation, so the errors are visible
            @if ($errors->any())
                const reopenModalId = {{ old('form_name') === 'send' ? "'sendLoanEmailModal'" : "'uploadLoanModal'" }};
                const reopenModalEl = document.getElementById(reopenModalId);
                if (reopenModalEl) {
                    new bootstrap.Modal(reopenModalEl).show();
                }
            @endif
        });
    </script>
@endpush

@if (!empty($dashboard['asOfDate']))
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
        <script>
            const loanChartPayload = @json($dashboard['chartPayload']);
            const loanMtdYtdPayload = @json($dashboard['mtdYtdPayload']);

            function lbSeedParticles(id, count = 48) {
                const host = document.getElementById(id);
                if (!host) return;
                host.innerHTML = '';
                for (let i = 0; i < count; i++) {
                    const p = document.createElement('span');
                    p.className = 'banner-particle' + (i % 4 === 0 ? ' alt' : '');
                    p.style.left = Math.random() * 100 + '%';
                    p.style.bottom = (-20 - Math.random() * 40) + 'px';
                    p.style.animationDuration = (7 + Math.random() * 7) + 's';
                    p.style.animationDelay = (Math.random() * 6) + 's';
                    p.style.opacity = String(0.15 + Math.random() * 0.55);
                    const sz = 4 + Math.random() * 7;
                    p.style.width = p.style.height = sz + 'px';
                    host.appendChild(p);
                }
            }

            function lbKes(v) {
                const a = Math.abs(v), s = v < 0 ? '-' : '';
                if (a >= 1e9) return s + 'KES ' + (a / 1e9).toFixed(2) + 'B';
                if (a >= 1e6) return s + 'KES ' + (a / 1e6).toFixed(2) + 'M';
                if (a >= 1e3) return s + 'KES ' + (a / 1e3).toFixed(2) + 'K';
                return s + 'KES ' + a.toFixed(2);
            }

            function lbAxis(v) {
                const a = Math.abs(v);
                if (a >= 1e9) return (v / 1e9).toFixed(1) + 'B';
                if (a >= 1e6) return (v / 1e6).toFixed(1) + 'M';
                if (a >= 1e3) return (v / 1e3).toFixed(1) + 'K';
                return Number(v).toFixed(0);
            }

            const lbTip = {
                backgroundColor: 'rgba(255,255,255,0.98)',
                borderWidth: 1,
                borderColor: 'rgba(0,130,187,0.20)',
                titleColor: '#005B82',
                bodyColor: '#464646',
                padding: 10,
                cornerRadius: 8,
            };

            function lbRemoveSkeleton(canvasId) {
                const shell = document.getElementById(canvasId)?.closest('.chart-shell');
                if (shell) shell.classList.remove('skeleton');
            }

            function lbWithFade(canvasId, fn) {
                const shell = document.getElementById(canvasId)?.closest('.chart-shell');
                shell?.classList.add('updating');
                fn();
                setTimeout(() => shell?.classList.remove('updating'), 170);
            }

            function lbDrawSparkline(canvas, rawData, hexColor) {
                if (!canvas || !rawData || rawData.length < 2) return;
                const dpr = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * dpr;
                canvas.height = rect.height * dpr;
                const ctx = canvas.getContext('2d');
                ctx.scale(dpr, dpr);

                const W = rect.width, H = rect.height;
                const data = rawData.slice(-16);
                const min = Math.min(...data), max = Math.max(...data);
                const rng = max - min || 1;
                const pts = data.map((v, i) => [(i / (data.length - 1)) * W, H - ((v - min) / rng) * (H - 6) - 3]);

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

            function lbPopulateSparklines() {
                const sparkData = loanChartPayload.overall?.daily?.data || [];
                document.querySelectorAll('.loan-dash .summary-card .spark-canvas').forEach(canvas => {
                    const color = canvas.dataset.color || '#0082BB';
                    lbDrawSparkline(canvas, sparkData, color);
                });
            }

            function lbPopulateSegmentDeltas() {
                const datasets = loanChartPayload.segments?.daily?.datasets || [];
                datasets.forEach(ds => {
                    const data = ds.data || [];
                    const delta = data[data.length - 1] || 0;
                    document.querySelectorAll('[data-segment-label="' + ds.label + '"] .js-seg-delta').forEach(el => {
                        const isUp = delta > 0, isDown = delta < 0;
                        el.textContent = (isUp ? '▲ ' : isDown ? '▼ ' : '') + lbKes(Math.abs(delta)) + ' daily movement';
                        el.className = 'segment-delta-inner ' + (isUp ? 'delta-up' : isDown ? 'delta-down' : 'delta-flat');
                    });
                });
            }

            function lbBuildStackedBar(canvasId) {
                return new Chart(document.getElementById(canvasId).getContext('2d'), {
                    type: 'bar',
                    data: { labels: [], datasets: [] },
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 10, boxWidth: 7, font: { size: 10, weight: '700', family: 'Montserrat, Segoe UI, Arial, sans-serif' } } },
                            tooltip: Object.assign({}, lbTip, {
                                callbacks: {
                                    title: items => items?.[0]?.chart?.data?.labels?.[items?.[0]?.dataIndex ?? 0] || '',
                                    label: ctx => ' ' + ctx.dataset.label + ': ' + lbKes(ctx.parsed.y)
                                }
                            }),
                            datalabels: {
                                display: ctx => Math.abs(ctx.dataset.data[ctx.dataIndex] ?? 0) >= 1e8,
                                color: '#fff',
                                font: { weight: '700', size: 9, family: 'Montserrat, Segoe UI, Arial, sans-serif' },
                                formatter: v => lbAxis(v),
                                anchor: 'center', align: 'center', clamp: true,
                                textShadowBlur: 4, textShadowColor: 'rgba(0,0,0,0.35)',
                            }
                        },
                        scales: {
                            x: { stacked: true, grid: { display: false }, ticks: { font: { size: 9, weight: '600' }, maxRotation: 0, autoSkip: true, maxTicksLimit: 14 } },
                            y: { stacked: true, grid: { color: 'rgba(0,0,0,0.045)' }, ticks: { font: { size: 9 }, callback: v => lbAxis(v) } }
                        }
                    }
                });
            }

            function lbBuildDoughnut(canvasId, payload) {
                return new Chart(document.getElementById(canvasId).getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: payload?.labels || [],
                        datasets: [{ data: payload?.data || [], backgroundColor: payload?.colors || [], borderColor: '#E8EBF0', borderWidth: 3, hoverOffset: 8 }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '55%',
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12, boxWidth: 8 } },
                            tooltip: Object.assign({}, lbTip, {
                                callbacks: {
                                    label(ctx) {
                                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                        const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                        return ' ' + ctx.label + ': ' + lbKes(ctx.parsed) + ' (' + pct + '%)';
                                    }
                                }
                            }),
                            datalabels: {
                                display: ctx => {
                                    const dataset = ctx.dataset;
                                    const total = dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? (dataset.data[ctx.dataIndex] / total) * 100 : 0;
                                    return pct >= 4;
                                },
                                color: '#ffffff',
                                font: { weight: '800', size: 12, family: 'Montserrat, Segoe UI, Arial, sans-serif' },
                                formatter: (value, ctx) => {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    return total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '';
                                },
                                textShadowBlur: 6, textShadowColor: 'rgba(0,0,0,0.55)',
                            }
                        }
                    }
                });
            }

            let lbOverallBarChart, lbMovementChart, lbSegmentChart, lbMtdYtdChart,
                lbSegmentPieChart, lbCurrencyMixPieChart, lbStatusPieChart;

            function lbUpdateOverallBar(mode) {
                lbWithFade('lbOverallChart', () => {
                    const p = loanChartPayload.overallBreakdown?.[mode] || { labels: [], datasets: [], periods: [] };
                    const barConfig = {
                        daily: { maxBarThickness: 28, categoryPercentage: 0.72, barPercentage: 0.82, maxTicksLimit: 14 },
                        weekly: { maxBarThickness: 44, categoryPercentage: 0.86, barPercentage: 0.92, maxTicksLimit: 13 },
                        monthly: { maxBarThickness: 56, categoryPercentage: 0.92, barPercentage: 0.95, maxTicksLimit: 13 },
                    }[mode] || { maxBarThickness: 32, categoryPercentage: 0.78, barPercentage: 0.86, maxTicksLimit: 14 };

                    lbOverallBarChart.data.labels = p.labels || [];
                    lbOverallBarChart.data.datasets = (p.datasets || []).map(ds => ({
                        label: ds.label,
                        data: ds.data,
                        backgroundColor: ds.color,
                        borderColor: (ds.data || []).map((_, i) => (p.periods?.[i]?.is_baseline ? '#F59E0B' : ds.color)),
                        borderWidth: (ds.data || []).map((_, i) => (p.periods?.[i]?.is_baseline ? 2 : 0)),
                        borderRadius: 5,
                        borderSkipped: false,
                        maxBarThickness: barConfig.maxBarThickness,
                        categoryPercentage: barConfig.categoryPercentage,
                        barPercentage: barConfig.barPercentage,
                    }));
                    lbOverallBarChart.options.scales.x.ticks.maxTicksLimit = barConfig.maxTicksLimit;
                    lbOverallBarChart.options.scales.x.ticks.autoSkip = mode === 'daily';
                    lbOverallBarChart.update();
                });
                document.getElementById('lbOverallSub').textContent = {
                    daily: 'Daily performing closing balances split by business segment, with EOY baseline fixed at the start.',
                    weekly: 'Weekly performing closing balances split by business segment, with EOY baseline fixed at the start.',
                    monthly: 'Month-end performing closing balances split by business segment, with EOY baseline fixed at the start.',
                }[mode];
            }

            function lbUpdateMovement(mode) {
                lbWithFade('lbMovementChart', () => {
                    const p = loanChartPayload.overall?.[mode] || { labels: [], data: [] };
                    lbMovementChart.data.labels = p.labels || [];
                    lbMovementChart.data.datasets[0].data = p.data || [];
                    lbMovementChart.update();
                });
                document.getElementById('lbMovementSub').textContent = {
                    daily: 'Net performing loan book movement between consecutive business days.',
                    weekly: 'Net movement between weekly closing points.',
                    monthly: 'Net movement between monthly closing points.',
                }[mode];
            }

            function lbUpdateSegment(mode) {
                lbWithFade('lbSegmentChart', () => {
                    const p = loanChartPayload.segments?.[mode] || { labels: [], datasets: [] };
                    lbSegmentChart.data.labels = p.labels || [];
                    lbSegmentChart.data.datasets = (p.datasets || []).map(ds => ({
                        label: ds.label,
                        data: ds.data,
                        borderColor: ds.color,
                        backgroundColor: ds.color + '22',
                        pointBackgroundColor: ds.color,
                        pointRadius: 2.5,
                        pointHoverRadius: 5,
                        borderWidth: 2,
                        tension: 0.35,
                        fill: false,
                    }));
                    lbSegmentChart.update();
                });
                if (mode === 'daily') lbPopulateSegmentDeltas();
            }

            document.addEventListener('DOMContentLoaded', function () {
                lbSeedParticles('loanDashParticles', 48);

                if (typeof ChartDataLabels !== 'undefined') {
                    Chart.register(ChartDataLabels);
                }
                Chart.defaults.color = '#8A96A8';
                Chart.defaults.font.family = 'Montserrat, Segoe UI, Arial, sans-serif';
                Chart.defaults.font.size = 11;

                lbOverallBarChart = lbBuildStackedBar('lbOverallChart');

                const lbMoveCtx = document.getElementById('lbMovementChart').getContext('2d');
                const lbMoveGrad = lbMoveCtx.createLinearGradient(0, 0, 0, 320);
                lbMoveGrad.addColorStop(0, 'rgba(0,130,187,0.20)');
                lbMoveGrad.addColorStop(1, 'rgba(0,130,187,0.02)');

                lbMovementChart = new Chart(lbMoveCtx, {
                    type: 'line',
                    data: { labels: [], datasets: [{ label: 'Net Movement', data: [], borderColor: '#0082BB', backgroundColor: lbMoveGrad, fill: true, borderWidth: 2.5, tension: 0.35, pointRadius: 3, pointHoverRadius: 6, pointBackgroundColor: '#BED600' }] },
                    options: {
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: Object.assign({}, lbTip, { callbacks: { label: ctx => ' Movement: ' + lbKes(ctx.parsed.y) } }),
                            datalabels: { display: false }
                        },
                        scales: {
                            x: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { maxRotation: 45 } },
                            y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: v => lbAxis(v) } }
                        }
                    }
                });

                lbSegmentChart = new Chart(document.getElementById('lbSegmentChart').getContext('2d'), {
                    type: 'line',
                    data: { labels: [], datasets: [] },
                    options: {
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 14, boxWidth: 8 } },
                            tooltip: Object.assign({}, lbTip, { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + lbKes(ctx.parsed.y) } }),
                            datalabels: { display: false }
                        },
                        scales: {
                            x: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { maxRotation: 45 } },
                            y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: v => lbAxis(v) } }
                        }
                    }
                });

                lbMtdYtdChart = new Chart(document.getElementById('lbMtdYtdChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: loanMtdYtdPayload.labels || [],
                        datasets: [
                            { label: 'MTD Movement', data: loanMtdYtdPayload.mtd || [], backgroundColor: 'rgba(0,130,187,0.75)', borderColor: '#0082BB', borderWidth: 1.5, borderRadius: 5, maxBarThickness: 28 },
                            { label: 'YTD Movement', data: loanMtdYtdPayload.ytd || [], backgroundColor: 'rgba(16,185,129,0.72)', borderColor: '#10B981', borderWidth: 1.5, borderRadius: 5, maxBarThickness: 28 }
                        ]
                    },
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 14, boxWidth: 8 } },
                            tooltip: Object.assign({}, lbTip, { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + lbKes(ctx.parsed.y) } }),
                            datalabels: {
                                display: ctx => Math.abs(ctx.dataset.data[ctx.dataIndex] ?? 0) >= 1e6,
                                color: '#fff',
                                font: { weight: '700', size: 10, family: 'Montserrat, Segoe UI, Arial, sans-serif' },
                                formatter: v => lbAxis(v),
                                anchor: 'center', align: 'center', clamp: true,
                            }
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: v => lbAxis(v) } }
                        }
                    }
                });

                lbSegmentPieChart = lbBuildDoughnut('lbSegmentPieChart', loanChartPayload.segmentPie);
                lbCurrencyMixPieChart = lbBuildDoughnut('lbCurrencyMixPieChart', loanChartPayload.currencyMixPie);
                lbStatusPieChart = lbBuildDoughnut('lbStatusPieChart', loanChartPayload.statusPie);

                ['lbOverallChart', 'lbMovementChart', 'lbSegmentChart', 'lbMtdYtdChart',
                    'lbSegmentPieChart', 'lbCurrencyMixPieChart', 'lbStatusPieChart'
                ].forEach(lbRemoveSkeleton);

                lbUpdateOverallBar('daily');
                lbUpdateMovement('daily');
                lbUpdateSegment('daily');
                lbPopulateSparklines();

                const lbChartUpdaters = {
                    lbOverall: lbUpdateOverallBar,
                    lbMovement: lbUpdateMovement,
                    lbSegment: lbUpdateSegment,
                };
                document.querySelectorAll('.loan-dash .chart-mode-switch').forEach(switchEl => {
                    const key = switchEl.getAttribute('data-chart');
                    switchEl.querySelectorAll('.chart-mode-btn').forEach(btn => {
                        btn.addEventListener('click', function () {
                            switchEl.querySelectorAll('.chart-mode-btn').forEach(b => b.classList.remove('active'));
                            btn.classList.add('active');
                            lbChartUpdaters[key]?.(btn.getAttribute('data-mode'));
                        });
                    });
                });

                document.querySelectorAll('.loan-dash .tab-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        document.querySelectorAll('.loan-dash .tab-btn').forEach(b => b.classList.remove('active'));
                        document.querySelectorAll('.loan-dash .tab-pane').forEach(p => p.classList.remove('active'));
                        btn.classList.add('active');
                        document.getElementById('tab-' + btn.getAttribute('data-tab')).classList.add('active');
                    });
                });

                window.addEventListener('resize', () => {
                    [lbOverallBarChart, lbMovementChart, lbSegmentChart, lbMtdYtdChart,
                        lbSegmentPieChart, lbCurrencyMixPieChart, lbStatusPieChart
                    ].forEach(c => c?.resize());
                    lbPopulateSparklines();
                });
            });
        </script>
    @endpush
@endif
