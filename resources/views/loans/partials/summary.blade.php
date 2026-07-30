<div class="loan-metric-grid">
    <div class="loan-metric-card">
        <div class="loan-metric-label">PMS Rows</div>
        <div class="loan-metric-value">{{ number_format($run->total_pms_rows) }}</div>
    </div>

    <div class="loan-metric-card">
        <div class="loan-metric-label">Loan Details Rows</div>
        <div class="loan-metric-value">{{ number_format($run->total_loan_details_rows) }}</div>
    </div>

    <div class="loan-metric-card">
        <div class="loan-metric-label">Final Loan Book Rows</div>
        <div class="loan-metric-value">{{ number_format($run->total_final_loan_book_rows) }}</div>
    </div>

    <div class="loan-metric-card">
        <div class="loan-metric-label">Exceptions</div>
        <div class="loan-metric-value">{{ number_format($run->total_exceptions) }}</div>
    </div>

    <div class="loan-metric-card">
        <div class="loan-metric-label">PMS Negative Outstanding</div>
        <div class="loan-metric-value">{{ number_format($run->total_pms_negative_outstanding, 2) }}</div>
    </div>

    <div class="loan-metric-card">
        <div class="loan-metric-label">Loan Book Outstanding</div>
        <div class="loan-metric-value">{{ number_format($run->total_loan_book_outstanding, 2) }}</div>
    </div>

    <div class="loan-metric-card">
        <div class="loan-metric-label">Control Difference</div>
        <div class="loan-metric-value">{{ number_format($run->control_difference, 2) }}</div>
    </div>

    <div class="loan-metric-card">
        <div class="loan-metric-label">Processed At</div>
        <div class="loan-metric-value" style="font-size: 15px;">
            {{ $run->processed_at ? $run->processed_at->format('Y-m-d H:i') : '-' }}
        </div>
    </div>
</div>
