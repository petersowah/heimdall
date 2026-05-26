<?php

namespace PeterSowah\Heimdall\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PeterSowah\Heimdall\Heimdall;
use PeterSowah\Heimdall\HeimdallServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard();
        Heimdall::auth(fn () => true);
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [HeimdallServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('heimdall.domain', 'localhost');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app->afterResolving('migrator', function ($migrator) {
            $migrator->path(realpath(__DIR__.'/../vendor/orchestra/testbench-core/laravel/migrations'));
            $migrator->path(__DIR__.'/../database/migrations');
        });
    }
}
