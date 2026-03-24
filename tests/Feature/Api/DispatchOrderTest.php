<?php

namespace Tests\Feature\Api;

use App\Models\DispatchOrder;
use App\Models\Machinery;
use App\Models\MachineryOwner;
use App\Models\Driver;
use App\Models\Supplier;
use App\Models\Project;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class DispatchOrderTest extends ApiTestCase
{
    #[Test]
    public function a_super_admin_can_view_all_dispatch_orders(): void
    {
        DispatchOrder::factory()->count(3)->create();

        $response = $this->getJson('/api/dispatch_orders');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    #[Test]
    public function a_machinery_owner_can_only_view_dispatch_orders_for_his_machineries(): void
    {
        // 1. إعداد صاحب آلية وربطه بالمستخدم الحالي
        $myOwnerProfile = MachineryOwner::factory()->create(['user_id' => $this->machineryOwnerUser->id]);
        $myMachinery = Machinery::factory()->create(['owner_id' => $myOwnerProfile->id]);

        // 2. إنشاء إذن خروج يخص آليته
        DispatchOrder::factory()->create(['machinery_id' => $myMachinery->id]);

        // 3. إنشاء إذن خروج يخص آلية أخرى (وهمية)
        DispatchOrder::factory()->create();

        // 4. تسجيل الدخول كصاحب الآلية
        Sanctum::actingAs($this->machineryOwnerUser);

        // 5. جلب البيانات
        $response = $this->getJson('/api/dispatch_orders');

        // 6. التحقق من أنه يرى إذن خروج واحد فقط
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    #[Test]
    public function an_employee_can_create_a_dispatch_order(): void
    {
        Sanctum::actingAs($this->employeeUser);

        $machinery = Machinery::factory()->create();
        $driver = Driver::factory()->create();
        $supplier = Supplier::factory()->create();
        $project = Project::factory()->create();

        // توليد رقم طلب من 18 خانة
        $orderNo = '100000000000000001';

        $orderData = [
            'order_no' => $orderNo,
            'machinery_id' => $machinery->id,
            'driver_id' => $driver->id,
            'supplier_id' => $supplier->id,
            'project_id' => $project->id,
            'operation_type' => 'نقل رمل',
            'pricing_type' => 'trip',
            'quantity' => 10,
            'unit_price' => 150,
            'total_cost' => 1500, // 10 * 150
            'status' => 'pending',
        ];

        $response = $this->postJson('/api/dispatch_orders', $orderData);

        $response->assertCreated(); // 201
        $this->assertDatabaseHas('dispatch_orders', [
            'order_no' => $orderNo,
            'total_cost' => 1500
        ]);
    }

    #[Test]
    public function an_admin_can_update_a_dispatch_order(): void
    {
        Sanctum::actingAs($this->adminUser);
        $order = DispatchOrder::factory()->create(['status' => 'pending']);

        $updateData = array_merge($order->toArray(), [
            'status' => 'completed',
        ]);

        $response = $this->putJson("/api/dispatch_orders/{$order->id}", $updateData);

        $response->assertOk();
        $this->assertDatabaseHas('dispatch_orders', [
            'id' => $order->id,
            'status' => 'completed'
        ]);
    }

    #[Test]
    public function an_accountant_cannot_delete_a_dispatch_order(): void
    {
        Sanctum::actingAs($this->accountantUser);
        $order = DispatchOrder::factory()->create();

        $response = $this->deleteJson("/api/dispatch_orders/{$order->id}");

        $response->assertForbidden(); // 403
    }
}
