{{--  resources\views\emails\finance\dsa_account_opening_report.blade.php  --}}

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body style="margin:0; padding:0; background:#EDEDED; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; color:#2a2a2a; -webkit-font-smoothing:antialiased;">

@php
    $t        = $report['totals'];
    $dsaRows  = $report['dsaRows'];
    $months   = $report['months'];
    $weeks    = $report['weeks'];
    $topRows  = $dsaRows->take($topLimit);
    $fmtDate  = fn ($d) => \Carbon\Carbon::parse($d)->format('d M Y');

    $money         = fn ($v) => number_format((float) round($v));
    $activeDsasMtd = $dsaRows->where('mtd_accounts', '>', 0)->count();

    $tiles = [
        [
            'label' => 'Accounts WTD',
            'value' => number_format($t->wtd_accounts),
            'sub'   => number_format($t->wtd_customers) . ' customers',
            'fg'    => '#005B82', 'bg' => '#E8F4FB', 'border' => '#B3D9ED',
        ],
        [
            'label' => 'Accounts MTD',
            'value' => number_format($t->mtd_accounts),
            'sub'   => number_format($t->mtd_customers) . ' customers · ' . $activeDsasMtd . ' active DSAs',
            'fg'    => '#4a6a1a', 'bg' => '#F4FAD4', 'border' => '#D0E06B',
        ],
        [
            'label' => 'DSA Deposits',
            'value' => $money($t->deposits),
            'sub'   => number_format($t->book_accounts) . ' accounts, all time',
            'fg'    => '#4527A0', 'bg' => '#F3EFFB', 'border' => '#D1C4E9',
        ],
        [
            'label' => 'DSA Customer Loans',
            'value' => $money($t->loans),
            'sub'   => number_format($t->book_customers) . ' customers, performing book',
            'fg'    => '#14532d', 'bg' => '#DCFCE7', 'border' => '#86EFAC',
        ],
    ];

    $th = 'padding:7px 8px; font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:0.4px; color:#ffffff; background:#005B82; white-space:nowrap;';
    $td = 'padding:6px 8px; font-size:11.5px; border-bottom:1px solid #EDEDED; white-space:nowrap;';
    $tdTotal = 'padding:7px 8px; font-size:11.5px; font-weight:900; background:#E2E8F0; white-space:nowrap;';
@endphp

