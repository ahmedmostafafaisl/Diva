<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Models\Notification;

class NotificationService
{
    public function sendNotification($title, $body, $recipientToken, $notifiable_id, $appointmentId)
    {
        // 1. Create and store notification in the database
        $notification = Notification::create([
            'title' => $title,
            'body' => $body,
            'notifiable_id' => $notifiable_id,
        ]);



        // 2. Send notification via Firebase Cloud Messaging
        // try {
        $firebaseNotification = FirebaseNotification::create($title, $body);
        // $message = CloudMessage::withTarget('token', $recipientToken)
        //     ->withNotification($firebaseNotification);

        $message = CloudMessage::withTarget('token', $recipientToken)
            ->withNotification($firebaseNotification)
            ->withData([
                'appointment_id' => $appointmentId, // Include your appointment_id here
            ]);

        Firebase::messaging()->send($message);



        // 3. Update the notification status in the database
        $notification->update(['is_sent' => true]);

        return true;
        // } catch (\Exception $e) {
        //     // Handle error and possibly log it
        //     \Log::error('Notification failed: ' . $e->getMessage());
        //     return false;
        // }
    }
}
