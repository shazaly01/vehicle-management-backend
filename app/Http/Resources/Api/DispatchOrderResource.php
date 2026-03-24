<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // تحويل الرقم الطويل إلى نص للحفاظ على الدقة
            'order_no' => (string) $this->order_no,

            'machinery_id' => $this->machinery_id,
            'driver_id' => $this->driver_id,
            'supplier_id' => $this->supplier_id,
            'project_id' => $this->project_id,

            'operation_type' => $this->operation_type,
            'pricing_type' => $this->pricing_type,

            // تحويل القيم المالية والكميات إلى أرقام عشرية
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_cost' => (float) $this->total_cost,

            'shipped_material_note' => $this->shipped_material_note,
            'shipped_material_value' => $this->shipped_material_value ? (float) $this->shipped_material_value : 0,

            'status' => $this->status,

            // العلاقات
            'machinery' => new MachineryResource($this->whenLoaded('machinery')),
            'driver' => new DriverResource($this->whenLoaded('driver')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'project' => new ProjectResource($this->whenLoaded('project')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
