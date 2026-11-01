<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Subscription
 */
class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $plan = $this->whenLoaded('plan');

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'plan_id' => $this->plan_id,
            'plan_slug' => $plan?->slug,
            'plan_name' => $plan?->name,
            'plan_price_xof' => $plan?->price_xof,
            'status' => $this->status,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'is_active' => $this->isActive(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
