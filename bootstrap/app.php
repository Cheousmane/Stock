<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'quota' => \App\Http\Middleware\CheckSubscriptionQuota::class,
            'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        $middleware->removeFromGroup('api', \Illuminate\Routing\Middleware\SubstituteBindings::class);

        // Apply security headers to all routes
        $middleware->prependToGroup('web', \App\Http\Middleware\SecurityHeadersMiddleware::class);
        $middleware->prependToGroup('api', \App\Http\Middleware\SecurityHeadersMiddleware::class);
    })
->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('reminders:send')->dailyAt('08:00')->withoutOverlapping();
        $schedule->command('stock:alerts')->dailyAt('08:05')->withoutOverlapping();
        $schedule->command('companies:manage-statuses')->dailyAt('08:10')->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
