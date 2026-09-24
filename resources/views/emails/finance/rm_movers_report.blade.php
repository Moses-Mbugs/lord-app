{{--  resources\views\emails\finance\rm_movers_report.blade.php  --}}

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body style="margin:0; padding:0; background:#EDEDED; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; color:#2a2a2a; -webkit-font-smoothing:antialiased;">

@php
    $wrapPadY   = 16;
    $wrapPadX   = 10;

    $headerPadT = 18;
    $headerPadX = 22;
    $headerPadB = 16;

    $contentPadT = 16;
    $contentPadX = 22;
    $contentPadB = 18;

    $sectionGap  = 12;
@endphp

<div style="max-width:1000px; margin:0 auto; padding:{{ $wrapPadY }}px {{ $wrapPadX }}px;">

    <div style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 3px 10px rgba(0,0,0,0.07); border:1px solid #E0E0E0;">

        {{-- HEADER --}}
        <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#005B82" style="width:100%; background:#005B82; mso-table-lspace:0pt; mso-table-rspace:0pt;">
            <tr>
                <td style="padding:{{ $headerPadT }}px {{ $headerPadX }}px {{ $headerPadB }}px; background:#005B82;" bgcolor="#005B82">

                    <table width="100%" cellpadding="0" cellspacing="0" style="width:100%; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                        <tr>
                            <td style="vertical-align:middle;">
                                <div style="font-size:18px; font-weight:900; letter-spacing:-0.2px; margin:0 0 4px 0; color:#ffffff;">
                                    RM Movers Report
                                </div>
                                <div style="font-size:11px; font-weight:600; color:#ccecf7;">
                                    Relationship Manager portfolio, deposit &amp; loan movement — Finance Analytics
                                </div>
                            </td>
                            <td style="vertical-align:middle; text-align:right; white-space:nowrap; padding-left:12px;"></td>
                        </tr>
                    </table>

                    <table width="100%" cellpadding="0" cellspacing="0" style="width:100%; margin-top:10px; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                        <tr>
                            <td>
                                <span style="display:inline-block; padding:4px 10px; border-radius:999px; background:rgba(255,255,255,0.16); border:1px solid rgba(255,255,255,0.28); font-size:11px; font-weight:800; color:#ffffff; white-space:nowrap;">
                                    Period:&nbsp;
                                    <strong style="font-weight:900; color:#BED600;">{{ \Carbon\Carbon::parse($start)->format('d M Y') }}</strong>
                                    &nbsp;→&nbsp;
                                    <strong style="font-weight:900; color:#BED600;">{{ \Carbon\Carbon::parse($end)->format('d M Y') }}</strong>
                                </span>
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>

        {{-- CONTENT --}}
        <div style="padding:{{ $contentPadT }}px {{ $contentPadX }}px {{ $contentPadB }}px;">

            @php
                $t = $totals ?? (object) [];
                $depMv = (float) ($t->movement ?? 0);
                $loanMv = (float) ($t->loan_movement ?? 0);
                $depGain = $depMv >= 0;
                $loanGain = $loanMv >= 0;

                $tiles = [
                    [
                        'label' => 'Customers',
                        'value' => number_format((int) ($t->cif_count ?? 0)),
                        'sub'   => number_format((int) ($t->total_accounts ?? 0)) . ' accounts',
                        'fg'    => '#4527A0', 'bg' => '#F3EFFB', 'border' => '#D1C4E9',
                    ],
                    [
                        'label' => 'Accounts Managed',
                        'value' => number_format((int) ($t->total_accounts ?? 0)),
                        'sub'   => 'across ' . ($rmRows ?? collect())->count() . ' RMs',
                        'fg'    => '#005B82', 'bg' => '#E8F4FB', 'border' => '#B3D9ED',
                    ],
                    [
                        'label' => 'Deposit Movement',
                        'value' => ($depGain ? '▲ ' : '▼ ') . number_format((int) round(abs($depMv))),
                        'sub'   => 'close ' . number_format((int) round($t->end_balance ?? 0)),
                        'fg'    => $depGain ? '#4a6a1a' : '#a11818',
                        'bg'    => $depGain ? '#F4FAD4' : '#FFF0F0',
                        'border'=> $depGain ? '#D0E06B' : '#FFB3B3',
                    ],
                    [
                        'label' => 'Loan Movement',
                        'value' => ($loanGain ? '▲ ' : '▼ ') . number_format((int) round(abs($loanMv))),
                        'sub'   => 'close ' . number_format((int) round($t->loan_close ?? 0)),
                        'fg'    => $loanGain ? '#14532d' : '#7f1d1d',
                        'bg'    => $loanGain ? '#DCFCE7' : '#FEF2F2',
                        'border'=> $loanGain ? '#86EFAC' : '#FCA5A5',
                    ],
                ];
            @endphp

            {{-- KPI STRIP --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="width:100%; margin:0 0 16px; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                <tr>
                    @foreach ($tiles as $tile)
                        <td width="25%" style="padding:0 {{ $loop->first ? 0 : 4 }}px 0 {{ $loop->last ? 0 : 4 }}px; vertical-align:top;">
                            <div style="background:{{ $tile['bg'] }}; border:1px solid {{ $tile['border'] }}; border-radius:10px; padding:10px 10px;">
                                <div style="font-size:8.5px; text-transform:uppercase; letter-spacing:0.6px; font-weight:900; color:{{ $tile['fg'] }}; opacity:0.85; margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $tile['label'] }}
                                </div>
                                <div style="font-size:17px; font-weight:900; color:{{ $tile['fg'] }}; line-height:1.15; white-space:nowrap;">
                                    {{ $tile['value'] }}
                                </div>
                                <div style="font-size:9.5px; font-weight:700; color:{{ $tile['fg'] }}; opacity:0.7; margin-top:2px;">
                                    {{ $tile['sub'] }}
                                </div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>

            {{-- SECTION: RM SUMMARY --}}
            <div style="margin:0 0 10px; padding-bottom:8px; border-bottom:2px solid #E0E0E0;">
                <table width="100%" cellpadding="0" cellspacing="0" style="width:100%; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <span style="display:inline-block; width:5px; height:16px; background:linear-gradient(180deg,#BED600 0%,#669438 100%); border-radius:3px; vertical-align:middle; margin-right:8px;"></span>
                            <span style="font-size:14px; font-weight:900; color:#005B82; letter-spacing:-0.15px;">RM Portfolio &amp; Movement</span>
                            <span style="font-size:11px; font-weight:700; color:#979797;"> — LCY equivalent, deposits P50 excluded</span>
                        </td>
                    </tr>
                </table>
            </div>

            @include('emails.finance.partials.rm_movers_table', [
                'rows'   => $rmRows ?? collect(),
                'totals' => $totals ?? null,
                'start'  => $start,
                'end'    => $end,
            ])

            {{-- SECTION: TOP CUSTOMER GAINERS --}}
            <div style="margin:{{ $sectionGap }}px 0 10px; padding-bottom:8px; border-bottom:2px solid #E0E0E0;">
                <span style="display:inline-block; width:5px; height:16px; background:linear-gradient(180deg,#86EFAC 0%,#14532d 100%); border-radius:3px; vertical-align:middle; margin-right:8px;"></span>
                <span style="font-size:14px; font-weight:900; color:#005B82; letter-spacing:-0.15px;">Top Deposit Gainers</span>
                <span style="font-size:11px; font-weight:700; color:#979797;"> — customers, across the listed RMs</span>
            </div>

            @include('emails.finance.partials.rm_movers_drivers_table', [
                'rows' => $topGainers ?? collect(),
            ])

            {{-- SECTION: TOP CUSTOMER LOSERS --}}
            <div style="margin:{{ $sectionGap }}px 0 10px; padding-bottom:8px; border-bottom:2px solid #E0E0E0;">
                <span style="display:inline-block; width:5px; height:16px; background:linear-gradient(180deg,#fca5a5 0%,#7f1d1d 100%); border-radius:3px; vertical-align:middle; margin-right:8px;"></span>
                <span style="font-size:14px; font-weight:900; color:#005B82; letter-spacing:-0.15px;">Top Deposit Losers</span>
                <span style="font-size:11px; font-weight:700; color:#979797;"> — customers, across the listed RMs</span>
            </div>

            @include('emails.finance.partials.rm_movers_drivers_table', [
                'rows' => $topLosers ?? collect(),
            ])

            {{-- Notes --}}
            <div style="font-size:11px; color:#646464; margin-top:{{ $sectionGap }}px; padding:10px 12px; background:#f9fbe8; border:1px solid #d8e870; border-left:4px solid #BED600; border-radius:8px; line-height:1.55;">
                <strong style="color:#2a2a2a; font-weight:900;">Notes:</strong>
                Deposit/Loan Movement = <span style="background:rgba(0,0,0,0.06); padding:2px 5px; border-radius:4px; font-family:ui-monospace,'Courier New',monospace; font-size:10.5px;">close − open</span>.
                Customers/Accounts reflect the latest portfolio snapshot (not date-scoped). Loans shown are performing book only (Normal/Watch/OAEM/Substandard, non-Corporate).
                Values are rounded for readability. P50 branch and GL 216220001 are excluded from deposits. This report is currently scoped to a fixed RM portfolio list.
            </div>

        </div>

        {{-- FOOTER --}}
        <div style="padding:10px {{ $contentPadX }}px; font-size:10.5px; color:#979797; background:#EDEDED; border-top:1px solid #E0E0E0;">
            <table width="100%" cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt; mso-table-rspace:0pt;">
                <tr>
                    <td style="vertical-align:middle;">
                        <strong style="color:#2a2a2a;">Ecobank</strong> — Automated Finance Report · RM Movers
                    </td>
                    <td style="vertical-align:middle; text-align:right;"></td>
                </tr>
            </table>
        </div>

    </div>
</div>

</body>
</html>
