<?php

namespace App\Http\Requests\Loans;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LoanUtilizationUploadRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'loans_portfolio_file' => [
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
            'loans_portfolio_file.required' => 'Please upload the LOANS PORTFOLIO NEW report.',
            'loans_portfolio_file.mimes' => 'The LOANS PORTFOLIO NEW report must be an Excel or CSV file.',
        ];
    }
}
