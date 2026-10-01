<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\FirebaseNotificationService;

class NotifyUpcomingAppointments extends Command
{
    protected $signature = 'notify:appointments';
    protected $description = 'Send notifications to users who have appointments in the next 30 minutes';
    protected $firebaseNotificationService;

    public function __construct(FirebaseNotificationService $firebaseNotificationService)
    {
        parent::__construct();
        $this->firebaseNotificationService = $firebaseNotificationService;
    }

    public function handle()
    {
        $timeZone = new CarbonTimeZone('Asia/Riyadh');
        $currentDateTime = Carbon::now($timeZone); // Get the current timestamp
        $next30Minutes = Carbon::now($timeZone)->addMinutes(30);

        // Get clients with their upcoming appointments
        $appointments = Appointment::whereRaw(
            "STR_TO_DATE(CONCAT(appointment_date, ' ', start_time), '%Y-%m-%d %H:%i:%s') BETWEEN ? AND ?",
            [$currentDateTime, $next30Minutes]
        )->with('client')->get();

        foreach ($appointments as $appointment) {
            $user = $appointment->user;

            if ($user && $user->fcm_token) {
                $title = ' موعد قادم ';
                $body = '      الموعد القادم الخاص بك  في تمام الساعة :' . $appointment->start_time;
                $response = $this->firebaseNotificationService->sendNotification($user->fcm_token, $title, $body, $appointment->id);

                Log::info("Firebase notification sent to: {$user->username}, Appointment ID: {$appointment->id}");
            }
        }

        $this->info('Firebase notifications sent successfully.');
    }
}
