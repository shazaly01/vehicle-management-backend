<?php

namespace App\Http\Requests\DispatchOrder;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispatchOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // بيانات العقد الرئيسي
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'operation_type' => ['required', 'string', 'max:255'],
            'target_quantity' => ['required', 'numeric', 'min:0'],
            'material_unit_price' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:active,completed,canceled'],

            // --- التعديل الجوهري لدعم "الحشر" ---
            // نتحقق أن trips هي مصفوفة (Array)
            'trips' => ['nullable', 'array'],
            // نتحقق من محتويات كل شاحنة داخل المصفوفة
            'trips.*.machinery_id' => ['required_with:trips', 'exists:machineries,id'],
            'trips.*.quantity' => ['required_with:trips', 'numeric', 'min:0.1'],
            'trips.*.status' => ['nullable', 'string', 'in:dispatched,loaded,delivered'],
        ];
    }

    /**
     * تخصيص أسماء الحقول لتظهر أخطاء مفهومة بالعربية
     */
    public function attributes(): array
    {
        return [
            'trips.*.machinery_id' => 'الآلية في قائمة الشاحنات',
            'trips.*.quantity' => 'الكمية لكل شاحنة',
            'project_id' => 'المشروع',
            'target_quantity' => 'الكمية المستهدفة',
        ];
    }
}
