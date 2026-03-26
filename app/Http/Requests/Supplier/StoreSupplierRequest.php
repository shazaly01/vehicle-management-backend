<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // حقول المورد الأساسية
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:suppliers,phone'],
            // حقل الرصيد الافتتاحي (بناءً على جدول الموردين لديك)
            'current_balance' => ['nullable', 'numeric'],

            // حقل التحكم: هل ننشئ حساب دخول؟
            'create_account' => ['required', 'boolean'],

            // حقول المستخدم (تُفحص فقط إذا كان create_account يساوي true)
            'username' => [
                'required_if:create_account,true',
                'nullable',
                'string',
                'max:50',
                'unique:users,username'
            ],
            'email' => [
                'nullable',
                'email',
                'unique:users,email'
            ],
            // كلمة السر اختيارية، إذا لم تُرسل سيتم استخدام رقم الهاتف كافتراضي في الـ Controller
            'password' => [
                'nullable',
                'string',
                'min:8'
            ],
        ];
    }

    /**
     * تخصيص رسائل الخطأ لتكون واضحة للمستخدم
     */
    public function messages(): array
    {
        return [
            'username.required_if' => 'اسم المستخدم مطلوب عند اختيار إنشاء حساب دخول.',
            'username.unique' => 'اسم المستخدم هذا محجوز مسبقاً، اختر اسماً آخر.',
            'phone.unique' => 'رقم الهاتف هذا مسجل لمورد آخر بالفعل.',
        ];
    }
}
