<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\OneSignalMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toOneSignal')) {
            return;
        }

        $appId = config('services.onesignal.app_id');
        $apiKey = config('services.onesignal.rest_api_key');

        if (! $appId || ! $apiKey) {
            return;
        }

        $playerIds = $notifiable->routeNotificationFor('OneSignal', $notification) ?? [];
        if (empty($playerIds)) {
            return;
        }

        /** @var OneSignalMessage $message */
        $message = $notification->toOneSignal($notifiable);
        if (! $message instanceof OneSignalMessage) {
            return;
        }

        $payload = [
            'app_id' => $appId,
            'include_player_ids' => array_values($playerIds),
            'headings' => ['en' => $message->title, 'it' => $message->title],
            'contents' => ['en' => $message->body, 'it' => $message->body],
        ];

        if ($message->url) {
            $payload['url'] = $message->url;
        }

        $data = $message->data;
        if (method_exists($notifiable, 'unreadNotifications')) {
            $data['unread_count'] = $notifiable->unreadNotifications()->count();
        }
        if (! empty($data)) {
            $payload['data'] = $data;
        }
        if ($message->icon) {
            $payload['chrome_web_icon'] = $message->icon;
            $payload['firefox_icon'] = $message->icon;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic '.$apiKey,
            'Content-Type' => 'application/json; charset=utf-8',
        ])->post('https://api.onesignal.com/notifications', $payload);

        if ($response->failed()) {
            Log::warning('OneSignal push failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'notification' => get_class($notification),
            ]);
        }
    }
}
