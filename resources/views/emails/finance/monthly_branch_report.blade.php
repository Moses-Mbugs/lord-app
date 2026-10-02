{{-- resources/views/emails/finance/monthly_branch_report.blade.php --}}
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
</head>
<body style="margin:0;padding:0;background:#EAEEF2;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1a1f2e;-webkit-font-smoothing:antialiased;">

@php
    $depP  = $report['deposit_periods'];
    $loanP = $report['loan_periods'];
    $rows  = $report['rows'];

    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';

    $abbr = function($v, bool $signed = true) {
        $n    = abs((float) $v);
        $sign = $signed ? ((float) $v < 0 ? '−' : '+') : '';
        if ($n >= 1_000_000_000) return $sign . number_format($n / 1_000_000_000, 2) . 'B';
        if ($n >= 1_000_000)     return $sign . number_format($n / 1_000_000, 2)     . 'M';
        if ($n >= 1_000)         return $sign . number_format($n / 1_000, 1)          . 'K';
        return $sign . number_format((int) $n);
    };

    $total = collect($rows)->firstWhere('code', 'ALL') ?? [];

    // Deposits blue, Loans green, NTB amber — same product colours as the Loans & Deposits email.
    $kpis = [
        ['label' => 'Deposits Month Δ', 'kind' => 'movement', 'value' => $total['dep_month'] ?? 0,  'sub' => 'from ' . $fmtDate($depP['month_start']), 'accent' => '#1D4ED8', 'tile' => '#EFF6FF', 'tx' => '#1E40AF'],
        ['label' => 'Deposits YTD Δ',   'kind' => 'movement', 'value' => $total['dep_ytd'] ?? 0,    'sub' => 'from ' . $fmtDate($depP['ytd_start']),   'accent' => '#1D4ED8', 'tile' => '#EFF6FF', 'tx' => '#1E40AF'],
        ['label' => 'Loans Month Δ',    'kind' => 'movement', 'value' => $total['loan_month'] ?? 0, 'sub' => $loanP ? 'from ' . $fmtDate($loanP['month_start']) : 'no loan snapshot', 'accent' => '#15803D', 'tile' => '#F0FDF4', 'tx' => '#166534'],
        ['label' => 'NTB Month',        'kind' => 'count',    'value' => $total['ntb_month'] ?? 0,  'sub' => 'new customers', 'accent' => '#B45309', 'tile' => '#FFFBEB', 'tx' => '#92400E'],
        ['label' => 'NTB YTD',          'kind' => 'count',    'value' => $total['ntb_ytd'] ?? 0,    'sub' => 'new customers', 'accent' => '#B45309', 'tile' => '#FFFBEB', 'tx' => '#92400E'],
    ];

    $mvBadge = fn($v) => (float) $v >= 0
        ? 'display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:#DCFCE7;color:#14532D;border:1px solid #86EFAC;'
        : 'display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:#FEE2E2;color:#7F1D1D;border:1px solid #FCA5A5;';
    $mvText = fn($v) => ((float) $v >= 0 ? '▲ ' : '▼ ') . $abbr($v, false);
    $th     = 'padding:6px 10px;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;';
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
            <div style="font-size:26px;font-weight:900;color:#ffffff;letter-spacing:-0.6px;line-height:1.1;">{{ \Carbon\Carbon::createFromFormat('!Y-m', $report['month'])->format('F') }} Branch Performance</div>
            <div style="font-size:12px;font-weight:500;color:rgba(255,255,255,0.55);margin-top:5px;letter-spacing:0.2px;">
              <span style="color:#93C5FD;">Deposits</span> &nbsp;·&nbsp; <span style="color:#86EFAC;">Performing Loans</span> &nbsp;·&nbsp; <span style="color:#FCD34D;">NTB</span> &nbsp;·&nbsp; All Branches (P50 excluded)
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
        $v      = (float) $kpi['value'];
        $isMv   = $kpi['kind'] === 'movement';
        $color  = $isMv ? ($v >= 0 ? '#15803D' : '#BE123C') : $kpi['tx'];
        $bg     = $isMv ? ($v >= 0 ? '#F0FDF4' : '#FFF1F2') : '#ffffff';
        $bd     = $isMv ? ($v >= 0 ? '#BBF7D0' : '#FECDD3') : '#FDE68A';
        $text   = $isMv ? $abbr($v) : number_format((int) $v);
        $isLast = $i === count($kpis) - 1;
      @endphp
      <td style="padding:16px 18px;{{ !$isLast ? 'border-right:1px solid #E2E8F0;' : '' }}border-top:3px solid {{ $kpi['accent'] }};vertical-align:top;background:{{ $kpi['tile'] }};width:{{ round(100 / count($kpis), 4) }}%;" bgcolor="{{ $kpi['tile'] }}">
        <div style="font-size:9px;font-weight:800;color:{{ $kpi['tx'] }};text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;white-space:nowrap;">{{ $kpi['label'] }}</div>
        <div style="display:inline-block;padding:4px 9px;border-radius:8px;background:{{ $bg }};border:1px solid {{ $bd }};">
          <span style="font-size:16px;font-weight:900;color:{{ $color }};font-family:'Courier New',ui-monospace,monospace;letter-spacing:-0.5px;">{{ $text }}</span>
        </div>
        <div style="font-size:9.5px;color:#94A3B8;margin-top:5px;white-space:nowrap;">{{ $kpi['sub'] }}</div>
      </td>
    @endforeach
  </tr>
