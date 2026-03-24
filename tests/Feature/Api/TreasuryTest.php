<?php

namespace Tests\Feature\Api;

use App\Models\Treasury;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class TreasuryTest extends ApiTestCase
{
    #[Test]
    public function a_super_admin_can_view_all_treasuries(): void
    {
        Treasury::factory()->count(2)->create();

        $response = $this->getJson('/api/treasuries');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    #[Test]
    public function a_supervisor_cannot_create_a_treasury(): void
    {
        Sanctum::actingAs($this->supervisorUser);
        $treasuryData = Treasury::factory()->make()->toArray();

        $response = $this->postJson('/api/treasuries', $treasuryData);

        $response->assertForbidden(); // 403
    }

    #[Test]
    public function an_accountant_can_create_a_treasury(): void
    {
        // المحاسب يجب أن يكون قادراً على إدارة الخزائن
        Sanctum::actingAs($this->accountantUser);

        $treasuryData = [
            'name' => 'خزينة فرع الرياض',
            'balance' => 50000.00
        ];

        $response = $this->postJson('/api/treasuries', $treasuryData);

        $response->assertCreated(); // 201
        $this->assertDatabaseHas('treasuries', ['name' => 'خزينة فرع الرياض']);
    }

    #[Test]
    public function an_admin_can_update_a_treasury(): void
    {
        Sanctum::actingAs($this->adminUser);
        $treasury = Treasury::factory()->create(['name' => 'خزينة قديمة']);

        $updateData = [
            'name' => 'خزينة محدثة',
            'balance' => 10000.50
        ];

        $response = $this->putJson("/api/treasuries/{$treasury->id}", $updateData);

        $response->assertOk();
        $this->assertDatabaseHas('treasuries', ['name' => 'خزينة محدثة']);
    }

    #[Test]
    public function an_employee_cannot_delete_a_treasury(): void
    {
        Sanctum::actingAs($this->employeeUser);
        $treasury = Treasury::factory()->create();

        $response = $this->deleteJson("/api/treasuries/{$treasury->id}");

        $response->assertForbidden(); // 403
        $this->assertDatabaseHas('treasuries', ['id' => $treasury->id]);
    }
}
