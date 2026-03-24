<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treasury extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'balance',
    ];

    /**
     * علاقة الخزينة بالمعاملات المالية
     * (جميع الحركات من سحب وإيداع التي تمت عبر هذه الخزينة)
     */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }
}
