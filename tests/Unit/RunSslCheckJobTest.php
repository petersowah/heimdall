<?php

use Orchestra\Testbench\Factories\UserFactory;
use PeterSowah\Heimdall\Jobs\RunSslCheck;
use PeterSowah\Heimdall\Models\Check;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Services\Checkers\SslCheckerService;
use PeterSowah\Heimdall\Services\Notifications\EmailNotificationService;
use PeterSowah\Heimdall\Services\Notifications\SlackNotificationService;
use PeterSowah\Heimdall\Services\Notifications\TelegramNotificationService;

function makeSslNotifiers(bool $expectSend = false): array
{
    $slack = Mockery::mock(SlackNotificationService::class);
    $telegram = Mockery::mock(TelegramNotificationService::class);
    $email = Mockery::mock(EmailNotificationService::class);

    if ($expectSend) {
        $slack->shouldReceive('send')->once();
        $telegram->shouldReceive('send')->once();
        $email->shouldReceive('send')->once();
    } else {
        $slack->shouldReceive('send')->never();
        $telegram->shouldReceive('send')->never();
        $email->shouldReceive('send')->never();
    }

    return [$slack, $telegram, $email];
}

beforeEach(function () {
    $user = UserFactory::new()->create();
    $this->domain = Domain::create([
        'user_id' => $user->id,
        'name' => 'example.com',
        'is_active' => true,
        'notify_ssl' => true,
    ]);
});

it('skips check when domain is inactive', function () {
    $this->domain->update(['is_active' => false]);

    $checker = Mockery::mock(SslCheckerService::class);
    $checker->shouldReceive('check')->never();

    [$slack, $telegram, $email] = makeSslNotifiers(false);

    (new RunSslCheck($this->domain))->handle($checker, $slack, $telegram, $email);

    expect(Check::count())->toBe(0);
});

it('creates a check record with the result', function () {
    $checker = Mockery::mock(SslCheckerService::class);
    $checker->shouldReceive('check')->once()->andReturn([
        'status' => 'ok',
        'value' => 90,
        'message' => 'SSL expires in 90 days',
        'raw_data' => [],
    ]);

    [$slack, $telegram, $email] = makeSslNotifiers(false);

    (new RunSslCheck($this->domain))->handle($checker, $slack, $telegram, $email);

    expect(Check::count())->toBe(1);

    $check = Check::first();
    expect($check->type)->toBe('ssl');
    expect($check->status)->toBe('ok');
    expect($check->value)->toBe(90);
    expect($check->domain_id)->toBe($this->domain->id);
});

it('sends notifications on warning status', function () {
    $checker = Mockery::mock(SslCheckerService::class);
    $checker->shouldReceive('check')->once()->andReturn([
        'status' => 'warning',
        'value' => 25,
        'message' => 'SSL expires in 25 days',
        'raw_data' => [],
    ]);

    [$slack, $telegram, $email] = makeSslNotifiers(true);

    (new RunSslCheck($this->domain))->handle($checker, $slack, $telegram, $email);
});

it('sends notifications on critical status', function () {
    $checker = Mockery::mock(SslCheckerService::class);
    $checker->shouldReceive('check')->once()->andReturn([
        'status' => 'critical',
        'value' => 3,
        'message' => 'SSL expires in 3 days',
        'raw_data' => [],
    ]);

    [$slack, $telegram, $email] = makeSslNotifiers(true);

    (new RunSslCheck($this->domain))->handle($checker, $slack, $telegram, $email);
});

it('does not send notifications when notify_ssl is false', function () {
    $this->domain->update(['notify_ssl' => false]);

    $checker = Mockery::mock(SslCheckerService::class);
    $checker->shouldReceive('check')->once()->andReturn([
        'status' => 'critical',
        'value' => 3,
        'message' => 'SSL expires in 3 days',
        'raw_data' => [],
    ]);

    [$slack, $telegram, $email] = makeSslNotifiers(false);

    (new RunSslCheck($this->domain))->handle($checker, $slack, $telegram, $email);
});

it('does not send notifications on ok status', function () {
    $checker = Mockery::mock(SslCheckerService::class);
    $checker->shouldReceive('check')->once()->andReturn([
        'status' => 'ok',
        'value' => 90,
        'message' => 'SSL expires in 90 days',
        'raw_data' => [],
    ]);

    [$slack, $telegram, $email] = makeSslNotifiers(false);

    (new RunSslCheck($this->domain))->handle($checker, $slack, $telegram, $email);
});
