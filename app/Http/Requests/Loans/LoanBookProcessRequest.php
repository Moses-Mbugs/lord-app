<?php

namespace App\Http\Requests\Loans;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LoanBookProcessRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'loan_book_date' => [
                'nullable',
                'date',
            ],

            'portfolio_report' => [
                'required',
                'file',
                'mimes:xls,xlsx,csv',
            ],

            'credit_cards_report' => [
                'required',
                'file',
                'mimes:xls,xlsx,csv',
            ],
        ];
    }

    public function messages()
    {
        return [
            'portfolio_report.required' => 'Please attach the Portfolio Account Report.',
            'credit_cards_report.required' => 'Please attach the Credit Cards Report.',
        ];
    }
}
