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
            'name' => $this->name,
            'phone' => $this->phone,
            'current_balance' => (float) $this->current_balance,

            // العلاقات
            'dispatch_orders' => DispatchOrderResource::collection($this->whenLoaded('dispatchOrders')),
            'financial_transactions' => FinancialTransactionResource::collection($this->whenLoaded('financialTransactions')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
