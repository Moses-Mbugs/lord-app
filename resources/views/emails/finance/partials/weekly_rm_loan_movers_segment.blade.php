{{--  resources/views/emails/finance/partials/weekly_rm_loan_movers_segment.blade.php
      One segment's (Premier/Advantage/Direct) RM loan table + top movers, for the Weekly
      RM Loan Movers email. Expects: $segment (name), $data (['week'|'mtd'|'ytd' => [...]]),
      $weekEnd, $weekStart, $fmtDate  --}}
@php
    $weekData = $data['week'] ?? ['summary' => collect(), 'all' => null, 'topGainers' => collect(), 'topLosers' => collect()];
    $mtdData  = $data['mtd']  ?? ['summary' => collect(), 'all' => null];
    $ytdData  = $data['ytd']  ?? ['summary' => collect(), 'all' => null];

    $weekAll = $weekData['all'] ?? null;
    $mtdAll  = $mtdData['all']  ?? null;
    $ytdAll  = $ytdData['all']  ?? null;

    $emptyRmRow = fn($code, $name) => [
        'code' => $code, 'name' => $name,
        'accounts' => 0, 'customers' => 0,
        'loan_week' => 0, 'loan_mtd' => 0, 'loan_ytd' => 0, 'loan_balance' => 0,
    ];

    $rmMap = [];
    foreach (['week' => $weekData, 'mtd' => $mtdData, 'ytd' => $ytdData] as $key => $periodData) {
        foreach (collect($periodData['summary'] ?? []) as $r) {
            $code = (string) ($r->rm_code ?? '');
            if ($code === '') continue;
            if (!isset($rmMap[$code])) {
                $rmMap[$code] = $emptyRmRow($code, (string) ($r->rm_name ?? $code));
            }
            $rmMap[$code]['name']        = (string) ($r->rm_name ?? $rmMap[$code]['name']);
            $rmMap[$code]["loan_{$key}"] = (float) ($r->loan_movement ?? 0);
            if ($key === 'week') {
                $rmMap[$code]['accounts']     = (int) ($r->account_count  ?? 0);
                $rmMap[$code]['customers']    = (int) ($r->customer_count ?? 0);
                $rmMap[$code]['loan_balance'] = (float) ($r->loan_close   ?? 0);
            }
        }
    }

    $rmMap['ALL'] = $emptyRmRow('ALL', 'Total');
    if ($weekAll) {
        $rmMap['ALL']['accounts']     = (int) ($weekAll->account_count  ?? 0);
        $rmMap['ALL']['customers']    = (int) ($weekAll->customer_count ?? 0);
        $rmMap['ALL']['loan_week']    = (float) ($weekAll->loan_movement ?? 0);
        $rmMap['ALL']['loan_balance'] = (float) ($weekAll->loan_close    ?? 0);
    }
    if ($mtdAll) {
        $rmMap['ALL']['loan_mtd'] = (float) ($mtdAll->loan_movement ?? 0);
    }
    if ($ytdAll) {
        $rmMap['ALL']['loan_ytd'] = (float) ($ytdAll->loan_movement ?? 0);
    }

    // Rank by WTD loan movement (best performer first), Total pinned last.
    uasort($rmMap, function($a, $b) {
        if ($a['code'] === 'ALL') return 1;
        if ($b['code'] === 'ALL') return -1;
        return $b['loan_week'] <=> $a['loan_week'];
    });

    $topGainers = ($weekData['topGainers'] ?? collect())->take(5);
    $topLosers  = ($weekData['topLosers']  ?? collect())->take(5);
@endphp

{{-- SEGMENT BANNER --}}
<div style="margin:0 0 14px;padding:8px 14px;background:linear-gradient(90deg,#166534 0%,#15803D 100%);border-radius:8px;">
  <span style="font-size:14px;font-weight:900;color:#ffffff;letter-spacing:0.2px;">{{ $segment }}</span>
  <span style="font-size:11px;font-weight:700;color:#DCFCE7;margin-left:8px;">{{ count($rmMap) - 1 }} RM{{ (count($rmMap) - 1) === 1 ? '' : 's' }}</span>
</div>

<table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;">
  <tr>
    <td style="padding-right:10px;vertical-align:middle;">
      <div style="width:4px;height:18px;background:linear-gradient(180deg,#00B4D8 0%,#0077B6 100%);border-radius:2px;"></div>
    </td>
    <td style="vertical-align:middle;">
      <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">RM Loan Summary — Ranked by Weekly Performance</span>
      <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· KES Equivalent</span>
    </td>
  </tr>
</table>

