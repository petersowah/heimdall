<?php

namespace PeterSowah\Heimdall\Services\Notifications;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PeterSowah\Heimdall\Models\AlertLog;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Models\NotificationSetting;

class TelegramNotificationService
{
    public function send(Domain $domain, string $alertType, string $message, array $fields = []): bool
    {
        $setting = NotificationSetting::where('user_id', $domain->user_id)->first();

        if (! $setting?->telegram_bot_token || ! $setting?->telegram_chat_id) {
            return false;
        }

        if ($this->isOnCooldown($domain, 'telegram', $alertType)) {
            return false;
        }

        $statusEmoji = $this->statusEmoji($alertType);
        $text = "<b>{$statusEmoji} Heimdall Alert</b>\n\n";
        $text .= "<b>Domain:</b> {$domain->name}\n";
        $text .= "<b>Alert:</b> {$alertType}\n\n";
        $text .= $message;

        if (! empty($fields)) {
            $text .= "\n\n";
            foreach ($fields as $key => $value) {
                $text .= "<b>{$key}:</b> {$value}\n";
            }
        }

        $payload = [
            'chat_id' => $setting->telegram_chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        try {
            $response = Http::post(
                "https://api.telegram.org/bot{$setting->telegram_bot_token}/sendMessage",
                $payload
            );

            if ($response->successful()) {
                AlertLog::create([
                    'domain_id' => $domain->id,
                    'channel' => 'telegram',
                    'alert_type' => $alertType,
                    'sent_at' => now(),
                    'payload' => $payload,
                ]);

                return true;
            }
        } catch (Exception $e) {
            Log::error("Heimdall Telegram notification failed for domain {$domain->name}: {$e->getMessage()}");
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
            str_contains($alertType, 'resolved') => '✅',
            str_contains($alertType, 'critical') => '🚨',
            str_contains($alertType, 'warning') => '⚠️',
            default => 'ℹ️',
        };
    }
}
