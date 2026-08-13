<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\NumberToWords;

describe('NumberToWords', function () {

    it('converts units', function () {
        expect(NumberToWords::convert(1))->toBe('un');
        expect(NumberToWords::convert(16))->toBe('seize');
        expect(NumberToWords::convert(0))->toBe('zéro');
    });

    it('converts teens', function () {
        expect(NumberToWords::convert(17))->toBe('dix-sept');
        expect(NumberToWords::convert(19))->toBe('dix-neuf');
    });

    it('converts tens', function () {
        expect(NumberToWords::convert(20))->toBe('vingt');
        expect(NumberToWords::convert(21))->toBe('vingt et un');
        expect(NumberToWords::convert(35))->toBe('trente-cinq');
        expect(NumberToWords::convert(61))->toBe('soixante et un');
    });

    it('converts seventies', function () {
        expect(NumberToWords::convert(70))->toBe('soixante-dix');
        expect(NumberToWords::convert(71))->toBe('soixante et onze');
        expect(NumberToWords::convert(79))->toBe('soixante-dix-neuf');
    });

    it('converts eighties and nineties', function () {
        expect(NumberToWords::convert(80))->toBe('quatre-vingts');
        expect(NumberToWords::convert(81))->toBe('quatre-vingt-un');
        expect(NumberToWords::convert(90))->toBe('quatre-vingt-dix');
        expect(NumberToWords::convert(99))->toBe('quatre-vingt-dix-neuf');
    });

    it('converts hundreds', function () {
        expect(NumberToWords::convert(100))->toBe('cent');
        expect(NumberToWords::convert(200))->toBe('deux cents');
        expect(NumberToWords::convert(201))->toBe('deux cent un');
        expect(NumberToWords::convert(980))->toBe('neuf cent quatre-vingts');
    });

    it('converts thousands', function () {
        expect(NumberToWords::convert(1000))->toBe('mille');
        expect(NumberToWords::convert(1500))->toBe('mille cinq cents');
        expect(NumberToWords::convert(2000))->toBe('deux mille');
        expect(NumberToWords::convert(125000))->toBe('cent vingt-cinq mille');
    });

    it('converts millions and billions', function () {
        expect(NumberToWords::convert(1000000))->toBe('un million');
        expect(NumberToWords::convert(2000000))->toBe('deux millions');
        expect(NumberToWords::convert(2500000000))->toBe('deux milliards cinq cents millions');
    });

    it('converts negative numbers', function () {
        expect(NumberToWords::convert(-45))->toBe('moins quarante-cinq');
    });

    it('formats amount with XOF currency name', function () {
        expect(NumberToWords::amountToWords(125000, 'XOF'))
            ->toBe('Cent vingt-cinq mille francs CFA');
    });

    it('falls back to currency code when unknown', function () {
        expect(NumberToWords::amountToWords(50, 'XYZ'))->toBe('Cinquante XYZ');
    });

});
