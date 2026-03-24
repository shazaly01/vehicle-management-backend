<?php

namespace Tests\Feature\Api;

use App\Models\Machinery;
use App\Models\MachineryOwner;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class MachineryTest extends ApiTestCase
{
    #[Test]
    public function a_super_admin_can_view_all_machineries(): void
    {
        Machinery::factory()->count(5)->create();

        $response = $this->getJson('/api/machineries');

        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    #[Test]
    public function a_machinery_owner_can_only_view_his_own_machineries(): void
    {
        // 1. إنشاء ملف مالك لمستخدم "صاحب الآلية" الذي جهزناه
        $myOwnerProfile = MachineryOwner::factory()->create([
            'user_id' => $this->machineryOwnerUser->id
        ]);

        // 2. إنشاء آليتين تتبعان لهذا المالك
        Machinery::factory()->count(2)->create([
            'owner_id' => $myOwnerProfile->id
        ]);

        // 3. إنشاء 3 آليات تتبع لمالك آخر (وهمي)
        Machinery::factory()->count(3)->create();

        // 4. تسجيل الدخول كصاحب الآلية
        Sanctum::actingAs($this->machineryOwnerUser);

        // 5. جلب قائمة الآليات
        $response = $this->getJson('/api/machineries');

        // 6. التحقق: يجب أن يرى فقط الآليتين الخاصتين به، وليس الـ 5 آليات كلها
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    #[Test]
    public function an_employee_can_create_a_machinery(): void
    {
        Sanctum::actingAs($this->employeeUser);

        $owner = MachineryOwner::factory()->create();

        $machineryData = [
            'owner_id' => $owner->id,
            'plate_number_or_name' => 'شاحنة مرسيدس 123',
            'status' => 'available',
            'cost_type' => 'trip',
        ];

        $response = $this->postJson('/api/machineries', $machineryData);

        $response->assertCreated(); // 201
        $this->assertDatabaseHas('machineries', ['plate_number_or_name' => 'شاحنة مرسيدس 123']);
    }

    #[Test]
    public function an_admin_can_update_machinery_status(): void
    {
        Sanctum::actingAs($this->adminUser);
        $machinery = Machinery::factory()->create(['status' => 'available']);

        $updateData = [
            'owner_id' => $machinery->owner_id,
            'plate_number_or_name' => $machinery->plate_number_or_name,
            'status' => 'maintenance',
            'cost_type' => $machinery->cost_type,
        ];

        $response = $this->putJson("/api/machineries/{$machinery->id}", $updateData);

        $response->assertOk();
        $this->assertDatabaseHas('machineries', [
            'id' => $machinery->id,
            'status' => 'maintenance'
        ]);
    }

    #[Test]
    public function an_accountant_cannot_delete_a_machinery(): void
    {
        Sanctum::actingAs($this->accountantUser);
        $machinery = Machinery::factory()->create();

        $response = $this->deleteJson("/api/machineries/{$machinery->id}");

        $response->assertForbidden(); // 403
    }
}
