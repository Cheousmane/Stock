<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans('pdf.verify') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; background: #f1f5f9; color: #334155; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(2, 6, 23, .08); max-width: 480px; width: 100%; padding: 32px; text-align: center; }
        .badge { display: inline-block; padding: 6px 16px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; background: #dcfce7; color: #166534; }
        .title { font-size: 20px; font-weight: 700; margin: 16px 0 4px; color: #0f172a; }
        .muted { color: #64748b; font-size: 14px; }
        .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .row:last-child { border-bottom: 0; }
        .row strong { color: #0f172a; }
        .total { font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 8px; }
        .hint { margin-top: 20px; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 13px; color: #475569; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">{{ trans('pdf.verified') }}</span>
        <h1 class="title">{{ trans('pdf.verified_message', ['company' => $document->company->name]) }}</h1>
        <p class="muted">{{ trans('pdf.document_number') }}</p>
        <p style="font-size:18px;font-weight:700;margin:4px 0 16px">{{ $document->number }}</p>
        <div>
            <div class="row">
                <span class="muted">{{ trans('pdf.document_type') }}</span>
                <strong>{{ trans('pdf.' . $docTypeKey) }}</strong>
            </div>
            <div class="row">
                <span class="muted">{{ trans('pdf.issue_date') }}</span>
                <strong>{{ $document->issue_date->format('d/m/Y') }}</strong>
            </div>
            <div class="row">
                <span class="muted">{{ trans('pdf.status') }}</span>
                <strong>{{ trans('pdf.status_' . $document->status->value) }}</strong>
            </div>
            @if(isset($document->total_xof))
                <div class="row">
                    <span class="muted">{{ trans('pdf.total') }}</span>
                    <strong class="total">{{ \App\Support\Money::format($document->total_xof, $document->company->metadata['currency'] ?? 'XOF') }}</strong>
                </div>
            @endif
        </div>
        <div class="hint">{{ $document->company->name }} &mdash; SIDIBE CORPORATE</div>
    </div>
</body>
</html>
