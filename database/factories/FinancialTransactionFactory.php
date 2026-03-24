<?php

namespace Database\Factories;

use App\Models\FinancialTransaction;
use App\Models\Treasury;
use App\Models\MachineryOwner;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialTransactionFactory extends Factory
{
    protected $model = FinancialTransaction::class;

    public function definition(): array
    {
        return [
            // رقم معاملة فريد من 18 خانة
            'transaction_no' => fake()->unique()->numerify('##################'),

            'treasury_id' => Treasury::factory(),
            'transaction_type' => fake()->randomElement(['سداد مستحقات', 'دفعة مقدمة', 'تسوية حساب', 'مصروفات تشغيل']),

            // العلاقة المرنة (نفترض افتراضياً أن المعاملة لصاحب آلية)
            'related_entity_type' => MachineryOwner::class,
            'related_entity_id' => MachineryOwner::factory(),

            'amount' => fake()->randomFloat(2, 100, 50000),
            'description' => fake()->sentence(),
        ];
    }
}
