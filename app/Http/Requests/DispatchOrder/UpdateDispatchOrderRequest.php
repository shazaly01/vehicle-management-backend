<?php

namespace App\Http\Requests\DispatchOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDispatchOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'operation_type' => ['sometimes', 'required', 'string', 'max:255'],
            'target_quantity' => ['sometimes', 'required', 'numeric', 'min:0'],
            'material_unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:active,completed,canceled'],

            // السماح بتحديث قائمة الشاحنات (Trips) أثناء تعديل الأمر
            'trips' => ['nullable', 'array'],
            'trips.*.id' => ['nullable', 'exists:dispatch_order_trips,id'], // للتعرف على الحركات الموجودة مسبقاً
            'trips.*.machinery_id' => ['required_with:trips', 'exists:machineries,id'],
            'trips.*.quantity' => ['required_with:trips', 'numeric', 'min:0.1'],
            'trips.*.status' => ['nullable', 'string', 'in:dispatched,loaded,delivered'],
        ];
    }

    public function attributes(): array
    {
        return [
            'trips.*.machinery_id' => 'الآلية المختارة',
            'trips.*.quantity' => 'كمية الحركة',
        ];
    }
}
