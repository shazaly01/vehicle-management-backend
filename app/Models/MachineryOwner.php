<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MachineryOwner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'documents_path',
    ];

    /**
     * علاقة المالك بحساب المستخدم (لتسجيل الدخول)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * علاقة المالك بالآليات التي يمتلكها
     */
    public function machineries(): HasMany
    {
        return $this->hasMany(Machinery::class, 'owner_id');
    }

    /**
     * علاقة المالك بالمعاملات المالية (علاقة مرنة Polymorphic)
     */
    public function financialTransactions(): MorphMany
    {
        return $this->morphMany(FinancialTransaction::class, 'related_entity');
    }

    public function messages(): MorphMany
{
    return $this->morphMany(Message::class, 'messageable');
}
}
