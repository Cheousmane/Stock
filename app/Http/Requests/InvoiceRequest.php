<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Support\Money;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'client',
            'issue_date' => 'date d\'émission',
            'due_date' => 'date d\'échéance',
            'items' => 'lignes',
            'items.*.product_id' => 'produit',
            'items.*.description' => 'description',
            'items.*.quantity' => 'quantité',
            'items.*.unit_price_xof' => 'prix unitaire',
        ];
    }

    public function rules(): array
    {
        $isPatch = $this->isMethod('patch');
        $companyId = TenantContext::getCompanyId();

        $rules = [
            'customer_id' => $isPatch ? ['sometimes', 'integer', Rule::exists('customers', 'id')->where('company_id', $companyId)] : ['required', 'integer', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'issue_date' => $isPatch ? ['sometimes', 'date'] : ['required', 'date'],
            'due_date' => $isPatch ? ['sometimes', 'date', 'after_or_equal:issue_date'] : ['required', 'date', 'after_or_equal:issue_date'],
            'items' => $isPatch ? ['sometimes', 'array', 'min:1'] : ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.description' => $isPatch ? ['sometimes', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'items.*.quantity' => $isPatch ? ['sometimes', 'integer', 'min:1'] : ['required', 'integer', 'min:1'],
            'items.*.unit_price_xof' => $isPatch ? ['sometimes', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'items.*.tax_rate' => ['numeric', 'min:0', 'max:100'],
            'discount_xof' => ['nullable', 'integer', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
        ];

        return $rules;
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $invoice = $this->route('invoice');
                $invoice = $invoice instanceof Invoice ? $invoice : null;

                if ($this->exists('items')) {
                    $subtotal = 0;
                    $tax = 0;

                    foreach ($this->input('items', []) as $item) {
                        $itemSubtotal = Money::multiply(
                            (int) ($item['unit_price_xof'] ?? 0),
                            (int) ($item['quantity'] ?? 1),
                        );
                        $subtotal = Money::add($subtotal, $itemSubtotal);
                        $tax = Money::add(
                            $tax,
                            Money::percent($itemSubtotal, (float) ($item['tax_rate'] ?? 0)),
                        );
                    }
                } elseif ($invoice !== null) {
                    $subtotal = (int) $invoice->subtotal_xof;
                    $tax = (int) $invoice->tax_xof;
                } else {
                    return;
                }

                $grossTotal = Money::add($subtotal, $tax);
                $discount = $this->exists('discount_xof')
                    ? (int) ($this->input('discount_xof') ?? 0)
                    : (int) ($invoice?->discount_xof ?? 0);

                if ($discount > $grossTotal) {
                    $validator->errors()->add(
                        'discount_xof',
                        'La remise ne peut pas dépasser le montant avant remise.',
                    );

                    return;
                }

                if ($invoice !== null && $grossTotal - $discount < (int) $invoice->paid_xof) {
                    $validator->errors()->add(
                        'discount_xof',
                        'Le total après remise ne peut pas être inférieur aux paiements déjà enregistrés.',
                    );
                }
            },
        ];
    }
}
