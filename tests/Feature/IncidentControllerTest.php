<?php

use Orchestra\Testbench\Factories\UserFactory;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Models\Incident;

beforeEach(function () {
    $this->user = UserFactory::new()->create();
    $this->other = UserFactory::new()->create();
    $this->domain = Domain::create(['user_id' => $this->user->id, 'name' => 'example.com']);
});

it('returns paginated incidents for a domain', function () {
    Incident::create([
        'domain_id' => $this->domain->id,
        'type' => 'uptime',
        'status' => 'open',
        'started_at' => now(),
        'details' => 'down',
    ]);

    $this->actingAs($this->user)
        ->getJson("/heimdall/api/domains/{$this->domain->id}/incidents")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('forbids viewing incidents for another users domain', function () {
    $other = Domain::create(['user_id' => $this->other->id, 'name' => 'other.com']);

    $this->actingAs($this->user)
        ->getJson("/heimdall/api/domains/{$other->id}/incidents")
        ->assertForbidden();
});
