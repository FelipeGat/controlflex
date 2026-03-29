<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject'    => config('services.vapid.subject'),
                'publicKey'  => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ]);
    }

    /**
     * Send a push notification to all subscriptions of a given tenant.
     */
    public function notifyTenant(int $tenantId, string $title, string $body, array $data = []): void
    {
        $subscriptions = PushSubscription::where('tenant_id', $tenantId)->get();

        foreach ($subscriptions as $sub) {
            $this->webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'contentEncoding' => 'aesgcm',
                    'keys'            => [
                        'p256dh' => $sub->p256dh,
                        'auth'   => $sub->auth,
                    ],
                ]),
                json_encode([
                    'title' => $title,
                    'body'  => $body,
                    'icon'  => '/icons/icon-192.png',
                    'badge' => '/icons/icon-192.png',
                    'url'   => $data['url'] ?? '/dashboard',
                    'data'  => $data,
                ])
            );
        }

        // Send all queued notifications and clean up expired subscriptions
        foreach ($this->webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
            }
        }
    }

    /**
     * Send to a single user.
     */
    public function notifyUser(int $userId, string $title, string $body, array $data = []): void
    {
        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        foreach ($subscriptions as $sub) {
            $this->webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'contentEncoding' => 'aesgcm',
                    'keys'            => ['p256dh' => $sub->p256dh, 'auth' => $sub->auth],
                ]),
                json_encode([
                    'title' => $title,
                    'body'  => $body,
                    'icon'  => '/icons/icon-192.png',
                    'url'   => $data['url'] ?? '/dashboard',
                    'data'  => $data,
                ])
            );
        }

        foreach ($this->webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
            }
        }
    }
}
