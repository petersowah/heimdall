<?php

namespace PeterSowah\Heimdall\Services\Notifications;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PeterSowah\Heimdall\Mail\DomainAlertMail;
use PeterSowah\Heimdall\Models\AlertLog;
use PeterSowah\Heimdall\Models\Domain;

class EmailNotificationService
{
    public function send(Domain $domain, string $alertType, string $message, array $fields = []): bool
    {
        $recipients = $this->resolveRecipients($domain);

        if (empty($recipients)) {
            return false;
        }

        if ($this->isOnCooldown($domain, 'email', $alertType)) {
            return false;
        }

        try {
            Mail::to($recipients)->send(new DomainAlertMail($domain, $alertType, $message, $fields));

            AlertLog::create([
                'domain_id' => $domain->id,
                'channel' => 'email',
                'alert_type' => $alertType,
                'sent_at' => now(),
                'payload' => ['recipients' => $recipients, 'message' => $message],
            ]);

            return true;
        } catch (Exception $e) {
            Log::error("Heimdall email notification failed for domain {$domain->name}: {$e->getMessage()}");
        }

        return false;
    }

    private function resolveRecipients(Domain $domain): array
    {
        $fromConfig = config('heimdall.alert_emails', '');

        if (is_string($fromConfig) && $fromConfig !== '') {
            return array_map('trim', explode(',', $fromConfig));
        }

        if (is_array($fromConfig) && ! empty($fromConfig)) {
            return $fromConfig;
        }

        $setting = \PeterSowah\Heimdall\Models\NotificationSetting::where('user_id', $domain->user_id)->first();

        return $setting?->notification_emails ?? [];
    }

    private function isOnCooldown(Domain $domain, string $channel, string $alertType): bool
    {
        return $domain->alertLogs()
            ->where('channel', $channel)
            ->where('alert_type', $alertType)
            ->where('sent_at', '>=', now()->subHours(24))
            ->exists();
    }
}
