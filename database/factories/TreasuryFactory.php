<?php

namespace Database\Factories;

use App\Models\Treasury;
use Illuminate\Database\Eloquent\Factories\Factory;

class TreasuryFactory extends Factory
{
    protected $model = Treasury::class;

    public function definition(): array
    {
        return [
            'name' => 'خزينة ' . fake()->word(),
            // رصيد افتتاحي عشوائي للخزينة
            'balance' => fake()->randomFloat(2, 10000, 500000),
        ];
    }
}
