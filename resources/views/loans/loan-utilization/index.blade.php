@extends('layouts.loans.app')

@section('title', 'Loan Utilization')
@section('subtitle', 'Executive view of portfolio utilization, performance and NPL by product')

@push('styles')
    <style>
        .chart-box {
            position: relative;
            height: 280px;
        }
    </style>
@endpush

@section('content')

    {{-- ======================================================
         HERO
    ======================================================= --}}
    <section class="loan-hero">
        <div class="hero-content">
            <div class="hero-copy">
                <div class="hero-eyebrow">
                    <i class="fas fa-chart-pie"></i>
                    Portfolio Overview
                </div>

                <div class="hero-title">Loan Utilization</div>

                <div class="hero-description">
                    Portfolio exposure, performance and NPL broken down by product, refreshed from the latest
                    LOANS PORTFOLIO NEW upload. Download the full workbook or upload a new extract to refresh the data.
                </div>

                @if ($snapshot)
                    <div class="hero-actions">
                        <a href="{{ route('loans.loan-utilization.download', $snapshot->id) }}" class="btn btn-hero">
                            <i class="fas fa-file-excel"></i> Download Excel
                        </a>
                    </div>
                @endif
            </div>

            <div class="hero-panel">
                <div class="hero-panel-label">Snapshot</div>
                @if ($snapshot)
                    <div class="hero-panel-value">
                        As at {{ \Carbon\Carbon::parse($dashboard['as_of_date'])->format('d M Y') }}
                    </div>
                    <div class="hero-panel-meta">
                        <i class="fas fa-layer-group"></i>
                        {{ number_format($dashboard['totals']['volume']) }} loans
                    </div>

                    @if ($snapshots->count() > 1)
                        <form method="GET" action="{{ route('loans.loan-utilization.index') }}" style="margin-top: 10px;">
                            <select name="snapshot" aria-label="Choose snapshot" onchange="this.form.submit()">
                                @foreach ($snapshots as $s)
                                    <option value="{{ $s->id }}" {{ $snapshot->id === $s->id ? 'selected' : '' }}>
                                        As at {{ optional($s->as_of_date)->format('d M Y') }} ({{ number_format($s->total_rows) }} loans)
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                @else
                    <div class="hero-panel-value">No data yet</div>
                    <div class="hero-panel-meta"><i class="fas fa-upload"></i> Upload a report below</div>
                @endif
            </div>
        </div>
    </section>

    @if (!$snapshot)
        <section class="dashboard-card">
            <div class="empty">
                <i class="fas fa-chart-pie"></i>
                Upload a LOANS PORTFOLIO NEW report below to populate the dashboard.
            </div>
        </section>
    @else
        @php
            $t = $dashboard['totals'];
            $ragBadge = ['green' => 'badge-success', 'amber' => 'badge-warning', 'red' => 'badge-danger', 'none' => 'badge-muted'];
            $ragKpi = ['green' => 'kpi-green', 'amber' => 'kpi-amber', 'red' => 'kpi-red', 'none' => ''];
            $unmapped = collect($dashboard['products'])->firstWhere('product_name', 'Unmapped - Review');
        @endphp

        @if ($unmapped && $unmapped['volume'] > 0)
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-triangle-exclamation"></i>
                <div>
                    <strong>{{ $unmapped['volume'] }}</strong> loan(s) totalling
                    <strong>KES {{ number_format($unmapped['total']) }}</strong> could not be auto-categorized into a
                    product ("Unmapped - Review"). Check the Product_Mapping sheet in the downloaded workbook.
                </div>
            </div>
        @endif

        {{-- ======================================================
             KPI TILES
        ======================================================= --}}
        <div class="kpi-grid">
            <div class="kpi kpi-blue">
                <div class="kpi-top">
                    <div class="kpi-icon"><i class="fas fa-sack-dollar"></i></div>
                    <span class="kpi-mini-label">KES</span>
                </div>
                <div class="kpi-value" title="{{ number_format($t['total']) }}">{{ number_format($t['total']) }}</div>
                <div class="kpi-label">Total Portfolio O/S</div>
            </div>

            <div class="kpi kpi-green">
                <div class="kpi-top">
                    <div class="kpi-icon"><i class="fas fa-circle-check"></i></div>
                    <span class="kpi-mini-label">KES</span>
                </div>
                <div class="kpi-value" title="{{ number_format($t['performing']) }}">{{ number_format($t['performing']) }}</div>
                <div class="kpi-label">Performing</div>
            </div>

            <div class="kpi kpi-red">
                <div class="kpi-top">
                    <div class="kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
                    <span class="kpi-mini-label">KES</span>
                </div>
                <div class="kpi-value" title="{{ number_format($t['non_performing']) }}">{{ number_format($t['non_performing']) }}</div>
                <div class="kpi-label">Non-Performing</div>
            </div>

            <div class="kpi {{ $ragKpi[$t['rag_npl']] }}">
                <div class="kpi-top">
                    <div class="kpi-icon"><i class="fas fa-percent"></i></div>
                    <span class="badge {{ $ragBadge[$t['rag_npl']] }}">{{ $t['rag_npl'] }}</span>
                </div>
                <div class="kpi-value">{{ number_format($t['npl_ratio'] * 100, 1) }}%</div>
                <div class="kpi-label">NPL Ratio</div>
            </div>

            <div class="kpi {{ $t['utilisation'] !== null ? $ragKpi[$t['rag_utilisation']] : '' }}">
                <div class="kpi-top">
                    <div class="kpi-icon"><i class="fas fa-gauge-high"></i></div>
                    @if ($t['utilisation'] !== null)
                        <span class="badge {{ $ragBadge[$t['rag_utilisation']] }}">{{ number_format($t['utilisation'] * 100, 1) }}%</span>
                    @else
                        <span class="kpi-mini-label">No limits set</span>
                    @endif
                </div>
                <div class="kpi-value" title="{{ number_format($t['approved_limit']) }}">{{ number_format($t['approved_limit']) }}</div>
                <div class="kpi-label">Approved Limit (KES) / % Utilised</div>
            </div>
        </div>

        {{-- ======================================================
             CHARTS
        ======================================================= --}}
        <div class="grid-2">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-title">
                        <div class="dashboard-card-title-icon"><i class="fas fa-chart-pie"></i></div>
                        <div>
                            <h3>Performing vs Non-Performing</h3>
                            <div class="dashboard-card-subtitle">Share of total portfolio exposure</div>
                        </div>
                    </div>
                </div>
                <div class="chart-box"><canvas id="chartPerformance"></canvas></div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-title">
                        <div class="dashboard-card-title-icon"><i class="fas fa-chart-bar"></i></div>
                        <div>
                            <h3>Total Exposure by Product</h3>
                            <div class="dashboard-card-subtitle">Outstanding balance (LCY) per product category</div>
                        </div>
                    </div>
                </div>
                <div class="chart-box"><canvas id="chartByProduct"></canvas></div>
            </div>
        </div>

        <div class="grid-2">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-title">
                        <div class="dashboard-card-title-icon"><i class="fas fa-triangle-exclamation"></i></div>
                        <div>
                            <h3>NPL Ratio by Product</h3>
                            <div class="dashboard-card-subtitle">Red / Amber / Green against policy thresholds</div>
                        </div>
                    </div>
                </div>
                <div class="chart-box"><canvas id="chartNpl"></canvas></div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-title">
                        <div class="dashboard-card-title-icon"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <h3>Disbursement Trend by Product</h3>
                            <div class="dashboard-card-subtitle">YTD / MTD / WTD / Last Day exposure</div>
                        </div>
                    </div>
                </div>
                <div class="chart-box"><canvas id="chartTrend"></canvas></div>
            </div>
        </div>

        {{-- ======================================================
             PRODUCT BREAKDOWN
        ======================================================= --}}
        <section class="dashboard-card flush">
            <form method="POST" action="{{ route('loans.loan-utilization.approved-limits') }}">
                @csrf

                <div class="dashboard-card-header">
                    <div class="dashboard-card-title">
                        <div class="dashboard-card-title-icon"><i class="fas fa-table-list"></i></div>
                        <div>
                            <h3>Product Breakdown</h3>
                            <div class="dashboard-card-subtitle">
                                Approved Limit is a manual input (board-approved product ceiling). Edit and save.
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-floppy-disk"></i> Save Approved Limits
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Approved Limit</th>
                                <th class="num">Performing</th>
                                <th class="num">Non-Performing</th>
                                <th class="num">Total O/S</th>
                                <th>% Utilised</th>
                                <th>NPL Ratio</th>
                                <th class="num">Volume</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dashboard['products'] as $p)
                                <tr>
                                    <td class="wrap strong">{{ $p['product_name'] }}</td>
                                    <td>
                                        <input type="text" inputmode="numeric" style="width: 150px; text-align: right;"
                                            aria-label="Approved limit for {{ $p['product_name'] }}"
                                            name="approved_limit[{{ $p['product_name'] }}]"
                                            value="{{ number_format($p['approved_limit'], 0, '.', '') }}">
                                    </td>
                                    <td class="num">{{ number_format($p['performing']) }}</td>
                                    <td class="num">{{ number_format($p['non_performing']) }}</td>
                                    <td class="num strong">{{ number_format($p['total']) }}</td>
                                    <td>
                                        @if ($p['utilisation'] !== null)
                                            <span class="badge {{ $ragBadge[$p['rag_utilisation']] }}">{{ number_format($p['utilisation'] * 100, 1) }}%</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $ragBadge[$p['rag_npl']] }}">{{ number_format($p['npl_ratio'] * 100, 1) }}%</span>
                                    </td>
                                    <td class="num">{{ number_format($p['volume']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </section>
    @endif

    {{-- ======================================================
         UPLOAD
    ======================================================= --}}
    <section class="dashboard-card">
        <div class="dashboard-card-header">
            <div class="dashboard-card-title">
                <div class="dashboard-card-title-icon"><i class="fas fa-upload"></i></div>
                <div>
                    <h3>Upload LOANS PORTFOLIO NEW Report</h3>
                    <div class="dashboard-card-subtitle">
                        Each upload adds a new snapshot. History is kept, so you can switch snapshots in the banner above.
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('loans.loan-utilization.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid-2" style="margin-bottom: 0;">
                <div>
                    <label class="field-label" for="loans_portfolio_file">LOANS PORTFOLIO NEW File <span class="req">*</span></label>
                    @include('loans.partials.file-drop', [
                        'id' => 'loans_portfolio_file',
                        'name' => 'loans_portfolio_file',
                        'accept' => '',
                    ])
                </div>

                <div>
                    <label class="field-label">Required Columns</label>
                    @foreach (['Contract No', 'Account Name', 'Exposure Amount(lcy)', 'Past Due Days', 'User Defined Status', 'Frr', 'Orr', 'Credit Line', 'Gl Name', 'Business Segment', 'Industry Segment', 'Value Dt'] as $col)
                        <span class="tag">{{ $col }}</span>
                    @endforeach
                </div>
            </div>

            <div style="margin-top: 16px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-arrows-rotate"></i> Upload &amp; Refresh
                </button>
            </div>
        </form>
    </section>

@endsection

@if ($snapshot)
    @php
        $ragColorMap = ['green' => '#27945A', 'amber' => '#E6A11B', 'red' => '#D64545', 'none' => '#98A2B3'];
        $chartProducts = collect($dashboard['products']);

        $jsProductNames = $chartProducts->pluck('product_name');
        $jsPerforming = $chartProducts->pluck('performing');
        $jsNonPerforming = $chartProducts->pluck('non_performing');
        $jsTotals = $chartProducts->pluck('total');
        $jsNplRatios = $chartProducts->pluck('npl_ratio')->map(function ($v) {
            return round($v * 100, 1);
        });
        $jsRagColors = $chartProducts->pluck('rag_npl')->map(function ($v) use ($ragColorMap) {
            return $ragColorMap[$v];
        });
        $jsYtd = $chartProducts->pluck('ytd');
        $jsMtd = $chartProducts->pluck('mtd');
        $jsWtd = $chartProducts->pluck('wtd');
        $jsLastDay = $chartProducts->pluck('last_day');
    @endphp
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            const products = @json($jsProductNames);
            const performing = @json($jsPerforming);
            const nonPerforming = @json($jsNonPerforming);
            const totals = @json($jsTotals);
            const nplRatios = @json($jsNplRatios);
            const ragColors = @json($jsRagColors);
            const ytd = @json($jsYtd);
            const mtd = @json($jsMtd);
            const wtd = @json($jsWtd);
            const lastDay = @json($jsLastDay);

            const totalPerforming = @json($t['performing']);
            const totalNonPerforming = @json($t['non_performing']);

            Chart.defaults.font.family = "'Montserrat', sans-serif";
            Chart.defaults.font.size = 11;
            Chart.defaults.color = '#667085';
            Chart.defaults.borderColor = '#EDF1F5';
            Chart.defaults.maintainAspectRatio = false;

            const barStyle = { borderRadius: 4, maxBarThickness: 28 };

            new Chart(document.getElementById('chartPerformance'), {
                type: 'doughnut',
                data: {
                    labels: ['Performing', 'Non-Performing'],
                    datasets: [{
                        data: [totalPerforming, totalNonPerforming],
                        backgroundColor: ['#0082BB', '#D64545'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }],
                },
                options: { cutout: '64%', plugins: { legend: { position: 'bottom' } } },
            });

            new Chart(document.getElementById('chartByProduct'), {
                type: 'bar',
                data: {
                    labels: products,
                    datasets: [{ label: 'Total O/S (KES)', data: totals, backgroundColor: '#0082BB', ...barStyle }],
                },
                options: {
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: { x: { ticks: { callback: (v) => Number(v).toLocaleString() } } },
                },
            });

            new Chart(document.getElementById('chartNpl'), {
                type: 'bar',
                data: {
                    labels: products,
                    datasets: [{ label: 'NPL Ratio (%)', data: nplRatios, backgroundColor: ragColors, ...barStyle }],
                },
                options: { plugins: { legend: { display: false } } },
            });

            new Chart(document.getElementById('chartTrend'), {
                type: 'bar',
                data: {
                    labels: products,
                    datasets: [
                        { label: 'YTD', data: ytd, backgroundColor: '#005B82', ...barStyle },
                        { label: 'MTD', data: mtd, backgroundColor: '#00A9CE', ...barStyle },
                        { label: 'WTD', data: wtd, backgroundColor: '#BED600', ...barStyle },
                        { label: 'Last Day', data: lastDay, backgroundColor: '#E99A28', ...barStyle },
                    ],
                },
                options: {
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { ticks: { callback: (v) => Number(v).toLocaleString() } } },
                },
            });
        </script>
    @endpush
@endif
