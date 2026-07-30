@extends('layouts.finance.template')

@section('title', 'RM Targets')

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

        .rmt-page { padding: 10px 14px 26px; }

        /* ── Hero ─────────────────────────────────── */
        .rmt-hero {
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

        .rmt-hero-main {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 18px; flex-wrap: wrap; margin-bottom: 18px;
        }

        .rmt-hero-title-wrap { display: flex; align-items: center; gap: 14px; min-width: 280px; }

        .rmt-hero-icon {
            width: 52px; height: 52px; border-radius: 16px;
            background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.18);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; flex-shrink: 0;
        }

        .rmt-hero-eyebrow {
            font-size: 0.72rem; font-weight: 800; letter-spacing: 0.12em;
            text-transform: uppercase; color: rgba(255,255,255,0.68); margin-bottom: 4px;
        }

        .rmt-hero-title { font-size: clamp(1.35rem, 2vw, 2rem); line-height: 1.1; font-weight: 900; letter-spacing: -0.04em; margin: 0; }

        .rmt-hero-sub { margin-top: 6px; font-size: 0.84rem; color: rgba(255,255,255,0.72); display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

        .rmt-hero-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        .rmt-year-select {
            border-radius: 999px; padding: 8px 13px; font-size: 0.78rem; font-weight: 800;
            background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.22); color: #fff;
        }
        .rmt-year-select option { color: #1e293b; }

        .rmt-hero-btn {
            display: inline-flex; align-items: center; gap: 7px;
            border-radius: 999px; padding: 8px 13px; font-size: 0.74rem; font-weight: 800;
            background: rgba(255,255,255,0.95); color: var(--eco-dark-blue);
            border: 1px solid rgba(255,255,255,0.48); text-decoration: none; white-space: nowrap;
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }
        .rmt-hero-btn:hover { color: var(--eco-dark-blue); text-decoration: none; transform: translateY(-1px); box-shadow: 0 12px 24px rgba(0,0,0,0.14); }

        .rmt-hero-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }

        .rmt-kpi {
            position: relative; overflow: hidden;
            background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.16);
            border-radius: 16px; padding: 14px 15px 13px 15px;
            border-top: 3px solid var(--kpi-accent, rgba(255,255,255,0.4));
        }

        .rmt-kpi::after {
            content: ""; position: absolute; top: -30px; right: -30px;
            width: 90px; height: 90px; border-radius: 50%;
            background: var(--kpi-accent, transparent); opacity: 0.16; pointer-events: none;
        }

        .rmt-kpi-icon {
            position: absolute; top: 12px; right: 13px;
            width: 30px; height: 30px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            background: var(--kpi-accent, rgba(255,255,255,0.16)); color: #fff; font-size: 0.82rem;
            box-shadow: 0 6px 14px rgba(0,0,0,0.18);
        }

        .rmt-kpi--deposits { --kpi-accent: #22c1e0; }
        .rmt-kpi--loans    { --kpi-accent: #ff9f43; }
        .rmt-kpi--ntb      { --kpi-accent: #b98af6; }
        .rmt-kpi--rms      { --kpi-accent: #2ee6a6; }

        .rmt-kpi-label { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,0.68); margin-bottom: 6px; max-width: 78%; }
        .rmt-kpi-value { font-size: 1.32rem; font-weight: 900; font-family: 'DM Mono', monospace; letter-spacing: -0.02em; }
        .rmt-kpi-sub { margin-top: 4px; font-size: 0.72rem; color: rgba(255,255,255,0.72); font-family: 'DM Mono', monospace; }

        @media (max-width: 900px) {
            .rmt-hero-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        /* ── Toolbar ──────────────────────────────── */
        .rmt-toolbar {
            background: var(--eco-card); border-radius: 14px; padding: 12px 14px;
            box-shadow: var(--eco-shadow); border: 1px solid var(--eco-border);
            margin-bottom: 14px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        }

        .rmt-search {
            border: 1px solid #d0dce8; border-radius: 9px; padding: 7px 10px;
            font-size: 0.82rem; color: var(--eco-text); min-height: 34px; min-width: 240px; flex: 1;
        }
        .rmt-search:focus { outline: none; border-color: var(--eco-blue); box-shadow: 0 0 0 3px rgba(0,130,187,0.12); }

        .rmt-filter-select {
            border: 1px solid #d0dce8; border-radius: 9px; padding: 7px 10px;
            font-size: 0.8rem; color: var(--eco-text); min-height: 34px; background: #fff;
        }
        .rmt-filter-select:focus { outline: none; border-color: var(--eco-blue); }

        .rmt-legend { display: flex; align-items: center; gap: 12px; font-size: 0.7rem; color: var(--eco-muted); font-weight: 700; flex-wrap: wrap; margin-left: auto; }
        .rmt-legend span { display: inline-flex; align-items: center; gap: 5px; }
        .rmt-legend-label {
            font-size: 0.66rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em;
            color: var(--eco-dark-blue); padding-right: 4px; border-right: 1px solid var(--eco-border);
        }

        /* ── Table panel ──────────────────────────── */
        .rmt-panel {
            background: var(--eco-card); border-radius: 14px; box-shadow: var(--eco-shadow);
            border: 1px solid var(--eco-border); overflow: hidden;
        }

        .rmt-table-scroll {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 560px;
        }

        .rmt-table { width: 100%; border-collapse: collapse; font-size: 0.79rem; }

        .rmt-table th {
            position: sticky; top: 0; z-index: 2;
            background: var(--eco-dark-blue); color: #fff; padding: 9px 11px; text-align: right;
            font-size: 0.66rem; font-weight: 900; text-transform: uppercase; letter-spacing: .3px; white-space: nowrap;
        }
        .rmt-table th.text-left { text-align: left; }

        .rmt-table td { padding: 9px 11px; border-bottom: 1px solid #f0f4f8; color: var(--eco-text); vertical-align: middle; text-align: right; white-space: nowrap; }
        .rmt-table td.text-left { text-align: left; }

        .rmt-table tbody tr { cursor: pointer; transition: background .12s; box-shadow: inset 3px 0 0 transparent; }
        .rmt-table tbody tr:hover td { background: #f5f9fc; }
        .rmt-table tr:last-child td { border-bottom: none; }

        .rmt-row-good { box-shadow: inset 3px 0 0 var(--success); }
        .rmt-row-warn { box-shadow: inset 3px 0 0 var(--warn); }
        .rmt-row-bad  { box-shadow: inset 3px 0 0 var(--danger); }
        .rmt-row-none { box-shadow: inset 3px 0 0 rgba(100,116,139,0.35); }

        .rmt-table tfoot td {
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

        .pct-pill {
            display: inline-block; min-width: 52px; padding: 2px 8px; border-radius: 999px;
            font-size: 0.7rem; font-weight: 900; font-family: 'DM Mono', monospace;
        }
        .pct-good { background: var(--success-soft); color: var(--success); }
        .pct-warn { background: var(--warn-soft); color: var(--warn); }
        .pct-bad  { background: var(--danger-soft); color: var(--danger); }
        .pct-none { background: rgba(100,116,139,0.1); color: var(--eco-muted); }

        /* ── Rating / grade badges — same 1-5 scale as the branch dashboard ── */
        .rmt-grade {
            display: inline-flex; align-items: center; justify-content: center;
            width: 23px; height: 23px; border-radius: 50%;
            font-size: 0.7rem; font-weight: 900; font-family: 'DM Mono', monospace;
        }
        .rmt-grade-1 { background: #FEF2F2; color: #B91C1C; }
        .rmt-grade-2 { background: #FFF7ED; color: #92400E; }
        .rmt-grade-3 { background: #FEFCE8; color: #713F12; }
        .rmt-grade-4 { background: #F0FDF4; color: #166534; }
        .rmt-grade-5 { background: #DCFCE7; color: #14532D; }
        .rmt-grade-none { background: rgba(100,116,139,0.1); color: var(--eco-muted); font-size: 0.9rem; }

        .empty-row td, .loading-row td { text-align: center; padding: 30px; white-space: normal; }
        .empty-icon { font-size: 1.4rem; color: rgba(0,91,130,0.22); margin-bottom: 6px; }
        .empty-title { font-size: 0.88rem; font-weight: 900; color: var(--eco-dark-blue); }
        .spinner {
            display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(0,91,130,0.16);
            border-top-color: var(--eco-dark-blue); border-radius: 50%; animation: spin .65s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Drilldown drawer ──────────────────────── */
        .rmt-drawer-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 1800;
            align-items: center; justify-content: flex-end;
        }
        .rmt-drawer-overlay.open { display: flex; }

        .rmt-drawer {
            background: #fff; width: min(460px, 94vw); height: 100%;
            box-shadow: -18px 0 45px rgba(0,0,0,0.18); overflow-y: auto;
            display: flex; flex-direction: column;
        }

        .rmt-drawer-header {
            padding: 18px 20px; border-bottom: 1px solid #edf2f7;
            display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
            background: linear-gradient(90deg, rgba(0,91,130,0.05), transparent);
        }

        .rmt-drawer-title { font-size: 1rem; font-weight: 950; color: var(--eco-dark-blue); margin: 0; }
        .rmt-drawer-sub { margin-top: 3px; font-size: 0.76rem; color: var(--eco-muted); font-weight: 700; }

        .rmt-drawer-close { background: transparent; border: none; font-size: 1.1rem; cursor: pointer; color: var(--eco-muted); width: 32px; height: 32px; border-radius: 10px; }
        .rmt-drawer-close:hover { background: #f2f4f7; color: var(--eco-dark-blue); }

        .rmt-drawer-body { padding: 18px 20px 26px; }

        .rmt-drawer-chart-wrap { position: relative; height: 240px; margin-bottom: 18px; }

        .rmt-drawer-stat { display: flex; align-items: center; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #f0f4f8; font-size: 0.82rem; }
        .rmt-drawer-stat:last-child { border-bottom: none; }
        .rmt-drawer-stat-label { color: var(--eco-muted); font-weight: 700; }
        .rmt-drawer-stat-value { font-family: 'DM Mono', monospace; font-weight: 900; color: var(--eco-text); }
    </style>
@endpush

@section('content')

<div class="rmt-page">

    {{-- Hero --}}
    <div class="rmt-hero">
        <div class="rmt-hero-main">
            <div class="rmt-hero-title-wrap">
                <div class="rmt-hero-icon"><i class="fa-solid fa-bullseye"></i></div>
                <div>
                    <div class="rmt-hero-eyebrow">Relationship Managers</div>
                    <h1 class="rmt-hero-title">RM Targets</h1>
                    <div class="rmt-hero-sub">
                        <span>Deposits, loans &amp; NTB — actual vs annual target</span>
                    </div>
                </div>
            </div>

            <div class="rmt-hero-actions">
                <select id="year-select" class="rmt-year-select" onchange="loadData()"></select>
                <a class="rmt-hero-btn" href="{{ url('/finance/rm-targets/manage') }}">
                    <i class="fa-solid fa-pen-to-square"></i> Manage Targets
                </a>
            </div>
        </div>

        <div class="rmt-hero-kpis">
            <div class="rmt-kpi rmt-kpi--deposits">
                <div class="rmt-kpi-icon"><i class="fa-solid fa-piggy-bank"></i></div>
                <div class="rmt-kpi-label">Deposits — Actual / Target</div>
                <div class="rmt-kpi-value" id="kpi-deposits">—</div>
                <div class="rmt-kpi-sub" id="kpi-deposits-pct">—</div>
            </div>
            <div class="rmt-kpi rmt-kpi--loans">
                <div class="rmt-kpi-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="rmt-kpi-label">Loans — Actual / Target</div>
                <div class="rmt-kpi-value" id="kpi-loans">—</div>
                <div class="rmt-kpi-sub" id="kpi-loans-pct">—</div>
            </div>
            <div class="rmt-kpi rmt-kpi--ntb">
                <div class="rmt-kpi-icon"><i class="fa-solid fa-user-plus"></i></div>
                <div class="rmt-kpi-label">NTB — Actual / Target</div>
                <div class="rmt-kpi-value" id="kpi-ntb">—</div>
                <div class="rmt-kpi-sub" id="kpi-ntb-pct">—</div>
            </div>
            <div class="rmt-kpi rmt-kpi--rms">
                <div class="rmt-kpi-icon"><i class="fa-solid fa-users"></i></div>
                <div class="rmt-kpi-label">RMs Tracked</div>
                <div class="rmt-kpi-value" id="kpi-rms">—</div>
                <div class="rmt-kpi-sub" id="kpi-rms-sub">—</div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="rmt-toolbar">
        <input type="text" id="search-input" class="rmt-search" placeholder="Search by RM code or name…" oninput="applyFilters()">

        <select id="segment-filter" class="rmt-filter-select" onchange="onSegmentChange()">
            <option value="">All Segments</option>
        </select>

        <select id="subsegment-filter" class="rmt-filter-select" onchange="applyFilters()">
            <option value="">All Sub-segments</option>
        </select>

        <div class="rmt-legend">
            <span class="rmt-legend-label">Rating Scale</span>
            <span><span class="rmt-grade rmt-grade-5" style="width:16px;height:16px;font-size:0.58rem;">5</span> Far Exceeds &ge;120%</span>
            <span><span class="rmt-grade rmt-grade-4" style="width:16px;height:16px;font-size:0.58rem;">4</span> Exceeds 101–119%</span>
            <span><span class="rmt-grade rmt-grade-3" style="width:16px;height:16px;font-size:0.58rem;">3</span> Meets 96–100%</span>
            <span><span class="rmt-grade rmt-grade-2" style="width:16px;height:16px;font-size:0.58rem;">2</span> Partial 50–95%</span>
            <span><span class="rmt-grade rmt-grade-1" style="width:16px;height:16px;font-size:0.58rem;">1</span> Below &lt;50%</span>
        </div>
    </div>

    {{-- Table --}}
    <div class="rmt-panel">
        <div class="rmt-table-scroll">
            <table class="rmt-table">
                <thead>
                    <tr>
                        <th class="text-left">RM</th>
                        <th class="text-left">Segment</th>
                        <th class="text-left">Sub-segment</th>
                        <th>Deposits Actual</th>
                        <th>Deposits Target</th>
                        <th>Deposits %</th>
                        <th>Grade</th>
                        <th>Loans Actual</th>
                        <th>Loans Target</th>
                        <th>Loans %</th>
                        <th>Grade</th>
                        <th>NTB Actual</th>
                        <th>NTB Target</th>
                        <th>NTB %</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody id="rmt-tbody">
                    <tr class="loading-row"><td colspan="15"><span class="spinner"></span> Loading…</td></tr>
                </tbody>
                <tfoot id="rmt-tfoot" style="display:none;">
                    <tr>
                        <td class="text-left" colspan="3">Total (targeted RMs)</td>
                        <td id="foot-deposits-actual"></td>
                        <td id="foot-deposits-target"></td>
                        <td id="foot-deposits-pct"></td>
                        <td>—</td>
                        <td id="foot-loans-actual"></td>
                        <td id="foot-loans-target"></td>
                        <td id="foot-loans-pct"></td>
                        <td>—</td>
                        <td id="foot-ntb-actual"></td>
                        <td id="foot-ntb-target"></td>
                        <td id="foot-ntb-pct"></td>
                        <td>—</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

{{-- ─── Drilldown drawer ─── --}}
<div class="rmt-drawer-overlay" id="drawer-overlay" onclick="if(event.target===this)closeDrawer()">
    <div class="rmt-drawer">
        <div class="rmt-drawer-header">
            <div>
                <h3 class="rmt-drawer-title" id="drawer-title">RM</h3>
                <div class="rmt-drawer-sub" id="drawer-sub">—</div>
            </div>
            <button class="rmt-drawer-close" onclick="closeDrawer()">&#x2715;</button>
        </div>
        <div class="rmt-drawer-body">
            <div class="rmt-drawer-chart-wrap">
                <canvas id="drawer-chart"></canvas>
            </div>
            <div id="drawer-stats"></div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    const rows = @json($rows);
    const currentYear = {{ $year }};
    let filteredRows = rows;
    let drawerChart  = null;

    const $ = id => document.getElementById(id);

    function fmtMoney(v) {
        const n = Number(v || 0);
        if (Math.abs(n) >= 1e9) return (n / 1e9).toFixed(2) + 'B';
        if (Math.abs(n) >= 1e6) return (n / 1e6).toFixed(2) + 'M';
        if (Math.abs(n) >= 1e3) return (n / 1e3).toFixed(1) + 'K';
        return n.toLocaleString();
    }

    function fmtFull(v) { return Number(v || 0).toLocaleString('en-KE', { maximumFractionDigits: 0 }); }

    function escHtml(v) {
        return String(v ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
    }

    function pctPill(pct) {
        if (pct === null || pct === undefined) return `<span class="pct-pill pct-none">—</span>`;
        const cls = pct >= 90 ? 'pct-good' : (pct >= 50 ? 'pct-warn' : 'pct-bad');
        return `<span class="pct-pill ${cls}">${pct}%</span>`;
    }

    /* ─── Rating / grade badges — same 1-5 scale as the branch dashboard ───
       5 = Far Exceeds (>=120%), 4 = Exceeds (101-119%), 3 = Meets (96-100%),
       2 = Partially Meets (50-95%), 1 = Doesn't Meet (<50%). */
    const GRADE_LABELS = {
        5: 'Far Exceeds Expectations',
        4: 'Exceeds Expectations',
        3: 'Meets Expectations',
        2: 'Partially Meets Expectations',
        1: "Doesn't Meet Expectations",
    };

    function gradeBadge(grade) {
        if (grade === null || grade === undefined) return `<span class="rmt-grade rmt-grade-none">—</span>`;
        return `<span class="rmt-grade rmt-grade-${grade}" title="${GRADE_LABELS[grade] || ''}">${grade}</span>`;
    }

    function rowStripeClass(r) {
        const grades = [r.deposit_grade, r.loan_grade, r.ntb_grade].filter(g => g !== null && g !== undefined);
        if (!grades.length) return 'rmt-row-none';
        const avg = grades.reduce((a, b) => a + b, 0) / grades.length;
        if (avg >= 4) return 'rmt-row-good';
        if (avg >= 3) return 'rmt-row-warn';
        return 'rmt-row-bad';
    }

    /* ─── Colourful, deterministic segment/sub-segment badge palette ─── */
    const SEGMENT_PALETTE = [
        { bg: 'rgba(0,130,187,0.15)',   fg: '#005b82' },  // blue
        { bg: 'rgba(99,153,34,0.15)',   fg: '#4d7028' },  // green
        { bg: 'rgba(255,159,67,0.18)',  fg: '#b45309' },  // amber
        { bg: 'rgba(185,138,246,0.18)', fg: '#7c3aed' },  // purple
        { bg: 'rgba(46,230,166,0.18)',  fg: '#0f8a63' },  // teal
        { bg: 'rgba(236,72,153,0.15)',  fg: '#be185d' },  // pink
        { bg: 'rgba(34,193,224,0.18)',  fg: '#0e7490' },  // cyan
        { bg: 'rgba(220,38,38,0.13)',   fg: '#b91c1c' },  // red
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

    function loadData() {
        const year = $('year-select').value;
        if (parseInt(year, 10) !== currentYear) {
            window.location.href = `${window.location.pathname}?year=${encodeURIComponent(year)}`;
        }
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
        const segment = $('segment-filter').value;
        const subsegment = $('subsegment-filter').value;

        filteredRows = rows.filter(r => {
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
            $('rmt-tbody').innerHTML = `
                <tr class="empty-row">
                    <td colspan="15">
                        <div class="empty-icon"><i class="fa-regular fa-folder-open"></i></div>
                        <div class="empty-title">No RMs match your filters</div>
                    </td>
                </tr>`;
            $('rmt-tfoot').style.display = 'none';
            return;
        }

        $('rmt-tbody').innerHTML = filteredRows.map((r, i) => `
            <tr onclick="openDrawer(${i})" class="${rowStripeClass(r)}">
                <td class="text-left">
                    <span class="badge-code">${escHtml(r.rm_code)}</span>
                    <div class="rm-name-cell">${escHtml(r.name)}</div>
                </td>
                <td class="text-left">${segmentBadge(r.segment)}</td>
                <td class="text-left">${segmentBadge(r.subsegment, true)}</td>
                <td>${fmtMoney(r.actual_deposits)}</td>
                <td>${r.has_target ? fmtMoney(r.deposit_target) : '<span class="badge-none">Not set</span>'}</td>
                <td>${pctPill(r.deposit_pct)}</td>
                <td>${gradeBadge(r.deposit_grade)}</td>
                <td>${fmtMoney(r.actual_loans)}</td>
                <td>${r.has_target ? fmtMoney(r.loan_target) : '<span class="badge-none">Not set</span>'}</td>
                <td>${pctPill(r.loan_pct)}</td>
                <td>${gradeBadge(r.loan_grade)}</td>
                <td>${fmtFull(r.actual_ntb)}</td>
                <td>${r.has_target ? fmtFull(r.ntb_target) : '<span class="badge-none">Not set</span>'}</td>
                <td>${pctPill(r.ntb_pct)}</td>
                <td>${gradeBadge(r.ntb_grade)}</td>
            </tr>`).join('');

        $('rmt-tfoot').style.display = '';
    }

    function renderKpis() {
        const t = @json($targetTotals);
        const a = @json($actualTotals);

        const depPct = t.deposits > 0 ? Math.round((a.deposits / t.deposits) * 1000) / 10 : null;
        const loanPct = t.loans > 0 ? Math.round((a.loans / t.loans) * 1000) / 10 : null;
        const ntbPct = t.ntb > 0 ? Math.round((a.ntb / t.ntb) * 1000) / 10 : null;

        $('kpi-deposits').textContent = `${fmtMoney(a.deposits)} / ${fmtMoney(t.deposits)}`;
        $('kpi-deposits-pct').textContent = depPct !== null ? `${depPct}% achieved` : 'No targets set';

        $('kpi-loans').textContent = `${fmtMoney(a.loans)} / ${fmtMoney(t.loans)}`;
        $('kpi-loans-pct').textContent = loanPct !== null ? `${loanPct}% achieved` : 'No targets set';

        $('kpi-ntb').textContent = `${fmtFull(a.ntb)} / ${fmtFull(t.ntb)}`;
        $('kpi-ntb-pct').textContent = ntbPct !== null ? `${ntbPct}% achieved` : 'No targets set';

        $('kpi-rms').textContent = {{ $targetedCount }};
        $('kpi-rms-sub').textContent = `{{ $untargetedCount }} without a target`;

        $('foot-deposits-actual').textContent = fmtMoney(a.deposits);
        $('foot-deposits-target').textContent = fmtMoney(t.deposits);
        $('foot-deposits-pct').innerHTML = pctPill(depPct);
        $('foot-loans-actual').textContent = fmtMoney(a.loans);
        $('foot-loans-target').textContent = fmtMoney(t.loans);
        $('foot-loans-pct').innerHTML = pctPill(loanPct);
        $('foot-ntb-actual').textContent = fmtFull(a.ntb);
        $('foot-ntb-target').textContent = fmtFull(t.ntb);
        $('foot-ntb-pct').innerHTML = pctPill(ntbPct);
    }

    /* ─── Drilldown drawer ─── */
    function openDrawer(index) {
        const r = filteredRows[index];
        if (!r) return;

        $('drawer-title').textContent = `${r.rm_code} — ${r.name}`;
        $('drawer-sub').textContent = r.segment ? `${r.segment} · ${currentYear}` : `${currentYear}`;

        $('drawer-stats').innerHTML = `
            <div class="rmt-drawer-stat"><span class="rmt-drawer-stat-label">Deposits Achievement</span><span class="rmt-drawer-stat-value">${r.deposit_pct !== null ? r.deposit_pct + '%' : '—'} ${gradeBadge(r.deposit_grade)}</span></div>
            <div class="rmt-drawer-stat"><span class="rmt-drawer-stat-label">Loans Achievement</span><span class="rmt-drawer-stat-value">${r.loan_pct !== null ? r.loan_pct + '%' : '—'} ${gradeBadge(r.loan_grade)}</span></div>
            <div class="rmt-drawer-stat"><span class="rmt-drawer-stat-label">NTB Achievement</span><span class="rmt-drawer-stat-value">${r.ntb_pct !== null ? r.ntb_pct + '%' : '—'} ${gradeBadge(r.ntb_grade)}</span></div>
        `;

        if (drawerChart) drawerChart.destroy();
        const canvas = $('drawer-chart');
        drawerChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: ['Deposits', 'Loans', 'NTB'],
                datasets: [
                    {
                        label: 'Actual',
                        data: [r.actual_deposits, r.actual_loans, r.actual_ntb],
                        backgroundColor: 'rgba(0,130,187,0.75)',
                        borderRadius: 6,
                    },
                    {
                        label: 'Target',
                        data: [r.deposit_target ?? 0, r.loan_target ?? 0, r.ntb_target ?? 0],
                        backgroundColor: 'rgba(99,153,34,0.55)',
                        borderRadius: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { position: 'bottom' } },
            },
        });

        $('drawer-overlay').classList.add('open');
    }

    function closeDrawer() {
        $('drawer-overlay').classList.remove('open');
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });

    /* ─── Init ─── */
    document.addEventListener('DOMContentLoaded', () => {
        initYearSelect();
        populateSegmentFilter();
        renderKpis();
        applyFilters();
    });
</script>
@endpush
