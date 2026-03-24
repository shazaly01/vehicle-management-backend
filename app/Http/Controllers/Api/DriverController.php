<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Http\Requests\Driver\StoreDriverRequest;
use App\Http\Requests\Driver\UpdateDriverRequest;
use App\Http\Resources\Api\DriverResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DriverController extends Controller
{
    /**
     * عرض قائمة السائقين (مع دعم البحث بالاسم أو الكود)
     */
    public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Driver::class);

        $query = Driver::query();

        // تفعيل البحث (بالاسم أو الكود الوظيفي)
        if ($request->filled('search')) {
            $searchTerm = $request->search;

            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  // البحث في الكود الوظيفي (Employee Code)
                  ->orWhere('employee_code', 'like', '%' . $searchTerm . '%');
            });
        }

        // جلب البيانات مع الترقيم
        $drivers = $query->latest()->paginate(15);

        return DriverResource::collection($drivers);
    }

    public function store(StoreDriverRequest $request): DriverResource
    {
        $this->authorize('create', Driver::class);

        $driver = Driver::create($request->validated());

        return new DriverResource($driver);
    }

    public function show(Driver $driver): DriverResource
    {
        $this->authorize('view', $driver);

        return new DriverResource($driver);
    }

    public function update(UpdateDriverRequest $request, Driver $driver): DriverResource
    {
        $this->authorize('update', $driver);

        $driver->update($request->validated());

        return new DriverResource($driver);
    }

    public function destroy(Driver $driver): JsonResponse
    {
        $this->authorize('delete', $driver);

        $driver->delete();

        return response()->json([
            'message' => 'تم حذف السائق بنجاح'
        ], 200);
    }
}
