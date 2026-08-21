<?php

return [
    'balances' => [

        'base_dir' => '/mnt/eke_dailyflexcubereports',

        'country_folder' => 'Kenya',

        // Who gets the "started importing / not found, retrying / imported successfully" emails
        // from the finance:import-daily-balances scheduled command.
        'import_status_to' => env('BALANCES_IMPORT_NOTIFY_EMAIL', 'mmuigai@ecobank.com,ALLEKE-ICT@ecobank.com'),

        'top_movers_to' => [
            // 'mmuigai@ecobank.com',
            'RMBITHI@ecobank.com',
            'JMOKUA@ecobank.com',
            'MWANJIRU@ecobank.com',
            'hobiero@ecobank.com',
            'BMuthini@ecobank.com',
            'FODUOR@ecobank.com',
            'JJELAGAT@ecobank.com',
            'CRONO@ecobank.com',
            'VMBAABU@ecobank.com',
            'WWACIIRA@ecobank.com',
            'HMUDUKIZA@ecobank.com',
            'MOSOULEYMANE@ecobank.com',
            'AMUREITHI@ecobank.com',
            'RSIRO@ecobank.com',
            'RBETT@ecobank.com',
            'cMBENGE@ecobank.com',
            'LOUTA@ecobank.com',
            'AKIBAARA@ecobank.com',
            'cKICHE@ecobank.com',
            'TOKUMU@ecobank.com',
            'FLEIN@ecobank.com',
            'JNYABERI@ecobank.com',
            'LMAGERO@ecobank.com',
            'RMAINGI@ecobank.com',
            'AKAMERE@ecobank.com',
            'LKAGIO@ecobank.com',
            'UWAMBUA@ecobank.com',
            'ANGURE@ecobank.com',
            'BMURIUKI@ecobank.com',
            'JORENGE@ecobank.com',
            'AHWANG@ecobank.com',
            'GTAK@ecobank.com',
            'SMAGOTHE@ecobank.com',
            'NOGUTU@ecobank.com',
            'CMAKONE@ecobank.com',
            'FESSENDI@ecobank.com',
            'TMIGUNI@ecobank.com',
            'AMIRITI@ecobank.com',
            'FYEGO@ecobank.com',
            'JoMUTHAMA@ecobank.com',
            'EMBANO@ecobank.com',
            // '',
            // '',
            // '',
            // '',
            // '',
            // '',
        ],

        'top_movers_cc' => [
            // 'JJELAGAT@ecobank.com',
            // 'CRONO@ecobank.com',
            // 'VMBAABU@ecobank.com',
            // 'WWACIIRA@ecobank.com',
            // 'HMUDUKIZA@ecobank.com',
            // 'MOSOULEYMANE@ecobank.com',
            // 'ALLEKE-DEVSupport@ecobank.com',
            'ALLEKE-ICT@ecobank.com',
            'mmuigai@ecobank.com',

        ],

        // 'top_movers_bcc' => [
        //     'jfmuthui@ecobank.com',
        // ],

        'branch_movers_to' => [
            // 'mmuigai@ecobank.com',
            'RMBITHI@ecobank.com',
            'JMOKUA@ecobank.com',
            'MWANJIRU@ecobank.com',
            'hobiero@ecobank.com',
            'BMuthini@ecobank.com',
            'FODUOR@ecobank.com',
            'AllEKE-BBHEADS@ecobank.com',
            'ALLEKE-BOH@ecobank.com',
            'TOKWARO@ecobank.com',
            'JJELAGAT@ecobank.com',
            'CRONO@ecobank.com',
            'VMBAABU@ecobank.com',
            'JMEEME@ecobank.com',
            'VMONARI@ecobank.com',
            'HGATHII@ecobank.com',
            'AADAM2@ecobank.com',
            'CADEWUNMI@ecobank.com',
            'HGATHII@ecobank.com',
            'JGITUMA@ecobank.com',
            'EMIANO@ecobank.com',
            'LMINING@ecobank.com',
            'BMulumia@ecobank.com',
            'MMURAYA@ecobank.com',
            'RNZIOKA@ecobank.com',
            'DOJIAMBO@ecobank.com',
            'BRONO@ecobank.com',
            'RTHAIRU@ecobank.com',
            'GWANJALA@ecobank.com',
            'RSIRO@ecobank.com',
            'RBETT@ecobank.com',
            'AMUREITHI@ecobank.com',
            'cMBENGE@ecobank.com',
            'LOUTA@ecobank.com',
            'AKIBAARA@ecobank.com',
            'cKICHE@ecobank.com',
            'TOKUMU@ecobank.com',
            'FLEIN@ecobank.com',
            'JNYABERI@ecobank.com',
            'LMAGERO@ecobank.com',
            'RMAINGI@ecobank.com',
            'AKAMERE@ecobank.com',
            'LKAGIO@ecobank.com',
            'UWAMBUA@ecobank.com',
            'ANGURE@ecobank.com',
            'BMURIUKI@ecobank.com',
            'JORENGE@ecobank.com',
            'AHWANG@ecobank.com',
            'GTAK@ecobank.com',
            'SMAGOTHE@ecobank.com',
            'NOGUTU@ecobank.com',
            'CMAKONE@ecobank.com',
            'FYEGO@ecobank.com',
            'GMUENDO@ecobank.com',
            'FESSENDI@ecobank.com',
            'TMIGUNI@ecobank.com',
            'AMIRITI@ecobank.com',
            'JoMUTHAMA@ecobank.com',
            'EMBANO@ecobank.com',
            // '',
            // '',
            // '',
            // '',
            // '',
            // '',
            ],

        'branch_movers_cc' => [
            // 'fidotieno@ecobank.com',
            // 'mgichomo@ecobank.com',
            // 'TOKWARO@ecobank.com',
            // 'JJELAGAT@ecobank.com',
            // 'VMBAABU@ecobank.com',
            // 'JMEEME@ecobank.com',
            // 'VMONARI@ecobank.com',
            // 'HGATHII@ecobank.com',
            'mmuigai@ecobank.com',
            'ALLEKE-ICT@ecobank.com',
            // 'ALLEKE-DEVSupport@ecobank.com',
            ],



    ],

    'loans' => [
        'to' => [
            'RMBITHI@ecobank.com',
            'JMOKUA@ecobank.com',
            'MWANJIRU@ecobank.com',
            'hobiero@ecobank.com',
            'BMuthini@ecobank.com',
            'FODUOR@ecobank.com',
            'AllEKE-BBHEADS@ecobank.com',
            'ALLEKE-BOH@ecobank.com',
            'TOKWARO@ecobank.com',
            'JJELAGAT@ecobank.com',
            'VMBAABU@ecobank.com',
            'CRONO@ecobank.com',
            'JMEEME@ecobank.com',
            'VMONARI@ecobank.com',
            'HGATHII@ecobank.com',
            'AADAM2@ecobank.com',
            'CADEWUNMI@ecobank.com',
            'HGATHII@ecobank.com',
            'JGITUMA@ecobank.com',
            'EMIANO@ecobank.com',
            'LMINING@ecobank.com',
            'BMulumia@ecobank.com',
            'MMURAYA@ecobank.com',
            'RNZIOKA@ecobank.com',
            'DOJIAMBO@ecobank.com',
            'BRONO@ecobank.com',
            'RTHAIRU@ecobank.com',
            'GWANJALA@ecobank.com',
            'RSIRO@ecobank.com',
            'RBETT@ecobank.com',
            'AMUREITHI@ecobank.com',
            'cMBENGE@ecobank.com',
            'LOUTA@ecobank.com',
            'AKIBAARA@ecobank.com',
            'cKICHE@ecobank.com',
            'TOKUMU@ecobank.com',
            'FLEIN@ecobank.com',
            'JNYABERI@ecobank.com',
            'LMAGERO@ecobank.com',
            'RMAINGI@ecobank.com',
            'AKAMERE@ecobank.com',
            'LKAGIO@ecobank.com',
            'UWAMBUA@ecobank.com',
            'ANGURE@ecobank.com',
            'BMURIUKI@ecobank.com',
            'JORENGE@ecobank.com',
            'AHWANG@ecobank.com',
            'GTAK@ecobank.com',
            'SMAGOTHE@ecobank.com',
            'NOGUTU@ecobank.com',
            'CMAKONE@ecobank.com',
            'GMUENDO@ecobank.com',
            'FESSENDI@ecobank.com',
            'TMIGUNI@ecobank.com',
            'AMIRITI@ecobank.com',
            'FYEGO@ecobank.com',
            'JoMUTHAMA@ecobank.com',
            'EMBANO@ecobank.com',
            // '',
            // '',

            // 'mmuigai@ecobank.com',
        ],
        'cc' => [
            'ALLEKE-ICT@ecobank.com',
            'mmuigai@ecobank.com',
        ],
    ],

    'weekly_segment' => [
        'to' => [
            // 'mmuigai@ecobank.com',
            'RMBITHI@ecobank.com',
            'JMOKUA@ecobank.com',
            'MWANJIRU@ecobank.com',
            'hobiero@ecobank.com',
            'BMuthini@ecobank.com',
            'FODUOR@ecobank.com',
            'AllEKE-BBHEADS@ecobank.com',
            'ALLEKE-BOH@ecobank.com',
            'TOKWARO@ecobank.com',
            'JJELAGAT@ecobank.com',
            'VMBAABU@ecobank.com',
            'CRONO@ecobank.com',
            'JMEEME@ecobank.com',
            'VMONARI@ecobank.com',
            'HGATHII@ecobank.com',
            'AADAM2@ecobank.com',
            'CADEWUNMI@ecobank.com',
            'HGATHII@ecobank.com',
            'JGITUMA@ecobank.com',
            'EMIANO@ecobank.com',
            'LMINING@ecobank.com',
            'BMulumia@ecobank.com',
            'MMURAYA@ecobank.com',
            'RNZIOKA@ecobank.com',
            'DOJIAMBO@ecobank.com',
            'BRONO@ecobank.com',
            'RTHAIRU@ecobank.com',
            'GWANJALA@ecobank.com',
            'RSIRO@ecobank.com',
            'RBETT@ecobank.com',
            'AMUREITHI@ecobank.com',
            'cMBENGE@ecobank.com',
            'LOUTA@ecobank.com',
            'AKIBAARA@ecobank.com',
            'cKICHE@ecobank.com',
            'TOKUMU@ecobank.com',
            'FLEIN@ecobank.com',
            'JNYABERI@ecobank.com',
            'LMAGERO@ecobank.com',
            'RMAINGI@ecobank.com',
            'AKAMERE@ecobank.com',
            'LKAGIO@ecobank.com',
            'UWAMBUA@ecobank.com',
            'ANGURE@ecobank.com',
            'BMURIUKI@ecobank.com',
            'JORENGE@ecobank.com',
            'AHWANG@ecobank.com',
            'GTAK@ecobank.com',
            'SMAGOTHE@ecobank.com',
            'NOGUTU@ecobank.com',
            'CMAKONE@ecobank.com',
            'GMUENDO@ecobank.com',
            'FESSENDI@ecobank.com',
            'TMIGUNI@ecobank.com',
            'AMIRITI@ecobank.com',
            'FYEGO@ecobank.com',
            'JoMUTHAMA@ecobank.com',
            'EMBANO@ecobank.com',
            // '',
            // '',

        ],
        'cc' => [
            // 'ALLEKE-ICT@ecobank.com',
            'mmuigai@ecobank.com',
        ],
    ],

    'weekly_loan' => [
        'to' => [
            // Test phase — sending to mmuigai@ecobank.com only. Expand once validated.
            // 'mmuigai@ecobank.com',
            'RMBITHI@ecobank.com',
            'JMOKUA@ecobank.com',
            'MWANJIRU@ecobank.com',
            'hobiero@ecobank.com',
            'BMuthini@ecobank.com',
            'FODUOR@ecobank.com',
            'AllEKE-BBHEADS@ecobank.com',
            'ALLEKE-BOH@ecobank.com',
            'TOKWARO@ecobank.com',
            'JJELAGAT@ecobank.com',
            'VMBAABU@ecobank.com',
            'JMEEME@ecobank.com',
            'CRONO@ecobank.com',
            'VMONARI@ecobank.com',
            'HGATHII@ecobank.com',
            'AADAM2@ecobank.com',
            'CADEWUNMI@ecobank.com',
            'HGATHII@ecobank.com',
            'JGITUMA@ecobank.com',
            'EMIANO@ecobank.com',
            'LMINING@ecobank.com',
            'BMulumia@ecobank.com',
            'MMURAYA@ecobank.com',
            'RNZIOKA@ecobank.com',
            'DOJIAMBO@ecobank.com',
            'BRONO@ecobank.com',
            'RTHAIRU@ecobank.com',
            'GWANJALA@ecobank.com',
            'RSIRO@ecobank.com',
            'RBETT@ecobank.com',
            'AMUREITHI@ecobank.com',
            'cMBENGE@ecobank.com',
            'LOUTA@ecobank.com',
            'AKIBAARA@ecobank.com',
            'cKICHE@ecobank.com',
            'TOKUMU@ecobank.com',
            'FLEIN@ecobank.com',
            'JNYABERI@ecobank.com',
            'LMAGERO@ecobank.com',
            'RMAINGI@ecobank.com',
            'AKAMERE@ecobank.com',
            'LKAGIO@ecobank.com',
            'UWAMBUA@ecobank.com',
            'ANGURE@ecobank.com',
            'BMURIUKI@ecobank.com',
            'JORENGE@ecobank.com',
            'AHWANG@ecobank.com',
            'GTAK@ecobank.com',
            'SMAGOTHE@ecobank.com',
            'NOGUTU@ecobank.com',
            'CMAKONE@ecobank.com',
            'GMUENDO@ecobank.com',
            'FESSENDI@ecobank.com',
            'TMIGUNI@ecobank.com',
            'AMIRITI@ecobank.com',
            'FYEGO@ecobank.com',
            'JoMUTHAMA@ecobank.com',
            'EMBANO@ecobank.com',
            // '',
            // '',
        ],
        'cc' => [
            // 'ALLEKE-ICT@ecobank.com',
            'mmuigai@ecobank.com',
        ],
    ],
];

