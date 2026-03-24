<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المورد
            $table->string('phone')->nullable(); // رقم التواصل
            $table->decimal('current_balance', 15, 2)->default(0); // رصيد المورد الحالي (دائن أو مدين)
            $table->timestamps(); // تاريخي الإنشاء والتحديث
            $table->softDeletes(); // الحذف المرن
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
