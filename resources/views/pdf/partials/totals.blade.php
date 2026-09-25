<table class="totals-wrap" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <table class="totals" cellpadding="0" cellspacing="0">
                <tr>
                    <td>{{ trans('pdf.subtotal') }}</td>
                    <td>{{ $money::format($subtotal, $currency) }}</td>
                </tr>
                @if(count($taxBreakdown) > 0)
                    @foreach($taxBreakdown as $tb)
                        <tr>
                            <td>{{ trans('pdf.vat') }} {{ $tb['rate'] }}%</td>
                            <td>{{ $money::format($tb['amount'], $currency) }}</td>
                        </tr>
                    @endforeach
                @elseif($taxTotal > 0)
                    <tr>
                        <td>{{ trans('pdf.total_vat') }}</td>
                        <td>{{ $money::format($taxTotal, $currency) }}</td>
                    </tr>
                @endif
                @if($discount > 0)
                    <tr>
                        <td>{{ trans('pdf.discount') }}@if($discountType) ({{ $discountType }})@endif</td>
                        <td>-{{ $money::format($discount, $currency) }}</td>
                    </tr>
                @endif
                <tr class="grand-total">
                    <td>{{ trans('pdf.total') }}</td>
                    <td>{{ $money::format($total, $currency) }}</td>
                </tr>
                @if($paid !== null && $paid > 0)
                    <tr class="paid-row">
                        <td>{{ trans('pdf.paid') }}</td>
                        <td>{{ $money::format($paid, $currency) }}</td>
                    </tr>
                    <tr class="balance-row">
                        <td>{{ trans('pdf.balance_due') }}</td>
                        <td>{{ $money::format($balanceDue, $currency) }}</td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
