<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word() . '.pdf',
            'file_path' => 'private_documents/' . fake()->uuid() . '.pdf',
            // سيتم تحديد الـ documentable_type و id عند الاستدعاء في التيست
        ];
    }
}
