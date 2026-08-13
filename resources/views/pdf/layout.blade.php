<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <title>{{ trans('pdf.' . $docTypeKey) }} {{ $docNumber }}</title>
    <style>
        @page { margin: 16mm 13mm 24mm 13mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #334155; line-height: 1.5; margin: 0; padding: 0; }
        .header { border-bottom: 2.5px solid {{ $accent }}; padding-bottom: 12px; margin-bottom: 18px; }
        .company-name { font-size: 17pt; font-weight: bold; color: {{ $accent }}; }
        .doc-title { font-size: 16pt; font-weight: bold; text-align: right; color: #1e293b; }
        .muted { color: #64748b; font-size: 8.5pt; }
        .info-row { width: 100%; margin-bottom: 16px; }
        .info-row td { vertical-align: top; }
        .info-box-title { font-size: 8.5pt; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; font-weight: bold; }
        .info-row p { margin: 1.5px 0; font-size: 9.5pt; }
        .legal-line { margin-top: 4px; font-size: 8pt; }
        table.items { width: 100%; border-collapse: collapse; margin: 14px 0; }
        table.items th { background: {{ $accent }}; color: #fff; padding: 7px 9px; text-align: left; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.5px; }
        table.items th:last-child { text-align: right; }
        table.items td { padding: 7px 9px; border-bottom: 1px solid #e2e8f0; font-size: 9pt; }
        table.items td:last-child { text-align: right; }
        table.items tr:nth-child(even) td { background: #f8fafc; }
        .totals { width: 46%; margin-left: auto; margin-top: 12px; }
        .totals td { padding: 4px 8px; font-size: 9.5pt; }
        .totals td:last-child { text-align: right; white-space: nowrap; }
        .totals .grand-total td { font-size: 11.5pt; font-weight: bold; border-top: 2px solid {{ $accent }}; padding-top: 7px; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
        .status-paid, .status-delivered, .status-accepted { background: #dcfce7; color: #166534; }
        .status-sent, .status-shipped, .status-partial { background: #dbeafe; color: #1e40af; }
        .status-draft, .status-pending { background: #f1f5f9; color: #475569; }
        .status-overdue, .status-expired { background: #fef3c7; color: #92400e; }
        .status-cancelled, .status-rejected, .status-returned { background: #fee2e2; color: #991b1b; }
        .notes { margin-top: 16px; padding-top: 10px; border-top: 1px dashed #cbd5e1; font-size: 9pt; color: #475569; }
        .footer { position: fixed; bottom: 8mm; left: 0; right: 0; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 5px; font-size: 7.5pt; color: #94a3b8; }
        .pagenum:after { content: counter(page) " / " counter(pages); }
        .qr-box { text-align: right; margin-top: 8px; }
        .qr-box img { width: 22mm; height: 22mm; }
        .qr-hint { font-size: 6.5pt; color: #94a3b8; margin-top: 2px; }
        .watermark { position: fixed; top: 45%; left: 18%; width: 64%; text-align: center; transform: rotate(-30deg); font-size: 30pt; font-weight: bold; letter-spacing: 5px; opacity: 0.13; border: 3px solid currentColor; border-radius: 6px; padding: 8px 0; }
        .bank-block { margin-top: 14px; padding: 10px 12px; background: #f8fafc; border-left: 3px solid {{ $accent }}; font-size: 9pt; }
        .bank-title { font-size: 8pt; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: bold; margin-bottom: 4px; }
        .words-block { margin-top: 10px; font-size: 9pt; color: #475569; font-style: italic; }
        .words-block strong { color: #1e293b; }
        .signature-box { margin-top: 28px; width: 100%; }
        .sig-line { border-top: 1px solid #94a3b8; margin-top: 42px; padding-top: 5px; font-size: 8.5pt; color: #64748b; }
        .signature-box img { max-height: 90px; }
        .validity { margin-top: 14px; padding: 8px 12px; background: {{ $accent }}1a; border-left: 3px solid {{ $accent }}; font-size: 9pt; color: #1e293b; }
    </style>
</head>
<body>
    @isset($watermark)
        <div class="watermark" style="color: {{ $watermarkColor ?? '#64748b' }}">{{ $watermark }}</div>
    @endisset

    <div class="header">
        <table style="width:100%">
            <tr>
                <td style="width:60%;vertical-align:top">
                    <table style="width:100%">
                        <tr>
                            @if($logoSrc)
                                <td style="width:52px;vertical-align:middle;padding-right:10px">
                                    <img src="{{ $logoSrc }}" alt="Logo" style="max-width:52px;max-height:52px;object-fit:contain">
                                </td>
                            @endif
                            <td style="vertical-align:middle">
                                <div class="company-name">{{ $company->name }}</div>
                                <div class="muted" style="margin-top:3px">
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
                                    <div class="muted legal-line">{{ implode('  |  ', $legal) }}</div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:40%;text-align:right;vertical-align:top">
                    <div class="doc-title">{{ trans('pdf.' . $docTypeKey) }}</div>
                    <div class="muted" style="margin-top:3px">{{ trans('pdf.number') }} {{ $docNumber }}</div>
                    <div style="margin-top:6px">
                        <span class="status-badge status-{{ $statusValue }}">{{ trans('pdf.status_' . $statusValue) }}</span>
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
        <table>
            <tr>
                <td style="width:60%">{{ $company->name }} &mdash; {{ trans('pdf.generated_at') }} {{ $generatedAt }}</td>
                <td style="width:40%;text-align:right">{{ trans('pdf.page') }} <span class="pagenum"></span></td>
            </tr>
        </table>
    </div>
</body>
</html>
