<?php

namespace App\Services;

use App\Models\Notification;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $firebase = (new Factory)
            ->withServiceAccount(base_path('storage/firebase_credentials.json'));

        $this->messaging = $firebase->createMessaging();
    }

    public function sendNotification($deviceToken, $userId, $title, $body, $data = [])
    {
        $message = Messaging\CloudMessage::withTarget('token', $deviceToken)
            ->withNotification(Messaging\Notification::create($title, $body))
            ->withData($data);

        try {
            $this->messaging->send($message);

            // Store in database
            Notification::create([
                'user_id' => $userId,
                'title'   => $title,
                'body'    => $body,
                'read'    => 0, // Default unread status
            ]);

            return ['success' => true, 'message' => 'Notification sent and stored successfully'];
        } catch (MessagingException | FirebaseException $e) {
            return [
                'success' => false,
                'message' => 'Failed to send notification',
                'error'   => $e->getMessage(),
            ];
        }
    }
}
