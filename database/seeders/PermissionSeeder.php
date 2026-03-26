<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. تنظيف الكاش الخاص بالصلاحيات لضمان تطبيق التعديلات فوراً
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guardName = 'api';

        // ==========================================
        // 2. تعريف كافة الصلاحيات (Permissions)
        // ==========================================
        $permissions = [
            // لوحة التحكم والتقارير
            'dashboard.view',
            'report.view',           // صلاحية عامة للمديرين والمحاسبين لرؤية كل التقارير
            'report.my_statement',  // صلاحية خاصة للمالك لرؤية كشف حسابه الشخصي فقط

            // إدارة المستخدمين والصلاحيات
            'user.view', 'user.create', 'user.update', 'user.delete',
            'role.view', 'role.create', 'role.update', 'role.delete',
            'setting.view', 'setting.update',
            'backup.view', 'backup.create', 'backup.delete', 'backup.download',

            // إدارة الكيانات الأساسية
            'supplier.view', 'supplier.create', 'supplier.update', 'supplier.delete',
            'machinery_owner.view', 'machinery_owner.create', 'machinery_owner.update', 'machinery_owner.delete',
            'driver.view', 'driver.create', 'driver.update', 'driver.delete',
            'project.view', 'project.create', 'project.update', 'project.delete',
            'machinery.view', 'machinery.create', 'machinery.update', 'machinery.delete',
            'treasury.view', 'treasury.create', 'treasury.update', 'treasury.delete',

            // العمليات التشغيلية
            'dispatch_order.view', 'dispatch_order.create', 'dispatch_order.update', 'dispatch_order.delete',
            'dispatch_order_trip.update_status',
            'financial_transaction.view', 'financial_transaction.create', 'financial_transaction.update', 'financial_transaction.delete',

            // إدارة المستندات (التي تم إضافتها مؤخراً)
            'document.view', 'document.create', 'document.delete',
            'message.view', 'message.create', 'message.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]);
        }

        // ==========================================
        // 3. تعريف الأدوار وتخصيص الصلاحيات (Roles)
        // ==========================================

        // --- أ. مدير النظام (Super Admin) ---
        // ملاحظة: السوبر أدمن عادة يعطى كل الصلاحيات عبر Gate::before في الـ AuthServiceProvider
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => $guardName]);

        // --- ب. الإدمن (Admin) ---
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => $guardName]);
        $adminRole->syncPermissions(Permission::where('guard_name', $guardName)->pluck('name'));

        // --- ج. المحاسب (Accountant) ---
        $accountantRole = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => $guardName]);
        $accountantRole->syncPermissions([
            'dashboard.view',
            'report.view', // يرى كافة التقارير المالية
            'document.view',
            'supplier.view', 'supplier.create', 'supplier.update',
            'machinery_owner.view',
            'project.view',
            'dispatch_order.view',
            'treasury.view', 'treasury.create', 'treasury.update',
            'financial_transaction.view', 'financial_transaction.create', 'financial_transaction.update',
            'message.view', 'message.create',
        ]);

        // --- د. المراقب/المشرف (Supervisor) ---
        $supervisorRole = Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => $guardName]);
        $supervisorRole->syncPermissions([
            'dashboard.view',
            'machinery.view', 'machinery.create', 'machinery.update',
            'driver.view', 'driver.create', 'driver.update',
            'project.view',
            'dispatch_order.view', 'dispatch_order.create', 'dispatch_order.update',
            'supplier.view',
            'document.view', 'document.create', // لرفع رخص الآليات وعقود السائقين
            'message.view', 'message.create',
        ]);

        // --- هـ. مراجع (Auditor) ---
        $auditorRole = Role::firstOrCreate(['name' => 'Auditor', 'guard_name' => $guardName]);
        $auditorRole->syncPermissions(
            Permission::where('guard_name', $guardName)->where('name', 'like', '%.view')->pluck('name')
        );
        $auditorRole->givePermissionTo('report.view'); // يرى التقارير أيضاً

        // --- و. صاحب آلية (Machinery Owner) - [مهم جداً] ---
        $ownerRole = Role::firstOrCreate(['name' => 'Machinery Owner', 'guard_name' => $guardName]);
        $ownerRole->syncPermissions([
            'dashboard.view',
            'report.my_statement', // الصلاحية المخصصة: يرى كشف حسابه الشخصي فقط
            'machinery.view',      // يرى آلياته فقط (سيتم تصفيتها عبر الـ Policy)
            'document.view',       // يرى مستنداته فقط
            'dispatch_order.view',
            'financial_transaction.view',
        ]);



        $supplierRole = Role::firstOrCreate(['name' => 'Supplier', 'guard_name' => $guardName]);
$supplierRole->syncPermissions([
    'dashboard.view',
    'dispatch_order_trip.update_status',   // الصلاحية الأهم: لضغط زر "تأكيد التحميل"
    'document.view',                       // ليرى المستندات الخاصة به
]);
    }
}
