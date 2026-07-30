{{--
  Parameters:
  $segments  – array from WeeklyLoanReportService
  $weekStart, $weekEnd, $mtdStart
--}}
@php
    use Carbon\Carbon;

    $fmtShort = fn($d) => $d ? Carbon::parse($d)->format('d M') : '—';

    $fmtFull  = fn($v) => number_format((int) round(abs((float) $v)));

    $mvCell = function($v, $compact = false) {
        $n   = (float) $v;
        $abs = number_format((int) round(abs($n)));
        $fs  = $compact ? '10.5px' : '11.5px';
        $fw  = '700';
        if ($n > 0) return "<span style=\"font-size:{$fs};font-weight:{$fw};color:#15803D;font-family:'Courier New',ui-monospace,monospace;\">+{$abs}</span>";
        if ($n < 0) return "<span style=\"font-size:{$fs};font-weight:{$fw};color:#BE123C;font-family:'Courier New',ui-monospace,monospace;\">−{$abs}</span>";
        return "<span style=\"font-size:{$fs};font-weight:{$fw};color:#94A3B8;font-family:'Courier New',ui-monospace,monospace;\">—</span>";
    };

    $palette = [
        'CORPORATE BANKING'  => ['accent' => '#1D4ED8', 'rowBg' => '#EFF6FF', 'subBg' => '#FAFCFF', 'nameTx' => '#1E40AF', 'dotBg' => '#BFDBFE'],
        'COMMERCIAL BANKING' => ['accent' => '#7C3AED', 'rowBg' => '#F5F3FF', 'subBg' => '#FDFCFF', 'nameTx' => '#5B21B6', 'dotBg' => '#DDD6FE'],
        'CONSUMER BANKING'   => ['accent' => '#D97706', 'rowBg' => '#FFFBEB', 'subBg' => '#FFFEF8', 'nameTx' => '#92400E', 'dotBg' => '#FDE68A'],
        'UNMAPPED'           => ['accent' => '#64748B', 'rowBg' => '#F8FAFC', 'subBg' => '#FAFBFC', 'nameTx' => '#334155', 'dotBg' => '#CBD5E1'],
        'ALL'                => ['accent' => '#0F172A', 'rowBg' => '#E8EDF2', 'subBg' => '#E8EDF2', 'nameTx' => '#0F172A', 'dotBg' => '#94A3B8'],
    ];
@endphp

<div style="border-radius:12px;overflow:hidden;border:1px solid #E2E8F0;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
<table width="100%" cellpadding="0" cellspacing="0"
    style="width:100%;border-collapse:collapse;font-size:12px;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">

  <thead>
    <tr bgcolor="#1B344F">
      <th style="width:4px;padding:0;background:#0F2744;border-bottom:1px solid #0F2744;"></th>
      <th style="padding:11px 16px 11px 12px;text-align:left;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;width:38%;">
        Segment</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:20%;">
        Weekly<br><span style="font-size:8.5px;font-weight:500;color:#475569;letter-spacing:0;text-transform:none;">{{ $fmtShort($weekStart) }} → {{ $fmtShort($weekEnd) }}</span></th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:20%;">
        MTD<br><span style="font-size:8.5px;font-weight:500;color:#475569;letter-spacing:0;text-transform:none;">from {{ $fmtShort($mtdStart) }}</span></th>
      <th style="padding:11px 16px 11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:22%;">
        Loans<br><span style="font-size:8.5px;font-weight:500;color:#475569;letter-spacing:0;text-transform:none;">as at {{ $fmtShort($weekEnd) }}</span></th>
    </tr>
  </thead>

  <tbody>
    @forelse ($segments as $seg)
      @php
        $code        = $seg['code'] ?? 'UNMAPPED';
        $isTotal     = ($code === 'ALL');
        $p           = $palette[$code] ?? $palette['UNMAPPED'];
        $visibleSubs = array_values(array_filter($seg['sub_segments'] ?? [], fn($s) => ($s['name'] ?? '') !== 'Unmapped'));
        $hasSubs     = !empty($visibleSubs);
        $segBorderTop = $isTotal ? 'border-top:2px solid #CBD5E1;' : 'border-top:1px solid #E8ECF1;';
      @endphp

      <tr style="background:{{ $p['rowBg'] }};">
        <td style="width:4px;padding:0;background:{{ $p['accent'] }};{{ $segBorderTop }}"></td>
        <td style="padding:11px 14px 11px 14px;{{ $segBorderTop }}">
          <span style="font-size:12.5px;font-weight:800;color:{{ $p['nameTx'] }};letter-spacing:0.1px;">
            {{ strtoupper($seg['name'] ?? $code) }}
          </span>
          @if(!$isTotal && $hasSubs)
            <span style="font-size:9.5px;font-weight:600;color:#94A3B8;margin-left:6px;vertical-align:middle;">
              {{ count($visibleSubs) }} sub-segments
            </span>
          @endif
        </td>
        <td style="padding:11px 14px;text-align:right;{{ $segBorderTop }}">{!! $mvCell($seg['weekly_mv'] ?? 0) !!}</td>
        <td style="padding:11px 14px;text-align:right;{{ $segBorderTop }}">{!! $mvCell($seg['mtd_mv'] ?? 0) !!}</td>
        <td style="padding:11px 16px 11px 14px;text-align:right;{{ $segBorderTop }};font-family:'Courier New',ui-monospace,monospace;font-size:12px;font-weight:700;color:{{ $p['nameTx'] }};">
          {{ $fmtFull($seg['total_loans'] ?? 0) }}
        </td>
      </tr>

      @foreach ($seg['sub_segments'] ?? [] as $si => $sub)
        @php
          if (($sub['name'] ?? '') === 'Unmapped') continue;
          $isLastSub = $loop->last;
          $subBorder = $isLastSub ? 'border-bottom:1px solid #E8ECF1;' : 'border-bottom:1px solid #F1F5F9;';
        @endphp
        <tr style="background:{{ $p['subBg'] }};">
          <td style="width:4px;padding:0;background:{{ $p['accent'] }};opacity:0.25;{{ $subBorder }}"></td>
          <td style="padding:6px 14px 6px 24px;{{ $subBorder }}">
            <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">
              <tr>
                <td style="padding-right:8px;vertical-align:middle;">
                  <div style="width:5px;height:5px;border-radius:50%;background:{{ $p['dotBg'] }};"></div>
                </td>
                <td style="vertical-align:middle;">
                  <span style="font-size:11px;color:#475569;font-weight:500;">{{ $sub['name'] ?? '' }}</span>
                </td>
              </tr>
            </table>
          </td>
          <td style="padding:6px 14px;text-align:right;{{ $subBorder }}">{!! $mvCell($sub['weekly_mv'] ?? 0, true) !!}</td>
          <td style="padding:6px 14px;text-align:right;{{ $subBorder }}">{!! $mvCell($sub['mtd_mv'] ?? 0, true) !!}</td>
          <td style="padding:6px 16px 6px 14px;text-align:right;{{ $subBorder }};font-family:'Courier New',ui-monospace,monospace;font-size:11px;color:#64748B;font-weight:500;">
            {{ $fmtFull($sub['total_loans'] ?? 0) }}
          </td>
        </tr>
      @endforeach

    @empty
      <tr>
        <td colspan="5" style="padding:28px;text-align:center;color:#94A3B8;font-size:12px;">
          No segment data available for this period.
        </td>
      </tr>
    @endforelse
  </tbody>

</table>
</div>
