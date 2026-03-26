<?php

namespace App\Http\Requests\DispatchOrder;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispatchOrderTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // يجب ربط الحركة بأمر التشغيل الرئيسي
            'dispatch_order_id' => ['required', 'exists:dispatch_orders,id'],

            // نختار الآلية فقط (السائق سيتم سحبه برمجياً في الكنترولر لضمان الدقة)
            'machinery_id' => ['required', 'exists:machineries,id'],

            // الكمية المنفذة في هذه الحركة (مثلاً 1 شحنة، أو 20 طن)
            'quantity' => ['required', 'numeric', 'min:0.01'],

            // الملاحظات اختيارية
            'note' => ['nullable', 'string', 'max:500'],

            // الحالة الافتراضية عند الإنشاء
            'status' => ['nullable', 'string', 'in:dispatched,loaded,delivered,canceled'],
        ];
    }
}
