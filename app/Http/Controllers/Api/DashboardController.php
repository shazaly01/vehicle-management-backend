<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

// استدعاء النماذج المطلوبة للإحصائيات
use App\Models\Machinery;
use App\Models\MachineryOwner;
use App\Models\Project;
use App\Models\Driver;
use App\Models\Treasury;
use App\Models\DispatchOrder;

class DashboardController extends Controller
{
    /**
     * عرض إحصائيات لوحة التحكم الرئيسية
     */
    public function stats(Request $request): JsonResponse
    {
        // تم التحقق من الصلاحية 'dashboard.view' مسبقاً في مسار (Route) الـ API

        $user = auth()->user();

        // ==========================================
        // 1. لوحة تحكم خاصة بـ "صاحب الآلية"
        // ==========================================
        if ($user->hasRole('Machinery Owner')) {
            $owner = MachineryOwner::where('user_id', $user->id)->first();

            if (!$owner) {
                return response()->json(['message' => 'لم يتم ربط حسابك بملف مالك آلية بعد.'], 404);
            }

            // إحصائيات الآليات الخاصة به
            $myMachineries = Machinery::where('owner_id', $owner->id)->get();

            // آخر أوامر التشغيل لآلياته
            $recentOrders = DispatchOrder::with(['project:id,name', 'machinery:id,plate_number_or_name'])
                ->whereHas('machinery', function ($q) use ($owner) {
                    $q->where('owner_id', $owner->id);
                })
                ->latest()
                ->take(5)
                ->get();

            return response()->json([
                'role' => 'owner',
                'stats' => [
                    'total_machineries' => $myMachineries->count(),
                    'available_machineries' => $myMachineries->where('status', 'available')->count(),
                    'busy_machineries' => $myMachineries->where('status', 'busy')->count(),
                    'maintenance_machineries' => $myMachineries->where('status', 'maintenance')->count(),
                ],
                'recent_dispatch_orders' => $recentOrders,
            ]);
        }

        // ==========================================
        // 2. لوحة تحكم الإدارة (المدير، المشرف، المحاسب، إلخ)
        // ==========================================

        // إحصائيات عامة
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'active')->count();
        $totalDrivers = Driver::count();

        // إحصائيات الآليات
        $machineriesStats = [
            'total' => Machinery::count(),
            'available' => Machinery::where('status', 'available')->count(),
            'busy' => Machinery::where('status', 'busy')->count(),
            'maintenance' => Machinery::where('status', 'maintenance')->count(),
        ];

        // إجمالي أرصدة الخزائن (بناءً على صلاحية رؤية الخزائن)
        $treasuriesTotalBalance = 0;
        if ($user->can('treasury.view')) {
            $treasuriesTotalBalance = Treasury::sum('balance');
        }

        // آخر 5 أذونات خروج (مهام تشغيلية)
        $recentDispatchOrders = [];
        if ($user->can('dispatch_order.view')) {
            $recentDispatchOrders = DispatchOrder::with([
                    'machinery:id,plate_number_or_name',
                    'driver:id,name',
                    'project:id,name'
                ])
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_no' => (string) $order->order_no,
                        'machinery' => $order->machinery->plate_number_or_name ?? 'غير محدد',
                        'driver' => $order->driver->name ?? 'غير محدد',
                        'project' => $order->project->name ?? 'غير محدد',
                        'status' => $order->status,
                        'created_at' => $order->created_at->format('Y-m-d H:i'),
                    ];
                });
        }

        // إرسال البيانات المجمعة
        return response()->json([
            'role' => 'management',
            'stats' => [
                'projects' => [
                    'total' => $totalProjects,
                    'active' => $activeProjects,
                ],
                'machineries' => $machineriesStats,
                'total_drivers' => $totalDrivers,
                'treasuries_total_balance' => (float) $treasuriesTotalBalance,
            ],
            'recent_dispatch_orders' => $recentDispatchOrders,
        ]);
    }
}