// friday committttt

//                  How to use this project works

// php artisan reports:run-balances --auto --date=2026-02-16  this will run for the specified date (2026-02-16) and send
//                              the email with the top movers report for that date. The --auto flag is just to indicate
                                // that it's being run automatically, you can omit it if you want to run it manually.
                                // If you omit the --date flag, it will default to yesterday's date, so you can schedule
                                // it in the Kernel.php to run every day at a specific time.

// php artisan reports:run-balances --auto    this will pick yesterday's date as default, so you can schedule it in the
                                // Kernel.php


// php artisan reports:run-balances 2026-02-16 --import-path=/mnt/eke_dailyflexcubereports/2026/Feb/16/Kenya
                                // this will run manual...with the specified date and import path, you can use this
                                // if you want to run it for a specific date and import the data from a specific path.
                                // The --import-path flag is optional, if you omit it, it will use the default path based on the date.


// php artisan reports:build-segment-movers 2026-02-16 2026-02-17
                                // this will build the segment movers report for the specified date range (2026-02-16 to 2026-02-17).
                                // You can adjust the dates as needed. This command reads from the customer_balances and
                                // customer_accounts_imports tables to calculate the segment movements and stores the results
                                // in the segment_movers table. You should run this command after you have imported the balances
                                // data for the relevant dates, and before you run any commands that rely on the segment movers data.


