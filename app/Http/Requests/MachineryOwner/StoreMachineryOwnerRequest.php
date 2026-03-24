<?php

namespace App\Http\Requests\MachineryOwner;

use Illuminate\Foundation\Http\FormRequest;

class StoreMachineryOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // حقول صاحب الآلية الأساسية
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:machinery_owners,phone'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

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
            // إذا كنت تريد السماح بكلمة سر مخصصة، وإلا سنعتمد رقم الهاتف ككلمة سر افتراضية
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
            'phone.unique' => 'رقم الهاتف هذا مسجل لمالك آخر بالفعل.',
        ];
    }
}
