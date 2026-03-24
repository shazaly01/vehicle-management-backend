<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'username' => $this->username,
            'email' => $this->email,
            'created_at' => $this->created_at?->toDateTimeString(),

            // تحميل الأدوار
            'roles' => RoleResource::collection($this->whenLoaded('roles')),

            // --- [التعديل هنا] إضافة بيانات المالك المرتبط ---
            // نستخدم whenLoaded لضمان عدم حدوث خطأ إذا لم تكن العلاقة محملة
            'machinery_owner' => new MachineryOwnerResource($this->whenLoaded('machineryOwner')),
        ];
    }
}
