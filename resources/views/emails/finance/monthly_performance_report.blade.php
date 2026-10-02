{{-- resources/views/emails/finance/monthly_performance_report.blade.php --}}
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
</head>
<body style="margin:0;padding:0;background:#EAEEF2;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1a1f2e;-webkit-font-smoothing:antialiased;">

@php
    $dep   = $report['deposits'];
    $loans = $report['loans'];
    $depP  = $dep['periods'];
    $loanP = $loans['periods'];

    // Product colour coding — Deposits blue, Loans green — used by headings, KPI tiles,
    // table headers and segment rows so each product reads as one block.
    $themes = [
        'deposits' => [
            'accent' => '#1D4ED8', 'grad' => '#3B82F6 0%,#1D4ED8 100%', 'label' => '#1E40AF',
            'head' => '#1E3A8A', 'headLine' => '#1E40AF', 'headText' => '#BFDBFE', 'headSub' => '#93C5FD',
            'name' => '#1E40AF', 'rowBg' => '#EFF6FF', 'subBg' => '#FAFCFF', 'dot' => '#93C5FD', 'totalBg' => '#DBEAFE',
            'tileBg' => '#EFF6FF', 'tileLine' => '#BFDBFE',
        ],
        'loans' => [
            'accent' => '#15803D', 'grad' => '#22C55E 0%,#15803D 100%', 'label' => '#166534',
            'head' => '#14532D', 'headLine' => '#166534', 'headText' => '#BBF7D0', 'headSub' => '#86EFAC',
            'name' => '#166534', 'rowBg' => '#F0FDF4', 'subBg' => '#FAFFFB', 'dot' => '#86EFAC', 'totalBg' => '#DCFCE7',
            'tileBg' => '#F0FDF4', 'tileLine' => '#BBF7D0',
        ],
    ];

    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';

    $abbr = function($v, bool $signed = true) {
        $n    = abs((float) $v);
        $sign = $signed ? ((float) $v < 0 ? '−' : '+') : '';
        if ($n >= 1_000_000_000) return $sign . number_format($n / 1_000_000_000, 2) . 'B';
        if ($n >= 1_000_000)     return $sign . number_format($n / 1_000_000, 2)     . 'M';
        if ($n >= 1_000)         return $sign . number_format($n / 1_000, 1)          . 'K';
        return $sign . number_format((int) $n);
    };

    $depTotal  = collect($dep['bank'])->firstWhere('code', 'ALL') ?? [];
    $loanTotal = collect($loans['segments'])->firstWhere('code', 'ALL') ?? [];

    $pct = function($mv, $balance) {
        $opening = (float) $balance - (float) $mv;
        return $opening > 0 ? (((float) $mv >= 0 ? '+' : '−') . number_format(abs((float) $mv / $opening * 100), 1) . '% MoM') : '';
    };

    $kpis = [
        ['product' => 'deposits', 'label' => 'Total Deposits',   'kind' => 'balance',  'value' => $depTotal['balance']  ?? 0, 'sub' => 'as at ' . $fmtDate($depP['month_end'])],
        ['product' => 'deposits', 'label' => 'Deposits Month Δ', 'kind' => 'movement', 'value' => $depTotal['month_mv'] ?? 0, 'sub' => $pct($depTotal['month_mv'] ?? 0, $depTotal['balance'] ?? 0) ?: 'from ' . $fmtDate($depP['month_start'])],
        ['product' => 'deposits', 'label' => 'Deposits YTD Δ',   'kind' => 'movement', 'value' => $depTotal['ytd_mv']   ?? 0, 'sub' => 'from ' . $fmtDate($depP['ytd_start'])],
    ];
    if ($loanP) {
        $kpis[] = ['product' => 'loans', 'label' => 'Total Loans',   'kind' => 'balance',  'value' => $loanTotal['balance']  ?? 0, 'sub' => 'as at ' . $fmtDate($loanP['month_end'])];
        $kpis[] = ['product' => 'loans', 'label' => 'Loans Month Δ', 'kind' => 'movement', 'value' => $loanTotal['month_mv'] ?? 0, 'sub' => $pct($loanTotal['month_mv'] ?? 0, $loanTotal['balance'] ?? 0) ?: 'from ' . $fmtDate($loanP['month_start'])];
    }

    $sectionLabel = fn(array $t, string $title, string $sub = '') =>
        '<table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;"><tr>'
        . '<td style="padding-right:10px;vertical-align:middle;"><div style="width:4px;height:18px;background:' . $t['accent'] . ';background:linear-gradient(180deg,' . $t['grad'] . ');border-radius:2px;"></div></td>'
        . '<td style="vertical-align:middle;"><span style="font-size:13px;font-weight:800;color:' . $t['label'] . ';letter-spacing:-0.2px;">' . e($title) . '</span>'
        . ($sub !== '' ? '<span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· ' . e($sub) . '</span>' : '')
        . '</td></tr></table>';

    $mvBadge = fn($v) => (float) $v >= 0
        ? 'display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:#DCFCE7;color:#14532D;border:1px solid #86EFAC;'
        : 'display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:#FEE2E2;color:#7F1D1D;border:1px solid #FCA5A5;';
    $mvText = fn($v) => ((float) $v >= 0 ? '▲ ' : '▼ ') . $abbr($v, false);

    $loansLag = $loanP && $loanP['month_end'] < $depP['month_end'];
