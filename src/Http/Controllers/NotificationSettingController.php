<?php

namespace PeterSowah\Heimdall\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use PeterSowah\Heimdall\Models\NotificationSetting;

class NotificationSettingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $setting = NotificationSetting::where('user_id', $request->user()->id)->first();

        return response()->json([
            'slack_webhook_url' => $setting?->slack_webhook_url,
            'telegram_bot_token' => $setting?->telegram_bot_token ? '***' : null,
            'telegram_chat_id' => $setting?->telegram_chat_id,
            'notification_emails' => $setting?->notification_emails ?? [],
            'has_slack' => (bool) $setting?->slack_webhook_url,
            'has_telegram' => (bool) ($setting?->telegram_bot_token && $setting?->telegram_chat_id),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slack_webhook_url' => ['nullable', 'url', 'max:500'],
            'telegram_bot_token' => ['nullable', 'string', 'max:100'],
            'telegram_chat_id' => ['nullable', 'string', 'max:100'],
            'notification_emails' => ['nullable', 'array'],
            'notification_emails.*' => ['email'],
        ]);

        NotificationSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json(['message' => 'Notification settings updated']);
    }

    public function testSlack(Request $request): JsonResponse
    {
        $setting = NotificationSetting::where('user_id', $request->user()->id)->first();

        if (! $setting?->slack_webhook_url) {
            return response()->json(['message' => 'No Slack webhook configured.'], 422);
        }

        $payload = [
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => ['type' => 'plain_text', 'text' => '✅ Heimdall Test Notification'],
                ],
                [
                    'type' => 'section',
                    'text' => ['type' => 'mrkdwn', 'text' => 'Your Slack notifications are working correctly.'],
                ],
            ],
        ];

        $response = Http::post($setting->slack_webhook_url, $payload);

        if ($response->successful()) {
            return response()->json(['message' => 'Test notification sent to Slack.']);
        }

        return response()->json(['message' => 'Slack delivery failed: '.$response->body()], 422);
    }

    public function testTelegram(Request $request): JsonResponse
    {
        $setting = NotificationSetting::where('user_id', $request->user()->id)->first();

        if (! $setting?->telegram_bot_token || ! $setting?->telegram_chat_id) {
            return response()->json(['message' => 'Telegram bot token and chat ID are required.'], 422);
        }

        $text = "✅ <b>Heimdall Test Notification</b>\n\nYour Telegram notifications are working correctly.";

        $response = Http::post(
            "https://api.telegram.org/bot{$setting->telegram_bot_token}/sendMessage",
            [
                'chat_id' => $setting->telegram_chat_id,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]
        );

        if ($response->successful()) {
            return response()->json(['message' => 'Test notification sent to Telegram.']);
        }

        $error = $response->json('description') ?? $response->body();

        return response()->json(['message' => 'Telegram delivery failed: '.$error], 422);
    }
}
