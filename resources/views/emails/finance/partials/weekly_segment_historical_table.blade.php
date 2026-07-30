{{--
  Parameters:
  $historicalSection – array{labels, bank: {segments}, lcy: {segments}, fcy: {segments}}
--}}
@php
    $labels       = $historicalSection['labels']          ?? [];
    $bankSegments = $historicalSection['bank']['segments'] ?? [];
    $lcySegments  = $historicalSection['lcy']['segments']  ?? [];
    $fcySegments  = $historicalSection['fcy']['segments']  ?? [];

    $fmtFull = fn($v) => number_format((int) round(abs((float) $v)));

    $mvCell = function($v, $compact = false) {
        $n   = (float) $v;
        $abs = number_format((int) round(abs($n)));
        $fs  = $compact ? '10.5px' : '11.5px';
        if ($n > 0) return "<span style=\"font-size:{$fs};font-weight:700;color:#15803D;font-family:'Courier New',ui-monospace,monospace;\">+{$abs}</span>";
        if ($n < 0) return "<span style=\"font-size:{$fs};font-weight:700;color:#BE123C;font-family:'Courier New',ui-monospace,monospace;\">−{$abs}</span>";
        return "<span style=\"font-size:{$fs};font-weight:700;color:#94A3B8;font-family:'Courier New',ui-monospace,monospace;\">—</span>";
    };

    $palette = [
        'CB'  => ['accent' => '#1D4ED8', 'rowBg' => '#EFF6FF', 'subBg' => '#FAFCFF', 'nameTx' => '#1E40AF', 'dotBg' => '#BFDBFE'],
        'CM'  => ['accent' => '#7C3AED', 'rowBg' => '#F5F3FF', 'subBg' => '#FDFCFF', 'nameTx' => '#5B21B6', 'dotBg' => '#DDD6FE'],
        'CS'  => ['accent' => '#D97706', 'rowBg' => '#FFFBEB', 'subBg' => '#FFFEF8', 'nameTx' => '#92400E', 'dotBg' => '#FDE68A'],
        'OT'  => ['accent' => '#64748B', 'rowBg' => '#F8FAFC', 'subBg' => '#FAFBFC', 'nameTx' => '#334155', 'dotBg' => '#CBD5E1'],
        'ALL' => ['accent' => '#0F172A', 'rowBg' => '#1B2A3B', 'subBg' => '#1B2A3B', 'nameTx' => '#FFFFFF', 'dotBg' => '#94A3B8'],
    ];

    // Shared table renderer — used for Bank, LCY and FCY
    $renderTable = function(array $segments) use ($palette, $fmtFull, $mvCell, $labels): string {
        $out = '';
        foreach ($segments as $seg) {
            $code    = $seg['code'] ?? 'OT';
            $isTotal = ($code === 'ALL');
            $p       = $palette[$code] ?? $palette['OT'];
            $hasSubs = !empty($seg['sub_segments']);
            $borderTop = $isTotal ? 'border-top:2px solid #94A3B8;' : 'border-top:1px solid #E8ECF1;';

            // Segment row
            $totalStyle = $isTotal
                ? 'font-size:13px;font-weight:900;color:#FFFFFF;letter-spacing:0.2px;'
                : "font-size:12.5px;font-weight:800;color:{$p['nameTx']};letter-spacing:0.1px;";

            $out .= "<tr style=\"background:{$p['rowBg']};\">";
            $out .= "<td style=\"width:5px;padding:0;background:{$p['accent']};{$borderTop}\"></td>";
            $out .= "<td style=\"padding:" . ($isTotal ? '13px' : '11px') . " 14px " . ($isTotal ? '13px' : '11px') . " 14px;{$borderTop}\">";
            $out .= "<span style=\"{$totalStyle}\">" . strtoupper($seg['name'] ?? $code) . "</span>";
            if (!$isTotal && $hasSubs) {
                $out .= "<span style=\"font-size:9.5px;font-weight:600;color:#94A3B8;margin-left:6px;vertical-align:middle;\">" . count($seg['sub_segments']) . " sub-segments</span>";
            }
            $out .= "</td>";

            $numStyle = $isTotal
                ? "padding:" . ($isTotal ? '13px' : '11px') . " 14px;text-align:right;{$borderTop};font-family:'Courier New',ui-monospace,monospace;font-size:13px;font-weight:900;color:#FFFFFF;"
                : "padding:11px 14px;text-align:right;{$borderTop};font-family:'Courier New',ui-monospace,monospace;font-size:12px;font-weight:700;color:{$p['nameTx']};";

            $out .= "<td style=\"{$numStyle}\">" . $fmtFull($seg['ye_bal'] ?? 0) . "</td>";
            $out .= "<td style=\"{$numStyle}\">" . $fmtFull($seg['m3_bal'] ?? 0) . "</td>";
            $out .= "<td style=\"{$numStyle}\">" . $fmtFull($seg['m2_bal'] ?? 0) . "</td>";
            $out .= "<td style=\"{$numStyle}\">" . $fmtFull($seg['m1_bal'] ?? 0) . "</td>";
            $mvPad = $isTotal ? "padding:13px 16px 13px 14px;" : "padding:11px 16px 11px 14px;";
            $out .= "<td style=\"{$mvPad}text-align:right;{$borderTop}\">" . $mvCell($seg['w1_mv'] ?? 0, false) . "</td>";
            $out .= "</tr>";

            // Sub-segment rows
            foreach ($seg['sub_segments'] ?? [] as $sub) {
                $isLast    = ($sub === end($seg['sub_segments']));
                $subBorder = $isLast ? 'border-bottom:1px solid #E8ECF1;' : 'border-bottom:1px solid #F1F5F9;';

                $out .= "<tr style=\"background:{$p['subBg']};\">";
                $out .= "<td style=\"width:5px;padding:0;background:{$p['accent']};opacity:0.25;{$subBorder}\"></td>";
                $out .= "<td style=\"padding:6px 14px 6px 28px;{$subBorder}\">";
                $out .= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;\"><tr>";
                $out .= "<td style=\"padding-right:8px;vertical-align:middle;\"><div style=\"width:5px;height:5px;border-radius:50%;background:{$p['dotBg']};\"></div></td>";
                $out .= "<td style=\"vertical-align:middle;\"><span style=\"font-size:11px;color:#475569;font-weight:500;\">" . ($sub['name'] ?? '') . "</span></td>";
                $out .= "</tr></table></td>";
                $out .= "<td style=\"padding:6px 14px;text-align:right;{$subBorder};font-family:'Courier New',ui-monospace,monospace;font-size:11px;color:#64748B;font-weight:500;\">" . $fmtFull($sub['ye_bal'] ?? 0) . "</td>";
                $out .= "<td style=\"padding:6px 14px;text-align:right;{$subBorder};font-family:'Courier New',ui-monospace,monospace;font-size:11px;color:#64748B;font-weight:500;\">" . $fmtFull($sub['m3_bal'] ?? 0) . "</td>";
                $out .= "<td style=\"padding:6px 14px;text-align:right;{$subBorder};font-family:'Courier New',ui-monospace,monospace;font-size:11px;color:#64748B;font-weight:500;\">" . $fmtFull($sub['m2_bal'] ?? 0) . "</td>";
                $out .= "<td style=\"padding:6px 14px;text-align:right;{$subBorder};font-family:'Courier New',ui-monospace,monospace;font-size:11px;color:#64748B;font-weight:500;\">" . $fmtFull($sub['m1_bal'] ?? 0) . "</td>";
                $out .= "<td style=\"padding:6px 16px 6px 14px;text-align:right;{$subBorder}\">" . $mvCell($sub['w1_mv'] ?? 0, true) . "</td>";
                $out .= "</tr>";
            }
        }
        return $out;
    };
