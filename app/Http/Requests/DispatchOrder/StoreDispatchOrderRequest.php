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
            // رقم الإذن: مطلوب، رقمي، لا يتجاوز 18 خانة (ليتوافق مع DECIMAL(18,0))، وفريد
            'order_no' => ['required', 'numeric', 'digits_between:1,18', 'unique:dispatch_orders,order_no'],

            'machinery_id' => ['required', 'exists:machineries,id'],
            'driver_id' => ['required', 'exists:drivers,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'project_id' => ['nullable', 'exists:projects,id'],

            'operation_type' => ['required', 'string', 'max:255'],
            'pricing_type' => ['required', 'string', 'in:trip,weight,hour,day'],

            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'total_cost' => ['required', 'numeric', 'min:0'],

            'shipped_material_note' => ['nullable', 'string', 'max:500'],
            'shipped_material_value' => ['nullable', 'numeric', 'min:0'],

            'status' => ['nullable', 'string', 'in:pending,active,completed,canceled'],
        ];
    }
}
