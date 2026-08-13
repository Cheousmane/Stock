@if(count($payments) > 0)
    <div class="notes">
        <strong>{{ trans('pdf.payments') }}</strong>
        <table class="items" style="margin-top:6px">
            <thead>
                <tr>
                    <th style="width:18%">{{ trans('pdf.payment_date') }}</th>
                    <th style="width:30%">{{ trans('pdf.payment_method') }}</th>
                    <th style="width:32%">{{ trans('pdf.payment_reference') }}</th>
                    <th style="width:20%;text-align:right">{{ trans('pdf.payment_amount') }}</th>
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
