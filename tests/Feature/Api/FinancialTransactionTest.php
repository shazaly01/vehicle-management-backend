<?php

namespace Tests\Feature\Api;

use App\Models\FinancialTransaction;
use App\Models\MachineryOwner;
use App\Models\Supplier;
use App\Models\Treasury;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class FinancialTransactionTest extends ApiTestCase
{
    #[Test]
    public function a_super_admin_can_view_all_financial_transactions(): void
    {
        FinancialTransaction::factory()->count(4)->create();

        $response = $this->getJson('/api/financial_transactions');

        $response->assertOk();
        $response->assertJsonCount(4, 'data');
    }

    #[Test]
    public function a_machinery_owner_can_only_view_his_own_transactions(): void
    {
        // 1. إعداد صاحب آلية مرتبط بالمستخدم
        $myOwnerProfile = MachineryOwner::factory()->create(['user_id' => $this->machineryOwnerUser->id]);

        // 2. إنشاء معاملة مالية تخصه (سداد مستحقات)
        FinancialTransaction::factory()->create([
            'related_entity_type' => MachineryOwner::class,
            'related_entity_id' => $myOwnerProfile->id,
        ]);

        // 3. إنشاء معاملة مالية أخرى لمورد
        FinancialTransaction::factory()->create([
            'related_entity_type' => Supplier::class,
            'related_entity_id' => Supplier::factory()->create()->id,
        ]);

        // 4. تسجيل الدخول كصاحب الآلية
        Sanctum::actingAs($this->machineryOwnerUser);

        $response = $this->getJson('/api/financial_transactions');

        // 5. التحقق من أنه يرى معاملته فقط
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    #[Test]
    public function an_accountant_can_create_a_financial_transaction(): void
    {
        Sanctum::actingAs($this->accountantUser);

        $treasury = Treasury::factory()->create();
        $supplier = Supplier::factory()->create();

        // رقم معاملة من 18 خانة
        $transactionNo = '999999999988888888';

        $transactionData = [
            'transaction_no' => $transactionNo,
            'treasury_id' => $treasury->id,
            'transaction_type' => 'دفعة مقدمة',
            'related_entity_type' => Supplier::class,
            'related_entity_id' => $supplier->id,
            'amount' => 5000.00,
            'description' => 'دفعة مقدمة لتوريد أسمنت',
        ];

        $response = $this->postJson('/api/financial_transactions', $transactionData);

        $response->assertCreated(); // 201
        $this->assertDatabaseHas('financial_transactions', [
            'transaction_no' => $transactionNo,
            'amount' => 5000.00
        ]);
    }

    #[Test]
    public function an_admin_can_update_a_financial_transaction(): void
    {
        Sanctum::actingAs($this->adminUser);
        $transaction = FinancialTransaction::factory()->create(['amount' => 1000]);

        $updateData = array_merge($transaction->toArray(), [
            'amount' => 1500, // تعديل المبلغ
        ]);

        $response = $this->putJson("/api/financial_transactions/{$transaction->id}", $updateData);

        $response->assertOk();
        $this->assertDatabaseHas('financial_transactions', [
            'id' => $transaction->id,
            'amount' => 1500
        ]);
    }

    #[Test]
    public function an_employee_cannot_delete_a_financial_transaction(): void
    {
        // الموظف لا يملك صلاحية حذف الحركات المالية، السوبر آدمن أو المدير فقط قد يملكونها بناءً على الـ Seeder
        Sanctum::actingAs($this->employeeUser);
        $transaction = FinancialTransaction::factory()->create();

        $response = $this->deleteJson("/api/financial_transactions/{$transaction->id}");

        $response->assertForbidden(); // 403
    }
}
