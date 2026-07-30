{{-- resources/views/emails/finance/weekly_branch_movers_report.blade.php --}}
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
</head>
<body style="margin:0;padding:0;background:#EAEEF2;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1a1f2e;-webkit-font-smoothing:antialiased;">

@php
    $weekPeriod = $periods['week'] ?? [];
    $mtdPeriod  = $periods['mtd']  ?? [];
    $ytdPeriod  = $periods['ytd']  ?? [];

    $weekStart = $weekPeriod['start'] ?? '';
    $mtdStart  = $mtdPeriod['start']  ?? '';
    $ytdStart  = $ytdPeriod['start']  ?? '';

    $weekData = $data['week'] ?? ['summary' => collect(), 'topGainers' => collect(), 'topLosers' => collect()];
    $mtdData  = $data['mtd']  ?? ['summary' => collect(), 'topGainers' => collect(), 'topLosers' => collect()];
    $ytdData  = $data['ytd']  ?? ['summary' => collect(), 'topGainers' => collect(), 'topLosers' => collect()];

    $fmtDate  = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';
    $fmtShort = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M')   : '—';

    // Abbreviate large numbers  e.g. 88,000,000,000 → 88.0B
    $abbr = function($v) {
        $n = abs((float) $v);
        $sign = (float) $v >= 0 ? '+' : '−';
        if ($n >= 1_000_000_000) return $sign . number_format($n / 1_000_000_000, 2) . 'B';
        if ($n >= 1_000_000)     return $sign . number_format($n / 1_000_000, 2)     . 'M';
        if ($n >= 1_000)         return $sign . number_format($n / 1_000, 1)          . 'K';
        return $sign . number_format((int) $n);
    };

    $abbrAbs = function($v) {
        $n = abs((float) $v);
        if ($n >= 1_000_000_000) return 'KES ' . number_format($n / 1_000_000_000, 2) . 'B';
        if ($n >= 1_000_000)     return 'KES ' . number_format($n / 1_000_000, 2)     . 'M';
        if ($n >= 1_000)         return 'KES ' . number_format($n / 1_000, 1)          . 'K';
        return 'KES ' . number_format((int) $n);
    };

    // Extract TOTAL row from weekly summary for KPIs
    $weekAll = $weekData['summary']->first(fn($r) => strtoupper(trim((string)($r->group_key ?? ''))) === 'ALL');
    $mtdAll  = $mtdData['summary']->first(fn($r)  => strtoupper(trim((string)($r->group_key ?? ''))) === 'ALL');
    $ytdAll  = $ytdData['summary']->first(fn($r)  => strtoupper(trim((string)($r->group_key ?? ''))) === 'ALL');

    $totalDeposits   = (float) ($weekAll->end_balance   ?? 0);
    $weeklyMovement  = (float) ($weekAll->movement      ?? 0);
    $mtdMovement     = (float) ($mtdAll->movement       ?? 0);
    $ytdMovement     = (float) ($ytdAll->movement       ?? 0);

    $kpis = [
        ['label' => 'Total Deposits',  'value' => $totalDeposits,  'mv' => false, 'sub' => 'as at ' . $fmtDate($weekEnd)],
        ['label' => 'Weekly Movement', 'value' => $weeklyMovement, 'mv' => true,  'sub' => $fmtShort($weekStart) . ' → ' . $fmtShort($weekEnd)],
        ['label' => 'Month-to-Date',   'value' => $mtdMovement,    'mv' => true,  'sub' => 'from ' . $fmtDate($mtdStart)],
        ['label' => 'Year-to-Date',    'value' => $ytdMovement,    'mv' => true,  'sub' => 'from ' . $fmtDate($ytdStart)],
    ];

    // Build combined branch map keyed by group_key for the multi-period table
    $branchMap = [];
    foreach ($weekData['summary'] as $r) {
        $code = strtoupper(trim((string)($r->group_key ?? '')));
        if ($code === '') continue;
        $branchMap[$code] = [
            'code'         => $code,
            'name'         => (string)($r->group_name ?? $code),
            'end_balance'  => (float)($r->end_balance ?? 0),
            'week_mv'      => (float)($r->movement ?? 0),
            'week_loan_mv' => (float)($r->loan_movement ?? 0),
            'mtd_mv'       => 0,
            'ytd_mv'       => 0,
        ];
    }
    foreach ($mtdData['summary'] as $r) {
        $code = strtoupper(trim((string)($r->group_key ?? '')));
        if ($code === '') continue;
        if (!isset($branchMap[$code])) {
            $branchMap[$code] = ['code' => $code, 'name' => (string)($r->group_name ?? $code), 'end_balance' => 0, 'week_mv' => 0, 'week_loan_mv' => 0, 'mtd_mv' => 0, 'ytd_mv' => 0];
        }
        $branchMap[$code]['mtd_mv'] = (float)($r->movement ?? 0);
    }
    foreach ($ytdData['summary'] as $r) {
        $code = strtoupper(trim((string)($r->group_key ?? '')));
        if ($code === '') continue;
        if (!isset($branchMap[$code])) {
            $branchMap[$code] = ['code' => $code, 'name' => (string)($r->group_name ?? $code), 'end_balance' => 0, 'week_mv' => 0, 'week_loan_mv' => 0, 'mtd_mv' => 0, 'ytd_mv' => 0];
        }
        $branchMap[$code]['ytd_mv'] = (float)($r->movement ?? 0);
    }

    // Sort: regular P-branches first (A-Z), then 834/950, then ALL
    uksort($branchMap, function($a, $b) {
        $special = ['834' => 1, '950' => 2, 'ALL' => 99];
        $as = $special[$a] ?? 0;
        $bs = $special[$b] ?? 0;
        if ($as !== $bs) return $as - $bs;
        return strcmp($a, $b);
    });

    $topGainers = $weekData['topGainers']->take(5);
    $topLosers  = $weekData['topLosers']->take(5);
