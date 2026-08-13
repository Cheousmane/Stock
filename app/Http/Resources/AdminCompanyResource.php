<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminCompanyResource extends JsonResource
{
    public function toArray($request): array
    {
        $metadata = $this->metadata ?? [];

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
            'trial_ends_at' => $this->trial_ends_at,
            'suspended_at' => $this->suspended_at,
            'currency_code' => $this->currency_code,
            'plan' => new PlanResource($this->whenLoaded('plan')),
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