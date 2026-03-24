<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancialTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_no',
        'treasury_id',
        'transaction_type',
        'related_entity_type',
        'related_entity_id',
        'amount',
        'description',
    ];

    /**
     * تحديد نوع البيانات للحقول لضمان الدقة
     */
    protected $casts = [
        'transaction_no' => 'string', // للحفاظ على دقة الـ 18 رقماً
        'amount' => 'decimal:2',
    ];

    /**
     * علاقة المعاملة بالخزينة (اختياري في حال التسويات القيودية)
     */
    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    /**
     * العلاقة المرنة (Polymorphic)
     * هذه الدالة تجلب الكيان المرتبط بالمعاملة تلقائياً
     * (سواء كان مورد، صاحب آلية، مشروع، أو سائق)
     */
    public function related_entity(): MorphTo
    {
        return $this->morphTo();
    }
}
