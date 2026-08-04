<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\PermissionRegistrar;

/**
 * Class UserResource
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $companyId = $this->company_id;

        // Set Spatie team context so getAllPermissions() works
        app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'locale' => $this->locale ?? 'fr',
            'roles' => $this->relationLoaded('roles') ? $this->roles->pluck('name') : [],
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'is_super_admin' => $this->is_super_admin ?? false,
            'company' => $this->when($this->relationLoaded('company'), fn () => [
                'uuid' => $this->company->uuid,
                'name' => $this->company->name,
                'slug' => $this->company->slug,
            ]),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'is_online' => $this->isOnline(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
