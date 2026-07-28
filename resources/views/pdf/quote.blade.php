<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Devis {{ $quote->number }}</title>
    <style>
        @page { margin: 20mm 15mm; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #333; line-height: 1.5; margin: 0; padding: 0; }
        .header { border-bottom: 2px solid #7c3aed; padding-bottom: 15px; margin-bottom: 20px; }
        .header .company-name { font-size: 18pt; font-weight: bold; color: #7c3aed; }
        .header .doc-title { font-size: 16pt; font-weight: bold; text-align: right; color: #1e293b; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-box { width: 48%; }
        .info-box h3 { font-size: 9pt; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .info-box p { margin: 2px 0; font-size: 10pt; }
        table.items { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table.items th { background: #7c3aed; color: #fff; padding: 8px 10px; text-align: left; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.5px; }
        table.items th:last-child { text-align: right; }
        table.items td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size: 9pt; }
        table.items td:last-child { text-align: right; }
        table.items tr:nth-child(even) td { background: #f8fafc; }
        .totals { width: 100%; margin-top: 10px; }
        .totals td { padding: 5px 10px; font-size: 10pt; }
        .totals td:last-child { text-align: right; width: 150px; }
        .totals .grand-total td { font-size: 12pt; font-weight: bold; border-top: 2px solid #7c3aed; padding-top: 8px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 9pt; font-weight: bold; text-transform: uppercase; }
        .status-draft { background: #f1f5f9; color: #475569; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-accepted { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-expired { background: #fef3c7; color: #92400e; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 30px; font-size: 8pt; color: #94a3b8; text-align: center; }
        .notes { margin-top: 20px; padding-top: 15px; border-top: 1px dashed #cbd5e1; font-size: 9pt; color: #475569; }
        .validity { margin-top: 20px; padding: 10px 15px; background: #faf5ff; border-left: 3px solid #7c3aed; font-size: 9pt; color: #4c1d95; }
    </style>
</head>
<body>
    @php $logo = $company->metadata['logo'] ?? null; $logoSrc = $logo ? \Illuminate\Support\Facades\Storage::disk('public')->path($logo) : null; @endphp
    <div class="header">
        <table style="width:100%">
            <tr>
                <td style="width:50%">
                    <table style="width:100%">
                        <tr>
                            @if($logoSrc)
                                <td style="width:50px;vertical-align:middle;padding-right:10px">
                                    <img src="{{ $logoSrc }}" alt="Logo" style="max-width:50px;max-height:50px;object-fit:contain">
                                </td>
                            @endif
                            <td style="vertical-align:middle">
                                <div class="company-name">{{ $company->name }}</div>
                                <div style="font-size:9pt;color:#64748b;margin-top:4px">
                                    @if($company->metadata && isset($company->metadata['address']))
                                        {{ $company->metadata['address'] }}<br>
                                    @endif
                                    @if($company->metadata && isset($company->metadata['phone']))
                                        {{ $company->metadata['phone'] }}<br>
                                    @endif
                                    @if($company->metadata && isset($company->metadata['email']))
                                        {{ $company->metadata['email'] }}
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:50%;text-align:right">
                    <div class="doc-title">DEVIS</div>
                    <div style="font-size:10pt;color:#64748b;margin-top:4px">N° {{ $quote->number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-row">
        <div class="info-box">
            <h3>Destinataire</h3>
            <p><strong>{{ $customer->name }}</strong></p>
            @if($customer->address)
                <p>{{ $customer->address }}</p>
            @endif
            @if($customer->city)
                <p>{{ $customer->city }}{{ $customer->country ? ', ' . $customer->country : '' }}</p>
            @endif
            @if($customer->email)
                <p>{{ $customer->email }}</p>
            @endif
            @if($customer->phone)
                <p>{{ $customer->phone }}</p>
            @endif
            @if($customer->tax_number)
                <p>N° TVA: {{ $customer->tax_number }}</p>
            @endif
        </div>
        <div class="info-box" style="text-align:right">
            <h3>Détails</h3>
            <p><strong>Date d'émission:</strong> {{ $quote->issue_date->format('d/m/Y') }}</p>
            <p><strong>Date d'expiration:</strong> {{ $quote->expiration_date->format('d/m/Y') }}</p>
            <p><strong>Statut:</strong>
                <span class="status-badge status-{{ $quote->status->value }}">
                    {{ ucfirst($quote->status->value) }}
                </span>
            </p>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:45%">Description</th>
                <th style="width:10%;text-align:center">Qté</th>
                <th style="width:15%;text-align:right">Prix unitaire</th>
                <th style="width:10%;text-align:right">TVA</th>
                <th style="width:20%;text-align:right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="text-align:center">{{ $item->quantity }}</td>
                    <td style="text-align:right">{{ $money::format($item->unit_price_xof) }}</td>
                    <td style="text-align:right">{{ $item->tax_rate > 0 ? $item->tax_rate . '%' : '-' }}</td>
                    <td style="text-align:right">{{ $money::format($item->total_xof) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td style="text-align:right">Sous-total</td>
            <td>{{ $money::format($quote->subtotal_xof) }}</td>
        </tr>
        @if($quote->tax_xof > 0)
            <tr>
                <td style="text-align:right">Total TVA</td>
                <td>{{ $money::format($quote->tax_xof) }}</td>
            </tr>
        @endif
        @if($quote->discount_xof > 0)
            <tr>
                <td style="text-align:right">Remise{{ $quote->discount_type ? ' (' . $quote->discount_type . ')' : '' }}</td>
                <td>-{{ $money::format($quote->discount_xof) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td style="text-align:right">Total</td>
            <td>{{ $money::format($quote->total_xof) }}</td>
        </tr>
    </table>

    <div class="validity">
        Ce devis est valable jusqu'au {{ $quote->expiration_date->format('d/m/Y') }}.
    </div>

    @if($quote->notes)
        <div class="notes">
            <strong>Notes:</strong><br>
            {{ nl2br(e($quote->notes)) }}
        </div>
    @endif

    @if($quote->terms)
        <div class="notes">
            <strong>Conditions:</strong><br>
            {{ nl2br(e($quote->terms)) }}
        </div>
    @endif

    <div class="footer">
        {{ $company->name }} &mdash; Document généré le {{ now()->format('d/m/Y \à H:i') }}
    </div>
</body>
</html>