@endphp

<div style="max-width:980px;margin:0 auto;padding:24px 14px;">
<div style="background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.10),0 2px 6px rgba(0,0,0,0.06);border:1px solid #D9E2EC;">

{{-- ═══════════════════════ HEADER ═══════════════════════ --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;" bgcolor="#002E4A">
  <tr>
    <td style="padding:28px 32px 26px;background:linear-gradient(140deg,#002E4A 0%,#00476A 55%,#005E8A 100%);" bgcolor="#002E4A">

      {{-- Brand label --}}
      <div style="font-size:10px;font-weight:800;color:rgba(255,255,255,0.45);letter-spacing:2.5px;text-transform:uppercase;margin-bottom:8px;">
        ECOBANK KENYA
      </div>

      <table width="100%" cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">
        <tr>
          <td style="vertical-align:top;">
            <div style="font-size:26px;font-weight:900;color:#ffffff;letter-spacing:-0.6px;line-height:1.1;">
              Weekly Branch Movements</div>
            <div style="font-size:12px;font-weight:500;color:rgba(255,255,255,0.55);margin-top:5px;letter-spacing:0.2px;">
              Deposits &amp; Performing Loans &nbsp;·&nbsp; All Branches (P50 excluded)
            </div>
          </td>
          <td style="vertical-align:top;text-align:right;white-space:nowrap;">
            <div style="display:inline-block;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.22);border-radius:10px;padding:8px 16px;">
              <div style="font-size:9.5px;font-weight:700;color:rgba(255,255,255,0.55);text-transform:uppercase;letter-spacing:1px;">Week ending</div>
              <div style="font-size:16px;font-weight:900;color:#ffffff;margin-top:1px;">{{ $fmtDate($weekEnd) }}</div>
            </div>
          </td>
        </tr>
      </table>

      {{-- Period pills --}}
      <div style="margin-top:16px;">
        <span style="display:inline-block;padding:5px 13px;border-radius:999px;font-size:10.5px;font-weight:700;color:#ffffff;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);margin-right:7px;white-space:nowrap;">
          Week &nbsp;{{ $fmtShort($weekStart) }} → {{ $fmtShort($weekEnd) }}
        </span>
        <span style="display:inline-block;padding:5px 13px;border-radius:999px;font-size:10.5px;font-weight:700;color:#ffffff;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);margin-right:7px;white-space:nowrap;">
          MTD from &nbsp;{{ $fmtDate($mtdStart) }}
        </span>
        <span style="display:inline-block;padding:5px 13px;border-radius:999px;font-size:10.5px;font-weight:700;color:#ffffff;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);white-space:nowrap;">
          YTD from &nbsp;{{ $fmtDate($ytdStart) }}
        </span>
      </div>

    </td>
  </tr>
</table>
{{-- ═══════════════════════ END HEADER ═══════════════════════ --}}

{{-- ═══════════════════════ KPI STRIP ═══════════════════════ --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;border-bottom:1px solid #E2E8F0;" bgcolor="#F8FAFC">
  <tr>
    @foreach ($kpis as $i => $kpi)
      @php
        $mv      = (float) $kpi['value'];
        $isGain  = $mv >= 0;
        $mvColor = $kpi['mv'] ? ($isGain ? '#15803D' : '#BE123C') : '#0F172A';
        $mvBg    = $kpi['mv'] ? ($isGain ? '#F0FDF4'  : '#FFF1F2') : '#F1F5F9';
        $mvBd    = $kpi['mv'] ? ($isGain ? '#BBF7D0'  : '#FECDD3') : '#E2E8F0';
        $numText = $kpi['mv'] ? $abbr($mv) : $abbrAbs($mv);
        $isLast  = $i === count($kpis) - 1;
      @endphp
      <td style="padding:18px 22px;{{ !$isLast ? 'border-right:1px solid #E2E8F0;' : '' }}vertical-align:top;background:#F8FAFC;width:25%;" bgcolor="#F8FAFC">
        <div style="font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">
          {{ $kpi['label'] }}
        </div>
        <div style="display:inline-block;padding:4px 10px;border-radius:8px;background:{{ $mvBg }};border:1px solid {{ $mvBd }};">
          <span style="font-size:19px;font-weight:900;color:{{ $mvColor }};font-family:'Courier New',ui-monospace,monospace;letter-spacing:-0.5px;">{{ $numText }}</span>
        </div>
        <div style="font-size:10px;color:#94A3B8;margin-top:5px;">{{ $kpi['sub'] }}</div>
      </td>
    @endforeach
  </tr>
</table>
{{-- ═══════════════════════ END KPI STRIP ═══════════════════════ --}}

{{-- ═══════════════════════ TABLE SECTION ═══════════════════════ --}}
<div style="padding:22px 28px 30px;">

  {{-- Section label --}}
  <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;">
    <tr>
      <td style="padding-right:10px;vertical-align:middle;">
        <div style="width:4px;height:18px;background:linear-gradient(180deg,#00B4D8 0%,#0077B6 100%);border-radius:2px;"></div>
      </td>
      <td style="vertical-align:middle;">
        <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">Branch Movement Summary</span>
        <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· KES Equivalent</span>
      </td>
    </tr>
  </table>

  <table width="100%" cellpadding="0" cellspacing="0"
    style="width:100%;border-collapse:separate;border-spacing:0;font-size:11px;border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <thead>
      <tr>
        {{-- Group headers --}}
        <th rowspan="2"
          style="padding:7px 10px;background:#F1F5F9;border-bottom:2px solid #CBD5E1;text-align:left;font-size:9px;font-weight:900;color:#475569;text-transform:uppercase;letter-spacing:0.7px;white-space:nowrap;border-right:1px solid #CBD5E1;width:22%;">
          Branch
        </th>
        <th rowspan="2"
          style="padding:7px 10px;background:#F1F5F9;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#475569;text-transform:uppercase;letter-spacing:0.7px;white-space:nowrap;border-right:2px solid #CBD5E1;width:14%;">
          End Balance
        </th>
        <th colspan="3"
          style="padding:6px 10px;background:#EFF6FF;border-bottom:1px solid #BFDBFE;text-align:center;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.7px;border-right:2px solid #BFDBFE;">
          Deposits Movement
        </th>
        <th colspan="1"
          style="padding:6px 10px;background:#F0FDF4;border-bottom:1px solid #BBF7D0;text-align:center;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.7px;">
          Perf. Loans
        </th>
      </tr>
      <tr>
        <th style="padding:6px 10px;background:#EFF6FF;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;border-left:1px solid #BFDBFE;">
          Weekly Δ
        </th>
        <th style="padding:6px 10px;background:#EFF6FF;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">
          MTD Δ
        </th>
        <th style="padding:6px 10px;background:#EFF6FF;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;border-right:2px solid #BFDBFE;">
          YTD Δ
        </th>
        <th style="padding:6px 10px;background:#F0FDF4;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">
          Weekly Δ
        </th>
      </tr>
    </thead>
    <tbody>
      @foreach ($branchMap as $bCode => $b)
        @php
          $isTotal  = $bCode === 'ALL';
          $isEven   = $loop->iteration % 2 === 0;
          $rowBg    = $isTotal ? '#F1F5F9' : ($isEven ? '#F8FAFC' : '#ffffff');
          $isLast   = $loop->last;
          $border   = $isLast ? 'none' : '1px solid #E2E8F0';

          $eb       = (float) ($b['end_balance'] ?? 0);
          $wkMv     = (float) ($b['week_mv']     ?? 0);
          $mtdMv    = (float) ($b['mtd_mv']      ?? 0);
          $ytdMv    = (float) ($b['ytd_mv']      ?? 0);
          $loanWk   = (float) ($b['week_loan_mv'] ?? 0);

          $fmtMv = function($v) {
              $n    = abs((float) $v);
              $sign = (float) $v >= 0 ? '▲' : '▼';
              if ($n >= 1_000_000_000) return $sign . ' ' . number_format($n / 1_000_000_000, 2) . 'B';
              if ($n >= 1_000_000)     return $sign . ' ' . number_format($n / 1_000_000, 2)     . 'M';
              if ($n >= 1_000)         return $sign . ' ' . number_format($n / 1_000, 1)          . 'K';
              return $sign . ' ' . number_format((int) $n);
          };
          $fmtBal = function($v) {
              $n = abs((float) $v);
              if ($n >= 1_000_000_000) return number_format($n / 1_000_000_000, 2) . 'B';
              if ($n >= 1_000_000)     return number_format($n / 1_000_000, 2)     . 'M';
              return number_format((int) $n);
          };

          $mvStyle = fn($v) => (float)$v >= 0
              ? 'display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:#f4fad4;color:#4a6a1a;border:1px solid #d0e06b;'
              : 'display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:#fff0f0;color:#a11818;border:1px solid #ffb3b3;';
          $loanMvStyle = fn($v) => (float)$v >= 0
              ? 'display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:#bbf7d0;color:#14532d;border:1px solid #86efac;'
              : 'display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:#fecaca;color:#7f1d1d;border:1px solid #fca5a5;';
        @endphp
        <tr style="background:{{ $rowBg }};">
          <td style="padding:7px 10px;border-bottom:{{ $border }};border-right:1px solid #E2E8F0;">
            <span style="display:inline-block;padding:2px 8px;border-radius:999px;
              background:{{ $isTotal ? '#E2E8F0' : '#EFF6FF' }};
              border:1px solid {{ $isTotal ? '#CBD5E1' : '#BFDBFE' }};
              color:{{ $isTotal ? '#334155' : '#1D4ED8' }};
              font-weight:900;font-size:10px;letter-spacing:0.3px;text-transform:uppercase;">
              {{ $isTotal ? 'TOTAL' : $b['name'] }}
            </span>
          </td>
          <td style="padding:7px 10px;border-bottom:{{ $border }};border-right:2px solid #CBD5E1;text-align:right;font-family:ui-monospace,'Courier New',monospace;font-weight:700;font-size:11px;color:#374151;">
            {{ $fmtBal($eb) }}
          </td>
          <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;border-left:1px solid #BFDBFE;">
            <span style="{{ $mvStyle($wkMv) }}">{{ $fmtMv($wkMv) }}</span>
          </td>
          <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;">
            <span style="{{ $mvStyle($mtdMv) }}">{{ $fmtMv($mtdMv) }}</span>
          </td>
          <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;border-right:2px solid #BFDBFE;">
            <span style="{{ $mvStyle($ytdMv) }}">{{ $fmtMv($ytdMv) }}</span>
          </td>
          <td style="padding:7px 10px;border-bottom:{{ $border }};background:{{ $isEven ? '#F0FDF4' : '#ECFDF5' }};text-align:right;">
            <span style="{{ $loanMvStyle($loanWk) }}">{{ $fmtMv($loanWk) }}</span>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{-- ── Top Weekly Movers ──────────────────────── --}}
  @if ($topGainers->isNotEmpty() || $topLosers->isNotEmpty())
  <div style="margin-top:32px;">

    {{-- Section label --}}
    <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;">
      <tr>
        <td style="padding-right:10px;vertical-align:middle;">
          <div style="width:4px;height:18px;background:linear-gradient(180deg,#F59E0B 0%,#B45309 100%);border-radius:2px;"></div>
        </td>
        <td style="vertical-align:middle;">
          <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">Top Weekly Movers</span>
          <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· {{ $fmtDate($weekStart) }} → {{ $fmtDate($weekEnd) }}</span>
        </td>
      </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;">
      <tr>
        {{-- Gainers --}}
        <td style="width:49%;vertical-align:top;padding-right:8px;">
          <table width="100%" cellpadding="0" cellspacing="0"
            style="width:100%;border-collapse:separate;border-spacing:0;font-size:11px;border:1px solid #BBF7D0;border-radius:10px;overflow:hidden;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">
            <thead>
              <tr>
                <th colspan="3" style="padding:8px 12px;background:#166534;text-align:left;font-size:10px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:0.8px;">
                  ▲ Top Gainers
                </th>
              </tr>
              <tr>
                <th style="padding:6px 10px;background:#F0FDF4;border-bottom:1px solid #BBF7D0;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;width:8%;">#</th>
                <th style="padding:6px 10px;background:#F0FDF4;border-bottom:1px solid #BBF7D0;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;text-align:left;">Branch</th>
                <th style="padding:6px 10px;background:#F0FDF4;border-bottom:1px solid #BBF7D0;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;text-align:right;">Movement</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($topGainers as $i => $r)
                @php
                  $mv  = (float)($r->movement ?? 0);
                  $n   = abs($mv);
                  $str = $n >= 1_000_000_000 ? number_format($n/1_000_000_000,2).'B'
                       : ($n >= 1_000_000    ? number_format($n/1_000_000,2).'M'
                       : ($n >= 1_000        ? number_format($n/1_000,1).'K'
                       : number_format((int)$n)));
                  $rowBg = $i % 2 === 0 ? '#ffffff' : '#F0FDF4';
                  $isLast = $loop->last;
                @endphp
                <tr style="background:{{ $rowBg }};">
                  <td style="padding:7px 10px;text-align:center;font-weight:900;color:#15803D;{{ !$isLast ? 'border-bottom:1px solid #DCFCE7;' : '' }}">{{ $i + 1 }}</td>
                  <td style="padding:7px 10px;font-weight:700;color:#1F3A5F;{{ !$isLast ? 'border-bottom:1px solid #DCFCE7;' : '' }}">{{ $r->group_name ?? $r->group_key ?? '—' }}</td>
                  <td style="padding:7px 10px;text-align:right;{{ !$isLast ? 'border-bottom:1px solid #DCFCE7;' : '' }}">
                    <span style="display:inline-block;padding:3px 8px;border-radius:6px;font-weight:900;font-size:10.5px;background:#bbf7d0;color:#14532d;border:1px solid #86efac;">▲ {{ $str }}</span>
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" style="padding:12px;text-align:center;color:#94A3B8;font-size:11px;">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </td>

        {{-- Losers --}}
        <td style="width:49%;vertical-align:top;padding-left:8px;">
          <table width="100%" cellpadding="0" cellspacing="0"
            style="width:100%;border-collapse:separate;border-spacing:0;font-size:11px;border:1px solid #FECACA;border-radius:10px;overflow:hidden;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">
            <thead>
              <tr>
                <th colspan="3" style="padding:8px 12px;background:#991B1B;text-align:left;font-size:10px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:0.8px;">
                  ▼ Top Losers
                </th>
              </tr>
              <tr>
                <th style="padding:6px 10px;background:#FFF5F5;border-bottom:1px solid #FECACA;font-size:9px;font-weight:900;color:#B91C1C;text-transform:uppercase;letter-spacing:0.6px;width:8%;">#</th>
                <th style="padding:6px 10px;background:#FFF5F5;border-bottom:1px solid #FECACA;font-size:9px;font-weight:900;color:#B91C1C;text-transform:uppercase;letter-spacing:0.6px;text-align:left;">Branch</th>
                <th style="padding:6px 10px;background:#FFF5F5;border-bottom:1px solid #FECACA;font-size:9px;font-weight:900;color:#B91C1C;text-transform:uppercase;letter-spacing:0.6px;text-align:right;">Movement</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($topLosers as $i => $r)
                @php
                  $mv  = (float)($r->movement ?? 0);
                  $n   = abs($mv);
                  $str = $n >= 1_000_000_000 ? number_format($n/1_000_000_000,2).'B'
                       : ($n >= 1_000_000    ? number_format($n/1_000_000,2).'M'
                       : ($n >= 1_000        ? number_format($n/1_000,1).'K'
                       : number_format((int)$n)));
                  $rowBg = $i % 2 === 0 ? '#ffffff' : '#FFF5F5';
                  $isLast = $loop->last;
                @endphp
                <tr style="background:{{ $rowBg }};">
                  <td style="padding:7px 10px;text-align:center;font-weight:900;color:#B91C1C;{{ !$isLast ? 'border-bottom:1px solid #FECACA;' : '' }}">{{ $i + 1 }}</td>
                  <td style="padding:7px 10px;font-weight:700;color:#1F3A5F;{{ !$isLast ? 'border-bottom:1px solid #FECACA;' : '' }}">{{ $r->group_name ?? $r->group_key ?? '—' }}</td>
                  <td style="padding:7px 10px;text-align:right;{{ !$isLast ? 'border-bottom:1px solid #FECACA;' : '' }}">
                    <span style="display:inline-block;padding:3px 8px;border-radius:6px;font-weight:900;font-size:10.5px;background:#fecaca;color:#7f1d1d;border:1px solid #fca5a5;">▼ {{ $str }}</span>
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" style="padding:12px;text-align:center;color:#94A3B8;font-size:11px;">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </td>
      </tr>
    </table>

  </div>
  @endif

  {{-- ── Notes ──────────────────────── --}}
  <div style="margin-top:20px;font-size:10.5px;color:#64748B;padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-left:4px solid #005B82;border-radius:8px;line-height:1.6;">
    <strong style="color:#1F3A5F;font-weight:900;">Notes:</strong>
    Movement = <span style="background:rgba(0,0,0,0.06);padding:2px 5px;border-radius:4px;font-family:ui-monospace,'Courier New',monospace;font-size:10px;">end_balance − start_balance</span> for each period.
    MTD is measured from the last day of the previous month; YTD from 31 Dec of the previous year.
    Performing Loans excludes Corporate segment; deduped per account per snapshot.
    P50 (Head Office) excluded from all branch figures. Excel attachment contains full period detail.
  </div>

</div>
{{-- ═══════════════════════ END TABLE SECTION ═══════════════════════ --}}

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
              <span>Automated Finance Reports</span>
              <span style="color:#CBD5E1;margin:0 6px;">·</span>
              <span>Weekly Branch Movements</span>
            </span>
          </td>
          <td style="vertical-align:middle;text-align:right;">
            <span style="font-size:10.5px;color:#94A3B8;">Generated {{ now()->timezone(config('app.timezone', 'Africa/Nairobi'))->format('d M Y, H:i') }} EAT</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
{{-- ═══════════════════════ END FOOTER ═══════════════════════ --}}

</div>
</div>

</body>
</html>