</table>

<div style="padding:22px 28px 30px;">

  <table width="100%" cellpadding="0" cellspacing="0"
    style="width:100%;border-collapse:separate;border-spacing:0;font-size:11px;border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <thead>
      <tr>
        <th rowspan="2" style="padding:7px 10px;background:#F1F5F9;border-bottom:2px solid #CBD5E1;text-align:left;font-size:9px;font-weight:900;color:#475569;text-transform:uppercase;letter-spacing:0.7px;border-right:1px solid #CBD5E1;width:22%;">Branch</th>
        <th colspan="3" bgcolor="#1E3A8A" style="padding:6px 10px;background:#1E3A8A;text-align:center;font-size:9px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:0.7px;border-right:2px solid #BFDBFE;">Deposits</th>
        <th colspan="2" bgcolor="#14532D" style="padding:6px 10px;background:#14532D;text-align:center;font-size:9px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:0.7px;border-right:2px solid #BBF7D0;">Loans</th>
        <th colspan="2" bgcolor="#92400E" style="padding:6px 10px;background:#92400E;text-align:center;font-size:9px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:0.7px;">NTB</th>
      </tr>
      <tr>
        <th style="{{ $th }}background:#EFF6FF;color:#1D4ED8;">Month Δ</th>
        <th style="{{ $th }}background:#EFF6FF;color:#1D4ED8;">YTD Δ</th>
        <th style="{{ $th }}background:#EFF6FF;color:#1D4ED8;border-right:2px solid #BFDBFE;">Closing</th>
        <th style="{{ $th }}background:#F0FDF4;color:#15803D;">Month Δ</th>
        <th style="{{ $th }}background:#F0FDF4;color:#15803D;border-right:2px solid #BBF7D0;">Closing</th>
        <th style="{{ $th }}background:#FFFBEB;color:#B45309;">Month</th>
        <th style="{{ $th }}background:#FFFBEB;color:#B45309;">YTD</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($rows as $b)
        @php
          $isTotal = $b['code'] === 'ALL';
          $border  = $loop->last ? 'none' : '1px solid #E2E8F0';
          $td      = "padding:7px 10px;border-bottom:{$border};text-align:right;";
          $depBg   = $isTotal ? '#DBEAFE' : ($loop->even ? '#EFF6FF' : '#F8FBFF');
          $loanBg  = $isTotal ? '#DCFCE7' : ($loop->even ? '#F0FDF4' : '#F8FEFA');
          $ntbBg   = $isTotal ? '#FEF3C7' : ($loop->even ? '#FFFBEB' : '#FFFDF5');
          $mono    = "font-family:ui-monospace,'Courier New',monospace;font-weight:700;";
        @endphp
        <tr style="background:{{ $isTotal ? '#F1F5F9' : '#ffffff' }};">
          <td style="padding:7px 10px;border-bottom:{{ $border }};border-right:1px solid #E2E8F0;font-weight:{{ $isTotal ? '900' : '700' }};font-size:10.5px;color:#1F3A5F;text-transform:uppercase;">
            {{ $isTotal ? 'TOTAL' : $b['name'] }}
          </td>
          <td style="{{ $td }}background:{{ $depBg }};"><span style="{{ $mvBadge($b['dep_month']) }}">{{ $mvText($b['dep_month']) }}</span></td>
          <td style="{{ $td }}background:{{ $depBg }};"><span style="{{ $mvBadge($b['dep_ytd']) }}">{{ $mvText($b['dep_ytd']) }}</span></td>
          <td style="{{ $td }}{{ $mono }}background:{{ $depBg }};color:#1E40AF;border-right:2px solid #BFDBFE;">{{ $abbr($b['dep_balance'], false) }}</td>
          @if ($loanP)
            <td style="{{ $td }}background:{{ $loanBg }};"><span style="{{ $mvBadge($b['loan_month']) }}">{{ $mvText($b['loan_month']) }}</span></td>
            <td style="{{ $td }}{{ $mono }}background:{{ $loanBg }};color:#166534;border-right:2px solid #BBF7D0;">{{ $abbr($b['loan_balance'], false) }}</td>
          @else
            <td colspan="2" style="{{ $td }}background:{{ $loanBg }};text-align:center;color:#94A3B8;border-right:2px solid #BBF7D0;">—</td>
          @endif
          <td style="{{ $td }}{{ $mono }}background:{{ $ntbBg }};color:#92400E;">{{ number_format($b['ntb_month']) }}</td>
          <td style="{{ $td }}{{ $mono }}background:{{ $ntbBg }};color:#92400E;">{{ number_format($b['ntb_ytd']) }}</td>
        </tr>
      @empty
        <tr><td colspan="8" style="padding:20px;text-align:center;color:#94A3B8;">No branch data for this period.</td></tr>
      @endforelse
    </tbody>
  </table>

  <div style="margin-top:20px;font-size:10.5px;color:#64748B;padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-left:4px solid #005B82;border-radius:8px;line-height:1.6;">
    <strong style="color:#1F3A5F;font-weight:900;">Notes:</strong>
    Month Δ compares the last posted balance of {{ $report['label'] }} with the last posted balance of the previous month; Closing is the balance at month end.
    Deposits YTD Δ is measured from the last balance of the previous year, or the earliest balance held this year where there is no prior-year data.
    Loans are the performing book excluding the Corporate segment (as in the weekly branch report), month-on-month only.
    NTB = distinct CIFs with a new account opened in the calendar month / year. P50 (Head Office), staff accounts and staff loans excluded throughout.
    The Excel attachment adds each branch's top 20 deposit and loan customer movers for the month.
  </div>

</div>

{{-- ═══════════════════════ FOOTER ═══════════════════════ --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;border-top:2px solid #E2E8F0;" bgcolor="#F8FAFC">
  <tr>
    <td style="padding:14px 32px;background:#F8FAFC;" bgcolor="#F8FAFC">
      <span style="font-size:11px;color:#94A3B8;">
        <strong style="color:#334155;font-weight:800;font-size:12px;">Ecobank Kenya</strong>
        <span style="color:#CBD5E1;margin:0 6px;">·</span>
        <span>Monthly Branch Performance</span>
        <span style="color:#CBD5E1;margin:0 6px;">·</span>
        <span>Generated {{ now()->timezone('Africa/Nairobi')->format('d M Y, H:i') }} EAT</span>
      </span>
    </td>
  </tr>
</table>

</div>
</div>

</body>
</html>
