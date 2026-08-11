@php
    $rows       = $rows       ?? collect();
    $start      = $start      ?? null;
    $end        = $end        ?? null;
    $showCurrency = $showCurrency ?? false;

    $font = 11.5;

    if ($showCurrency) {
        $wCustomer = 27; $wBr = 8; $wCcy = 8; $wSub = 10;
        $wStart = 16; $wEnd = 16; $wMove = 15;
        $totalCols = 7;
    } else {
        $wCustomer = 34; $wBr = 9; $wSub = 11;
        $wStart = 17; $wEnd = 17; $wMove = 12;
        $totalCols = 6;
    }
@endphp

@if ($rows->isEmpty())
    <div style="text-align:center; padding:20px 12px; background:#FAFBFD; border:1px solid #E2E8F0; border-radius:8px;">
        <div style="font-size:22px; margin-bottom:6px; opacity:0.4;">◌</div>
        <div style="font-size:11px; font-weight:600; color:#64748B; margin-bottom:2px;">No movers</div>
        <div style="font-size:10.5px; color:#94A3B8;">No qualifying movements in this period.</div>
    </div>
@else
    @php
        $totalStart  = $rows->sum(fn($r) => (float)($r->start_balance ?? 0));
        $totalEnd    = $rows->sum(fn($r) => (float)($r->end_balance   ?? 0));
        $totalMove   = $rows->sum(fn($r) => (float)($r->movement      ?? 0));
        $isGainTotal = $totalMove >= 0;
    @endphp

    <table width="100%" cellpadding="0" cellspacing="0"
        style="width:100%; border-collapse:collapse; font-size:{{ $font }}px; border:1px solid #E2E8F0; border-radius:8px; overflow:hidden; background:#ffffff; table-layout:fixed; mso-table-lspace:0pt; mso-table-rspace:0pt; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
        <thead>
            <tr>
                <th style="padding:8px 10px; background:#F8FAFC; text-transform:uppercase; font-size:9px; letter-spacing:0.7px; font-weight:700; color:#64748B; border-bottom:1px solid #E2E8F0; text-align:left; width:{{ $wCustomer }}%;">
                    Customer</th>
                <th style="padding:8px 10px; background:#F8FAFC; text-transform:uppercase; font-size:9px; letter-spacing:0.7px; font-weight:700; color:#64748B; border-bottom:1px solid #E2E8F0; text-align:center; width:{{ $wBr }}%;">
                    Br</th>
                @if ($showCurrency)
                    <th style="padding:8px 10px; background:#F8FAFC; text-transform:uppercase; font-size:9px; letter-spacing:0.7px; font-weight:700; color:#64748B; border-bottom:1px solid #E2E8F0; text-align:center; width:{{ $wCcy }}%;">
                        CCY</th>
                @endif
                <th style="padding:8px 10px; background:#F8FAFC; text-transform:uppercase; font-size:9px; letter-spacing:0.7px; font-weight:700; color:#64748B; border-bottom:1px solid #E2E8F0; text-align:center; width:{{ $wSub }}%;">
                    Sub Seg</th>
                <th style="padding:8px 10px; background:#F8FAFC; text-transform:uppercase; font-size:9px; letter-spacing:0.7px; font-weight:700; color:#64748B; border-bottom:1px solid #E2E8F0; text-align:right; width:{{ $wStart }}%;">
                    {{ $start ? \Carbon\Carbon::parse($start)->format('d M') : 'Start' }}</th>
                <th style="padding:8px 10px; background:#F8FAFC; text-transform:uppercase; font-size:9px; letter-spacing:0.7px; font-weight:700; color:#64748B; border-bottom:1px solid #E2E8F0; text-align:right; width:{{ $wEnd }}%;">
                    {{ $end ? \Carbon\Carbon::parse($end)->format('d M') : 'End' }}</th>
                <th style="padding:8px 10px; background:#F8FAFC; text-transform:uppercase; font-size:9px; letter-spacing:0.7px; font-weight:700; color:#64748B; border-bottom:1px solid #E2E8F0; text-align:right; width:{{ $wMove }}%;">
                    Move</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $r)
                @php
                    $movement = (float) ($r->movement ?? 0);
                    $isGain   = $movement > 0;
                    $branch   = $r->branch_code   ?? '—';
                    $custName = $r->customer_name ?? '—';
                    $ccy      = $r->currency      ?? '—';
                    $subSeg   = $r->sub_segment   ?? null;
                    $subSeg   = ($subSeg && $subSeg !== 'UNMAPPED') ? $subSeg : '—';
                    $rowBg    = $i % 2 === 0 ? '#ffffff' : '#FAFBFD';
                    $startTxt = number_format((int) round((float) ($r->start_balance ?? 0)));
                    $endTxt   = number_format((int) round((float) ($r->end_balance   ?? 0)));
                    $moveTxt  = number_format((int) round(abs($movement)));
                @endphp
                <tr style="background:{{ $rowBg }};">

                    <td style="padding:8px 10px; border-bottom:1px solid #F1F5F9; color:#1E293B; font-size:{{ $font }}px; font-weight:500; line-height:1.3;">
                        <div title="{{ $custName }}" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $custName }}</div>
                    </td>

                    <td style="padding:8px 10px; border-bottom:1px solid #F1F5F9; text-align:center;">
                        <span style="display:inline-block; padding:2px 7px; border-radius:999px; background:#EFF6FF; border:1px solid #BFDBFE; color:#1D4ED8; font-weight:700; font-size:9px; letter-spacing:0.25px;">{{ $branch }}</span>
                    </td>

                    @if ($showCurrency)
                        <td style="padding:8px 10px; border-bottom:1px solid #F1F5F9; text-align:center;">
                            <span style="display:inline-block; padding:2px 7px; border-radius:999px; background:#FEFCE8; border:1px solid #FDE68A; color:#92400E; font-weight:700; font-size:9px; letter-spacing:0.3px; text-transform:uppercase; min-width:28px;">{{ $ccy }}</span>
                        </td>
                    @endif

                    <td style="padding:8px 10px; border-bottom:1px solid #F1F5F9; text-align:center;">
                        <span style="display:inline-block; padding:2px 7px; border-radius:999px; background:#F5F3FF; border:1px solid #DDD6FE; color:#5B21B6; font-weight:700; font-size:9px; letter-spacing:0.25px; text-transform:uppercase;">{{ $subSeg }}</span>
                    </td>

                    <td style="padding:8px 10px; border-bottom:1px solid #F1F5F9; text-align:right; font-family:'Courier New',ui-monospace,monospace; font-size:{{ $font }}px; color:#475569; font-weight:600; white-space:nowrap;">{{ $startTxt }}</td>

                    <td style="padding:8px 10px; border-bottom:1px solid #F1F5F9; text-align:right; font-family:'Courier New',ui-monospace,monospace; font-size:{{ $font }}px; color:#475569; font-weight:600; white-space:nowrap;">{{ $endTxt }}</td>

                    <td style="padding:8px 10px; border-bottom:1px solid #F1F5F9; text-align:right;">
                        @if ($isGain)
                            <span style="display:inline-block; padding:3px 8px; border-radius:6px; font-weight:700; font-size:{{ $font }}px; white-space:nowrap; background:#F0FDF4; color:#15803D; border:1px solid #BBF7D0; font-family:'Courier New',ui-monospace,monospace;">+{{ $moveTxt }}</span>
                        @else
                            <span style="display:inline-block; padding:3px 8px; border-radius:6px; font-weight:700; font-size:{{ $font }}px; white-space:nowrap; background:#FFF1F2; color:#BE123C; border:1px solid #FECDD3; font-family:'Courier New',ui-monospace,monospace;">−{{ $moveTxt }}</span>
                        @endif
                    </td>

                </tr>
            @endforeach

            {{-- Subtotal row (requested: totals below the last shown row) --}}
            @php $totMoveTxt = number_format((int) round(abs($totalMove))); @endphp
            <tr style="background:#F1F5F9;">
                <td colspan="{{ $showCurrency ? 4 : 3 }}" style="padding:8px 10px; border-top:2px solid #CBD5E1; font-size:10px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">
                    Subtotal — {{ $rows->count() }} shown
                </td>
                <td style="padding:8px 10px; border-top:2px solid #CBD5E1; text-align:right; font-family:'Courier New',ui-monospace,monospace; font-size:11px; color:#334155; font-weight:700;">
                    {{ number_format((int) round($totalStart)) }}
                </td>
                <td style="padding:8px 10px; border-top:2px solid #CBD5E1; text-align:right; font-family:'Courier New',ui-monospace,monospace; font-size:11px; color:#334155; font-weight:700;">
                    {{ number_format((int) round($totalEnd)) }}
                </td>
                <td style="padding:8px 10px; border-top:2px solid #CBD5E1; text-align:right;">
                    @if ($isGainTotal)
                        <span style="display:inline-block; padding:3px 8px; border-radius:6px; font-weight:800; font-size:11px; white-space:nowrap; background:#F0FDF4; color:#15803D; border:1px solid #BBF7D0; font-family:'Courier New',ui-monospace,monospace;">+{{ $totMoveTxt }}</span>
                    @else
                        <span style="display:inline-block; padding:3px 8px; border-radius:6px; font-weight:800; font-size:11px; white-space:nowrap; background:#FFF1F2; color:#BE123C; border:1px solid #FECDD3; font-family:'Courier New',ui-monospace,monospace;">−{{ $totMoveTxt }}</span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
@endif
