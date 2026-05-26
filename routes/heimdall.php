<?php

use Illuminate\Support\Facades\Route;
use PeterSowah\Heimdall\Http\Controllers\CheckController;
use PeterSowah\Heimdall\Http\Controllers\DomainController;
use PeterSowah\Heimdall\Http\Controllers\HeimdallController;
use PeterSowah\Heimdall\Http\Controllers\IncidentController;
use PeterSowah\Heimdall\Http\Controllers\NotificationSettingController;

$domain = config('heimdall.domain');
$path = config('heimdall.path', 'heimdall');
$middleware = config('heimdall.middleware', ['web', 'auth']);

Route::domain($domain ?? '{_heimdall_domain?}')
    ->prefix($path)
    ->middleware($middleware)
    ->name('heimdall.')
    ->group(function () {
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/dashboard', [DomainController::class, 'dashboard'])->name('dashboard');

            Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
            Route::post('/domains', [DomainController::class, 'store'])->name('domains.store');
            Route::get('/domains/{domain}', [DomainController::class, 'show'])->name('domains.show');
            Route::put('/domains/{domain}', [DomainController::class, 'update'])->name('domains.update');
            Route::delete('/domains/{domain}', [DomainController::class, 'destroy'])->name('domains.destroy');
            Route::post('/domains/{domain}/check', [DomainController::class, 'check'])->name('domains.check');
            Route::get('/domains/{domain}/checks', [CheckController::class, 'index'])->name('domains.checks.index');
            Route::get('/domains/{domain}/incidents', [IncidentController::class, 'index'])->name('domains.incidents.index');

            Route::get('/notification-settings', [NotificationSettingController::class, 'show'])->name('notification-settings.show');
            Route::put('/notification-settings', [NotificationSettingController::class, 'update'])->name('notification-settings.update');
            Route::post('/notification-settings/test/slack', [NotificationSettingController::class, 'testSlack'])->name('notification-settings.test.slack');
            Route::post('/notification-settings/test/telegram', [NotificationSettingController::class, 'testTelegram'])->name('notification-settings.test.telegram');
        });

        Route::get('/{view?}', HeimdallController::class)->where('view', '(.*)')->name('index');
    });
