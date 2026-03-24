<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- استيراد متحكمات النظام الأساسية ---
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\BackupController;

// --- استيراد متحكمات منظومة حركة الآليات الجديدة ---
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\MachineryOwnerController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TreasuryController;
use App\Http\Controllers\Api\MachineryController;
use App\Http\Controllers\Api\DispatchOrderController;
use App\Http\Controllers\Api\FinancialTransactionController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\MessageController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// --- المسارات العامة (Public Routes) ---
// لا تحتاج إلى مصادقة
Route::post('/login', [AuthController::class, 'login']);

// مسار عام لتحميل الملفات المرفوعة (إذا لزم الأمر)
// Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download')->middleware('signed');
Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
    ->name('documents.download')
    ->middleware('signed');
// --- المسارات المحمية (Protected Routes) ---
// تتطلب مصادقة باستخدام Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // --- مسارات إدارة النسخ الاحتياطي ---
    Route::prefix('backups')->name('backups.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->middleware('can:backup.view');
        Route::post('/', [BackupController::class, 'store'])->middleware('can:backup.create');
        Route::get('/download', [BackupController::class, 'download'])->middleware('can:backup.download');
        Route::delete('/', [BackupController::class, 'destroy'])->middleware('can:backup.delete');
    });

    // --- مسارات لوحة التحكم ---
    Route::get('/dashboard', [DashboardController::class, 'stats'])
         ->middleware('can:dashboard.view')
         ->name('dashboard.stats');

    // --- مسارات التقارير (تم تعديلها لتناسب النظام الجديد) ---
   Route::prefix('reports')->name('reports.')->group(function () {
    // ملخص عام (بدون ID)
    Route::get('/suppliers-summary', [ReportController::class, 'suppliersSummary']);
    Route::get('/projects', [ReportController::class, 'projectsReportByFilter']);

    // تقارير تفصيلية لكيان محدد (RESTful Pattern: resource/id/action)
    Route::get('/suppliers/{supplier}/statement', [ReportController::class, 'supplierStatement']);
    Route::get('/machinery-owners/{machineryOwner}/statement', [ReportController::class, 'machineryOwnerStatement']);
});

    // --- مسارات المصادقة والمستخدم الحالي ---
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        // جلب بيانات المستخدم مع أدواره، وصلاحياته، وملف صاحب الآلية (إن وجد)
        $user = $request->user()->load([
            'roles:id,name',
            'roles.permissions:id,name',
            'machineryOwner' // لجلب بيانات شركته/آلياته إذا كان صاحب آلية
        ]);
        return response()->json($user);
    });

    // --- مسارات إدارة الأدوار والصلاحيات والمستخدمين ---
    Route::get('roles/permissions', [RoleController::class, 'getAllPermissions'])->name('roles.permissions');
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);

    // ==========================================================
    // --- مسارات الكيانات الأساسية لمنظومة حركة الآليات ---
    // (التحقق من الصلاحيات يتم داخل الـ Controllers عبر الـ Policies)
    // ==========================================================

    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('machinery_owners', MachineryOwnerController::class);
    Route::apiResource('drivers', DriverController::class);
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('treasuries', TreasuryController::class);
    Route::apiResource('machineries', MachineryController::class);
    Route::apiResource('dispatch_orders', DispatchOrderController::class);
    Route::apiResource('financial_transactions', FinancialTransactionController::class);
    Route::apiResource('documents', DocumentController::class)->except(['update', 'show']);

    Route::post('messages/{message}/resend', [MessageController::class, 'resend'])->name('messages.resend');
    Route::apiResource('messages', MessageController::class);

});
