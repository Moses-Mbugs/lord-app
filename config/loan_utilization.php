<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LOANS PORTFOLIO NEW extract — column mapping
    |--------------------------------------------------------------------------
    */
    'required' => [
        'contract_no',
        'account_name',
        'exposure_lcy',
        'past_due_days',
        'user_status',
        'frr',
        'orr',
        'credit_line',
        'gl_name',
        'business_segment',
        'industry_segment',
        'value_dt',
    ],

    'aliases' => [
        'contract_no' => [
            'Contract No',
            'Contract Number',
        ],

        'account_name' => [
            'Account Name',
            'Customer Name',
            'Name',
        ],

        'exposure_lcy' => [
            'Exposure Amount(lcy)',
            'Exposure Amount (lcy)',
            'Exposure Amount LCY',
        ],

        'past_due_days' => [
            'Past Due Days',
        ],

        'user_status' => [
            'User Defined Status',
        ],

        'frr' => [
            'Frr (risk Rating)',
            'Frr',
            'FRR',
        ],

        'orr' => [
            'Orr (risk Category)',
            'Orr',
            'ORR',
        ],

        'credit_line' => [
            'Credit Line',
        ],

        'gl_name' => [
            'Gl Name',
            'GL Name',
        ],

        'business_segment' => [
            'Business Segment',
        ],

        'industry_segment' => [
            'Industry Segment',
        ],

        'value_dt' => [
            'Value Dt',
            'Value Date',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Name categories
    |--------------------------------------------------------------------------
    | The 9 canonical buckets shown on the executive dashboard. "Unmapped -
    | Review" is a catch-all for Credit Line codes the rules below don't
    | recognize — flagged in the UI for a manual override (see
    | loan_utilization_product_overrides).
    */
    'product_names' => [
        'Personal Loan/Pension Backed/Insurance Premium Financing',
        'Residential and Land Purchase',
        'EKE Asset Backed',
        'Cash Backed/T-Bills/Bank Guarantee',
        'EKE Contract-LPO-Receivable',
        'EKE Inventory and distributorship',
        'Agriculture',
        'School Finance',
        'Unmapped - Review',
    ],

    /*
    |--------------------------------------------------------------------------
    | Credit Line prefix -> Product Name rules
    |--------------------------------------------------------------------------
    | Best-effort mapping derived from the source portfolio's Credit Line
    | codes, Gl Name and Business Segment (see the conversion notes from the
    | original Excel-based conversion). Corrections should be added to
    | loan_utilization_product_overrides rather than edited here, so they
    | survive re-imports and don't require a deploy.
    */
    'prefix_rules' => [
        '/^PP_EMPLOY/' => 'Personal Loan/Pension Backed/Insurance Premium Financing',
        '/^STAFFLNPL/' => 'Personal Loan/Pension Backed/Insurance Premium Financing',
        '/^STFLN_UPL/' => 'Personal Loan/Pension Backed/Insurance Premium Financing',
        '/^STAFFLNCR/' => 'Personal Loan/Pension Backed/Insurance Premium Financing',
        '/^STAFLN_EM/' => 'Personal Loan/Pension Backed/Insurance Premium Financing',
        '/^PP_ANNUIT/' => 'Personal Loan/Pension Backed/Insurance Premium Financing',
        '/^STFLN_EDU/' => 'Personal Loan/Pension Backed/Insurance Premium Financing',
        '/^STFLN_SHB/' => 'Personal Loan/Pension Backed/Insurance Premium Financing',
        '/^CASH_COLL/' => 'Cash Backed/T-Bills/Bank Guarantee',

        '/^STAFFLNMG/' => 'Residential and Land Purchase',
        '/^PP_HOUSLN/' => 'Residential and Land Purchase',
        '/^PP_HOUSEQ/' => 'Residential and Land Purchase',
        '/^RSTR_MORG/' => 'Residential and Land Purchase',
        '/^STFMG_EQR/' => 'Residential and Land Purchase',

        '/^PP_ASTAKM/' => 'EKE Asset Backed',
        '/^PP_ASTAGF/' => 'EKE Asset Backed',
        '/^PP_AUTOLN/' => 'EKE Asset Backed',
        '/^FIN_LEASE/' => 'EKE Asset Backed',
        '/^STAFFLVEH/' => 'EKE Asset Backed',

        '/^PP_LXCASH/' => 'Cash Backed/T-Bills/Bank Guarantee',
        '/^PP_BLIVGF/' => 'Cash Backed/T-Bills/Bank Guarantee',
        '/^PP_BDINVF/' => 'Cash Backed/T-Bills/Bank Guarantee',
        '/^LETT_CRDT/' => 'Cash Backed/T-Bills/Bank Guarantee',
        '/^IMPRT_FIN/' => 'Cash Backed/T-Bills/Bank Guarantee',
        '/^PP_LXCONF/' => 'Cash Backed/T-Bills/Bank Guarantee',
        '/^PP_LXCCCP/' => 'Cash Backed/T-Bills/Bank Guarantee',
        '/^PP_LXSHOP/' => 'Cash Backed/T-Bills/Bank Guarantee',
        '/^PP_LXELLC/' => 'Cash Backed/T-Bills/Bank Guarantee',

        '/^PP_LPOCOF/' => 'EKE Contract-LPO-Receivable',
        '/^PP_LPCFGF/' => 'EKE Contract-LPO-Receivable',
        '/^LPO_FNAGF/' => 'EKE Contract-LPO-Receivable',
        '/^WORK_ORDR/' => 'EKE Contract-LPO-Receivable',
        '/^PP_LXINVF/' => 'EKE Contract-LPO-Receivable',
        '/^PP_LXRECF/' => 'EKE Contract-LPO-Receivable',
        '/^PP_LXRREC/' => 'EKE Contract-LPO-Receivable',
        '/^INV_FNAGF/' => 'EKE Contract-LPO-Receivable',
        '/^INV_FINAN/' => 'EKE Contract-LPO-Receivable',

        '/^PP_INVFGF/' => 'EKE Inventory and distributorship',
        '/^INV_DISCT/' => 'EKE Inventory and distributorship',
        '/^SHT_LOAN/' => 'EKE Inventory and distributorship',
        '/^MDT_LOANS/' => 'EKE Inventory and distributorship',
        '/^LTM_LOANS/' => 'EKE Inventory and distributorship',
        '/^TERM_LOAN/' => 'EKE Inventory and distributorship',
        '/^RESTRU_LN/' => 'EKE Inventory and distributorship',
        '/^RESTRLOAN/' => 'EKE Inventory and distributorship',
        '/^PP_RESTRL/' => 'EKE Inventory and distributorship',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Defined Status -> Classification label (CBK convention)
    |--------------------------------------------------------------------------
    */
    'status_labels' => [
        'NORM' => 'Normal (0-30 DPD)',
        'OAEM' => 'Watch (31-90 DPD)',
        'SUB1' => 'Substandard (91-180 DPD)',
        'SUBS' => 'Substandard (91-180 DPD)',
        'DOUB' => 'Doubtful (181-360 DPD)',
        'LOSS' => 'Loss (361+ DPD)',
        'WOFF' => 'Write-Off',
    ],

    /*
    |--------------------------------------------------------------------------
    | Executive dashboard RAG thresholds
    |--------------------------------------------------------------------------
    | Adjustable defaults for the Red/Amber/Green status shown per product.
    */
    'rag_thresholds' => [
        'npl_ratio' => ['green' => 0.05, 'amber' => 0.10],
        'utilisation' => ['green' => 0.70, 'amber' => 0.90],
    ],
];
