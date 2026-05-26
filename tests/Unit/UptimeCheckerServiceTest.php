<?php

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\Factories\UserFactory;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Services\Checkers\UptimeCheckerService;

beforeEach(function () {
    $user = UserFactory::new()->create();
    $this->domain = Domain::create(['user_id' => $user->id, 'name' => 'example.com']);
    $this->service = new UptimeCheckerService;
});

it('returns ok status for a 200 response', function () {
    Http::fake(['https://example.com' => Http::response('', 200)]);

    $result = $this->service->check($this->domain);

    expect($result['status'])->toBe('ok');
    expect($result['value'])->toBeInt();
});

it('returns critical status for a 500 response', function () {
    Http::fake(['https://example.com' => Http::response('', 500)]);

    $result = $this->service->check($this->domain);

    expect($result['status'])->toBe('critical');
    expect($result['raw_data']['http_status'])->toBe(500);
});

it('returns critical status for a 404 response', function () {
    Http::fake(['https://example.com' => Http::response('', 404)]);

    $result = $this->service->check($this->domain);

    expect($result['status'])->toBe('critical');
});

it('returns warning status for a 301 redirect', function () {
    Http::fake(['https://example.com' => Http::response('', 301)]);

    $result = $this->service->check($this->domain);

    expect($result['status'])->toBe('warning');
});

it('returns critical status when request throws an exception', function () {
    Http::fake(['https://example.com' => fn () => throw new Exception('Connection refused')]);

    $result = $this->service->check($this->domain);

    expect($result['status'])->toBe('critical');
    expect($result['value'])->toBeNull();
    expect($result['message'])->toContain('Connection refused');
});

it('includes response time in raw data', function () {
    Http::fake(['https://example.com' => Http::response('', 200)]);

    $result = $this->service->check($this->domain);

    expect($result['raw_data'])->toHaveKey('response_time_ms');
    expect($result['raw_data'])->toHaveKey('http_status');
});
