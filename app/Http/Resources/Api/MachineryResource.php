<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MachineryResource extends JsonResource
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
            'owner_id' => $this->owner_id,
            'plate_number_or_name' => $this->plate_number_or_name,
            'status' => $this->status,
            'type' => $this->type,
            'cost_type' => $this->cost_type,

            // العلاقات
            'owner' => new MachineryOwnerResource($this->whenLoaded('owner')),
            'dispatch_orders' => DispatchOrderResource::collection($this->whenLoaded('dispatchOrders')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
