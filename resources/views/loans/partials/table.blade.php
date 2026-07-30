<div class="loan-card">
    <div class="loan-card-header">
        <div>
            <h3>Final Loan Book Records</h3>
            <div class="loan-card-subtitle">
                Only records with negative net outstanding amounts are posted here.
            </div>
        </div>
    </div>

    <div class="loan-table-wrap">
        <table class="loan-table">
            <thead>
                <tr>
                    <th>Related Account</th>
                    <th>Customer Id</th>
                    <th>Name</th>
                    <th>Branch</th>
                    <th>Product Type</th>
                    <th>Currency</th>
                    <th>Business Segment</th>
                    <th>Net Outstanding</th>
                    <th>Loan Book Outstanding</th>
                    <th>Outstanding LCY</th>
                    <th>Maturity Date</th>
                    <th>RM Officer</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->related_account }}</td>
                        <td>{{ $entry->related_customer_id }}</td>
                        <td>{{ $entry->name }}</td>
                        <td>{{ $entry->branch }}</td>
                        <td>{{ $entry->product_type }}</td>
                        <td>{{ $entry->currency }}</td>
                        <td>{{ $entry->business_segment }}</td>
                        <td>{{ number_format($entry->net_outstanding_amount, 2) }}</td>
                        <td>{{ number_format($entry->loan_book_outstanding, 2) }}</td>
                        <td>{{ number_format($entry->outstanding_amount_lcy, 2) }}</td>
                        <td>{{ $entry->maturity_date ? $entry->maturity_date->format('Y-m-d') : '-' }}</td>
                        <td>{{ $entry->rm_officer }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="loan-empty">
                            No final Loan Book records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $entries->links() }}
</div>
