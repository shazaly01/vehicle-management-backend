<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\Api\ProjectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    /**
     * عرض قائمة المشاريع (مع دعم البحث بالاسم والفلترة بالحالة)
     */
    public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $query = Project::query();

        // 1. البحث باسم المشروع
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // 2. الفلترة بحالة المشروع (active, on_hold, completed)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // جلب المشاريع مرتبة من الأحدث للأقدم مع التقسيم
        $projects = $query->latest()->paginate(15);

        return ProjectResource::collection($projects);
    }

    /**
     * إضافة مشروع جديد
     */
    public function store(StoreProjectRequest $request): ProjectResource
    {
        $this->authorize('create', Project::class);

        $project = Project::create($request->validated());

        return new ProjectResource($project);
    }

    /**
     * عرض تفاصيل مشروع محدد
     */
    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        // يمكنك تحميل العلاقات إذا لزم الأمر مثل: $project->load('dispatchOrders');
        return new ProjectResource($project);
    }

    /**
     * تحديث بيانات مشروع
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return new ProjectResource($project);
    }

    /**
     * حذف مشروع (حذف مرن)
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json([
            'message' => 'تم حذف المشروع بنجاح'
        ], 200);
    }
}
