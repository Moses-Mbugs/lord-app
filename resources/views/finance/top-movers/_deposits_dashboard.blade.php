@php
    $deposits = $deposits ?? null;

    $pctChange = function ($start, $end) {
        $start = (float) $start;
        $end   = (float) $end;
        if ($start == 0.0) {
            return null;
        }
        return round((($end - $start) / abs($start)) * 100, 2);
    };

    $fmt = fn ($v) => number_format((float) $v, 2);

    $segmentBadge = [
        'CB'  => ['bg' => '#DBEAFE', 'fg' => '#1E3A8A'],
        'CM'  => ['bg' => '#EDE9FE', 'fg' => '#4C1D95'],
        'CS'  => ['bg' => '#FEF3C7', 'fg' => '#78350F'],
        'OT'  => ['bg' => '#F1F5F9', 'fg' => '#334155'],
        'ALL' => ['bg' => '#E2E8F0', 'fg' => '#0F172A'],
    ];

    $panels = $deposits ? [
        [
            'key'          => 'dep_cif',
            'label'        => 'CIF Movers',
            'note'         => 'KES Equivalent · All Segments',
            'gain'         => $deposits['grouped']['CIF_ONLY']['GAIN'] ?? collect(),
            'loss'         => $deposits['grouped']['CIF_ONLY']['LOSS'] ?? collect(),
            'showCurrency' => false,
        ],
        [
            'key'          => 'dep_lcy',
            'label'        => 'Local Currency (LCY) Movers',
            'note'         => 'Amounts in KES',
            'gain'         => $deposits['grouped']['CIF_CURRENCY']['LCY']['GAIN'] ?? collect(),
            'loss'         => $deposits['grouped']['CIF_CURRENCY']['LCY']['LOSS'] ?? collect(),
            'showCurrency' => false,
        ],
        [
            'key'          => 'dep_fcy',
            'label'        => 'Foreign Currency (FCY) Movers',
            'note'         => 'Amounts in original foreign currency',
            'gain'         => $deposits['grouped']['CIF_CURRENCY']['FCY']['GAIN'] ?? collect(),
            'loss'         => $deposits['grouped']['CIF_CURRENCY']['FCY']['LOSS'] ?? collect(),
            'showCurrency' => true,
        ],
    ] : [];
@endphp

<style>
    .dep-dashboard { margin-bottom: 24px; }

    .dep-kpi-header {
        background: linear-gradient(135deg, var(--blue) 0%, var(--blue-dark) 100%);
        border-radius: var(--radius);
        padding: 20px 24px;
        margin-bottom: 16px;
        box-shadow: var(--shadow-md);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
    }

    .dep-kpi-header h5 {
        font-size: 1.05rem;
        font-weight: 800;
        margin: 0 0 3px;
    }

    .dep-kpi-header .dep-range {
        font-size: .8rem;
        color: rgba(255, 255, 255, .7);
    }

    .dep-net-badge {
        display: inline-block;
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(255, 255, 255, .18);
        border-radius: 10px;
        padding: 10px 18px;
        text-align: right;
    }

    .dep-net-badge .dep-net-label {
        font-size: .68rem;
        font-weight: 700;
        color: rgba(255, 255, 255, .55);
        text-transform: uppercase;
        letter-spacing: .07em;
        margin-bottom: 3px;
    }

    .dep-net-badge .dep-net-value {
        font-size: 1.25rem;
        font-weight: 900;
        font-family: var(--font-mono);
        letter-spacing: -0.5px;
    }

    .dep-net-badge .dep-net-value.gain { color: #6EE7B7; }
    .dep-net-badge .dep-net-value.loss { color: #FCA5A5; }

    .dep-net-badge .dep-net-sub {
        font-size: .74rem;
        color: rgba(255, 255, 255, .55);
        margin-top: 2px;
    }

    .dep-segment-card {
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-light);
        padding: 18px 20px 6px;
        margin-bottom: 20px;
    }

    .dep-segment-card h6 {
        font-size: .85rem;
        font-weight: 700;
        color: var(--gray-text);
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 12px;
    }

    .dep-seg-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 999px;
        font-weight: 700;
        font-size: .78rem;
        letter-spacing: .02em;
    }

    .dep-panels-grid { display: flex; flex-direction: column; gap: 20px; }

    .dep-tab-nav {
        display: flex;
        border-bottom: 2px solid var(--gray-light);
        background: var(--gray-bg);
        padding: 0 20px;
        gap: 4px;
    }

    .dep-tab-btn {
        padding: 12px 18px;
        border: none;
        background: transparent;
        font-family: var(--font);
        font-weight: 600;
        font-size: .87rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--gray-mid);
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        border-radius: 8px 8px 0 0;
    }

    .dep-tab-btn.active.gain-tab {
        color: var(--gain-text);
        border-bottom-color: #4caf50;
        background: rgba(76, 175, 80, .06);
    }

    .dep-tab-btn.active.loss-tab {
        color: var(--loss-text);
        border-bottom-color: #f44336;
        background: rgba(244, 67, 54, .06);
    }

    .dep-tab-panel { display: none; padding: 16px 20px 20px; }
    .dep-tab-panel.active { display: block; }

    .dep-empty-row td { text-align: center; color: var(--gray-mid); padding: 24px 0; font-size: .85rem; }
