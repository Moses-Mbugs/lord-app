<?php

namespace App\Http\Requests\Loans;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LoanBookPmsUploadRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'pms_report' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:204800',
            ],
        ];
    }

    public function messages()
    {
        return [
            'pms_report.required' => 'Please upload the PMS Loan Proofing Report.',
            'pms_report.mimes' => 'The PMS Loan Proofing Report must be an Excel or CSV file.',
        ];
    }
}
