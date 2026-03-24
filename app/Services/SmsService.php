<?php

namespace App\Services;

use App\Models\Message;
use App\Jobs\SendSmsJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * 1. إرسال رسالة فردية لأي كيان (مالك، مورد، سائق)
     * يتم استدعاء هذه الدالة من الكنترولر أو الأوبزرفر
     */
    public function sendMessage(Model $recipient, string $content, $senderId = null)
    {
        // استخراج رقم الهاتف (دعم مسميات phone أو mobile)
        $phone = $recipient->phone ?? $recipient->mobile ?? null;

        if (empty($phone)) {
            Log::warning("محاولة إرسال SMS لكيان بدون رقم هاتف: " . get_class($recipient) . " ID: " . $recipient->id);
            return false;
        }

        // إنشاء سجل الرسالة في قاعدة البيانات (Polymorphic)
        $message = Message::create([
            'content'          => $content,
            'phone'            => $phone,
            'type'             => 'individual',
            'status'           => 'pending',
            'sender_id'        => $senderId,
            'messageable_id'   => $recipient->id,
            'messageable_type' => get_class($recipient),
        ]);

        // إرسال المهمة للطابور (Queue) للتنفيذ في الخلفية
        SendSmsJob::dispatch($message);

        return $message;
    }

    /**
     * 2. إرسال جماعي لمجموعة من الكيانات
     */
    public function sendBulk($recipients, string $content, $senderId = null)
    {
        foreach ($recipients as $recipient) {
            $this->sendMessage($recipient, $content, $senderId);
        }
        return count($recipients);
    }

    /**
     * 3. محرك الإرسال الفعلي (الربط مع Rasael API)
     * يتم استدعاؤه بواسطة الـ Job
     */
    public function dispatchToProvider(Message $message)
    {
        try {
            $token = $this->getRasaelToken();
            $formattedPhone = $this->formatLibyanPhoneNumber($message->phone);

            $payload = [
                'phoneNumber' => $formattedPhone,
                'message'     => $message->content,
                'senderID'    => config('services.rasael.sender_id'),
            ];

            // إرسال الطلب للبوابة
            $response = Http::withToken($token)
                ->acceptJson()
                ->post('https://rasael.almasafa.ly/api/sms/Send', $payload);

            // التحقق من استجابة السيرفر (نبحث عن نجاح الطلب وعدم وجود كلمة error)
            if ($response->successful() && !str_contains(strtolower($response->body()), 'error')) {
                $message->update([
                    'status' => 'sent',
                    'error_log' => null
                ]);
                return true;
            }

            throw new \Exception("Rasael API Error (Status {$response->status()}): " . $response->body());

        } catch (\Exception $e) {
            $message->update([
                'status'    => 'failed',
                'error_log' => $e->getMessage()
            ]);
            Log::error("SMS Dispatch Failed (Message ID: {$message->id}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * 4. جلب توكن تسجيل الدخول مع التخزين المؤقت (Cache)
     */
    private function getRasaelToken()
    {
        return Cache::remember('rasael_auth_token', 7200, function () {
            $response = Http::acceptJson()->post('https://rasael.almasafa.ly/api/MasafaRasaelLogin', [
                'username' => config('services.rasael.username'),
                'password' => config('services.rasael.password'),
            ]);

            if ($response->successful()) {
                $body = $response->body();
                $json = json_decode($body, true);

                // استخراج التوكن سواء كان JSON أو نصاً مباشراً
                $token = (is_array($json) && isset($json['token'])) ? $json['token'] : trim($body, " \"");

                if (empty($token)) {
                    throw new \Exception('API Login successful but token is empty');
                }

                return $token;
            }

            throw new \Exception('Rasael Login Failed: ' . $response->body());
        });
    }

    /**
     * 5. تنسيق الأرقام الليبية (إضافة مفتاح الدولة 218)
     */
    private function formatLibyanPhoneNumber($phone)
    {
        // تنظيف الرقم من أي رموز وأخذ الأرقام فقط، ثم حذف الصفر من اليسار
        $cleanPhone = ltrim(preg_replace('/[^0-9]/', '', $phone), '0');

        // إذا كان يبدأ بـ 9 (مثل 91, 92, 94) نضيف 218
        if (str_starts_with($cleanPhone, '9')) {
            return '218' . $cleanPhone;
        }

        return $cleanPhone;
    }
}
