{{--  resources/views/emails/finance/partials/rm_loan_summary_table.blade.php  --}}
@php
    $rows = $rows ?? collect();
    $totals = $totals ?? null;
    $pad = 8;
    $font = 11.5;
@endphp

@if ($rows->isEmpty())
    <div style="text-align:center; padding:14px 10px;">
        <div style="font-size:11.5px; font-weight:900; color:#464646; margin-bottom:3px;">No data</div>
        <div style="font-size:10.5px; color:#979797;">No qualifying loan movements for this period.</div>
    </div>
@else
    <div style="overflow-x:auto;">
    <table width="100%" cellpadding="0" cellspacing="0"
        style="width:100%; border-collapse:separate; border-spacing:0; font-size:{{ $font }}px; border:1px solid #E0E0E0; border-radius:10px; overflow:hidden; background:#ffffff; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; table-layout:fixed; mso-table-lspace:0pt; mso-table-rspace:0pt;">
        <thead>
            <tr>
                <th style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:left; width:24%;">RM</th>
                <th style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:left; width:10%;">Code</th>
                <th style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:11%;">Accounts</th>
                <th style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:11%;">Customers</th>
                <th style="padding:{{ $pad }}px; background:#DCFCE7; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:right; width:14%; border-left:2px solid #BBF7D0;">Open</th>
                <th style="padding:{{ $pad }}px; background:#DCFCE7; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:right; width:14%;">Close</th>
                <th style="padding:{{ $pad }}px; background:#DCFCE7; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:right; width:16%;">Movement</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $r)
                @php
                    $rowBg = $i % 2 === 0 ? '#ffffff' : '#fafbfc';
                    $isLast = $loop->last && !$totals;
                    $rowBorder = $isLast ? 'none' : '1px solid #E0E0E0';

                    $open = (float) ($r->loan_open ?? 0);
                    $close = (float) ($r->loan_close ?? 0);
                    $mv = (float) ($r->loan_movement ?? 0);
                    $isGain = $mv >= 0;
                    $arrow = $isGain ? '▲' : '▼';
                @endphp
                <tr style="background:{{ $rowBg }};">
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; line-height:1.2;">
                        <span title="{{ $r->rm_name ?? $r->rm_code }}" style="display:inline-block; max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; vertical-align:bottom; font-weight:800; color:#2a2a2a;">
                            {{ $r->rm_name ?? $r->rm_code }}
                        </span>
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; line-height:1.2;">
                        <span style="display:inline-block; padding:2px 6px; border-radius:999px; background:#e8f4fb; border:1px solid #b3d9ed; color:#005B82; font-weight:900; font-size:9.5px; letter-spacing:0.3px;">
                            {{ $r->rm_code }}
                        </span>
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:right; font-weight:800; color:#646464;">
                        {{ number_format((int) ($r->account_count ?? 0)) }}
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:right; font-weight:800; color:#646464;">
                        {{ number_format((int) ($r->customer_count ?? 0)) }}
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; background:#F0FDF4; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#166534; border-left:2px solid #BBF7D0;">
                        {{ number_format((int) round($open), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; background:#F0FDF4; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#166534;">
                        {{ number_format((int) round($close), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; background:#F0FDF4; text-align:right;">
                        <span style="display:inline-block; padding:4px 8px; border-radius:8px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:{{ $isGain ? '#bbf7d0' : '#fecaca' }}; color:{{ $isGain ? '#14532d' : '#7f1d1d' }}; border:1px solid {{ $isGain ? '#86efac' : '#fca5a5' }};">
                            {{ $arrow }} {{ number_format((int) round(abs($mv)), 0) }}
                        </span>
                    </td>
                </tr>
            @endforeach

            @if ($totals)
                @php
                    $tOpen = (float) ($totals->loan_open ?? 0);
                    $tClose = (float) ($totals->loan_close ?? 0);
                    $tMv = (float) ($totals->loan_movement ?? 0);
                    $tIsGain = $tMv >= 0;
                    $tArrow = $tIsGain ? '▲' : '▼';
                @endphp
                <tr style="background:#ececec;">
                    <td colspan="2" style="padding:{{ $pad }}px; font-weight:900; color:#2a2a2a; text-transform:uppercase; font-size:9.5px;">
                        Total ({{ $rows->count() }} RMs)
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-weight:900; color:#2a2a2a;">
                        {{ number_format((int) ($totals->account_count ?? 0)) }}
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-weight:900; color:#2a2a2a;">
                        {{ number_format((int) ($totals->customer_count ?? 0)) }}
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#166534; border-left:2px solid #d4d4d4;">
                        {{ number_format((int) round($tOpen), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#166534;">
                        {{ number_format((int) round($tClose), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right;">
                        <span style="display:inline-block; padding:4px 8px; border-radius:8px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:{{ $tIsGain ? '#bbf7d0' : '#fecaca' }}; color:{{ $tIsGain ? '#14532d' : '#7f1d1d' }}; border:1px solid {{ $tIsGain ? '#86efac' : '#fca5a5' }};">
                            {{ $tArrow }} {{ number_format((int) round(abs($tMv)), 0) }}
                        </span>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    </div>
@endif