// php artisan reports:run-balances 2026-02-18 --no-import
                                // this will run the balances report for the specified date (2026-02-18) without importing new data.
                                // This is useful if you have already imported the data for that date and just want to re-run the report
                                // or send the email again without re-importing. The --no-import flag tells the command to skip the import step
                                // and just use the existing data in the database for that date.

//  php artisan import:balances "/mnt/eke_dailyflexcubereports/2026/JUL/8/Kenya.zip"
                                // this will import the balances data from the specified path ("/mnt/eke_dailyflexcubereports/2026/Feb/20/Kenya") for the date 2026-02-20.
                                // You can use this command to import data for a specific date without running the full report, which can be useful for testing or if you want to import data in advance before running the report. The command will read the data from the specified path and store it in the customer_balances table in the database.

// php artisan import:balances "/mnt/eke_dailyflexcubereports/2026/Feb/20/Kenya" --keep-raw=1

// php artisan reports:build-sub-segment-movers 2026-03-31 2026-04-01

// weekly movement
// php artisan reports:build-weekly-segment 2026-06-29
// php artisan reports:email-weekly-segment 2026-06-29



// http://eke-intranetlive:400/command/export:customers ----import latest customers data from the database to a CSV file, which can be used to updated the customers data in the system.
//  imr.customer_accounts_imports;
// http://eke-intranetlive:400/eod-reports


