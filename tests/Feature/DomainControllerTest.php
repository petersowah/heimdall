<?php

use Illuminate\Support\Facades\Bus;
use Orchestra\Testbench\Factories\UserFactory;
use PeterSowah\Heimdall\Jobs\RunDnsCheck;
use PeterSowah\Heimdall\Jobs\RunSslCheck;
use PeterSowah\Heimdall\Jobs\RunUptimeCheck;
use PeterSowah\Heimdall\Jobs\RunWhoisCheck;
use PeterSowah\Heimdall\Models\Domain;

beforeEach(function () {
    Bus::fake();
    $this->user = UserFactory::new()->create();
    $this->other = UserFactory::new()->create();
});

it('requires authentication', function () {
    $this->getJson('/heimdall/api/domains')->assertUnauthorized();
});

it('returns only the authenticated users domains', function () {
    Domain::create(['user_id' => $this->user->id, 'name' => 'mine.com']);
    Domain::create(['user_id' => $this->other->id, 'name' => 'theirs.com']);

    $this->actingAs($this->user)
        ->getJson('/heimdall/api/domains')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'mine.com');
});

it('stores a domain and dispatches all check jobs', function () {
    $this->actingAs($this->user)
        ->postJson('/heimdall/api/domains', ['name' => 'example.com'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'example.com');

    $this->assertDatabaseHas('heimdall_domains', [
        'user_id' => $this->user->id,
        'name' => 'example.com',
    ]);

    Bus::assertDispatched(RunSslCheck::class);
    Bus::assertDispatched(RunUptimeCheck::class);
    Bus::assertDispatched(RunDnsCheck::class);
    Bus::assertDispatched(RunWhoisCheck::class);
});

it('normalises the domain name on store', function () {
    $this->actingAs($this->user)
        ->postJson('/heimdall/api/domains', ['name' => '  EXAMPLE.COM/ '])
        ->assertCreated()
        ->assertJsonPath('data.name', 'example.com');
});

it('rejects duplicate domain names for the same user', function () {
    Domain::create(['user_id' => $this->user->id, 'name' => 'example.com']);

    $this->actingAs($this->user)
        ->postJson('/heimdall/api/domains', ['name' => 'example.com'])
        ->assertUnprocessable();
});

it('allows the same domain name for different users', function () {
    Domain::create(['user_id' => $this->other->id, 'name' => 'example.com']);

    $this->actingAs($this->user)
        ->postJson('/heimdall/api/domains', ['name' => 'example.com'])
        ->assertCreated();
});

it('shows a domain', function () {
    $domain = Domain::create(['user_id' => $this->user->id, 'name' => 'example.com']);

    $this->actingAs($this->user)
        ->getJson("/heimdall/api/domains/{$domain->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'example.com');
});

it('forbids viewing another users domain', function () {
    $domain = Domain::create(['user_id' => $this->other->id, 'name' => 'example.com']);

    $this->actingAs($this->user)
        ->getJson("/heimdall/api/domains/{$domain->id}")
        ->assertForbidden();
});

it('updates a domain', function () {
    $domain = Domain::create(['user_id' => $this->user->id, 'name' => 'example.com']);

    $this->actingAs($this->user)
        ->putJson("/heimdall/api/domains/{$domain->id}", ['is_active' => false])
        ->assertOk();

    $this->assertDatabaseHas('heimdall_domains', ['id' => $domain->id, 'is_active' => false]);
});

it('forbids updating another users domain', function () {
    $domain = Domain::create(['user_id' => $this->other->id, 'name' => 'example.com']);

    $this->actingAs($this->user)
        ->putJson("/heimdall/api/domains/{$domain->id}", ['is_active' => false])
        ->assertForbidden();
});

it('deletes a domain', function () {
    $domain = Domain::create(['user_id' => $this->user->id, 'name' => 'example.com']);

    $this->actingAs($this->user)
        ->deleteJson("/heimdall/api/domains/{$domain->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('heimdall_domains', ['id' => $domain->id]);
});

it('forbids deleting another users domain', function () {
    $domain = Domain::create(['user_id' => $this->other->id, 'name' => 'example.com']);

    $this->actingAs($this->user)
        ->deleteJson("/heimdall/api/domains/{$domain->id}")
        ->assertForbidden();
});

it('dispatches all check jobs on manual check', function () {
    $domain = Domain::create(['user_id' => $this->user->id, 'name' => 'example.com']);

    $this->actingAs($this->user)
        ->postJson("/heimdall/api/domains/{$domain->id}/check")
        ->assertOk();

    Bus::assertDispatched(RunSslCheck::class);
    Bus::assertDispatched(RunUptimeCheck::class);
    Bus::assertDispatched(RunDnsCheck::class);
    Bus::assertDispatched(RunWhoisCheck::class);
});
