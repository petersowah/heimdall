<?php

namespace PeterSowah\Heimdall;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use PeterSowah\Heimdall\Http\Middleware\Authorize;
use PeterSowah\Heimdall\Models\Domain;
use PeterSowah\Heimdall\Policies\DomainPolicy;

class HeimdallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/heimdall.php', 'heimdall');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/heimdall.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'heimdall');

        $this->publishes([
            __DIR__.'/../public/vendor/heimdall' => public_path('vendor/heimdall'),
        ], 'heimdall-assets');

        $this->publishes([
            __DIR__.'/../config/heimdall.php' => config_path('heimdall.php'),
        ], 'heimdall-config');

        Gate::policy(Domain::class, DomainPolicy::class);

        Gate::define('viewHeimdall', fn ($user) => Heimdall::check($user));
    }
}
