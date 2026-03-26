<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. إعادة هيكلة جدول الأوامر الرئيسية (Dispatch Orders)
        Schema::table('dispatch_orders', function (Blueprint $table) {
            // أ. إسقاط المفاتيح الأجنبية القديمة الخاصة بالتنفيذ الفردي
            $table->dropForeign(['machinery_id']);
            $table->dropForeign(['driver_id']);

            // ب. إسقاط الأعمدة التي سيتم نقلها لجدول الحركات
            $table->dropColumn([
                'machinery_id',
                'driver_id',
                'pricing_type',
                'quantity',
                'unit_price',
                'total_cost',
                'shipped_material_note',
                'shipped_material_value',
                // سنقوم بإسقاط status القديم لنعيد إنشائه بمعاني جديدة للأمر الرئيسي
                'status'
            ]);

            // ج. تعديل order_no ليكون DECIMAL(18,0) حسب قاعدتك الثابتة
            $table->decimal('order_no', 18, 0)->change();

            // د. إضافة حقول العقد الجديد
            $table->decimal('target_quantity', 10, 2)->default(0)->after('project_id')->comment('الكمية الإجمالية المستهدفة');
            $table->decimal('material_unit_price', 10, 2)->default(0)->after('target_quantity')->comment('سعر المورد للوحدة');
            $table->string('status')->default('active')->after('material_unit_price')->comment('حالة الأمر: active, completed, canceled');
        });

        // 2. إنشاء جدول الحركات/النقلات (Dispatch Order Trips)
        Schema::create('dispatch_order_trips', function (Blueprint $table) {
            $table->id();

            // الربط بالأمر الرئيسي
            $table->foreignId('dispatch_order_id')->constrained()->cascadeOnDelete();

            // الربط بالتنفيذ (الآلية والسائق)
            $table->foreignId('machinery_id')->constrained()->restrictOnDelete();
            $table->foreignId('driver_id')->constrained()->restrictOnDelete();

            // تسعير المالك (الآلية)
            $table->string('transport_cost_type')->comment('نوع حساب الآلية: trip, weight, hour, day');
            $table->decimal('quantity', 10, 2)->default(1)->comment('الكمية المنفذة في هذه الحركة');
            $table->decimal('transport_unit_price', 10, 2)->default(0)->comment('سعر وحدة الترحيل للآلية');

            // التتبع ودورة الاعتماد
            $table->string('status')->default('dispatched')->comment('dispatched, loaded, delivered, canceled');
            $table->timestamp('loaded_at')->nullable()->comment('وقت تأكيد التحميل من المورد');
            $table->timestamp('delivered_at')->nullable()->comment('وقت تأكيد الاستلام من المشروع');

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_order_trips');

        // ملاحظة: كتابة كود الـ down لإعادة الجدول القديم لحالته السابقة يتطلب إعادة إنشاء الحقول المحذوفة.
        // تم تجاوزها هنا للتركيز على التقدم للأمام، ولكن يُنصح بأخذ نسخة احتياطية من الداتا قبل الـ Migrate.
    }
};
