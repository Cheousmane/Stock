<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de livraison {{ $deliveryNote->number }}</title>
    <style>
        @page { margin: 20mm 15mm; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #333; line-height: 1.5; margin: 0; padding: 0; }
        .header { border-bottom: 2px solid #059669; padding-bottom: 15px; margin-bottom: 20px; }
        .header .company-name { font-size: 18pt; font-weight: bold; color: #059669; }
        .header .doc-title { font-size: 16pt; font-weight: bold; text-align: right; color: #1e293b; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-box { width: 48%; }
        .info-box h3 { font-size: 9pt; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .info-box p { margin: 2px 0; font-size: 10pt; }
        table.items { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table.items th { background: #059669; color: #fff; padding: 8px 10px; text-align: left; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.5px; }
        table.items th:last-child { text-align: right; }
        table.items td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size: 9pt; }
        table.items td:last-child { text-align: right; }
        table.items tr:nth-child(even) td { background: #f8fafc; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 9pt; font-weight: bold; text-transform: uppercase; }
        .status-pending { background: #f1f5f9; color: #475569; }
        .status-shipped { background: #dbeafe; color: #1e40af; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-returned { background: #fee2e2; color: #991b1b; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 30px; font-size: 8pt; color: #94a3b8; text-align: center; }
        .notes { margin-top: 20px; padding-top: 15px; border-top: 1px dashed #cbd5e1; font-size: 9pt; color: #475569; }
        .signature-box { margin-top: 30px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 4px; text-align: center; }
        .signature-box img { max-height: 80px; }
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
                    <div class="doc-title">BON DE LIVRAISON</div>
                    <div style="font-size:10pt;color:#64748b;margin-top:4px">N° {{ $deliveryNote->number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-row">
        <div class="info-box">
            <h3>Livré à</h3>
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
        </div>
        <div class="info-box" style="text-align:right">
            <h3>Détails</h3>
            <p><strong>Date d'émission:</strong> {{ $deliveryNote->issue_date->format('d/m/Y') }}</p>
            @if($deliveryNote->delivery_date)
                <p><strong>Date de livraison:</strong> {{ $deliveryNote->delivery_date->format('d/m/Y') }}</p>
            @endif
            <p><strong>Statut:</strong>
                <span class="status-badge status-{{ $deliveryNote->status->value }}">
                    {{ ucfirst($deliveryNote->status->value) }}
                </span>
            </p>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:60%">Description</th>
                <th style="width:20%;text-align:center">Qté</th>
                <th style="width:20%;text-align:right">Unité</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="text-align:center">{{ $item->quantity }}</td>
                    <td style="text-align:right">{{ $item->product?->unit?->code ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($deliveryNote->notes)
        <div class="notes">
            <strong>Notes:</strong><br>
            {{ nl2br(e($deliveryNote->notes)) }}
        </div>
    @endif

    @if($deliveryNote->signature)
        <div class="signature-box">
            <strong>Signature du destinataire:</strong><br>
            @if(str_starts_with($deliveryNote->signature, 'data:image'))
                <img src="{{ $deliveryNote->signature }}" alt="Signature">
            @else
                <p>{{ $deliveryNote->signature }}</p>
            @endif
        </div>
    @endif

    @if($deliveryNote->invoice)
        <div class="notes">
            <strong>Facture associée:</strong> {{ $deliveryNote->invoice->number }}
        </div>
    @endif

    <div class="footer">
        {{ $company->name }} &mdash; Document généré le {{ now()->format('d/m/Y \à H:i') }}
    </div>
</body>
</html>
