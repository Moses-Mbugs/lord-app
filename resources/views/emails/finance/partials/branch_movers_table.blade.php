{{--  resources\views\emails\finance\partials\branch_movers_table.blade.php  --}}
@php
    $rows = $rows ?? collect();
    $start = $start ?? null;
    $end = $end ?? null;

    $compact = $compact ?? false;
    $showRank = $showRank ?? false;

    $pad = $compact ? 6 : 8;
    $font = $compact ? 11 : 11.5;

    $nameMax = $compact ? '220px' : '320px';
@endphp

@if ($rows->isEmpty())
    <div style="text-align:center; padding:14px 10px;">
        <div style="margin-bottom:6px; color:#BED600; opacity:0.8;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        <div style="font-size:11.5px; font-weight:900; color:#464646; margin-bottom:3px;">No data</div>
        <div style="font-size:10.5px; color:#979797;">No qualifying movements for this period.</div>
    </div>
@else
    <table width="100%" cellpadding="0" cellspacing="0"
        style="width:100%; border-collapse:separate; border-spacing:0; font-size:{{ $font }}px; border:1px solid #E0E0E0; border-radius:10px; overflow:hidden; background:#ffffff; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; table-layout:fixed; mso-table-lspace:0pt; mso-table-rspace:0pt;">
        <thead>
            {{-- Group header row --}}
            <tr>
                @if ($showRank)
                    <th style="padding:4px {{ $pad }}px; background:#D8D8D8; border-bottom:1px solid #C0C0C0;"></th>
                @endif
                <th style="padding:4px {{ $pad }}px; background:#D8D8D8; border-bottom:1px solid #C0C0C0;"></th>

                <th colspan="3"
                    style="padding:5px {{ $pad }}px; background:#D8E9F3; text-transform:uppercase; font-size:9px; letter-spacing:0.8px; font-weight:900; color:#005B82; text-align:center; border-bottom:1px solid #A8C9DE; border-left:1px solid #A8C9DE;">
                    Deposits
                </th>

                <th colspan="3"
                    style="padding:5px {{ $pad }}px; background:#BBF7D0; text-transform:uppercase; font-size:9px; letter-spacing:0.8px; font-weight:900; color:#14532d; text-align:center; border-bottom:1px solid #86EFAC; border-left:2px solid #86EFAC;">
                    Performing Loans
                </th>
            </tr>

            {{-- Column header row --}}
            <tr>
                @if ($showRank)
                    <th
                        style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:center; width:5%;">
                        #</th>
                @endif

                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:left; width:{{ $showRank ? '17%' : '22%' }};">
                    Branch
                </th>

                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:14%; border-left:1px solid #A8C9DE;">
                    {{ $start ? \Carbon\Carbon::parse($start)->format('d M') : 'Start' }}
                </th>

                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:14%;">
                    {{ $end ? \Carbon\Carbon::parse($end)->format('d M') : 'End' }}
                </th>

                <th
                    style="padding:{{ $pad }}px; background:#EDEDED; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#464646; border-bottom:2px solid #D0D0D0; text-align:right; width:13%;">
                    Dep Move
                </th>

                <th
                    style="padding:{{ $pad }}px; background:#DCFCE7; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:right; width:12%; border-left:2px solid #BBF7D0;">
                    Open
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#DCFCE7; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:right; width:12%;">
                    Close
                </th>
                <th
                    style="padding:{{ $pad }}px; background:#DCFCE7; text-transform:uppercase; font-size:9px; letter-spacing:0.6px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:right; width:13%;">
                    Move
                </th>
            </tr>
        </thead>

        <tbody>
            @foreach ($rows as $i => $r)
                @php
                    $rowBg = $i % 2 === 0 ? '#ffffff' : '#fafbfc';
                    $isLast = $loop->last;
                    $rowBorder = $isLast ? 'none' : '1px solid #E0E0E0';

                    $branch = (string) ($r->group_name ?? ($r->group_key ?? ($r->branch_code ?? '—')));

                    $sb = (float) ($r->start_balance ?? 0);
                    $eb = (float) ($r->end_balance ?? 0);
                    $mv = (float) ($r->movement ?? 0);

                    $loanOpen = (float) ($r->loan_open ?? 0);
                    $loanClose = (float) ($r->loan_close ?? 0);
                    $loanMv = (float) ($r->loan_movement ?? 0);

                    $isGain = $mv > 0;
                    $isLoanGain = $loanMv > 0;
                    $arrow = $isGain ? '▲' : '▼';
                    $loanArrow = $isLoanGain ? '▲' : '▼';

                    $startTxt = number_format((int) round($sb), 0);
                    $endTxt = number_format((int) round($eb), 0);
                    $moveTxt = number_format((int) round(abs($mv)), 0);
                    $loanOpenTxt = number_format((int) round($loanOpen), 0);
                    $loanCloseTxt = number_format((int) round($loanClose), 0);
                    $loanMvTxt = number_format((int) round(abs($loanMv)), 0);

                    $loanRowBg = $i % 2 === 0 ? '#F0FDF4' : '#DCFCE7';

                    $rank = $r->rank ?? null;
                    $isTotal =
                        strtoupper(trim($branch)) === 'ALL' ||
                        strtoupper(trim((string) ($r->group_name ?? ''))) === 'TOTAL';
                @endphp

                <tr style="background:{{ $rowBg }};">

                    @if ($showRank)
                        <td
                            style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; text-align:center; font-weight:900; color:#464646; line-height:1.2;">
                            {{ $rank ?? '—' }}
                        </td>
                    @endif

                    <td style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; line-height:1.2;">
                        <span
                            style="display:inline-block; padding:2px 8px; border-radius:999px;
                        background:{{ $isTotal ? '#ededed' : '#e8f4fb' }};
                        border:1px solid {{ $isTotal ? '#d0d0d0' : '#b3d9ed' }};
                        color:{{ $isTotal ? '#464646' : '#005B82' }};
                        font-weight:900; font-size:10px; letter-spacing:0.35px; text-transform:uppercase;">
                            <span title="{{ $branch }}"
                                style="display:inline-block; max-width:{{ $nameMax }}; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; vertical-align:bottom;">
                                {{ $isTotal ? 'TOTAL' : $branch }}
                            </span>
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
                        @if ($mv >= 0)
                            <span
                                style="display:inline-block; padding:4px 8px; border-radius:8px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:#f4fad4; color:#4a6a1a; border:1px solid #d0e06b;">
                                {{ $arrow }} {{ $moveTxt }}
                            </span>
                        @else
                            <span
                                style="display:inline-block; padding:4px 8px; border-radius:8px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:#fff0f0; color:#a11818; border:1px solid #ffb3b3;">
                                {{ $arrow }} {{ $moveTxt }}
                            </span>
                        @endif
                    </td>

                    {{-- Performing Loans Open --}}
                    <td
                        style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; background:{{ $loanRowBg }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#166534; line-height:1.2; border-left:2px solid #BBF7D0;">
                        {{ $loanOpenTxt }}
                    </td>

                    {{-- Performing Loans Close --}}
                    <td
                        style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; background:{{ $loanRowBg }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:900; color:#166534; line-height:1.2;">
                        {{ $loanCloseTxt }}
                    </td>

                    {{-- Performing Loans Move --}}
                    <td
                        style="padding:{{ $pad }}px; border-bottom:{{ $rowBorder }}; background:{{ $loanRowBg }}; text-align:right;">
                        @if ($loanMv >= 0)
                            <span
                                style="display:inline-block; padding:4px 8px; border-radius:8px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:#bbf7d0; color:#14532d; border:1px solid #86efac;">
                                {{ $loanArrow }} {{ $loanMvTxt }}
                            </span>
                        @else
                            <span
                                style="display:inline-block; padding:4px 8px; border-radius:8px; font-weight:900; font-size:{{ $font }}px; white-space:nowrap; background:#fecaca; color:#7f1d1d; border:1px solid #fca5a5;">
                                {{ $loanArrow }} {{ $loanMvTxt }}
                            </span>
                        @endif
                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>
@endif