@endphp

{{-- ── Bank Table (all currencies, KES equivalent) ──────────────────────── --}}
<div style="border-radius:12px;overflow:hidden;border:1px solid #E2E8F0;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:24px;">
<table width="100%" cellpadding="0" cellspacing="0"
    style="width:100%;border-collapse:collapse;font-size:12px;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;min-width:680px;">
  <thead>
    <tr>
      <td colspan="7" style="background:#1F3A5F;padding:10px 16px;">
        <span style="font-size:11px;font-weight:800;color:#CBD5E1;text-transform:uppercase;letter-spacing:1.5px;">Bank Deposits (All Currencies, KES Equivalent)</span>
      </td>
    </tr>
    <tr bgcolor="#1B344F">
      <th style="width:5px;padding:0;background:#0F2744;border-bottom:1px solid #0F2744;"></th>
      <th style="padding:11px 16px 11px 12px;text-align:left;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;width:26%;">Segment</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:14%;">{{ $labels['ye'] ?? 'YE Balance' }}</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:14%;">{{ $labels['m3'] ?? 'Month-3' }}</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:14%;">{{ $labels['m2'] ?? 'Month-2' }}</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:14%;">{{ $labels['m1'] ?? 'Month-1' }}</th>
      <th style="padding:11px 16px 11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:18%;">{{ $labels['w1'] ?? 'W-1 Mv' }}</th>
    </tr>
  </thead>
  <tbody>
    @if(empty($bankSegments))
      <tr><td colspan="7" style="padding:24px;text-align:center;color:#94A3B8;">No bank-level data.</td></tr>
    @else
      {!! $renderTable($bankSegments) !!}
    @endif
  </tbody>
