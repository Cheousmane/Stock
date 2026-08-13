<div class="signature-box">
    <table style="width:100%">
        <tr>
            <td style="width:42%;vertical-align:top">
                @if($signatureImage)
                    <img src="{{ $signatureImage }}" alt="Signature"><br>
                @endif
                <div class="sig-line">{{ trans('pdf.signature') }}</div>
            </td>
            <td style="width:16%"></td>
            <td style="width:42%;vertical-align:top">
                <div class="sig-line">{{ trans('pdf.signature_date') }}</div>
            </td>
        </tr>
    </table>
</div>
