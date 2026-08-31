@extends('layouts.finance.template')

@section('title', 'RM Performance')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --eco-blue: #0082BB;
            --eco-dark-blue: #004d6e;
            --eco-deep-blue: #00364f;
            --eco-green: #639922;
            --eco-bg: #eef4f8;
            --eco-card: #ffffff;
            --eco-text: #1e293b;
            --eco-muted: #64748b;
            --eco-border: rgba(0, 91, 130, 0.10);
            --eco-shadow: 0 16px 35px rgba(15, 23, 42, 0.08);
            --success: #15803d;
            --success-soft: rgba(21,128,61,0.10);
            --danger: #dc2626;
            --danger-soft: rgba(220,38,38,0.10);
            --warn: #b45309;
            --warn-soft: rgba(180,83,9,0.10);
        }

        body {
            background:
                radial-gradient(circle at top left, rgba(0, 130, 187, 0.08), transparent 30%),
                linear-gradient(180deg, #f4f8fb 0%, var(--eco-bg) 100%);
        }

        .rmp-page { padding: 10px 14px 26px; }

        /* ── Hero ─────────────────────────────────── */
        .rmp-hero {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            background:
                radial-gradient(circle at top right, rgba(99, 153, 34, 0.35), transparent 30%),
                linear-gradient(135deg, var(--eco-deep-blue), var(--eco-dark-blue) 48%, #006e9e);
            padding: 22px 22px 16px;
            margin-bottom: 12px;
            box-shadow: 0 22px 45px rgba(0, 77, 110, 0.23);
            color: #fff;
        }

        .rmp-hero-main {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 18px; flex-wrap: wrap; margin-bottom: 18px;
        }

        .rmp-hero-title-wrap { display: flex; align-items: center; gap: 14px; min-width: 280px; }

        .rmp-hero-icon {
            width: 52px; height: 52px; border-radius: 16px;
            background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.18);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; flex-shrink: 0;
        }

        .rmp-hero-eyebrow {
            font-size: 0.72rem; font-weight: 800; letter-spacing: 0.12em;
            text-transform: uppercase; color: rgba(255,255,255,0.68); margin-bottom: 4px;
        }

        .rmp-hero-title { font-size: clamp(1.35rem, 2vw, 2rem); line-height: 1.1; font-weight: 900; letter-spacing: -0.04em; margin: 0; }

        .rmp-hero-sub { margin-top: 6px; font-size: 0.84rem; color: rgba(255,255,255,0.72); display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

        .rmp-hero-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        .rmp-year-select {
            border-radius: 999px; padding: 8px 13px; font-size: 0.78rem; font-weight: 800;
            background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.22); color: #fff;
        }
        .rmp-year-select option { color: #1e293b; }

        .rmp-hero-btn {
            display: inline-flex; align-items: center; gap: 7px;
            border-radius: 999px; padding: 8px 13px; font-size: 0.74rem; font-weight: 800;
            background: rgba(255,255,255,0.95); color: var(--eco-dark-blue);
            border: 1px solid rgba(255,255,255,0.48); cursor: pointer; white-space: nowrap;
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }
        .rmp-hero-btn:hover { transform: translateY(-1px); box-shadow: 0 12px 24px rgba(0,0,0,0.14); }
        .rmp-hero-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }

        .rmp-hero-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }

        .rmp-kpi {
            position: relative; overflow: hidden;
            background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.16);
            border-radius: 16px; padding: 14px 15px 13px 15px;
            border-top: 3px solid var(--kpi-accent, rgba(255,255,255,0.4));
        }

        .rmp-kpi::after {
            content: ""; position: absolute; top: -30px; right: -30px;
            width: 90px; height: 90px; border-radius: 50%;
            background: var(--kpi-accent, transparent); opacity: 0.16; pointer-events: none;
        }

        .rmp-kpi-icon {
            position: absolute; top: 12px; right: 13px;
            width: 30px; height: 30px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            background: var(--kpi-accent, rgba(255,255,255,0.16)); color: #fff; font-size: 0.82rem;
            box-shadow: 0 6px 14px rgba(0,0,0,0.18);
        }

        .rmp-kpi--deposits { --kpi-accent: #22c1e0; }
        .rmp-kpi--loans    { --kpi-accent: #ff9f43; }
        .rmp-kpi--ntb      { --kpi-accent: #b98af6; }
        .rmp-kpi--rms      { --kpi-accent: #2ee6a6; }

        .rmp-kpi-label { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,0.68); margin-bottom: 6px; max-width: 82%; display: flex; align-items: center; gap: 5px; }
        .rmp-kpi-value { font-size: 1.32rem; font-weight: 900; font-family: 'DM Mono', monospace; letter-spacing: -0.02em; }
        .rmp-kpi-sub { margin-top: 4px; font-size: 0.72rem; color: rgba(255,255,255,0.72); font-family: 'DM Mono', monospace; }

        .rmp-info-icon { cursor: help; opacity: 0.75; }

        @media (max-width: 900px) {
            .rmp-hero-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        /* ── Toolbar ──────────────────────────────── */
        .rmp-toolbar {
            background: var(--eco-card); border-radius: 14px; padding: 12px 14px;
            box-shadow: var(--eco-shadow); border: 1px solid var(--eco-border);
            margin-bottom: 14px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        }

        .rmp-search {
            border: 1px solid #d0dce8; border-radius: 9px; padding: 7px 10px;
            font-size: 0.82rem; color: var(--eco-text); min-height: 34px; min-width: 240px; flex: 1;
        }
        .rmp-search:focus { outline: none; border-color: var(--eco-blue); box-shadow: 0 0 0 3px rgba(0,130,187,0.12); }

        .rmp-filter-select {
            border: 1px solid #d0dce8; border-radius: 9px; padding: 7px 10px;
            font-size: 0.8rem; color: var(--eco-text); min-height: 34px; background: #fff;
        }
        .rmp-filter-select:focus { outline: none; border-color: var(--eco-blue); }

        .rmp-toolbar-note { margin-left: auto; font-size: 0.72rem; color: var(--eco-muted); font-weight: 700; }

        .rmp-legend { display: flex; align-items: center; gap: 10px; font-size: 0.7rem; color: var(--eco-muted); font-weight: 700; flex-wrap: wrap; }
        .rmp-legend span { display: inline-flex; align-items: center; gap: 5px; }
        .rmp-legend-label {
            font-size: 0.66rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em;
            color: var(--eco-dark-blue); padding-right: 4px; border-right: 1px solid var(--eco-border);
        }
        .rmp-legend-hint { font-style: italic; color: var(--eco-muted); }

        /* ── Table panel ──────────────────────────── */
        .rmp-panel {
            background: var(--eco-card); border-radius: 14px; box-shadow: var(--eco-shadow);
            border: 1px solid var(--eco-border); overflow: hidden;
        }

        .rmp-table-scroll { overflow-x: auto; overflow-y: auto; max-height: 560px; }

        .rmp-table { width: 100%; border-collapse: collapse; font-size: 0.79rem; }

        .rmp-table th {
            position: sticky; top: 0; z-index: 2;
            background: var(--eco-dark-blue); color: #fff; padding: 9px 11px; text-align: right;
            font-size: 0.66rem; font-weight: 900; text-transform: uppercase; letter-spacing: .3px; white-space: nowrap;
        }
        .rmp-table th.text-left { text-align: left; }

        .rmp-table td { padding: 9px 11px; border-bottom: 1px solid #f0f4f8; color: var(--eco-text); vertical-align: middle; text-align: right; white-space: nowrap; }
        .rmp-table td.text-left { text-align: left; }

        .rmp-table tbody tr { cursor: pointer; transition: background .12s; }
        .rmp-table tbody tr:hover td { background: #f5f9fc; }
        .rmp-table tr:last-child td { border-bottom: none; }

        .rmp-table tbody tr.rmp-branch-row { cursor: default; }
        .rmp-table tbody tr.rmp-branch-row td {
            background: #eef4f8; padding: 8px 11px; text-align: left; white-space: normal;
            border-bottom: 1px solid var(--eco-border); border-top: 1px solid var(--eco-border);
        }
        .rmp-table tbody tr.rmp-branch-row:hover td { background: #eef4f8; }
        .rmp-table tbody tr.rmp-branch-row:first-child td { border-top: none; }
        .rmp-branch-name { font-size: 0.78rem; font-weight: 900; color: var(--eco-dark-blue); }
        .rmp-branch-name i { margin-right: 6px; color: var(--eco-blue); }
        .rmp-branch-meta { margin-left: 12px; font-size: 0.72rem; font-weight: 700; color: var(--eco-muted); font-family: 'DM Mono', monospace; }

        .rmp-table tfoot td {
            position: sticky; bottom: 0; z-index: 1;
            background: #eef4f7; font-weight: 900; color: var(--eco-dark-blue);
            border-top: 2px solid var(--eco-border);
        }

        .badge-code {
            display: inline-block; background: rgba(0,91,130,0.1); color: var(--eco-dark-blue);
            border-radius: 7px; padding: 3px 9px; font-size: 0.72rem; font-weight: 950;
            font-family: 'DM Mono', monospace; white-space: nowrap;
        }

        .badge-segment {
            display: inline-block; border-radius: 7px; padding: 2px 8px;
            font-size: 0.68rem; font-weight: 900; white-space: nowrap;
        }

        .rm-name-cell { font-weight: 700; }
        .badge-none { color: var(--eco-muted); font-style: italic; font-size: 0.74rem; }

        .rmp-move-up   { color: var(--success); font-weight: 900; }
        .rmp-move-down { color: var(--danger); font-weight: 900; }
        .rmp-move-flat { color: var(--eco-muted); }

        .rmp-rank {
            display: inline-block; margin-left: 5px; padding: 1px 6px; border-radius: 999px;
            font-size: 0.62rem; font-weight: 800; font-family: 'DM Mono', monospace; white-space: nowrap;
        }
        .rmp-rank-top    { background: var(--success-soft); color: var(--success); }
        .rmp-rank-mid    { background: rgba(100,116,139,0.12); color: var(--eco-muted); }
        .rmp-rank-bottom { background: var(--danger-soft); color: var(--danger); }

        .empty-row td, .loading-row td { text-align: center; padding: 30px; white-space: normal; }
        .empty-icon { font-size: 1.4rem; color: rgba(0,91,130,0.22); margin-bottom: 6px; }
        .empty-title { font-size: 0.88rem; font-weight: 900; color: var(--eco-dark-blue); }
        .spinner {
            display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(0,91,130,0.16);
            border-top-color: var(--eco-dark-blue); border-radius: 50%; animation: spin .65s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Drilldown drawer ──────────────────────── */
        .rmp-drawer-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 1800;
            align-items: center; justify-content: flex-end;
        }
        .rmp-drawer-overlay.open { display: flex; }

        .rmp-drawer {
            background: #fff; width: min(480px, 94vw); height: 100%;
            box-shadow: -18px 0 45px rgba(0,0,0,0.18); overflow-y: auto;
            display: flex; flex-direction: column;
        }

        .rmp-drawer-header {
            padding: 18px 20px; border-bottom: 1px solid #edf2f7;
            display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
            background: linear-gradient(90deg, rgba(0,91,130,0.05), transparent);
        }

        .rmp-drawer-title { font-size: 1rem; font-weight: 950; color: var(--eco-dark-blue); margin: 0; }
        .rmp-drawer-sub { margin-top: 3px; font-size: 0.76rem; color: var(--eco-muted); font-weight: 700; }

        .rmp-drawer-close { background: transparent; border: none; font-size: 1.1rem; cursor: pointer; color: var(--eco-muted); width: 32px; height: 32px; border-radius: 10px; }
        .rmp-drawer-close:hover { background: #f2f4f7; color: var(--eco-dark-blue); }

        .rmp-drawer-body { padding: 18px 20px 26px; }

        .rmp-drawer-chart-wrap { position: relative; height: 260px; margin-bottom: 18px; }

        .rmp-drawer-stat { display: flex; align-items: center; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #f0f4f8; font-size: 0.82rem; }
        .rmp-drawer-stat:last-child { border-bottom: none; }
        .rmp-drawer-stat-label { color: var(--eco-muted); font-weight: 700; }
        .rmp-drawer-stat-value { font-family: 'DM Mono', monospace; font-weight: 900; color: var(--eco-text); }

        .rmp-drawer-note {
            margin-top: 16px; padding: 10px 12px; border-radius: 10px;
            background: rgba(180,83,9,0.08); border: 1px solid rgba(180,83,9,0.18);
            font-size: 0.72rem; color: var(--warn); font-weight: 600; line-height: 1.4;
        }
    </style>
@endpush

@section('content')

<div class="rmp-page">

    {{-- Hero --}}
    <div class="rmp-hero">
        <div class="rmp-hero-main">
            <div class="rmp-hero-title-wrap">
                <div class="rmp-hero-icon"><i class="fa-solid fa-chart-column"></i></div>
                <div>
                    <div class="rmp-hero-eyebrow">Relationship Managers</div>
                    <h1 class="rmp-hero-title">RM Performance</h1>
                    <div class="rmp-hero-sub">
                        <span>Deposits mobilized, loans disbursed &amp; NTB accounts — monthly and YTD</span>
                    </div>
                </div>
            </div>

            <div class="rmp-hero-actions">
                <select id="year-select" class="rmp-year-select" onchange="loadYear()"></select>
                <button type="button" class="rmp-hero-btn" id="rebuild-btn" onclick="rebuild()">
                    <i class="fa-solid fa-rotate"></i> Rebuild Data
                </button>
            </div>
        </div>

        <div class="rmp-hero-kpis">
            <div class="rmp-kpi rmp-kpi--deposits">
                <div class="rmp-kpi-icon"><i class="fa-solid fa-piggy-bank"></i></div>
                <div class="rmp-kpi-label">Deposits Mobilized (YTD)</div>
                <div class="rmp-kpi-value" id="kpi-deposits">—</div>
                <div class="rmp-kpi-sub" id="kpi-deposits-sub">—</div>
            </div>
            <div class="rmp-kpi rmp-kpi--loans">
                <div class="rmp-kpi-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="rmp-kpi-label">
                    Loans Disbursed (YTD)
                    <i class="fa-solid fa-circle-info rmp-info-icon"
                       title="Approximated from each loan's latest outstanding balance, bucketed by its value date — not a true disbursement feed."></i>
                </div>
                <div class="rmp-kpi-value" id="kpi-loans">—</div>
                <div class="rmp-kpi-sub">≈ proxy, see info icon</div>
            </div>
            <div class="rmp-kpi rmp-kpi--ntb">
                <div class="rmp-kpi-icon"><i class="fa-solid fa-user-plus"></i></div>
                <div class="rmp-kpi-label">NTB Accounts Opened (YTD)</div>
                <div class="rmp-kpi-value" id="kpi-ntb">—</div>
                <div class="rmp-kpi-sub">New-to-bank, this year</div>
            </div>
            <div class="rmp-kpi rmp-kpi--rms">
                <div class="rmp-kpi-icon"><i class="fa-solid fa-users"></i></div>
                <div class="rmp-kpi-label">RMs Tracked</div>
                <div class="rmp-kpi-value">{{ $trackedCount }}</div>
                <div class="rmp-kpi-sub">{{ $untrackedCount }} without data yet</div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="rmp-toolbar">
        <input type="text" id="search-input" class="rmp-search" placeholder="Search by RM code or name…" oninput="applyFilters()">

        <select id="branch-filter" class="rmp-filter-select" onchange="applyFilters()">
            <option value="">All Branches</option>
        </select>

        <select id="segment-filter" class="rmp-filter-select" onchange="onSegmentChange()">
            <option value="">All Segments</option>
        </select>

        <select id="subsegment-filter" class="rmp-filter-select" onchange="applyFilters()">
            <option value="">All Sub-segments</option>
        </select>

        <div class="rmp-legend">
            <span class="rmp-legend-label">Rank</span>
            <span><span class="rmp-rank rmp-rank-top">#1/5</span> Top third</span>
            <span><span class="rmp-rank rmp-rank-mid">#3/5</span> Middle</span>
            <span><span class="rmp-rank rmp-rank-bottom">#5/5</span> Bottom third</span>
            <span class="rmp-legend-hint">— within the RM's own branch</span>
        </div>

        <div class="rmp-toolbar-note" id="toolbar-note">—</div>
    </div>

    {{-- Table --}}
    <div class="rmp-panel">
        <div class="rmp-table-scroll">
            <table class="rmp-table">
                <thead>
                    <tr>
                        <th class="text-left">RM</th>
                        <th class="text-left">Branch</th>
                        <th class="text-left">Segment</th>
                        <th class="text-left">Sub-segment</th>
                        <th>Deposits — This Month</th>
                        <th>Deposits — YTD</th>
                        <th title="Total deposit balance currently under this RM, as of the latest available snapshot — not a movement.">Deposits — Portfolio</th>
                        <th>Loans Disbursed — This Month</th>
                        <th>Loans Disbursed — YTD</th>
                        <th>NTB — This Month</th>
                        <th>NTB — YTD</th>
                    </tr>
                </thead>
                <tbody id="rmp-tbody">
                    <tr class="loading-row"><td colspan="11"><span class="spinner"></span> Loading…</td></tr>
                </tbody>
                <tfoot id="rmp-tfoot" style="display:none;">
                    <tr>
                        <td class="text-left" colspan="4">Total (RMs with data)</td>
                        <td>—</td>
                        <td id="foot-deposits-ytd"></td>
                        <td id="foot-deposits-portfolio"></td>
                        <td>—</td>
                        <td id="foot-loans-ytd"></td>
                        <td>—</td>
                        <td id="foot-ntb-ytd"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

{{-- ─── Drilldown drawer ─── --}}
<div class="rmp-drawer-overlay" id="drawer-overlay" onclick="if(event.target===this)closeDrawer()">
    <div class="rmp-drawer">
        <div class="rmp-drawer-header">
            <div>
                <h3 class="rmp-drawer-title" id="drawer-title">RM</h3>
                <div class="rmp-drawer-sub" id="drawer-sub">—</div>
            </div>
            <button class="rmp-drawer-close" onclick="closeDrawer()">&#x2715;</button>
        </div>
        <div class="rmp-drawer-body">
            <div class="rmp-drawer-chart-wrap">
                <canvas id="drawer-chart"></canvas>
            </div>
            <div id="drawer-stats"></div>
            <div class="rmp-drawer-note">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Loans Disbursed is approximated from each loan's latest outstanding balance,
                bucketed by its value date — repayments made since disbursement are not netted out
                separately, and it is not a true disbursement feed.
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    const rows = @json($rows);
    const currentYear = {{ $year }};
    const totals = @json($totals);
    const snapshotDates = @json($snapshotDates);
    let filteredRows = rows;
    let drawerChart  = null;

    const $ = id => document.getElementById(id);

    function fmtMoney(v) {
        const n = Number(v || 0);
        const sign = n < 0 ? '-' : '';
        const abs = Math.abs(n);
        if (abs >= 1e9) return sign + (abs / 1e9).toFixed(2) + 'B';
        if (abs >= 1e6) return sign + (abs / 1e6).toFixed(2) + 'M';
        if (abs >= 1e3) return sign + (abs / 1e3).toFixed(1) + 'K';
        return sign + abs.toLocaleString();
    }

    function fmtFull(v) { return Number(v || 0).toLocaleString('en-KE', { maximumFractionDigits: 0 }); }

    function escHtml(v) {
        return String(v ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
    }

    function moveCell(v, formatter) {
        if (v === null || v === undefined) return '<span class="badge-none">—</span>';
        const n = Number(v);
        const cls = n > 0 ? 'rmp-move-up' : (n < 0 ? 'rmp-move-down' : 'rmp-move-flat');
        return `<span class="${cls}">${formatter(n)}</span>`;
    }

    /* ─── Rank badge — rank is within the RM's own branch, not bank-wide ─── */
    function rankBadge(rank, total) {
        if (!rank || !total || total <= 1) return '';
        const topCut = Math.max(1, Math.ceil(total / 3));
        const bottomCut = total - topCut + 1;
        const cls = rank <= topCut ? 'rmp-rank-top' : (rank >= bottomCut ? 'rmp-rank-bottom' : 'rmp-rank-mid');
        return `<span class="rmp-rank ${cls}" title="Ranked among ${total} RM(s) in this branch">#${rank}/${total}</span>`;
    }

    /* ─── Colourful, deterministic segment/sub-segment badge palette ─── */
    const SEGMENT_PALETTE = [
        { bg: 'rgba(0,130,187,0.15)',   fg: '#005b82' },
        { bg: 'rgba(99,153,34,0.15)',   fg: '#4d7028' },
        { bg: 'rgba(255,159,67,0.18)',  fg: '#b45309' },
        { bg: 'rgba(185,138,246,0.18)', fg: '#7c3aed' },
        { bg: 'rgba(46,230,166,0.18)',  fg: '#0f8a63' },
        { bg: 'rgba(236,72,153,0.15)',  fg: '#be185d' },
        { bg: 'rgba(34,193,224,0.18)',  fg: '#0e7490' },
        { bg: 'rgba(220,38,38,0.13)',   fg: '#b91c1c' },
    ];

    function hashStr(str) {
        let h = 0;
        for (let i = 0; i < str.length; i++) h = (h * 31 + str.charCodeAt(i)) >>> 0;
        return h;
    }

    function segmentBadge(value, subtle) {
        if (!value) return '<span class="badge-none">—</span>';
        const palette = SEGMENT_PALETTE[hashStr(String(value)) % SEGMENT_PALETTE.length];
        const style = subtle
            ? `background:transparent;color:${palette.fg};border:1.5px solid ${palette.fg};`
            : `background:${palette.bg};color:${palette.fg};`;
        return `<span class="badge-segment" style="${style}">${escHtml(value)}</span>`;
    }

    function initYearSelect() {
        const sel = $('year-select');
        const now = new Date().getFullYear();
        const years = [];
        for (let y = now + 1; y >= now - 4; y--) years.push(y);
        if (!years.includes(currentYear)) years.unshift(currentYear);
        sel.innerHTML = years.map(y => `<option value="${y}" ${y === currentYear ? 'selected' : ''}>${y}</option>`).join('');
    }

    function loadYear() {
        const year = $('year-select').value;
        if (parseInt(year, 10) !== currentYear) {
            window.location.href = `${window.location.pathname}?year=${encodeURIComponent(year)}`;
        }
    }

    function populateBranchFilter() {
        const select = $('branch-filter');
        const current = select.value;

        const branches = [...new Map(
            rows
                .filter(r => r.branch_code)
                .map(r => [r.branch_code, r.branch_name || r.branch_code])
        ).entries()].sort((a, b) => a[1].localeCompare(b[1]));

        select.innerHTML = '<option value="">All Branches</option>' +
            branches.map(([code, name]) => `<option value="${escHtml(code)}" ${code === current ? 'selected' : ''}>${escHtml(name)}</option>`).join('');
    }

    function populateSegmentFilter() {
        const select = $('segment-filter');
        const current = select.value;

        const segments = [...new Set(rows.map(r => String(r.segment || '').trim()).filter(Boolean))]
            .sort((a, b) => a.localeCompare(b));

        select.innerHTML = '<option value="">All Segments</option>' +
            segments.map(s => `<option value="${escHtml(s)}" ${s === current ? 'selected' : ''}>${escHtml(s)}</option>`).join('');

        populateSubsegmentFilter();
    }

    function populateSubsegmentFilter() {
        const segmentValue = $('segment-filter').value;
        const select = $('subsegment-filter');
        const current = select.value;

        const source = segmentValue ? rows.filter(r => String(r.segment || '') === segmentValue) : rows;
        const subsegments = [...new Set(source.map(r => String(r.subsegment || '').trim()).filter(Boolean))]
            .sort((a, b) => a.localeCompare(b));

        select.innerHTML = '<option value="">All Sub-segments</option>' +
            subsegments.map(s => `<option value="${escHtml(s)}" ${s === current ? 'selected' : ''}>${escHtml(s)}</option>`).join('');

        if (current && !subsegments.includes(current)) select.value = '';
    }

    function onSegmentChange() {
        populateSubsegmentFilter();
        applyFilters();
    }

    function applyFilters() {
        const search = ($('search-input').value || '').trim().toLowerCase();
        const branch = $('branch-filter').value;
        const segment = $('segment-filter').value;
        const subsegment = $('subsegment-filter').value;

        filteredRows = rows.filter(r => {
            if (branch && String(r.branch_code || '') !== branch) return false;
            if (segment && String(r.segment || '') !== segment) return false;
            if (subsegment && String(r.subsegment || '') !== subsegment) return false;

            return !search ||
                (r.rm_code || '').toLowerCase().includes(search) ||
                (r.name || '').toLowerCase().includes(search);
        });
        renderTable();
    }

    function renderTable() {
        if (!filteredRows.length) {
            $('rmp-tbody').innerHTML = `
                <tr class="empty-row">
                    <td colspan="11">
                        <div class="empty-icon"><i class="fa-regular fa-folder-open"></i></div>
                        <div class="empty-title">No RMs match your filters</div>
                    </td>
                </tr>`;
            $('rmp-tfoot').style.display = 'none';
            return;
        }

        // Group by branch (in filteredRows order, which is already ytd_deposit_movement desc
        // from the server, so within-branch order lines up with the rank badges). Each row
        // keeps its index into filteredRows so openDrawer(i) still resolves correctly.
        const groups = new Map();
        filteredRows.forEach((r, i) => {
            const key = r.branch_name || '__unassigned__';
            if (!groups.has(key)) groups.set(key, { name: r.branch_name || 'Unassigned', items: [] });
            groups.get(key).items.push({ r, i });
        });

        const sortedGroups = [...groups.values()].sort((a, b) => {
            if (a.name === 'Unassigned') return 1;
            if (b.name === 'Unassigned') return -1;
            return a.name.localeCompare(b.name);
        });

        const showHeaders = sortedGroups.length > 1;

        $('rmp-tbody').innerHTML = sortedGroups.map(group => {
            const rowsHtml = group.items.map(({ r, i }) => rmRowHtml(r, i)).join('');

            if (!showHeaders) return rowsHtml;

            const rmCount = group.items.length;
            const portfolioTotal = group.items.reduce((sum, { r }) => sum + Number(r.deposit_portfolio || 0), 0);
            const ytdTotal = group.items.reduce((sum, { r }) => sum + Number(r.ytd_deposit_movement || 0), 0);

            const headerRow = `
                <tr class="rmp-branch-row">
                    <td colspan="11">
                        <span class="rmp-branch-name"><i class="fa-solid fa-building-columns"></i> ${escHtml(group.name)}</span>
                        <span class="rmp-branch-meta">${rmCount} RM${rmCount === 1 ? '' : 's'} · Portfolio ${fmtMoney(portfolioTotal)} · Deposits YTD ${fmtMoney(ytdTotal)}</span>
                    </td>
                </tr>`;

            return headerRow + rowsHtml;
        }).join('');

        $('rmp-tfoot').style.display = '';
    }

    function rmRowHtml(r, i) {
        return `
            <tr onclick="openDrawer(${i})">
                <td class="text-left">
                    <span class="badge-code">${escHtml(r.rm_code)}</span>
                    <div class="rm-name-cell">${escHtml(r.name)}</div>
                </td>
                <td class="text-left">${r.branch_name ? escHtml(r.branch_name) : '<span class="badge-none">Unassigned</span>'}</td>
                <td class="text-left">${segmentBadge(r.segment)}</td>
                <td class="text-left">${segmentBadge(r.subsegment, true)}</td>
                <td>${moveCell(r.month_deposit_movement, fmtMoney)}</td>
                <td>${moveCell(r.ytd_deposit_movement, fmtMoney)}${rankBadge(r.deposit_rank, r.deposit_rank_total)}</td>
                <td>${r.has_data ? fmtMoney(r.deposit_portfolio) : '<span class="badge-none">No data</span>'}</td>
                <td>${r.has_data ? fmtMoney(r.month_loan_disbursed) : '<span class="badge-none">No data</span>'}</td>
                <td>${r.has_data ? fmtMoney(r.ytd_loan_disbursed) : '<span class="badge-none">No data</span>'}${rankBadge(r.loan_rank, r.loan_rank_total)}</td>
                <td>${r.has_data ? fmtFull(r.month_ntb) : '<span class="badge-none">No data</span>'}</td>
                <td>${r.has_data ? fmtFull(r.ytd_ntb) : '<span class="badge-none">No data</span>'}${rankBadge(r.ntb_rank, r.ntb_rank_total)}</td>
            </tr>`;
    }

    function renderKpis() {
        $('kpi-deposits').textContent = fmtMoney(totals.deposit_movement);
        $('kpi-deposits-sub').textContent = `Net movement Jan–${monthName(maxLatestMonth())} · Portfolio ${fmtMoney(totals.deposit_portfolio)}`;
        $('kpi-loans').textContent = fmtMoney(totals.loan_disbursed);
        $('kpi-ntb').textContent = fmtFull(totals.ntb);

        $('foot-deposits-ytd').textContent = fmtMoney(totals.deposit_movement);
        $('foot-deposits-portfolio').textContent = fmtMoney(totals.deposit_portfolio);
        $('foot-loans-ytd').textContent = fmtMoney(totals.loan_disbursed);
        $('foot-ntb-ytd').textContent = fmtFull(totals.ntb);

        const bal = snapshotDates.balance ? new Date(snapshotDates.balance).toLocaleDateString('en-KE') : '—';
        const loan = snapshotDates.loan ? new Date(snapshotDates.loan).toLocaleDateString('en-KE') : '—';
        $('toolbar-note').textContent = `Balances as of ${bal} · Loans as of ${loan}`;
    }

    function maxLatestMonth() {
        return rows.reduce((max, r) => r.latest_month && r.latest_month > max ? r.latest_month : max, 0) || new Date().getMonth() + 1;
    }

    function monthName(m) {
        const names = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return names[m] || '';
    }

    /* ─── Drilldown drawer ─── */
    async function openDrawer(index) {
        const r = filteredRows[index];
        if (!r) return;

        $('drawer-title').textContent = `${r.rm_code} — ${r.name}`;
        const subParts = [r.segment, r.branch_name, String(currentYear)].filter(Boolean);
        $('drawer-sub').textContent = subParts.join(' · ');

        $('drawer-stats').innerHTML = `
            <div class="rmp-drawer-stat"><span class="rmp-drawer-stat-label">Deposits — This Month</span><span class="rmp-drawer-stat-value">${moveCell(r.month_deposit_movement, fmtMoney)}</span></div>
            <div class="rmp-drawer-stat"><span class="rmp-drawer-stat-label">Deposits — YTD</span><span class="rmp-drawer-stat-value">${moveCell(r.ytd_deposit_movement, fmtMoney)}${rankBadge(r.deposit_rank, r.deposit_rank_total)}</span></div>
            <div class="rmp-drawer-stat"><span class="rmp-drawer-stat-label">Deposits — Portfolio</span><span class="rmp-drawer-stat-value">${fmtMoney(r.deposit_portfolio)}</span></div>
            <div class="rmp-drawer-stat"><span class="rmp-drawer-stat-label">Loans Disbursed — This Month</span><span class="rmp-drawer-stat-value">${fmtMoney(r.month_loan_disbursed)}</span></div>
            <div class="rmp-drawer-stat"><span class="rmp-drawer-stat-label">Loans Disbursed — YTD</span><span class="rmp-drawer-stat-value">${fmtMoney(r.ytd_loan_disbursed)}${rankBadge(r.loan_rank, r.loan_rank_total)}</span></div>
            <div class="rmp-drawer-stat"><span class="rmp-drawer-stat-label">NTB — This Month</span><span class="rmp-drawer-stat-value">${fmtFull(r.month_ntb)}</span></div>
            <div class="rmp-drawer-stat"><span class="rmp-drawer-stat-label">NTB — YTD</span><span class="rmp-drawer-stat-value">${fmtFull(r.ytd_ntb)}${rankBadge(r.ntb_rank, r.ntb_rank_total)}</span></div>
        `;

        $('drawer-overlay').classList.add('open');

        if (drawerChart) { drawerChart.destroy(); drawerChart = null; }

        try {
            const res = await fetch(`{{ route('finance.rm-performance.trend') }}?rm_code=${encodeURIComponent(r.rm_code)}&months=24`);
            const json = await res.json();
            if (!json.success) return;

            const labels = json.series.map(s => `${monthName(s.month)} ${String(s.year).slice(2)}`);
            const canvas = $('drawer-chart');

            drawerChart = new Chart(canvas, {
                data: {
                    labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Deposits Movement',
                            data: json.series.map(s => s.deposit_movement),
                            backgroundColor: 'rgba(0,130,187,0.75)',
                            borderRadius: 4,
                            yAxisID: 'y',
                        },
                        {
                            type: 'bar',
                            label: 'Loans Disbursed',
                            data: json.series.map(s => s.loan_disbursed_proxy),
                            backgroundColor: 'rgba(255,159,67,0.75)',
                            borderRadius: 4,
                            yAxisID: 'y',
                        },
                        {
                            type: 'line',
                            label: 'NTB Accounts',
                            data: json.series.map(s => s.ntb_count),
                            borderColor: 'rgba(185,138,246,0.95)',
                            backgroundColor: 'rgba(185,138,246,0.2)',
                            yAxisID: 'y1',
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y:  { beginAtZero: true, title: { display: true, text: 'KES' } },
                        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'NTB count' } },
                    },
                    plugins: { legend: { position: 'bottom' } },
                },
            });
        } catch (e) {
            // Chart is best-effort — stats above still render.
        }
    }

    function closeDrawer() {
        $('drawer-overlay').classList.remove('open');
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });

    /* ─── Rebuild ─── */
    async function rebuild() {
        const btn = $('rebuild-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" style="width:12px;height:12px;border-width:2px;"></span> Rebuilding…';

        try {
            const res = await fetch(`{{ route('finance.rm-performance.build') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
            const json = await res.json();

            if (json.success) {
                window.location.reload();
                return;
            }

            alert(json.message || 'Rebuild failed.');
        } catch (e) {
            alert('Rebuild failed: ' + e.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Rebuild Data';
        }
    }

    /* ─── Init ─── */
    document.addEventListener('DOMContentLoaded', () => {
        initYearSelect();
        populateBranchFilter();
        populateSegmentFilter();
        renderKpis();
        applyFilters();
    });
</script>
@endpush
