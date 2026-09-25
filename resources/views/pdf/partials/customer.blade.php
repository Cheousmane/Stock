<table class="info-row" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:49%;vertical-align:top">
            <div class="info-card">
                <div class="info-card-head accent">{{ $customerTitle }}</div>
                <div class="info-card-body">
                    <p class="lead">{{ $customer->name }}</p>
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
                        <p class="muted">{{ trans('pdf.tax_id') }}: {{ $customer->tax_number }}</p>
                    @endif
                </div>
            </div>
        </td>
        <td style="width:2%"></td>
        <td style="width:49%;vertical-align:top">
            <div class="info-card">
                <div class="info-card-head">{{ $detailsTitle }}</div>
                <div class="info-card-body">
                    @foreach($details as $label => $value)
                        <p><span class="muted">{{ $label }}</span><br><strong>{{ $value }}</strong></p>
                    @endforeach
                </div>
            </div>
        </td>
    </tr>
</table>
