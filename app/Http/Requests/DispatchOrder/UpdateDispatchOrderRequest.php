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
        // استخراج الـ ID لاستثنائه من قاعدة unique
        $dispatchOrderId = $this->route('dispatch_order') ? $this->route('dispatch_order')->id : null;

        return [
            'order_no' => ['sometimes', 'required', 'numeric', 'digits_between:1,18', 'unique:dispatch_orders,order_no,' . $dispatchOrderId],

            'machinery_id' => ['sometimes', 'required', 'exists:machineries,id'],
            'driver_id' => ['sometimes', 'required', 'exists:drivers,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'project_id' => ['nullable', 'exists:projects,id'],

            'operation_type' => ['sometimes', 'required', 'string', 'max:255'],
            'pricing_type' => ['sometimes', 'required', 'string', 'in:trip,weight,hour,day'],

            'quantity' => ['sometimes', 'required', 'numeric', 'min:0'],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'total_cost' => ['sometimes', 'required', 'numeric', 'min:0'],

            'shipped_material_note' => ['nullable', 'string', 'max:500'],
            'shipped_material_value' => ['nullable', 'numeric', 'min:0'],

            'status' => ['nullable', 'string', 'in:pending,active,completed,canceled'],
        ];
    }
}
