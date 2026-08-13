<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Support class for XOF currency operations.
 * Stores values as BigInt integers (zero-decimal currency).
 */
final class Money
{
    /**
     * Format a BigInt amount to a human-readable string with the given currency code.
     * Example: 1500000 -> "1 500 000 XOF" (or any other code, e.g. "FCFA").
     */
    public static function format(int $amount, ?string $currency = null): string
    {
        $currency ??= 'XOF';

        return number_format((float) $amount, 0, '.', ' ').' '.$currency;
    }

    /**
     * Safe addition of multiple XOF amounts.
     */
    public static function add(int ...$amounts): int
    {
        return array_sum($amounts);
    }

    /**
     * Safe subtraction from a base amount.
     */
    public static function subtract(int $base, int ...$amounts): int
    {
        return $base - array_sum($amounts);
    }

    /**
     * Multiply amount by a factor (e.g., tax rate, discount) and round to nearest integer.
     */
    public static function multiply(int $amount, float $factor): int
    {
        return (int) round($amount * $factor);
    }

    /**
     * Calculate a percentage of an amount and round to nearest integer.
     * Example: percent(1000, 18.0) -> 180 (for 18% VAT)
     */
    public static function percent(int $amount, float $percentage): int
    {
        if ($percentage < 0) {
            throw new InvalidArgumentException('Percentage cannot be negative.');
        }

        return (int) round(($amount * $percentage) / 100.0);
    }

    /**
     * Divide amount by a divisor and round to nearest integer.
     */
    public static function divide(int $amount, float $divisor): int
    {
        if ($divisor == 0.0) {
            throw new InvalidArgumentException('Division by zero.');
        }

        return (int) round($amount / $divisor);
    }

    /**
     * Convert float value securely to BigInt integer.
     */
    public static function fromFloat(float $value): int
    {
        return (int) round($value);
    }
}
