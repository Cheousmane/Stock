@extends('pdf.layout')

@section('content')
    @include('pdf.partials.customer', [
        'customerTitle' => trans('pdf.for'),
        'detailsTitle' => trans('pdf.details'),
        'details' => [
            trans('pdf.issue_date') => $quote->issue_date->format('d/m/Y'),
            trans('pdf.expiration_date') => $quote->expiration_date->format('d/m/Y'),
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

    <div class="validity">
        {{ trans('pdf.valid_until') }} {{ $quote->expiration_date->format('d/m/Y') }}.
    </div>

    @if($quote->notes)
        <div class="notes">
            <strong>{{ trans('pdf.notes') }}:</strong><br>
            {{ nl2br(e($quote->notes)) }}
        </div>
    @endif

    @if($quote->terms)
        <div class="notes">
            <strong>{{ trans('pdf.terms') }}:</strong><br>
            {{ nl2br(e($quote->terms)) }}
        </div>
    @endif

    @include('pdf.partials.signature', ['signatureImage' => null])
@endsection