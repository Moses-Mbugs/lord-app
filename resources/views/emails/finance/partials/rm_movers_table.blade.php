{{--  resources\views\emails\finance\partials\rm_movers_table.blade.php  --}}
@php
    $rows = $rows ?? collect();
    $totals = $totals ?? null;
    $start = $start ?? null;
    $end = $end ?? null;

    $pad = 7;
    $font = 10.8;
    $nameMax = '150px';
@endphp

@if ($rows->isEmpty())
    <div style="text-align:center; padding:14px 10px;">
        <div style="font-size:11.5px; font-weight:900; color:#464646; margin-bottom:3px;">No data</div>
        <div style="font-size:10.5px; color:#979797;">No qualifying movements for this period.</div>
    </div>
@else
    <div style="overflow-x:auto;">
    <table width="100%" cellpadding="0" cellspacing="0"
        style="width:100%; min-width:760px; border-collapse:separate; border-spacing:0; font-size:{{ $font }}px; border:1px solid #E0E0E0; border-radius:10px; overflow:hidden; background:#ffffff; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; table-layout:fixed; mso-table-lspace:0pt; mso-table-rspace:0pt;">
        <thead>
            {{-- Group header row --}}
            <tr>
                <th colspan="2" style="padding:4px {{ $pad }}px; background:#D8D8D8; border-bottom:1px solid #C0C0C0;"></th>

                <th colspan="2"
                    style="padding:5px {{ $pad }}px; background:#EDE7F6; text-transform:uppercase; font-size:8.5px; letter-spacing:0.7px; font-weight:900; color:#4527A0; text-align:center; border-bottom:1px solid #D1C4E9; border-left:1px solid #D1C4E9;">
                    Portfolio
                </th>

                <th colspan="3"
                    style="padding:5px {{ $pad }}px; background:#D8E9F3; text-transform:uppercase; font-size:8.5px; letter-spacing:0.7px; font-weight:900; color:#005B82; text-align:center; border-bottom:1px solid #A8C9DE; border-left:2px solid #A8C9DE;">
                    Deposits
                </th>

                <th colspan="3"
                    style="padding:5px {{ $pad }}px; background:#BBF7D0; text-transform:uppercase; font-size:8.5px; letter-spacing:0.7px; font-weight:900; color:#14532d; text-align:center; border-bottom:1px solid #86EFAC; border-left:2px solid #86EFAC;">
                    Performing Loans
                </th>
            </tr>

            {{-- Column header row --}}
            <tr>
                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:8.5px; letter-spacing:0.5px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:left; width:15%;">
                    RM
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:8.5px; letter-spacing:0.5px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:left; width:8%;">
                    Code
                </th>

                <th
                    style="padding:{{ $pad }}px; background:#F3EFFB; text-transform:uppercase; font-size:8.5px; letter-spacing:0.5px; font-weight:900; color:#4527A0; border-bottom:2px solid #D1C4E9; text-align:right; width:7.5%; border-left:1px solid #D1C4E9;">
                    Cust
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#F3EFFB; text-transform:uppercase; font-size:8.5px; letter-spacing:0.5px; font-weight:900; color:#4527A0; border-bottom:2px solid #D1C4E9; text-align:right; width:7.5%;">
                    Accts
                </th>

                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:8.5px; letter-spacing:0.5px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:12%; border-left:2px solid #A8C9DE;">
                    {{ $start ? \Carbon\Carbon::parse($start)->format('d M') : 'Open' }}
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:8.5px; letter-spacing:0.5px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:12%;">
                    {{ $end ? \Carbon\Carbon::parse($end)->format('d M') : 'Close' }}
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:8.5px; letter-spacing:0.5px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:11%;">
                    Move
                </th>

                <th
                    style="padding:{{ $pad }}px; background:#DCFCE7; text-transform:uppercase; font-size:8.5px; letter-spacing:0.5px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:right; width:11%; border-left:2px solid #BBF7D0;">
                    Open
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#DCFCE7; text-transform:uppercase; font-size:8.5px; letter-spacing:0.5px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:right; width:11%;">
                    Close
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#DCFCE7; text-transform:uppercase; font-size:8.5px; letter-spacing:0.5px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:right; width:11%;">
                    Move
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

                    $lo = (float) ($r->loan_open ?? 0);
                    $lc = (float) ($r->loan_close ?? 0);
                    $lmv = (float) ($r->loan_movement ?? 0);
                    $isLoanGain = $lmv >= 0;
                    $loanArrow = $isLoanGain ? '▲' : '▼';

                    $loanRowBg = $i % 2 === 0 ? '#F0FDF4' : '#DCFCE7';
                    $portRowBg = $i % 2 === 0 ? '#FAF8FE' : '#F3EFFB';
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
                            style="display:inline-block; padding:2px 6px; border-radius:999px; background:#e8f4fb; border:1px solid #b3d9ed; color:#005B82; font-weight:900; font-size:9.5px; letter-spacing:0.3px;">
                            {{ $r->rm_code }}
                        </span>
                    </td>

                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; background:{{ $portRowBg }}; text-align:right; font-weight:800; color:#4527A0; border-left:1px solid #EDE7F6;">
                        {{ number_format((int) ($r->cif_count ?? 0)) }}
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; background:{{ $portRowBg }}; text-align:right; font-weight:800; color:#4527A0;">
                        {{ number_format((int) ($r->total_accounts ?? 0)) }}
                    </td>

                    <td
                        style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#4b5563; border-left:2px solid #E0E0E0;">
                        {{ number_format((int) round($sb), 0) }}
                    </td>
                    <td
                        style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#4b5563;">
                        {{ number_format((int) round($eb), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:right;">
                        <span
                            style="display:inline-block; padding:3px 6px; border-radius:7px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:{{ $isGain ? '#f4fad4' : '#fff0f0' }}; color:{{ $isGain ? '#4a6a1a' : '#a11818' }}; border:1px solid {{ $isGain ? '#d0e06b' : '#ffb3b3' }};">
                            {{ $arrow }} {{ number_format((int) round(abs($mv)), 0) }}
                        </span>
                    </td>

                    <td
                        style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; background:{{ $loanRowBg }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#166534; border-left:2px solid #BBF7D0;">
                        {{ number_format((int) round($lo), 0) }}
                    </td>
                    <td
                        style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; background:{{ $loanRowBg }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#166534;">
                        {{ number_format((int) round($lc), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; background:{{ $loanRowBg }}; text-align:right;">
                        <span
                            style="display:inline-block; padding:3px 6px; border-radius:7px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:{{ $isLoanGain ? '#bbf7d0' : '#fecaca' }}; color:{{ $isLoanGain ? '#14532d' : '#7f1d1d' }}; border:1px solid {{ $isLoanGain ? '#86efac' : '#fca5a5' }};">
                            {{ $loanArrow }} {{ number_format((int) round(abs($lmv)), 0) }}
                        </span>
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

                    $tlo = (float) ($totals->loan_open ?? 0);
                    $tlc = (float) ($totals->loan_close ?? 0);
                    $tlmv = (float) ($totals->loan_movement ?? 0);
                    $tIsLoanGain = $tlmv >= 0;
                    $tLoanArrow = $tIsLoanGain ? '▲' : '▼';
                @endphp
                <tr style="background:#ececec;">
                    <td colspan="2" style="padding:{{ $pad }}px; font-weight:900; color:#2a2a2a; text-transform:uppercase; font-size:9.5px;">
                        Total ({{ $rows->count() }} RMs)
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-weight:900; color:#4527A0; border-left:1px solid #d4d4d4;">
                        {{ number_format((int) ($totals->cif_count ?? 0)) }}
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-weight:900; color:#4527A0;">
                        {{ number_format((int) ($totals->total_accounts ?? 0)) }}
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#2a2a2a; border-left:2px solid #d4d4d4;">
                        {{ number_format((int) round($tsb), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#2a2a2a;">
                        {{ number_format((int) round($teb), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right;">
                        <span
                            style="display:inline-block; padding:3px 6px; border-radius:7px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:{{ $tIsGain ? '#f4fad4' : '#fff0f0' }}; color:{{ $tIsGain ? '#4a6a1a' : '#a11818' }}; border:1px solid {{ $tIsGain ? '#d0e06b' : '#ffb3b3' }};">
                            {{ $tArrow }} {{ number_format((int) round(abs($tmv)), 0) }}
                        </span>
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#166534; border-left:2px solid #d4d4d4;">
                        {{ number_format((int) round($tlo), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#166534;">
                        {{ number_format((int) round($tlc), 0) }}
                    </td>
                    <td style="padding:{{ $pad }}px; text-align:right;">
                        <span
                            style="display:inline-block; padding:3px 6px; border-radius:7px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:{{ $tIsLoanGain ? '#bbf7d0' : '#fecaca' }}; color:{{ $tIsLoanGain ? '#14532d' : '#7f1d1d' }}; border:1px solid {{ $tIsLoanGain ? '#86efac' : '#fca5a5' }};">
                            {{ $tLoanArrow }} {{ number_format((int) round(abs($tlmv)), 0) }}
                        </span>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    </div>
@endif
