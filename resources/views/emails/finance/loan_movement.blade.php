<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
</head>
<body style="margin:0;padding:0;background:#EAEEF2;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1a1f2e;-webkit-font-smoothing:antialiased;">

@php
  $segments = $data['segments'] ?? [];
  $totals   = $data['totals']   ?? [];
  $gainers  = $data['movers']['gainers'] ?? [];
  $losers   = $data['movers']['losers']  ?? [];

  $fmtDate  = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';
  $shortDt  = fn($d) => $d ? strtoupper(\Carbon\Carbon::parse($d)->format('d M')) : '—';

  // Full number with commas — no abbreviation
  $fmtFull  = fn($v) => number_format((float) $v, 0);

  // Signed full number: +1,302,308 or −6,690,617
  $fmtMvFull = function($v) {
    $n   = (float) $v;
    $abs = abs($n);
    $sgn = $n >= 0 ? '+' : '−';
    return $sgn . number_format($abs, 0);
  };

  $fmtMv = function($v) {
    $n = abs((float) $v); $sgn = (float)$v >= 0 ? '+' : '−';
    if ($n >= 1_000_000_000) return $sgn . number_format($n / 1_000_000_000, 2) . 'B';
    if ($n >= 1_000_000)     return $sgn . number_format($n / 1_000_000,     2) . 'M';
    if ($n >= 1_000)         return $sgn . number_format($n / 1_000,          1) . 'K';
    return $sgn . number_format((int) $n);
  };

  $mvColor  = fn($v) => (float)$v >= 0 ? '#15803D' : '#BE123C';
  $mvBg     = fn($v) => (float)$v >= 0 ? '#F0FDF4' : '#FFF1F2';
  $mvBd     = fn($v) => (float)$v >= 0 ? '#BBF7D0' : '#FECDD3';
  $mvLight  = fn($v) => (float)$v >= 0 ? '#6EE7B7' : '#FCA5A5';
  $dirIcon  = fn($d) => match($d) { 'GAIN' => '▲', 'LOSS' => '▼', default => '—' };

  $grandMv       = $totals['movement']   ?? 0;
  $grandDir      = $totals['direction']  ?? 'FLAT';
  $grandEnd      = $totals['endBalance'] ?? 0;
  $bankTotalEnd  = $totals['bankTotalEnd'] ?? 0;
@endphp

{{-- 900 px to fit 6 columns comfortably --}}
<div style="max-width:900px;margin:0 auto;padding:24px 14px;">
<div style="background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.10),0 2px 6px rgba(0,0,0,0.06);border:1px solid #D9E2EC;">

