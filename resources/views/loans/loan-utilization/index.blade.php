@extends('layouts.finance.template')

@section('title', 'Loan Utilization')

@include('loans.partials.heading-font')

@push('styles')
    <style>
        .loan-stat-label {
            margin-bottom: 6px;
            color: var(--finance-muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .055em;
            text-transform: uppercase;
        }

        .loan-stat-value {
            overflow: hidden;
            color: var(--finance-text-strong);
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.2;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .loan-chart {
            position: relative;
            height: 280px;
        }

        .loan-col-tag {
            display: inline-block;
            margin: 0 4px 4px 0;
            padding: 3px 8px;
            color: var(--finance-muted);
            background: var(--finance-surface-soft);
            border: 1px solid var(--finance-border);
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 600;
        }
    </style>
@endpush

@section('content')

    <div class="page-header">
        <div>
            <h4 class="page-title loan-script-title">Loan Utilization</h4>
            <p class="text-muted small mb-0">
                Portfolio exposure, performance and NPL by product, from the latest LOANS PORTFOLIO NEW upload.
            </p>
        </div>

        @if ($snapshot)
            <div class="d-flex gap-2 flex-wrap align-items-center">
                @if ($snapshots->count() > 1)
                    <form method="GET" action="{{ route('loans.loan-utilization.index') }}">
                        <select name="snapshot" class="form-select" aria-label="Choose snapshot"
                            onchange="this.form.submit()">
                            @foreach ($snapshots as $s)
                                <option value="{{ $s->id }}" {{ $snapshot->id === $s->id ? 'selected' : '' }}>
                                    As at {{ optional($s->as_of_date)->format('d M Y') }} ({{ number_format($s->total_rows) }} loans)
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <a href="{{ route('loans.loan-utilization.download', $snapshot->id) }}" class="btn btn-primary">
                    <i class="ti ti-download"></i> Download Excel
                </a>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2 small" role="alert">
            <strong>Please correct the following:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!$snapshot)
        <div class="card mb-4">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-chart-pie d-block mb-2" style="font-size: 30px;"></i>
                <div class="fw-bold text-body">No data yet</div>
                Upload a LOANS PORTFOLIO NEW report below to populate the dashboard.
            </div>
        </div>
    @else
        @php
            $t = $dashboard['totals'];
            $ragClass = [
                'green' => 'finance-badge-success',
                'amber' => 'finance-badge-warning',
                'red' => 'finance-badge-danger',
                'none' => 'finance-badge-info',
            ];
            $unmapped = collect($dashboard['products'])->firstWhere('product_name', 'Unmapped - Review');
        @endphp

        @if ($unmapped && $unmapped['volume'] > 0)
            <div class="alert alert-warning py-2 small" role="alert">
                <i class="ti ti-alert-triangle"></i>
                <strong>{{ $unmapped['volume'] }}</strong> loan(s) totalling
                <strong>KES {{ number_format($unmapped['total']) }}</strong> could not be auto-categorized into a
                product ("Unmapped - Review"). Check the Product_Mapping sheet in the downloaded workbook.
            </div>
        @endif

        {{-- Headline numbers --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="loan-stat-label">Total Portfolio O/S</div>
                    <div class="loan-stat-value">KES {{ number_format($t['total']) }}</div>
                    <div class="text-muted small">
                        {{ number_format($t['volume']) }} loans · as at
                        {{ \Carbon\Carbon::parse($dashboard['as_of_date'])->format('d M Y') }}
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="loan-stat-label">Performing / Non-Performing</div>
                    <div class="loan-stat-value">KES {{ number_format($t['performing']) }}</div>
                    <div class="text-muted small">
                        <span class="text-danger fw-semibold">KES {{ number_format($t['non_performing']) }}</span> non-performing
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="loan-stat-label">NPL Ratio</div>
                    <div class="loan-stat-value">{{ number_format($t['npl_ratio'] * 100, 1) }}%</div>
                    <span class="finance-badge {{ $ragClass[$t['rag_npl']] }}">{{ strtoupper($t['rag_npl']) }}</span>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="loan-stat-label">Approved Limit / % Utilised</div>
                    <div class="loan-stat-value">KES {{ number_format($t['approved_limit']) }}</div>
                    @if ($t['utilisation'] !== null)
                        <span class="finance-badge {{ $ragClass[$t['rag_utilisation']] }}">
                            {{ number_format($t['utilisation'] * 100, 1) }}% utilised
                        </span>
                    @else
                        <span class="text-muted small">No limits set</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Charts --}}
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Performing vs Non-Performing</h6>
                        <div class="text-muted small">Share of total portfolio exposure</div>
                    </div>
                    <div class="card-body">
                        <div class="loan-chart"><canvas id="chartPerformance"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Total Exposure by Product</h6>
                        <div class="text-muted small">Outstanding balance (LCY) per product category</div>
                    </div>
                    <div class="card-body">
                        <div class="loan-chart"><canvas id="chartByProduct"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="card-title mb-0">NPL Ratio by Product</h6>
                        <div class="text-muted small">Red / Amber / Green against policy thresholds</div>
                    </div>
                    <div class="card-body">
                        <div class="loan-chart"><canvas id="chartNpl"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Disbursement Trend by Product</h6>
                        <div class="text-muted small">YTD / MTD / WTD / Last Day exposure</div>
                    </div>
                    <div class="card-body">
                        <div class="loan-chart"><canvas id="chartTrend"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Product breakdown --}}
        <div class="card mb-4">
            <form method="POST" action="{{ route('loans.loan-utilization.approved-limits') }}">
                @csrf

                <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <div>
                        <h6 class="card-title mb-0">Product Breakdown</h6>
                        <div class="text-muted small">
                            Approved Limit is a manual input (board-approved product ceiling). Edit and save below.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="ti ti-device-floppy"></i> Save Approved Limits
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle text-nowrap">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Approved Limit</th>
                                <th class="text-end">Performing</th>
                                <th class="text-end">Non-Performing</th>
                                <th class="text-end">Total O/S</th>
                                <th>% Utilised</th>
                                <th>NPL Ratio</th>
                                <th class="text-end">Volume</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dashboard['products'] as $p)
                                <tr>
                                    <td class="text-wrap fw-semibold" style="min-width: 220px;">{{ $p['product_name'] }}</td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm text-end" style="width: 160px;"
                                            inputmode="numeric" aria-label="Approved limit for {{ $p['product_name'] }}"
                                            name="approved_limit[{{ $p['product_name'] }}]"
                                            value="{{ number_format($p['approved_limit'], 0, '.', '') }}">
                                    </td>
                                    <td class="text-end">{{ number_format($p['performing']) }}</td>
                                    <td class="text-end">{{ number_format($p['non_performing']) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($p['total']) }}</td>
                                    <td>
                                        @if ($p['utilisation'] !== null)
                                            <span class="finance-badge {{ $ragClass[$p['rag_utilisation']] }}">{{ number_format($p['utilisation'] * 100, 1) }}%</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="finance-badge {{ $ragClass[$p['rag_npl']] }}">{{ number_format($p['npl_ratio'] * 100, 1) }}%</span>
                                    </td>
                                    <td class="text-end">{{ number_format($p['volume']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    @endif

    {{-- Upload --}}
    <div class="card">
        <div class="card-header">
            <h6 class="card-title mb-0">Upload LOANS PORTFOLIO NEW Report</h6>
            <div class="text-muted small">
                Each upload adds a new snapshot. History is kept, so you can switch between snapshots using the picker above.
            </div>
        </div>

        <div class="card-body">
            <form action="{{ route('loans.loan-utilization.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-4">
                    <div class="col-lg-6">
                        <label class="form-label" for="loans_portfolio_file">LOANS PORTFOLIO NEW file <span class="text-danger">*</span></label>
                        @include('loans.partials.file-drop', [
                            'id' => 'loans_portfolio_file',
                            'name' => 'loans_portfolio_file',
                            'accept' => '',
                        ])
                    </div>

                    <div class="col-lg-6">
                        <div class="text-muted small mb-1">Required columns</div>
                        <div>
                            @foreach (['Contract No', 'Account Name', 'Exposure Amount(lcy)', 'Past Due Days', 'User Defined Status', 'Frr', 'Orr', 'Credit Line', 'Gl Name', 'Business Segment', 'Industry Segment', 'Value Dt'] as $col)
                                <span class="loan-col-tag">{{ $col }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">
                    <i class="ti ti-refresh"></i> Upload &amp; Refresh
                </button>
            </form>
        </div>
    </div>

@endsection

@if ($snapshot)
    @php
        $ragColorMap = ['green' => '#00a86b', 'amber' => '#f5b700', 'red' => '#dc3545', 'none' => '#98a2b3'];
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

            Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
            Chart.defaults.color = '#667085';
            Chart.defaults.borderColor = '#e2e8f0';
            Chart.defaults.maintainAspectRatio = false;

            const barStyle = { borderRadius: 4, maxBarThickness: 28 };

            new Chart(document.getElementById('chartPerformance'), {
                type: 'doughnut',
                data: {
                    labels: ['Performing', 'Non-Performing'],
                    datasets: [{
                        data: [totalPerforming, totalNonPerforming],
                        backgroundColor: ['#0875e1', '#dc3545'],
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
                    datasets: [{ label: 'Total O/S (KES)', data: totals, backgroundColor: '#0875e1', ...barStyle }],
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
                        { label: 'YTD', data: ytd, backgroundColor: '#0875e1', ...barStyle },
                        { label: 'MTD', data: mtd, backgroundColor: '#11b8d4', ...barStyle },
                        { label: 'WTD', data: wtd, backgroundColor: '#00a86b', ...barStyle },
                        { label: 'Last Day', data: lastDay, backgroundColor: '#f5b700', ...barStyle },
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