@endphp

<div style="max-width:1100px;margin:0 auto;padding:24px 14px;">
<div style="background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.10),0 2px 6px rgba(0,0,0,0.06);border:1px solid #D9E2EC;">

{{-- ═══════════════════════ HEADER ═══════════════════════ --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;" bgcolor="#002E4A">
  <tr>
    <td style="padding:28px 32px 26px;background:linear-gradient(140deg,#002E4A 0%,#00476A 55%,#005E8A 100%);" bgcolor="#002E4A">
      <div style="font-size:10px;font-weight:800;color:rgba(255,255,255,0.45);letter-spacing:2.5px;text-transform:uppercase;margin-bottom:8px;">ECOBANK KENYA</div>
      <table width="100%" cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">
        <tr>
          <td style="vertical-align:top;">
            <div style="font-size:26px;font-weight:900;color:#ffffff;letter-spacing:-0.6px;line-height:1.1;">Loans &amp; Deposits Performance</div>
            <div style="font-size:12px;font-weight:500;color:rgba(255,255,255,0.55);margin-top:5px;letter-spacing:0.2px;">
              <span style="color:#93C5FD;">Deposits</span> &nbsp;·&nbsp; <span style="color:#86EFAC;">Loans</span> &nbsp;·&nbsp; Month-on-month by segment
            </div>
          </td>
          <td style="vertical-align:top;text-align:right;white-space:nowrap;">
            <div style="display:inline-block;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.22);border-radius:10px;padding:8px 16px;">
              <div style="font-size:9.5px;font-weight:700;color:rgba(255,255,255,0.55);text-transform:uppercase;letter-spacing:1px;">{{ $report['is_month_closed'] ? 'Month' : 'Month to date' }}</div>
              <div style="font-size:16px;font-weight:900;color:#ffffff;margin-top:1px;">{{ $report['label'] }}</div>
            </div>
          </td>
        </tr>
      </table>
      <div style="margin-top:16px;">
        <span style="display:inline-block;padding:5px 13px;border-radius:999px;font-size:10.5px;font-weight:700;color:#ffffff;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);margin-right:7px;white-space:nowrap;">
          Month &nbsp;{{ $fmtDate($depP['month_start']) }} → {{ $fmtDate($depP['month_end']) }}
        </span>
        <span style="display:inline-block;padding:5px 13px;border-radius:999px;font-size:10.5px;font-weight:700;color:#ffffff;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);white-space:nowrap;">
          Deposits YTD from &nbsp;{{ $fmtDate($depP['ytd_start']) }}
        </span>
      </div>
    </td>
  </tr>
</table>

{{-- ═══════════════════════ KPI STRIP ═══════════════════════ --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;border-bottom:1px solid #E2E8F0;">
  <tr>
    @foreach ($kpis as $i => $kpi)
      @php
        $t       = $themes[$kpi['product']];
        $mv      = (float) $kpi['value'];
        $isMv    = $kpi['kind'] === 'movement';
        $color   = $isMv ? ($mv >= 0 ? '#15803D' : '#BE123C') : $t['name'];
        $bg      = $isMv ? ($mv >= 0 ? '#F0FDF4' : '#FFF1F2') : '#ffffff';
        $bd      = $isMv ? ($mv >= 0 ? '#BBF7D0' : '#FECDD3') : $t['tileLine'];
        $text    = $isMv ? $abbr($mv) : 'KES ' . $abbr($mv, false);
        $isLast  = $i === count($kpis) - 1;
      @endphp
      <td style="padding:16px 18px;{{ !$isLast ? 'border-right:1px solid #E2E8F0;' : '' }}border-top:3px solid {{ $t['accent'] }};vertical-align:top;background:{{ $t['tileBg'] }};width:{{ round(100 / count($kpis), 4) }}%;" bgcolor="{{ $t['tileBg'] }}">
        <div style="font-size:9px;font-weight:800;color:{{ $t['label'] }};text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;white-space:nowrap;">{{ $kpi['label'] }}</div>
        <div style="display:inline-block;padding:4px 9px;border-radius:8px;background:{{ $bg }};border:1px solid {{ $bd }};">
          <span style="font-size:16px;font-weight:900;color:{{ $color }};font-family:'Courier New',ui-monospace,monospace;letter-spacing:-0.5px;">{{ $text }}</span>
        </div>
        <div style="font-size:9.5px;color:#94A3B8;margin-top:5px;white-space:nowrap;">{{ $kpi['sub'] }}</div>
      </td>
    @endforeach
  </tr>
</table>

<div style="padding:22px 28px 30px;">

  {{-- ── Deposits ──────────────────────── --}}
  {!! $sectionLabel($themes['deposits'], 'Deposits by Segment', 'Bank (all currencies, KES equivalent)') !!}
  @include('emails.finance.partials.monthly_segment_table', [
      'segments' => $dep['bank'], 'periods' => $depP, 'balanceLabel' => 'Deposits', 'theme' => $themes['deposits'],
  ])

  {{-- ── Loans ──────────────────────── --}}
  <div style="margin-top:32px;">
    {!! $sectionLabel($themes['loans'], 'Loans by Segment', 'Performing book, KES equivalent') !!}
    @if ($loanP)
      @if ($loansLag)
        <div style="margin-bottom:10px;font-size:11px;color:#166534;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:8px 12px;">
          Latest loan book for {{ $report['label'] }} is as at <strong>{{ $fmtDate($loanP['month_end']) }}</strong> — loans are imported separately from deposit balances.
        </div>
      @endif
      @include('emails.finance.partials.monthly_segment_table', [
          'segments' => $loans['segments'], 'periods' => $loanP, 'balanceLabel' => 'Loans', 'theme' => $themes['loans'], 'showYtd' => false,
      ])
    @else
      <div style="font-size:12px;color:#92400E;background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:14px 16px;">
        No loan book has been imported for <strong>{{ $loans['missing'] }}</strong>, so month-on-month loan movement can't be calculated yet.
        Import it with <span style="font-family:ui-monospace,'Courier New',monospace;">php artisan loans:import</span> and re-run
        <span style="font-family:ui-monospace,'Courier New',monospace;">php artisan reports:email-monthly-performance {{ $report['month'] }}</span>.
      </div>
    @endif
  </div>

  {{-- ── Top customer movers ──────────────────────── --}}
  @foreach (['deposits' => ['Deposits', $dep['top']], 'loans' => ['Loans', $loans['top']]] as $product => [$productLabel, $top])
    @php
      $t       = $themes[$product];
      $gainers = collect($top['gainers'] ?? [])->take(10);
      $losers  = collect($top['losers'] ?? [])->take(10);
    @endphp
    @continue($gainers->isEmpty() && $losers->isEmpty())
    <div style="margin-top:32px;">
      {!! $sectionLabel($t, "Top {$productLabel} Movers This Month", 'Top 10 customers each way · full list in the Excel attachment') !!}
      <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;">
        <tr>
          @foreach ([['▲ Top Gainers', $gainers, '#15803D'], ['▼ Top Losers', $losers, '#BE123C']] as $side => [$heading, $rows, $dirColor])
            <td style="width:50%;vertical-align:top;{{ $side === 0 ? 'padding-right:8px;' : 'padding-left:8px;' }}">
              <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:separate;border-spacing:0;font-size:11px;border:1px solid {{ $t['tileLine'] }};border-radius:10px;overflow:hidden;background:#ffffff;">
                <tr><th colspan="3" bgcolor="{{ $t['head'] }}" style="padding:8px 12px;background:{{ $t['head'] }};text-align:left;font-size:10px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:0.8px;">{{ $heading }}</th></tr>
                @forelse ($rows as $i => $r)
                  @php $line = !$loop->last ? "border-bottom:1px solid {$t['tileLine']};" : ''; @endphp
                  <tr style="background:{{ $i % 2 ? $t['rowBg'] : '#ffffff' }};">
                    <td style="padding:6px 10px;width:6%;text-align:center;font-weight:900;color:{{ $dirColor }};{{ $line }}">{{ $i + 1 }}</td>
                    <td style="padding:6px 10px;color:#1F3A5F;{{ $line }}">
                      <div style="font-weight:700;">{{ \Illuminate\Support\Str::limit((string) ($r->customer_name ?? $r->cif), 34) }}</div>
                      <div style="font-size:9.5px;color:#94A3B8;">{{ $r->cif }} · {{ $r->sub_segment_name ?? '' }}</div>
                    </td>
                    <td style="padding:6px 10px;text-align:right;white-space:nowrap;{{ $line }}">
                      <span style="{{ $mvBadge($r->movement) }}">{{ $mvText($r->movement) }}</span>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="3" style="padding:12px;text-align:center;color:#94A3B8;">No data</td></tr>
                @endforelse
              </table>
            </td>
          @endforeach
        </tr>
      </table>
    </div>
  @endforeach

  {{-- ── Notes ──────────────────────── --}}
  <div style="margin-top:24px;font-size:10.5px;color:#64748B;padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-left:4px solid #005B82;border-radius:8px;line-height:1.6;">
    <strong style="color:#1F3A5F;font-weight:900;">Notes:</strong>
    Month Δ compares the last posted balance of {{ $report['label'] }} with the last posted balance of the previous month; MoM % is Month Δ over the opening balance.
    Deposits YTD Δ is measured from the last balance of the previous year, or the earliest balance held this year where there is no prior-year data (start date shown above).
    Loans are shown month-on-month only for now (the performing book: NORM/OAEM/SUBS/Watch).
    Deposits use the same exclusions as the weekly report (P50 and GL 216220001, with the usual exception CIFs).
    Staff accounts and staff loans are excluded throughout.
    The Excel attachment carries the LCY/FCY deposit splits and the full top customer movers lists. Branch performance is sent as a separate email.
  </div>

</div>

{{-- ═══════════════════════ FOOTER ═══════════════════════ --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;border-top:2px solid #E2E8F0;" bgcolor="#F8FAFC">
  <tr>
    <td style="padding:14px 32px;background:#F8FAFC;" bgcolor="#F8FAFC">
      <table width="100%" cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">
        <tr>
          <td style="vertical-align:middle;">
            <span style="font-size:11px;color:#94A3B8;">
              <strong style="color:#334155;font-weight:800;font-size:12px;">Ecobank Kenya</strong>
              <span style="color:#CBD5E1;margin:0 6px;">·</span>
              <span>Monthly Loans &amp; Deposits Performance</span>
            </span>
          </td>
          <td style="vertical-align:middle;text-align:right;">
            <span style="font-size:10.5px;color:#94A3B8;">Generated {{ now()->timezone('Africa/Nairobi')->format('d M Y, H:i') }} EAT</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

</div>
</div>

</body>
</html>
