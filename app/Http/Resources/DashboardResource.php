<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'total_revenue_xof' => $this->total_revenue_xof,
            'outstanding' => $this->outstanding,
            'recent_payments' => $this->recent_payments,
            'total_expenses' => $this->total_expenses,
            'recent_expenses' => $this->recent_expenses,
            'capital' => $this->capital,
            'total_invoices' => $this->total_invoices,
            'total_customers' => $this->total_customers,
            'total_products' => $this->total_products,
            'low_stock_products' => $this->low_stock_products,
            'total_credit_notes' => $this->total_credit_notes,
            'draft_credit_notes' => $this->draft_credit_notes,
            'credit_notes_amount' => $this->credit_notes_amount,
            'recent_invoices' => InvoiceResource::collection($this->recent_invoices),
            'revenue_by_month' => $this->revenue_by_month,
            'top_products' => $this->top_products,
        ];
    }
}
