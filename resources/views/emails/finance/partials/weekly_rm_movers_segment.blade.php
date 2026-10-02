{{--  resources/views/emails/finance/partials/weekly_rm_movers_segment.blade.php
      One segment's (Premier/Advantage/Direct) RM table, for the Weekly RM Movers email.
      Top movers are shown once, across all segments, below the last segment's table —
      see partials/weekly_top_deposit_movers.blade.php.
      Expects: $segment (name), $data (['week'|'mtd'|'ytd' => [...]]),
      $weekEnd, $weekStart, $mtdStart, $ytdStart, $fmtDate, $fmtShort  --}}
@php
    $weekData = $data['week'] ?? ['summary' => collect(), 'all' => null];
    $mtdData  = $data['mtd']  ?? ['summary' => collect(), 'all' => null];
    $ytdData  = $data['ytd']  ?? ['summary' => collect(), 'all' => null];

    $weekAll = $weekData['all'] ?? null;
    $mtdAll  = $mtdData['all']  ?? null;
    $ytdAll  = $ytdData['all']  ?? null;

    $emptyRmRow = fn($code, $name) => [
        'code' => $code, 'name' => $name,
        'dep_week' => 0, 'dep_mtd' => 0, 'dep_ytd' => 0, 'dep_balance' => 0,
        'loan_week' => 0, 'loan_mtd' => 0, 'loan_balance' => 0,
        'ntb_week' => 0, 'ntb_mtd' => 0, 'ntb_ytd' => 0,
    ];

    $rmMap = [];
    foreach ($weekData['summary'] as $r) {
        $code = (string) $r->rm_code;
        $rmMap[$code] = $emptyRmRow($code, (string) $r->rm_name);
        $rmMap[$code]['dep_week']    = (float) $r->movement;
        $rmMap[$code]['dep_balance'] = (float) $r->end_balance;
        $rmMap[$code]['loan_week']   = (float) $r->loan_movement;
        $rmMap[$code]['loan_balance']= (float) $r->loan_close;
        $rmMap[$code]['ntb_week']    = (int) $r->ntb_count;
    }
    foreach ($mtdData['summary'] as $r) {
        $code = (string) $r->rm_code;
        if (!isset($rmMap[$code])) {
            $rmMap[$code] = $emptyRmRow($code, (string) $r->rm_name);
        }
        $rmMap[$code]['dep_mtd']  = (float) $r->movement;
        $rmMap[$code]['loan_mtd'] = (float) $r->loan_movement;
        $rmMap[$code]['ntb_mtd']  = (int) $r->ntb_count;
    }
    foreach ($ytdData['summary'] as $r) {
        $code = (string) $r->rm_code;
        if (!isset($rmMap[$code])) {
            $rmMap[$code] = $emptyRmRow($code, (string) $r->rm_name);
        }
        $rmMap[$code]['dep_ytd'] = (float) $r->movement;
        $rmMap[$code]['ntb_ytd'] = (int) $r->ntb_count;
    }

    // Total row, built from each period's separately-tracked 'all' aggregate.
    $rmMap['ALL'] = $emptyRmRow('ALL', 'Total');
    if ($weekAll) {
        $rmMap['ALL']['dep_week']    = (float) ($weekAll->movement    ?? 0);
        $rmMap['ALL']['dep_balance'] = (float) ($weekAll->end_balance ?? 0);
        $rmMap['ALL']['loan_week']   = (float) ($weekAll->loan_movement ?? 0);
        $rmMap['ALL']['loan_balance']= (float) ($weekAll->loan_close    ?? 0);
        $rmMap['ALL']['ntb_week']    = (int) ($weekAll->ntb_count       ?? 0);
    }
    if ($mtdAll) {
        $rmMap['ALL']['dep_mtd']  = (float) ($mtdAll->movement      ?? 0);
        $rmMap['ALL']['loan_mtd'] = (float) ($mtdAll->loan_movement ?? 0);
        $rmMap['ALL']['ntb_mtd']  = (int) ($mtdAll->ntb_count       ?? 0);
    }
    if ($ytdAll) {
        $rmMap['ALL']['dep_ytd'] = (float) ($ytdAll->movement  ?? 0);
        $rmMap['ALL']['ntb_ytd'] = (int) ($ytdAll->ntb_count   ?? 0);
    }

    // Rank by WTD deposit movement (best performer first), Total pinned last.
    uasort($rmMap, function($a, $b) {
        if ($a['code'] === 'ALL') return 1;
        if ($b['code'] === 'ALL') return -1;
        return $b['dep_week'] <=> $a['dep_week'];
    });
