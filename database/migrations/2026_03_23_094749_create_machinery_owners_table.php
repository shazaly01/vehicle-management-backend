<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machinery_owners', function (Blueprint $table) {
            $table->id();
            // ربط المالك بجدول المستخدمين (لكي يتمكن من تسجيل الدخول لرؤية حساباته)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name'); // اسم المالك أو الشركة
            $table->string('phone')->nullable(); // رقم التواصل
            $table->string('documents_path')->nullable(); // مسار حفظ المستندات المرفوعة (صور، هويات، سجلات)
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machinery_owners');
    }
};
