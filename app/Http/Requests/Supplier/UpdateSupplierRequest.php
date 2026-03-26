<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // جلب المورد الحالي من المسار (Route)
        $supplier = $this->route('supplier');
        // جلب معرف المستخدم المرتبط بهذا المورد (إن وجد) لاستثنائه من قواعد الـ Unique
        $userId = $supplier->user_id ?? null;

        return [
            // حقول المورد الأساسية (sometimes تعني أنه يتم التحقق منها فقط إذا تم إرسالها)
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('suppliers', 'phone')->ignore($supplier->id) // استثناء المورد الحالي
            ],
            'current_balance' => ['nullable', 'numeric'],

            // حقل التحكم
            'create_account' => ['nullable', 'boolean'],

            // حقول المستخدم
            'username' => [
                'required_if:create_account,true',
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($userId) // استثناء المستخدم الحالي
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'password' => [
                'nullable',
                'string',
                'min:8'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required_if' => 'اسم المستخدم مطلوب عند اختيار إنشاء حساب دخول.',
            'username.unique' => 'اسم المستخدم هذا محجوز مسبقاً، اختر اسماً آخر.',
            'phone.unique' => 'رقم الهاتف هذا مسجل لمورد آخر بالفعل.',
        ];
    }
}
