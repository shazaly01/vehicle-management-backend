<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        // إرجاع true هنا صحيح تماماً، لأننا نقوم بالتحقق من الصلاحيات
        // عبر $this->authorize('create', Driver::class) داخل الـ Controller
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            // التعديل هنا: استبدال numeric بـ string
            // digits_between ستضمن أن النص المدخل عبارة عن أرقام فقط (بدون فواصل أو أحرف)
            'emp_code' => ['required', 'string', 'digits_between:9,18', 'unique:drivers,emp_code'],

            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * تخصيص رسائل الخطأ لتكون واضحة للواجهة الأمامية
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم السائق مطلوب.',
            'emp_code.required' => 'كود الموظف مطلوب.',
            'emp_code.digits_between' => 'كود الموظف يجب أن يتكون من 9 إلى 18 رقماً.',
            'emp_code.unique' => 'كود الموظف هذا مسجل مسبقاً لسائق آخر.',
        ];
    }
}
