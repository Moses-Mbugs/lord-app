@php
    $rows = $rows ?? collect();
@endphp

@if ($rows->isEmpty())
    <div class="empty">No rows found for this bucket.</div>
@else
    <table>
        <thead>
        <tr>
            <th style="width: 22%;">Customer Name</th>
            <th style="width: 10%;">CIF</th>
            <th style="width: 12%;">Account</th>
            <th style="width: 8%;">Branch</th>
            <th style="width: 7%;">CCY</th>
            <th style="width: 13%;">Start</th>
            <th style="width: 13%;">End</th>
            <th style="width: 15%;">Movement</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $r)
            <tr>
                <td>{{ $r->customer_name ?? '' }}</td>
                <td>{{ $r->cif ?? '' }}</td>
                <td>{{ $r->cust_ac_no ?? '' }}</td>
                <td>{{ $r->branch_code ?? '' }}</td>
                <td>{{ $r->currency ?? '' }}</td>
                <td class="num">{{ number_format((float)($r->start_balance ?? 0), 2) }}</td>
                <td class="num">{{ number_format((float)($r->end_balance ?? 0), 2) }}</td>
                <td class="num"><strong>{{ number_format((float)($r->movement ?? 0), 2) }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
