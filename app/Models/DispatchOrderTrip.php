<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchOrderTrip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'dispatch_order_id',
        'machinery_id',
        'driver_id',
        'transport_cost_type',
        'quantity',
        'transport_unit_price',
        'status',
        'loaded_at',
        'delivered_at',
        'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'transport_unit_price' => 'decimal:2',
        'loaded_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }

    public function machinery(): BelongsTo
    {
        return $this->belongsTo(Machinery::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
