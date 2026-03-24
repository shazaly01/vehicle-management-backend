<?php

namespace App\Http\Requests\FinancialTransaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // رقم الحركة المالي: ليتوافق مع DECIMAL(18,0)
            'transaction_no' => ['required', 'numeric', 'digits_between:1,18', 'unique:financial_transactions,transaction_no'],

            'treasury_id' => ['nullable', 'exists:treasuries,id'],
            'transaction_type' => ['required', 'string', 'max:255'],

            // التحقق من أن الكيان المرتبط (Polymorphic) نص يمثل مسار المودل، والـ ID رقم
            'related_entity_type' => ['nullable', 'string', 'max:255'],
            'related_entity_id' => ['nullable', 'numeric'],

            'amount' => ['required', 'numeric', 'min:0'], // يمكن السماح بالقيم السالبة إذا كان نظامك يعتمد عليها للتسويات، وإلا min:0
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
