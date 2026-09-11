<?php

namespace App\Services\Notification;

use App\Models\Notification\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        protected FirebaseNotificationService $firebase
    ) {}

    public function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null
    ): Notification {
        // 1. Save notification to database
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        // 2. Find the user
        $user = User::find($userId);

        // 3. Send Firebase push notification
        if ($user && $user->fcm_token) {
            try {
                $this->firebase->send(
                    $user->fcm_token,
                    $title,
                    $message,
                    array_merge(
                        $data ?? [],
                        [
                            'notification_id' => (string) $notification->id,
                            'type' => $type,
                        ]
                    )
                );
            } catch (\Throwable $e) {
                Log::error('FCM notification failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notification;
    }
}