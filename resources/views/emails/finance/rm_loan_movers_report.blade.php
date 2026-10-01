{{-- resources/views/emails/finance/rm_loan_movers_report.blade.php --}}
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
</head>
<body style="margin:0;padding:0;background:#EAEEF2;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1a1f2e;-webkit-font-smoothing:antialiased;">

@php
    $segmentsData = $segmentsData ?? [];
    $t = $grandTotals ?? (object) [];

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

    $mv = (float) ($t->loan_movement ?? 0);
    $isGain = $mv >= 0;

    $kpis = [
        ['label' => 'Loan Accounts', 'kind' => 'count',    'value' => (int) ($t->account_count ?? 0),  'sub' => 'as at ' . $fmtDate($end)],
        ['label' => 'Customers',     'kind' => 'count',    'value' => (int) ($t->customer_count ?? 0), 'sub' => 'as at ' . $fmtDate($end)],
        ['label' => 'Loan Movement', 'kind' => 'movement', 'value' => $mv,                              'sub' => $fmtShort($start) . ' → ' . $fmtShort($end)],
        ['label' => 'Total Loan Book', 'kind' => 'balance', 'value' => (float) ($t->loan_close ?? 0),   'sub' => 'as at ' . $fmtDate($end)],
    ];
@endphp

<div style="max-width:1100px;margin:0 auto;padding:24px 14px;">
<div style="background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.10),0 2px 6px rgba(0,0,0,0.06);border:1px solid #D9E2EC;">

