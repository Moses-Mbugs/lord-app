{{--  resources\views\emails\finance\branch_movers_report.blade.php  --}}

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
                                    Branch Movers Report
                                </div>
                                <div style="font-size:11px; font-weight:600; color:#ccecf7;">
                                    Branch-level balance movement — Finance Analytics
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

            {{-- SECTION: SUMMARY --}}
            <div style="margin:0 0 10px; padding-bottom:8px; border-bottom:2px solid #E0E0E0;">
                <table width="100%" cellpadding="0" cellspacing="0" style="width:100%; mso-table-lspace:0pt; mso-table-rspace:0pt;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <span style="display:inline-block; width:5px; height:16px; background:linear-gradient(180deg,#BED600 0%,#669438 100%); border-radius:3px; vertical-align:middle; margin-right:8px;"></span>
                            <span style="font-size:14px; font-weight:900; color:#005B82; letter-spacing:-0.15px;">Branch Movement Summary</span>
                            <span style="font-size:11px; font-weight:700; color:#979797;"> — LCY equivalent (P50 excluded)</span>
                        </td>
                    </tr>
                </table>
            </div>

            @include('emails.finance.partials.branch_movers_table', [
                'rows'  => $summaryRows ?? collect(),
                'start' => $start,
                'end'   => $end,
                'compact' => false,
                'showRank'=> false,
            ])


            {{-- Notes --}}
            <div style="font-size:11px; color:#646464; margin-top:{{ $sectionGap }}px; padding:10px 12px; background:#f9fbe8; border:1px solid #d8e870; border-left:4px solid #BED600; border-radius:8px; line-height:1.55;">
                <strong style="color:#2a2a2a; font-weight:900;">Notes:</strong>
                Movement = <span style="background:rgba(0,0,0,0.06); padding:2px 5px; border-radius:4px; font-family:ui-monospace,'Courier New',monospace; font-size:10.5px;">end_balance − start_balance</span>.
                Values are rounded for readability. P50 is excluded.
            </div>

        </div>

        {{-- FOOTER --}}
        <div style="padding:10px {{ $contentPadX }}px; font-size:10.5px; color:#979797; background:#EDEDED; border-top:1px solid #E0E0E0;">
            <table width="100%" cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt; mso-table-rspace:0pt;">
                <tr>
                    <td style="vertical-align:middle;">
                        <strong style="color:#2a2a2a;">Ecobank</strong> — Automated Finance Report · Branch Movers
                    </td>
                    <td style="vertical-align:middle; text-align:right;">
                        {{--  <span style="display:inline-block; background:linear-gradient(135deg,#005B82 0%,#0082BB 100%); color:#ffffff; padding:4px 10px; border-radius:999px; font-size:9.5px; font-weight:900; letter-spacing:0.6px; text-transform:uppercase;">
                            Automated
                        </span>  --}}
                    </td>
                </tr>
            </table>
        </div>

    </div>
</div>

</body>
</html>