</table>
</div>

{{-- ── LCY Table ─────────────────────────────────────────────────────────── --}}
<div style="border-radius:12px;overflow:hidden;border:1px solid #E2E8F0;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:24px;">
<table width="100%" cellpadding="0" cellspacing="0"
    style="width:100%;border-collapse:collapse;font-size:12px;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;min-width:680px;">
  <thead>
    <tr>
      <td colspan="7" style="background:#0F2744;padding:10px 16px;">
        <span style="font-size:11px;font-weight:800;color:#94A3B8;text-transform:uppercase;letter-spacing:1.5px;">LCY Deposits (KES)</span>
      </td>
    </tr>
    <tr bgcolor="#1B344F">
      <th style="width:5px;padding:0;background:#0F2744;border-bottom:1px solid #0F2744;"></th>
      <th style="padding:11px 16px 11px 12px;text-align:left;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;width:26%;">Segment</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:14%;">{{ $labels['ye'] ?? 'YE Balance' }}</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:14%;">{{ $labels['m3'] ?? 'Month-3' }}</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:14%;">{{ $labels['m2'] ?? 'Month-2' }}</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:14%;">{{ $labels['m1'] ?? 'Month-1' }}</th>
      <th style="padding:11px 16px 11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:18%;">{{ $labels['w1'] ?? 'W-1 Mv' }}</th>
    </tr>
  </thead>
  <tbody>
    @if(empty($lcySegments))
      <tr><td colspan="7" style="padding:24px;text-align:center;color:#94A3B8;">No LCY data.</td></tr>
    @else
      {!! $renderTable($lcySegments) !!}
    @endif
  </tbody>
</table>
</div>

{{-- ── FCY Table ─────────────────────────────────────────────────────────── --}}
<div style="border-radius:12px;overflow:hidden;border:1px solid #E2E8F0;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
<table width="100%" cellpadding="0" cellspacing="0"
    style="width:100%;border-collapse:collapse;font-size:12px;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;min-width:680px;">
  <thead>
    <tr>
      <td colspan="7" style="background:#1A3A2A;padding:10px 16px;">
        <span style="font-size:11px;font-weight:800;color:#86EFAC;text-transform:uppercase;letter-spacing:1.5px;">FCY Deposits (KES equivalent)</span>
      </td>
    </tr>
    <tr bgcolor="#1B344F">
      <th style="width:5px;padding:0;background:#0F2744;border-bottom:1px solid #0F2744;"></th>
      <th style="padding:11px 16px 11px 12px;text-align:left;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;width:26%;">Segment</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:14%;">{{ $labels['ye'] ?? 'YE Balance' }}</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:14%;">{{ $labels['m3'] ?? 'Month-3' }}</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:14%;">{{ $labels['m2'] ?? 'Month-2' }}</th>
      <th style="padding:11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:14%;">{{ $labels['m1'] ?? 'Month-1' }}</th>
      <th style="padding:11px 16px 11px 14px;text-align:right;font-size:10px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid #253D54;white-space:nowrap;width:18%;">{{ $labels['w1'] ?? 'W-1 Mv' }}</th>
    </tr>
  </thead>
  <tbody>
    @if(empty($fcySegments))
      <tr><td colspan="7" style="padding:24px;text-align:center;color:#94A3B8;">No FCY data.</td></tr>
    @else
      {!! $renderTable($fcySegments) !!}
    @endif
  </tbody>
</table>
</div>
