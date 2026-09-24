{{--  resources\views\emails\finance\partials\rm_movers_table.blade.php  --}}
@php
    $rows = $rows ?? collect();
    $totals = $totals ?? null;
    $start = $start ?? null;
    $end = $end ?? null;

    $pad = 8;
    $font = 11.5;
    $nameMax = '220px';
@endphp

@if ($rows->isEmpty())
    <div style="text-align:center; padding:14px 10px;">
        <div style="font-size:11.5px; font-weight:900; color:#464646; margin-bottom:3px;">No data</div>
        <div style="font-size:10.5px; color:#979797;">No qualifying movements for this period.</div>
    </div>
@else
    <table width="100%" cellpadding="0" cellspacing="0"
        style="width:100%; border-collapse:separate; border-spacing:0; font-size:{{ $font }}px; border:1px solid #E0E0E0; border-radius:10px; overflow:hidden; background:#ffffff; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; table-layout:fixed; mso-table-lspace:0pt; mso-table-rspace:0pt;">
        <thead>
            <tr>
                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:left; width:26%;">
                    RM
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:left; width:12%;">
                    Code
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:16%;">
                    {{ $start ? \Carbon\Carbon::parse($start)->format('d M') : 'Start' }}
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:16%;">
                    {{ $end ? \Carbon\Carbon::parse($end)->format('d M') : 'End' }}
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:16%;">
                    Move
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:14%;">
                    Customers
                </th>
            </tr>
        </thead>

        <tbody>
            @foreach ($rows as $i => $r)
                @php
                    $rowBg = $i % 2 === 0 ? '#ffffff' : '#fafbfc';
                    $isLast = $loop->last && !$totals;
                    $rowBorder = $isLast ? 'none' : '1px solid #E0E0E0';

                    $sb = (float) ($r->start_balance ?? 0);
                    $eb = (float) ($r->end_balance ?? 0);
                    $mv = (float) ($r->movement ?? 0);

                    $isGain = $mv >= 0;
                    $arrow = $isGain ? '▲' : '▼';

                    $startTxt = number_format((int) round($sb), 0);
                    $endTxt = number_format((int) round($eb), 0);
                    $moveTxt = number_format((int) round(abs($mv)), 0);
                @endphp

                <tr style="background:{{ $rowBg }};">
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; line-height:1.2;">
                        <span title="{{ $r->rm_name ?? $r->rm_code }}"
                            style="display:inline-block; max-width:{{ $nameMax }}; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; vertical-align:bottom; font-weight:800; color:#2a2a2a;">
                            {{ $r->rm_name ?? $r->rm_code }}
                        </span>
                    </td>

                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; line-height:1.2;">
                        <span
                            style="display:inline-block; padding:2px 8px; border-radius:999px; background:#e8f4fb; border:1px solid #b3d9ed; color:#005B82; font-weight:900; font-size:10px; letter-spacing:0.35px;">
                            {{ $r->rm_code }}
                        </span>
                    </td>

                    <td
                        style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#4b5563; line-height:1.2;">
                        {{ $startTxt }}
                    </td>

                    <td
                        style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#4b5563; line-height:1.2;">
                        {{ $endTxt }}
                    </td>

                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:right;">
                        <span
                            style="display:inline-block; padding:4px 8px; border-radius:8px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:{{ $isGain ? '#f4fad4' : '#fff0f0' }}; color:{{ $isGain ? '#4a6a1a' : '#a11818' }}; border:1px solid {{ $isGain ? '#d0e06b' : '#ffb3b3' }};">
                            {{ $arrow }} {{ $moveTxt }}
                        </span>
                    </td>

                    <td
                        style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:right; font-weight:800; color:#646464; line-height:1.2;">
                        {{ (int) ($r->cif_count ?? 0) }}
                    </td>
                </tr>
            @endforeach

            @if ($totals)
                @php
                    $tsb = (float) ($totals->start_balance ?? 0);
                    $teb = (float) ($totals->end_balance ?? 0);
                    $tmv = (float) ($totals->movement ?? 0);
                    $tIsGain = $tmv >= 0;
                    $tArrow = $tIsGain ? '▲' : '▼';
                @endphp
                <tr style="background:#ededed;">
                    <td colspan="2" style="padding:{{ $pad }}px; font-weight:900; color:#2a2a2a; text-transform:uppercase; font-size:10.5px;">
                        Total ({{ $rows->count() }} RMs)
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#2a2a2a;">
                        {{ number_format((int) round($tsb), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#2a2a2a;">
                        {{ number_format((int) round($teb), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right;">
                        <span
                            style="display:inline-block; padding:4px 8px; border-radius:8px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:{{ $tIsGain ? '#f4fad4' : '#fff0f0' }}; color:{{ $tIsGain ? '#4a6a1a' : '#a11818' }}; border:1px solid {{ $tIsGain ? '#d0e06b' : '#ffb3b3' }};">
                            {{ $tArrow }} {{ number_format((int) round(abs($tmv)), 0) }}
                        </span>
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-weight:900; color:#2a2a2a;">
                        {{ (int) ($totals->cif_count ?? 0) }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
@endif
