<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoginLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'email' => $this->email,
            'success' => $this->success,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'method' => $this->method,
            'user' => new UserResource($this->whenLoaded('user')),
            'company' => new AdminCompanyResource($this->whenLoaded('company')),
            'login_at' => $this->login_at,
            'created_at' => $this->created_at,
        ];
    }
}
