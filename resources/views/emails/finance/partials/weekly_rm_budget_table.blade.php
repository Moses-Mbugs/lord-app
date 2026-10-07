{{--  resources/views/emails/finance/partials/weekly_rm_budget_table.blade.php
      Budget vs Actual (FY target) for one segment. Expects: $segment, $rows (collection
      of ['rm_code','rm_name','deposit_target','deposit_actual','deposit_pct','ntb_target',
      'ntb_actual','ntb_pct']), $totals (same shape), $targetYear  --}}
@php
    $rows = $rows ?? collect();
    $segColor = \App\Services\Reports\RmPortfolioService::segmentColor($segment);

    $fmtAbs = function ($v) {
        $n = abs((float) $v);
        if ($n >= 1_000_000_000) return number_format($n / 1_000_000_000, 2) . 'B';
        if ($n >= 1_000_000)     return number_format($n / 1_000_000, 2)     . 'M';
        if ($n >= 1_000)         return number_format($n / 1_000, 1)          . 'K';
        return number_format((int) $n);
    };

    $pctStyle = function ($pct) {
        if ($pct === null) {
            return ['bg' => '#F1F5F9', 'fg' => '#64748B', 'text' => '—'];
        }
        if ($pct >= 100) return ['bg' => '#DCFCE7', 'fg' => '#166534', 'text' => number_format($pct, 0) . '%'];
        if ($pct >= 75)  return ['bg' => '#FFFBEB', 'fg' => '#92400E', 'text' => number_format($pct, 0) . '%'];
        return ['bg' => '#FEF2F2', 'fg' => '#991B1B', 'text' => number_format($pct, 0) . '%'];
    };
@endphp

<table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin:18px 0 10px;">
  <tr>
    <td style="padding-right:10px;vertical-align:middle;">
      <div style="width:4px;height:18px;background:linear-gradient(180deg,{{ $segColor['from'] }} 0%,{{ $segColor['to'] }} 100%);border-radius:2px;"></div>
    </td>
    <td style="vertical-align:middle;">
      <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">Budget vs Actual — FY{{ $targetYear }}</span>
      <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· Deposit closing balance &amp; NTB year-to-date vs target</span>
    </td>
  </tr>
</table>

