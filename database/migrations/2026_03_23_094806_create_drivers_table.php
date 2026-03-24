<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم السائق
            // تم تحديد النوع كـ DECIMAL(18, 0) ليتسع لأكواد الموظفين ضمن شجرة الحسابات (9 أرقام فأكثر) بدون كسور
            $table->decimal('emp_code', 18, 0)->unique();
            $table->string('phone')->nullable(); // رقم هاتف السائق
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
