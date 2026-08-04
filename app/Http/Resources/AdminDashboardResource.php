<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminDashboardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'total_companies' => $this->total_companies,
            'active_companies' => $this->active_companies,
            'suspended_companies' => $this->suspended_companies,
            'total_users' => $this->total_users,
            'total_revenue' => $this->total_revenue,
            'paying_companies' => $this->paying_companies,
            'total_invoices' => $this->total_invoices,
            'recent_logins' => $this->recent_logins,
            'failed_logins' => $this->failed_logins,
            'logins_today' => LoginLogResource::collection($this->logins_today),
            'recent_companies' => $this->recent_companies,
            'chart_registrations' => $this->chart_registrations,
            'chart_logins' => $this->chart_logins,
        ];
    }
}