<div style="overflow-x:auto;">
<table width="100%" cellpadding="0" cellspacing="0"
  style="width:100%;min-width:680px;border-collapse:separate;border-spacing:0;font-size:11px;border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">
  <thead>
    <tr>
      <th rowspan="2" style="padding:7px 10px;background:#F1F5F9;border-bottom:2px solid #CBD5E1;text-align:left;font-size:9px;font-weight:900;color:#475569;text-transform:uppercase;letter-spacing:0.7px;white-space:nowrap;border-right:1px solid #CBD5E1;width:22%;">RM</th>
      <th colspan="3" style="padding:6px 10px;background:#EFF6FF;border-bottom:1px solid #BFDBFE;text-align:center;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.7px;border-right:2px solid #BFDBFE;">Deposits</th>
      <th colspan="3" style="padding:6px 10px;background:#FFFBEB;border-bottom:1px solid #FDE68A;text-align:center;font-size:9px;font-weight:900;color:#B45309;text-transform:uppercase;letter-spacing:0.7px;">NTB</th>
    </tr>
    <tr>
      <th style="padding:6px 10px;background:#EFF6FF;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;border-left:1px solid #BFDBFE;">Target</th>
      <th style="padding:6px 10px;background:#EFF6FF;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">Actual</th>
      <th style="padding:6px 10px;background:#EFF6FF;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#1D4ED8;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;border-right:2px solid #BFDBFE;">%</th>
      <th style="padding:6px 10px;background:#FFFBEB;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#B45309;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;border-left:1px solid #FDE68A;">Target</th>
      <th style="padding:6px 10px;background:#FFFBEB;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#B45309;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">Actual</th>
      <th style="padding:6px 10px;background:#FFFBEB;border-bottom:2px solid #CBD5E1;text-align:right;font-size:9px;font-weight:900;color:#B45309;text-transform:uppercase;letter-spacing:0.6px;white-space:nowrap;">%</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($rows as $i => $r)
      @php
        $isEven = ($i + 1) % 2 === 0;
        $rowBg  = $isEven ? '#F8FAFC' : '#ffffff';
        $isLast = $loop->last && !$totals;
        $border = $isLast ? 'none' : '1px solid #E2E8F0';
        $depPct = $pctStyle($r->deposit_pct ?? null);
        $ntbPct = $pctStyle($r->ntb_pct ?? null);
      @endphp
      <tr style="background:{{ $rowBg }};">
        <td style="padding:7px 10px;border-bottom:{{ $border }};border-right:1px solid #E2E8F0;">
          <span title="{{ $r->rm_name }}" style="display:inline-block;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;vertical-align:bottom;font-weight:800;color:#2a2a2a;">{{ $r->rm_name }}</span>
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;border-left:1px solid #BFDBFE;font-family:ui-monospace,'Courier New',monospace;font-weight:700;color:#4b5563;">{{ $fmtAbs($r->deposit_target ?? 0) }}</td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;font-family:ui-monospace,'Courier New',monospace;font-weight:700;color:#4b5563;">{{ $fmtAbs($r->deposit_actual ?? 0) }}</td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;border-right:2px solid #BFDBFE;">
          <span style="display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:{{ $depPct['bg'] }};color:{{ $depPct['fg'] }};">{{ $depPct['text'] }}</span>
        </td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;border-left:1px solid #FDE68A;font-weight:700;color:#92400E;">{{ number_format((int) ($r->ntb_target ?? 0)) }}</td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;font-weight:700;color:#92400E;">{{ number_format((int) ($r->ntb_actual ?? 0)) }}</td>
        <td style="padding:7px 10px;border-bottom:{{ $border }};text-align:right;">
          <span style="display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:{{ $ntbPct['bg'] }};color:{{ $ntbPct['fg'] }};">{{ $ntbPct['text'] }}</span>
        </td>
      </tr>
    @endforeach

    @if ($totals)
      @php
        $tDepPct = $pctStyle($totals->deposit_pct ?? null);
        $tNtbPct = $pctStyle($totals->ntb_pct ?? null);
      @endphp
      <tr style="background:#ececec;">
        <td style="padding:7px 10px;font-weight:900;color:#2a2a2a;text-transform:uppercase;font-size:9.5px;">{{ $segment }} TOTAL</td>
        <td style="padding:7px 10px;text-align:right;border-left:1px solid #d4d4d4;font-family:ui-monospace,'Courier New',monospace;font-weight:900;color:#2a2a2a;">{{ $fmtAbs($totals->deposit_target ?? 0) }}</td>
        <td style="padding:7px 10px;text-align:right;font-family:ui-monospace,'Courier New',monospace;font-weight:900;color:#2a2a2a;">{{ $fmtAbs($totals->deposit_actual ?? 0) }}</td>
        <td style="padding:7px 10px;text-align:right;border-right:2px solid #d4d4d4;">
          <span style="display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:{{ $tDepPct['bg'] }};color:{{ $tDepPct['fg'] }};">{{ $tDepPct['text'] }}</span>
        </td>
        <td style="padding:7px 10px;text-align:right;font-weight:900;color:#2a2a2a;">{{ number_format((int) ($totals->ntb_target ?? 0)) }}</td>
        <td style="padding:7px 10px;text-align:right;font-weight:900;color:#2a2a2a;">{{ number_format((int) ($totals->ntb_actual ?? 0)) }}</td>
        <td style="padding:7px 10px;text-align:right;">
          <span style="display:inline-block;padding:3px 7px;border-radius:6px;font-weight:900;font-size:10.5px;white-space:nowrap;background:{{ $tNtbPct['bg'] }};color:{{ $tNtbPct['fg'] }};">{{ $tNtbPct['text'] }}</span>
        </td>
      </tr>
    @endif
  </tbody>
</table>
</div>
