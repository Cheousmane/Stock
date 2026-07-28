<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'string',
        ];
    }
}
