<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'causer_type' => $this->causer_type,
            'causer_id' => $this->causer_id,
            'causer_name' => $this->causer?->name,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'subject_description' => $this->subject?->name ?? $this->subject?->reference ?? '#'.$this->subject_id,
            'properties' => $this->properties,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
