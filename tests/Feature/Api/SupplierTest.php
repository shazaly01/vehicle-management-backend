<?php

namespace Tests\Feature\Api;

use App\Models\Supplier;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class SupplierTest extends ApiTestCase
{
    #[Test]
    public function a_super_admin_can_view_a_list_of_suppliers(): void
    {
        // تهيئة 5 موردين وهميين
        Supplier::factory()->count(5)->create();

        // تنفيذ الطلب (نفترض أن Super Admin مسجل دخوله افتراضياً في ApiTestCase)
        $response = $this->getJson('/api/suppliers');

        // التحقق من الاستجابة
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }

    #[Test]
    public function a_supervisor_cannot_create_a_supplier(): void
    {
        // Arrange: تسجيل الدخول كمشرف (يمتلك صلاحية العرض فقط للموردين، وليس الإنشاء)
        // ملاحظة: تأكد من تعريف $this->supervisorUser في ApiTestCase
        Sanctum::actingAs($this->supervisorUser);
        $supplierData = Supplier::factory()->make()->toArray();

        // Act
        $response = $this->postJson('/api/suppliers', $supplierData);

        // Assert
        $response->assertForbidden(); // 403 Forbidden
    }

    #[Test]
    public function an_accountant_can_create_a_new_supplier(): void
    {
        // Arrange: تسجيل الدخول كمحاسب (يملك صلاحية إنشاء مورد)
        Sanctum::actingAs($this->accountantUser);
        $supplierData = Supplier::factory()->make()->toArray();

        // Act
        $response = $this->postJson('/api/suppliers', $supplierData);

        // Assert
        $response->assertCreated(); // 201 Created
        $this->assertDatabaseHas('suppliers', [
            'name' => $supplierData['name'],
            'phone' => $supplierData['phone']
        ]);
    }

    #[Test]
    public function an_admin_can_update_a_supplier(): void
    {
        // Arrange: تسجيل الدخول كمدير نظام
        Sanctum::actingAs($this->adminUser);
        $supplier = Supplier::factory()->create();
        $updateData = ['name' => 'شركة التوريدات المحدثة'];

        // Act
        $response = $this->putJson("/api/suppliers/{$supplier->id}", $updateData);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'شركة التوريدات المحدثة'
        ]);
    }

    #[Test]
    public function a_super_admin_can_delete_a_supplier(): void
    {
        // Arrange
        $supplier = Supplier::factory()->create();

        // Act
        $response = $this->deleteJson("/api/suppliers/{$supplier->id}");

        // Assert
        // لأننا نرجع response()->json(['message' => '...'], 200) في الـ Controller بدلاً من 204
        $response->assertOk();
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    #[Test]
    public function an_accountant_cannot_delete_a_supplier(): void
    {
        // Arrange: المحاسب يملك العرض والإنشاء والتعديل، لكن لا يملك صلاحية الحذف
        Sanctum::actingAs($this->accountantUser);
        $supplier = Supplier::factory()->create();

        // Act
        $response = $this->deleteJson("/api/suppliers/{$supplier->id}");

        // Assert
        $response->assertForbidden();
        // التأكد من أن السجل لم يتم حذفه
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
