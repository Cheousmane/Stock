@if(count($payments) > 0)
    <div class="callout" style="border-left-color:#16a34a">
        <div class="callout-title">{{ trans('pdf.payments') }}</div>
        <table class="items items-accent" style="margin-top:8px" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:18%">{{ trans('pdf.payment_date') }}</th>
                    <th style="width:30%">{{ trans('pdf.payment_method') }}</th>
                    <th style="width:30%">{{ trans('pdf.payment_reference') }}</th>
                    <th style="width:22%;text-align:right">{{ trans('pdf.payment_amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date?->format('d/m/Y') }}</td>
                        <td>
                            @php $methodKey = 'pdf.method_' . $payment->method; @endphp
                            {{ \Illuminate\Support\Facades\Lang::has($methodKey) ? trans($methodKey) : ucfirst((string) $payment->method) }}
                        </td>
                        <td>{{ $payment->reference ?: '-' }}</td>
                        <td>{{ $money::format($payment->amount_xof, $currency) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
