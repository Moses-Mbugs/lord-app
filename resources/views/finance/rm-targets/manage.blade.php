@extends('layouts.finance.template')

@section('title', 'Manage RM Targets')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --eco-blue: #0082BB;
            --eco-dark-blue: #005B82;
            --eco-green: #BED600;
            --eco-dark-green: #669438;
            --eco-gray: #464646;
            --eco-light-gray: #EDEDED;
            --eco-mid-gray: #979797;
            --eco-bg: #F5F8FB;
            --eco-white: #ffffff;
            --success: #15803d;
            --success-soft: rgba(21,128,61,0.10);
            --danger: #dc2626;
            --danger-soft: rgba(220,38,38,0.10);
            --card-shadow: 0 8px 28px rgba(0,0,0,0.06);
            --border-soft: 1px solid rgba(0,91,130,0.08);
            --radius-lg: 18px;
            --radius-md: 14px;
        }

        body { background: var(--eco-bg); }

        .rm-mgmt { padding: 14px 18px 32px; }

        /* ── Hero ─────────────────────────────────── */
        .mgmt-hero {
            border-radius: var(--radius-lg);
            padding: 22px 24px;
            margin-bottom: 16px;
            background: linear-gradient(135deg, var(--eco-dark-blue) 0%, var(--eco-blue) 60%, #0fa3d8 100%);
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
        }

        .mgmt-hero-left { display: flex; align-items: center; gap: 14px; }

        .mgmt-hero-icon {
            width: 52px; height: 52px;
            border-radius: 16px;
            background: rgba(255,255,255,0.14);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.3rem; flex-shrink: 0;
        }

        .mgmt-hero-title {
            margin: 0; color: #fff;
            font-size: 1.3rem; font-weight: 950; line-height: 1.15;
        }

        .mgmt-hero-sub {
            margin: 5px 0 0; color: rgba(255,255,255,0.76);
            font-size: 0.82rem; font-weight: 600;
        }

        /* ── Stat chips ───────────────────────────── */
        .stat-chips { display: flex; gap: 10px; flex-wrap: wrap; }

        .stat-chip {
            background: rgba(255,255,255,0.13);
            border: 1px solid rgba(255,255,255,0.16);
            color: #fff;
            border-radius: 999px;
            padding: 7px 14px;
            font-size: 0.78rem;
            font-weight: 900;
            display: flex; align-items: center; gap: 7px;
            white-space: nowrap;
        }

        .stat-chip span { font-family: 'DM Mono', monospace; }

        /* ── Toolbar ──────────────────────────────── */
        .toolbar {
            background: #fff;
            border-radius: var(--radius-md);
            padding: 12px 14px;
            box-shadow: var(--card-shadow);
            border: var(--border-soft);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .toolbar-left { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; flex-wrap: wrap; }
        .toolbar-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

        .search-input {
            border: 1px solid #d0dce8; border-radius: 9px;
            padding: 7px 10px; font-size: 0.82rem;
            color: var(--eco-gray); background: #fff;
            min-height: 34px; min-width: 220px;
        }

        .search-input:focus { outline: none; border-color: var(--eco-blue); box-shadow: 0 0 0 3px rgba(0,130,187,0.12); }

        .filter-select {
            border: 1px solid #d0dce8; border-radius: 9px;
            padding: 7px 10px; font-size: 0.82rem;
            color: var(--eco-gray); background: #fff;
            min-height: 34px;
        }

        .filter-select:focus { outline: none; border-color: var(--eco-blue); }

        /* ── Buttons ──────────────────────────────── */
        .btn-eco {
            background: var(--eco-dark-blue); color: #fff;
            border: none; border-radius: 9px;
            padding: 8px 14px; font-size: 0.8rem; font-weight: 900;
            cursor: pointer; transition: all .18s ease;
            min-height: 34px; display: inline-flex;
            align-items: center; justify-content: center;
            gap: 7px; white-space: nowrap;
        }

        .btn-eco:hover { background: #004b6e; transform: translateY(-1px); }
        .btn-eco:disabled { opacity: .65; cursor: not-allowed; transform: none; }

        .btn-eco-green  { background: var(--eco-dark-green); }
        .btn-eco-green:hover { background: #4d7028; }

        .btn-eco-danger { background: var(--danger); }
        .btn-eco-danger:hover { background: #b91c1c; }

        .btn-eco-light {
            background: #f2f7fb; color: var(--eco-dark-blue);
            border: 1px solid rgba(0,91,130,0.12);
        }
        .btn-eco-light:hover { background: #e8f3fa; }

        .btn-icon {
            width: 30px; height: 30px; border-radius: 8px;
            border: none; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.78rem; transition: all .15s;
        }

        .btn-edit  { background: rgba(0,91,130,0.08); color: var(--eco-dark-blue); }
        .btn-edit:hover { background: rgba(0,91,130,0.18); }

        .btn-del   { background: var(--danger-soft); color: var(--danger); }
        .btn-del:hover { background: rgba(220,38,38,0.18); }

        /* ── Table panel ──────────────────────────── */
        .panel {
            background: #fff; border-radius: var(--radius-md);
            box-shadow: var(--card-shadow); border: var(--border-soft);
            overflow: hidden;
        }

        .panel-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; padding: 12px 16px;
            border-bottom: var(--border-soft);
            background: linear-gradient(90deg, rgba(0,91,130,0.04), transparent);
        }

        .panel-title {
            font-size: 0.88rem; font-weight: 950;
            color: var(--eco-dark-blue); margin: 0;
            display: flex; align-items: center; gap: 8px;
        }

        .panel-count {
            font-family: 'DM Mono', monospace;
            font-size: 0.7rem; font-weight: 950;
            background: rgba(0,91,130,0.09); color: var(--eco-dark-blue);
            border-radius: 999px; padding: 2px 9px;
        }

        .rm-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }

        .rm-table th {
            background: var(--eco-dark-blue); color: #fff;
            padding: 9px 12px; text-align: left;
            font-size: 0.7rem; font-weight: 950;
            text-transform: uppercase; letter-spacing: .4px;
            white-space: nowrap;
        }

        .rm-table td {
            padding: 9px 12px;
            border-bottom: 1px solid #f0f4f8;
            color: var(--eco-gray);
            vertical-align: middle;
            white-space: nowrap;
        }

        .rm-table tr:hover td { background: #f5f9fc; }
        .rm-table tr:last-child td { border-bottom: none; }

        .badge-code {
            display: inline-block;
            background: rgba(0,91,130,0.1); color: var(--eco-dark-blue);
            border-radius: 7px; padding: 3px 9px;
            font-size: 0.74rem; font-weight: 950;
            font-family: 'DM Mono', monospace; white-space: nowrap;
        }

        .badge-segment {
            display: inline-block;
            background: rgba(190,214,0,0.15); color: var(--eco-dark-green);
            border-radius: 7px; padding: 3px 9px;
            font-size: 0.72rem; font-weight: 950; white-space: nowrap;
        }

        .badge-none { color: var(--eco-mid-gray); font-size: 0.74rem; font-style: italic; }

        .rm-name-cell { font-weight: 700; color: var(--eco-gray); }
        .rm-num-cell  { font-family: 'DM Mono', monospace; font-size: 0.78rem; text-align: right; }
        .rm-date-cell { font-size: 0.72rem; color: var(--eco-mid-gray); }

        .actions-cell { display: flex; gap: 6px; align-items: center; }

        /* ── Empty / loading ──────────────────────── */
        .empty-row td { text-align: center; padding: 32px; white-space: normal; }
        .empty-icon { font-size: 1.5rem; color: rgba(0,91,130,0.22); margin-bottom: 8px; }
        .empty-title { font-size: 0.9rem; font-weight: 950; color: var(--eco-dark-blue); margin-bottom: 4px; }
        .empty-sub   { font-size: 0.75rem; color: var(--eco-mid-gray); font-weight: 600; }

        .loading-row td { text-align: center; padding: 28px; color: var(--eco-mid-gray); white-space: normal; }

        .spinner {
            display: inline-block; width: 18px; height: 18px;
            border: 2px solid rgba(0,91,130,0.16);
            border-top-color: var(--eco-dark-blue);
            border-radius: 50%; animation: spin .65s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Modals ───────────────────────────────── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.44); z-index: 1800;
            align-items: center; justify-content: center;
            padding: 18px;
        }

        .modal-overlay.open { display: flex; }

        .modal-box {
            background: #fff; border-radius: 18px;
            width: min(520px, 96vw); max-height: 90vh;
            overflow: hidden; box-shadow: 0 24px 70px rgba(0,0,0,0.22);
            display: flex; flex-direction: column;
        }

        .modal-box.confirm { width: min(380px, 95vw); }

        .modal-header {
            display: flex; align-items: flex-start;
            justify-content: space-between; gap: 12px;
            padding: 18px 20px; border-bottom: 1px solid #edf2f7;
            background: linear-gradient(90deg, rgba(0,91,130,0.05), transparent);
        }

        .modal-title { font-size: 1rem; font-weight: 950; color: var(--eco-dark-blue); margin: 0; }
        .modal-subtitle { margin-top: 3px; color: var(--eco-mid-gray); font-size: 0.74rem; font-weight: 700; }

        .modal-close {
            background: transparent; border: none;
            font-size: 1.1rem; cursor: pointer;
            color: var(--eco-mid-gray); width: 32px; height: 32px;
            border-radius: 10px; line-height: 1;
        }
        .modal-close:hover { background: #f2f4f7; color: var(--eco-dark-blue); }

        .modal-body { padding: 18px 20px 22px; overflow-y: auto; }

        /* ── Form ─────────────────────────────────── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-grid .span-2 { grid-column: 1 / -1; }

        .form-group { display: flex; flex-direction: column; gap: 5px; }

        .form-label {
            font-size: 0.72rem; font-weight: 950;
            color: var(--eco-dark-blue); letter-spacing: .2px;
        }

        .form-label .req { color: var(--danger); margin-left: 2px; }

        .form-input, .form-select {
            border: 1px solid #d0dce8; border-radius: 9px;
            padding: 8px 11px; font-size: 0.82rem;
            color: var(--eco-gray); background: #fff;
            min-height: 36px; width: 100%;
        }

        .form-input:focus, .form-select:focus {
            outline: none; border-color: var(--eco-blue);
            box-shadow: 0 0 0 3px rgba(0,130,187,0.12);
        }

        .form-hint { font-size: 0.68rem; color: var(--eco-mid-gray); font-weight: 700; }

        .form-footer {
            display: flex; align-items: center;
            justify-content: flex-end; gap: 10px;
            margin-top: 6px;
        }

        .form-msg { font-size: 0.78rem; min-height: 20px; display: flex; align-items: center; gap: 7px; }

        .text-success { color: var(--success); font-weight: 950; }
        .text-danger  { color: var(--danger);  font-weight: 900; }
        .text-muted   { color: var(--eco-mid-gray); }

        /* ── Confirm modal ────────────────────────── */
        .confirm-body { padding: 20px; display: flex; flex-direction: column; gap: 14px; }

        .confirm-warning {
            background: var(--danger-soft);
            border: 1px solid rgba(220,38,38,0.18);
            border-radius: 12px; padding: 12px 14px;
            color: var(--danger); font-size: 0.8rem;
            font-weight: 700; line-height: 1.5;
            display: flex; align-items: flex-start; gap: 10px;
        }

        .confirm-actions { display: flex; gap: 10px; justify-content: flex-end; }
    </style>
@endpush

@section('content')

<div class="rm-mgmt">

    {{-- Hero --}}
    <div class="mgmt-hero">
        <div class="mgmt-hero-left">
            <div class="mgmt-hero-icon">
                <i class="fa-solid fa-bullseye"></i>
            </div>
            <div>
                <h1 class="mgmt-hero-title">Manage RM Targets</h1>
                <p class="mgmt-hero-sub">
                    Set or update annual deposit, loan and NTB targets for each Relationship Manager.
                </p>
            </div>
        </div>

        <div class="stat-chips">
            <div class="stat-chip">
                <i class="fa-solid fa-list-check"></i>
                Targets set: <span id="hero-total">—</span>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="toolbar">
        <div class="toolbar-left">
            <select id="year-filter" class="filter-select" onchange="loadData()"></select>

            <input type="text" id="search-input" class="search-input"
                   placeholder="Search by RM code…"
                   oninput="applyFilters()">
        </div>

        <div class="toolbar-right">
            <button class="btn-eco btn-eco-light" onclick="loadData()">
                <i class="fa-solid fa-rotate-right"></i>
                Refresh
            </button>

            <a class="btn-eco btn-eco-light" href="{{ url('/finance/rm-targets') }}">
                <i class="fa-solid fa-chart-column"></i>
                View Dashboard
            </a>

            <button class="btn-eco btn-eco-green" onclick="openAddModal()">
                <i class="fa-solid fa-circle-plus"></i>
                Add Target
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title">
                <i class="fa-solid fa-table"></i>
                RM Targets
                <span class="panel-count" id="table-count">—</span>
            </span>
        </div>

        <div style="overflow-x:auto;">
            <table class="rm-table">
                <thead>
                    <tr>
                        <th style="width:44px;">#</th>
                        <th>RM Code</th>
                        <th>Name</th>
                        <th>Segment</th>
                        <th>Year</th>
                        <th>Deposit Target</th>
                        <th>Loan Target</th>
                        <th>NTB Target</th>
                        <th>Updated By</th>
                        <th style="width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="rm-tbody">
                    <tr class="loading-row">
                        <td colspan="10">
                            <span class="spinner"></span> Loading…
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ─── Add / Edit Modal ─── --}}
<div class="modal-overlay" id="form-modal" onclick="if(event.target===this)closeFormModal()">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="form-modal-title">Add RM Target</h3>
                <div class="modal-subtitle" id="form-modal-sub">Fill in the details below.</div>
            </div>
            <button class="modal-close" onclick="closeFormModal()">&#x2715;</button>
        </div>

        <div class="modal-body">
            <form id="rm-form" onsubmit="submitForm(event)">
                <input type="hidden" id="form-mode" value="add">
                <input type="hidden" id="form-target-id" value="">

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="f-rm-code">
                            RM Code <span class="req">*</span>
                        </label>
                        <input type="text" id="f-rm-code" class="form-input"
                               list="rm-datalist"
                               placeholder="e.g. KE0042"
                               style="text-transform:uppercase;"
                               maxlength="10" required>
                        <datalist id="rm-datalist"></datalist>
                        <div class="form-hint">Must be an existing RM (see RM Management).</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="f-year">
                            Year <span class="req">*</span>
                        </label>
                        <input type="number" id="f-year" class="form-input"
                               min="2000" max="2100" required>
                    </div>

                    <div class="form-group span-2">
                        <label class="form-label" for="f-deposit-target">
                            Deposit Target (KES) <span class="req">*</span>
                        </label>
                        <input type="number" id="f-deposit-target" class="form-input"
                               min="0" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="f-loan-target">
                            Loan Target (KES) <span class="req">*</span>
                        </label>
                        <input type="number" id="f-loan-target" class="form-input"
                               min="0" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="f-ntb-target">
                            NTB Target (accounts) <span class="req">*</span>
                        </label>
                        <input type="number" id="f-ntb-target" class="form-input"
                               min="0" step="1" required>
                    </div>
                </div>

                <div class="form-footer" style="margin-top:18px;">
                    <div class="form-msg text-muted" id="form-msg"></div>
                    <button type="button" class="btn-eco btn-eco-light" onclick="closeFormModal()">Cancel</button>
                    <button type="submit" class="btn-eco btn-eco-green" id="form-submit-btn">
                        <i class="fa-solid fa-floppy-disk"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── Delete Confirm Modal ─── --}}
<div class="modal-overlay" id="delete-modal" onclick="if(event.target===this)closeDeleteModal()">
    <div class="modal-box confirm">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Remove Target</h3>
                <div class="modal-subtitle">This cannot be undone.</div>
            </div>
            <button class="modal-close" onclick="closeDeleteModal()">&#x2715;</button>
        </div>

        <div class="confirm-body">
            <div class="confirm-warning">
                <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px;flex-shrink:0;"></i>
                <div>
                    You are about to permanently remove the target for
                    <strong id="delete-target-label">this RM</strong>.
                </div>
            </div>

            <div id="delete-msg" class="form-msg text-muted"></div>

            <div class="confirm-actions">
                <button class="btn-eco btn-eco-light" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-eco btn-eco-danger" id="delete-confirm-btn" onclick="confirmDelete()">
                    <i class="fa-solid fa-trash"></i> Remove
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const BASE = '/finance/rm-targets/manage';

    let allRows      = [];
    let deleteTargetId = null;
    const currentYear = new Date().getFullYear();

    const $ = id => document.getElementById(id);

    function fmtMoney(v) {
        const n = Number(v || 0);
        return n.toLocaleString('en-KE', { maximumFractionDigits: 0 });
    }

    function fmtDate(str) {
        if (!str) return '—';
        const d = new Date(str);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function escHtml(v) {
        return String(v ?? '')
            .replaceAll('&', '&amp;').replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;').replaceAll('"', '&quot;');
    }

    function jsStr(v) { return JSON.stringify(String(v ?? '')); }

    /* ─── Init year filter ─── */
    function initYearFilter() {
        const sel = $('year-filter');
        if (sel.options.length) return;
        const years = [];
        for (let y = currentYear + 1; y >= currentYear - 4; y--) years.push(y);
        sel.innerHTML = years.map(y => `<option value="${y}" ${y === currentYear ? 'selected' : ''}>${y}</option>`).join('');
    }

    /* ─── Load RM datalist (for the add form) ─── */
    async function loadRmList() {
        try {
            const res = await fetch('/finance/rm-targets/rm-list');
            const rms = await res.json();
            $('rm-datalist').innerHTML = rms.map(r => `<option value="${escHtml(r.rm_code)}">${escHtml(r.name)}</option>`).join('');
        } catch (e) {
            console.error(e);
        }
    }

    /* ─── Load ─── */
    async function loadData() {
        initYearFilter();
        $('rm-tbody').innerHTML = `<tr class="loading-row"><td colspan="10"><span class="spinner"></span> Loading…</td></tr>`;

        const year = $('year-filter').value || currentYear;

        try {
            const res = await fetch(`${BASE}/data?year=${encodeURIComponent(year)}`);
            if (!res.ok) throw new Error('Failed');
            const d = await res.json();

            allRows = Array.isArray(d.rows) ? d.rows : [];
            $('hero-total').textContent = d.total ?? allRows.length;

            applyFilters();

        } catch (e) {
            console.error(e);
            $('rm-tbody').innerHTML = `
                <tr class="empty-row">
                    <td colspan="10">
                        <div class="empty-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div class="empty-title">Failed to load targets</div>
                        <div class="empty-sub">Please refresh the page or try again.</div>
                    </td>
                </tr>`;
        }
    }

    /* ─── Filter + render ─── */
    function applyFilters() {
        const search = ($('search-input').value || '').trim().toLowerCase();

        const filtered = allRows.filter(r => {
            return !search || (r.rm_code || '').toLowerCase().includes(search)
                || (r.name || '').toLowerCase().includes(search);
        });

        renderTable(filtered);
    }

    function renderTable(rows) {
        $('table-count').textContent = rows.length.toLocaleString();

        if (!rows.length) {
            $('rm-tbody').innerHTML = `
                <tr class="empty-row">
                    <td colspan="10">
                        <div class="empty-icon"><i class="fa-regular fa-folder-open"></i></div>
                        <div class="empty-title">No targets found for this year</div>
                        <div class="empty-sub">Add a target using the button above.</div>
                    </td>
                </tr>`;
            return;
        }

        $('rm-tbody').innerHTML = rows.map((r, i) => `
            <tr>
                <td style="color:var(--eco-mid-gray);font-size:0.74rem;font-weight:700;">${i + 1}</td>
                <td><span class="badge-code">${escHtml(r.rm_code)}</span></td>
                <td class="rm-name-cell">${escHtml(r.name) || '<span class="badge-none">Unknown RM</span>'}</td>
                <td>${r.segment ? `<span class="badge-segment">${escHtml(r.segment)}</span>` : '<span class="badge-none">—</span>'}</td>
                <td>${r.period_year}</td>
                <td class="rm-num-cell">${fmtMoney(r.deposit_target)}</td>
                <td class="rm-num-cell">${fmtMoney(r.loan_target)}</td>
                <td class="rm-num-cell">${fmtMoney(r.ntb_target)}</td>
                <td class="rm-date-cell">${escHtml(r.updated_by) || '—'}<br>${fmtDate(r.updated_at)}</td>
                <td>
                    <div class="actions-cell">
                        <button class="btn-icon btn-edit" title="Edit" onclick='openEditModal(${jsStr(String(r.id))})'>
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn-icon btn-del" title="Remove" onclick='openDeleteModal(${jsStr(String(r.id))}, ${jsStr(r.rm_code)}, ${jsStr(String(r.period_year))})'>
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`).join('');
    }

    /* ─── Add modal ─── */
    function openAddModal() {
        $('form-modal-title').textContent = 'Add RM Target';
        $('form-modal-sub').textContent   = 'All fields marked * are required.';
        $('form-mode').value       = 'add';
        $('form-target-id').value  = '';
        $('f-rm-code').value       = '';
        $('f-year').value          = $('year-filter').value || currentYear;
        $('f-deposit-target').value = '';
        $('f-loan-target').value    = '';
        $('f-ntb-target').value     = '';
        $('f-rm-code').readOnly = false;
        clearFormMsg();
        $('form-modal').classList.add('open');
        setTimeout(() => $('f-rm-code').focus(), 80);
    }

    /* ─── Edit modal ─── */
    function openEditModal(id) {
        const t = allRows.find(r => String(r.id) === String(id));
        if (!t) return;

        $('form-modal-title').textContent = `Edit — ${t.rm_code} (${t.period_year})`;
        $('form-modal-sub').textContent   = 'Update the target values and save.';
        $('form-mode').value       = 'edit';
        $('form-target-id').value  = t.id;
        $('f-rm-code').value       = t.rm_code;
        $('f-year').value          = t.period_year;
        $('f-deposit-target').value = t.deposit_target;
        $('f-loan-target').value    = t.loan_target;
        $('f-ntb-target').value     = t.ntb_target;
        clearFormMsg();
        $('form-modal').classList.add('open');
        setTimeout(() => $('f-deposit-target').focus(), 80);
    }

    function closeFormModal() {
        $('form-modal').classList.remove('open');
    }

    /* ─── Submit form ─── */
    async function submitForm(e) {
        e.preventDefault();

        const mode = $('form-mode').value;
        const id   = $('form-target-id').value;

        const payload = {
            rm_code:        $('f-rm-code').value.toUpperCase().trim(),
            period_year:    parseInt($('f-year').value, 10),
            deposit_target: parseFloat($('f-deposit-target').value || '0'),
            loan_target:    parseFloat($('f-loan-target').value || '0'),
            ntb_target:     parseInt($('f-ntb-target').value || '0', 10),
        };

        const token = document.querySelector('meta[name=csrf-token]')?.content;
        if (!token) { showFormMsg('CSRF token missing. Refresh the page.', 'danger'); return; }

        const url    = mode === 'add' ? `${BASE}` : `${BASE}/${id}`;
        const method = mode === 'add' ? 'POST' : 'PUT';

        setBtnLoading('form-submit-btn', true, 'Saving…');
        clearFormMsg();

        try {
            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify(payload),
            });

            const d = await res.json();

            if (res.ok && d.success) {
                showFormMsg(d.message, 'success');
                setTimeout(() => { closeFormModal(); loadData(); }, 750);
            } else {
                const errMsg = d.errors
                    ? Object.values(d.errors).flat().join(' ')
                    : (d.message || 'Something went wrong.');
                showFormMsg(errMsg, 'danger');
            }

        } catch (err) {
            console.error(err);
            showFormMsg('Network error. Please try again.', 'danger');
        } finally {
            setBtnLoading('form-submit-btn', false);
        }
    }

    /* ─── Delete modal ─── */
    function openDeleteModal(id, code, year) {
        deleteTargetId = id;
        $('delete-target-label').textContent = `${code} — ${year}`;
        $('delete-msg').textContent = '';
        $('delete-msg').className = 'form-msg text-muted';
        $('delete-modal').classList.add('open');
    }

    function closeDeleteModal() {
        $('delete-modal').classList.remove('open');
        deleteTargetId = null;
    }

    async function confirmDelete() {
        if (!deleteTargetId) return;

        const token = document.querySelector('meta[name=csrf-token]')?.content;
        if (!token) { $('delete-msg').textContent = 'CSRF token missing.'; return; }

        setBtnLoading('delete-confirm-btn', true, 'Removing…');

        try {
            const res = await fetch(`${BASE}/${deleteTargetId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            });

            const d = await res.json();

            if (res.ok && d.success) {
                closeDeleteModal();
                loadData();
            } else {
                $('delete-msg').textContent = d.message || 'Failed to remove target.';
                $('delete-msg').className   = 'form-msg text-danger';
            }

        } catch (err) {
            $('delete-msg').textContent = 'Network error.';
            $('delete-msg').className   = 'form-msg text-danger';
        } finally {
            setBtnLoading('delete-confirm-btn', false);
        }
    }

    /* ─── Helpers ─── */
    function showFormMsg(msg, type) {
        const el = $('form-msg');
        el.textContent = msg;
        el.className   = `form-msg text-${type}`;
    }

    function clearFormMsg() {
        const el = $('form-msg');
        el.textContent = '';
        el.className   = 'form-msg text-muted';
    }

    function setBtnLoading(btnId, loading, label = 'Loading…') {
        const btn = $(btnId);
        if (!btn) return;
        if (loading) {
            btn.dataset.orig = btn.innerHTML;
            btn.disabled     = true;
            btn.innerHTML    = `<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> ${label}`;
        } else {
            btn.disabled  = false;
            btn.innerHTML = btn.dataset.orig || btn.innerHTML;
        }
    }

    /* ─── Keyboard ─── */
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeFormModal(); closeDeleteModal(); }
    });

    /* ─── Init ─── */
    document.addEventListener('DOMContentLoaded', () => {
        loadRmList();
        loadData();
    });
</script>
@endpush