{{-- HEADER --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;" bgcolor="#002E4A">
  <tr>
    <td style="padding:28px 32px 26px;background:linear-gradient(140deg,#002E4A 0%,#00476A 55%,#005E8A 100%);" bgcolor="#002E4A">
      <div style="font-size:10px;font-weight:800;color:rgba(255,255,255,0.45);letter-spacing:2.5px;text-transform:uppercase;margin-bottom:8px;">ECOBANK KENYA</div>
      <table width="100%" cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">
        <tr>
          <td style="vertical-align:top;">
            <div style="font-size:26px;font-weight:900;color:#ffffff;letter-spacing:-0.6px;line-height:1.1;">RM Loan Movers</div>
            <div style="font-size:12px;font-weight:500;color:rgba(255,255,255,0.55);margin-top:5px;letter-spacing:0.2px;">
              Loan portfolio movement per RM &nbsp;·&nbsp; Performing book, Corporate &amp; Staff loans excluded
            </div>
          </td>
          <td style="vertical-align:top;text-align:right;white-space:nowrap;">
            <div style="display:inline-block;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.22);border-radius:10px;padding:8px 16px;">
              <div style="font-size:9.5px;font-weight:700;color:rgba(255,255,255,0.55);text-transform:uppercase;letter-spacing:1px;">Period</div>
              <div style="font-size:16px;font-weight:900;color:#ffffff;margin-top:1px;">{{ $fmtShort($start) }} → {{ $fmtShort($end) }}</div>
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

{{-- KPI STRIP --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;border-bottom:1px solid #E2E8F0;" bgcolor="#F8FAFC">
  <tr>
    @foreach ($kpis as $i => $kpi)
      @php
        $kind = $kpi['kind'] ?? 'count';
        $val  = $kpi['value'];

        if ($kind === 'movement') {
            $kGain = (float) $val >= 0;
            $mvColor = $kGain ? '#15803D' : '#BE123C';
            $mvBg    = $kGain ? '#F0FDF4' : '#FFF1F2';
            $mvBd    = $kGain ? '#BBF7D0' : '#FECDD3';
            $numText = $abbr($val);
        } elseif ($kind === 'balance') {
            $mvColor = '#0F172A'; $mvBg = '#F1F5F9'; $mvBd = '#E2E8F0';
            $numText = $abbrAbs($val);
        } else {
            $mvColor = '#166534'; $mvBg = '#F0FDF4'; $mvBd = '#BBF7D0';
            $numText = number_format((int) $val);
        }

        $isLast  = $i === count($kpis) - 1;
        $tdWidth = round(100 / count($kpis), 4);
      @endphp
      <td style="padding:16px 18px;{{ !$isLast ? 'border-right:1px solid #E2E8F0;' : '' }}vertical-align:top;background:#F8FAFC;width:{{ $tdWidth }}%;" bgcolor="#F8FAFC">
        <div style="font-size:9px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;white-space:nowrap;">{{ $kpi['label'] }}</div>
        <div style="display:inline-block;padding:4px 9px;border-radius:8px;background:{{ $mvBg }};border:1px solid {{ $mvBd }};">
          <span style="font-size:16px;font-weight:900;color:{{ $mvColor }};font-family:'Courier New',ui-monospace,monospace;letter-spacing:-0.5px;">{{ $numText }}</span>
        </div>
        <div style="font-size:9.5px;color:#94A3B8;margin-top:5px;white-space:nowrap;">{{ $kpi['sub'] }}</div>
      </td>
    @endforeach
  </tr>
</table>

{{-- CONTENT --}}
<div style="padding:22px 28px 30px;">

  @foreach ($segmentsData as $segment => $sd)
    {{-- SEGMENT BANNER --}}
    <div style="margin:{{ $loop->first ? 0 : 28 }}px 0 12px;padding:8px 14px;background:linear-gradient(90deg,#166534 0%,#15803D 100%);border-radius:8px;">
      <span style="font-size:14px;font-weight:900;color:#ffffff;letter-spacing:0.2px;">{{ $segment }}</span>
      <span style="font-size:11px;font-weight:700;color:#DCFCE7;margin-left:8px;">{{ $sd['rmRows']->count() }} RM{{ $sd['rmRows']->count() === 1 ? '' : 's' }}</span>
    </div>

    <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;">
      <tr>
        <td style="padding-right:10px;vertical-align:middle;">
          <div style="width:4px;height:18px;background:linear-gradient(180deg,#86EFAC 0%,#166534 100%);border-radius:2px;"></div>
        </td>
        <td style="vertical-align:middle;">
          <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">RM Loan Summary</span>
          <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· KES Equivalent</span>
        </td>
      </tr>
    </table>

    @include('emails.finance.partials.rm_loan_summary_table', [
        'rows'   => $sd['rmRows'],
        'totals' => $sd['totals'],
    ])

    @if ($sd['topGainers']->isNotEmpty() || $sd['topLosers']->isNotEmpty())
    <div style="margin-top:28px;">
      <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;">
        <tr>
          <td style="padding-right:10px;vertical-align:middle;">
            <div style="width:4px;height:18px;background:linear-gradient(180deg,#F59E0B 0%,#B45309 100%);border-radius:2px;"></div>
          </td>
          <td style="vertical-align:middle;">
            <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">Top Loan Movers</span>
            <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· {{ $segment }}</span>
          </td>
        </tr>
      </table>

      @include('emails.finance.partials.loan_movers_side_by_side', [
          'topGainers' => $sd['topGainers'],
          'topLosers'  => $sd['topLosers'],
      ])
    </div>
    @endif
  @endforeach

  <div style="margin-top:20px;font-size:10.5px;color:#64748B;padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-left:4px solid #005B82;border-radius:8px;line-height:1.6;">
    <strong style="color:#1F3A5F;font-weight:900;">Notes:</strong>
    Loan Movement = <span style="background:rgba(0,0,0,0.06);padding:2px 5px;border-radius:4px;font-family:ui-monospace,'Courier New',monospace;font-size:10px;">close − open</span>, using the nearest available loan snapshot on/before each period date.
    Performing book only (Normal/Watch/OAEM/Substandard, or blank status). Corporate segment and any loan whose linecode contains "Staff" are excluded.
    Accounts/Customers reflect the latest snapshot. This report is scoped to a fixed RM portfolio list.
  </div>

</div>

{{-- FOOTER --}}
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
              <span>RM Loan Movers</span>
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

</div>
</div>

</body>
</html>
