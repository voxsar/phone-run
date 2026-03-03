<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends Firebase Cloud Messaging (FCM) v1 API notifications to the admin.
 *
 * Setup:
 *  1. Create a Firebase project at https://console.firebase.google.com/
 *  2. Go to Project Settings → Service Accounts → Generate new private key
 *  3. Save the downloaded JSON file to storage/app/firebase-credentials.json
 *  4. Set FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json in .env
 *  5. Set FIREBASE_ADMIN_EMAIL=your-admin@email.com in .env
 *     (the admin's FCM device token must be stored in firebase_admin_token in .env)
 *
 * For admin browser/device push notifications you also need:
 *  - FIREBASE_PROJECT_ID= (from the service account JSON or Firebase console)
 *  - FIREBASE_ADMIN_TOKEN= FCM registration token of the admin device/browser
 */
class FirebaseNotificationService
{
    /**
     * Send a push notification to the admin FCM token.
     *
     * @param string $title   Notification title
     * @param string $body    Notification body
     * @param array  $data    Optional key-value data payload
     */
    public static function notifyAdmin(string $title, string $body, array $data = []): void
    {
        $adminToken = config('services.firebase.admin_token');
        $projectId  = config('services.firebase.project_id');

        if (empty($adminToken) || empty($projectId)) {
            return; // Firebase not configured — silently skip
        }

        try {
            $accessToken = self::getAccessToken();
            if ($accessToken === null) {
                return;
            }

            Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token'        => $adminToken,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data'         => array_map('strval', $data),
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::error('FirebaseNotificationService: ' . $e->getMessage());
        }
    }

    /**
     * Obtain a short-lived OAuth2 access token from the service-account credentials file.
     */
    private static function getAccessToken(): ?string
    {
        $credentialsPath = config('services.firebase.credentials');

        if (empty($credentialsPath) || ! file_exists($credentialsPath)) {
            return null;
        }

        $credentials = json_decode(file_get_contents($credentialsPath), true);
        if (! $credentials) {
            return null;
        }

        $now    = time();
        $header = self::base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = self::base64url(json_encode([
            'iss'   => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $signingInput = "{$header}.{$claims}";

        openssl_sign($signingInput, $signature, $credentials['private_key'], 'SHA256');
        $jwt = $signingInput . '.' . self::base64url($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        return $response->json('access_token');
    }

    /** RFC 4648 §5 URL-safe base64 encoding without padding. */
    private static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
