<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'rate' => 'decimal:6',
        ];
    }
}