//

            // QUERY SECTION

// To fetch the segment movers data for a specific date range, you can run the following SQL query against your database:
//     SELECT
//     cb.cif,
//     MAX(cb.customer_name) AS customer_name,
//     SUM(
//         CASE
//             WHEN cb.lcy_balance > 0
//             THEN cb.lcy_balance
//             ELSE 0
//         END
//     ) AS total_positive_balance
// FROM customer_balances cb
// WHERE cb.balance_date = '2026-03-02'
//   AND cb.cif IS NOT NULL
//   AND UPPER(TRIM(cb.branch_code)) <> 'P50'
//   and upper(TRIM(cb.cr_gl)) <> '216220001'
// GROUP BY cb.cif;


 // to query daily listings has branches and business segments
// SELECT
//     cb.cif,
//     MAX(cb.customer_name) AS customer_name,
//     MAX(cb.branch_code)   AS branch_code,
//     GROUP_CONCAT(DISTINCT cb.cust_ac_no ORDER BY cb.cust_ac_no SEPARATOR ', ') AS account_numbers,
//     GROUP_CONCAT(DISTINCT cai.etibiseg2 ORDER BY cai.etibiseg2 SEPARATOR ', ') AS etibiseg2,
//     SUM(
//         CASE WHEN cb.lcy_balance > 0 THEN cb.lcy_balance ELSE 0 END
//     ) AS total_positive_balance
// FROM customer_balances cb
// LEFT JOIN customer_accounts_imports cai
//     ON cai.f12_ac_no = cb.cust_ac_no
// WHERE cb.balance_date = '2026-07-20'
//   AND cb.cif IS NOT NULL
//   AND (
//         cb.cif IN (
//             '470000068','470218244','470224763','470090458',
//             '470321717','470291487','470317567','470803302','470251434'
//         )
//         OR (
//             UPPER(TRIM(cb.branch_code)) <> 'P50'
//             AND (
//                 cb.cr_gl IS NULL
//                 OR cb.cr_gl <> '216220001'
//             )
//         )
//       )
// GROUP BY cb.cif
// ORDER BY total_positive_balance DESC;



