<?php

namespace Database\Factories;

use App\Models\Machinery;
use App\Models\MachineryOwner;
use Illuminate\Database\Eloquent\Factories\Factory;

class MachineryFactory extends Factory
{
    protected $model = Machinery::class;

    public function definition(): array
    {
        return [
            // إنشاء صاحب آلية جديد وربطه بالآلية تلقائياً إذا لم يتم تمرير ID
            'owner_id' => MachineryOwner::factory(),

            // توليد رقم لوحة عشوائي (مثال: شاحنة 1234-أ ب)
            'plate_number_or_name' => 'شاحنة ' . fake()->numerify('####') . ' - ' . fake()->lexify('??'),

            // حالة الآلية
            'status' => fake()->randomElement(['available', 'busy', 'maintenance']),

            // نوع تسعير التكلفة الخاص بالآلية
            'cost_type' => fake()->randomElement(['trip', 'weight', 'hour', 'day']),
        ];
    }
}
