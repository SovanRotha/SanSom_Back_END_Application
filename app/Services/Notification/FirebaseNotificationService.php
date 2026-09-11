<?php

namespace App\Services\Notification;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;

class FirebaseNotificationService
{
    public function send(
        string $fcmToken,
        string $title,
        string $message,
        ?array $data = null
    ): array {
        $credentialsPath = base_path(
            config('services.firebase.credentials')
        );

        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            $credentialsPath
        );

        $token = $credentials->fetchAuthToken();

        if (!isset($token['access_token'])) {
            throw new \Exception(
                'Could not get Firebase access token.'
            );
        }

        $projectId = config('services.firebase.project_id');

        $response = Http::withToken($token['access_token'])
            ->post(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                [
                    'message' => [
                        'token' => $fcmToken,

                        'notification' => [
                            'title' => $title,
                            'body' => $message,
                        ],

                        'data' => collect($data ?? [])
                            ->map(fn ($value) => (string) $value)
                            ->toArray(),
                    ],
                ]
            );

        if ($response->failed()) {
            throw new \Exception(
                'FCM Error: ' . $response->body()
            );
        }

        return $response->json();
    }
}