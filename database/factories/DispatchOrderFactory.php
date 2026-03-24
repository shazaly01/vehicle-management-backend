<?php

namespace Database\Factories;

use App\Models\DispatchOrder;
use App\Models\Machinery;
use App\Models\Driver;
use App\Models\Supplier;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class DispatchOrderFactory extends Factory
{
    protected $model = DispatchOrder::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 100);
        $unitPrice = fake()->randomFloat(2, 50, 500);

        return [
            // توليد رقم إذن خروج فريد من 18 خانة
            'order_no' => fake()->unique()->numerify('##################'),

            // ربط الإذن بآلية وسائق، ومورد ومشروع بشكل تلقائي
            'machinery_id' => Machinery::factory(),
            'driver_id' => Driver::factory(),
            'supplier_id' => Supplier::factory(),
            'project_id' => Project::factory(),

            // بيانات العملية
            'operation_type' => fake()->randomElement(['نقل رمل', 'توريد أسمنت', 'نقل حجر', 'أعمال حفر', 'تسوية أرض']),
            'pricing_type' => fake()->randomElement(['trip', 'weight', 'hour', 'day']),

            // الحسابات المالية للعملية
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_cost' => $quantity * $unitPrice,

            // بيانات المواد المشحونة
            'shipped_material_note' => fake()->sentence(),
            'shipped_material_value' => fake()->randomFloat(2, 1000, 20000),

            // حالة الإذن
            'status' => fake()->randomElement(['pending', 'completed', 'canceled']),
        ];
    }
}
