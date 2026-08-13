<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class DashboardCache
{
    public const TTL = 120;

    public static function key(int $companyId): string
    {
        return "dashboard.{$companyId}";
    }

    public static function forget(int $companyId): void
    {
        Cache::forget(self::key($companyId));
        Cache::forget("dashboard.aggregates.v2.{$companyId}");
        Cache::forget("capital.{$companyId}");
    }
}