<div class="signature-box">
    <table style="width:100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:47%;vertical-align:top">
                <div class="sig-card">
                    @if($signatureImage)
                        <img src="{{ $signatureImage }}" alt="Signature">
                    @else
                        &nbsp;<br>&nbsp;
                    @endif
                </div>
                <div class="sig-label">{{ trans('pdf.signature') }}</div>
            </td>
            <td style="width:6%"></td>
            <td style="width:47%;vertical-align:top">
                <div class="sig-card">&nbsp;<br>&nbsp;</div>
                <div class="sig-label">{{ trans('pdf.signature_date') }}</div>
            </td>
        </tr>
    </table>
</div>