@endphp

{{-- SEGMENT BANNER --}}
<div style="margin:0 0 14px;padding:8px 14px;background:linear-gradient(90deg,#005B82 0%,#0082BB 100%);border-radius:8px;">
  <span style="font-size:14px;font-weight:900;color:#ffffff;letter-spacing:0.2px;">{{ $segment }}</span>
  <span style="font-size:11px;font-weight:700;color:#D8E9F3;margin-left:8px;">{{ count($rmMap) - 1 }} RM{{ (count($rmMap) - 1) === 1 ? '' : 's' }}</span>
</div>

<table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;">
  <tr>
    <td style="padding-right:10px;vertical-align:middle;">
      <div style="width:4px;height:18px;background:linear-gradient(180deg,#00B4D8 0%,#0077B6 100%);border-radius:2px;"></div>
    </td>
    <td style="vertical-align:middle;">
      <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">RM Movement Summary — Ranked by Weekly Performance</span>
      <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· KES Equivalent</span>
    </td>
  </tr>
</table>

<div style="overflow-x:auto;">
<table width="100%" cellpadding="0" cellspacing="0"
  style="width:100%;min-width:940px;border-collapse:separate;border-spacing:0;font-size:11px;border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">
  <thead>
    <tr>
      <th rowspan="2"
        style="padding:7px 10px;background:#F1F5F9;border-bottom:2px solid #CBD5E1;text-align:center;font-size:9px;font-weight:900;color:#475569;text-transform:uppercase;letter-spacing:0.7px;white-space:nowrap;border-right:1px solid #CBD5E1;width:5%;">
        Rank
      </th>
      <th rowspan="2"
        style="padding:7px 10px;background:#F1F5F9;border-bottom:2px solid #CBD5E1;text-align:left;font-size:9px;font-weight:900;color:#475569;text-transform:uppercase;letter-spacing:0.7px;white-space:nowrap;border-right:1px solid #CBD5E1;width:18%;">
        RM
      </th>
      <th colspan="4"
        style="padding:6px 10px;background:#EFF6FF;border-bottom:1px solid #BFDBFE;text-align:center;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.7px;border-right:2px solid #BFDBFE;">
        Deposits
      </th>
      <th colspan="3"
        style="padding:6px 10px;background:#F0FDF4;border-bottom:1px solid #BBF7D0;text-align:center;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.7px;border-right:2px solid #BBF7D0;">
        Loans
      </th>
      <th colspan="3"
        style="padding:6px 10px;background:#FFFBEB;border-bottom:1px solid #FDE68A;text-align:center;font-size:9px;font-weight:900;color:#B45309;text-transform:uppercase;letter-spacing:0.7px;">
        NTB
      </th>
    </tr>
    <tr>
      <th style="padding:6px 10px;background:#EFF6FF;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;border-left:1px solid #BFDBFE;">
        WTD Δ
      </th>
      <th style="padding:6px 10px;background:#EFF6FF;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">
        MTD Δ
      </th>
      <th style="padding:6px 10px;background:#EFF6FF;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">
        YTD Δ
      </th>
      <th style="padding:6px 10px;background:#EFF6FF;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;border-right:2px solid #BFDBFE;">
        Closing Bal
      </th>
      <th style="padding:6px 10px;background:#F0FDF4;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;border-left:1px solid #BBF7D0;">
        WTD Δ
      </th>
      <th style="padding:6px 10px;background:#F0FDF4;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">
        MTD Δ
      </th>
      <th style="padding:6px 10px;background:#F0FDF4;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;border-right:2px solid #BBF7D0;">
        Closing Bal
      </th>
      <th style="padding:6px 10px;background:#FFFBEB;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#B45309;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;border-left:1px solid #FDE68A;">
        WTD
      </th>
      <th style="padding:6px 10px;background:#FFFBEB;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#B45309;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">
        MTD
      </th>
      <th style="padding:6px 10px;background:#FFFBEB;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#B45309;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">
        YTD
      </th>
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

        $depWk   = (float) ($r['dep_week']    ?? 0);
        $depMtd  = (float) ($r['dep_mtd']     ?? 0);
        $depYtd  = (float) ($r['dep_ytd']     ?? 0);
        $depBal  = (float) ($r['dep_balance'] ?? 0);
        $loanWk  = (float) ($r['loan_week']    ?? 0);
        $loanMtd = (float) ($r['loan_mtd']     ?? 0);
        $loanBal = (float) ($r['loan_balance'] ?? 0);
        $ntbWk  = (int) ($r['ntb_week'] ?? 0);
        $ntbMtd = (int) ($r['ntb_mtd']  ?? 0);
        $ntbYtd = (int) ($r['ntb_ytd']  ?? 0);

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
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:center;border-right:1px solid #E2E8F0;">
          @if ($isTotal)
            <span style="color:#94A3B8;">—</span>
          @else
            @php
              $medal = ['#F59E0B', '#94A3B8', '#B45309'][$loop->iteration - 1] ?? null;
            @endphp
            <span style="display:inline-block;min-width:20px;padding:2px 6px;border-radius:999px;font-weight:900;font-size:10.5px;
              background:{{ $medal ? $medal : '#F1F5F9' }};
              color:{{ $medal ? '#ffffff' : '#475569' }};">
              {{ $loop->iteration }}
            </span>
          @endif
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};border-right:1px solid #E2E8F0;">
          <span title="{{ $r['name'] }}" style="display:inline-block;padding:2px 8px;border-radius:999px;max-width:170px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;vertical-align:bottom;
            background:{{ $isTotal ? '#E2E8F0' : '#EFF6FF' }};
            border:1px solid {{ $isTotal ? '#CBD5E1' : '#BFDBFE' }};
            color:{{ $isTotal ? '#334155' : '#1D4ED8' }};
            font-weight:900;font-size:10px;letter-spacing:0.3px;{{ $isTotal ? 'text-transform:uppercase;' : '' }}">
            {{ $isTotal ? 'TOTAL' : $r['name'] }}
          </span>
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;border-left:1px solid #BFDBFE;">
          <span style="{{ $mvStyle($depWk) }}">{{ $fmtMv($depWk) }}</span>
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;">
          <span style="{{ $mvStyle($depMtd) }}">{{ $fmtMv($depMtd) }}</span>
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;">
          <span style="{{ $mvStyle($depYtd) }}">{{ $fmtMv($depYtd) }}</span>
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;border-right:2px solid #BFDBFE;font-family:ui-monospace,'Courier New',monospace;font-weight:700;color:#374151;">
          {{ $fmtBal($depBal) }}
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};background:{{ $isEven ? '#F0FDF4' : '#ECFDF5' }};text-align:right;">
          <span style="{{ $loanMvStyle($loanWk) }}">{{ $fmtMv($loanWk) }}</span>
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};background:{{ $isEven ? '#F0FDF4' : '#ECFDF5' }};text-align:right;">
          <span style="{{ $loanMvStyle($loanMtd) }}">{{ $fmtMv($loanMtd) }}</span>
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};background:{{ $isEven ? '#F0FDF4' : '#ECFDF5' }};text-align:right;border-right:2px solid #BBF7D0;font-family:ui-monospace,'Courier New',monospace;font-weight:700;color:#374151;">
          {{ $fmtBal($loanBal) }}
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};background:{{ $isEven ? '#FFFBEB' : '#FEFCE8' }};text-align:right;font-weight:700;font-family:ui-monospace,'Courier New',monospace;color:#92400E;">
          {{ number_format($ntbWk) }}
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};background:{{ $isEven ? '#FFFBEB' : '#FEFCE8' }};text-align:right;font-weight:700;font-family:ui-monospace,'Courier New',monospace;color:#92400E;">
          {{ number_format($ntbMtd) }}
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};background:{{ $isEven ? '#FFFBEB' : '#FEFCE8' }};text-align:right;font-weight:700;font-family:ui-monospace,'Courier New',monospace;color:#92400E;">
          {{ number_format($ntbYtd) }}
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
</div>
