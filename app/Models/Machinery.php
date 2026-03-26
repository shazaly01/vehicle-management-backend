<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Machinery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'driver_id',
        'plate_number_or_name',
        'type',
        'status',
        'cost_type',
    ];

    /**
     * علاقة الآلية بمالكها (شركة أو فرد)
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(MachineryOwner::class, 'owner_id');
    }

    /**
     * علاقة الآلية بأوامر التشغيل (سجل العمليات والرحلات الخاصة بها)
     */
    public function dispatchOrders(): HasMany
    {
        return $this->hasMany(DispatchOrder::class);
    }


    public function documents(): MorphMany
{
    return $this->morphMany(\App\Models\Document::class, 'documentable');
}

public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
