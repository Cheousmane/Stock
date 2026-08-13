@extends('pdf.layout')

@section('content')
    @include('pdf.partials.customer', [
        'customerTitle' => trans('pdf.deliver_to'),
        'detailsTitle' => trans('pdf.details'),
        'details' => [
            trans('pdf.issue_date') => $deliveryNote->issue_date->format('d/m/Y'),
            trans('pdf.delivery_date') => $deliveryNote->delivery_date?->format('d/m/Y') ?? '-',
        ],
    ])

    <table class="items">
        <thead>
            <tr>
                <th style="width:52%">{{ trans('pdf.description') }}</th>
                <th style="width:18%">{{ trans('pdf.reference') }}</th>
                <th style="width:14%;text-align:center">{{ trans('pdf.quantity') }}</th>
                <th style="width:16%;text-align:right">{{ trans('pdf.unit') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->product?->sku ?? '-' }}</td>
                    <td style="text-align:center">{{ $item->quantity }}</td>
                    <td style="text-align:right">{{ $item->product?->unit?->code ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($deliveryNote->notes)
        <div class="notes">
            <strong>{{ trans('pdf.notes') }}:</strong><br>
            {{ nl2br(e($deliveryNote->notes)) }}
        </div>
    @endif

    @if($invoiceNumber)
        <div class="notes">
            <strong>{{ trans('pdf.invoice_associated') }}:</strong> {{ $invoiceNumber }}
        </div>
    @endif

    @include('pdf.partials.signature')
@endsection