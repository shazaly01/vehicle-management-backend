<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => 'مشروع ' . fake()->company(),
            'location' => fake()->address(),
            // نختار الحالة عشوائياً بين نشط ومكتمل (مع إعطاء احتمالية أكبر للمشاريع النشطة)
            'status' => fake()->randomElement(['active', 'active', 'active', 'completed']),
        ];
    }
}
