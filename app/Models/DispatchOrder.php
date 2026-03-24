<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_no',
        'machinery_id',
        'driver_id',
        'supplier_id',
        'project_id',
        'operation_type',
        'pricing_type',
        'quantity',
        'unit_price',
        'total_cost',
        'shipped_material_note',
        'shipped_material_value',
        'status',
    ];

    /**
     * تحديد نوع البيانات للحقول لضمان الدقة
     */
    protected $casts = [
        'order_no' => 'string', // للحفاظ على دقة الـ 18 رقماً
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'shipped_material_value' => 'decimal:2',
    ];

    /**
     * علاقة إذن الخروج بالآلية المستخدمة
     */
    public function machinery(): BelongsTo
    {
        return $this->belongsTo(Machinery::class);
    }

    /**
     * علاقة إذن الخروج بالسائق المنفذ
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * علاقة إذن الخروج بالمورد (اختياري)
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * علاقة إذن الخروج بالمشروع (اختياري)
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
