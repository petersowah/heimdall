<?php

use Orchestra\Testbench\Factories\UserFactory;
use PeterSowah\Heimdall\Heimdall;

it('grants access when auth callback returns true', function () {
    Heimdall::auth(fn () => true);

    $this->actingAs(UserFactory::new()->create())
        ->getJson('/heimdall/api/domains')
        ->assertOk();
});

it('denies access when auth callback returns false', function () {
    Heimdall::auth(fn () => false);

    $this->actingAs(UserFactory::new()->create())
        ->getJson('/heimdall/api/domains')
        ->assertForbidden();
});

it('allows filtering by user email', function () {
    $allowed = UserFactory::new()->create(['email' => 'admin@example.com']);
    $denied = UserFactory::new()->create(['email' => 'other@example.com']);

    Heimdall::auth(fn ($user) => $user->email === 'admin@example.com');

    $this->actingAs($allowed)->getJson('/heimdall/api/domains')->assertOk();
    $this->actingAs($denied)->getJson('/heimdall/api/domains')->assertForbidden();
});
