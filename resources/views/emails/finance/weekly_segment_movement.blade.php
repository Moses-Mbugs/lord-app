<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
</head>
<body style="margin:0;padding:0;background:#EAEEF2;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1a1f2e;-webkit-font-smoothing:antialiased;">

@php
    $periods     = $data['periods'] ?? [];
    $weekEnd     = $weekEnd ?? '';
    $weekStart   = $periods['week_start'] ?? '';
    $mtdStart    = $periods['mtd_start']  ?? '';
    $ytdStart     = $periods['ytd_start']  ?? '';
    $bankSegments = $data['bank']['segments'] ?? [];
    $lcySegments  = $data['lcy']['segments']  ?? [];
    $fcySegments  = $data['fcy']['segments']  ?? [];

    $fmtDate  = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';
    $fmtShort = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M')   : '—';

    // Abbreviate large numbers  e.g. 88,000,000,000 → 88.0B
    $abbr = function($v) {
        $n = abs((float) $v);
        if ($n >= 1_000_000_000) return ($v < 0 ? '−' : '+') . number_format($n / 1_000_000_000, 2) . 'B';
        if ($n >= 1_000_000)     return ($v < 0 ? '−' : '+') . number_format($n / 1_000_000,     2) . 'M';
        if ($n >= 1_000)         return ($v < 0 ? '−' : '+') . number_format($n / 1_000,          1) . 'K';
        return ($v < 0 ? '−' : '+') . number_format((int) $n);
    };

    $abbrDeposit = function($v) {
        $n = abs((float) $v);
        if ($n >= 1_000_000_000) return 'KES ' . number_format($n / 1_000_000_000, 2) . 'B';
        if ($n >= 1_000_000)     return 'KES ' . number_format($n / 1_000_000,     2) . 'M';
        return 'KES ' . number_format((int) $n);
    };

    // Extract totals row for KPI strip — bank level (all currencies)
    $totalsRow = collect($bankSegments)->firstWhere('code', 'ALL') ?? [];
    $kpis = [
        ['label' => 'Total Bank Deposits', 'value' => $totalsRow['total_deposits'] ?? 0, 'mv' => false, 'sub' => 'as at ' . $fmtDate($weekEnd)],
        ['label' => 'Weekly Movement',  'value' => $totalsRow['weekly_mv']      ?? 0, 'mv' => true,  'sub' => $fmtShort($weekStart) . ' → ' . $fmtShort($weekEnd)],
        ['label' => 'Month-to-Date',    'value' => $totalsRow['mtd_mv']         ?? 0, 'mv' => true,  'sub' => 'from ' . $fmtDate($mtdStart)],
        ['label' => 'Year-to-Date',     'value' => $totalsRow['ytd_mv']         ?? 0, 'mv' => true,  'sub' => 'from ' . $fmtDate($ytdStart)],
    ];
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
              Weekly Deposits Movement</div>
            <div style="font-size:12px;font-weight:500;color:rgba(255,255,255,0.55);margin-top:5px;letter-spacing:0.2px;">
              Corporate &nbsp;·&nbsp; Commercial &nbsp;·&nbsp; Consumer Banking
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
        $mv  = (float) $kpi['value'];
        $isGain = $mv >= 0;
        $mvColor = $kpi['mv'] ? ($isGain ? '#15803D' : '#BE123C') : '#0F172A';
        $mvBg    = $kpi['mv'] ? ($isGain ? '#F0FDF4'  : '#FFF1F2') : '#F1F5F9';
        $mvBd    = $kpi['mv'] ? ($isGain ? '#BBF7D0'  : '#FECDD3') : '#E2E8F0';
        $numText = $kpi['mv'] ? $abbr($mv) : $abbrDeposit($mv);
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

  {{-- Bank Summary section label --}}
  <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;">
    <tr>
      <td style="padding-right:10px;vertical-align:middle;">
        <div style="width:4px;height:18px;background:linear-gradient(180deg,#00B4D8 0%,#0077B6 100%);border-radius:2px;"></div>
      </td>
      <td style="vertical-align:middle;">
        <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">Segment Breakdown</span>
        <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· Bank (All Currencies, KES Equivalent)</span>
      </td>
    </tr>
  </table>

  @include('emails.finance.partials.weekly_segment_table', [
      'segments'  => $bankSegments,
      'weekStart' => $weekStart,
      'weekEnd'   => $weekEnd,
      'mtdStart'  => $mtdStart,
      'ytdStart'  => $ytdStart,
  ])

  {{-- LCY section ──────────────────────────────────────────── --}}
  <div style="margin-top:32px;">
    <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;">
      <tr>
        <td style="padding-right:10px;vertical-align:middle;">
          <div style="width:4px;height:18px;background:linear-gradient(180deg,#00B4D8 0%,#0077B6 100%);border-radius:2px;"></div>
        </td>
        <td style="vertical-align:middle;">
          <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">Segment Breakdown</span>
          <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· LCY (KES)</span>
        </td>
      </tr>
    </table>

    @include('emails.finance.partials.weekly_segment_table', [
        'segments'  => $lcySegments,
        'weekStart' => $weekStart,
        'weekEnd'   => $weekEnd,
        'mtdStart'  => $mtdStart,
        'ytdStart'  => $ytdStart,
    ])
  </div>

  {{-- FCY section ──────────────────────────────────────────── --}}
  <div style="margin-top:32px;">
    <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;">
      <tr>
        <td style="padding-right:10px;vertical-align:middle;">
          <div style="width:4px;height:18px;background:linear-gradient(180deg,#00B4D8 0%,#0077B6 100%);border-radius:2px;"></div>
        </td>
        <td style="vertical-align:middle;">
          <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">Segment Breakdown</span>
          <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· FCY (KES Equivalent)</span>
        </td>
      </tr>
    </table>

    @include('emails.finance.partials.weekly_segment_table', [
        'segments'  => $fcySegments,
        'weekStart' => $weekStart,
        'weekEnd'   => $weekEnd,
        'mtdStart'  => $mtdStart,
        'ytdStart'  => $ytdStart,
    ])
  </div>

  {{-- ── Historical Comparison Section ──────────────────────── --}}
  @if(!empty($historicalSection['bank']['segments']) || !empty($historicalSection['lcy']['segments']) || !empty($historicalSection['fcy']['segments']))
  <div style="margin-top:32px;">

    {{-- Section label --}}
    <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;">
      <tr>
        <td style="padding-right:10px;vertical-align:middle;">
          <div style="width:4px;height:18px;background:linear-gradient(180deg,#F59E0B 0%,#B45309 100%);border-radius:2px;"></div>
        </td>
        <td style="vertical-align:middle;">
          <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">Historical Comparison</span>
          <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· Bank, LCY &amp; FCY</span>
        </td>
      </tr>
    </table>

    @include('emails.finance.partials.weekly_segment_historical_table', [
        'historicalSection' => $historicalSection,
    ])
  </div>
  @endif

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
              {{--  <span>Automated Finance Reports</span>  --}}
              <span style="color:#CBD5E1;margin:0 6px;">·</span>
              <span>Weekly Segment Movement</span>
            </span>
          </td>
          <td style="vertical-align:middle;text-align:right;">
            <span style="font-size:10.5px;color:#94A3B8;">Built by Moses {{ now()->timezone('Africa/Nairobi')->format('d M Y, H:i') }} </span>
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
