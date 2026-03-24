<?php

namespace Tests\Feature\Api;

use App\Models\Project;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class ProjectTest extends ApiTestCase
{
    #[Test]
    public function a_super_admin_can_view_all_projects(): void
    {
        // Arrange
        Project::factory()->count(3)->create();

        // Act: السوبر آدمن مسجل دخول افتراضياً
        $response = $this->getJson('/api/projects');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    #[Test]
    public function a_supervisor_cannot_create_a_project(): void
    {
        // Arrange: المشرف (Supervisor) يملك صلاحيات المراقبة والعرض فقط عادةً
        Sanctum::actingAs($this->supervisorUser);

        $projectData = [
            'name' => 'مشروع بناء جديد',
            'location' => 'الرياض',
            'status' => 'active',
        ];

        // Act
        $response = $this->postJson('/api/projects', $projectData);

        // Assert
        $response->assertForbidden(); // 403 Forbidden
    }

    #[Test]
    public function an_employee_can_create_a_project(): void
    {
        // Arrange: الموظف (Employee) يمتلك صلاحية إدخال البيانات والإنشاء
        Sanctum::actingAs($this->employeeUser);

        $projectData = [
            'name' => 'مشروع البنية التحتية',
            'location' => 'جدة',
            'status' => 'active',
        ];

        // Act
        $response = $this->postJson('/api/projects', $projectData);

        // Assert
        $response->assertCreated(); // 201 Created

        $this->assertDatabaseHas('projects', [
            'name' => 'مشروع البنية التحتية',
            'status' => 'active'
        ]);
    }

    #[Test]
    public function an_admin_can_update_project_status(): void
    {
        // Arrange: إعداد مشروع بحالة "نشط"
        Sanctum::actingAs($this->adminUser);
        $project = Project::factory()->create([
            'name' => 'مشروع قيد التنفيذ',
            'status' => 'active'
        ]);

        // تحديث حالة المشروع إلى "مكتمل"
        $updateData = [
            'name' => $project->name,
            'location' => $project->location,
            'status' => 'completed',
        ];

        // Act
        $response = $this->putJson("/api/projects/{$project->id}", $updateData);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'completed'
        ]);
    }

    #[Test]
    public function an_accountant_cannot_delete_a_project(): void
    {
        // Arrange
        Sanctum::actingAs($this->accountantUser);
        $project = Project::factory()->create();

        // Act
        $response = $this->deleteJson("/api/projects/{$project->id}");

        // Assert
        $response->assertForbidden(); // 403 Forbidden
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    #[Test]
    public function a_super_admin_can_delete_a_project(): void
    {
        // Arrange
        $project = Project::factory()->create();

        // Act
        $response = $this->deleteJson("/api/projects/{$project->id}");

        // Assert
        $response->assertOk();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
