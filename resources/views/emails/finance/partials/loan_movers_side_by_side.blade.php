{{--  resources/views/emails/finance/partials/loan_movers_side_by_side.blade.php
      Expects: $topGainers, $topLosers — collections of ['account','name','rm_code','movement']  --}}
@php
    $topGainers = $topGainers ?? collect();
    $topLosers  = $topLosers  ?? collect();

    $fmtAbs = function ($v) {
        $n = abs((float) $v);
        if ($n >= 1_000_000_000) return number_format($n / 1_000_000_000, 2) . 'B';
        if ($n >= 1_000_000)     return number_format($n / 1_000_000, 2)     . 'M';
        if ($n >= 1_000)         return number_format($n / 1_000, 1)          . 'K';
        return number_format((int) $n);
    };
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;">
  <tr>
    <td style="width:49%;vertical-align:top;padding-right:8px;">
      <table width="100%" cellpadding="0" cellspacing="0"
        style="width:100%;border-collapse:separate;border-spacing:0;font-size:11px;border:1px solid #BBF7D0;border-radius:10px;overflow:hidden;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">
        <thead>
          <tr>
            <th colspan="4" style="padding:8px 12px;background:#166534;text-align:left;font-size:10px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:0.8px;">
              ▲ Top Loan Gainers
            </th>
          </tr>
          <tr>
            <th style="padding:6px 10px;background:#F0FDF4;border-bottom:1px solid #BBF7D0;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;width:8%;">#</th>
            <th style="padding:6px 10px;background:#F0FDF4;border-bottom:1px solid #BBF7D0;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;text-align:left;">Account</th>
            <th style="padding:6px 10px;background:#F0FDF4;border-bottom:1px solid #BBF7D0;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;text-align:left;width:14%;">RM</th>
            <th style="padding:6px 10px;background:#F0FDF4;border-bottom:1px solid #BBF7D0;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;text-align:right;">Movement</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($topGainers as $i => $r)
            @php
              $r = (object) $r;
              $rowBg = $i % 2 === 0 ? '#ffffff' : '#F0FDF4';
              $isLast = $loop->last;
              $b = $isLast ? '' : 'border-bottom:1px solid #DCFCE7;';
            @endphp
            <tr style="background:{{ $rowBg }};">
              <td style="padding:7px 10px;text-align:center;font-weight:900;color:#15803D;{{ $b }}">{{ $i + 1 }}</td>
              <td style="padding:7px 10px;font-weight:700;color:#1F3A5F;{{ $b }}">
                <span title="{{ $r->name ?? $r->account }}" style="display:inline-block;max-width:170px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;vertical-align:bottom;">{{ $r->name ?: $r->account }}</span>
              </td>
              <td style="padding:7px 10px;font-weight:800;color:#005B82;{{ $b }}">{{ $r->rm_code ?? '—' }}</td>
              <td style="padding:7px 10px;text-align:right;{{ $b }}">
                <span style="display:inline-block;padding:3px 8px;border-radius:6px;font-weight:900;font-size:10.5px;background:#bbf7d0;color:#14532d;border:1px solid #86efac;">▲ {{ $fmtAbs($r->movement ?? 0) }}</span>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" style="padding:12px;text-align:center;color:#94A3B8;font-size:11px;">No data</td></tr>
          @endforelse
        </tbody>
      </table>
    </td>

    <td style="width:49%;vertical-align:top;padding-left:8px;">
      <table width="100%" cellpadding="0" cellspacing="0"
        style="width:100%;border-collapse:separate;border-spacing:0;font-size:11px;border:1px solid #FECACA;border-radius:10px;overflow:hidden;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">
        <thead>
          <tr>
            <th colspan="4" style="padding:8px 12px;background:#991B1B;text-align:left;font-size:10px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:0.8px;">
              ▼ Top Loan Losers
            </th>
          </tr>
          <tr>
            <th style="padding:6px 10px;background:#FFF5F5;border-bottom:1px solid #FECACA;font-size:9px;font-weight:900;color:#B91C1C;text-transform:uppercase;letter-spacing:0.6px;width:8%;">#</th>
            <th style="padding:6px 10px;background:#FFF5F5;border-bottom:1px solid #FECACA;font-size:9px;font-weight:900;color:#B91C1C;text-transform:uppercase;letter-spacing:0.6px;text-align:left;">Account</th>
            <th style="padding:6px 10px;background:#FFF5F5;border-bottom:1px solid #FECACA;font-size:9px;font-weight:900;color:#B91C1C;text-transform:uppercase;letter-spacing:0.6px;text-align:left;width:14%;">RM</th>
            <th style="padding:6px 10px;background:#FFF5F5;border-bottom:1px solid #FECACA;font-size:9px;font-weight:900;color:#B91C1C;text-transform:uppercase;letter-spacing:0.6px;text-align:right;">Movement</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($topLosers as $i => $r)
            @php
              $r = (object) $r;
              $rowBg = $i % 2 === 0 ? '#ffffff' : '#FFF5F5';
              $isLast = $loop->last;
              $b = $isLast ? '' : 'border-bottom:1px solid #FECACA;';
            @endphp
            <tr style="background:{{ $rowBg }};">
              <td style="padding:7px 10px;text-align:center;font-weight:900;color:#B91C1C;{{ $b }}">{{ $i + 1 }}</td>
              <td style="padding:7px 10px;font-weight:700;color:#1F3A5F;{{ $b }}">
                <span title="{{ $r->name ?? $r->account }}" style="display:inline-block;max-width:170px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;vertical-align:bottom;">{{ $r->name ?: $r->account }}</span>
              </td>
              <td style="padding:7px 10px;font-weight:800;color:#005B82;{{ $b }}">{{ $r->rm_code ?? '—' }}</td>
              <td style="padding:7px 10px;text-align:right;{{ $b }}">
                <span style="display:inline-block;padding:3px 8px;border-radius:6px;font-weight:900;font-size:10.5px;background:#fecaca;color:#7f1d1d;border:1px solid #fca5a5;">▼ {{ $fmtAbs($r->movement ?? 0) }}</span>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" style="padding:12px;text-align:center;color:#94A3B8;font-size:11px;">No data</td></tr>
          @endforelse
        </tbody>
      </table>
    </td>
  </tr>
</table>
