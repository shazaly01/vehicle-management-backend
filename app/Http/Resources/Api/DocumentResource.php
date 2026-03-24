<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
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
            'name' => $this->name, // اسم المستند (مثلاً: رخصة قيادة، عقد إيجار)

            // الرابط الموقع (Signed URL) الذي ينتهي بعد 60 دقيقة للحماية
            'url' => $this->url,

            // استخراج امتداد الملف للعرض في الواجهة الأمامية (pdf, jpg, png)
            'extension' => $this->file_path ? strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)) : null,

            // معلومات الكيان المرتبط (Polymorphic)
            'target_id' => $this->documentable_id,
            'target_type' => $this->getFriendlyTypeName(), // تحويل اسم الكلاس إلى اسم بسيط للواجهة

            // تحميل بيانات الكيان المرتبط فقط إذا تم طلبها (Eager Loading)
            'target_details' => $this->whenLoaded('documentable'),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * دالة مساعدة لتحويل مساحة الأسماء (Namespace) إلى اسم بسيط مفهوم للواجهة الأمامية
     */
    private function getFriendlyTypeName(): string
    {
        return match ($this->documentable_type) {
            \App\Models\Driver::class => 'driver',
            \App\Models\Machinery::class => 'machinery',
            \App\Models\MachineryOwner::class => 'owner',
            \App\Models\Project::class => 'project',
            default => 'unknown',
        };
    }
}
