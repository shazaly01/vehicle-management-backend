<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'name' => $this->name,
            'location' => $this->location,
            'status' => $this->status,

            // العلاقات (يتم تحميلها فقط إذا تم طلبها عبر with في الـ Controller)
            'dispatch_orders' => DispatchOrderResource::collection($this->whenLoaded('dispatchOrders')),
            'financial_transactions' => FinancialTransactionResource::collection($this->whenLoaded('financialTransactions')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