{{-- ═══════════ HEADER ═══════════ --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;" bgcolor="#003D2E">
  <tr>
    <td style="padding:28px 32px 26px;background:linear-gradient(140deg,#003D2E 0%,#005C43 55%,#007A59 100%);">

      <div style="font-size:10px;font-weight:800;color:rgba(255,255,255,0.45);letter-spacing:2.5px;text-transform:uppercase;margin-bottom:8px;">
        ECOBANK KENYA · FINANCE
      </div>

      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="vertical-align:top;">
            <div style="font-size:26px;font-weight:900;color:#ffffff;letter-spacing:-0.6px;line-height:1.1;">Performing Loan Book Movement</div>
            <div style="font-size:12px;font-weight:500;color:rgba(255,255,255,0.55);margin-top:5px;">
              KES Equivalent &nbsp;·&nbsp; All Segments  &nbsp;·&nbsp; Principal Balances
            </div>
          </td>
          <td style="vertical-align:top;text-align:right;white-space:nowrap;">
            <div style="display:inline-block;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.22);border-radius:10px;padding:8px 16px;">
              <div style="font-size:9.5px;font-weight:700;color:rgba(255,255,255,0.55);text-transform:uppercase;letter-spacing:1px;">Report date</div>
              <div style="font-size:16px;font-weight:900;color:#ffffff;margin-top:1px;">{{ $fmtDate($end) }}</div>
            </div>
          </td>
        </tr>
      </table>

      <div style="margin-top:16px;">
        <span style="display:inline-block;padding:5px 13px;border-radius:999px;font-size:10.5px;font-weight:700;color:#ffffff;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);white-space:nowrap;">
          {{ $fmtDate($start) }} → {{ $fmtDate($end) }}
        </span>
      </div>

    </td>
  </tr>
</table>
{{-- ═══════════ END HEADER ═══════════ --}}

{{-- ═══════════ KPI STRIP ═══════════ --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-bottom:1px solid #E2E8F0;" bgcolor="#F8FAFC">
  <tr>
    <td style="padding:18px 22px;border-right:1px solid #E2E8F0;border-bottom:1px solid #E2E8F0;vertical-align:top;width:50%;" bgcolor="#F8FAFC">
      <div style="font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Performing Loan Book</div>
      <div style="font-size:19px;font-weight:900;color:#0F172A;font-family:'Courier New',ui-monospace,monospace;letter-spacing:-0.5px;">
        KES {{ $fmtFull($grandEnd) }}
      </div>
      <div style="font-size:10px;color:#94A3B8;margin-top:4px;">as at {{ $fmtDate($end) }}</div>
    </td>
    <td style="padding:18px 22px;border-bottom:1px solid #E2E8F0;vertical-align:top;width:50%;" bgcolor="#F8FAFC">
      <div style="font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Total Loan Book (Bank-Wide)</div>
      <div style="font-size:19px;font-weight:900;color:#0F172A;font-family:'Courier New',ui-monospace,monospace;letter-spacing:-0.5px;">
        KES {{ $fmtFull($bankTotalEnd) }}
      </div>
      <div style="font-size:10px;color:#94A3B8;margin-top:4px;">as at {{ $fmtDate($end) }} · all statuses</div>
    </td>
  </tr>
  <tr>
    <td style="padding:18px 22px;border-right:1px solid #E2E8F0;vertical-align:top;width:50%;" bgcolor="#F8FAFC">
      <div style="font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Day Movement</div>
      <div style="display:inline-block;padding:5px 12px;border-radius:8px;background:{{ $mvBg($grandMv) }};border:1px solid {{ $mvBd($grandMv) }};">
        <span style="font-size:18px;font-weight:900;color:{{ $mvColor($grandMv) }};font-family:'Courier New',ui-monospace,monospace;">{{ $fmtMv($grandMv) }}</span>
      </div>
      <div style="font-size:10px;color:#94A3B8;margin-top:5px;">{{ $fmtDate($start) }} → {{ $fmtDate($end) }}</div>
    </td>
    <td style="padding:18px 22px;vertical-align:top;width:50%;" bgcolor="#F8FAFC">
      <div style="font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Direction</div>
      <div style="font-size:18px;font-weight:900;color:{{ $mvColor($grandMv) }};">
        @if($grandDir === 'GAIN') ▲ Book Grew
        @elseif($grandDir === 'LOSS') ▼ Book Shrank
        @else — Flat
        @endif
      </div>
      <div style="font-size:10px;color:#94A3B8;margin-top:5px;">net change across all segments (performing)</div>
    </td>
  </tr>
</table>
{{-- ═══════════ END KPI STRIP ═══════════ --}}

{{-- ═══════════ SEGMENT OVERVIEW ═══════════ --}}
<div style="padding:26px 28px 10px;">

  <table cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
    <tr>
      <td style="padding-right:10px;vertical-align:middle;">
        <div style="width:4px;height:18px;background:linear-gradient(180deg,#00C875 0%,#007A59 100%);border-radius:2px;"></div>
      </td>
      <td style="vertical-align:middle;">
        <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">Segment Overview</span>
        <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· KES equivalent</span>
      </td>
    </tr>
  </table>

  @if(empty($segments))
    <p style="font-size:13px;color:#94A3B8;margin:0 0 20px;">No loan data for this period.</p>
  @else

  <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;border-collapse:collapse;margin-bottom:20px;">

    {{-- Column headers --}}
    <tr style="background:#1E293B;">
      <td style="padding:11px 14px;font-size:10px;font-weight:700;color:rgba(255,255,255,0.55);text-transform:uppercase;letter-spacing:1px;">Segment</td>
      <td style="padding:11px 14px;font-size:10px;font-weight:700;color:rgba(255,255,255,0.55);text-transform:uppercase;letter-spacing:1px;text-align:right;">{{ $shortDt($start) }}</td>
      <td style="padding:11px 14px;font-size:10px;font-weight:700;color:rgba(255,255,255,0.55);text-transform:uppercase;letter-spacing:1px;text-align:right;">{{ $shortDt($end) }}</td>
      <td style="padding:11px 14px;font-size:10px;font-weight:700;color:#F59E0B;text-transform:uppercase;letter-spacing:1px;text-align:right;">LCY MV</td>
      <td style="padding:11px 14px;font-size:10px;font-weight:700;color:#60A5FA;text-transform:uppercase;letter-spacing:1px;text-align:right;">FCY MV</td>
      <td style="padding:11px 14px;font-size:10px;font-weight:700;color:rgba(255,255,255,0.55);text-transform:uppercase;letter-spacing:1px;text-align:right;">Net Movement</td>
    </tr>

    @foreach($segments as $i => $seg)
      @php
        $rowBg  = $i % 2 === 0 ? '#FFFFFF' : '#F8FAFC';
        $lcyMv  = $seg['lcyMovement'] ?? 0;
        $fcyMv  = $seg['fcyMovement'] ?? 0;
        $netMv  = $seg['movement']    ?? 0;
      @endphp
      <tr style="background:{{ $rowBg }};">
        <td style="padding:11px 14px;font-size:13px;font-weight:700;color:#0F172A;border-bottom:1px solid #F1F5F9;">
          {{ $seg['name'] }}
        </td>
        <td style="padding:11px 14px;font-size:11.5px;color:#64748B;text-align:right;font-family:'Courier New',ui-monospace,monospace;border-bottom:1px solid #F1F5F9;white-space:nowrap;">
          {{ $fmtFull($seg['startBalance']) }}
        </td>
        <td style="padding:11px 14px;font-size:11.5px;font-weight:700;color:#0F172A;text-align:right;font-family:'Courier New',ui-monospace,monospace;border-bottom:1px solid #F1F5F9;white-space:nowrap;">
          {{ $fmtFull($seg['endBalance']) }}
        </td>
        <td style="padding:11px 14px;font-size:11.5px;font-weight:600;text-align:right;font-family:'Courier New',ui-monospace,monospace;border-bottom:1px solid #F1F5F9;white-space:nowrap;color:{{ $mvColor($lcyMv) }};">
          {{ $fmtMvFull($lcyMv) }}
        </td>
        <td style="padding:11px 14px;font-size:11.5px;font-weight:600;text-align:right;font-family:'Courier New',ui-monospace,monospace;border-bottom:1px solid #F1F5F9;white-space:nowrap;color:{{ $mvColor($fcyMv) }};">
          {{ $fmtMvFull($fcyMv) }}
        </td>
        <td style="padding:11px 14px;text-align:right;border-bottom:1px solid #F1F5F9;white-space:nowrap;">
          <span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;color:{{ $mvColor($netMv) }};background:{{ $mvBg($netMv) }};border:1px solid {{ $mvBd($netMv) }};font-family:'Courier New',ui-monospace,monospace;">
            {{ $dirIcon($seg['direction']) }} {{ $fmtMvFull($netMv) }}
          </span>
        </td>
      </tr>
    @endforeach

    {{-- Totals row --}}
    @php
      $tLcy = $totals['lcyMovement'] ?? 0;
      $tFcy = $totals['fcyMovement'] ?? 0;
    @endphp
    <tr style="background:linear-gradient(135deg,#003D2E 0%,#005C43 100%);">
      <td style="padding:12px 14px;font-size:13px;font-weight:900;color:#fff;">Totals</td>
      <td style="padding:12px 14px;font-size:11.5px;font-weight:700;color:rgba(255,255,255,0.65);text-align:right;font-family:'Courier New',ui-monospace,monospace;white-space:nowrap;">
        {{ $fmtFull($totals['startBalance'] ?? 0) }}
      </td>
      <td style="padding:12px 14px;font-size:11.5px;font-weight:900;color:#fff;text-align:right;font-family:'Courier New',ui-monospace,monospace;white-space:nowrap;">
        {{ $fmtFull($totals['endBalance'] ?? 0) }}
      </td>
      <td style="padding:12px 14px;font-size:11.5px;font-weight:700;text-align:right;font-family:'Courier New',ui-monospace,monospace;white-space:nowrap;color:{{ $mvLight($tLcy) }};">
        {{ $fmtMvFull($tLcy) }}
      </td>
      <td style="padding:12px 14px;font-size:11.5px;font-weight:700;text-align:right;font-family:'Courier New',ui-monospace,monospace;white-space:nowrap;color:{{ $mvLight($tFcy) }};">
        {{ $fmtMvFull($tFcy) }}
      </td>
      <td style="padding:12px 14px;text-align:right;white-space:nowrap;">
        <span style="font-size:12px;font-weight:900;color:{{ $mvLight($grandMv) }};font-family:'Courier New',ui-monospace,monospace;">
          {{ $dirIcon($grandDir) }} {{ $fmtMvFull($grandMv) }}
        </span>
      </td>
    </tr>

  </table>
  @endif

</div>
{{-- ═══════════ END SEGMENT OVERVIEW ═══════════ --}}

{{-- ═══════════ MOVERS ═══════════ --}}
@if(!empty($gainers) || !empty($losers))
<div style="padding:0 28px 28px;">

  <div style="border-top:1px solid #E2E8F0;margin-bottom:22px;"></div>

  <table cellpadding="0" cellspacing="0" style="margin-bottom:18px;">
    <tr>
      <td style="padding-right:10px;vertical-align:middle;">
        <div style="width:4px;height:18px;background:linear-gradient(180deg,#00C875 0%,#007A59 100%);border-radius:2px;"></div>
      </td>
      <td style="vertical-align:middle;">
        <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">Top Movers by CIF</span>
        <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· largest day-on-day changes</span>
      </td>
    </tr>
  </table>

  {{-- Increased --}}
  @if(!empty($gainers))
  <div style="margin-bottom:22px;">
    <div style="font-size:11px;font-weight:700;color:#15803D;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;padding:6px 12px;background:#F0FDF4;border-radius:6px;display:inline-block;border:1px solid #BBF7D0;">
      ▲ &nbsp;Top {{ count($gainers) }} Increased
    </div>
    <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;border-collapse:collapse;">
      <tr style="background:#F8FAFC;">
        <td style="padding:8px 12px;font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #E2E8F0;width:24px;">#</td>
        <td style="padding:8px 12px;font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #E2E8F0;">CIF / Name</td>
        <td style="padding:8px 12px;font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.8px;text-align:right;border-bottom:1px solid #E2E8F0;">Prior</td>
        <td style="padding:8px 12px;font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.8px;text-align:right;border-bottom:1px solid #E2E8F0;">Current</td>
        <td style="padding:8px 12px;font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.8px;text-align:right;border-bottom:1px solid #E2E8F0;">Movement</td>
      </tr>
      @foreach($gainers as $i => $g)
        @php $g = (array)$g; $rowBg = $i % 2 === 0 ? '#FFFFFF' : '#F8FAFC'; @endphp
        <tr style="background:{{ $rowBg }};">
          <td style="padding:9px 12px;font-size:11px;font-weight:700;color:#94A3B8;border-bottom:1px solid #F1F5F9;">{{ $i + 1 }}</td>
          <td style="padding:9px 12px;border-bottom:1px solid #F1F5F9;">
            <div style="font-size:11px;font-weight:700;color:#0F172A;font-family:'Courier New',ui-monospace,monospace;">{{ $g['cif'] ?? '—' }}</div>
            <div style="font-size:10.5px;color:#64748B;margin-top:1px;">{{ $g['name'] ?? '' }}</div>
          </td>
          <td style="padding:9px 12px;font-size:11.5px;color:#64748B;text-align:right;font-family:'Courier New',ui-monospace,monospace;border-bottom:1px solid #F1F5F9;white-space:nowrap;">{{ $fmtFull($g['start_balance'] ?? 0) }}</td>
          <td style="padding:9px 12px;font-size:11.5px;font-weight:700;color:#0F172A;text-align:right;font-family:'Courier New',ui-monospace,monospace;border-bottom:1px solid #F1F5F9;white-space:nowrap;">{{ $fmtFull($g['end_balance'] ?? 0) }}</td>
          <td style="padding:9px 12px;text-align:right;border-bottom:1px solid #F1F5F9;white-space:nowrap;">
            <span style="font-size:11.5px;font-weight:700;color:#15803D;font-family:'Courier New',ui-monospace,monospace;">{{ $fmtMvFull($g['movement'] ?? 0) }}</span>
          </td>
        </tr>
      @endforeach
    </table>
  </div>
  @endif

  {{-- Decreased --}}
  @if(!empty($losers))
  <div>
    <div style="font-size:11px;font-weight:700;color:#BE123C;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;padding:6px 12px;background:#FFF1F2;border-radius:6px;display:inline-block;border:1px solid #FECDD3;">
      ▼ &nbsp;Top {{ count($losers) }} Decreased
    </div>
    <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;border-collapse:collapse;">
      <tr style="background:#F8FAFC;">
        <td style="padding:8px 12px;font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #E2E8F0;width:24px;">#</td>
        <td style="padding:8px 12px;font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #E2E8F0;">CIF / Name</td>
        <td style="padding:8px 12px;font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.8px;text-align:right;border-bottom:1px solid #E2E8F0;">Prior</td>
        <td style="padding:8px 12px;font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.8px;text-align:right;border-bottom:1px solid #E2E8F0;">Current</td>
        <td style="padding:8px 12px;font-size:9.5px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.8px;text-align:right;border-bottom:1px solid #E2E8F0;">Movement</td>
      </tr>
      @foreach($losers as $i => $l)
        @php $l = (array)$l; $rowBg = $i % 2 === 0 ? '#FFFFFF' : '#F8FAFC'; @endphp
        <tr style="background:{{ $rowBg }};">
          <td style="padding:9px 12px;font-size:11px;font-weight:700;color:#94A3B8;border-bottom:1px solid #F1F5F9;">{{ $i + 1 }}</td>
          <td style="padding:9px 12px;border-bottom:1px solid #F1F5F9;">
            <div style="font-size:11px;font-weight:700;color:#0F172A;font-family:'Courier New',ui-monospace,monospace;">{{ $l['cif'] ?? '—' }}</div>
            <div style="font-size:10.5px;color:#64748B;margin-top:1px;">{{ $l['name'] ?? '' }}</div>
          </td>
          <td style="padding:9px 12px;font-size:11.5px;color:#64748B;text-align:right;font-family:'Courier New',ui-monospace,monospace;border-bottom:1px solid #F1F5F9;white-space:nowrap;">{{ $fmtFull($l['start_balance'] ?? 0) }}</td>
          <td style="padding:9px 12px;font-size:11.5px;font-weight:700;color:#0F172A;text-align:right;font-family:'Courier New',ui-monospace,monospace;border-bottom:1px solid #F1F5F9;white-space:nowrap;">{{ $fmtFull($l['end_balance'] ?? 0) }}</td>
          <td style="padding:9px 12px;text-align:right;border-bottom:1px solid #F1F5F9;white-space:nowrap;">
            <span style="font-size:11.5px;font-weight:700;color:#BE123C;font-family:'Courier New',ui-monospace,monospace;">{{ $fmtMvFull($l['movement'] ?? 0) }}</span>
          </td>
        </tr>
      @endforeach
    </table>
  </div>
  @endif

</div>
@endif
{{-- ═══════════ END MOVERS ═══════════ --}}

{{-- ═══════════ FOOTER ═══════════ --}}
<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-top:1px solid #E2E8F0;" bgcolor="#F8FAFC">
  <tr>
    <td style="padding:14px 28px;background:#F8FAFC;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="vertical-align:middle;">
            <span style="font-size:11px;color:#94A3B8;">
              <strong style="color:#334155;font-weight:800;font-size:12px;">Ecobank Kenya</strong>
              <span style="color:#CBD5E1;margin:0 6px;">·</span>
              <span>Finance · Daily Loan Book Movement</span>
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
{{-- ═══════════ END FOOTER ═══════════ --}}

</div>
</div>
</body>
</html>
