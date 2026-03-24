<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * الحماية تتم عبر الـ Policy أو الـ Middleware في الراوت
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قواعد التحقق: نتحقق من محتوى الرسالة، ونوع المستلم وهويته
     */
    public function rules(): array
    {
        return [
            'content'        => 'required|string|max:500',

            // نوع المستلم: يجب أن يكون أحد الكيانات الثلاثة المتاحة في مشروعك
            'recipient_type' => 'required|in:owner,supplier,driver',

            // معرف المستلم: يجب أن يكون موجوداً
            'recipient_id'   => 'required|integer',

            // اختيارياً: يمكن إضافة حقل للرقم إذا أردنا السماح بتعديله يدوياً قبل الإرسال
            'phone'          => 'nullable|string|max:20',
        ];
    }

    /**
     * رسائل الخطأ المخصصة بالعربية
     */
    public function messages(): array
    {
        return [
            'content.required'        => 'نص الرسالة مطلوب ولا يمكن أن يكون فارغاً.',
            'content.max'             => 'نص الرسالة طويل جداً (الحد الأقصى 500 حرف).',
            'recipient_type.required' => 'يجب تحديد فئة المستلم (مالك، مورد، أو سائق).',
            'recipient_type.in'       => 'فئة المستلم المختارة غير صحيحة.',
            'recipient_id.required'   => 'يجب اختيار المستلم من القائمة.',
            'recipient_id.integer'    => 'معرف المستلم غير صالح.',
        ];
    }
}
