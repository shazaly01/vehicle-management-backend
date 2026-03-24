<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'phone',
        'type',
        'status',
        'messageable_id',
        'messageable_type',
        'sender_id',
        'error_log',
    ];

    /**
     * العلاقة متعددة الأشكال: تسمح للرسالة بالانتماء لأي موديل (Owner, Supplier, Driver)
     */
    public function messageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * المستخدم (الموظف) الذي قام بإرسال الرسالة
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