<div style="max-width:1000px; margin:0 auto; padding:16px 10px;">

    <div style="background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 3px 10px rgba(0,0,0,0.07); border:1px solid #E0E0E0;">

        {{-- HEADER --}}
        <table width="100%" cellpadding="0" cellspacing="0" bgcolor="#005B82" style="width:100%; background:#005B82; mso-table-lspace:0pt; mso-table-rspace:0pt;">
            <tr>
                <td style="padding:18px 22px 16px; background:#005B82;" bgcolor="#005B82">
                    <div style="font-size:18px; font-weight:900; letter-spacing:-0.2px; margin:0 0 4px 0; color:#ffffff;">
                        DSA Account Opening Report
                    </div>
                    <div style="font-size:11px; font-weight:600; color:#ccecf7;">
                        Accounts opened by Direct Sales Agents (introducer CN…) — Finance Analytics
                    </div>
                    <div style="margin-top:10px;">
                        <span style="display:inline-block; padding:4px 10px; border-radius:999px; background:rgba(255,255,255,0.16); border:1px solid rgba(255,255,255,0.28); font-size:11px; font-weight:800; color:#ffffff; white-space:nowrap;">
                            As at&nbsp;<strong style="font-weight:900; color:#BED600;">{{ $fmtDate($report['asOf']) }}</strong>
                            &nbsp;·&nbsp; WTD from <strong style="font-weight:900; color:#BED600;">{{ $fmtDate($report['wtdStart']) }}</strong>
                            &nbsp;·&nbsp; MTD from <strong style="font-weight:900; color:#BED600;">{{ $fmtDate($report['mtdStart']) }}</strong>
                        </span>
                    </div>
                </td>
            </tr>
        </table>

        {{-- CONTENT --}}
        <div style="padding:16px 22px 18px;">

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

            {{-- SECTION: WEEK ON WEEK (all DSAs) --}}
            <div style="margin:0 0 10px; padding-bottom:8px; border-bottom:2px solid #E0E0E0;">
                <span style="display:inline-block; width:5px; height:16px; background:linear-gradient(180deg,#BED600 0%,#669438 100%); border-radius:3px; vertical-align:middle; margin-right:8px;"></span>
                <span style="font-size:14px; font-weight:900; color:#005B82; letter-spacing:-0.15px;">Week on Week</span>
                <span style="font-size:11px; font-weight:700; color:#979797;"> — accounts opened, all DSAs (Mon–Sun weeks)</span>
            </div>

            <table width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse; margin:0 0 20px;">
                <tr>
                    @foreach ($weeks as $w)
                        <th style="{{ $th }} text-align:right;">{{ $w['label'] }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($weeks as $key => $w)
                        <td style="{{ $tdTotal }} text-align:right;">{{ number_format($t->weeks[$key]) }}</td>
                    @endforeach
                </tr>
            </table>

            {{-- SECTION: DSA TABLE --}}
            <div style="margin:0 0 10px; padding-bottom:8px; border-bottom:2px solid #E0E0E0;">
                <span style="display:inline-block; width:5px; height:16px; background:linear-gradient(180deg,#BED600 0%,#669438 100%); border-radius:3px; vertical-align:middle; margin-right:8px;"></span>
                <span style="font-size:14px; font-weight:900; color:#005B82; letter-spacing:-0.15px;">Accounts Opened by DSA</span>
                <span style="font-size:11px; font-weight:700; color:#979797;">
                    — top {{ min($topLimit, $dsaRows->count()) }} of {{ $dsaRows->count() }} DSAs by MTD; all DSAs in the attachment
                </span>
            </div>

            @if ($dsaRows->isEmpty())
                <div style="font-size:12px; color:#646464; padding:12px; background:#F5F5F5; border-radius:8px;">
                    No DSA-introduced accounts found.
                </div>
            @else
                <table width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse;">
                    <tr>
                        <th style="{{ $th }} text-align:left;">DSA Code</th>
                        <th style="{{ $th }} text-align:right;">WTD</th>
                        <th style="{{ $th }} text-align:right;">MTD</th>
                        <th style="{{ $th }} text-align:right;">Accounts<br><span style="font-weight:700; opacity:0.8;">all time</span></th>
                        <th style="{{ $th }} text-align:right;">Deposits<br><span style="font-weight:700; opacity:0.8;">{{ $report['depositDate'] ? $fmtDate($report['depositDate']) : '' }}</span></th>
                        <th style="{{ $th }} text-align:right;">Loans<br><span style="font-weight:700; opacity:0.8;">{{ $report['loanDate'] ? $fmtDate($report['loanDate']) : '' }}</span></th>
                    </tr>
                    @foreach ($topRows as $d)
                        <tr style="background:{{ $loop->even ? '#FAFAFA' : '#ffffff' }};">
                            <td style="{{ $td }} font-weight:800; color:#005B82;">{{ $d->dsa_code }}</td>
                            <td style="{{ $td }} text-align:right;">{{ number_format($d->wtd_accounts) }}</td>
                            <td style="{{ $td }} text-align:right; font-weight:800;">{{ number_format($d->mtd_accounts) }}</td>
                            <td style="{{ $td }} text-align:right;">{{ number_format($d->book_accounts) }}</td>
                            <td style="{{ $td }} text-align:right;">{{ $money($d->deposits) }}</td>
                            <td style="{{ $td }} text-align:right;">{{ $money($d->loans) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td style="{{ $tdTotal }}">TOTAL (all DSAs)</td>
                        <td style="{{ $tdTotal }} text-align:right;">{{ number_format($t->wtd_accounts) }}</td>
                        <td style="{{ $tdTotal }} text-align:right;">{{ number_format($t->mtd_accounts) }}</td>
                        <td style="{{ $tdTotal }} text-align:right;">{{ number_format($t->book_accounts) }}</td>
                        <td style="{{ $tdTotal }} text-align:right;">{{ $money($t->deposits) }}</td>
                        <td style="{{ $tdTotal }} text-align:right;">{{ $money($t->loans) }}</td>
                    </tr>
                </table>
                <div style="font-size:10px; color:#979797; margin-top:6px;">
                    Deposits = latest LCY balance on every account the DSA has opened. Loans = performing, non-corporate book of those customers (by CIF); a customer brought in by two DSAs counts towards both, but once in the total.
                </div>
            @endif

            {{-- Notes --}}
            {{--  <div style="font-size:11px; color:#646464; margin-top:16px; padding:10px 12px; background:#f9fbe8; border:1px solid #d8e870; border-left:4px solid #BED600; border-radius:8px; line-height:1.55;">
                <strong style="color:#2a2a2a; font-weight:900;">Notes:</strong>
                DSA = account introducer code starting with <strong>CN</strong>; counts are accounts by open date.
                Staff accounts (KECATF) are excluded. Based on the latest customer accounts import, so accounts closed since opening are not counted.
                The attached Excel has Summary, Month on Month, Week on Week and the account-level detail (CIF, account, customer name, branch).
            </div>  --}}

        </div>

        {{-- FOOTER --}}
        <div style="padding:10px 22px; font-size:10.5px; color:#979797; background:#EDEDED; border-top:1px solid #E0E0E0;">
            <strong style="color:#2a2a2a;">Ecobank</strong> — Automated Finance Report · DSA Account Opening
        </div>

    </div>
</div>

</body>
</html>
