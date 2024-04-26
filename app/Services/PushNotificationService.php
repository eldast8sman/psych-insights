<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class PushNotificationService
{
    protected $firebase;

    public function __construct()
    {
        $this->firebase = (new Factory)
            ->withServiceAccount(Storage::disk('public')->get('/fcm/psychinsightsapp.json'))->createMessaging();
    }

    public function sendPushNotification($deviceToken, $title, $body, $data=[])
    {
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification(Notification::create($title, $body))
            ->withData($data)->withHighestPossiblePriority();

        $this->firebase->send($message);
    }
}
