<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // 1. مصفوفة ترجمة أنواع العمليات (أضف الأنواع الموجودة في قاعدة بياناتك)
        $operationTypeLabels = [
            'supply'    => 'عملية توريد',
            'transport' => 'عملية نقل',
            'internal'  => 'تشغيل داخلي',
        ];

        return [
            'id' => $this->id,
            'order_no' => (string) $this->order_no,

            // 2. نوع العملية (الكود + المسمى العربي)
            'operation_type' => $this->operation_type,
            'operation_type_label' => $operationTypeLabels[$this->operation_type] ?? $this->operation_type,

            // 3. جلب الأسماء مباشرة للسهولة (اختياري لكنه مفيد جداً للـ Vue)
            // نستخدم ?-> لضمان عدم حدوث خطأ إذا كانت العلاقة فارغة
            'supplier_name' => $this->supplier?->name ?? 'غير محدد',
            'project_name'  => $this->project?->name ?? 'غير محدد',

            'supplier_id' => $this->supplier_id,
            'project_id' => $this->project_id,

            'target_quantity' => (float) $this->target_quantity,
            'material_unit_price' => (float) $this->material_unit_price,

            'status' => $this->status,

            // 4. العلاقات الكاملة (تُحمل فقط عند الحاجة باستخدام whenLoaded)
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'project'  => new ProjectResource($this->whenLoaded('project')),

            'trips' => DispatchOrderTripResource::collection($this->whenLoaded('trips')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
