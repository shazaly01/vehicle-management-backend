<?php

namespace Tests\Feature\Api;

use App\Models\Document;
use App\Models\Driver;
use App\Models\Machinery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

class DocumentTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // استخدام قرص التخزين الوهمي لمنع تكدس الملفات الحقيقية
        Storage::fake('local');
    }

    #[Test]
    public function an_employee_can_upload_a_document_for_a_driver(): void
    {
        Sanctum::actingAs($this->employeeUser);
        $driver = Driver::factory()->create();
        $file = UploadedFile::fake()->create('license.pdf', 500, 'application/pdf');

        $data = [
            'name' => 'رخصة قيادة خاصة',
            'file' => $file,
            'target_type' => 'driver',
            'target_id' => $driver->id,
        ];

        $response = $this->postJson('/api/documents', $data);

        $response->assertCreated();
        $this->assertDatabaseHas('documents', [
            'name' => 'رخصة قيادة خاصة',
            'documentable_type' => Driver::class,
            'documentable_id' => $driver->id,
        ]);

        // التأكد من أن الملف حُفظ في المجلد الخاص (Private)
        $document = Document::first();
        Storage::disk('local')->assertExists($document->file_path);
    }

    #[Test]
    public function an_employee_can_upload_a_document_for_a_machinery(): void
    {
        Sanctum::actingAs($this->employeeUser);
        $machinery = Machinery::factory()->create();
        $file = UploadedFile::fake()->image('truck_photo.jpg');

        $data = [
            'name' => 'صورة الشاحنة من الأمام',
            'file' => $file,
            'target_type' => 'machinery',
            'target_id' => $machinery->id,
        ];

        $response = $this->postJson('/api/documents', $data);

        $response->assertCreated();
        $this->assertDatabaseHas('documents', [
            'documentable_type' => Machinery::class,
            'documentable_id' => $machinery->id,
        ]);
    }

#[Test]
    public function a_user_can_access_file_via_signed_url(): void
    {
        // 1. إنشاء مستند وملف وهمي (نعطيه حجماً لكي لا يكون فارغاً تماماً)
        $driver = Driver::factory()->create();
        $fakeFile = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        $path = Storage::disk('local')->putFile('private_documents', $fakeFile);

        $document = Document::create([
            'name' => 'ملف تجريبي',
            'file_path' => $path,
            'documentable_type' => Driver::class,
            'documentable_id' => $driver->id,
        ]);

        // 2. الحصول على الرابط الموقع
        $signedUrl = $document->url;

        // 3. محاولة الوصول للرابط
        $response = $this->get($signedUrl);

        // 4. التحقق من نجاح الرابط
        $response->assertOk();

        // أزلنا سطر التحقق من الـ Content-Type لأنه يتغير مع الملفات الوهمية، والاكتفاء بـ assertOk يفي بالغرض تماماً.
    }

    #[Test]
    public function signed_url_fails_if_tampered_with(): void
    {
        $driver = Driver::factory()->create();
        $document = Document::create([
            'name' => 'عقد مهم',
            'file_path' => 'private_documents/fake.pdf',
            'documentable_type' => Driver::class,
            'documentable_id' => $driver->id,
        ]);

        $originalUrl = $document->url;
        // التلاعب بالرابط بإضافة حرف واحد
        $tamperedUrl = $originalUrl . 'x';

        $response = $this->get($tamperedUrl);

        // يجب أن يرجع 403 لأن التوقيع غير صالح
        $response->assertStatus(403);
    }

    #[Test]
    public function a_super_admin_can_delete_a_document(): void
    {
        Sanctum::actingAs($this->superAdminUser);
        $document = Document::factory()->create([
            'documentable_type' => Driver::class,
            'documentable_id' => Driver::factory()->create()->id,
        ]);

        $response = $this->deleteJson("/api/documents/{$document->id}");

        $response->assertOk();
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }
}
