<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class AdminDashboardCache
{
    public const TTL = 300;

    private const VERSION_KEY = 'admin.dashboard.version';

    /**
     * Invalidate the admin dashboard cache. Called on every relevant write
     * (companies, users, invoices) via model observers.
     */
    public static function bump(): void
    {
        Cache::increment(self::VERSION_KEY);
    }

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 0);
    }

    public static function key(): string
    {
        return 'admin.dashboard.static.v' . self::version();
    }

    /**
     * Remember the "static" part of the admin dashboard.
     * The version counter in the key makes invalidation instant: any write
     * creates a fresh cache entry instead of a stale one.
     */
    public static function remember(callable $callback): mixed
    {
        return Cache::remember(self::key(), self::TTL, $callback);
    }
}
