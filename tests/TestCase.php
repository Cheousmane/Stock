<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('substring_index', function ($str, $delim, $count) {
                $parts = explode($delim, $str);
                if ($count < 0) {
                    return implode($delim, array_slice($parts, (int) $count));
                }
                return implode($delim, array_slice($parts, 0, (int) $count));
            });
        }
    }
}
