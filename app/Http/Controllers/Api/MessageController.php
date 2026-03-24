<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Message\StoreMessageRequest;
use App\Http\Resources\Api\MessageResource;
use App\Models\Message;
use App\Models\MachineryOwner;
use App\Models\Supplier;
use App\Models\Driver;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;

        // التعديل هنا: استبدال 'permission' بـ 'can' لتتوافق مع نظام Laravel الأساسي وباقي مساراتك
        $this->middleware('can:message.view')->only(['index', 'show']);
        $this->middleware('can:message.create')->only('store');
        $this->middleware('can:message.delete')->only('destroy');
    }

    /**
     * عرض سجل الرسائل الصادرة (مع البحث والفلترة)
     */
    public function index(Request $request)
    {
        $messages = Message::with(['sender', 'messageable'])
            ->when($request->search, function ($query, $search) {
                $query->where('content', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($request->limit ?? 15);

        return MessageResource::collection($messages);
    }

    /**
     * إرسال رسالة جديدة (يدوياً من لوحة التحكم)
     */
    public function store(StoreMessageRequest $request)
    {
        $validated = $request->validated();

        // 1. تحديد الموديل بناءً على النوع القادم من الفرونت-إند
        $recipientModel = $this->getRecipientModel($validated['recipient_type']);

        if (!$recipientModel) {
            return response()->json(['message' => 'نوع المستلم غير صالح'], 422);
        }

        // 2. البحث عن المستلم
        $recipient = $recipientModel::find($validated['recipient_id']);

        if (!$recipient) {
            return response()->json(['message' => 'المستلم المختار غير موجود'], 404);
        }

        // 3. استخدام الخدمة لإرسال الرسالة
        $message = $this->smsService->sendMessage(
            $recipient,
            $validated['content'],
            auth()->id()
        );

        if (!$message) {
            return response()->json(['message' => 'فشل في جدولة الرسالة، تأكد من وجود رقم هاتف'], 422);
        }

        return response()->json([
            'message' => 'تمت جدولة الرسالة للإرسال بنجاح',
            'data'    => new MessageResource($message)
        ]);
    }

    /**
     * عرض تفاصيل رسالة محددة (لمعرفة سجل الأخطاء مثلاً)
     */
    public function show(Message $message)
    {
        return new MessageResource($message->load(['sender', 'messageable']));
    }

    /**
     * حذف سجل رسالة
     */
    public function destroy(Message $message)
    {
        $message->delete();
        return response()->json(['message' => 'تم حذف سجل الرسالة بنجاح']);
    }

    /**
     * إعادة إرسال رسالة فشلت (إجراء إضافي مفيد)
     */
    public function resend(Message $message)
    {
        if ($message->status !== 'failed') {
            return response()->json(['message' => 'يمكن إعادة إرسال الرسائل الفاشلة فقط'], 422);
        }

        // تحديث الحالة لانتظار الإرسال مرة أخرى
        $message->update(['status' => 'pending', 'error_log' => null]);

        // إعادة استدعاء المهمة في الطابور
        \App\Jobs\SendSmsJob::dispatch($message);

        return response()->json(['message' => 'تمت إعادة جدولة الرسالة للإرسال']);
    }

    /**
     * دالة مساعدة لتحويل النوع لنص الموديل الكامل
     */
    private function getRecipientModel($type)
    {
        return match ($type) {
            'owner'    => MachineryOwner::class,
            'supplier' => Supplier::class,
            'driver'   => Driver::class,
            default    => null,
        };
    }
}