// same listing but ungrouped - one row per account, no SUM/GROUP BY
// SELECT
//     cb.cif,
//     cb.cust_ac_no    AS account_number,
//     cb.customer_name,
//     cb.branch_code,
//     cb.lcy_balance   AS balance
// FROM customer_balances cb
// WHERE cb.balance_date = '2026-07-10'
//   AND cb.cif IS NOT NULL
//   AND cb.lcy_balance > 0
//   AND (
//         cb.cif IN (
//             '470000068','470218244','470224763','470090458',
//             '470321717','470291487','470317567','470803302','470251434'
//         )
//         OR (
//             UPPER(TRIM(cb.branch_code)) <> 'P50'
//             AND (
//                 cb.cr_gl IS NULL
//                 OR cb.cr_gl <> '216220001'
//             )
//         )
//       )
// ORDER BY cb.cif, balance DESC;


// ungrouped listing, P50 hard-excluded (no exception-CIF override), with business segment (etibiseg2)
// joined on account number (not cif) so no fan-out risk even without GROUP BY
// SELECT
//     cb.cif,
//     cb.cust_ac_no    AS account_number,
//     cb.customer_name,
//     cb.branch_code,
//     cai.etibiseg2,
//     cb.lcy_balance   AS balance
// FROM customer_balances cb
// LEFT JOIN customer_accounts_imports cai
//     ON cai.f12_ac_no = cb.cust_ac_no
// WHERE cb.balance_date = '2026-07-10'
//   AND cb.cif IS NOT NULL
//   AND cb.lcy_balance > 0
//   AND UPPER(TRIM(cb.branch_code)) <> 'P50'
//   AND (
//         cb.cr_gl IS NULL
//         OR cb.cr_gl <> '216220001'
//       )
// ORDER BY cb.cif, balance DESC;


