{{--
  Parameters:
  $segments     – [{code, name, month_mv, ytd_mv, balance, sub_segments: [{name, month_mv, ytd_mv, balance}]}]
  $periods      – {month_start, month_end, ytd_start}
  $balanceLabel – e.g. 'Deposits' / 'Loans'
  $theme        – product colour set from monthly_performance_report: {head, headLine, headText, headSub, name, rowBg, subBg, dot, totalBg}
  $showYtd      – bool, default true
--}}
@php
    use Carbon\Carbon;

    $showYtd  = $showYtd ?? true;
    $cols     = $showYtd ? 5 : 4;
    $fmtShort = fn($d) => $d ? Carbon::parse($d)->format('d M') : '—';
    $fmtFull  = fn($v) => number_format((int) round(abs((float) $v)));

    $mvCell = function($v, $compact = false) {
        $n   = (float) $v;
        $abs = number_format((int) round(abs($n)));
        $fs  = $compact ? '10.5px' : '11.5px';
        if ($n > 0) return "<span style=\"font-size:{$fs};font-weight:700;color:#15803D;font-family:'Courier New',ui-monospace,monospace;\">+{$abs}</span>";
        if ($n < 0) return "<span style=\"font-size:{$fs};font-weight:700;color:#BE123C;font-family:'Courier New',ui-monospace,monospace;\">−{$abs}</span>";
        return "<span style=\"font-size:{$fs};font-weight:700;color:#94A3B8;font-family:'Courier New',ui-monospace,monospace;\">—</span>";
    };

    // Month Δ as a % of the opening balance (closing − month movement)
    $pctCell = function($mv, $balance, $compact = false) {
        $opening = (float) $balance - (float) $mv;
        $fs = $compact ? '10px' : '11px';
        if ($opening <= 0) return "<span style=\"font-size:{$fs};color:#94A3B8;\">—</span>";
        $pct   = (float) $mv / $opening * 100;
        $color = $pct > 0 ? '#15803D' : ($pct < 0 ? '#BE123C' : '#94A3B8');
        $sign  = $pct > 0 ? '+' : ($pct < 0 ? '−' : '');
        return "<span style=\"font-size:{$fs};font-weight:600;color:{$color};\">{$sign}" . number_format(abs($pct), 1) . "%</span>";
    };

    $th    = "padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:{$theme['headText']};text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid {$theme['headLine']};white-space:nowrap;";
    $thSub = "font-size:8.5px;font-weight:500;color:{$theme['headSub']};letter-spacing:0;text-transform:none;";
@endphp

<div style="border-radius:12px;overflow:hidden;border:1px solid #E2E8F0;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
<table width="100%" cellpadding="0" cellspacing="0"
    style="width:100%;border-collapse:collapse;font-size:12px;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">
  <thead>
    <tr bgcolor="{{ $theme['head'] }}" style="background:{{ $theme['head'] }};">
      <th style="padding:11px 16px 11px 14px;text-align:left;font-size:10px;font-weight:700;color:{{ $theme['headText'] }};text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid {{ $theme['headLine'] }};width:34%;">Segment</th>
      <th style="{{ $th }}">Month Δ<br><span style="{{ $thSub }}">{{ $fmtShort($periods['month_start'] ?? null) }} → {{ $fmtShort($periods['month_end'] ?? null) }}</span></th>
      <th style="{{ $th }}">MoM %</th>
      @if ($showYtd)
        <th style="{{ $th }}">YTD Δ<br><span style="{{ $thSub }}">from {{ $fmtShort($periods['ytd_start'] ?? null) }}</span></th>
      @endif
      <th style="{{ $th }}padding-right:16px;">{{ $balanceLabel }}<br><span style="{{ $thSub }}">as at {{ $fmtShort($periods['month_end'] ?? null) }}</span></th>
    </tr>
  </thead>
  <tbody>
    @forelse ($segments as $seg)
      @php
        $isTotal   = ($seg['code'] ?? '') === 'ALL';
        $subs      = $seg['sub_segments'] ?? [];
        $rowBg     = $isTotal ? $theme['totalBg'] : $theme['rowBg'];
        $nameColor = $isTotal ? '#0F172A' : $theme['name'];
        $borderTop = $isTotal ? 'border-top:2px solid #CBD5E1;' : 'border-top:1px solid #E8ECF1;';
      @endphp
      <tr style="background:{{ $rowBg }};">
        <td style="padding:11px 14px;{{ $borderTop }}">
          <span style="font-size:12.5px;font-weight:800;color:{{ $nameColor }};letter-spacing:0.1px;">{{ strtoupper($seg['name'] ?? '') }}</span>
          @if(!$isTotal && count($subs))
            <span style="font-size:9.5px;font-weight:600;color:#94A3B8;margin-left:6px;vertical-align:middle;">{{ count($subs) }} sub-segments</span>
          @endif
        </td>
        <td style="padding:11px 14px;text-align:right;{{ $borderTop }}">{!! $mvCell($seg['month_mv'] ?? 0) !!}</td>
        <td style="padding:11px 14px;text-align:right;{{ $borderTop }}">{!! $pctCell($seg['month_mv'] ?? 0, $seg['balance'] ?? 0) !!}</td>
        @if ($showYtd)
          <td style="padding:11px 14px;text-align:right;{{ $borderTop }}">{!! $mvCell($seg['ytd_mv'] ?? 0) !!}</td>
        @endif
        <td style="padding:11px 16px 11px 14px;text-align:right;{{ $borderTop }}font-family:'Courier New',ui-monospace,monospace;font-size:12px;font-weight:700;color:{{ $nameColor }};">{{ $fmtFull($seg['balance'] ?? 0) }}</td>
      </tr>

      @foreach ($subs as $sub)
        @php $subBorder = $loop->last ? 'border-bottom:1px solid #E8ECF1;' : 'border-bottom:1px solid #F1F5F9;'; @endphp
        <tr style="background:{{ $theme['subBg'] }};">
          <td style="padding:6px 14px 6px 24px;{{ $subBorder }}">
            <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">
              <tr>
                <td style="padding-right:8px;vertical-align:middle;"><div style="width:5px;height:5px;border-radius:50%;background:{{ $theme['dot'] }};"></div></td>
                <td style="vertical-align:middle;"><span style="font-size:11px;color:#475569;font-weight:500;">{{ $sub['name'] ?? '' }}</span></td>
              </tr>
            </table>
          </td>
          <td style="padding:6px 14px;text-align:right;{{ $subBorder }}">{!! $mvCell($sub['month_mv'] ?? 0, true) !!}</td>
          <td style="padding:6px 14px;text-align:right;{{ $subBorder }}">{!! $pctCell($sub['month_mv'] ?? 0, $sub['balance'] ?? 0, true) !!}</td>
          @if ($showYtd)
            <td style="padding:6px 14px;text-align:right;{{ $subBorder }}">{!! $mvCell($sub['ytd_mv'] ?? 0, true) !!}</td>
          @endif
          <td style="padding:6px 16px 6px 14px;text-align:right;{{ $subBorder }}font-family:'Courier New',ui-monospace,monospace;font-size:11px;color:#64748B;font-weight:500;">{{ $fmtFull($sub['balance'] ?? 0) }}</td>
        </tr>
      @endforeach
    @empty
      <tr><td colspan="{{ $cols }}" style="padding:28px;text-align:center;color:#94A3B8;font-size:12px;">No segment data available for this period.</td></tr>
    @endforelse
  </tbody>
</table>
</div>
