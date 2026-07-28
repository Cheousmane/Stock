<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'stripe_price_id' => $this->stripe_price_id,
            'price_xof' => $this->price_xof,
            'currency' => $this->currency,
            'trial_days' => $this->trial_days,
            'features' => $this->features,
            'quotas' => $this->quotas,
            'is_active' => $this->is_active,
            'sort' => $this->sort,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
