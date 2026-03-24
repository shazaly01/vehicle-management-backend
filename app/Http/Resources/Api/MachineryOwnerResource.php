<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
// بافتراض أن UserResource موجود في نفس المجلد، وإلا قم باستدعائه بـ use App\Http\Resources\UserResource;

class MachineryOwnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'documents_path' => $this->documents_path ? asset('storage/' . $this->documents_path) : null,

            // العلاقات
            'user' => new UserResource($this->whenLoaded('user')),
            'machineries' => MachineryResource::collection($this->whenLoaded('machineries')),
            'financial_transactions' => FinancialTransactionResource::collection($this->whenLoaded('financialTransactions')),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
