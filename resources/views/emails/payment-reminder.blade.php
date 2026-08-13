<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relance de paiement</title>
    <style>
        body { font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f4f5f7; color: #17171b; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgb(0 0 0 / 0.08); }
        .header { padding: 24px 32px; background: linear-gradient(135deg, #059669, #10b981); color: #ffffff; }
        .header h1 { font-size: 18px; margin: 0 0 4px; }
        .header p { margin: 0; font-size: 13px; opacity: 0.9; }
        .body { padding: 32px; }
        .amount { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 16px 20px; margin: 20px 0; }
        .amount p { margin: 0; font-size: 13px; color: #047857; }
        .amount strong { font-size: 24px; color: #047857; display: block; margin-top: 4px; }
        table.summary { width: 100%; border-collapse: collapse; font-size: 14px; margin: 20px 0; }
        table.summary td { padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        table.summary td:last-child { text-align: right; font-weight: 600; }
        .button { display: inline-block; margin-top: 24px; padding: 12px 24px; background: #059669; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 600; }
        .footer { padding: 20px 32px; font-size: 12px; color: #6b7280; border-top: 1px solid #f0f0f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $company?->name ?? config('app.name') }}</h1>
            <p>Relance de paiement — Facture {{ $invoice->number }}</p>
        </div>
        <div class="body">
            <p>Bonjour {{ $customer?->name ?? 'cher client' }},</p>
            <p>Votre facture <strong>{{ $invoice->number }}</strong> émise le {{ $invoice->issue_date?->format('d/m/Y') }} reste impayée. Nous vous invitons à procéder au règlement.</p>

            <div class="amount">
                <p>Montant restant dû</p>
                <strong>{{ number_format($dueAmount / 100, 0, ',', ' ') }} XOF</strong>
            </div>

            <table class="summary">
                <tr>
                    <td>Facture</td>
                    <td>{{ $invoice->number }}</td>
                </tr>
                <tr>
                    <td>Date d'échéance</td>
                    <td>{{ $invoice->due_date?->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Statut</td>
                    <td>{{ $invoice->status->value }}</td>
                </tr>
            </table>

            <a class="button" href="{{ $invoiceUrl }}">Consulter la facture</a>

            <p style="margin-top: 24px; font-size: 13px; color: #6b7280;">
                Si le paiement a déjà été effectué, merci d'ignorer ce message.
            </p>
        </div>
        <div class="footer">
            {{ $company?->name ?? config('app.name') }} — © {{ date('Y') }}
        </div>
    </div>
</body>
</html>