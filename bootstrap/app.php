<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckSubscriptionQuota;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: explode(',', (string) env('TRUSTED_PROXIES', '*')));
        $middleware->alias([
            'tenant' => TenantMiddleware::class,
            'quota' => CheckSubscriptionQuota::class,
            'bindings' => SubstituteBindings::class,
            'admin' => AdminMiddleware::class,
            'security.headers' => SecurityHeadersMiddleware::class,
        ]);

        $middleware->removeFromGroup('api', SubstituteBindings::class);

        // Apply security headers to all routes
        $middleware->prependToGroup('web', SecurityHeadersMiddleware::class);
        $middleware->prependToGroup('api', SecurityHeadersMiddleware::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('reminders:send')->dailyAt('08:00')->withoutOverlapping();
        $schedule->command('stock:alerts')->dailyAt('08:05')->withoutOverlapping();
        $schedule->command('companies:manage-statuses')->dailyAt('08:10')->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
