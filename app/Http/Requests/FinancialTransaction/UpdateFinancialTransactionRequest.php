<?php

namespace App\Http\Requests\FinancialTransaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $transactionId = $this->route('financial_transaction') ? $this->route('financial_transaction')->id : null;

        return [
            'transaction_no' => ['sometimes', 'required', 'numeric', 'digits_between:1,18', 'unique:financial_transactions,transaction_no,' . $transactionId],

            'treasury_id' => ['nullable', 'exists:treasuries,id'],
            'transaction_type' => ['sometimes', 'required', 'string', 'max:255'],

            'related_entity_type' => ['nullable', 'string', 'max:255'],
            'related_entity_id' => ['nullable', 'numeric'],

            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
