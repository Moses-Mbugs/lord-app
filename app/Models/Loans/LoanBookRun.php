<?php

namespace App\Models\Loans;

use Illuminate\Database\Eloquent\Model;
use App\Models\Loans\LoanBookPmsStaging;
use App\Models\Loans\LoanBookDetailsStaging;

class LoanBookRun extends Model
{
    protected $fillable = [
        'batch_reference',
        'loan_book_date',
        'pms_original_filename',
        'loan_details_original_filename',
        'total_pms_rows',
        'total_loan_details_rows',
        'total_final_loan_book_rows',
        'total_exceptions',
        'total_pms_net_outstanding',
        'total_pms_negative_outstanding',
        'total_loan_book_outstanding',
        'control_difference',
        'status',
        'processed_by',
        'processed_at',
        'control_summary',
        'failure_reason',
        'portfolio_original_filename',
        'credit_cards_original_filename',
        'total_portfolio_rows_read',
        'total_portfolio_rows_selected',
        'total_credit_card_rows_read',
        'total_credit_card_rows_selected',
        'lms_original_filename',
        'total_lms_rows_read',
        'total_lms_rows_selected',
    ];

    protected $casts = [
        'loan_book_date' => 'date',
        'processed_at' => 'datetime',
        'control_summary' => 'array',
    ];

    public function entries()
    {
        return $this->hasMany(LoanBookEntry::class, 'loan_book_run_id');
    }

    public function exceptions()
    {
        return $this->hasMany(LoanBookException::class, 'loan_book_run_id');
    }

    public function getStatusBadgeClassAttribute()
    {
        if ($this->status === 'completed') {
            return 'badge-success';
        }

        if ($this->status === 'failed') {
            return 'badge-danger';
        }

        return 'badge-warning';
    }
    public function pmsStaging()
    {
        return $this->hasMany(LoanBookPmsStaging::class, 'loan_book_run_id');
    }

    public function detailsStaging()
    {
        return $this->hasMany(LoanBookDetailsStaging::class, 'loan_book_run_id');
    }
}
