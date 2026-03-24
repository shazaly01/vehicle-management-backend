<?php

namespace App\Http\Requests\Document;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // يمكنك تغييره حسب الـ Policy الخاصة بك، أو جعله true مؤقتاً
        return true;
    }

public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,png|max:25600',
            // التعديل 1: أضفنا driver هنا
            'target_type' => 'required|in:owner,machinery,project,driver',
            'target_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $type = $this->input('target_type');
                    // التعديل 2: أضفنا driver إلى الـ match
                    $table = match($type) {
                        'owner' => 'machinery_owners',
                        'machinery' => 'machineries',
                        'project' => 'projects',
                        'driver' => 'drivers',
                        default => null,
                    };

                    if ($table && !\Illuminate\Support\Facades\DB::table($table)->where('id', $value)->exists()) {
                         $fail("السجل المختار غير موجود في النظام.");
                    }
                },
            ],
        ];
    }
}
