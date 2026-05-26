<?php

use Orchestra\Testbench\Factories\UserFactory;
use PeterSowah\Heimdall\Jobs\RunUptimeCheck;
use PeterSowah\Heimdall\Models\Check;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Models\Incident;
use PeterSowah\Heimdall\Services\Checkers\UptimeCheckerService;
use PeterSowah\Heimdall\Services\Notifications\EmailNotificationService;
use PeterSowah\Heimdall\Services\Notifications\SlackNotificationService;
use PeterSowah\Heimdall\Services\Notifications\TelegramNotificationService;

function makeUptimeNotifiers(int $times = 0): array
{
    $slack = Mockery::mock(SlackNotificationService::class);
    $telegram = Mockery::mock(TelegramNotificationService::class);
    $email = Mockery::mock(EmailNotificationService::class);

    if ($times > 0) {
        $slack->shouldReceive('send')->times($times);
        $telegram->shouldReceive('send')->times($times);
        $email->shouldReceive('send')->times($times);
    } else {
        $slack->shouldReceive('send')->never();
        $telegram->shouldReceive('send')->never();
        $email->shouldReceive('send')->never();
    }

    return [$slack, $telegram, $email];
}

function runUptime(Domain $domain, array $result, array $notifiers): void
{
    $checker = Mockery::mock(UptimeCheckerService::class);
    $checker->shouldReceive('check')->once()->andReturn($result);
    [$slack, $telegram, $email] = $notifiers;
    (new RunUptimeCheck($domain))->handle($checker, $slack, $telegram, $email);
}

beforeEach(function () {
    $user = UserFactory::new()->create();
    $this->domain = Domain::create([
        'user_id' => $user->id,
        'name' => 'example.com',
        'is_active' => true,
        'notify_uptime' => true,
    ]);
});

it('skips check when domain is inactive', function () {
    $this->domain->update(['is_active' => false]);

    $checker = Mockery::mock(UptimeCheckerService::class);
    $checker->shouldReceive('check')->never();

    [$slack, $telegram, $email] = makeUptimeNotifiers(0);
    (new RunUptimeCheck($this->domain))->handle($checker, $slack, $telegram, $email);

    expect(Check::count())->toBe(0);
});

it('creates a check record', function () {
    runUptime($this->domain, [
        'status' => 'ok',
        'value' => 120,
        'message' => 'HTTP 200 in 120ms',
        'raw_data' => ['http_status' => 200, 'response_time_ms' => 120],
    ], makeUptimeNotifiers(0));

    expect(Check::count())->toBe(1);
    expect(Check::first()->type)->toBe('uptime');
    expect(Check::first()->status)->toBe('ok');
});

it('opens an incident after 3 consecutive failures', function () {
    $okResult = ['status' => 'ok', 'value' => 100, 'message' => 'ok', 'raw_data' => []];
    $badResult = ['status' => 'critical', 'value' => null, 'message' => 'down', 'raw_data' => []];

    runUptime($this->domain, $badResult, makeUptimeNotifiers(0));
    runUptime($this->domain, $badResult, makeUptimeNotifiers(0));

    expect(Incident::count())->toBe(0);

    runUptime($this->domain, $badResult, makeUptimeNotifiers(1));

    expect(Incident::count())->toBe(1);
    expect(Incident::first()->status)->toBe('open');
});

it('does not open duplicate incidents', function () {
    $badResult = ['status' => 'critical', 'value' => null, 'message' => 'down', 'raw_data' => []];

    runUptime($this->domain, $badResult, makeUptimeNotifiers(0));
    runUptime($this->domain, $badResult, makeUptimeNotifiers(0));
    runUptime($this->domain, $badResult, makeUptimeNotifiers(1));

    runUptime($this->domain, $badResult, makeUptimeNotifiers(0));
    runUptime($this->domain, $badResult, makeUptimeNotifiers(0));

    expect(Incident::count())->toBe(1);
});

it('resolves open incident on recovery', function () {
    Incident::create([
        'domain_id' => $this->domain->id,
        'type' => 'uptime',
        'status' => 'open',
        'started_at' => now()->subMinutes(30),
        'details' => 'down',
    ]);

    runUptime($this->domain, [
        'status' => 'ok',
        'value' => 100,
        'message' => 'HTTP 200 in 100ms',
        'raw_data' => [],
    ], makeUptimeNotifiers(1));

    expect(Incident::first()->status)->toBe('resolved');
    expect(Incident::first()->resolved_at)->not->toBeNull();
});

it('does not notify on recovery when no open incident exists', function () {
    runUptime($this->domain, [
        'status' => 'ok',
        'value' => 100,
        'message' => 'HTTP 200 in 100ms',
        'raw_data' => [],
    ], makeUptimeNotifiers(0));
});
