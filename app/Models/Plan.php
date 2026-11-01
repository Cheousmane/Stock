<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public const FREE_SLUG = 'free';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'stripe_price_id',
        'price_xof',
        'currency',
        'trial_days',
        'features',
        'quotas',
        'is_active',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'price_xof' => 'integer',
            'features' => 'array',
            'quotas' => 'array',
            'is_active' => 'boolean',
            'trial_days' => 'integer',
            'sort' => 'integer',
        ];
    }

    public static function free(): ?self
    {
        return static::where('slug', self::FREE_SLUG)->first();
    }

    public function isFree(): bool
    {
        return $this->slug === self::FREE_SLUG;
    }

    /**
     * Renvoie la limite d'un quota. -1 (ou null) signifie illimité.
     */
    public function quota(string $key): int
    {
        $value = $this->quotas[$key] ?? -1;

        return is_numeric($value) ? (int) $value : -1;
    }

    public function isUnlimited(string $key): bool
    {
        return $this->quota($key) < 0;
    }
}
