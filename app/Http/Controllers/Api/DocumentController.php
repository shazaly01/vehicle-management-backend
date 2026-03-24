<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Resources\Api\DocumentResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $query = Document::with('documentable');

        // الفلترة بناءً على المعطيات القادمة من الواجهة
        if (request()->has('target_id') && request()->has('target_type')) {
            $modelType = match(request('target_type')) {
                'owner' => \App\Models\MachineryOwner::class,
                'machinery' => \App\Models\Machinery::class,
                'project' => \App\Models\Project::class,
                'driver' => \App\Models\Driver::class,
                default => null,
            };

            if ($modelType) {
                $query->where('documentable_id', request('target_id'))
                      ->where('documentable_type', $modelType);
            }
        }

        $documents = $query->latest()->paginate(20);
        return DocumentResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        // التخزين في 'local' داخل مجلد 'private_documents' للحماية
        $path = $request->file('file')->store('private_documents', 'local');

        $modelType = match($request->target_type) {
            'owner' => \App\Models\MachineryOwner::class,
            'machinery' => \App\Models\Machinery::class,
            'project' => \App\Models\Project::class,
            'driver' => \App\Models\Driver::class,
        };

        $document = Document::create([
            'name' => $request->name,
            'file_path' => $path,
            'documentable_id' => $request->target_id,
            'documentable_type' => $modelType,
        ]);

        return response()->json([
            'message' => 'تم رفع المستند بنجاح.',
            'data' => DocumentResource::make($document),
        ], Response::HTTP_CREATED);
    }

    public function download(Document $document)
    {
        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'الملف غير موجود');
        }

        $path = Storage::disk('local')->path($document->file_path);

        // إرجاع الملف للعرض المباشر (inline)
        return response()->file($path);
    }

    public function destroy(Document $document): JsonResponse
    {
        $document->delete();

        return response()->json(['message' => 'تم حذف المستند بنجاح.']);
    }
}
