<?php

use Orchestra\Testbench\Factories\UserFactory;
use PeterSowah\Heimdall\Models\Check;
use PeterSowah\Heimdall\Models\Domain;

beforeEach(function () {
    $this->user = UserFactory::new()->create();
    $this->other = UserFactory::new()->create();
    $this->domain = Domain::create(['user_id' => $this->user->id, 'name' => 'example.com']);
});

it('returns paginated checks for a domain', function () {
    Check::create([
        'domain_id' => $this->domain->id,
        'type' => 'ssl',
        'status' => 'ok',
        'checked_at' => now(),
        'message' => 'SSL ok',
        'raw_data' => [],
    ]);

    $this->actingAs($this->user)
        ->getJson("/heimdall/api/domains/{$this->domain->id}/checks")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters checks by type', function () {
    foreach (['ssl', 'ssl', 'uptime'] as $type) {
        Check::create([
            'domain_id' => $this->domain->id,
            'type' => $type,
            'status' => 'ok',
            'checked_at' => now(),
            'raw_data' => [],
        ]);
    }

    $this->actingAs($this->user)
        ->getJson("/heimdall/api/domains/{$this->domain->id}/checks?type=ssl")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('forbids viewing checks for another users domain', function () {
    $other = Domain::create(['user_id' => $this->other->id, 'name' => 'other.com']);

    $this->actingAs($this->user)
        ->getJson("/heimdall/api/domains/{$other->id}/checks")
        ->assertForbidden();
});