// same as above but rolled back up to CIF level (P50 hard-excluded, business segment included).
// join is still on account number (1:1) before the GROUP BY, so summing per cif afterwards
// is safe - no fan-out multiplication.
// SELECT
//     cb.cif,
//     MAX(cb.customer_name) AS customer_name,
//     MAX(cb.branch_code)   AS branch_code,
//     GROUP_CONCAT(DISTINCT cb.cust_ac_no ORDER BY cb.cust_ac_no SEPARATOR ', ') AS account_numbers,
//     GROUP_CONCAT(DISTINCT cai.etibiseg2 ORDER BY cai.etibiseg2 SEPARATOR ', ') AS etibiseg2,
//     SUM(
//         CASE WHEN cb.lcy_balance > 0 THEN cb.lcy_balance ELSE 0 END
//     ) AS total_positive_balance
// FROM customer_balances cb
// LEFT JOIN customer_accounts_imports cai
//     ON cai.f12_ac_no = cb.cust_ac_no
// WHERE cb.balance_date = '2026-07-10'
//   AND cb.cif IS NOT NULL
//   AND UPPER(TRIM(cb.branch_code)) <> 'P50'
//   AND (
//         cb.cr_gl IS NULL
//         OR cb.cr_gl <> '216220001'
//       )
// GROUP BY cb.cif
// ORDER BY total_positive_balance DESC;



// find the count of each segment in the customers table
// SELECT
//     etibiseg2,
//     COUNT(*) AS total_records
// FROM imr.customer_accounts_imports
// GROUP BY etibiseg2
// ORDER BY total_records DESC;


// find the records with null, empty, 'Nap', or starting with 'D' in the etibiseg2 column of the customer_accounts_imports table
// SELECT eti_cif_class_category, etibiseg2, acc_ofcr, f12_cif, f12_ac_no, branch_code, cust_ac_no, record_stat, account_class, ac_desc, ac_open_date,  ac_stat_dormant, atm_facility, telephone, e_mail
// FROM imr.customer_accounts_imports
// WHERE etibiseg2 IS NULL
//    OR TRIM(etibiseg2) = ''
//    OR TRIM(etibiseg2) = 'Nap'
//    OR TRIM(etibiseg2) LIKE 'D%'
// ORDER BY etibiseg2;




// segmest listings on a CIF level
// SELECT
//     f12_cif AS cif,
//     etibiseg2 AS raw_segment,
//     branch_code, cust_ac_no, record_stat, account_class, ac_desc, ac_open_date,


//     CASE
//         WHEN UPPER(TRIM(etibiseg2)) LIKE 'CB%' THEN 'CB'
//         WHEN UPPER(TRIM(etibiseg2)) LIKE 'CM%' THEN 'CM'
//         WHEN UPPER(TRIM(etibiseg2)) LIKE 'CS%' THEN 'CS'
//         ELSE 'OT'
//     END AS segment_code,

//     CASE
//         WHEN UPPER(TRIM(etibiseg2)) LIKE 'CB%' THEN 'Corporate'
//         WHEN UPPER(TRIM(etibiseg2)) LIKE 'CM%' THEN 'Commercial'
//         WHEN UPPER(TRIM(etibiseg2)) LIKE 'CS%' THEN 'Consumer'
//         ELSE 'Others'
//     END AS segment_name

// FROM customer_accounts_imports
// WHERE f12_cif IS NOT NULL;

// total balances for others
// SELECT
//     cb.cust_ac_no,
//     ca.ac_desc,
//     ca.etibiseg2,
//     cb.balance,
//     cb.lcy_balance

// FROM imr.customer_balances cb
// JOIN imr.customer_accounts_imports ca
//     ON cb.cust_ac_no = ca.f12_ac_no

// WHERE
//     cb.balance_date = '2026-04-28'
//     AND (
//         ca.etibiseg2 IS NULL
//         OR TRIM(ca.etibiseg2) = ''
//         OR TRIM(ca.etibiseg2) = 'Nap'
//         OR TRIM(ca.etibiseg2) LIKE 'D%'
//     );


// http://eke-intranetlive:400/command/export:customers
// this command exports the customers data from the database to a CSV file,
// which can be used to updated the customers data in the system.
// http://eke-intranetlive:400/eod-reports they will appear here after running the balances report command, you can click on the report to view the details and download the CSV file if needed



// fail safe if the import isnt working

// copy the files from eke daily flexcube to a temp file in the machine

// mkdir -p /tmp/balances_20260428
// rsync -ah --partial --progress \
// "/mnt/eke_dailyflexcubereports/2026/APRIL/28/Kenya/BALANCES PER CUSTOMER_v1_txt_28.04.2026.txt" \
// "/tmp/balances_20260428/"

