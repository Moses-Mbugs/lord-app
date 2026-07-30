<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PMS Loan Proofing Report
    |--------------------------------------------------------------------------
    */
    'pms' => [
        'required' => [
            'gl_code',
            'related_account',
            'related_customer_id',
            'name',
            'outstanding_amount',
        ],

        'aliases' => [
            'gl_code' => [
                'Gl Code',
                'GL Code',
                'G/L Code',
            ],

            'related_account' => [
                'Related Account',
                'Related A/c',
                'Related Account Number',
                'Account Number',
                'Loan Account',
            ],

            'related_customer_id' => [
                'Related Customer Id',
                'Related Customer ID',
                'Customer Id',
                'Customer ID',
                'CIF',
                'Customer No',
            ],

            'name' => [
                'Name',
                'Customer Name',
                'Account Name',
            ],

            'outstanding_amount' => [
                'Outstanding Amount',
                'Outstanding Amount from',
                'Outstanding Amount From',
                'Amount',
                'Balance',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Loans Details Report
    |--------------------------------------------------------------------------
    */
    'loan_details' => [
        'required' => [
            'related_account',
            'related_customer_id',
            'name',
            'frr',
            'orr',
            'account_status',
            'value_dt',
            'maturity_date',
            'linecode',
            'branch',
            'product_type',
            'currency',
            'industrycode',
            'status',
            'interest_rate',
            'exch_rate',
            'tenor',
            'limit',
            'limit_lcy',
            'group_code',
            'sub_sic_code',
            'business_segment',
            'product_code',
            'latest_status_change',
            'rm_officer',
            'collateral_code',
        ],

        'aliases' => [
            'related_account' => [
                'Related Account',
                'Related A/c',
                'Related Account Number',
                'Account Number',
                'Loan Account',
            ],

            'related_customer_id' => [
                'Related Customer Id',
                'Related Customer ID',
                'Customer Id',
                'Customer ID',
                'CIF',
                'Customer No',
            ],

            'name' => [
                'Name',
                'Customer Name',
                'Account Name',
            ],

            'frr' => [
                'Frr',
                'FRR',
            ],

            'orr' => [
                'Orr',
                'ORR',
            ],

            'account_status' => [
                'Account Status',
            ],

            'value_dt' => [
                'Value Dt',
                'Value Date',
            ],

            'maturity_date' => [
                'Maturity Date',
            ],

            'linecode' => [
                'Linecode',
                'Line Code',
            ],

            'branch' => [
                'Branch',
            ],

            'product_type' => [
                'Product Type',
            ],

            'currency' => [
                'Currency',
                'Ccy',
                'CCY',
            ],

            'industrycode' => [
                'Industrycode',
                'Industry Code',
            ],

            'status' => [
                'Status',
            ],

            'interest_rate' => [
                'Interest Rate',
                'Rate',
            ],

            'exch_rate' => [
                'Exch Rate',
                'Exchange Rate',
            ],

            'tenor' => [
                'Tenor',
            ],

            'limit' => [
                'Limit',
            ],

            'limit_lcy' => [
                'Limit Lcy',
                'Limit LCY',
            ],

            'group_code' => [
                'Group Code',
            ],

            'sub_sic_code' => [
                'Sub Sic Code',
                'Sub SIC Code',
            ],

            'business_segment' => [
                'Business Segment',
            ],

            'product_code' => [
                'Product Code',
            ],

            'latest_status_change' => [
                'Latest Status Change',
            ],

            'rm_officer' => [
                'Rm Officer',
                'RM Officer',
                'Relationship Manager',
            ],

            'collateral_code' => [
                'Collateral Code',
            ],
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | LMS Loan Portfolio Report
    |--------------------------------------------------------------------------
    */
    'lms' => [
        'required' => [
            'full_name',
            'cif_number',
            'account_no',
            'total_outstanding',
            'loan_status',
        ],

        'aliases' => [
            'full_name' => [
                'Full Name',
            ],

            'customer_no' => [
                'Customer No.',
                'Customer No',
            ],

            'account_no' => [
                'Account No.',
                'Account No',
                'Account Number',
            ],

            'cif_number' => [
                'CIF Number',
                'CIF',
            ],

            'loan_account_no' => [
                'Loan Account No.',
                'Loan Account No',
            ],

            'application_ref' => [
                'Application Ref.',
                'Application Ref',
            ],

            'branch' => [
                'Branch',
            ],

            'account_status' => [
                'Account Status',
            ],

            'product_type' => [
                'Product/Category',
                'Product',
                'Category',
            ],

            'credit_limit' => [
                'Credit Limit',
            ],

            'available_limit' => [
                'Available Limit',
            ],

            'disbursed_amount' => [
                'Disbursed Amount',
            ],

            'interest_rate' => [
                'Interest Rate (PA)',
                'Interest Rate',
            ],

            'tenure_months' => [
                'Tenure (Mo)',
                'Tenure',
            ],

            'principal_outstanding' => [
                'Principal Outstanding',
            ],

            'interest_outstanding' => [
                'Interest Outstanding',
            ],

            'penalty_outstanding' => [
                'Penalty Outstanding',
            ],

            'total_outstanding' => [
                'Total Outstanding',
            ],

            'total_repaid' => [
                'Total Repaid',
            ],

            'next_due_date' => [
                'Next Due Date',
            ],

            'loan_status' => [
                'Loan Status',
            ],

            'disbursed_at' => [
                'Disbursed At',
            ],

            'closed_at' => [
                'Closed At',
            ],

            'total_fee_revenue' => [
                'Total Fee Revenue (Posted)',
            ],

            'dl_fee' => [
                'DL Fee (Posted)',
            ],

            'processing_fee' => [
                'Processing Fee (Posted)',
            ],

            'insurance_fee' => [
                'Insurance Fee (Posted)',
            ],

            'excise_duty' => [
                'Excise Duty (Posted)',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Portfolio Account Report
    |--------------------------------------------------------------------------
    */
    'portfolio_accounts' => [
        'required' => [
            'branch_name',
            'customer_ac_no',
            'ccy',
            'frr',
            'orr',
            'gl_name',
            'lcy_curr_balance',
            'status_since',
            'pdo_days',
            'status',
            'customer_no',
            'description',
        ],

        'aliases' => [
            'branch_name' => [
                'Branch Name',
                'Branch Name.',
                'Branch',
            ],

            'customer_ac_no' => [
                'Customer Ac No',
                'Cust Ac No',
                'Customer A/c No',
                'Customer Account No',
                'Customer Account Number',
                'Account Number',
                'Account No',
            ],

            'ccy' => [
                'CCY',
                'Ccy',
                'Currency',
            ],

            'frr' => [
                'FRR',
                'Frr',
                'Frr (risk Rating)',
            ],

            'orr' => [
                'ORR',
                'Orr',
                'Orr (risk Category)',
            ],

            'gl_name' => [
                'GL Name',
                'Gl Name',
                'G/L Name',
                'GI Name',
            ],

            'lcy_curr_balance' => [
                'LCY Curr Balance',
                'LCY Current Balance',
                'Lcy Curr Balance',
                'LCY Balance',
            ],

            'status_since' => [
                'Status Since',
                'Status Date',
            ],

            'pdo_days' => [
                'PDO Days',
                'Pdo Days',
                'Days Past Due',
            ],

            'status' => [
                'Status',
            ],

            'customer_no' => [
                'Customer No',
                'Customer No.',
                'Cust No',
                'Customer Number',
                'Customer ID',
                'Customer Id',
                'CIF',
            ],

            'description' => [
                'Desc',
                'Description',
                'As Desc',
                'Ac Desc',
                'Name',
                'Customer Name',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Credit Cards Report
    |--------------------------------------------------------------------------
    */
    'credit_cards' => [
        'required' => [
            'name',
            'flexcube_account_cif',
            'branch_name',
            'card_account',
            'contract_currency',
            'status',
            'outstanding_amount',
            'amount_arrears',
            'days_in_arrears',
            'rate',
        ],

        'aliases' => [
            'name' => [
                'Name',
                'Customer Name',
            ],

            'flexcube_account_cif' => [
                'Flexcube Account Cif',
                'Flexcube Account CIF',
                'Account Cif',
                'Account CIF',
                'CIF',
                'Customer ID',
                'Customer Id',
            ],

            'branch_name' => [
                'Branch Name',
                'Branch',
            ],

            'card_account' => [
                'Card Account',
                'Card Account No',
                'Card Account Number',
                'Account Number',
            ],

            'contract_currency' => [
                'Contract Currency',
                'Currency',
                'CCY',
                'Ccy',
            ],

            'status' => [
                'Status',
            ],

            'outstanding_amount' => [
                'Outstanding Amount',
                'Outstanding',
                'Balance',
            ],

            'amount_arrears' => [
                'Amount Arrears',
                'Arrears Amount',
            ],

            'days_in_arrears' => [
                'Days In Arrears',
                'Days in Arrears',
                'Arrears Days',
            ],

            'rate' => [
                'Rate',
                'Interest Rate',
            ],
        ],
    ],

];
