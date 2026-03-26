<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. تعديل جدول السائقين: جعل كود الموظف اختياري وتغيير نوعه إلى DECIMAL(18, 0)
        Schema::table('drivers', function (Blueprint $table) {
            $table->decimal('emp_code', 18, 0)->nullable()->change();
        });

        // 2. تعديل جدول الآليات: إضافة حقل السائق
        Schema::table('machineries', function (Blueprint $table) {
            $table->foreignId('driver_id')->after('owner_id')->constrained('drivers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('machineries', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn('driver_id');
        });

        Schema::table('drivers', function (Blueprint $table) {
            // إعادة الحقل لحالته السابقة (نصي وإلزامي) في حال التراجع
            $table->string('emp_code')->nullable(false)->change();
        });
    }
};
