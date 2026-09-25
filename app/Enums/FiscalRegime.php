<?php

declare(strict_types=1);

namespace App\Enums;

enum FiscalRegime: string
{
    case Standard = 'standard';
    case EMECeF = 'emecf';        // Benin e-MECeF format
    case FNC = 'fnc';             // Côte d'Ivoire FNC format
    case OHADA = 'ohada';         // OHADA accounting regime
    case Custom = 'custom';       // Custom/Configurable regime

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::EMECeF => 'e-MECeF (Bénin)',
            self::FNC => 'FNC (Côte d\'Ivoire)',
            self::OHADA => 'OHADA',
            self::Custom => 'Personnalisé',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Standard => 'FAC',
            self::EMECeF => 'EME',
            self::FNC => 'FNC',
            self::OHADA => 'OHD',
            self::Custom => 'CFG',
        };
    }

    public function requiresFiscalValidation(): bool
    {
        return in_array($this, [self::EMECeF, self::FNC, self::OHADA], true);
    }
}