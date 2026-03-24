    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::create('machineries', function (Blueprint $table) {
                $table->id();
                // ربط الآلية بصاحبها (يجب أن يكون جدول machinery_owners قد تم إنشاؤه مسبقاً)
                $table->foreignId('owner_id')->constrained('machinery_owners')->cascadeOnDelete();

                $table->string('plate_number_or_name'); // رقم اللوحة أو اسم الآلية
                $table->string('type')->nullable();
                $table->string('status')->default('available'); // حالة الآلية (available, busy, maintenance)
                $table->string('cost_type')->nullable(); // نوع التكلفة الافتراضي (trip, weight, hour, day)

                $table->timestamps();
                $table->softDeletes();
            });
        }

        public function down(): void
        {
            Schema::dropIfExists('machineries');
        }
    };
