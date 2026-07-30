{{--  resources\views\emails\finance\partials\loan_account_movers_table.blade.php  --}}
{{-- Expects: $branchCode, $branchName, $gainers (Collection), $losers (Collection), $pad, $font --}}
@php
    $hasGainers = isset($gainers) && $gainers->isNotEmpty();
    $hasLosers  = isset($losers)  && $losers->isNotEmpty();
    if (!$hasGainers && !$hasLosers) return;
@endphp

<div style="margin-bottom:14px;">
    {{-- Branch header --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt; mso-table-rspace:0pt; margin-bottom:6px;">
        <tr>
            <td style="vertical-align:middle;">
                <span style="display:inline-block; padding:3px 10px; border-radius:999px; background:#DCFCE7; border:1px solid #BBF7D0; font-size:10px; font-weight:900; color:#166534; letter-spacing:0.3px; text-transform:uppercase;">
                    {{ $branchCode }}
                </span>
                <span style="font-size:11.5px; font-weight:900; color:#166534; margin-left:6px; vertical-align:middle;">
                    {{ $branchName }}
                </span>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0"
           style="width:100%; border-collapse:separate; border-spacing:0; font-size:{{ $font ?? 11 }}px; border:1px solid #BBF7D0; border-radius:8px; overflow:hidden; background:#ffffff; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; table-layout:fixed; mso-table-lspace:0pt; mso-table-rspace:0pt;">

        <thead>
        <tr>
            <th style="padding:{{ $pad ?? 6 }}px; background:#DCFCE7; text-transform:uppercase; font-size:9px; letter-spacing:0.5px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:center; width:5%;">#</th>
            <th style="padding:{{ $pad ?? 6 }}px; background:#DCFCE7; text-transform:uppercase; font-size:9px; letter-spacing:0.5px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:left; width:20%;">Account</th>
            <th style="padding:{{ $pad ?? 6 }}px; background:#DCFCE7; text-transform:uppercase; font-size:9px; letter-spacing:0.5px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:left; width:25%;">Name</th>
            <th style="padding:{{ $pad ?? 6 }}px; background:#DCFCE7; text-transform:uppercase; font-size:9px; letter-spacing:0.5px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:right; width:17%;">Opening</th>
            <th style="padding:{{ $pad ?? 6 }}px; background:#DCFCE7; text-transform:uppercase; font-size:9px; letter-spacing:0.5px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:right; width:17%;">Closing</th>
            <th style="padding:{{ $pad ?? 6 }}px; background:#DCFCE7; text-transform:uppercase; font-size:9px; letter-spacing:0.5px; font-weight:900; color:#166534; border-bottom:2px solid #BBF7D0; text-align:right; width:16%;">Movement</th>
        </tr>
        </thead>

        <tbody>
        @if($hasGainers)
            {{-- Gainers sub-header --}}
            <tr>
                <td colspan="6" style="padding:4px 8px; background:#F0FDF4; border-bottom:1px solid #BBF7D0; font-size:9px; font-weight:900; color:#166534; text-transform:uppercase; letter-spacing:0.6px;">
                    ▲ Top Gainers
                </td>
            </tr>
            @foreach($gainers as $r)
                @php
                    $isLast = $loop->last && !$hasLosers;
                    $bg  = $loop->iteration % 2 === 0 ? '#F0FDF4' : '#ffffff';
                    $mv  = (float)($r->loan_movement ?? 0);
                    $mvTxt = number_format((int) round(abs($mv)), 0);
                @endphp
                <tr style="background:{{ $bg }};">
                    <td style="padding:{{ $pad ?? 6 }}px; border-bottom:{{ $isLast ? 'none' : '1px solid #E8F5E9' }}; text-align:center; font-weight:900; color:#166534;">{{ $r->rank ?? $loop->iteration }}</td>
                    <td style="padding:{{ $pad ?? 6 }}px; border-bottom:{{ $isLast ? 'none' : '1px solid #E8F5E9' }}; font-family:ui-monospace,'Courier New',monospace; font-size:10px; color:#166534; font-weight:700;">{{ $r->related_account ?? '—' }}</td>
                    <td style="padding:{{ $pad ?? 6 }}px; border-bottom:{{ $isLast ? 'none' : '1px solid #E8F5E9' }}; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px;">{{ $r->account_name ?? '—' }}</td>
                    <td style="padding:{{ $pad ?? 6 }}px; border-bottom:{{ $isLast ? 'none' : '1px solid #E8F5E9' }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:700; color:#374151;">{{ number_format((int) round((float)($r->loan_open ?? 0)), 0) }}</td>
                    <td style="padding:{{ $pad ?? 6 }}px; border-bottom:{{ $isLast ? 'none' : '1px solid #E8F5E9' }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:700; color:#374151;">{{ number_format((int) round((float)($r->loan_close ?? 0)), 0) }}</td>
                    <td style="padding:{{ $pad ?? 6 }}px; border-bottom:{{ $isLast ? 'none' : '1px solid #E8F5E9' }}; text-align:right;">
                        <span style="display:inline-block; padding:3px 7px; border-radius:6px; font-weight:900; font-size:10px; white-space:nowrap; background:#bbf7d0; color:#14532d; border:1px solid #86efac;">
                            ▲ {{ $mvTxt }}
                        </span>
                    </td>
                </tr>
            @endforeach
        @endif

        @if($hasLosers)
            {{-- Losers sub-header --}}
            <tr>
                <td colspan="6" style="padding:4px 8px; background:#FFF1F2; border-bottom:1px solid #FECDD3; border-top:{{ $hasGainers ? '2px solid #BBF7D0' : 'none' }}; font-size:9px; font-weight:900; color:#9F1239; text-transform:uppercase; letter-spacing:0.6px;">
                    ▼ Top Losers
                </td>
            </tr>
            @foreach($losers as $r)
                @php
                    $isLast = $loop->last;
                    $bg  = $loop->iteration % 2 === 0 ? '#FFF8F8' : '#ffffff';
                    $mv  = (float)($r->loan_movement ?? 0);
                    $mvTxt = number_format((int) round(abs($mv)), 0);
                @endphp
                <tr style="background:{{ $bg }};">
                    <td style="padding:{{ $pad ?? 6 }}px; border-bottom:{{ $isLast ? 'none' : '1px solid #FCE7E7' }}; text-align:center; font-weight:900; color:#9F1239;">{{ $r->rank ?? $loop->iteration }}</td>
                    <td style="padding:{{ $pad ?? 6 }}px; border-bottom:{{ $isLast ? 'none' : '1px solid #FCE7E7' }}; font-family:ui-monospace,'Courier New',monospace; font-size:10px; color:#9F1239; font-weight:700;">{{ $r->related_account ?? '—' }}</td>
                    <td style="padding:{{ $pad ?? 6 }}px; border-bottom:{{ $isLast ? 'none' : '1px solid #FCE7E7' }}; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px;">{{ $r->account_name ?? '—' }}</td>
                    <td style="padding:{{ $pad ?? 6 }}px; border-bottom:{{ $isLast ? 'none' : '1px solid #FCE7E7' }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:700; color:#374151;">{{ number_format((int) round((float)($r->loan_open ?? 0)), 0) }}</td>
                    <td style="padding:{{ $pad ?? 6 }}px; border-bottom:{{ $isLast ? 'none' : '1px solid #FCE7E7' }}; text-align:right; font-family:ui-monospace,'Courier New',monospace; font-weight:700; color:#374151;">{{ number_format((int) round((float)($r->loan_close ?? 0)), 0) }}</td>
                    <td style="padding:{{ $pad ?? 6 }}px; border-bottom:{{ $isLast ? 'none' : '1px solid #FCE7E7' }}; text-align:right;">
                        <span style="display:inline-block; padding:3px 7px; border-radius:6px; font-weight:900; font-size:10px; white-space:nowrap; background:#fecaca; color:#7f1d1d; border:1px solid #fca5a5;">
                            ▼ {{ $mvTxt }}
                        </span>
                    </td>
                </tr>
            @endforeach
        @endif
        </tbody>
    </table>
</div>
