<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // نجلب الـ ID الخاص بالسائق من المسار (Route) لاستثنائه من قاعدة unique أثناء التحديث
        $driverId = $this->route('driver') ? $this->route('driver')->id : null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'emp_code' => ['sometimes', 'required', 'numeric', 'digits_between:9,18', 'unique:drivers,emp_code,' . $driverId],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
