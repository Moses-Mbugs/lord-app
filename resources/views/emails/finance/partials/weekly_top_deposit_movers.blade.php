{{--  resources/views/emails/finance/partials/weekly_top_deposit_movers.blade.php
      Top Weekly Deposit Movers (customers), across all segments combined.
      Expects: $topGainers, $topLosers (collections of ['cif'/'customer_name','rm_code','movement'])  --}}
@php
    $topGainers = $topGainers ?? collect();
    $topLosers  = $topLosers  ?? collect();
@endphp

@if ($topGainers->isNotEmpty() || $topLosers->isNotEmpty())
<div style="margin-top:32px;">

  <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-bottom:14px;">
    <tr>
      <td style="padding-right:10px;vertical-align:middle;">
        <div style="width:4px;height:18px;background:linear-gradient(180deg,#F59E0B 0%,#B45309 100%);border-radius:2px;"></div>
      </td>
      <td style="vertical-align:middle;">
        <span style="font-size:13px;font-weight:800;color:#0F172A;letter-spacing:-0.2px;">Top Weekly Deposit Movers</span>
        <span style="font-size:11px;font-weight:500;color:#94A3B8;margin-left:8px;">· customers, across all segments</span>
      </td>
    </tr>
  </table>

  <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;">
    <tr>
      <td style="width:49%;vertical-align:top;padding-right:8px;">
        <table width="100%" cellpadding="0" cellspacing="0"
          style="width:100%;border-collapse:separate;border-spacing:0;font-size:11px;border:1px solid #BBF7D0;border-radius:10px;overflow:hidden;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">
          <thead>
            <tr>
              <th colspan="3" style="padding:8px 12px;background:#166534;text-align:left;font-size:10px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:0.8px;">
                ▲ Top Gainers
              </th>
            </tr>
            <tr>
              <th style="padding:6px 10px;background:#F0FDF4;border-bottom:1px solid #BBF7D0;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;width:8%;">#</th>
              <th style="padding:6px 10px;background:#F0FDF4;border-bottom:1px solid #BBF7D0;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;text-align:left;">Customer / RM</th>
              <th style="padding:6px 10px;background:#F0FDF4;border-bottom:1px solid #BBF7D0;font-size:9px;font-weight:900;color:#15803D;text-transform:uppercase;letter-spacing:0.6px;text-align:right;">Movement</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($topGainers as $i => $r)
              @php
                $r   = (object) $r;
                $mv  = (float)($r->movement ?? 0);
                $n   = abs($mv);
                $str = $n >= 1_000_000_000 ? number_format($n/1_000_000_000,2).'B'
                     : ($n >= 1_000_000    ? number_format($n/1_000_000,2).'M'
                     : ($n >= 1_000        ? number_format($n/1_000,1).'K'
                     : number_format((int)$n)));
                $rowBg = $i % 2 === 0 ? '#ffffff' : '#F0FDF4';
                $isLast = $loop->last;
              @endphp
              <tr style="background:{{ $rowBg }};">
                <td style="padding:7px 10px;text-align:center;font-weight:900;color:#15803D;{{ !$isLast ? 'border-bottom:1px solid #DCFCE7;' : '' }}">{{ $i + 1 }}</td>
                <td style="padding:7px 10px;font-weight:700;color:#1F3A5F;{{ !$isLast ? 'border-bottom:1px solid #DCFCE7;' : '' }}">
                  <span title="{{ $r->customer_name ?? $r->cif ?? '—' }}" style="display:inline-block;max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;vertical-align:bottom;">{{ $r->customer_name ?? $r->cif ?? '—' }}</span>
                  <span style="color:#94A3B8;font-weight:600;"> · {{ $r->rm_code ?? '—' }}</span>
                </td>
                <td style="padding:7px 10px;text-align:right;{{ !$isLast ? 'border-bottom:1px solid #DCFCE7;' : '' }}">
                  <span style="display:inline-block;padding:3px 8px;border-radius:6px;font-weight:900;font-size:10.5px;background:#bbf7d0;color:#14532d;border:1px solid #86efac;">▲ {{ $str }}</span>
                </td>
              </tr>
            @empty
              <tr><td colspan="3" style="padding:12px;text-align:center;color:#94A3B8;font-size:11px;">No data</td></tr>
            @endforelse
          </tbody>
        </table>
      </td>

      <td style="width:49%;vertical-align:top;padding-left:8px;">
        <table width="100%" cellpadding="0" cellspacing="0"
          style="width:100%;border-collapse:separate;border-spacing:0;font-size:11px;border:1px solid #FECACA;border-radius:10px;overflow:hidden;background:#ffffff;mso-table-lspace:0pt;mso-table-rspace:0pt;">
          <thead>
            <tr>
              <th colspan="3" style="padding:8px 12px;background:#991B1B;text-align:left;font-size:10px;font-weight:900;color:#ffffff;text-transform:uppercase;letter-spacing:0.8px;">
                ▼ Top Losers
              </th>
            </tr>
            <tr>
              <th style="padding:6px 10px;background:#FFF5F5;border-bottom:1px solid #FECACA;font-size:9px;font-weight:900;color:#B91C1C;text-transform:uppercase;letter-spacing:0.6px;width:8%;">#</th>
              <th style="padding:6px 10px;background:#FFF5F5;border-bottom:1px solid #FECACA;font-size:9px;font-weight:900;color:#B91C1C;text-transform:uppercase;letter-spacing:0.6px;text-align:left;">Customer / RM</th>
              <th style="padding:6px 10px;background:#FFF5F5;border-bottom:1px solid #FECACA;font-size:9px;font-weight:900;color:#B91C1C;text-transform:uppercase;letter-spacing:0.6px;text-align:right;">Movement</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($topLosers as $i => $r)
              @php
                $r   = (object) $r;
                $mv  = (float)($r->movement ?? 0);
                $n   = abs($mv);
                $str = $n >= 1_000_000_000 ? number_format($n/1_000_000_000,2).'B'
                     : ($n >= 1_000_000    ? number_format($n/1_000_000,2).'M'
                     : ($n >= 1_000        ? number_format($n/1_000,1).'K'
                     : number_format((int)$n)));
                $rowBg = $i % 2 === 0 ? '#ffffff' : '#FFF5F5';
                $isLast = $loop->last;
              @endphp
              <tr style="background:{{ $rowBg }};">
                <td style="padding:7px 10px;text-align:center;font-weight:900;color:#B91C1C;{{ !$isLast ? 'border-bottom:1px solid #FECACA;' : '' }}">{{ $i + 1 }}</td>
                <td style="padding:7px 10px;font-weight:700;color:#1F3A5F;{{ !$isLast ? 'border-bottom:1px solid #FECACA;' : '' }}">
                  <span title="{{ $r->customer_name ?? $r->cif ?? '—' }}" style="display:inline-block;max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;vertical-align:bottom;">{{ $r->customer_name ?? $r->cif ?? '—' }}</span>
                  <span style="color:#94A3B8;font-weight:600;"> · {{ $r->rm_code ?? '—' }}</span>
                </td>
                <td style="padding:7px 10px;text-align:right;{{ !$isLast ? 'border-bottom:1px solid #FECACA;' : '' }}">
                  <span style="display:inline-block;padding:3px 8px;border-radius:6px;font-weight:900;font-size:10.5px;background:#fecaca;color:#7f1d1d;border:1px solid #fca5a5;">▼ {{ $str }}</span>
                </td>
              </tr>
            @empty
              <tr><td colspan="3" style="padding:12px;text-align:center;color:#94A3B8;font-size:11px;">No data</td></tr>
            @endforelse
          </tbody>
        </table>
      </td>
    </tr>
  </table>

</div>
@endif
