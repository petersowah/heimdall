<?php

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\Factories\UserFactory;
use PeterSowah\Heimdall\Models\NotificationSetting;

beforeEach(function () {
    $this->user = UserFactory::new()->create();
});

it('returns null values when no settings exist', function () {
    $this->actingAs($this->user)
        ->getJson('/heimdall/api/notification-settings')
        ->assertOk()
        ->assertJson([
            'slack_webhook_url' => null,
            'telegram_bot_token' => null,
            'has_slack' => false,
            'has_telegram' => false,
        ]);
});

it('masks telegram token in response', function () {
    NotificationSetting::create([
        'user_id' => $this->user->id,
        'telegram_bot_token' => 'secret-token',
        'telegram_chat_id' => '12345',
    ]);

    $this->actingAs($this->user)
        ->getJson('/heimdall/api/notification-settings')
        ->assertOk()
        ->assertJsonPath('telegram_bot_token', '***')
        ->assertJsonPath('has_telegram', true);
});

it('creates notification settings', function () {
    $this->actingAs($this->user)
        ->putJson('/heimdall/api/notification-settings', [
            'slack_webhook_url' => 'https://hooks.slack.com/services/foo',
            'notification_emails' => ['ops@example.com'],
        ])
        ->assertOk();

    $this->assertDatabaseHas('heimdall_notification_settings', [
        'user_id' => $this->user->id,
        'slack_webhook_url' => 'https://hooks.slack.com/services/foo',
    ]);
});

it('updates existing notification settings', function () {
    NotificationSetting::create([
        'user_id' => $this->user->id,
        'slack_webhook_url' => 'https://hooks.slack.com/old',
    ]);

    $this->actingAs($this->user)
        ->putJson('/heimdall/api/notification-settings', [
            'slack_webhook_url' => 'https://hooks.slack.com/new',
        ])
        ->assertOk();

    $this->assertDatabaseCount('heimdall_notification_settings', 1);
    $this->assertDatabaseHas('heimdall_notification_settings', [
        'slack_webhook_url' => 'https://hooks.slack.com/new',
    ]);
});

it('rejects invalid email in notification_emails', function () {
    $this->actingAs($this->user)
        ->putJson('/heimdall/api/notification-settings', [
            'notification_emails' => ['not-an-email'],
        ])
        ->assertUnprocessable();
});

it('returns 422 when testing slack with no webhook configured', function () {
    $this->actingAs($this->user)
        ->postJson('/heimdall/api/notification-settings/test/slack')
        ->assertUnprocessable();
});

it('sends test slack notification', function () {
    Http::fake(['https://hooks.slack.com/*' => Http::response('ok', 200)]);

    NotificationSetting::create([
        'user_id' => $this->user->id,
        'slack_webhook_url' => 'https://hooks.slack.com/services/test',
    ]);

    $this->actingAs($this->user)
        ->postJson('/heimdall/api/notification-settings/test/slack')
        ->assertOk();
});

it('returns 422 when slack webhook responds with error', function () {
    Http::fake(['https://hooks.slack.com/*' => Http::response('invalid_payload', 400)]);

    NotificationSetting::create([
        'user_id' => $this->user->id,
        'slack_webhook_url' => 'https://hooks.slack.com/services/test',
    ]);

    $this->actingAs($this->user)
        ->postJson('/heimdall/api/notification-settings/test/slack')
        ->assertUnprocessable();
});

it('returns 422 when testing telegram with no config', function () {
    $this->actingAs($this->user)
        ->postJson('/heimdall/api/notification-settings/test/telegram')
        ->assertUnprocessable();
});

it('sends test telegram notification', function () {
    Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true], 200)]);

    NotificationSetting::create([
        'user_id' => $this->user->id,
        'telegram_bot_token' => 'bot-token',
        'telegram_chat_id' => '12345',
    ]);

    $this->actingAs($this->user)
        ->postJson('/heimdall/api/notification-settings/test/telegram')
        ->assertOk();
});
