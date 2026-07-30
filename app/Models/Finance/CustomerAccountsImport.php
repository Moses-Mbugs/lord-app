<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class CustomerAccountsImport extends Model
{
    protected $table = 'customer_accounts_imports';

    protected $fillable = [
        'introducer',
        'cust_category',
        'eti_cif_class_category',
        'etibiseg2',
        'acc_ofcr',
        'officer_name',
        'f12_cif',
        'f12_ac_no',
        'branch_code',
        'acy_withdrawable_bal',
        'cust_ac_no',
        'record_stat',
        'account_class',
        'ac_desc',
        'ac_open_date',
        'dormancy_date',
        'ac_stat_dormant',
        'address_line1',
        'lcy_curr_balance',
        'cheque_book_facility',
        'atm_facility',
        'telephone',
        'e_mail',
    ];

    protected $casts = [
        'acy_withdrawable_bal' => 'decimal:2',
        'lcy_curr_balance' => 'decimal:2',
        'ac_open_date' => 'date',
        'dormancy_date' => 'date',
    ];
}
