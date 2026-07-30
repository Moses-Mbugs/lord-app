@extends('layouts.finance.template')

@section('title', 'Sub Segment Movement')

@section('content')
    <style>
        :root {
            --ecobank-light-blue: #0082BB;
            --ecobank-dark-blue: #005B82;
            --ecobank-light-green: #BED600;
            --ecobank-dark-green: #669438;
            --ecobank-gray: #464646;
            --ecobank-light-gray: #EDEDED;
            --ecobank-mid-gray: #979797;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);

            /* Extra accent palette for a livelier dashboard */
            --accent-purple: #7C3AED;
            --accent-pink: #EC4899;
            --accent-orange: #F97316;
            --accent-teal: #14B8A6;
            --accent-amber: #F59E0B;
            --accent-red: #EF4444;
        }

        body {
            background: linear-gradient(180deg, #F3F8FC 0%, #EEF6EE 100%);
        }

        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0, 130, 187, 0.08);
            border-top: 4px solid transparent;
            border-image: linear-gradient(90deg, var(--ecobank-light-blue), var(--accent-purple), var(--ecobank-light-green)) 1;
        }

        .dashboard-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .dashboard-title {
            background: linear-gradient(90deg, var(--ecobank-dark-blue), var(--accent-purple));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
        }

        .section-title {
            color: var(--ecobank-dark-blue);
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 18px;
            border-radius: 3px;
            background: linear-gradient(180deg, var(--ecobank-light-blue), var(--accent-pink));
        }

        .form-group label {
            font-weight: 600;
            color: var(--ecobank-dark-blue);
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid var(--ecobank-light-gray);
            border-radius: 8px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--ecobank-light-blue);
            box-shadow: 0 0 0 0.2rem rgba(0, 130, 187, 0.15);
        }

        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: var(--ecobank-light-blue);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--ecobank-dark-blue);
            transform: translateY(-1px);
            box-shadow: var(--hover-shadow);
        }

        .btn-success {
            background: var(--ecobank-light-green);
            color: var(--ecobank-dark-blue);
        }

        .btn-success:hover {
            background: var(--ecobank-dark-green);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: var(--hover-shadow);
        }

        .btn-secondary {
            background: #fff;
            color: var(--ecobank-light-blue);
            border: 2px solid var(--ecobank-light-blue);
        }

        .btn-secondary:hover {
            background: #f0f9ff;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .metric-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            border-left: 4px solid var(--ecobank-light-green);
            height: 100%;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }

        /* Distinct accent per metric so the row reads as a colorful strip */
        .metric-card.accent-blue {
            border-left-color: var(--ecobank-light-blue);
        }

        .metric-card.accent-green {
            border-left-color: var(--ecobank-dark-green);
        }

        .metric-card.accent-purple {
            border-left-color: var(--accent-purple);
        }

        .metric-card.accent-teal {
            border-left-color: var(--accent-teal);
        }

        .metric-card.accent-amber {
            border-left-color: var(--accent-amber);
        }

        .metric-card.accent-pink {
            border-left-color: var(--accent-pink);
        }

        .metric-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }

        .metric-card.accent-blue .metric-icon {
            background: rgba(0, 130, 187, 0.12);
            color: var(--ecobank-light-blue);
        }

        .metric-card.accent-green .metric-icon {
            background: rgba(102, 148, 56, 0.12);
            color: var(--ecobank-dark-green);
        }

        .metric-card.accent-purple .metric-icon {
            background: rgba(124, 58, 237, 0.12);
            color: var(--accent-purple);
        }

        .metric-card.accent-teal .metric-icon {
            background: rgba(20, 184, 166, 0.12);
            color: var(--accent-teal);
        }

        .metric-card.accent-amber .metric-icon {
            background: rgba(245, 158, 11, 0.12);
            color: var(--accent-amber);
        }

        .metric-card.accent-pink .metric-icon {
            background: rgba(236, 72, 153, 0.12);
            color: var(--accent-pink);
        }

        .metric-label {
            color: var(--ecobank-mid-gray);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .metric-value {
            color: var(--ecobank-dark-blue);
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0;
        }

        .metric-value.text-positive {
            color: var(--ecobank-dark-green);
        }

        .metric-value.text-negative {
            color: var(--accent-red);
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            background: white;
        }

        .table thead th {
            background: linear-gradient(90deg, var(--ecobank-dark-blue), var(--ecobank-light-blue) 60%, var(--accent-teal));
            color: white;
            border: none;
            font-weight: 600;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .modal-xl {
            max-width: 90%;
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: var(--hover-shadow);
        }

        .modal-header.bg-ecobank {
            background: linear-gradient(135deg, var(--accent-purple), var(--ecobank-light-blue), var(--ecobank-light-green)) !important;
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }

        .text-ecobank {
            color: var(--ecobank-dark-blue);
        }

        .status-text {
            color: var(--ecobank-mid-gray);
            font-size: 0.9rem;
        }

        .status-text.is-error {
            color: #B91C1C;
            font-weight: 600;
        }

        /* Chart scroll wrapper */
        #subSegmentMovementChart {
            overflow-x: auto;
            overflow-y: hidden;
        }

        @media (max-width: 768px) {
            .modal-xl {
                max-width: 95%;
            }
        }
    </style>

    <div class="container-fluid mt-3">
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <h3 class="dashboard-title">Sub Segment Movement Dashboard</h3>
            </div>

            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" class="form-control"
                            value="{{ now()->startOfMonth()->toDateString() }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label for="business">Business</label>
                        <select id="business" class="form-control">
                            <option value="">All</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label for="business_segment_name">Parent Segment</label>
                        <select id="business_segment_name" class="form-control">
                            <option value="">All</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label for="mis_code">MIS Code</label>
                        <input type="text" id="mis_code" class="form-control" placeholder="e.g. CBRC_2200">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" class="form-control" placeholder="Description / code">
                    </div>
                </div>
            </div>

            <div class="mt-2">
                <button type="button" class="btn btn-success mr-2" id="btnBuildAndLoad">Build &amp; Load</button>
                <button type="button" class="btn btn-primary mr-2" id="btnLoadOnly">Load Existing</button>
                <button type="button" class="btn btn-secondary" id="btnResetFilters">Reset Filters</button>
            </div>

            <div class="mt-3">
                <small class="status-text" id="buildStatus"></small>
            </div>
        </div>

        <div class="row">
            <div class="col-md-2 mb-3">
                <div class="metric-card accent-blue">
                    <div class="metric-icon">💰</div>
                    <div class="metric-label">Start Balance</div>
                    <h5 class="metric-value" id="cardStartBalance">0.00</h5>
                </div>
            </div>

            <div class="col-md-2 mb-3">
                <div class="metric-card accent-purple">
                    <div class="metric-icon">🏦</div>
                    <div class="metric-label">End Balance</div>
                    <h5 class="metric-value" id="cardEndBalance">0.00</h5>
                </div>
            </div>

            <div class="col-md-2 mb-3">
                <div class="metric-card accent-teal">
                    <div class="metric-icon">📈</div>
                    <div class="metric-label">Movement</div>
                    <h5 class="metric-value" id="cardMovement">0.00</h5>
                </div>
            </div>

            <div class="col-md-2 mb-3">
                <div class="metric-card accent-amber">
                    <div class="metric-icon">👥</div>
                    <div class="metric-label">Total CIFs</div>
                    <h5 class="metric-value" id="cardCifCount">0</h5>
                </div>
            </div>

            <div class="col-md-2 mb-3">
                <div class="metric-card accent-green">
                    <div class="metric-icon">🚀</div>
                    <div class="metric-label">Biggest Positive</div>
                    <h6 class="metric-value text-positive" id="cardPositiveMover">-</h6>
                </div>
            </div>

            <div class="col-md-2 mb-3">
                <div class="metric-card accent-pink">
                    <div class="metric-icon">📉</div>
                    <div class="metric-label">Biggest Negative</div>
                    <h6 class="metric-value text-negative" id="cardNegativeMover">-</h6>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <h5 class="section-title">Top 15 Sub Segment Movers</h5>
            <div id="subSegmentMovementChart" style="height: 500px;"></div>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <h5 class="section-title mb-0">Movement Details</h5>
                <span class="status-text" id="rowCountLabel">0 rows</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Business</th>
                            <th>Parent Segment</th>
                            <th>Sub Segment</th>
                            <th>MIS Code</th>
                            <th>Description</th>
                            <th class="text-right">Start Balance</th>
                            <th class="text-right">End Balance</th>
                            <th class="text-right">Movement</th>
                            <th class="text-right">CIF Count</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="resultsTableBody">
                        <tr>
                            <td colspan="10" class="text-center text-muted">No data loaded yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="drilldownModal" tabindex="-1" role="dialog" aria-labelledby="drilldownModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-ecobank">
                    <h5 id="drilldownModalLabel" class="modal-title">Sub Segment Drilldown</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span class="text-white">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>CIF</th>
                                    <th>Business</th>
                                    <th>Parent Segment</th>
                                    <th>Sub Segment</th>
                                    <th>MIS Code</th>
                                    <th>Description</th>
                                    <th class="text-right">Start Balance</th>
                                    <th class="text-right">End Balance</th>
                                    <th class="text-right">Movement</th>
                                </tr>
                            </thead>
                            <tbody id="drilldownTableBody">
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No drilldown data yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

    <script>
        (function() {
            const routes = {
                build: @json(route('finance.sub-segment-movement.build')),
                data: @json(route('finance.sub-segment-movement.data')),
                drilldown: @json(route('finance.sub-segment-movement.drilldown')),
            };

            function getCsrfToken() {
                // Read fresh each time in case the meta tag / session is refreshed client-side.
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            }

            const els = {
                startDate: document.getElementById('start_date'),
                endDate: document.getElementById('end_date'),
                business: document.getElementById('business'),
                businessSegmentName: document.getElementById('business_segment_name'),
                misCode: document.getElementById('mis_code'),
                search: document.getElementById('search'),
                btnBuildAndLoad: document.getElementById('btnBuildAndLoad'),
                btnLoadOnly: document.getElementById('btnLoadOnly'),
                btnResetFilters: document.getElementById('btnResetFilters'),
                buildStatus: document.getElementById('buildStatus'),
                rowCountLabel: document.getElementById('rowCountLabel'),
                resultsTableBody: document.getElementById('resultsTableBody'),
                drilldownTableBody: document.getElementById('drilldownTableBody'),
                cardStartBalance: document.getElementById('cardStartBalance'),
                cardEndBalance: document.getElementById('cardEndBalance'),
                cardMovement: document.getElementById('cardMovement'),
                cardCifCount: document.getElementById('cardCifCount'),
                cardPositiveMover: document.getElementById('cardPositiveMover'),
                cardNegativeMover: document.getElementById('cardNegativeMover'),
            };

            /* ── Helpers ───────────────────────────────────────── */

            // Escape untrusted text before injecting into innerHTML — prevents XSS
            // from descriptions / codes / segment names that may contain HTML chars.
            function esc(v) {
                return String(v ?? '').replace(/[&<>"']/g, c => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                } [c]));
            }

            // Shared magnitude-suffix logic used by both the axis and tooltip formatters.
            function magnitudeParts(v) {
                const a = Math.abs(v);
                if (a >= 1e9) return {
                    scaled: a / 1e9,
                    suffix: 'B'
                };
                if (a >= 1e6) return {
                    scaled: a / 1e6,
                    suffix: 'M'
                };
                if (a >= 1e3) return {
                    scaled: a / 1e3,
                    suffix: 'K'
                };
                return {
                    scaled: a,
                    suffix: ''
                };
            }

            function fAxis(v) {
                const {
                    scaled,
                    suffix
                } = magnitudeParts(v);
                return suffix ? `${(v < 0 ? -scaled : scaled).toFixed(1)}${suffix}` : v.toFixed(0);
            }

            function fKes(v) {
                const s = v < 0 ? '-' : '';
                const {
                    scaled,
                    suffix
                } = magnitudeParts(v);
                return suffix ? `${s}KES ${scaled.toFixed(2)}${suffix}` : `${s}KES ${scaled.toFixed(2)}`;
            }

            function getFilters() {
                return {
                    start_date: els.startDate.value,
                    end_date: els.endDate.value,
                    business: els.business.value,
                    business_segment_name: els.businessSegmentName.value,
                    mis_code: els.misCode.value,
                    search: els.search.value,
                };
            }

            function validateDates() {
                if (!els.startDate.value || !els.endDate.value) {
                    showStatus('Please select both start date and end date.', true);
                    return false;
                }
                if (els.endDate.value < els.startDate.value) {
                    showStatus('End date cannot be earlier than start date.', true);
                    return false;
                }
                return true;
            }

            function numberFormat(value) {
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(Number(value || 0));
            }

            function setLoading(isLoading) {
                els.btnBuildAndLoad.disabled = isLoading;
                els.btnLoadOnly.disabled = isLoading;
                els.btnResetFilters.disabled = isLoading;
            }

            // Inline, non-blocking status/error display instead of alert().
            function showStatus(message, isError = false) {
                els.buildStatus.textContent = message;
                els.buildStatus.classList.toggle('is-error', !!isError);
            }

            function resetFilters() {
                els.business.value = '';
                els.businessSegmentName.value = '';
                els.misCode.value = '';
                els.search.value = '';
            }

            function populateSelect(selectEl, values, selectedValue = '') {
                const current = selectedValue || selectEl.value || '';
                selectEl.innerHTML = `<option value="">All</option>`;
                (values || []).forEach(value => {
                    const selected = current === value ? 'selected' : '';
                    selectEl.insertAdjacentHTML('beforeend',
                        `<option value="${esc(value)}" ${selected}>${esc(value)}</option>`);
                });
            }

            function updateCards(summary) {
                els.cardStartBalance.textContent = numberFormat(summary.start_balance);
                els.cardEndBalance.textContent = numberFormat(summary.end_balance);

                const movement = Number(summary.movement || 0);
                els.cardMovement.textContent = numberFormat(movement);
                els.cardMovement.classList.toggle('text-positive', movement > 0);
                els.cardMovement.classList.toggle('text-negative', movement < 0);

                els.cardCifCount.textContent = Number(summary.cif_count || 0).toLocaleString();
                els.cardPositiveMover.textContent = summary.biggest_positive_mover || '-';
                els.cardNegativeMover.textContent = summary.biggest_negative_mover || '-';
                els.rowCountLabel.textContent = `${summary.rows_count || 0} rows`;
            }

            /* ── Chart.js bar chart ────────────────────────────── */
            let chartInstance = null;

            function renderChart(chartRows) {
                const shell = document.getElementById('subSegmentMovementChart');

                if (chartInstance) {
                    chartInstance.destroy();
                    chartInstance = null;
                }

                shell.innerHTML = '<canvas id="subSegmentCanvas"></canvas>';
                const canvas = document.getElementById('subSegmentCanvas');

                // Dynamically widen canvas so all bars are readable
                const rowH = 34;
                const minH = shell.clientHeight || 500;
                const calcH = Math.max(minH, chartRows.length * rowH + 60);
                canvas.style.width = '100%';
                canvas.style.height = calcH + 'px';
                canvas.height = calcH;

                const values = chartRows.map(r => Number(r.y || 0));

                // Build a per-bar gradient (positive → teal/green, negative → pink/red)
                // so the chart reads as colorful rather than flat two-tone.
                const ctx = canvas.getContext('2d');
                const colors = values.map(v => {
                    if (v >= 0) {
                        const g = ctx.createLinearGradient(0, 0, canvas.width, 0);
                        g.addColorStop(0, 'rgba(20,184,166,0.85)'); // teal
                        g.addColorStop(1, 'rgba(0,130,187,0.85)'); // ecobank blue
                        return g;
                    }
                    const g = ctx.createLinearGradient(0, 0, canvas.width, 0);
                    g.addColorStop(0, 'rgba(239,68,68,0.85)'); // red
                    g.addColorStop(1, 'rgba(236,72,153,0.85)'); // pink
                    return g;
                });
                const borderColors = values.map(v =>
                    v >= 0 ? '#0F766E' : '#B91C1C'
                );

                chartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartRows.map(r => r.label),
                        datasets: [{
                            label: 'Movement',
                            data: values,
                            backgroundColor: colors,
                            borderColor: borderColors,
                            borderWidth: 1.5,
                            borderRadius: 4,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        maintainAspectRatio: false,
                        responsive: true,
                        animation: {
                            duration: 350
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(255,255,255,0.98)',
                                borderWidth: 1,
                                borderColor: 'rgba(0,130,187,0.20)',
                                titleColor: '#005B82',
                                bodyColor: '#464646',
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label(c) {
                                        return ` Movement: ${fKes(c.parsed.x)}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: 'rgba(0,0,0,0.05)'
                                },
                                ticks: {
                                    callback: v => fAxis(v),
                                    font: {
                                        size: 10
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Movement (KES)',
                                    font: {
                                        size: 10
                                    },
                                    color: '#8A96A8'
                                }
                            },
                            y: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        }
                    }
                });
            }

            /* ── Table ─────────────────────────────────────────── */
            function renderTable(rows) {
                if (!rows || rows.length === 0) {
                    els.resultsTableBody.innerHTML = `
                        <tr>
                            <td colspan="10" class="text-center text-muted">No movement data found for the selected filters.</td>
                        </tr>`;
                    return;
                }

                els.resultsTableBody.innerHTML = rows.map(row => `
                    <tr>
                        <td>${esc(row.business)}</td>
                        <td>${esc(row.business_segment_name)}</td>
                        <td>${esc(row.business_seg_short)}</td>
                        <td>${esc(row.mis_code)}</td>
                        <td>${esc(row.code_desc)}</td>
                        <td class="text-right">${numberFormat(row.start_balance)}</td>
                        <td class="text-right">${numberFormat(row.end_balance)}</td>
                        <td class="text-right">${numberFormat(row.movement)}</td>
                        <td class="text-right">${Number(row.cif_count || 0).toLocaleString()}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" data-mis-code="${esc(row.mis_code)}">
                                Drilldown
                            </button>
                        </td>
                    </tr>`).join('');
            }

            /* ── Build & Load ──────────────────────────────────── */
            async function buildAndLoad() {
                if (!validateDates()) return;

                setLoading(true);
                showStatus('Building sub-segment movement data...');

                try {
                    const response = await fetch(routes.build, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            start_date: els.startDate.value,
                            end_date: els.endDate.value,
                        }),
                    });

                    const payload = await response.json();

                    if (!response.ok) throw new Error(payload.message || 'Build failed.');

                    showStatus(payload.message || 'Build completed.');
                    await loadData();
                } catch (error) {
                    showStatus(error.message || 'Build failed.', true);
                } finally {
                    setLoading(false);
                }
            }

            /* ── Load Data ─────────────────────────────────────── */
            let loadController = null;

            async function loadData() {
                if (!validateDates()) return;

                // Cancel any in-flight load so an older, slower response can't
                // overwrite a newer one (out-of-order race condition).
                loadController?.abort();
                loadController = new AbortController();
                const {
                    signal
                } = loadController;

                setLoading(true);
                showStatus('Loading sub-segment movement data...');

                try {
                    const params = new URLSearchParams(getFilters()).toString();
                    const response = await fetch(`${routes.data}?${params}`, {
                        headers: {
                            'Accept': 'application/json'
                        },
                        signal,
                    });

                    const payload = await response.json();

                    if (!response.ok) throw new Error(payload.message || 'Load failed.');

                    updateCards(payload.summary || {});
                    renderChart(payload.chart || []);
                    renderTable(payload.rows || []);
                    populateSelect(els.business, payload.filters?.businesses || [], els.business.value);
                    populateSelect(els.businessSegmentName, payload.filters?.segments || [],
                        els.businessSegmentName.value);

                    showStatus(payload.summary?.last_build_at ?
                        `Loaded successfully. Last build/update: ${payload.summary.last_build_at}` :
                        'Loaded successfully.');
                } catch (error) {
                    if (error.name === 'AbortError') return; // superseded by a newer request
                    showStatus(error.message || 'Load failed.', true);
                } finally {
                    setLoading(false);
                }
            }

            /* ── Drilldown ─────────────────────────────────────── */
            let drilldownController = null;

            async function openSubSegmentDrilldown(misCode) {
                if (!validateDates()) return;

                drilldownController?.abort();
                drilldownController = new AbortController();
                const {
                    signal
                } = drilldownController;

                els.drilldownTableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center text-muted">Loading drilldown...</td>
                    </tr>`;

                try {
                    const params = new URLSearchParams({
                        start_date: els.startDate.value,
                        end_date: els.endDate.value,
                        mis_code: misCode,
                    }).toString();

                    const response = await fetch(`${routes.drilldown}?${params}`, {
                        headers: {
                            'Accept': 'application/json'
                        },
                        signal,
                    });

                    const payload = await response.json();

                    if (!response.ok) throw new Error(payload.message || 'Failed to load drilldown.');

                    const rows = payload.rows || [];

                    if (rows.length === 0) {
                        els.drilldownTableBody.innerHTML = `
                            <tr>
                                <td colspan="9" class="text-center text-muted">No CIF details found.</td>
                            </tr>`;
                    } else {
                        els.drilldownTableBody.innerHTML = rows.map(row => `
                            <tr>
                                <td>${esc(row.cif)}</td>
                                <td>${esc(row.business)}</td>
                                <td>${esc(row.business_segment_name)}</td>
                                <td>${esc(row.business_seg_short)}</td>
                                <td>${esc(row.mis_code)}</td>
                                <td>${esc(row.code_desc)}</td>
                                <td class="text-right">${numberFormat(row.start_balance)}</td>
                                <td class="text-right">${numberFormat(row.end_balance)}</td>
                                <td class="text-right">${numberFormat(row.movement)}</td>
                            </tr>`).join('');
                    }

                    $('#drilldownModal').modal('show');
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    els.drilldownTableBody.innerHTML = `
                        <tr>
                            <td colspan="9" class="text-center text-muted">${esc(error.message || 'Failed to load drilldown.')}</td>
                        </tr>`;
                    $('#drilldownModal').modal('show');
                }
            }

            /* ── Event listeners ───────────────────────────────── */
            els.btnBuildAndLoad.addEventListener('click', buildAndLoad);
            els.btnLoadOnly.addEventListener('click', loadData);
            els.btnResetFilters.addEventListener('click', function() {
                resetFilters();
                loadData();
            });

            // Filter dropdowns now trigger a reload automatically.
            els.business.addEventListener('change', loadData);
            els.businessSegmentName.addEventListener('change', loadData);

            // Enter key in text filters triggers a reload.
            els.misCode.addEventListener('keydown', e => {
                if (e.key === 'Enter') loadData();
            });
            els.search.addEventListener('keydown', e => {
                if (e.key === 'Enter') loadData();
            });

            // Event delegation for drilldown buttons instead of inline onclick
            // (avoids breaking on special characters in mis_code and avoids
            // exposing a function on window).
            els.resultsTableBody.addEventListener('click', e => {
                const btn = e.target.closest('[data-mis-code]');
                if (btn) openSubSegmentDrilldown(btn.dataset.misCode);
            });

            loadData();
        })();
    </script>
@endpush