</style>

<div class="dep-dashboard">
    @if (!$deposits)
        <div class="movers-card" style="padding:40px 20px;">
            <div class="empty-state" style="display:block;">
                <div class="empty-icon"><i class="fas fa-database" aria-hidden="true"></i></div>
                <h5>No deposit movement snapshot yet</h5>
                <p>Run <code>php artisan reports:run-daily</code> (or <code>reports:build-top-movers</code>) to build the top movers snapshot this dashboard reads from.</p>
            </div>
        </div>
    @else
        @php
            $kpis = $deposits['kpis'];
            $isGain = $kpis['total_movement'] >= 0;
        @endphp

        <div class="dep-kpi-header">
            <div>
                <h5><i class="fas fa-sack-dollar" aria-hidden="true"></i> Daily Deposits Movement</h5>
                <div class="dep-range">
                    {{ \Carbon\Carbon::parse($deposits['start'])->format('d M Y') }}
                    &nbsp;→&nbsp;
                    {{ \Carbon\Carbon::parse($deposits['end'])->format('d M Y') }}
                    &nbsp;·&nbsp; KES equivalent
                </div>
            </div>

            <div class="dep-net-badge">
                <div class="dep-net-label">Total Net Movement</div>
                <div class="dep-net-value {{ $isGain ? 'gain' : 'loss' }}">
                    {{ $isGain ? '+' : '−' }}{{ number_format((int) round(abs($kpis['total_movement']))) }}
                </div>
                <div class="dep-net-sub">
                    {{ $kpis['gainers_count'] }} gainers &nbsp;·&nbsp; {{ $kpis['losers_count'] }} losers shown
                </div>
            </div>
        </div>

        @if ($deposits['segments']->isNotEmpty())
            <div class="dep-segment-card">
                <h6>Segment Overview</h6>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered w-100 mb-0">
                        <thead>
                            <tr>
                                <th>Segment</th>
                                <th class="text-end">{{ \Carbon\Carbon::parse($deposits['start'])->format('d M') }}</th>
                                <th class="text-end">{{ \Carbon\Carbon::parse($deposits['end'])->format('d M') }}</th>
                                <th class="text-end">LCY Mv</th>
                                <th class="text-end">FCY Mv</th>
                                <th class="text-end">Net Movement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($deposits['segments'] as $s)
                                @php
                                    $code = strtoupper((string) ($s->segment_code ?? 'OT'));
                                    $badge = $segmentBadge[$code] ?? $segmentBadge['OT'];
                                    $movement = (float) ($s->movement ?? 0);
                                    $lcyMv = (float) ($s->lcy_movement ?? 0);
                                    $fcyMv = (float) ($s->fcy_movement ?? 0);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="dep-seg-badge" style="background:{{ $badge['bg'] }};color:{{ $badge['fg'] }};">
                                            {{ $s->segment_name ?? $code }}
                                        </span>
                                    </td>
                                    <td class="text-end num-cell">{{ $fmt($s->start_balance ?? 0) }}</td>
                                    <td class="text-end num-cell">{{ $fmt($s->end_balance ?? 0) }}</td>
                                    <td class="text-end">
                                        <span class="{{ $lcyMv >= 0 ? 'pct-gain' : 'pct-loss' }}">
                                            {{ $lcyMv >= 0 ? '+' : '' }}{{ $fmt($lcyMv) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="{{ $fcyMv >= 0 ? 'pct-gain' : 'pct-loss' }}">
                                            {{ $fcyMv >= 0 ? '+' : '' }}{{ $fmt($fcyMv) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="{{ $movement >= 0 ? 'badge-gain' : 'badge-loss' }}">
                                            <i class="fas {{ $movement >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} fa-xs" aria-hidden="true"></i>
                                            {{ $fmt(abs($movement)) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="dep-panels-grid">
            @foreach ($panels as $panel)
                <div class="movers-card">
                    <div class="dep-tab-nav" role="tablist" aria-label="{{ $panel['label'] }} direction">
                        <button type="button" class="dep-tab-btn gain-tab active" data-panel="{{ $panel['key'] }}" data-dir="gain">
                            <i class="fas fa-arrow-up" aria-hidden="true"></i> Top Gainers
                            <span class="tab-badge gain-badge">{{ $panel['gain']->count() }}</span>
                        </button>
                        <button type="button" class="dep-tab-btn loss-tab" data-panel="{{ $panel['key'] }}" data-dir="loss">
                            <i class="fas fa-arrow-down" aria-hidden="true"></i> Top Losers
                            <span class="tab-badge loss-badge">{{ $panel['loss']->count() }}</span>
                        </button>
                    </div>

                    <div class="tab-topbar" style="padding:14px 20px 0;">
                        <div class="tab-topbar-info">
                            <strong>{{ $panel['label'] }}</strong>
                        </div>
                        <span class="amount-note">
                            <i class="fas fa-coins" aria-hidden="true"></i>
                            {{ $panel['note'] }}
                        </span>
                    </div>

                    @foreach (['gain' => $panel['gain'], 'loss' => $panel['loss']] as $dir => $rows)
                        <div class="dep-tab-panel {{ $dir === 'gain' ? 'active' : '' }}" data-panel="{{ $panel['key'] }}" data-dir="{{ $dir }}">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>CIF</th>
                                            <th>Customer</th>
                                            <th>Branch</th>
                                            @if ($panel['showCurrency'])
                                                <th>Currency</th>
                                            @endif
                                            <th class="text-end">Previous Balance</th>
                                            <th class="text-end">Current Balance</th>
                                            <th class="text-end">Movement</th>
                                            <th class="text-end">% Change</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($rows as $r)
                                            @php $pct = $pctChange($r->start_balance ?? 0, $r->end_balance ?? 0); @endphp
                                            <tr>
                                                <td>{{ $r->cif ?? '—' }}</td>
                                                <td>{{ $r->customer_name ?? '—' }}</td>
                                                <td>{{ $r->branch_code ?? '—' }}</td>
                                                @if ($panel['showCurrency'])
                                                    <td>{{ $r->currency ?? '—' }}</td>
                                                @endif
                                                <td class="text-end num-cell">{{ $fmt($r->start_balance ?? 0) }}</td>
                                                <td class="text-end num-cell">{{ $fmt($r->end_balance ?? 0) }}</td>
                                                <td class="text-end">
                                                    <span class="{{ $dir === 'gain' ? 'badge-gain' : 'badge-loss' }}">
                                                        <i class="fas {{ $dir === 'gain' ? 'fa-arrow-up' : 'fa-arrow-down' }} fa-xs" aria-hidden="true"></i>
                                                        {{ $fmt(abs($r->movement ?? 0)) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    @if ($pct === null)
                                                        <span class="text-muted"><abbr title="Not available because previous balance was zero">—</abbr></span>
                                                    @else
                                                        <span class="{{ $pct >= 0 ? 'pct-gain' : 'pct-loss' }}">{{ $pct >= 0 ? '+' : '' }}{{ number_format($pct, 2) }}%</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="dep-empty-row">
                                                <td colspan="{{ $panel['showCurrency'] ? 8 : 7 }}">No {{ $dir === 'gain' ? 'gainers' : 'losers' }} in this window.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    document.querySelectorAll('.dep-tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const panelKey = btn.dataset.panel;
            const dir = btn.dataset.dir;
            const card = btn.closest('.movers-card');

            card.querySelectorAll('.dep-tab-btn').forEach(b => b.classList.toggle('active', b === btn));
            card.querySelectorAll('.dep-tab-panel').forEach(function (p) {
                p.classList.toggle('active', p.dataset.panel === panelKey && p.dataset.dir === dir);
            });
        });
    });
</script>
