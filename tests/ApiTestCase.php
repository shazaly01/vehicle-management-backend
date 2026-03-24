<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class ApiTestCase extends BaseTestCase
{
    // استخدام هذه الـ Trait يضمن مسح قاعدة البيانات بعد كل اختبار وإعادتها لحالتها الأصلية
    use RefreshDatabase;

    // تعريف خصائص المستخدمين الذين سنستخدمهم في الاختبارات
    protected User $superAdminUser;
    protected User $adminUser;
    protected User $accountantUser;
    protected User $supervisorUser;
    protected User $employeeUser;
    protected User $machineryOwnerUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. تشغيل ملف بذر الصلاحيات أولاً (هام جداً لتعريف الأدوار والصلاحيات في الـ Database الخاصة بالاختبار)
        $this->seed(PermissionSeeder::class);

        // 2. إنشاء مستخدم "Super Admin" وإعطائه الدور
        $this->superAdminUser = User::factory()->create();
        $this->superAdminUser->assignRole('Super Admin');

        // 3. إنشاء مستخدم "Admin" (مدير نظام)
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');

        // 4. إنشاء مستخدم "Accountant" (محاسب)
        $this->accountantUser = User::factory()->create();
        $this->accountantUser->assignRole('Accountant');

        // 5. إنشاء مستخدم "Supervisor" (مشرف حركة)
        $this->supervisorUser = User::factory()->create();
        $this->supervisorUser->assignRole('Supervisor');

        // 6. إنشاء مستخدم "Employee" (موظف إدخال بيانات)
        $this->employeeUser = User::factory()->create();
        $this->employeeUser->assignRole('Employee');

        // 7. إنشاء مستخدم "Machinery Owner" (صاحب آلية)
        $this->machineryOwnerUser = User::factory()->create();
        $this->machineryOwnerUser->assignRole('Machinery Owner');

        // 8. جعل الـ Super Admin هو المستخدم الافتراضي المسجل دخوله في كل الاختبارات
        // (يمكنك تغييره داخل الاختبار نفسه باستخدام Sanctum::actingAs إذا احتجت لاختبار دور آخر)
        Sanctum::actingAs($this->superAdminUser);
    }
}
