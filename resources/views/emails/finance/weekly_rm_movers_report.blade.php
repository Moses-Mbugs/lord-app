{{-- resources/views/emails/finance/weekly_rm_movers_report.blade.php --}}
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

    $segmentsData = $segmentsData ?? [];
    $grandData    = $grandData ?? [];

    $fmtDate  = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';
    $fmtShort = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M')   : '—';

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

    $weekAll = $grandData['week'] ?? null;
    $mtdAll  = $grandData['mtd']  ?? null;
    $ytdAll  = $grandData['ytd']  ?? null;

    $depWtd          = (float) ($weekAll->movement    ?? 0);
    $depMtd          = (float) ($mtdAll->movement     ?? 0);
    $depYtd          = (float) ($ytdAll->movement     ?? 0);
    $depClosingTotal = (float) ($weekAll->end_balance ?? 0);

    $ntbWtd = (int) ($weekAll->ntb_count ?? 0);
    $ntbMtd = (int) ($mtdAll->ntb_count  ?? 0);
    $ntbYtd = (int) ($ytdAll->ntb_count  ?? 0);

    $kpis = [
        ['label' => 'Deposits WTD',   'kind' => 'movement', 'value' => $depWtd,          'sub' => $fmtShort($weekStart) . ' → ' . $fmtShort($weekEnd)],
        ['label' => 'Deposits MTD',   'kind' => 'movement', 'value' => $depMtd,          'sub' => 'from ' . $fmtDate($mtdStart)],
        ['label' => 'Deposits YTD',   'kind' => 'movement', 'value' => $depYtd,          'sub' => 'from ' . $fmtDate($ytdStart)],
        ['label' => 'Total Deposits', 'kind' => 'balance',  'value' => $depClosingTotal, 'sub' => 'as at ' . $fmtDate($weekEnd)],
        ['label' => 'NTB WTD',        'kind' => 'count',    'value' => $ntbWtd,          'sub' => $fmtShort($weekStart) . ' → ' . $fmtShort($weekEnd)],
        ['label' => 'NTB MTD',        'kind' => 'count',    'value' => $ntbMtd,          'sub' => 'from ' . $fmtDate($mtdStart)],
        ['label' => 'NTB YTD',        'kind' => 'count',    'value' => $ntbYtd,          'sub' => 'from ' . $fmtDate($ytdStart)],
    ];
@endphp

<div style="max-width:1100px;margin:0 auto;padding:24px 14px;">
<div style="background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.10),0 2px 6px rgba(0,0,0,0.06);border:1px solid #D9E2EC;">

