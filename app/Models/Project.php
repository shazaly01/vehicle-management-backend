<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'location',
        'status',
    ];

    /**
     * علاقة المشروع بأوامر التشغيل (أذونات الخروج المتجهة لهذا المشروع)
     */
    public function dispatchOrders(): HasMany
    {
        return $this->hasMany(DispatchOrder::class);
    }

    /**
     * علاقة المشروع بالمعاملات المالية (علاقة مرنة Polymorphic)
     * لتسجيل أي مصاريف أو دفعات تمت مباشرة على حساب المشروع
     */
    public function financialTransactions(): MorphMany
    {
        return $this->morphMany(FinancialTransaction::class, 'related_entity');
    }


        public function documents(): MorphMany
{
    return $this->morphMany(\App\Models\Document::class, 'documentable');
}
}
