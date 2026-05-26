<?php

namespace PeterSowah\Heimdall\Services\Notifications;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PeterSowah\Heimdall\Models\AlertLog;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Models\NotificationSetting;

class SlackNotificationService
{
    public function send(Domain $domain, string $alertType, string $message, array $fields = []): bool
    {
        $setting = NotificationSetting::where('user_id', $domain->user_id)->first();

        if (! $setting?->slack_webhook_url) {
            return false;
        }

        if ($this->isOnCooldown($domain, 'slack', $alertType)) {
            return false;
        }

        $statusEmoji = $this->statusEmoji($alertType);

        $blocks = [
            [
                'type' => 'header',
                'text' => ['type' => 'plain_text', 'text' => "{$statusEmoji} Heimdall Alert: {$domain->name}"],
            ],
            [
                'type' => 'section',
                'text' => ['type' => 'mrkdwn', 'text' => $message],
            ],
        ];

        if (! empty($fields)) {
            $blocks[] = [
                'type' => 'section',
                'fields' => array_map(fn ($k, $v) => [
                    'type' => 'mrkdwn',
                    'text' => "*{$k}:*\n{$v}",
                ], array_keys($fields), array_values($fields)),
            ];
        }

        $payload = ['blocks' => $blocks];

        try {
            $response = Http::post($setting->slack_webhook_url, $payload);

            if ($response->successful()) {
                AlertLog::create([
                    'domain_id' => $domain->id,
                    'channel' => 'slack',
                    'alert_type' => $alertType,
                    'sent_at' => now(),
                    'payload' => $payload,
                ]);

                return true;
            }
        } catch (Exception $e) {
            Log::error("Heimdall Slack notification failed for domain {$domain->name}: {$e->getMessage()}");
        }

        return false;
    }

    private function isOnCooldown(Domain $domain, string $channel, string $alertType): bool
    {
        return $domain->alertLogs()
            ->where('channel', $channel)
            ->where('alert_type', $alertType)
            ->where('sent_at', '>=', now()->subHours(24))
            ->exists();
    }

    private function statusEmoji(string $alertType): string
    {
        return match (true) {
            str_contains($alertType, 'resolved') => ':white_check_mark:',
            str_contains($alertType, 'critical') => ':rotating_light:',
            str_contains($alertType, 'warning') => ':warning:',
            default => ':information_source:',
        };
    }
}
