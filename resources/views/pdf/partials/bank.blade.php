@if($bankName || $bankAccount || $bankSwift)
    <div class="bank-block">
        <div class="bank-title">{{ trans('pdf.payment_details') }}</div>
        @if($bankName)
            <div><strong>{{ trans('pdf.bank_name') }} :</strong> {{ $bankName }}</div>
        @endif
        @if($bankAccount)
            <div><strong>{{ trans('pdf.bank_account') }} :</strong> {{ $bankAccount }}</div>
        @endif
        @if($bankSwift)
            <div><strong>{{ trans('pdf.bank_swift') }} :</strong> {{ $bankSwift }}</div>
        @endif
    </div>
@endif
