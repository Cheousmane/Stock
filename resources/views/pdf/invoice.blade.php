@extends('pdf.layout')

@section('content')
    @include('pdf.partials.customer', [
        'customerTitle' => trans('pdf.bill_to'),
        'detailsTitle' => trans('pdf.details'),
        'details' => [
            trans('pdf.issue_date') => $invoice->issue_date->format('d/m/Y'),
            trans('pdf.due_date') => $invoice->due_date->format('d/m/Y'),
        ],
    ])

    <table class="items items-accent" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th class="num">N°</th>
                <th style="width:38%">{{ trans('pdf.description') }}</th>
                <th style="width:9%;text-align:center">{{ trans('pdf.quantity') }}</th>
                <th style="width:16%;text-align:right">{{ trans('pdf.unit_price') }}</th>
                <th style="width:9%;text-align:right">{{ trans('pdf.vat') }}</th>
                <th style="width:22%;text-align:right">{{ trans('pdf.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>{{ $item->description }}</td>
                    <td style="text-align:center">{{ $item->quantity }}</td>
                    <td style="text-align:right">{{ $money::format($item->unit_price_xof, $currency) }}</td>
                    <td style="text-align:right">{{ $item->tax_rate > 0 ? $item->tax_rate . '%' : '-' }}</td>
                    <td style="text-align:right">{{ $money::format($item->total_xof, $currency) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @include('pdf.partials.totals')

    @if($total > 0)
        <div class="callout"><span class="callout-title">{{ trans('pdf.amount_in_words') }}</span><br><strong>{{ $amountInWords }}.</strong></div>
    @endif

    @include('pdf.partials.bank')

    @include('pdf.partials.payments')

    @if($invoice->notes)
        <div class="notes">
            <strong>{{ trans('pdf.notes') }}:</strong><br>
            {{ nl2br(e($invoice->notes)) }}
        </div>
    @endif

    @if($invoice->terms)
        <div class="notes">
            <strong>{{ trans('pdf.terms') }}:</strong><br>
            {{ nl2br(e($invoice->terms)) }}
        </div>
    @endif

    @include('pdf.partials.signature', ['signatureImage' => null])
@endsection
