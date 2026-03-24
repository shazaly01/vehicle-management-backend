<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();

            // تطبيق DECIMAL(18, 0) لرقم الحركة المالية/سند القيد
            $table->decimal('transaction_no', 18, 0)->unique();

            // الخزينة المرتبطة بالحركة (اختيارية في حال كانت تسوية بدون حركة نقدية)
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries')->nullOnDelete();

            // نوع الحركة (قبض، صرف، تسوية، تحويل)
            $table->string('transaction_type');

            // Polymorphic Relation للربط مع أي كيان (الموردين، أصحاب الآليات، المشاريع، السائقين)
            // سينشئ حقلين: related_entity_type (نوع الموديل) و related_entity_id (رقم الـ ID)
            $table->string('related_entity_type')->nullable();
            $table->unsignedBigInteger('related_entity_id')->nullable();

            $table->index(
                ['related_entity_type', 'related_entity_id'],
                'ft_related_entity_idx'
            );

            // القيمة المالية
            $table->decimal('amount', 15, 2);

            // تفاصيل الحركة
            $table->text('description')->nullable(); // بيان سبب الحركة

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
