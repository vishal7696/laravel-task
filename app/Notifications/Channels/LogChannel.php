<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Minimal custom notification channel that writes to the shopify_import
 * log channel. Registered in AppServiceProvider via Notification::extend.
 * Laravel does not ship a "log" notification channel out of the box, so we
 * provide a tiny one here rather than pulling in mail/Slack credentials
 * just to satisfy the "error notification system" bonus requirement.
 */
class LogChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $message = method_exists($notification, 'toLog')
            ? $notification->toLog($notifiable)
            : (string) $notification;

        Log::channel('shopify_import')->error('notification: '.$message);
    }
}
