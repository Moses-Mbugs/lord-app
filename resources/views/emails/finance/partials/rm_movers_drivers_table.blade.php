{{--  resources\views\emails\finance\partials\rm_movers_drivers_table.blade.php  --}}
@php
    $rows = $rows ?? collect();
    $pad = 7;
    $font = 11;
@endphp

@if (empty($rows) || (is_countable($rows) && count($rows) === 0))
    <div style="text-align:center; padding:12px 10px;">
        <div style="font-size:11px; color:#979797;">No qualifying customer movements.</div>
    </div>
@else
    <table width="100%" cellpadding="0" cellspacing="0"
        style="width:100%; border-collapse:separate; border-spacing:0; font-size:{{ $font }}px; border:1px solid #E0E0E0; border-radius:10px; overflow:hidden; background:#ffffff; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; table-layout:fixed; mso-table-lspace:0pt; mso-table-rspace:0pt;">
        <thead>
            <tr>
                <th style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:left; width:34%;">
                    Customer
                </th>
                <th style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:left; width:14%;">
                    RM
                </th>
                <th style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:17%;">
                    Start
                </th>
                <th style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:17%;">
                    End
                </th>
                <th style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:18%;">
                    Move
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $r)
                @php
                    $r = (object) $r;
                    $rowBg = $i % 2 === 0 ? '#ffffff' : '#fafbfc';
                    $isLast = $loop->last;
                    $rowBorder = $isLast ? 'none' : '1px solid #E0E0E0';

                    $mv = (float) ($r->movement ?? 0);
                    $isGain = $mv >= 0;
                    $arrow = $isGain ? '▲' : '▼';
                @endphp
                <tr style="background:{{ $rowBg }};">
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; line-height:1.2;">
                        <span title="{{ $r->customer_name ?? $r->cif }}"
                            style="display:inline-block; max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; vertical-align:bottom; font-weight:700; color:#2a2a2a;">
                            {{ $r->customer_name ?? $r->cif }}
                        </span>
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; font-weight:800; color:#005B82; line-height:1.2;">
                        {{ $r->rm_code ?? '—' }}
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:800; color:#4b5563;">
                        {{ number_format((int) round((float) ($r->start_balance ?? 0)), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:800; color:#4b5563;">
                        {{ number_format((int) round((float) ($r->end_balance ?? 0)), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:right;">
                        <span
                            style="display:inline-block; padding:3px 7px; border-radius:8px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:{{ $isGain ? '#f4fad4' : '#fff0f0' }}; color:{{ $isGain ? '#4a6a1a' : '#a11818' }}; border:1px solid {{ $isGain ? '#d0e06b' : '#ffb3b3' }};">
                            {{ $arrow }} {{ number_format((int) round(abs($mv)), 0) }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
