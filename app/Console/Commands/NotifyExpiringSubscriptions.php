<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use App\Models\Subscription;
use Illuminate\Console\Command;
use App\Services\FirebaseNotificationService;

class NotifyExpiringSubscriptions extends Command
{
    protected $signature = 'notify:expiring-subscriptions';
    protected $description = 'Send notifications to customers whose subscriptions will expire in 3 days';
    protected $firebaseNotificationService;


    public function __construct(FirebaseNotificationService $firebaseNotificationService)
    {
        parent::__construct();
        $this->firebaseNotificationService = $firebaseNotificationService;
    }

    public function handle()
    {
        $timeZone = new CarbonTimeZone('Asia/Riyadh');
        $threeDaysLater = Carbon::now($timeZone)->addDays(3)->toDateString();
        $subscriptions = Subscription::whereDate('expires_at', '=', $threeDaysLater)
            ->where('payment_status', 'paid')
            ->where('remaining_count', '>', 0)
            ->whereNotNull('customer_id')
            ->with('customer')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info("No expiring subscriptions found.");
            return;
        }

        foreach ($subscriptions as $subscription) {
            $customer = $subscription->customer;

            if ($customer && $customer->fcm_token) {
                $title = "اشعار انتهاء الاشتراك";
                $body = "عزيزي {$subscription->customer->username}، اشتراكك سينتهي خلال 3 أيام. قم بتجديده الآن للاستمرار في الاستفادة من خدماتنا.";

                $response = $this->firebaseNotificationService->sendNotification($customer->fcm_token, $title, $body);

                $this->info("تم إرسال الإشعار إلى العميل: " . $subscription->customer->username);
            }
        }
        $this->info("تم إرسال الإشعارات للعملاء الذين ستنتهي اشتراكاتهم خلال 3 أيام.");
    }
}
