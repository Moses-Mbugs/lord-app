<?php

namespace App\Http\Requests\Loans;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LoanBookDetailsUploadRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'loan_details_report' => [
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
            'loan_details_report.required' => 'Please upload the Loans Details Report.',
            'loan_details_report.mimes' => 'The Loans Details Report must be an Excel or CSV file.',
        ];
    }
}
