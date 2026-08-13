<table class="info-row">
    <tr>
        <td style="width:50%">
            <div class="info-box-title">{{ $customerTitle }}</div>
            <p><strong>{{ $customer->name }}</strong></p>
            @if($customer->address)
                <p>{{ $customer->address }}</p>
            @endif
            @if($customer->city)
                <p>{{ $customer->city }}{{ $customer->country ? ', ' . $customer->country : '' }}</p>
            @endif
            @if($customer->email)
                <p>{{ $customer->email }}</p>
            @endif
            @if($customer->phone)
                <p>{{ $customer->phone }}</p>
            @endif
            @if($customer->tax_number)
                <p>{{ trans('pdf.tax_id') }}: {{ $customer->tax_number }}</p>
            @endif
        </td>
        <td style="width:50%;text-align:right;vertical-align:top">
            <div class="info-box-title" style="text-align:right">{{ $detailsTitle }}</div>
            @foreach($details as $label => $value)
                <p><strong>{{ $label }}:</strong> {{ $value }}</p>
            @endforeach
        </td>
    </tr>
</table>
