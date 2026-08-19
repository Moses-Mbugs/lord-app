@extends('layouts.loans.template')

@section('title', 'Loan Utilization')
@section('page_title', 'Loan Utilization')
@section('page_subtitle', 'Executive view of portfolio utilization, performance and NPL by product')

@section('content')

    <div class="loan-page-hero">
        <h1>Loan Utilization Dashboard</h1>
        <p>
            Portfolio exposure, performance and NPL broken down by product, refreshed from the latest
            LOANS PORTFOLIO NEW upload. Download the full workbook or upload a new extract to refresh the data.
        </p>

        @if ($snapshot)
            <div class="loan-hero-actions">
                <a href="{{ route('loans.loan-utilization.download', $snapshot->id) }}" class="btn-loan-hero">
                    ⬇ Download as Excel
                </a>

                @if ($snapshots->count() > 1)
                    <form method="GET" action="{{ route('loans.loan-utilization.index') }}" style="display:inline-flex;align-items:center;gap:8px;">
                        <select name="snapshot" class="form-control" style="min-height:38px;padding:6px 10px;"
                            onchange="this.form.submit()">
                            @foreach ($snapshots as $s)
                                <option value="{{ $s->id }}" {{ $snapshot->id === $s->id ? 'selected' : '' }}>
                                    As at {{ optional($s->as_of_date)->format('d M Y') }} ({{ number_format($s->total_rows) }} loans)
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success loan-alert">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger loan-alert">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger loan-alert">
            <strong>Please correct the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!$snapshot)
        <div class="loan-card">
            <h3>No data yet</h3>
            <p class="loan-muted">Upload a LOANS PORTFOLIO NEW report below to populate the dashboard.</p>
        </div>
    @else
        @php
            $t = $dashboard['totals'];
            $ragClass = ['green' => 'loan-badge-success', 'amber' => 'loan-badge-warning', 'red' => 'loan-badge-danger', 'none' => 'loan-badge-info'];
            $unmapped = collect($dashboard['products'])->firstWhere('product_name', 'Unmapped - Review');
        @endphp

        @if ($unmapped && $unmapped['volume'] > 0)
            <div class="alert alert-warning loan-alert">
                <strong>{{ $unmapped['volume'] }}</strong> loan(s) totalling
                <strong>KES {{ number_format($unmapped['total']) }}</strong> could not be auto-categorized into a
                product ("Unmapped - Review"). Check the Product_Mapping sheet in the downloaded workbook.
            </div>
        @endif

        <div class="loan-metric-grid">
            <div class="loan-metric-card">
                <div class="loan-metric-label">As-Of Date</div>
                <div class="loan-metric-value" style="font-size:17px;">{{ \Carbon\Carbon::parse($dashboard['as_of_date'])->format('d M Y') }}</div>
            </div>
            <div class="loan-metric-card">
                <div class="loan-metric-label">Total Portfolio O/S</div>
                <div class="loan-metric-value">KES {{ number_format($t['total']) }}</div>
            </div>
            <div class="loan-metric-card">
                <div class="loan-metric-label">Performing</div>
                <div class="loan-metric-value">KES {{ number_format($t['performing']) }}</div>
            </div>
            <div class="loan-metric-card">
                <div class="loan-metric-label">Non-Performing</div>
                <div class="loan-metric-value">KES {{ number_format($t['non_performing']) }}</div>
            </div>
            <div class="loan-metric-card">
                <div class="loan-metric-label">NPL Ratio</div>
                <div class="loan-metric-value">
                    <span class="loan-badge {{ $ragClass[$t['rag_npl']] }}">{{ number_format($t['npl_ratio'] * 100, 1) }}%</span>
                </div>
            </div>
            <div class="loan-metric-card">
                <div class="loan-metric-label">Approved Limit / % Utilised</div>
                <div class="loan-metric-value" style="font-size:17px;">
                    KES {{ number_format($t['approved_limit']) }}
                    @if ($t['utilisation'] !== null)
                        <span class="loan-badge {{ $ragClass[$t['rag_utilisation']] }}">{{ number_format($t['utilisation'] * 100, 1) }}%</span>
                    @endif
                </div>
            </div>
            <div class="loan-metric-card">
                <div class="loan-metric-label">Loan Count</div>
                <div class="loan-metric-value">{{ number_format($t['volume']) }}</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="loan-card">
                    <div class="loan-card-header">
                        <div>
                            <h3>Performing vs Non-Performing</h3>
                            <div class="loan-card-subtitle">Share of total portfolio exposure</div>
                        </div>
                    </div>
                    <canvas id="chartPerformance" height="220"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="loan-card">
                    <div class="loan-card-header">
                        <div>
                            <h3>Total Exposure by Product</h3>
                            <div class="loan-card-subtitle">Outstanding balance (LCY) per product category</div>
                        </div>
                    </div>
                    <canvas id="chartByProduct" height="220"></canvas>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="loan-card">
                    <div class="loan-card-header">
                        <div>
                            <h3>NPL Ratio by Product</h3>
                            <div class="loan-card-subtitle">Red / Amber / Green against policy thresholds</div>
                        </div>
                    </div>
                    <canvas id="chartNpl" height="220"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="loan-card">
                    <div class="loan-card-header">
                        <div>
                            <h3>Disbursement Trend by Product</h3>
                            <div class="loan-card-subtitle">YTD / MTD / WTD / Last Day exposure</div>
                        </div>
                    </div>
                    <canvas id="chartTrend" height="220"></canvas>
                </div>
            </div>
        </div>

        <div class="loan-card">
            <div class="loan-card-header">
                <div>
                    <h3>Product Breakdown</h3>
                    <div class="loan-card-subtitle">
                        Approved Limit is a manual input (board-approved product ceiling) — edit and save below.
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('loans.loan-utilization.approved-limits') }}">
                @csrf
                <div class="loan-table-wrap">
                    <table class="loan-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Approved Limit</th>
                                <th>Performing</th>
                                <th>Non-Performing</th>
                                <th>Total O/S</th>
                                <th>% Utilised</th>
                                <th>NPL Ratio</th>
                                <th>Volume</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dashboard['products'] as $p)
                                <tr>
                                    <td style="white-space:normal;min-width:220px;">{{ $p['product_name'] }}</td>
                                    <td>
                                        <input type="text" class="form-control" style="min-height:36px;width:150px;"
                                            name="approved_limit[{{ $p['product_name'] }}]"
                                            value="{{ number_format($p['approved_limit'], 0, '.', '') }}">
                                    </td>
                                    <td>{{ number_format($p['performing']) }}</td>
                                    <td>{{ number_format($p['non_performing']) }}</td>
                                    <td>{{ number_format($p['total']) }}</td>
                                    <td>
                                        @if ($p['utilisation'] !== null)
                                            <span class="loan-badge {{ $ragClass[$p['rag_utilisation']] }}">{{ number_format($p['utilisation'] * 100, 1) }}%</span>
                                        @else
                                            <span class="loan-muted">—</span>
                                        @endif
                                    </td>
                                    <td><span class="loan-badge {{ $ragClass[$p['rag_npl']] }}">{{ number_format($p['npl_ratio'] * 100, 1) }}%</span></td>
                                    <td>{{ number_format($p['volume']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn-loan-primary" style="margin-top:16px;">Save Approved Limits</button>
            </form>
        </div>
    @endif

    <div class="loan-card">
        <div class="loan-card-header">
            <div>
                <h3>Upload LOANS PORTFOLIO NEW Report</h3>
                <div class="loan-card-subtitle">
                    Uploading a new file adds a fresh snapshot — history is kept, so you can compare against
                    previous uploads using the picker above.
                </div>
            </div>
        </div>

        <form action="{{ route('loans.loan-utilization.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label">LOANS PORTFOLIO NEW file</label>
                <input type="file" name="loans_portfolio_file" class="form-control" required>
                <div class="loan-help-text">
                    Required columns: Contract No, Account Name, Exposure Amount(lcy), Past Due Days, User Defined
                    Status, Frr, Orr, Credit Line, Gl Name, Business Segment, Industry Segment, Value Dt.
                </div>
            </div>

            <button type="submit" class="btn-loan-primary">Upload &amp; Refresh</button>
        </form>
    </div>

@endsection

@if ($snapshot)
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            const products = @json(collect($dashboard['products'])->pluck('product_name'));
            const performing = @json(collect($dashboard['products'])->pluck('performing'));
            const nonPerforming = @json(collect($dashboard['products'])->pluck('non_performing'));
            const totals = @json(collect($dashboard['products'])->pluck('total'));
            const nplRatios = @json(collect($dashboard['products'])->pluck('npl_ratio')->map(fn($v) => round($v * 100, 1)));
            const ragColors = @json(collect($dashboard['products'])->pluck('rag_npl')->map(fn($v) => ['green' => '#168A45', 'amber' => '#B7791F', 'red' => '#C0392B', 'none' => '#6B7C8F'][$v]));
            const ytd = @json(collect($dashboard['products'])->pluck('ytd'));
            const mtd = @json(collect($dashboard['products'])->pluck('mtd'));
            const wtd = @json(collect($dashboard['products'])->pluck('wtd'));
            const lastDay = @json(collect($dashboard['products'])->pluck('last_day'));

            const totalPerforming = {{ $t['performing'] }};
            const totalNonPerforming = {{ $t['non_performing'] }};

            new Chart(document.getElementById('chartPerformance'), {
                type: 'doughnut',
                data: {
                    labels: ['Performing', 'Non-Performing'],
                    datasets: [{
                        data: [totalPerforming, totalNonPerforming],
                        backgroundColor: ['#0082BB', '#C0392B'],
                    }],
                },
                options: { plugins: { legend: { position: 'bottom' } } },
            });

            new Chart(document.getElementById('chartByProduct'), {
                type: 'bar',
                data: {
                    labels: products,
                    datasets: [{ label: 'Total O/S (KES)', data: totals, backgroundColor: '#0082BB' }],
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
                    datasets: [{ label: 'NPL Ratio (%)', data: nplRatios, backgroundColor: ragColors }],
                },
                options: { plugins: { legend: { display: false } } },
            });

            new Chart(document.getElementById('chartTrend'), {
                type: 'bar',
                data: {
                    labels: products,
                    datasets: [
                        { label: 'YTD', data: ytd, backgroundColor: '#0082BB' },
                        { label: 'MTD', data: mtd, backgroundColor: '#0D7C8C' },
                        { label: 'WTD', data: wtd, backgroundColor: '#BED600' },
                        { label: 'Last Day', data: lastDay, backgroundColor: '#B7791F' },
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
