<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\Test;

describe('Money', function () {

    it('formats integer as XOF currency string', function () {
        expect(Money::format(1_500_000))->toBe('1 500 000 XOF');
    });

    it('formats zero', function () {
        expect(Money::format(0))->toBe('0 XOF');
    });

    it('adds multiple amounts correctly', function () {
        expect(Money::add(1000, 2000, 3000))->toBe(6000);
    });

    it('adds with single amount', function () {
        expect(Money::add(5000))->toBe(5000);
    });

    it('adds with no arguments', function () {
        expect(Money::add())->toBe(0);
    });

    it('subtracts amounts correctly', function () {
        expect(Money::subtract(10000, 3000, 2000))->toBe(5000);
    });

    it('subtracts single amount', function () {
        expect(Money::subtract(5000, 2000))->toBe(3000);
    });

    it('subtracts with no amounts', function () {
        expect(Money::subtract(1000))->toBe(1000);
    });

    it('calculates percentage correctly', function () {
        expect(Money::percent(10000, 18.0))->toBe(1800);
    });

    it('calculates 0% percentage', function () {
        expect(Money::percent(5000, 0.0))->toBe(0);
    });

    it('calculates 100% percentage', function () {
        expect(Money::percent(2500, 100.0))->toBe(2500);
    });

    it('calculates 50% percentage rounding down', function () {
        expect(Money::percent(199, 50.0))->toBe(100);
    });

    it('converts float to integer safely', function () {
        expect(Money::fromFloat(1500.75))->toBe(1501);
    });

    it('converts float with .5 rounds up', function () {
        expect(Money::fromFloat(1000.5))->toBe(1001);
    });

    it('converts float with .49 rounds down', function () {
        expect(Money::fromFloat(1000.49))->toBe(1000);
    });

});
