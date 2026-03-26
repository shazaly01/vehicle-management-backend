<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_no',
        'supplier_id',
        'project_id',
        'operation_type',
        'target_quantity',
        'material_unit_price',
        'status',
    ];

    protected $casts = [
        // نحتفظ به كنص في لارافيل لمنع تحويله لـ Scientific Notation، رغم أنه في الداتا بيز Decimal
        'order_no' => 'string',
        'target_quantity' => 'decimal:2',
        'material_unit_price' => 'decimal:2',
    ];

    /**
     * توليد order_no أوتوماتيكياً عند الإنشاء
     */
protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        if (empty($model->order_no)) {
            // 1. جلب آخر رقم تم تسجيله في النظام
            $latestOrder = static::latest('id')->first();

            // 2. إذا كان هناك طلب سابق، نزيد الرقم واحد، وإذا لم يوجد نبدأ من 1
            // قمنا باستخدام الـ ID لضمان عدم التكرار وسهولة التسلسل
            $nextSequence = $latestOrder ? ($latestOrder->id + 1) : 1;

            // 3. تركيب السيريال: (سنة 26) + (رقم متسلسل من 4 خانات)
            // النتيجة ستكون مثلاً: 260001، 260002، وهكذا...
            $model->order_no = date('y') . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        }
    });
}
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * علاقة الأمر الرئيسي بالحركات/النقلات التابعة له
     */
    public function trips(): HasMany
    {
        return $this->hasMany(DispatchOrderTrip::class);
    }
}
