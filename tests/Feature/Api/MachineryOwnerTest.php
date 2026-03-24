<?php

namespace Tests\Feature\Api;

use App\Models\MachineryOwner;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class MachineryOwnerTest extends ApiTestCase
{
    #[Test]
    public function a_super_admin_can_view_all_machinery_owners(): void
    {
        // Arrange
        MachineryOwner::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/machinery_owners');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    #[Test]
    public function a_machinery_owner_can_view_his_own_profile(): void
    {
        // Arrange: إنشاء ملف مالك وربطه بمستخدم "صاحب آلية" الذي جهزناه في ApiTestCase
        $owner = MachineryOwner::factory()->create([
            'user_id' => $this->machineryOwnerUser->id
        ]);

        Sanctum::actingAs($this->machineryOwnerUser);

        // Act: محاولة عرض ملفه الشخصي
        $response = $this->getJson("/api/machinery_owners/{$owner->id}");

        // Assert: يجب أن ينجح الطلب
        $response->assertOk();
        $response->assertJsonPath('data.id', $owner->id);
    }

    #[Test]
    public function a_machinery_owner_cannot_view_others_profile(): void
    {
        // Arrange: تسجيل الدخول كصاحب آلية
        Sanctum::actingAs($this->machineryOwnerUser);

        // إنشاء ملف مالك *آخر* يعود لمستخدم مختلف
        $otherUser = User::factory()->create();
        $otherOwner = MachineryOwner::factory()->create([
            'user_id' => $otherUser->id
        ]);

        // Act: محاولة الدخول لملف المالك الآخر
        $response = $this->getJson("/api/machinery_owners/{$otherOwner->id}");

        // Assert: يجب أن يتم طرده (ممنوع من الوصول)
        $response->assertForbidden(); // 403 Forbidden
    }

#[Test]
    public function an_employee_can_create_machinery_owner_with_document(): void
    {
        // Arrange
        Storage::fake('public');

        // --- التعديل هنا: استخدمنا الموظف بدلاً من المحاسب ---
        Sanctum::actingAs($this->employeeUser);

        // إنشاء ملف PDF وهمي
        $fakePdf = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        $data = [
            'name' => 'شركة النقل السريع',
            'phone' => '0123456789',
            'document' => $fakePdf,
        ];

        // Act
        $response = $this->postJson('/api/machinery_owners', $data);

        // Assert
        $response->assertCreated(); // 201

        $this->assertDatabaseHas('machinery_owners', [
            'name' => 'شركة النقل السريع'
        ]);

        $owner = MachineryOwner::where('name', 'شركة النقل السريع')->first();

        $this->assertNotNull($owner->documents_path);
        Storage::disk('public')->assertExists($owner->documents_path);
    }

    #[Test]
    public function an_admin_can_update_machinery_owner(): void
    {
        // Arrange
        Sanctum::actingAs($this->adminUser);
        $owner = MachineryOwner::factory()->create(['name' => 'الاسم القديم']);

        $updateData = ['name' => 'الاسم الجديد بعد التعديل'];

        // Act
        $response = $this->putJson("/api/machinery_owners/{$owner->id}", $updateData);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('machinery_owners', [
            'id' => $owner->id,
            'name' => 'الاسم الجديد بعد التعديل'
        ]);
    }

    #[Test]
    public function an_employee_cannot_delete_machinery_owner(): void
    {
        // Arrange: الموظف (Employee) يملك صلاحيات العرض والإنشاء فقط
        Sanctum::actingAs($this->employeeUser);
        $owner = MachineryOwner::factory()->create();

        // Act
        $response = $this->deleteJson("/api/machinery_owners/{$owner->id}");

        // Assert
        $response->assertForbidden(); // 403
        $this->assertDatabaseHas('machinery_owners', ['id' => $owner->id]);
    }
}
