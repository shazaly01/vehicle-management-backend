<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * تحويل كائن الرسالة إلى مصفوفة قابلة للقراءة من قبل Vue
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'content'        => $this->content,
            'phone'          => $this->phone,
            'type'           => $this->type,   // individual, automated
            'status'         => $this->status, // pending, sent, failed

            // --- بيانات المستلم (Polymorphic) ---
            'recipient_name' => $this->messageable?->name ?? $this->messageable?->full_name ?? '---',
            'recipient_type' => $this->formatRecipientType($this->messageable_type),
            'recipient_id'   => $this->messageable_id,

            // --- بيانات المرسل (الموظف) ---
            'sender'         => $this->sender?->full_name ?? 'نظام آلي',

            'error_log'      => $this->error_log,
            'created_at'     => $this->created_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * دالة مساعدة لتحويل اسم الكلاس إلى مسمى عربي مفهوم للواجهة
     */
    private function formatRecipientType($classPath): string
    {
        if (!$classPath) return 'غير محدد';

        return match ($classPath) {
            \App\Models\MachineryOwner::class => 'صاحب آلية',
            \App\Models\Supplier::class       => 'مورد',
            \App\Models\Driver::class         => 'سائق',
            default                           => 'أخرى',
        };
    }
}
