<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // تحويل كود الموظف إلى نص للحفاظ على دقة DECIMAL(18, 0) في الواجهة الأمامية
            'emp_code' => (string) $this->emp_code,
            'phone' => $this->phone,

            // العلاقات
            'dispatch_orders' => DispatchOrderResource::collection($this->whenLoaded('dispatchOrders')),
            'financial_transactions' => FinancialTransactionResource::collection($this->whenLoaded('financialTransactions')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
