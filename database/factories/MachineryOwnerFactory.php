<?php

namespace Database\Factories;

use App\Models\MachineryOwner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MachineryOwnerFactory extends Factory
{
    protected $model = MachineryOwner::class;

    public function definition(): array
    {
        return [
            // إنشاء مستخدم وهمي وربطه كصاحب آلية
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'documents_path' => null, // نتركه فارغاً في الاختبارات لتجنب مشاكل الملفات الوهمية
        ];
    }
}
