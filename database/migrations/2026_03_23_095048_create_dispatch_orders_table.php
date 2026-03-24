<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_orders', function (Blueprint $table) {
            $table->id();

            // تطبيق DECIMAL(18, 0) للأرقام المرجعية بناءً على تعليماتك
            $table->decimal('order_no', 18, 0)->unique();

            // العلاقات مع الجداول الأخرى
            $table->foreignId('machinery_id')->constrained('machineries');
            $table->foreignId('driver_id')->constrained('drivers');

            // المورد والمشروع اختياريان (قد تكون الرحلة حرة أو بدون مورد)
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();

            // تفاصيل التشغيل
            $table->string('operation_type'); // نوع العملية (تحميل، تفريغ، إلخ)
            $table->string('pricing_type'); // طريقة الحساب (وزن، رحلة، ساعة، يومية)
            $table->decimal('quantity', 10, 2); // الكمية (مثلاً: 5 ساعات، 20 طن، 3 رحلات)

            // القيم المالية (هذه أسعار وقيم لذلك نستخدم 15,2)
            $table->decimal('unit_price', 15, 2); // سعر الوحدة
            $table->decimal('total_cost', 15, 2); // إجمالي تكلفة التشغيل للآلية

            // تفاصيل المادة المشحونة (خاصة بالمورد)
            $table->string('shipped_material_note')->nullable(); // ملحوظة بنوع المادة
            $table->decimal('shipped_material_value', 15, 2)->nullable()->default(0); // قيمة المادة المشحونة للمورد

            $table->string('status')->default('pending'); // حالة الأمر (قيد التنفيذ، مكتمل، ملغي)

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_orders');
    }
};
