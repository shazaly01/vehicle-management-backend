<?php

namespace Database\Factories;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            // توليد رقم فريد يتكون من 18 خانة ليتناسب مع هيكلية قاعدة البيانات
            'emp_code' => fake()->unique()->numerify('##################'),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