// confirm if it exist with
// ls -lh /tmp/balances_20260428/

// the import locally with this command
// php artisan import:balances /tmp/balances_20260428

// you will have to adjust the dates



// Change Password for Windows Share Mount

// or straight up run

// sudo nano /root/.smb_eke_dailyflexcubereports

// change username and password in that file, then save and exit, then run

// Remount the share

// sudo umount -l /mnt/eke_dailyflexcubereports

// sudo mount -a

// ls -lh /mnt/eke_dailyflexcubereports --- testing


// business segments query for loans
// SELECT
//     ll.cif,
//     ll.name,
//     ll.related_account,
//     ll.business_segment AS raw_segment,
//     COALESCE(
//         sm_agg.business,
//         CASE
//             WHEN UPPER(TRIM(ll.business_segment)) LIKE '%CORPORATE%'  THEN 'CORPORATE BANKING'
//             WHEN UPPER(TRIM(ll.business_segment)) LIKE '%COMMERCIAL%'
//               OR UPPER(TRIM(ll.business_segment)) LIKE '%COMERCIAL%'  THEN 'COMMERCIAL BANKING'
//             WHEN UPPER(TRIM(ll.business_segment)) LIKE '%CONSUMER%'   THEN 'CONSUMER BANKING'
//             ELSE ll.business_segment
//         END,
//         'UNMAPPED'
//     ) AS canonical_segment,
//     ll.loan_book_outstanding,
//     ll.as_at_date
// FROM loan_listings ll
// LEFT JOIN (
//     SELECT
//         cai.f12_cif AS cif,
//         MAX(sm.business) AS business
//     FROM customer_accounts_imports cai
//     INNER JOIN sub_segment_mappings sm
//         ON sm.mis_code = TRIM(cai.etibiseg2)
//     WHERE
//         cai.f12_cif IS NOT NULL
//         AND cai.etibiseg2 IS NOT NULL
//         AND TRIM(cai.etibiseg2) <> ''
//     GROUP BY cai.f12_cif
// ) sm_agg ON sm_agg.cif = ll.cif
// WHERE ll.as_at_date = '2026-06-15'   -- swap in your date
// ORDER BY canonical_segment, ll.name




// -- Cif level
// SELECT
//     cb.cif,
//     MAX(cb.customer_name) AS customer_name,
//     MAX(cb.branch_code)   AS branch_code,
//     GROUP_CONCAT(DISTINCT cb.cust_ac_no ORDER BY cb.cust_ac_no SEPARATOR ', ') AS account_numbers,
//     GROUP_CONCAT(DISTINCT cai.etibiseg2 ORDER BY cai.etibiseg2 SEPARATOR ', ') AS etibiseg2,
//     SUM(
//         CASE WHEN cb.lcy_balance > 0 THEN cb.lcy_balance ELSE 0 END
//     ) AS total_positive_balance
// FROM customer_balances cb
// LEFT JOIN customer_accounts_imports cai
//     ON cai.f12_ac_no = cb.cust_ac_no
// WHERE cb.balance_date = '2026-07-21'
//   AND cb.cif IS NOT NULL
//   AND (
//         cb.cif IN (
//             '470000068','470218244','470224763','470090458',
//             '470321717','470291487','470317567','470803302','470251434'
//         )
//         OR (
//             UPPER(TRIM(cb.branch_code)) <> 'P50'
//             AND (
//                 cb.cr_gl IS NULL
//                 OR cb.cr_gl <> '216220001'
//             )
//         )
//       )
// GROUP BY cb.cif
// ORDER BY total_positive_balance DESC;


// -- Account level
//  SELECT
//     cb.cif,
//     cb.cust_ac_no    AS account_number,
//     cb.customer_name,
//     cb.branch_code,
//     cai.etibiseg2,
//     cb.lcy_balance   AS balance
// FROM customer_balances cb
// LEFT JOIN customer_accounts_imports cai
//     ON cai.f12_ac_no = cb.cust_ac_no
// WHERE cb.balance_date = '2026-07-21'
//   AND cb.cif IS NOT NULL
//   AND cb.lcy_balance > 0
//   AND UPPER(TRIM(cb.branch_code)) <> 'P50'
//   AND (
//         cb.cr_gl IS NULL
//         OR cb.cr_gl <> '216220001'
//       )
// ORDER BY cb.cif, balance DESC;
