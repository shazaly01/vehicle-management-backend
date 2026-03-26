<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchOrderTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // مصفوفة الترجمة لتظهر المسميات بشكل صحيح في الواجهة
        $costTypeLabels = [
            'trip'   => 'بالنقلة',
            'hour'   => 'بالساعة',
            'day'    => 'باليومية',
            'weight' => 'بالوزن',
        ];

        return [
            'id' => $this->id,
            'dispatch_order_id' => $this->dispatch_order_id,
            'machinery_id' => $this->machinery_id,
            'driver_id' => $this->driver_id,

            // 1. الكود التقني (للحسابات)
            'transport_cost_type' => $this->transport_cost_type,

            // 2. المسمى النصي (للعرض في الجدول) - هذا ما كان ينقصك
            'cost_type_label' => $costTypeLabels[$this->transport_cost_type] ?? $this->transport_cost_type,

            'quantity' => (float) $this->quantity,
            'transport_unit_price' => (float) $this->transport_unit_price,

            'total_transport_cost' => (float) ($this->quantity * $this->transport_unit_price),

            'status' => $this->status,
            'loaded_at' => $this->loaded_at?->format('Y-m-d H:i:s'),
            'delivered_at' => $this->delivered_at?->format('Y-m-d H:i:s'),

            'note' => $this->note,
            'dispatch_order' => new DispatchOrderResource($this->whenLoaded('dispatchOrder')),

            'machinery' => new MachineryResource($this->whenLoaded('machinery')),
            'driver' => new DriverResource($this->whenLoaded('driver')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
