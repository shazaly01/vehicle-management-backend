<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            // رصيد افتتاحي عشوائي بين 0 و 50,000
            'current_balance' => fake()->randomFloat(2, 0, 50000),
        ];
    }
}
