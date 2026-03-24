<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('machineries', function (Blueprint $table) {
            // إضافة حقل النوع بعد حقل رقم اللوحة
            $table->string('type')->nullable()->after('plate_number_or_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machineries', function (Blueprint $table) {
            // حذف الحقل في حال التراجع عن الهجرة
            $table->dropColumn('type');
        });
    }
};
