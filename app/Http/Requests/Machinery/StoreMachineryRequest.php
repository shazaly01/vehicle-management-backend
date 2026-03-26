<?php

namespace App\Http\Requests\Machinery;

use Illuminate\Foundation\Http\FormRequest;

class StoreMachineryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'exists:machinery_owners,id'],
            'driver_id' => ['required', 'exists:drivers,id'],
            'plate_number_or_name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:available,busy,maintenance'],
            'cost_type' => ['nullable', 'string', 'in:trip,weight,hour,day'],
        ];
    }
}
