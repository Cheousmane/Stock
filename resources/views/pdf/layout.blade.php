<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>{{ trans('pdf.' . $docTypeKey) }} {{ $docNumber }}</title>
    <style>
        /* ── Pro Max PDF theme (Dompdf-safe: tables, hex colors, no gradients) ── */
        /* Compact : un document léger tient sur 1 page, les gros coulent sur N pages */
        @page { margin: 11mm 11mm 18mm 11mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #1e293b; line-height: 1.45; margin: 0; padding: 0; }
        tr { page-break-inside: avoid; }
        .totals-wrap, .callout, .notes, .validity, .signature-box, .words-block { page-break-inside: avoid; }

        .accent-bar { height: 4px; background: {{ $accent }}; margin: -11mm -11mm 0 -11mm; }
        .header { margin: 8px 0 2px 0; }
        .logo-box { border: 1px solid #e2e8f0; background: #f8fafc; width: 48px; height: 48px; text-align: center; vertical-align: middle; }
        .logo-box img { max-width: 42px; max-height: 42px; }
        .company-name { font-size: 14.5pt; font-weight: bold; color: #0f172a; letter-spacing: -0.3px; }
        .company-accent { display: inline-block; width: 30px; height: 3px; background: {{ $accent }}; margin: 4px 0; }
        .muted { color: #64748b; font-size: 8pt; }
        .legal-line { margin-top: 3px; font-size: 7pt; color: #94a3b8; }

        .doc-card { border: 1px solid #e2e8f0; background: #f8fafc; padding: 8px 10px; }
        .doc-label { font-size: 7pt; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; font-weight: bold; }
        .doc-title { font-size: 17pt; font-weight: bold; color: #0f172a; letter-spacing: -0.5px; margin: 1px 0; }
        .doc-number { font-size: 8.5pt; color: #475569; }
        .doc-number strong { color: #0f172a; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; }
        .status-paid, .status-delivered, .status-accepted { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .status-sent, .status-shipped, .status-partial { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .status-draft, .status-pending { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .status-overdue, .status-expired { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .status-cancelled, .status-rejected, .status-returned { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .meta-strip { width: 100%; border-collapse: collapse; margin: 8px 0 2px 0; border-top: 2px solid {{ $accent }}; }
        .meta-strip td { padding: 5px 0; font-size: 8pt; color: #475569; }

        .info-row { width: 100%; margin: 8px 0 2px 0; }
        .info-row td { vertical-align: top; }
        .info-card { border: 1px solid #e2e8f0; background: #ffffff; padding: 0; }
        .info-card-head { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 5px 9px; font-size: 7pt; color: #64748b; text-transform: uppercase; letter-spacing: 1.2px; font-weight: bold; }
        .info-card-head.accent { border-left: 3px solid {{ $accent }}; }
        .info-card-body { padding: 6px 9px; }
        .info-card-body p { margin: 1px 0; font-size: 8.5pt; }
        .info-card-body .lead { font-size: 10pt; font-weight: bold; color: #0f172a; }

        table.items { width: 100%; border-collapse: collapse; margin: 10px 0 2px 0; border: 1px solid #e2e8f0; }
        table.items thead { display: table-header-group; }
        table.items th { background: #0f172a; color: #ffffff; padding: 6px 8px; text-align: left; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.8px; }
        table.items th.num, table.items td.num { width: 24px; text-align: center; color: #94a3b8; }
        table.items th:last-child { text-align: right; }
        table.items td { padding: 5px 8px; border-bottom: 1px solid #eef2f7; font-size: 8.5pt; vertical-align: top; }
        table.items td:last-child { text-align: right; font-weight: bold; white-space: nowrap; }
        table.items tr:nth-child(even) td { background: #f8fafc; }
        table.items tr:last-child td { border-bottom: none; }
        .items-accent { border-top: 3px solid {{ $accent }}; }

        .totals-wrap { width: 100%; margin-top: 8px; }
        .totals { width: 48%; margin-left: auto; border: 1px solid #e2e8f0; border-collapse: collapse; }
        .totals td { padding: 4px 9px; font-size: 8.5pt; border-bottom: 1px solid #eef2f7; }
        .totals td:last-child { text-align: right; white-space: nowrap; font-weight: bold; }
        .totals tr:last-child td { border-bottom: none; }
        .totals .grand-total td { background: {{ $accent }}; color: #ffffff; font-size: 10.5pt; font-weight: bold; padding: 6px 9px; }
        .totals .paid-row td { color: #166534; background: #f0fdf4; }
        .totals .balance-row td { background: #0f172a; color: #ffffff; font-size: 10pt; font-weight: bold; padding: 6px 9px; }

        .callout { margin-top: 10px; padding: 7px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid {{ $accent }}; font-size: 8.5pt; color: #334155; }
        .callout-title { font-size: 7pt; text-transform: uppercase; letter-spacing: 1.2px; color: #64748b; font-weight: bold; margin-bottom: 3px; }
        .words-block { margin-top: 8px; font-size: 8.5pt; color: #475569; font-style: italic; }
        .words-block strong { color: #0f172a; font-style: normal; }
        .validity { margin-top: 10px; padding: 7px 10px; background: #f0fdf4; border: 1px solid #86efac; border-left: 3px solid #16a34a; font-size: 8.5pt; color: #14532d; }
        .notes { margin-top: 10px; padding: 7px 10px; background: #ffffff; border: 1px dashed #cbd5e1; font-size: 8.5pt; color: #475569; }
        .notes strong { color: #0f172a; }

        .signature-box { margin-top: 18px; width: 100%; }
        .sig-card { border: 1px solid #e2e8f0; background: #f8fafc; height: 58px; text-align: center; vertical-align: bottom; color: #cbd5e1; font-size: 7pt; }
        .sig-card img { max-height: 52px; }
        .sig-label { font-size: 7.5pt; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; padding-top: 4px; text-align: center; }

        .footer { position: fixed; bottom: 5mm; left: 0; right: 0; width: 100%; border-top: 2px solid {{ $accent }}; padding-top: 4px; font-size: 7pt; color: #94a3b8; }
        .pagenum:after { content: counter(page) " / " counter(pages); }
        .qr-box { text-align: right; margin-top: 6px; }
        .qr-box img { width: 18mm; height: 18mm; border: 1px solid #e2e8f0; padding: 2px; background: #ffffff; }
        .qr-hint { font-size: 6pt; color: #94a3b8; margin-top: 2px; }
        .watermark { position: fixed; top: 44%; left: 16%; width: 68%; text-align: center; transform: rotate(-30deg); font-size: 30pt; font-weight: bold; letter-spacing: 5px; opacity: 0.1; border: 3px solid currentColor; border-radius: 8px; padding: 8px 0; }
    </style>
</head>
<body>
    <div class="accent-bar"></div>

    @isset($watermark)
        <div class="watermark" style="color: {{ $watermarkColor ?? '#64748b' }}">{{ $watermark }}</div>
    @endisset

    <div class="header">
        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="width:58%;vertical-align:top">
                    <table style="border-collapse:collapse">
                        <tr>
                            @if($logoSrc)
                                <td style="vertical-align:top;padding-right:10px">
                                    <div class="logo-box">
                                        <img src="{{ $logoSrc }}" alt="Logo">
                                    </div>
                                </td>
                            @endif
                            <td style="vertical-align:top">
                                <div class="company-name">{{ $company->name }}</div>
                                <div class="company-accent"></div>
                                <div class="muted">
                                    @if(!empty($companyMeta['address']))
                                        {{ $companyMeta['address'] }}<br>
                                    @endif
                                    @if(!empty($companyMeta['phone']))
                                        {{ $companyMeta['phone'] }}<br>
                                    @endif
                                    @if(!empty($companyMeta['email']))
                                        {{ $companyMeta['email'] }}
                                    @endif
                                </div>
                                @php
                                    $legal = [];
                                    if (!empty($companyMeta['legal_rc'])) $legal[] = trans('pdf.legal_registration') . ' : ' . $companyMeta['legal_rc'];
                                    if (!empty($companyMeta['legal_ifu'])) $legal[] = trans('pdf.legal_ifu') . ' : ' . $companyMeta['legal_ifu'];
                                    if (!empty($companyMeta['legal_rccm'])) $legal[] = trans('pdf.legal_rccm') . ' : ' . $companyMeta['legal_rccm'];
                                @endphp
                                @if($legal)
                                    <div class="legal-line">{{ implode('  |  ', $legal) }}</div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:42%;text-align:right;vertical-align:top">
                    <div class="doc-card">
                        <div class="doc-title">{{ trans('pdf.' . $docTypeKey) }}</div>
                        <div class="doc-number">{{ trans('pdf.number') }} <strong>{{ $docNumber }}</strong></div>
                        <div style="margin-top:5px">
                            <span class="status-badge status-{{ $statusValue }}">{{ trans('pdf.status_' . $statusValue) }}</span>
                        </div>
                    </div>
                    @if($qrSrc)
                        <div class="qr-box">
                            <img src="{{ $qrSrc }}" alt="QR">
                            <div class="qr-hint">{{ trans('pdf.verification_hint') }}</div>
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @yield('content')

    <div class="footer">
        <table style="width:100%">
            <tr>
                <td style="width:65%">{{ $company->name }} &mdash; {{ trans('pdf.generated_at') }} {{ $generatedAt }}</td>
                <td style="width:35%;text-align:right">{{ trans('pdf.page') }} <span class="pagenum"></span></td>
            </tr>
        </table>
    </div>
</body>
</html>
