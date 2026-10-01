<?php

namespace App\Services;

use Berkayk\OneSignal\OneSignalFacade as OneSignal;

class PushNotifications
{

    public function sendNotificationToUser($message, $userNotificationId)
    {
        OneSignal::sendNotificationToUser(
            $message,
            $userNotificationId,
        );
    }
}
