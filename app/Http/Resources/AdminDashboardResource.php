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
            'active_users' => $this->active_users,
            'adoption_rate' => $this->adoption_rate,
            'total_revenue' => $this->total_revenue,
            'paying_companies' => $this->paying_companies,
            'total_invoices' => $this->total_invoices,
            'trial_companies' => $this->trial_companies,
            'trial_expired_companies' => $this->trial_expired_companies,
            'invoiced_volume_30d' => $this->invoiced_volume_30d,
            'invoices_count_30d' => $this->invoices_count_30d,
            'top_companies' => $this->top_companies,
            'alerts' => $this->alerts,
            'recent_logins' => $this->recent_logins,
            'failed_logins' => $this->failed_logins,
            'logins_today' => LoginLogResource::collection($this->logins_today),
            'recent_companies' => $this->recent_companies,
            'chart_registrations' => $this->chart_registrations,
            'chart_logins' => $this->chart_logins,
            'companies_without_plan' => $this->companies_without_plan,
            'companies_with_plan' => $this->companies_with_plan,
            'plan_distribution' => $this->plan_distribution,
            'companies_without_plan_list' => $this->companies_without_plan_list,
        ];
    }
}
