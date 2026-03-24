<?php

namespace Tests\Feature\Api;

use App\Models\Driver;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class DriverTest extends ApiTestCase
{
    #[Test]
    public function a_super_admin_can_view_all_drivers(): void
    {
        // Arrange
        Driver::factory()->count(4)->create();

        // Act: السوبر آدمن مسجل دخول افتراضياً من ApiTestCase
        $response = $this->getJson('/api/drivers');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(4, 'data');
    }

    #[Test]
    public function a_machinery_owner_cannot_create_a_driver(): void
    {
        // Arrange: صاحب الآلية لا ينبغي أن يكون قادراً على إضافة سائقين للنظام
        Sanctum::actingAs($this->machineryOwnerUser);
        $driverData = Driver::factory()->make()->toArray();

        // Act
        $response = $this->postJson('/api/drivers', $driverData);

        // Assert
        $response->assertForbidden(); // 403 Forbidden
    }

    #[Test]
    public function an_employee_can_create_a_driver(): void
    {
        // Arrange: الموظف يمتلك صلاحية الإنشاء
        Sanctum::actingAs($this->employeeUser);

        // سنحدد كود موظف يتكون من 18 رقماً للتأكد من توافقه مع DECIMAL(18, 0)
        $empCode = '123456789012345678';

        $driverData = [
            'name' => 'أحمد السائق',
            'emp_code' => $empCode,
            'phone' => '0501234567',
        ];

        // Act
        $response = $this->postJson('/api/drivers', $driverData);

        // Assert
        $response->assertCreated(); // 201 Created

        // التأكد من الحفظ في قاعدة البيانات
        $this->assertDatabaseHas('drivers', [
            'name' => 'أحمد السائق',
            'emp_code' => $empCode
        ]);
    }

    #[Test]
    public function an_admin_can_update_a_driver(): void
    {
        // Arrange
        Sanctum::actingAs($this->adminUser);
        $driver = Driver::factory()->create(['name' => 'سائق قديم']);

        $updateData = [
            'name' => 'سائق محدث',
            'emp_code' => $driver->emp_code, // نحافظ على الكود القديم
            'phone' => '0599999999'
        ];

        // Act
        $response = $this->putJson("/api/drivers/{$driver->id}", $updateData);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'name' => 'سائق محدث',
            'phone' => '0599999999'
        ]);
    }

    #[Test]
    public function an_accountant_cannot_delete_a_driver(): void
    {
        // Arrange: المحاسب يملك العرض والتعديل ربما، لكن ليس الحذف
        Sanctum::actingAs($this->accountantUser);
        $driver = Driver::factory()->create();

        // Act
        $response = $this->deleteJson("/api/drivers/{$driver->id}");

        // Assert
        $response->assertForbidden(); // 403
        $this->assertDatabaseHas('drivers', ['id' => $driver->id]);
    }

    #[Test]
    public function a_super_admin_can_delete_a_driver(): void
    {
        // Arrange
        $driver = Driver::factory()->create();

        // Act
        $response = $this->deleteJson("/api/drivers/{$driver->id}");

        // Assert
        $response->assertOk();
        $this->assertSoftDeleted('drivers', ['id' => $driver->id]);
    }
}
