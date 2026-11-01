<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Company
 */
class AdminCompanyResource extends JsonResource
{
    public function toArray($request): array
    {
        $metadata = $this->metadata ?? [];

        $subscription = $this->subscription ?? null;
        if ($subscription === null && $this->relationLoaded('activeSubscription') && $this->activeSubscription) {
            $active = $this->activeSubscription;
            $subscription = [
                'id' => $active->id,
                'status' => $active->status,
                'plan_id' => $active->plan_id,
                'plan_name' => $active->plan?->name,
                'starts_at' => $active->starts_at,
                'ends_at' => $active->ends_at,
                'notes' => $active->notes,
                'created_at' => $active->created_at,
            ];
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $metadata['email'] ?? $this->owner?->email,
            'phone' => $metadata['phone'] ?? $this->phone,
            'address' => $metadata['address'] ?? $this->address,
            'currency' => $metadata['currency'] ?? $this->currency_code,
            'status' => $this->status,
            'size' => $this->size,
            'industry' => $this->industry,
            'owner' => $this->owner ? [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ] : null,
            'trial_started_at' => $this->trial_started_at,
            'trial_ends_at' => $this->trial_ends_at,
            'trial_active' => $this->hasActiveTrial(),
            'suspended_at' => $this->suspended_at,
            'currency_code' => $this->currency_code,
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'subscription' => $subscription,
            'quota_snapshot' => $this->quota_snapshot ?? null,
            'over_quota' => $this->quota_snapshot['over_quota'] ?? false,
            'frozen_total' => $this->quota_snapshot['frozen_total'] ?? null,
            'frozen_records' => $this->frozen_records ?? null,
            'users' => UserResource::collection($this->whenLoaded('users')),
            'users_count' => $this->whenCounted('users'),
            'invoices_count' => $this->whenCounted('invoices'),
            'customers_count' => $this->whenCounted('customers'),
            'products_count' => $this->whenCounted('products'),
            'last_login' => $this->last_login,
            'metadata' => $metadata,
            'invoiced_volume_30d' => $this->invoiced_volume_30d ?? null,
            'invoices_count_30d' => $this->invoices_count_30d ?? null,
            'timeline' => $this->timeline ?? [],
            'suspended_until' => $metadata['suspended_until'] ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