<div style="overflow-x:auto;">
<table width="100%" cellpadding="0" cellspacing="0"
  style="width:100%;min-width:820px;border-collapse:separate;border-spacing:0;font-size:11px;border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">
  <thead>
    <tr>
      <th style="padding:7px 10px;background:#F1F5F9;border-bottom:2px solid #CBD5E1;text-align:center;font-size:9px;font-weight:900;color:#475569;text-transform:uppercase;letter-spacing:0.7px;white-space:nowrap;border-right:1px solid #CBD5E1;width:5%;">Rank</th>
      <th style="padding:7px 10px;background:#F1F5F9;border-bottom:2px solid #CBD5E1;text-align:left;font-size:9px;font-weight:900;color:#475569;text-transform:uppercase;letter-spacing:0.7px;white-space:nowrap;border-right:1px solid #CBD5E1;width:20%;">RM</th>
      <th style="padding:7px 10px;background:#F1F5F9;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#475569;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;width:10%;">Accounts</th>
      <th style="padding:7px 10px;background:#F1F5F9;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#475569;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;width:10%;border-right:2px solid #CBD5E1;">Customers</th>
      <th style="padding:7px 10px;background:#F0FDF4;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">WTD Δ</th>
      <th style="padding:7px 10px;background:#F0FDF4;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">MTD Δ</th>
      <th style="padding:7px 10px;background:#F0FDF4;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">YTD Δ</th>
      <th style="padding:7px 10px;background:#F0FDF4;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">Closing Bal</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($rmMap as $rCode => $r)
      @php
        $isTotal  = $rCode === 'ALL';
        $isEven   = $loop->iteration % 2 === 0;
        $rowBg    = $isTotal ? '#F1F5F9' : ($isEven ? '#F8FAFC' : '#ffffff');
        $isLast   = $loop->last;
        $border   = $isLast ? 'none' : '1px solid #E2E8F0';

        $loanWk  = (float) ($r['loan_week']    ?? 0);
        $loanMtd = (float) ($r['loan_mtd']     ?? 0);
        $loanYtd = (float) ($r['loan_ytd']     ?? 0);
        $loanBal = (float) ($r['loan_balance'] ?? 0);

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
            ? 'display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:#bbf7d0;color:#14532d;border:1px solid #86efac;'
            : 'display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:#fecaca;color:#7f1d1d;border:1px solid #fca5a5;';
      @endphp
      <tr style="background:{{ $rowBg }};">
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:center;border-right:1px solid #E2E8F0;">
          @if ($isTotal)
            <span style="color:#94A3B8;">—</span>
          @else
            @php $medal = ['#F59E0B', '#94A3B8', '#B45309'][$loop->iteration - 1] ?? null; @endphp
            <span style="display:inline-block;min-width:20px;padding:2px 6px;border-radius:999px;font-weight:900;font-size:10.5px;background:{{ $medal ?: '#F1F5F9' }};color:{{ $medal ? '#ffffff' : '#475569' }};">
              {{ $loop->iteration }}
            </span>
          @endif
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};border-right:1px solid #E2E8F0;">
          <span title="{{ $r['name'] }}" style="display:inline-block;padding:2px 8px;border-radius:999px;max-width:170px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;vertical-align:bottom;
            background:{{ $isTotal ? '#E2E8F0' : '#F0FDF4' }};
            border:1px solid {{ $isTotal ? '#CBD5E1' : '#BBF7D0' }};
            color:{{ $isTotal ? '#334155' : '#166534' }};
            font-weight:900;font-size:10px;letter-spacing:0.3px;{{ $isTotal ? 'text-transform:uppercase;' : '' }}">
            {{ $isTotal ? 'TOTAL' : $r['name'] }}
          </span>
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;font-weight:800;color:#4b5563;">{{ number_format((int) ($r['accounts'] ?? 0)) }}</td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;font-weight:800;color:#4b5563;border-right:2px solid #E2E8F0;">{{ number_format((int) ($r['customers'] ?? 0)) }}</td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;"><span style="{{ $mvStyle($loanWk) }}">{{ $fmtMv($loanWk) }}</span></td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;"><span style="{{ $mvStyle($loanMtd) }}">{{ $fmtMv($loanMtd) }}</span></td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;"><span style="{{ $mvStyle($loanYtd) }}">{{ $fmtMv($loanYtd) }}</span></td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;font-family:ui-monospace,'Courier New',monospace;font-weight:700;color:#374151;">{{ $fmtBal($loanBal) }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
</div>

@if ($topGainers->isNotEmpty() || $topLosers->isNotEmpty())
<div style="margin-top:32px;">
  <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;">
    <tr>
      <td style="padding-right:10px;vertical-align:middle;">
        <div style="width:4px;height:18px;background:linear-gradient(180deg,#F59E0B 0%,#B45309 100%);border-radius:2px;"></div>
      </td>
      <td style="vertical-align:middle;">
        <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">Top Weekly Loan Movers</span>
        <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· {{ $segment }}</span>
      </td>
    </tr>
  </table>

  @include('emails.finance.partials.loan_movers_side_by_side', [
      'topGainers' => $topGainers,
      'topLosers'  => $topLosers,
  ])
</div>
@endif
