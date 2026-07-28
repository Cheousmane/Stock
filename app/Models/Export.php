<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Export extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'status',
        'file_path',
        'file_name',
        'file_size',
        'error_message',
        'exportable_type',
        'exportable_id',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exportable(): MorphTo
    {
        return $this->morphTo();
    }
}
