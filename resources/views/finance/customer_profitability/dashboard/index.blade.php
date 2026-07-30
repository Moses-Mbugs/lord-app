@extends('layouts.finance.template')
@section('title', 'Customer Profitability Dashboard')

@push('styles')
    <style>
        :root {
            --cp-navy: var(--finance-navy, #052b4f);
            --cp-blue: #0082BB;
            --cp-blue-soft: #e8f5fb;
            --cp-green: #BED600;
            --cp-green-dark: #669438;
            --cp-ink: #14213d;
            --cp-muted: var(--finance-muted, #6b7280);
            --cp-border: var(--finance-border, #e5e7eb);
            --cp-card: #ffffff;
            --cp-red: #dc2626;
            --cp-shadow: 0 10px 30px rgba(5,43,79,.08);
            --cp-shadow-sm: 0 6px 16px rgba(5,43,79,.06);
            --cp-radius-lg: 16px;
            --cp-radius-md: 12px;
        }

        .cp-page { margin: -4px -2px 0; color: var(--cp-ink); }

        /* ── Hero ─────────────────────────────────────────────────── */
        .cp-hero {
            border-radius: var(--cp-radius-lg);
            padding: 16px 20px;
            color: #fff;
            background: linear-gradient(135deg, #052b4f 0%, #073b6b 55%, #005B82 100%);
            box-shadow: var(--cp-shadow);
            margin-bottom: 12px;
        }

        .cp-hero-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .cp-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.16);
            color: rgba(255,255,255,.88);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .cp-eyebrow-dot {
            width: 7px; height: 7px;
            border-radius: 999px;
            background: var(--cp-green);
        }

        .cp-title {
            margin: 8px 0 4px;
            font-size: clamp(16px, 2vw, 22px);
            font-weight: 800;
            letter-spacing: -.03em;
            line-height: 1.1;
        }

        .cp-subtitle {
            color: rgba(255,255,255,.75);
            font-size: 12px;
            margin: 0;
        }

        .cp-hero-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .cp-hero-stat {
            text-align: center;
            min-width: 80px;
            padding: 8px 12px;
            border-radius: 12px;
            background: rgba(255,255,255,.10);
            border: 1px solid rgba(255,255,255,.14);
        }

        .cp-hero-stat-val {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -.025em;
            line-height: 1;
        }

        .cp-hero-stat-lbl {
            font-size: 10px;
            color: rgba(255,255,255,.65);
            text-transform: uppercase;
            letter-spacing: .07em;
            font-weight: 700;
            margin-top: 3px;
        }

        .cp-select, .cp-select:focus {
            min-height: 34px;
            border: 1px solid rgba(255,255,255,.22);
            background-color: rgba(255,255,255,.12);
            color: #fff;
            box-shadow: none;
            border-radius: 999px;
            font-size: 11px;
            max-width: 280px;
        }

        .cp-select option { color: #111827; }

        .cp-btn-primary, .cp-btn-primary:hover, .cp-btn-primary:focus {
            border: 0;
            color: #09213a;
            background: linear-gradient(135deg, #d9ee47, #BED600);
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            padding: 8px 14px;
        }

        .cp-btn-ghost, .cp-btn-ghost:hover, .cp-btn-ghost:focus {
            color: #fff;
            border: 1px solid rgba(255,255,255,.20);
            background: rgba(255,255,255,.10);
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            padding: 7px 12px;
        }

        /* ── KPI strip ────────────────────────────────────────────── */
        .cp-kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0,1fr));
            gap: 10px;
            margin: 0 0 12px;
        }

        .cp-kpi-card {
            border-radius: var(--cp-radius-md);
            background: var(--cp-card);
            border: 1px solid rgba(226,232,240,.9);
            box-shadow: var(--cp-shadow-sm);
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cp-kpi-icon {
            flex: 0 0 auto;
            width: 34px; height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            color: var(--icon-color, var(--cp-blue));
            background: var(--icon-bg, var(--cp-blue-soft));
            font-size: 14px;
            font-weight: 900;
        }

        .cp-kpi-label {
            color: var(--cp-muted);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 800;
            margin-bottom: 3px;
        }

        .cp-kpi-value {
            color: var(--cp-navy);
            font-size: clamp(16px, 1.6vw, 22px);
            font-weight: 850;
            line-height: 1.1;
            letter-spacing: -.03em;
        }

        .cp-kpi-sub {
            color: #94a3b8;
            font-size: 10px;
            margin-top: 2px;
            line-height: 1.3;
        }

        .cp-negative { color: var(--cp-red) !important; }

        /* ── Tabs ─────────────────────────────────────────────────── */
        .cp-tabs-shell {
            position: sticky; top: 0; z-index: 8;
            margin: 0 0 12px;
            padding: 6px;
            border-radius: 14px;
            background: rgba(245,248,251,.9);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(226,232,240,.78);
        }

        .cp-tabs {
            gap: 6px; border: 0;
            flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none;
        }

        .cp-tabs::-webkit-scrollbar { display: none; }

        .cp-tabs .nav-link {
            border: 0; border-radius: 999px;
            color: #64748b; font-size: 11px; font-weight: 800;
            padding: 8px 12px; white-space: nowrap;
            transition: all .15s ease;
        }

        .cp-tabs .nav-link:hover { color: var(--cp-blue); background: rgba(0,130,187,.07); }

        .cp-tabs .nav-link.active {
            color: #fff;
            background: linear-gradient(135deg, var(--cp-navy), var(--cp-blue));
            box-shadow: 0 8px 18px rgba(0,91,130,.18);
        }

        /* ── Cards ────────────────────────────────────────────────── */
        .cp-card {
            background: var(--cp-card);
            border: 1px solid rgba(226,232,240,.92);
            border-radius: var(--cp-radius-lg);
            box-shadow: var(--cp-shadow-sm);
            overflow: hidden;
        }

        .cp-card-body { padding: 14px; }

        .cp-card-header {
            display: flex; align-items: flex-start;
            justify-content: space-between;
            gap: 12px; margin-bottom: 12px;
        }

        .cp-card-title {
            color: var(--cp-navy);
            font-size: 13px; font-weight: 850;
            letter-spacing: -.015em; margin: 0;
        }

        .cp-card-subtitle {
            color: #94a3b8; font-size: 11px;
            margin: 3px 0 0; line-height: 1.4;
        }

        .cp-pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 9px; border-radius: 999px;
            font-size: 10px; font-weight: 800;
            color: var(--cp-blue); background: var(--cp-blue-soft);
            white-space: nowrap;
        }

        .cp-pill.success { color: #3f6212; background: #f0f7c2; }
        .cp-pill.danger  { color: #991b1b; background: #fee2e2; }

        .cp-chart {
            position: relative;
            min-height: var(--chart-h, 220px);
            height: var(--chart-h, 220px);
        }

        /* ── Grids ────────────────────────────────────────────────── */
        .cp-overview-grid {
            display: grid;
            grid-template-columns: minmax(280px, .75fr) minmax(360px, 1.25fr);
            gap: 12px; margin-bottom: 12px;
        }

        .cp-two-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 12px;
        }

        .cp-section-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(280px, .75fr);
            gap: 12px;
        }

        /* ── Table ────────────────────────────────────────────────── */
        .cp-table { margin: 0; border-collapse: separate; border-spacing: 0 6px; }

        .cp-table thead th {
            border: 0; padding: 0 10px 6px;
            color: #94a3b8; font-size: 10px;
            text-transform: uppercase; letter-spacing: .08em; font-weight: 850;
        }

        .cp-table tbody tr { background: #fff; box-shadow: 0 4px 12px rgba(15,23,42,.04); }

        .cp-table tbody td {
            border-top: 1px solid #eef2f7; border-bottom: 1px solid #eef2f7;
            padding: 10px; vertical-align: middle; font-size: 11px;
        }

        .cp-table tbody td:first-child { border-left: 1px solid #eef2f7; border-radius: 12px 0 0 12px; }
        .cp-table tbody td:last-child  { border-right: 1px solid #eef2f7; border-radius: 0 12px 12px 0; }

        .cp-customer-cell { display: flex; align-items: center; gap: 8px; min-width: 180px; }

        .cp-avatar {
            flex: 0 0 auto; width: 30px; height: 30px;
            border-radius: 10px; display: inline-flex;
            align-items: center; justify-content: center;
            color: var(--cp-navy);
            background: linear-gradient(135deg, #e8f5fb, #f0f7c2);
            font-size: 11px; font-weight: 900;
        }

        .cp-name { color: #1f2937; font-weight: 800; line-height: 1.2; }
        .cp-meta { color: #94a3b8; font-size: 10px; margin-top: 1px; }

        .badge-rc  { background: #dbeafe; color: #1d4ed8; }
        .badge-mc  { background: #d1fae5; color: #065f46; }
        .badge-io  { background: #ede9fe; color: #5b21b6; }
        .badge-fi  { background: #fee2e2; color: #991b1b; }
        .badge-pscb { background: #fef3c7; color: #92400e; }

        .badge-rc,.badge-mc,.badge-io,.badge-fi,.badge-pscb {
            border-radius: 999px; padding: 4px 8px;
            font-size: 10px; font-weight: 850; letter-spacing: .03em;
        }

        /* ── Insight list ─────────────────────────────────────────── */
        .cp-insight-list { display: grid; gap: 8px; margin: 0; padding: 0; list-style: none; }

        .cp-insight-list li {
            display: flex; align-items: flex-start; gap: 8px;
            padding: 10px; border-radius: 12px;
            background: #f8fafc; border: 1px solid #eef2f7;
        }

        .cp-insight-dot {
            flex: 0 0 auto; width: 8px; height: 8px; margin-top: 4px;
            border-radius: 999px; background: var(--cp-blue);
            box-shadow: 0 0 0 4px rgba(0,130,187,.10);
        }

        .cp-insight-title { display: block; color: var(--cp-navy); font-size: 11px; font-weight: 850; line-height: 1.3; }
        .cp-insight-copy  { display: block; color: #64748b; font-size: 10px; line-height: 1.4; margin-top: 1px; }

        /* ── Empty state ──────────────────────────────────────────── */
        .cp-empty {
            padding: 24px; text-align: center; color: #64748b;
            background: #f8fafc; border: 1px dashed #cbd5e1;
            border-radius: var(--cp-radius-lg);
        }
        .cp-empty strong { display: block; color: var(--cp-navy); font-size: 13px; margin-bottom: 3px; }

        /* ── Customer Search ──────────────────────────────────────── */
        .cp-search-input {
            border-radius: 999px;
            border: 1.5px solid #e2e8f0;
            padding: 9px 16px;
            font-size: 13px;
            background: #f8fafc;
            color: var(--cp-ink);
            outline: none;
            transition: border-color .15s;
            flex: 1;
        }
        .cp-search-input:focus { border-color: var(--cp-blue); background: #fff; }

        .cp-search-btn {
            border-radius: 999px;
            border: 0;
            padding: 9px 20px;
            font-size: 12px;
            font-weight: 800;
            color: #09213a;
            background: linear-gradient(135deg, #d9ee47, #BED600);
            cursor: pointer;
            white-space: nowrap;
        }

        .cp-result-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }

        .cp-result-item {
            padding: 10px 14px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            cursor: pointer;
            transition: border-color .14s, background .14s;
            background: #fff;
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
        }

        .cp-result-item:hover, .cp-result-item.active {
            border-color: var(--cp-blue);
            background: var(--cp-blue-soft);
        }

        .cp-result-name { font-size: 12px; font-weight: 800; color: var(--cp-navy); line-height: 1.2; }
        .cp-result-meta { font-size: 10px; color: #64748b; margin-top: 2px; }
        .cp-result-rev  { font-size: 13px; font-weight: 850; color: var(--cp-navy); white-space: nowrap; }

        .cp-detail-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: var(--cp-radius-lg);
            padding: 14px;
        }

        .cp-detail-name  { font-size: 15px; font-weight: 850; color: var(--cp-navy); margin-bottom: 2px; }
        .cp-detail-sub   { font-size: 11px; color: #64748b; }
        .cp-detail-stats { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0; }

        .cp-detail-stat {
            flex: 1; min-width: 80px;
            padding: 8px 10px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            text-align: center;
        }

        .cp-detail-stat-val { font-size: 15px; font-weight: 850; color: var(--cp-navy); }
        .cp-detail-stat-lbl { font-size: 9px; color: #94a3b8; text-transform: uppercase; letter-spacing: .07em; font-weight: 700; margin-top: 2px; }

        .cp-search-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 14px;
            align-items: start;
        }

        /* ── Animation ────────────────────────────────────────────── */
        .cp-tab-pane { animation: cpFadeUp .22s ease both; }

        @keyframes cpFadeUp {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Responsive ───────────────────────────────────────────── */
        @media (max-width: 1199.98px) {
            .cp-kpi-grid { grid-template-columns: repeat(3, minmax(0,1fr)); }
            .cp-hero-inner { flex-direction: column; align-items: flex-start; }
            .cp-overview-grid, .cp-section-grid { grid-template-columns: 1fr; }
            .cp-search-layout { grid-template-columns: 1fr; }
        }

        @media (max-width: 767.98px) {
            .cp-page { margin: 0; }
            .cp-hero { padding: 12px 14px; }
            .cp-kpi-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .cp-two-grid { grid-template-columns: 1fr; }
            .cp-card-body { padding: 12px; }
        }
    </style>
@endpush

@section('content')
    @php
        $totalRevenue    = $summary['total_revenue']    ?? 0;
        $customerCount   = $summary['customer_count']   ?? 0;
        $topCustomerRev  = $summary['top_customer_rev'] ?? 0;
        $topCustomerName = $summary['top_customer_name'] ?? 'No customer yet';
        $lossMakingCount = $summary['loss_making_count'] ?? 0;
        $avgRevenue      = $summary['avg_revenue']       ?? 0;
        $lossRate        = $customerCount > 0 ? round(($lossMakingCount / $customerCount) * 100, 1) : 0;
        $topCustomerShare = $totalRevenue > 0 ? round(($topCustomerRev / $totalRevenue) * 100, 1) : 0;
        $topSegmentName   = collect($segmentData ?? [])->sortDesc()->keys()->first() ?? 'N/A';
        $topSegmentValue  = collect($segmentData ?? [])->sortDesc()->first() ?? 0;
        $topSegmentShare  = $totalRevenue > 0 ? round(($topSegmentValue / $totalRevenue) * 100, 1) : 0;
        $topRm            = collect($rmPerformance ?? [])->sortByDesc('total_revenue')->first();
        $topRmName        = $topRm['rm']            ?? 'N/A';
        $topRmRevenue     = $topRm['total_revenue'] ?? 0;
    @endphp

    <div class="cp-page">

        {{-- ── Hero ───────────────────────────────────────────────── --}}
        <section class="cp-hero">
            <div class="cp-hero-inner">
                <div>
                    <span class="cp-eyebrow"><span class="cp-eyebrow-dot"></span> Customer Profitability</span>
                    <h1 class="cp-title">Profitability Dashboard</h1>
                    <p class="cp-subtitle">
                        {{ $batch->original_name }}
                        @if($batch->ytd_label) &nbsp;·&nbsp; {{ $batch->ytd_label }}@endif
                        &nbsp;·&nbsp; {{ $batch->created_at->format('d M Y') }}
                    </p>

                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @if ($batches->count() > 1)
                            <select class="form-select form-select-sm cp-select"
                                onchange="window.location='{{ url('finance/customer-profitability/dashboard') }}/' + this.value">
                                @foreach ($batches as $b)
                                    <option value="{{ $b->id }}" {{ $b->id === $batch->id ? 'selected' : '' }}>
                                        {{ $b->original_name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        <a href="{{ route('finance.customer_profitability.upload') }}" class="btn cp-btn-primary">Upload</a>
                    </div>
                </div>

                <div class="cp-hero-right">
                    <div class="cp-hero-stat">
                        <div class="cp-hero-stat-val">${{ number_format($totalRevenue / 1e6, 1) }}M</div>
                        <div class="cp-hero-stat-lbl">YTD Revenue</div>
                    </div>
                    <div class="cp-hero-stat">
                        <div class="cp-hero-stat-val">{{ number_format($customerCount) }}</div>
                        <div class="cp-hero-stat-lbl">Customers</div>
                    </div>
                    <div class="cp-hero-stat">
                        <div class="cp-hero-stat-val" style="color: #f87171;">{{ number_format($lossMakingCount) }}</div>
                        <div class="cp-hero-stat-lbl">Loss Making</div>
                    </div>
                    <div class="cp-hero-stat">
                        <div class="cp-hero-stat-val">{{ $topSegmentName }}</div>
                        <div class="cp-hero-stat-lbl">Top Segment</div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── KPI strip ───────────────────────────────────────────── --}}
        <div class="cp-kpi-grid">
            <div class="cp-kpi-card" style="--icon-bg:#e8f5fb;--icon-color:#0082BB;">
                <span class="cp-kpi-icon">$</span>
                <div>
                    <div class="cp-kpi-label">Total Revenue</div>
                    <div class="cp-kpi-value">${{ number_format($totalRevenue / 1e6, 1) }}M</div>
                    <div class="cp-kpi-sub">YTD portfolio profitability</div>
                </div>
            </div>

            <div class="cp-kpi-card" style="--icon-bg:#f0f7c2;--icon-color:#669438;">
                <span class="cp-kpi-icon">C</span>
                <div>
                    <div class="cp-kpi-label">Customers</div>
                    <div class="cp-kpi-value">{{ number_format($customerCount) }}</div>
                    <div class="cp-kpi-sub">{{ number_format(count($segmentData ?? [])) }} active segments</div>
                </div>
            </div>

            <div class="cp-kpi-card" style="--icon-bg:#ede9fe;--icon-color:#5b21b6;">
                <span class="cp-kpi-icon">T</span>
                <div>
                    <div class="cp-kpi-label">Top Customer</div>
                    <div class="cp-kpi-value">${{ number_format($topCustomerRev / 1e6, 1) }}M</div>
                    <div class="cp-kpi-sub">{{ Str::limit($topCustomerName, 28) }}</div>
                </div>
            </div>

            <div class="cp-kpi-card" style="--icon-bg:#fee2e2;--icon-color:#dc2626;">
                <span class="cp-kpi-icon">!</span>
                <div>
                    <div class="cp-kpi-label">Loss Making</div>
                    <div class="cp-kpi-value cp-negative">{{ number_format($lossMakingCount) }}</div>
                    <div class="cp-kpi-sub">{{ $lossRate }}% require review</div>
                </div>
            </div>

            <div class="cp-kpi-card" style="--icon-bg:#fffbeb;--icon-color:#b45309;">
                <span class="cp-kpi-icon">A</span>
                <div>
                    <div class="cp-kpi-label">Avg / Customer</div>
                    <div class="cp-kpi-value">${{ number_format($avgRevenue / 1e3, 0) }}K</div>
                    <div class="cp-kpi-sub">Average YTD contribution</div>
                </div>
            </div>
        </div>

        {{-- ── Tabs ────────────────────────────────────────────────── --}}
        <div class="cp-tabs-shell">
            <ul class="nav nav-tabs cp-tabs" id="dashTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tabOverview" role="tab">Overview</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-bs-toggle="tab" href="#tabSearch" role="tab">Customer Lookup</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-bs-toggle="tab" href="#tabTop" role="tab">Top Customers</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-bs-toggle="tab" href="#tabLoss" role="tab">Loss Makers</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-bs-toggle="tab" href="#tabMix" role="tab">Revenue Mix</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-bs-toggle="tab" href="#tabRM" role="tab">RM Performance</a>
                </li>
            </ul>
        </div>

        <div class="tab-content pb-4">

            {{-- ── Overview tab ────────────────────────────────────── --}}
            <div class="tab-pane cp-tab-pane fade show active" id="tabOverview" role="tabpanel">
                <div class="cp-overview-grid">
                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Revenue by segment</h2>
                                    <p class="cp-card-subtitle">Segment concentration and contribution split.</p>
                                </div>
                                <span class="cp-pill success">{{ $topSegmentShare }}% top seg.</span>
                            </div>
                            <div class="cp-chart" style="--chart-h:220px;">
                                <canvas id="cSeg"></canvas>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-2" id="segLegend"></div>
                        </div>
                    </div>

                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Monthly revenue trend</h2>
                                    <p class="cp-card-subtitle">Revenue movement by month.</p>
                                </div>
                                <span class="cp-pill">Trend</span>
                            </div>
                            <div class="cp-chart" style="--chart-h:255px;">
                                <canvas id="cMo"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cp-section-grid">
                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Monthly revenue by segment</h2>
                                    <p class="cp-card-subtitle">Which segments drive monthly performance.</p>
                                </div>
                                <span class="cp-pill">Stacked</span>
                            </div>
                            <div class="cp-chart" style="--chart-h:240px;">
                                <canvas id="cMoSeg"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Executive signals</h2>
                                    <p class="cp-card-subtitle">Key points for management attention.</p>
                                </div>
                            </div>
                            <ul class="cp-insight-list">
                                <li>
                                    <span class="cp-insight-dot"></span>
                                    <span>
                                        <span class="cp-insight-title">{{ $topSegmentName }} leads at {{ $topSegmentShare }}%</span>
                                        <span class="cp-insight-copy">{{ $topSegmentName }} contributes the largest share of YTD revenue.</span>
                                    </span>
                                </li>
                                <li>
                                    <span class="cp-insight-dot" style="background:var(--cp-red);box-shadow:0 0 0 4px rgba(220,38,38,.10);"></span>
                                    <span>
                                        <span class="cp-insight-title">{{ number_format($lossMakingCount) }} loss-making accounts</span>
                                        <span class="cp-insight-copy">Prioritize pricing, wallet share and product cost review.</span>
                                    </span>
                                </li>
                                <li>
                                    <span class="cp-insight-dot" style="background:var(--cp-green-dark);box-shadow:0 0 0 4px rgba(190,214,0,.18);"></span>
                                    <span>
                                        <span class="cp-insight-title">{{ Str::limit($topCustomerName, 28) }} leads revenue</span>
                                        <span class="cp-insight-copy">Top customer contributes {{ $topCustomerShare }}% of portfolio revenue.</span>
                                    </span>
                                </li>
                                <li>
                                    <span class="cp-insight-dot" style="background:#7c3aed;box-shadow:0 0 0 4px rgba(124,58,237,.10);"></span>
                                    <span>
                                        <span class="cp-insight-title">{{ $topRmName }} leads RM revenue</span>
                                        <span class="cp-insight-copy">Current RM revenue ≈ ${{ number_format($topRmRevenue / 1e6, 1) }}M.</span>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Customer Lookup tab ──────────────────────────────── --}}
            <div class="tab-pane cp-tab-pane fade" id="tabSearch" role="tabpanel">
                <div class="cp-card mb-3">
                    <div class="cp-card-body">
                        <div class="cp-card-header">
                            <div>
                                <h2 class="cp-card-title">Customer Lookup</h2>
                                <p class="cp-card-subtitle">Search by customer name or CIF to view their profitability breakdown and trends.</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mb-3">
                            <input type="text" id="custSearchInput" class="cp-search-input"
                                placeholder="Type customer name or CIF…" autocomplete="off">
                            <button class="cp-search-btn" onclick="cpSearch()">Search</button>
                        </div>
                        <div id="searchStatus" class="text-muted" style="font-size:12px;"></div>
                    </div>
                </div>

                <div class="cp-search-layout" id="searchLayout" style="display:none!important;">
                    <div>
                        <div class="cp-card">
                            <div class="cp-card-body">
                                <div class="cp-card-title mb-2">Results</div>
                                <ul class="cp-result-list" id="cpResultList"></ul>
                            </div>
                        </div>
                    </div>
                    <div id="cpDetailPanel">
                        <div class="cp-empty"><strong>Select a customer</strong>Click a result on the left to see their breakdown.</div>
                    </div>
                </div>
            </div>

            {{-- ── Top customers tab ───────────────────────────────── --}}
            <div class="tab-pane cp-tab-pane fade" id="tabTop" role="tabpanel">
                <div class="cp-section-grid">
                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Top 20 customers by YTD revenue</h2>
                                    <p class="cp-card-subtitle">Ranked revenue leaders.</p>
                                </div>
                                <span class="cp-pill success">Revenue leaders</span>
                            </div>
                            <div class="cp-chart" style="--chart-h:580px;">
                                <canvas id="cTop"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Customer leaderboard</h2>
                                    <p class="cp-card-subtitle">Highest contributors at a glance.</p>
                                </div>
                            </div>
                            @if (count($topCustomers ?? []) > 0)
                                <div class="table-responsive">
                                    <table class="table cp-table">
                                        <thead>
                                            <tr><th>Customer</th><th>Seg</th><th class="text-end">Revenue</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach (collect($topCustomers ?? [])->take(8) as $c)
                                                <tr>
                                                    <td>
                                                        <div class="cp-customer-cell">
                                                            <span class="cp-avatar">{{ strtoupper(substr($c['name'],0,1)) }}</span>
                                                            <span>
                                                                <span class="cp-name">{{ Str::limit($c['name'],26) }}</span>
                                                                <span class="cp-meta">Rank #{{ $loop->iteration }}</span>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge badge-{{ strtolower(str_replace('-','',$c['segment'])) }}">{{ $c['segment'] }}</span></td>
                                                    <td class="text-end fw-bold">${{ number_format($c['revenue']/1e6,2) }}M</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="cp-empty"><strong>No data</strong>Upload a valid file to see top customers.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Loss Makers tab ─────────────────────────────────── --}}
            <div class="tab-pane cp-tab-pane fade" id="tabLoss" role="tabpanel">
                <div class="cp-section-grid">
                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Loss-making accounts</h2>
                                    <p class="cp-card-subtitle">Accounts generating negative total revenue.</p>
                                </div>
                                <span class="cp-pill danger">{{ number_format($lossMakingCount) }} accounts</span>
                            </div>
                            @if (count($lossMakers ?? []) > 0)
                                <div class="table-responsive">
                                    <table class="table cp-table">
                                        <thead>
                                            <tr>
                                                <th>Customer</th><th>Seg</th>
                                                <th class="text-end">Net interest</th>
                                                <th class="text-end">Total revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($lossMakers as $l)
                                                <tr>
                                                    <td>
                                                        <div class="cp-customer-cell">
                                                            <span class="cp-avatar">{{ strtoupper(substr($l['name'],0,1)) }}</span>
                                                            <span>
                                                                <span class="cp-name">{{ Str::limit($l['name'],30) }}</span>
                                                                <span class="cp-meta">Requires remediation</span>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge badge-{{ strtolower(str_replace('-','',$l['segment'])) }}">{{ $l['segment'] }}</span></td>
                                                    <td class="text-end cp-negative fw-semibold">${{ number_format($l['interest']/1e3,1) }}K</td>
                                                    <td class="text-end cp-negative fw-bold">${{ number_format($l['revenue']/1e3,1) }}K</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="cp-empty"><strong>No loss makers found</strong>This batch has no loss-making customer relationships.</div>
                            @endif
                        </div>
                    </div>

                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Loss magnitude</h2>
                                    <p class="cp-card-subtitle">Visual severity of negative contributors.</p>
                                </div>
                            </div>
                            @if (count($lossMakers ?? []) > 0)
                                <div class="cp-chart" style="--chart-h:{{ max(260, count($lossMakers) * 36 + 60) }}px;">
                                    <canvas id="cLoss"></canvas>
                                </div>
                            @else
                                <div class="cp-empty"><strong>Nothing to visualize</strong>No negative contributors in this batch.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Revenue Mix tab ─────────────────────────────────── --}}
            <div class="tab-pane cp-tab-pane fade" id="tabMix" role="tabpanel">
                <div class="cp-section-grid">
                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Revenue composition by segment</h2>
                                    <p class="cp-card-subtitle">Net interest, fees, and FX income by segment.</p>
                                </div>
                                <span class="cp-pill">Composition</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-2" id="mixInlineLegend"></div>
                            <div class="cp-chart" style="--chart-h:280px;">
                                <canvas id="cMix"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Overall composition</h2>
                                    <p class="cp-card-subtitle">Portfolio-level revenue type contribution.</p>
                                </div>
                            </div>
                            <div class="cp-chart" style="--chart-h:200px;">
                                <canvas id="cMixD"></canvas>
                            </div>
                            <div class="d-flex flex-column gap-2 mt-2" id="mixLegend"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── RM Performance tab ──────────────────────────────── --}}
            <div class="tab-pane cp-tab-pane fade" id="tabRM" role="tabpanel">
                <div class="cp-two-grid mb-3">
                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Revenue by relationship manager</h2>
                                    <p class="cp-card-subtitle">Revenue ownership by RM portfolio.</p>
                                </div>
                                <span class="cp-pill success">Top: {{ Str::limit($topRmName,18) }}</span>
                            </div>
                            <div class="cp-chart" style="--chart-h:260px;">
                                <canvas id="cRM"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Customer count by RM</h2>
                                    <p class="cp-card-subtitle">Portfolio breadth and customer allocation.</p>
                                </div>
                                <span class="cp-pill">Coverage</span>
                            </div>
                            <div class="cp-chart" style="--chart-h:260px;">
                                <canvas id="cRMc"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cp-section-grid">
                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">Monthly revenue trend by RM</h2>
                                    <p class="cp-card-subtitle">RM momentum and consistency across months.</p>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-2" id="rmLegend"></div>
                            <div class="cp-chart" style="--chart-h:260px;">
                                <canvas id="cRMt"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="cp-card">
                        <div class="cp-card-body">
                            <div class="cp-card-header">
                                <div>
                                    <h2 class="cp-card-title">RM leaderboard</h2>
                                    <p class="cp-card-subtitle">Revenue and relationship count by manager.</p>
                                </div>
                            </div>
                            @if (count($rmPerformance ?? []) > 0)
                                <div class="table-responsive">
                                    <table class="table cp-table">
                                        <thead>
                                            <tr><th>RM</th><th class="text-end">Customers</th><th class="text-end">Revenue</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($rmPerformance as $r)
                                                <tr>
                                                    <td>
                                                        <div class="cp-customer-cell">
                                                            <span class="cp-avatar">{{ strtoupper(substr($r['rm'],0,1)) }}</span>
                                                            <span>
                                                                <span class="cp-name">{{ Str::limit($r['rm'],24) }}</span>
                                                                <span class="cp-meta">Rank #{{ $loop->iteration }}</span>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-semibold">{{ number_format($r['customer_count']) }}</td>
                                                    <td class="text-end fw-bold">${{ number_format($r['total_revenue']/1e6,2) }}M</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="cp-empty"><strong>No RM data</strong>RM data will appear when available in the upload.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
    <script>
    (() => {
        const SC   = ['#0082BB','#669438','#7F77DD','#D85A30','#BA7517'];
        const SEGS = ['RC','MC','IO','FI','PS-CB'];
        const GR   = 'rgba(15,23,42,.07)';
        const TC   = '#64748b';

        const MONTHS = @json($months ?? []);
        const MONTH_LABELS = MONTHS.map(m => {
            const [y,mo] = m.split('-');
            return new Date(+y,+mo-1,1).toLocaleDateString('en',{month:'short'})+'-'+y.slice(2);
        });

        const segmentData      = @json($segmentData ?? []);
        const monthlyTrend     = @json($monthlyTrend ?? []);
        const monthlyBySegment = @json($monthlyBySegment ?? []);
        const topCustomers     = @json($topCustomers ?? []);
        const lossMakers       = @json($lossMakers ?? []);
        const revenueMix       = @json($revenueMix ?? []);
        const rmPerformance    = @json($rmPerformance ?? []);
        const rmMonthly        = @json($rmMonthly ?? []);
        const TOTAL_REVENUE    = {{ $totalRevenue }};
        const SEARCH_URL       = '{{ route("finance.customer_profitability.dashboard.search", $batch->id) }}';

        const money = n => {
            n = Number(n||0); const a = Math.abs(n), s = n<0 ? '-' : '';
            if (a>=1e9) return s+'$'+(a/1e9).toFixed(1)+'B';
            if (a>=1e6) return s+'$'+(a/1e6).toFixed(1)+'M';
            if (a>=1e3) return s+'$'+Math.round(a/1e3)+'K';
            return s+'$'+Math.round(a);
        };
        const millionAxis = v => '$'+Math.round(Number(v||0)/1e6)+'M';
        const pct = (v,t) => t > 0 ? Math.round((v/t)*100) : 0;

        Chart.defaults.font.family = "Inter,system-ui,-apple-system,sans-serif";
        Chart.defaults.color = TC;
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(5,43,79,.94)';
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 10;
        Chart.defaults.plugins.tooltip.titleFont = {weight:'800'};

        const baseGrid = {
            x: { grid:{color:GR,drawBorder:false}, border:{display:false}, ticks:{color:TC,autoSkip:false,maxRotation:0} },
            y: { grid:{color:GR,drawBorder:false}, border:{display:false}, ticks:{color:TC} }
        };

        const mk = (id,cfg) => { const el=document.getElementById(id); return el ? new Chart(el,cfg) : null; };

        const chip = (label,color,val=null,extra='') =>
            `<span class="cp-pill" style="color:${color};background:${color}14;">
                <span style="width:7px;height:7px;border-radius:999px;background:${color};display:inline-block;"></span>
                ${label}${val!==null?' '+money(val):''}${extra}
            </span>`;

        // Segment donut
        const segL = Object.keys(segmentData), segV = Object.values(segmentData).map(Number);
        const segT = segV.reduce((a,b)=>a+b,0);
        mk('cSeg',{type:'doughnut',data:{labels:segL,datasets:[{data:segV,backgroundColor:segL.map((_,i)=>SC[i%SC.length]),borderColor:'#fff',borderWidth:4,hoverOffset:6}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',animation:{animateRotate:true,duration:900},plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.label}: ${money(c.raw)} (${pct(c.raw,segT)}%)`}}}}});
        const segLeg=document.getElementById('segLegend');
        if(segLeg) segLeg.innerHTML=segL.map((s,i)=>chip(s,SC[i%SC.length],segV[i],` · ${pct(segV[i],segT)}%`)).join('');

        // Monthly line
        mk('cMo',{type:'line',data:{labels:MONTH_LABELS,datasets:[{label:'Revenue',data:MONTHS.map(m=>monthlyTrend[m]??0),borderColor:'#0082BB',backgroundColor:ctx=>{const{ctx:c,chartArea:a}=ctx.chart;if(!a)return'rgba(0,130,187,.08)';const g=c.createLinearGradient(0,a.top,0,a.bottom);g.addColorStop(0,'rgba(0,130,187,.2)');g.addColorStop(1,'rgba(0,130,187,.00)');return g;},tension:.38,fill:true,pointRadius:4,pointHoverRadius:6,pointBackgroundColor:'#fff',pointBorderColor:'#0082BB',pointBorderWidth:2,borderWidth:2.5}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${money(c.raw)}`}}},scales:{...baseGrid,y:{...baseGrid.y,ticks:{color:TC,callback:millionAxis}}}}});

        // Monthly stacked by segment
        mk('cMoSeg',{type:'bar',data:{labels:MONTH_LABELS,datasets:SEGS.map((seg,i)=>({label:seg,stack:'s',data:MONTHS.map(m=>monthlyBySegment[seg]?.[m]??0),backgroundColor:SC[i%SC.length],borderWidth:0,borderRadius:i===SEGS.length-1?6:0,borderSkipped:false}))},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.dataset.label}: ${money(c.raw)}`}}},scales:{x:{stacked:true,grid:{display:false},border:{display:false},ticks:{color:TC,autoSkip:false,maxRotation:0}},y:{stacked:true,grid:{color:GR},border:{display:false},ticks:{color:TC,callback:millionAxis}}}}});

        // Top 20 horizontal bar
        const segColorMap={'RC':'#0082BB','MC':'#669438','IO':'#7F77DD','FI':'#D85A30','PS-CB':'#BA7517'};
        mk('cTop',{type:'bar',data:{labels:topCustomers.map(c=>String(c.name||'').length>28?String(c.name).slice(0,28)+'…':c.name),datasets:[{label:'Revenue',data:topCustomers.map(c=>c.revenue),backgroundColor:topCustomers.map(c=>segColorMap[c.segment]||'#94a3b8'),borderWidth:0,borderRadius:6,barThickness:16}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${money(c.raw)} · ${topCustomers[c.dataIndex]?.segment??''}`}}},scales:{x:{grid:{color:GR},border:{display:false},ticks:{color:TC,callback:millionAxis}},y:{grid:{display:false},border:{display:false},ticks:{color:TC,font:{size:10,weight:'700'}}}}}});

        // Loss makers
        if(lossMakers.length) mk('cLoss',{type:'bar',data:{labels:lossMakers.map(l=>String(l.name||'').length>22?String(l.name).slice(0,22)+'…':l.name),datasets:[{label:'Loss',data:lossMakers.map(l=>l.revenue),backgroundColor:lossMakers.map(l=>Number(l.revenue)<-500000?'#dc2626':'#f87171'),borderWidth:0,borderRadius:6,barThickness:18}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${money(c.raw)}`}}},scales:{x:{grid:{color:GR},border:{display:false},ticks:{color:TC,callback:v=>money(v)}},y:{grid:{display:false},border:{display:false},ticks:{color:TC,font:{size:10,weight:'700'}}}}}});

        // Revenue mix
        const mixSegs=Object.keys(revenueMix);
        const mixC={interest:'#0082BB',fees:'#669438',fx:'#7F77DD'};
        const mxIL=document.getElementById('mixInlineLegend');
        if(mxIL) mxIL.innerHTML=[chip('Net interest',mixC.interest),chip('Fees',mixC.fees),chip('FX income',mixC.fx)].join('');
        mk('cMix',{type:'bar',data:{labels:mixSegs,datasets:[{label:'Net interest',stack:'r',data:mixSegs.map(s=>revenueMix[s]?.interest??0),backgroundColor:mixC.interest,borderWidth:0,borderRadius:4,borderSkipped:false},{label:'Fees',stack:'r',data:mixSegs.map(s=>revenueMix[s]?.fees??0),backgroundColor:mixC.fees,borderWidth:0,borderRadius:4,borderSkipped:false},{label:'FX income',stack:'r',data:mixSegs.map(s=>revenueMix[s]?.fx??0),backgroundColor:mixC.fx,borderWidth:0,borderRadius:4,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.dataset.label}: ${money(c.raw)}`}}},scales:{x:{stacked:true,grid:{display:false},border:{display:false},ticks:{color:TC,autoSkip:false}},y:{stacked:true,grid:{color:GR},border:{display:false},ticks:{color:TC,callback:millionAxis}}}}});
        const tI=mixSegs.reduce((a,s)=>a+Number(revenueMix[s]?.interest??0),0);
        const tF=mixSegs.reduce((a,s)=>a+Number(revenueMix[s]?.fees??0),0);
        const tX=mixSegs.reduce((a,s)=>a+Number(revenueMix[s]?.fx??0),0);
        const tM=tI+tF+tX;
        mk('cMixD',{type:'doughnut',data:{labels:['Net interest','Fees','FX income'],datasets:[{data:[tI,tF,tX],backgroundColor:[mixC.interest,mixC.fees,mixC.fx],borderColor:'#fff',borderWidth:4,hoverOffset:6}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.label}: ${money(c.raw)} (${pct(c.raw,tM)}%)`}}}}});
        const mL=document.getElementById('mixLegend');
        if(mL) mL.innerHTML=[['Net interest',mixC.interest,tI],['Fees',mixC.fees,tF],['FX income',mixC.fx,tX]].map(([l,c,v])=>chip(l,c,v,` · ${pct(v,tM)}%`)).join('');

        // RM charts
        mk('cRM',{type:'bar',data:{labels:rmPerformance.map(r=>String(r.rm||'').length>16?String(r.rm).slice(0,16)+'…':r.rm),datasets:[{label:'Revenue',data:rmPerformance.map(r=>r.total_revenue),backgroundColor:rmPerformance.map((_,i)=>SC[i%SC.length]),borderWidth:0,borderRadius:6,barThickness:26}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${money(c.raw)}`}}},scales:{x:{grid:{display:false},border:{display:false},ticks:{color:TC}},y:{grid:{color:GR},border:{display:false},ticks:{color:TC,callback:millionAxis}}}}});
        mk('cRMc',{type:'bar',data:{labels:rmPerformance.map(r=>String(r.rm||'').length>16?String(r.rm).slice(0,16)+'…':r.rm),datasets:[{label:'Customers',data:rmPerformance.map(r=>r.customer_count),backgroundColor:rmPerformance.map((_,i)=>SC[i%SC.length]),borderWidth:0,borderRadius:6,barThickness:26}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.raw} customers`}}},scales:{x:{grid:{display:false},border:{display:false},ticks:{color:TC}},y:{grid:{color:GR},border:{display:false},ticks:{color:TC}}}}});
        const rL=document.getElementById('rmLegend');
        if(rL) rL.innerHTML=rmMonthly.map((r,i)=>chip(r.rm,SC[i%SC.length])).join('');
        mk('cRMt',{type:'line',data:{labels:MONTH_LABELS,datasets:rmMonthly.map((r,i)=>({label:r.rm,data:MONTHS.map(m=>r.months?.[m]??0),borderColor:SC[i%SC.length],backgroundColor:'transparent',tension:.34,pointRadius:3,pointHoverRadius:6,pointBackgroundColor:'#fff',pointBorderWidth:2,borderWidth:2.5,borderDash:i===3?[5,4]:[]}))},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>` ${c.dataset.label}: ${money(c.raw)}`}}},scales:{...baseGrid,y:{...baseGrid.y,ticks:{color:TC,callback:millionAxis}}}}});

        // Resize on tab switch
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(t=>t.addEventListener('shown.bs.tab',()=>window.dispatchEvent(new Event('resize'))));

        // ── Customer Lookup ──────────────────────────────────────────
        let searchResults = [];
        let breakdownChart = null;
        let trendChart = null;

        document.getElementById('custSearchInput').addEventListener('keydown', e => {
            if (e.key === 'Enter') cpSearch();
        });

        window.cpSearch = async function () {
            const q = document.getElementById('custSearchInput').value.trim();
            const status = document.getElementById('searchStatus');
            const layout = document.getElementById('searchLayout');

            if (q.length < 2) { status.textContent = 'Enter at least 2 characters.'; return; }

            status.textContent = 'Searching…';
            layout.style.display = 'none';

            try {
                const res  = await fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
                const data = await res.json();
                searchResults = data;

                if (!data.length) {
                    status.textContent = 'No customers found matching "' + q + '".';
                    return;
                }

                status.textContent = data.length + ' result' + (data.length>1?'s':'') + ' found.';
                layout.style.removeProperty('display');

                const list = document.getElementById('cpResultList');
                list.innerHTML = data.map((c,i) => `
                    <li class="cp-result-item" data-idx="${i}" onclick="cpSelectCustomer(${i})">
                        <span>
                            <div class="cp-result-name">${c.name||'—'}</div>
                            <div class="cp-result-meta">${c.cif||''} · ${c.segment||''} · ${c.rm||''}</div>
                        </span>
                        <span class="cp-result-rev">${money(c.total_revenue)}</span>
                    </li>`).join('');

                if (data.length === 1) cpSelectCustomer(0);
            } catch (e) {
                status.textContent = 'Search failed. Please try again.';
            }
        };

        window.cpSelectCustomer = function(idx) {
            document.querySelectorAll('.cp-result-item').forEach((el,i) => el.classList.toggle('active', i===idx));

            const c = searchResults[idx];
            if (!c) return;

            const contribPct = TOTAL_REVENUE > 0 ? ((c.total_revenue / TOTAL_REVENUE) * 100).toFixed(2) : '0.00';

            document.getElementById('cpDetailPanel').innerHTML = `
                <div class="cp-detail-card mb-3">
                    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                        <div>
                            <div class="cp-detail-name">${c.name||'—'}</div>
                            <div class="cp-detail-sub">CIF: ${c.cif||'—'} &nbsp;·&nbsp; Segment: ${c.segment||'—'} &nbsp;·&nbsp; RM: ${c.rm||'—'}</div>
                        </div>
                        <span class="cp-pill success">${contribPct}% of portfolio</span>
                    </div>
                    <div class="cp-detail-stats">
                        <div class="cp-detail-stat">
                            <div class="cp-detail-stat-val">${money(c.total_revenue)}</div>
                            <div class="cp-detail-stat-lbl">Total Revenue</div>
                        </div>
                        <div class="cp-detail-stat">
                            <div class="cp-detail-stat-val">${money(c.net_interest_income)}</div>
                            <div class="cp-detail-stat-lbl">Net Interest</div>
                        </div>
                        <div class="cp-detail-stat">
                            <div class="cp-detail-stat-val">${money(c.total_fees)}</div>
                            <div class="cp-detail-stat-lbl">Total Fees</div>
                        </div>
                        <div class="cp-detail-stat">
                            <div class="cp-detail-stat-val">${money(c.fx_income)}</div>
                            <div class="cp-detail-stat-lbl">FX Income</div>
                        </div>
                        <div class="cp-detail-stat">
                            <div class="cp-detail-stat-val" style="${Number(c.interest_paid)<0?'color:var(--cp-red)':''}">${money(c.interest_paid)}</div>
                            <div class="cp-detail-stat-lbl">Interest Paid</div>
                        </div>
                    </div>
                </div>

                <div class="cp-detail-card mb-3">
                    <div class="cp-card-title mb-2">Revenue Breakdown</div>
                    <div style="height:220px;position:relative;">
                        <canvas id="cBreakdown"></canvas>
                    </div>
                </div>

                ${Object.keys(c.monthly_trend||{}).length > 0 ? `
                <div class="cp-detail-card">
                    <div class="cp-card-title mb-2">Monthly Revenue Trend</div>
                    <div style="height:180px;position:relative;">
                        <canvas id="cCustTrend"></canvas>
                    </div>
                </div>` : ''}
            `;

            // Revenue breakdown chart
            if (breakdownChart) breakdownChart.destroy();
            const bkLabels = ['Interest Loans','Interest ODs','Interest Trade','Fees & Comm.','Trade Fees','FX Income','Other Income'];
            const bkData   = [c.interest_from_loans, c.interest_from_ods, c.interest_from_trade, c.fees_and_commissions, c.trade_fees, c.fx_income, c.other_income].map(Number);
            const bkColors = bkData.map(v => v < 0 ? '#f87171' : '#0082BB');
            breakdownChart = new Chart(document.getElementById('cBreakdown'), {
                type: 'bar',
                data: {
                    labels: bkLabels,
                    datasets: [{ label: 'Amount', data: bkData, backgroundColor: bkColors, borderWidth: 0, borderRadius: 6, barThickness: 22 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                    plugins: { legend:{display:false}, tooltip:{callbacks:{label:ctx=>` ${money(ctx.raw)}`}} },
                    scales: {
                        x: { grid:{color:'rgba(15,23,42,.07)'}, border:{display:false}, ticks:{color:TC,callback:v=>money(v)} },
                        y: { grid:{display:false}, border:{display:false}, ticks:{color:TC,font:{size:11,weight:'700'}} }
                    }
                }
            });

            // Monthly trend chart (if data)
            const trendMonths = Object.keys(c.monthly_trend||{}).sort();
            if (trendMonths.length > 0 && document.getElementById('cCustTrend')) {
                if (trendChart) trendChart.destroy();
                const trendLabels = trendMonths.map(m => {
                    const [y,mo] = m.split('-');
                    return new Date(+y,+mo-1,1).toLocaleDateString('en',{month:'short'})+'-'+y.slice(2);
                });
                trendChart = new Chart(document.getElementById('cCustTrend'), {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [{
                            label: 'Revenue', data: trendMonths.map(m=>c.monthly_trend[m]),
                            borderColor:'#0082BB', backgroundColor:'rgba(0,130,187,.10)',
                            tension:.38, fill:true, pointRadius:4, pointHoverRadius:6,
                            pointBackgroundColor:'#fff', pointBorderColor:'#0082BB',
                            pointBorderWidth:2, borderWidth:2.5
                        }]
                    },
                    options: {
                        responsive:true, maintainAspectRatio:false,
                        plugins: { legend:{display:false}, tooltip:{callbacks:{label:c=>` ${money(c.raw)}`}} },
                        scales: {
                            x: { grid:{display:false}, border:{display:false}, ticks:{color:TC} },
                            y: { grid:{color:'rgba(15,23,42,.07)'}, border:{display:false}, ticks:{color:TC,callback:millionAxis} }
                        }
                    }
                });
            }
        };
    })();
    </script>
@endpush
