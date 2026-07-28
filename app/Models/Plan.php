<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
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
}
