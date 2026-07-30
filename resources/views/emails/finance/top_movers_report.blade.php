<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
</head>

<body
    style="margin:0; padding:0; background:#F2F4F7; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; color:#1a1f2e; -webkit-font-smoothing:antialiased;">

    @php
        $maxWidth    = 1160;
        $sectionGap  = 16;
        $contentPadX = 24;
        $segments    = $segments ?? collect();
        $grouped     = $grouped  ?? [];

        $cifGain = ($grouped['CIF_ONLY']['GAIN'] ?? collect());
        $cifLoss = ($grouped['CIF_ONLY']['LOSS'] ?? collect());
    @endphp

    <div style="max-width:{{ $maxWidth }}px; margin:0 auto; padding:20px 12px;">
        <div
            style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08), 0 1px 4px rgba(0,0,0,0.04); border:1px solid #E2E8F0;">

            {{-- ═══════════ HEADER ═══════════ --}}
            <table width="100%" cellpadding="0" cellspacing="0"
                style="width:100%; mso-table-lspace:0pt; mso-table-rspace:0pt;" bgcolor="#00355A">
                <tr>
                    <td style="padding:26px {{ $contentPadX }}px 22px; background:linear-gradient(135deg,#00355A 0%,#005C8A 100%);"
                        bgcolor="#00355A">
                        <table width="100%" cellpadding="0" cellspacing="0"
                            style="mso-table-lspace:0pt; mso-table-rspace:0pt;">
                            <tr>
                                <td style="vertical-align:middle;">
                                    <div style="font-size:9px; font-weight:700; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:1.5px; margin-bottom:5px;">
                                        ECOBANK KENYA · TREASURY &amp; FINANCE</div>
                                    <div style="font-size:22px; font-weight:900; color:#ffffff; letter-spacing:-0.5px; line-height:1.1;">
                                        Daily Deposits Movement Report</div>
                                    <div style="font-size:11.5px; font-weight:500; color:rgba(255,255,255,0.6); margin-top:4px;">
                                        {{ $start ? \Carbon\Carbon::parse($start)->format('d M Y') : '' }}
                                        &nbsp;→&nbsp;
                                        {{ $end ? \Carbon\Carbon::parse($end)->format('d M Y') : '' }}
                                        &nbsp;·&nbsp; KES equivalent
                                    </div>
                                </td>
                                <td style="vertical-align:middle; text-align:right; white-space:nowrap;">
                                    @php
                                        // Use the ALL/Totals segment row for the true portfolio net movement
                                        $totalsRow = $segments->first(fn($s) => strtoupper((string)($s->segment_code ?? '')) === 'ALL');
                                        if ($totalsRow) {
                                            $netAll = (float) ($totalsRow->movement ?? 0);
                                        } else {
                                            // Fallback: sum all individual segment rows
                                            $netAll = (float) $segments
                                                ->filter(fn($s) => strtoupper((string)($s->segment_code ?? '')) !== 'ALL')
                                                ->sum(fn($s) => (float)($s->movement ?? 0));
                                        }
                                        $netColor = $netAll >= 0 ? '#6EE7B7' : '#FCA5A5';
                                    @endphp
                                    <div style="display:inline-block; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); border-radius:10px; padding:10px 16px; text-align:right;">
                                        <div style="font-size:9px; font-weight:700; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:1px; margin-bottom:3px;">TOTAL NET MOVEMENT</div>
                                        <div style="font-size:18px; font-weight:900; color:{{ $netColor }}; font-family:'Courier New',ui-monospace,monospace; letter-spacing:-0.5px;">
                                            {{ $netAll >= 0 ? '+' : '−' }}{{ number_format((int) round(abs($netAll))) }}
                                        </div>
                                        <div style="font-size:9.5px; color:rgba(255,255,255,0.45); margin-top:2px;">
                                            {{ $cifGain->count() }} gainers &nbsp;·&nbsp; {{ $cifLoss->count() }} losers shown
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            {{-- ═══════════ END HEADER ═══════════ --}}

            {{-- ═══════════ CONTENT ═══════════ --}}
            <div style="padding:20px {{ $contentPadX }}px 24px;">

                {{-- ───── SEGMENT SUMMARY ───── --}}
                @if ($segments->isNotEmpty())
                    <div style="margin-bottom:10px;">
                        <span
                            style="font-size:11px; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:0.8px;">Segment
                            Overview</span>
                    </div>

                    {{-- Outlook-safe: border-radius/overflow on wrapping div, not the table --}}
                    <div style="border-radius:10px; overflow:hidden; border:1px solid #E2E8F0;">
                        <table width="100%" cellpadding="0" cellspacing="0"
                            style="width:100%; border-collapse:collapse; font-size:12px; background:#ffffff; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                            <thead>
                                <tr>
                                    <th style="padding:10px 12px; background:#F8FAFC; text-transform:uppercase; font-size:9.5px; letter-spacing:0.7px; font-weight:700; color:#64748B; border-bottom:1px solid #E2E8F0; text-align:left; width:22%;">
                                        Segment</th>
                                    <th style="padding:10px 12px; background:#F8FAFC; text-transform:uppercase; font-size:9.5px; letter-spacing:0.7px; font-weight:700; color:#64748B; border-bottom:1px solid #E2E8F0; text-align:right; width:14%;">
                                        {{ $start ? \Carbon\Carbon::parse($start)->format('d M') : 'Start' }}</th>
                                    <th style="padding:10px 12px; background:#F8FAFC; text-transform:uppercase; font-size:9.5px; letter-spacing:0.7px; font-weight:700; color:#64748B; border-bottom:1px solid #E2E8F0; text-align:right; width:14%;">
                                        {{ $end ? \Carbon\Carbon::parse($end)->format('d M') : 'End' }}</th>
                                    {{-- New LCY column --}}
                                    <th style="padding:10px 12px; background:#EFF6FF; text-transform:uppercase; font-size:9.5px; letter-spacing:0.7px; font-weight:700; color:#1D4ED8; border-bottom:1px solid #BFDBFE; text-align:right; width:16%;">
                                        LCY Mv</th>
                                    {{-- New FCY column --}}
                                    <th style="padding:10px 12px; background:#FFF7ED; text-transform:uppercase; font-size:9.5px; letter-spacing:0.7px; font-weight:700; color:#92400E; border-bottom:1px solid #FDE68A; text-align:right; width:16%;">
                                        FCY Mv</th>
                                    <th style="padding:10px 12px; background:#F8FAFC; text-transform:uppercase; font-size:9.5px; letter-spacing:0.7px; font-weight:700; color:#64748B; border-bottom:1px solid #E2E8F0; text-align:right; width:18%;">
                                        Net Movement</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($segments as $i => $s)
                                    @php
                                        $isLast   = $loop->last;
                                        $rowBg    = $i % 2 === 0 ? '#ffffff' : '#FAFBFD';
                                        $segName  = (string) ($s->segment_name ?? ($s->segment_code ?? '—'));
                                        $segCode  = strtoupper((string) ($s->segment_code ?? ''));
                                        $startBal = (float) ($s->start_balance ?? 0);
                                        $endBal   = (float) ($s->end_balance   ?? 0);
                                        $movement = (float) ($s->movement      ?? 0);
                                        $lcyMv    = (float) ($s->lcy_movement  ?? 0);
                                        $fcyMv    = (float) ($s->fcy_movement  ?? 0);
                                        $isGain   = $movement > 0;
                                        $startTxt = number_format((int) round($startBal));
                                        $endTxt   = number_format((int) round($endBal));
                                        $moveTxt  = number_format((int) round(abs($movement)));
                                        $lcyTxt   = number_format((int) round(abs($lcyMv)));
                                        $fcyTxt   = number_format((int) round(abs($fcyMv)));

                                        $badgeBg = '#EFF6FF'; $badgeBd = '#BFDBFE'; $badgeTx = '#1D4ED8';
                                        if ($segCode === 'CS') { $badgeBg = '#FEFCE8'; $badgeBd = '#FDE68A'; $badgeTx = '#92400E'; }
                                        if ($segCode === 'CM') { $badgeBg = '#F5F3FF'; $badgeBd = '#DDD6FE'; $badgeTx = '#5B21B6'; }
                                        if ($segCode === 'ALL'){ $badgeBg = '#F1F5F9'; $badgeBd = '#CBD5E1'; $badgeTx = '#475569'; }
                                    @endphp
                                    <tr style="background:{{ $rowBg }};">
                                        <td style="padding:10px 12px; {{ $isLast ? '' : 'border-bottom:1px solid #F1F5F9;' }}">
                                            <span style="display:inline-block; padding:3px 10px; border-radius:999px; background:{{ $badgeBg }}; border:1px solid {{ $badgeBd }}; color:{{ $badgeTx }}; font-weight:700; font-size:10.5px; letter-spacing:0.3px;">{{ $segName }}</span>
                                        </td>
                                        <td style="padding:10px 12px; {{ $isLast ? '' : 'border-bottom:1px solid #F1F5F9;' }} text-align:right; font-family:'Courier New',ui-monospace,monospace; font-size:12px; color:#334155; font-weight:600;">{{ $startTxt }}</td>
                                        <td style="padding:10px 12px; {{ $isLast ? '' : 'border-bottom:1px solid #F1F5F9;' }} text-align:right; font-family:'Courier New',ui-monospace,monospace; font-size:12px; color:#334155; font-weight:600;">{{ $endTxt }}</td>

                                        {{-- LCY Movement --}}
                                        <td style="padding:10px 12px; {{ $isLast ? '' : 'border-bottom:1px solid #EFF6FF;' }} text-align:right; background:{{ $i % 2 === 0 ? '#F8FBFF' : '#F0F6FF' }};">
                                            @if ($lcyMv > 0)
                                                <span style="font-family:'Courier New',ui-monospace,monospace; font-size:11.5px; font-weight:700; color:#15803D;">+{{ $lcyTxt }}</span>
                                            @elseif ($lcyMv < 0)
                                                <span style="font-family:'Courier New',ui-monospace,monospace; font-size:11.5px; font-weight:700; color:#BE123C;">−{{ $lcyTxt }}</span>
                                            @else
                                                <span style="font-family:'Courier New',ui-monospace,monospace; font-size:11.5px; color:#94A3B8;">0</span>
                                            @endif
                                        </td>

                                        {{-- FCY Movement --}}
                                        <td style="padding:10px 12px; {{ $isLast ? '' : 'border-bottom:1px solid #FEF3C7;' }} text-align:right; background:{{ $i % 2 === 0 ? '#FFFDF8' : '#FFF9F0' }};">
                                            @if ($fcyMv > 0)
                                                <span style="font-family:'Courier New',ui-monospace,monospace; font-size:11.5px; font-weight:700; color:#15803D;">+{{ $fcyTxt }}</span>
                                            @elseif ($fcyMv < 0)
                                                <span style="font-family:'Courier New',ui-monospace,monospace; font-size:11.5px; font-weight:700; color:#BE123C;">−{{ $fcyTxt }}</span>
                                            @else
                                                <span style="font-family:'Courier New',ui-monospace,monospace; font-size:11.5px; color:#94A3B8;">—</span>
                                            @endif
                                        </td>

                                        {{-- Net Movement --}}
                                        <td style="padding:10px 12px; {{ $isLast ? '' : 'border-bottom:1px solid #F1F5F9;' }} text-align:right;">
                                            @if ($isGain)
                                                <span style="display:inline-block; padding:4px 10px; border-radius:6px; font-weight:700; font-size:11.5px; background:#F0FDF4; color:#15803D; border:1px solid #BBF7D0; font-family:'Courier New',ui-monospace,monospace;">+{{ $moveTxt }}</span>
                                            @else
                                                <span style="display:inline-block; padding:4px 10px; border-radius:6px; font-weight:700; font-size:11.5px; background:#FFF1F2; color:#BE123C; border:1px solid #FECDD3; font-family:'Courier New',ui-monospace,monospace;">−{{ $moveTxt }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                {{-- ───── END SEGMENT SUMMARY ───── --}}


                {{-- ═══════════ CIF-ONLY ═══════════ --}}
                <div style="margin:{{ $sectionGap }}px 0 10px; padding-top:14px; border-top:1px solid #E2E8F0;">
                    <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt; mso-table-rspace:0pt;">
                        <tr>
                            <td style="vertical-align:middle; padding-right:10px;">
                                <div style="width:4px; height:18px; background:linear-gradient(180deg,#00B4D8 0%,#0077B6 100%); border-radius:2px;"></div>
                            </td>
                            <td>
                                <span style="font-size:14px; font-weight:800; color:#0F172A; letter-spacing:-0.2px;">CIF-Only Movers</span>
                                <span style="margin-left:8px; font-size:10px; color:#64748B; font-weight:500;">KES Equivalent · All Segments</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <table width="100%" cellpadding="0" cellspacing="0"
                    style="width:100%; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                    <tr>
                        <td width="50%" style="vertical-align:top; padding-right:7px;">
                            <div style="margin-bottom:7px;">
                                <span style="display:inline-block; font-size:10px; padding:4px 12px; border-radius:999px; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; background:#F0FDF4; border:1px solid #BBF7D0; color:#15803D;">▲ Gainers</span>
                            </div>
                            @include('emails.finance.partials.top_movers_table', [
                                'rows' => $cifGain->sortByDesc('movement'),
                                'start' => $start,
                                'end' => $end,
                                'showCurrency' => false,
                            ])
                        </td>
                        <td width="50%" style="vertical-align:top; padding-left:7px;">
                            <div style="margin-bottom:7px;">
                                <span style="display:inline-block; font-size:10px; padding:4px 12px; border-radius:999px; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; background:#FFF1F2; border:1px solid #FECDD3; color:#BE123C;">▼ Losers</span>
                            </div>
                            @include('emails.finance.partials.top_movers_table', [
                                'rows' => $cifLoss->sortBy('movement'),
                                'start' => $start,
                                'end' => $end,
                                'showCurrency' => false,
                            ])
                        </td>
                    </tr>
                </table>


                {{-- ═══════════ LCY / FCY ═══════════ --}}
                @foreach (['LCY', 'FCY'] as $ct)
                    <div style="margin:{{ $sectionGap }}px 0 10px; padding-top:14px; border-top:1px solid #E2E8F0;">
                        <table cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt; mso-table-rspace:0pt;">
                            <tr>
                                <td style="vertical-align:middle; padding-right:10px;">
                                    <div
                                        style="width:4px; height:18px; background:linear-gradient(180deg,#00B4D8 0%,#0077B6 100%); border-radius:2px;">
                                    </div>
                                </td>
                                <td><span
                                        style="font-size:14px; font-weight:800; color:#0F172A; letter-spacing:-0.2px;">{{ $ct }}
                                        Movers</span></td>
                            </tr>
                        </table>
                    </div>

                    <table width="100%" cellpadding="0" cellspacing="0"
                        style="width:100%; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                        <tr>
                            <td width="50%" style="vertical-align:top; padding-right:7px;">
                                <div style="margin-bottom:7px;">
                                    <span
                                        style="display:inline-block; font-size:10px; padding:4px 12px; border-radius:999px; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; background:#F0FDF4; border:1px solid #BBF7D0; color:#15803D;">▲
                                        Gainers</span>
                                </div>
                                {{-- ✅ Fixed: was incorrectly pulling from CIF_ONLY LOSS --}}
                                @include('emails.finance.partials.top_movers_table', [
                                    'rows' => ($grouped['CIF_CURRENCY'][$ct]['GAIN'] ?? collect())->sortByDesc(
                                        'movement'),
                                    'start' => $start,
                                    'end' => $end,
                                    'showCurrency' => true,
                                ])
                            </td>
                            <td width="50%" style="vertical-align:top; padding-left:7px;">
                                <div style="margin-bottom:7px;">
                                    <span
                                        style="display:inline-block; font-size:10px; padding:4px 12px; border-radius:999px; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; background:#FFF1F2; border:1px solid #FECDD3; color:#BE123C;">▼
                                        Losers</span>
                                </div>
                                @include('emails.finance.partials.top_movers_table', [
                                    'rows' => ($grouped['CIF_CURRENCY'][$ct]['LOSS'] ?? collect())->sortBy(
                                        'movement'),
                                    'start' => $start,
                                    'end' => $end,
                                    'showCurrency' => true,
                                ])
                            </td>
                        </tr>
                    </table>
                @endforeach

            </div>
            {{-- ═══════════ END CONTENT ═══════════ --}}

            {{-- ═══════════ FOOTER ═══════════ --}}
            <table width="100%" cellpadding="0" cellspacing="0"
                style="mso-table-lspace:0pt; mso-table-rspace:0pt; border-top:1px solid #E2E8F0;" bgcolor="#F8FAFC">
                <tr>
                    <td style="padding:12px {{ $contentPadX }}px; background:#F8FAFC;" bgcolor="#F8FAFC">
                        <table width="100%" cellpadding="0" cellspacing="0"
                            style="mso-table-lspace:0pt; mso-table-rspace:0pt;">
                            <tr>
                                <td style="vertical-align:middle;">
                                    <span style="font-size:11px; color:#94A3B8; font-weight:500;">
                                        <strong style="color:#475569; font-weight:700;">Ecobank</strong> · Automated
                                        Finance Report · Top Movers
                                    </span>
                                </td>
                                <td style="vertical-align:middle; text-align:right;">
                                    <span style="font-size:10px; color:#94A3B8; font-weight:500;">
                                        {{ $start && $end ? \Carbon\Carbon::parse($start)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($end)->format('d M Y') : '' }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            {{-- ═══════════ END FOOTER ═══════════ --}}

        </div>
    </div>

</body>

</html>
