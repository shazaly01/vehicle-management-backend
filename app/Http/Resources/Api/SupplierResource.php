<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id, // أضفنا هذا
            'name' => $this->name,
            'phone' => $this->phone,
            'current_balance' => (float) $this->current_balance,

            // العلاقات
            'user' => new UserResource($this->whenLoaded('user')), // أضفنا هذا (تأكد من وجود UserResource)
            'dispatch_orders' => DispatchOrderResource::collection($this->whenLoaded('dispatchOrders')),
            'financial_transactions' => FinancialTransactionResource::collection($this->whenLoaded('financialTransactions')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
