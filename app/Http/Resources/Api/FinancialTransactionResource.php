<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 1. أزلنا (string) من المعرفات لتعود لطبيعتها وتتطابق مع القوائم
            'id' => $this->id,

            // تحويل الرقم الطويل إلى نص للحفاظ على الدقة (هذا صحيح نبقيه كما هو)
            'transaction_no' => (string) $this->transaction_no,

            'treasury_id' => $this->treasury_id,
            'transaction_type' => $this->transaction_type,

            // الكيان المرتبط (Polymorphic)
            'related_entity_type' => $this->related_entity_type,
            'related_entity_id' => $this->related_entity_id,

            // 2. السحر هنا: نرسل الحقول مجهزة ومفصلة للواجهة لتقرأها مباشرة
            'project_id' => $this->related_entity_type === 'App\\Models\\Project' ? $this->related_entity_id : null,
            'supplier_id' => $this->related_entity_type === 'App\\Models\\Supplier' ? $this->related_entity_id : null,
            'machinery_owner_id' => $this->related_entity_type === 'App\\Models\\MachineryOwner' ? $this->related_entity_id : null,

            'amount' => (float) $this->amount,
            'description' => $this->description,

            // العلاقات
            'treasury' => new TreasuryResource($this->whenLoaded('treasury')),

            // بالنسبة للعلاقة المرنة (morphTo)، يمكننا إرجاعها بناءً على نوعها إذا كانت محملة
            'related_entity' => $this->whenLoaded('related_entity', function () {
                $entity = $this->related_entity;

                // تحديد الـ Resource المناسب بناءً على نوع الكلاس
                return match (get_class($entity)) {
                    \App\Models\Supplier::class => new SupplierResource($entity),
                    \App\Models\MachineryOwner::class => new MachineryOwnerResource($entity),
                    \App\Models\Project::class => new ProjectResource($entity),
                    \App\Models\Driver::class => new DriverResource($entity),
                    default => $entity,
                };
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
