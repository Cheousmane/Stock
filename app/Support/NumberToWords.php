<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Converts integer amounts to French words (orthographe classique).
 * Used for "amount in words" blocks on invoices, quotes, etc.
 */
final class NumberToWords
{
    private const UNITS = [
        0 => 'zéro', 1 => 'un', 2 => 'deux', 3 => 'trois', 4 => 'quatre',
        5 => 'cinq', 6 => 'six', 7 => 'sept', 8 => 'huit', 9 => 'neuf',
        10 => 'dix', 11 => 'onze', 12 => 'douze', 13 => 'treize',
        14 => 'quatorze', 15 => 'quinze', 16 => 'seize',
    ];

    private const TENS = [
        2 => 'vingt', 3 => 'trente', 4 => 'quarante',
        5 => 'cinquante', 6 => 'soixante', 8 => 'quatre-vingt',
    ];

    /**
     * Currency codes -> French currency name used in "amount in words".
     */
    private const CURRENCY_NAMES = [
        'XOF' => 'francs CFA',
        'FCFA' => 'francs CFA',
        'XAF' => 'francs CFA',
        'EUR' => 'euros',
        'USD' => 'dollars américains',
        'GHS' => 'cédis',
        'NGN' => 'naira',
    ];

    public static function convert(int $number): string
    {
        if ($number < 0) {
            return 'moins '.self::convert(-$number);
        }

        if ($number < 17) {
            return self::UNITS[$number];
        }

        if ($number < 20) {
            return 'dix-'.self::UNITS[$number - 10];
        }

        if ($number < 70) {
            return self::twoDigits($number, intdiv($number, 10));
        }

        if ($number < 80) {
            return match ($number) {
                70 => 'soixante-dix',
                71 => 'soixante et onze',
                default => 'soixante-'.self::convert($number - 60),
            };
        }

        if ($number < 100) {
            return $number === 80
                ? 'quatre-vingts'
                : 'quatre-vingt-'.self::convert($number - 80);
        }

        if ($number < 1000) {
            $hundreds = intdiv($number, 100);
            $rest = $number % 100;

            $prefix = $hundreds === 1 ? 'cent' : self::convert($hundreds).' cent';

            if ($rest === 0) {
                return $hundreds > 1 ? $prefix.'s' : $prefix;
            }

            return $prefix.' '.self::convert($rest);
        }

        return self::convertLarge($number);
    }

    /**
     * Convert an amount to French words with the proper currency name.
     * Example: 125000, 'XOF' -> "cent vingt-cinq mille francs CFA".
     */
    public static function amountToWords(int $amount, string $currency = 'XOF'): string
    {
        $words = self::convert($amount);
        $name = self::CURRENCY_NAMES[strtoupper($currency)] ?? strtoupper($currency);

        return ucfirst($words.' '.$name);
    }

    private static function twoDigits(int $number, int $ten): string
    {
        $unit = $number % 10;

        if ($unit === 0) {
            return self::TENS[$ten];
        }

        if ($unit === 1) {
            return self::TENS[$ten].' et un';
        }

        return self::TENS[$ten].'-'.self::UNITS[$unit];
    }

    private static function convertLarge(int $number): string
    {
        $scales = [
            [1_000_000_000, 'milliard', 'milliards'],
            [1_000_000, 'million', 'millions'],
            [1_000, 'mille', 'mille'],
        ];

        foreach ($scales as [$value, $singular, $plural]) {
            if ($number >= $value) {
                $count = intdiv($number, $value);
                $rest = $number % $value;

                $word = match (true) {
                    $value === 1_000 && $count === 1 => 'mille',
                    $count === 1 => 'un '.$singular,
                    default => self::convert($count).' '.$plural,
                };

                return $rest === 0 ? $word : $word.' '.self::convert($rest);
            }
        }

        return (string) $number;
    }
}
