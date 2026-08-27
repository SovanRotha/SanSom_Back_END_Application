<?php

namespace App\Services\Notification;


use App\Models\Notification\Notification ;

class NotificationService
{
    public function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    //Budget Warning



    //Budget Alert



    //Budget Exceeded



    //Saving Reminders



    //Goal Completed



    // Goal Alert



    //Bill Reminder


}