{{-- ═══════════════════════ HEADER ═══════════════════════ --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;" bgcolor="#002E4A">
  <tr>
    <td style="padding:28px 32px 26px;background:linear-gradient(140deg,#002E4A 0%,#00476A 55%,#005E8A 100%);" bgcolor="#002E4A">

      <div style="font-size:10px;font-weight:800;color:rgba(255,255,255,0.45);letter-spacing:2.5px;text-transform:uppercase;margin-bottom:8px;">
        ECOBANK KENYA
      </div>

      <table width="100%" cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">
        <tr>
          <td style="vertical-align:top;">
            <div style="font-size:26px;font-weight:900;color:#ffffff;letter-spacing:-0.6px;line-height:1.1;">
              Weekly RM Movements
            </div>
            <div style="font-size:12px;font-weight:500;color:rgba(255,255,255,0.55);margin-top:5px;letter-spacing:0.2px;">
              Deposits &bull; Performing Loans &bull; NTB &nbsp;·&nbsp; Fixed RM Portfolio
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

{{-- ═══════════════════════ KPI STRIP (all segments combined) ═══════════════════════ --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;border-bottom:1px solid #E2E8F0;" bgcolor="#F8FAFC">
  <tr>
    @foreach ($kpis as $i => $kpi)
      @php
        $kind   = $kpi['kind'] ?? 'movement';
        $mv     = (float) $kpi['value'];
        $isGain = $mv >= 0;

        if ($kind === 'movement') {
            $mvColor = $isGain ? '#15803D' : '#BE123C';
            $mvBg    = $isGain ? '#F0FDF4' : '#FFF1F2';
            $mvBd    = $isGain ? '#BBF7D0' : '#FECDD3';
            $numText = $abbr($mv);
        } elseif ($kind === 'balance') {
            $mvColor = '#0F172A';
            $mvBg    = '#F1F5F9';
            $mvBd    = '#E2E8F0';
            $numText = $abbrAbs($mv);
        } else {
            $mvColor = '#B45309';
            $mvBg    = '#FFFBEB';
            $mvBd    = '#FDE68A';
            $numText = number_format((int) $mv);
        }

        $isLast  = $i === count($kpis) - 1;
        $tdWidth = round(100 / count($kpis), 4);
      @endphp
      <td style="padding:16px 18px;{{ !$isLast ? 'border-right:1px solid #E2E8F0;' : '' }}vertical-align:top;background:#F8FAFC;width:{{ $tdWidth }}%;" bgcolor="#F8FAFC">
        <div style="font-size:9px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;white-space:nowrap;">
          {{ $kpi['label'] }}
        </div>
        <div style="display:inline-block;padding:4px 9px;border-radius:8px;background:{{ $mvBg }};border:1px solid {{ $mvBd }};">
          <span style="font-size:16px;font-weight:900;color:{{ $mvColor }};font-family:'Courier New',ui-monospace,monospace;letter-spacing:-0.5px;">{{ $numText }}</span>
        </div>
        <div style="font-size:9.5px;color:#94A3B8;margin-top:5px;white-space:nowrap;">{{ $kpi['sub'] }}</div>
      </td>
    @endforeach
  </tr>
</table>
{{-- ═══════════════════════ END KPI STRIP ═══════════════════════ --}}

{{-- ═══════════════════════ TABLE SECTION ═══════════════════════ --}}
<div style="padding:22px 28px 30px;">

  @php $budgetData = $budgetData ?? []; @endphp

  @foreach ($segmentsData as $segment => $data)
    <div style="{{ $loop->first ? '' : 'margin-top:28px;' }}">
      @include('emails.finance.partials.weekly_rm_movers_segment', [
          'segment'   => $segment,
          'data'      => $data,
          'weekEnd'   => $weekEnd,
          'weekStart' => $weekStart,
          'mtdStart'  => $mtdStart,
          'ytdStart'  => $ytdStart,
          'fmtDate'   => $fmtDate,
          'fmtShort'  => $fmtShort,
      ])

      @if (isset($budgetData[$segment]))
        @include('emails.finance.partials.weekly_rm_budget_table', [
            'segment'    => $segment,
            'rows'       => $budgetData[$segment]['rows'],
            'totals'     => $budgetData[$segment]['totals'],
            'targetYear' => $targetYear ?? now()->year,
        ])
      @endif
    </div>
  @endforeach

  @include('emails.finance.partials.weekly_top_deposit_movers', [
      'topGainers' => $topGainers ?? collect(),
      'topLosers'  => $topLosers ?? collect(),
  ])

  {{-- ── Notes ──────────────────────── --}}
  <div style="margin-top:20px;font-size:10.5px;color:#64748B;padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-left:4px solid #005B82;border-radius:8px;line-height:1.6;">
    <strong style="color:#1F3A5F;font-weight:900;">Notes:</strong>
    Deposits/Loans Δ = <span style="background:rgba(0,0,0,0.06);padding:2px 5px;border-radius:4px;font-family:ui-monospace,'Courier New',monospace;font-size:10px;">end_balance − start_balance</span> for each period; Closing Bal is the balance as at {{ $fmtDate($weekEnd) }}.
    MTD is measured from the last day of the previous month; YTD from 31 Dec of the previous year.
    NTB = distinct CIFs with a new account opened in that period, attributed to the RM on that account.
    Performing Loans excludes Corporate segment; deduped per account per snapshot. Deposits and NTB are tracked WTD/MTD/YTD; Loans are tracked WTD/MTD only (no YTD).
    Rank is by Deposits WTD Δ, highest first within each segment (Total row excluded from ranking).
    P50 branch and GL 216220001 excluded from deposits, matching the daily RM Movers report. Staff accounts (account_class = KECATF) are also excluded.
    This report is grouped by Job Unit segment (Premier/Advantage/Direct), scoped to a fixed RM portfolio list.
    @if (!empty($budgetData))
      Budget vs Actual compares the current deposit closing balance and YTD NTB count against each RM's FY{{ $targetYear ?? now()->year }} target (set via /rm-targets/manage); RMs with no target recorded show "—".
    @endif
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
              <span>Weekly RM Movements</span>
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
