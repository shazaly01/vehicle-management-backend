<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'emp_code', // كود الموظف
        'phone',
    ];

    /**
     * علاقة السائق بأوامر التشغيل (أذونات الخروج)
     */
    public function dispatchOrders(): HasMany
    {
        return $this->hasMany(DispatchOrder::class);
    }

    /**
     * علاقة السائق بالمعاملات المالية (علاقة مرنة Polymorphic)
     */
    public function financialTransactions(): MorphMany
    {
        return $this->morphMany(FinancialTransaction::class, 'related_entity');
    }


    public function documents(): MorphMany
{
    return $this->morphMany(\App\Models\Document::class, 'documentable');
}


public function messages(): MorphMany
{
    return $this->morphMany(Message::class, 'messageable');
}
}
