<?php

namespace App\Http\Requests\MachineryOwner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMachineryOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // جلب معرف "صاحب الآلية" الحالي من الرابط (Route)
        // تأكد أن الاسم يطابق المتغير في الـ Controller (غالباً machinery_owner)
        $ownerId = $this->route('machinery_owner')->id ?? $this->machinery_owner;
        $userId = $this->route('machinery_owner')->user_id ?? null;

        return [
            // تحديث بيانات المالك مع استثناء السجل الحالي من فحص التكرار (Unique)
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('machinery_owners', 'phone')->ignore($ownerId)
            ],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            // منطق حساب المستخدم في التحديث
            'create_account' => ['nullable', 'boolean'],

            // التحقق من اسم المستخدم (إذا كان سيتم إنشاء حساب جديد أو تحديث حساب موجود)
            'username' => [
                'required_if:create_account,true',
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($userId)
            ],

            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($userId)
            ],

            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'اسم المستخدم هذا مستخدم بالفعل من قبل شخص آخر.',
            'phone.unique' => 'رقم الهاتف هذا مسجل لمالك آخر.',
        ];
    }
}
