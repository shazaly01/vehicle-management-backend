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
    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        $table->text('content'); // نص الرسالة
        $table->string('phone'); // الرقم الذي أُرسل إليه فعلياً
        $table->string('type')->default('individual'); // فردي أو آلي
        $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');

        // --- هنا الجزء الاحترافي ---
        // سيقوم بإنشاء عمودين: messageable_id و messageable_type
        // هذا يسمح للرسالة بالارتباط بـ (Owner أو Supplier أو Driver)
        $table->nullableMorphs('messageable');

        $table->foreignId('sender_id')->nullable()->constrained('users'); // الموظف المرسل
        $table->text('error_log')->nullable(); // لحفظ سبب الفشل إن وجد
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
