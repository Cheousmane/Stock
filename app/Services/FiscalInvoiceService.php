<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FiscalRegime;
use App\Models\Company;
use App\Models\Invoice;
use App\Support\NumberToWords;
use App\Support\TenantContext;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

final class FiscalInvoiceService
{
    public function generateFiscalData(Invoice $invoice): array
    {
        $company = $invoice->company;
        $meta = $company->metadata ?? [];
        $currency = $meta['currency'] ?? 'XOF';
        $fiscalRegime = $invoice->fiscal_regime ?? FiscalRegime::Standard;

        $data = [
            'fiscal_regime' => $fiscalRegime->value,
            'fiscal_regime_label' => $fiscalRegime->label(),
            'fiscal_prefix' => $fiscalRegime->prefix(),
            'tax_id_number' => $invoice->tax_id_number ?? ($company->metadata['tax_id'] ?? ''),
            'fiscal_reference' => $invoice->fiscal_reference ?? $this->generateFiscalReference($invoice),
            'is_fiscal' => $invoice->is_fiscal ?? false,
            'fiscal_issue_date' => $invoice->fiscal_issue_date ?? now(),
            'currency' => $currency,
            'amount_in_words' => NumberToWords::amountToWords($invoice->total_xof, $currency),
        ];

        // Generate fiscal QR code if regime requires it
        if ($fiscalRegime->requiresFiscalValidation()) {
            $data['fiscal_qr_code'] = $this->generateFiscalQRCode($invoice, $fiscalRegime);
        }

        // Add fiscal mentions based on regime
        $data['fiscal_mentions'] = $this->getFiscalMentions($invoice, $fiscalRegime);

        return $data;
    }

    private function generateFiscalReference(Invoice $invoice): string
    {
        $prefix = $invoice->fiscal_regime->prefix() ?? 'FAC';
        $dateCode = $invoice->issue_date?->format('ymd') ?? date('ymd');
        $random = substr(bin2hex(random_bytes(3)), 0, 4);
        return "{$prefix}-{$dateCode}-{$invoice->id}-{$random}";
    }

    private function generateFiscalQRCode(Invoice $invoice, FiscalRegime $regime): ?string
    {
        $verificationUrl = url('/verify/invoice/' . $invoice->uuid);
        
        try {
            $qr = new QrCode(data: $verificationUrl, size: 300, margin: 4);
            $writer = new PngWriter();
            $result = $writer->write($qr);
            
            return 'data:image/png;base64,'.base64_encode($result->getString());
        } catch (\Throwable) {
            return null;
        }
    }

    private function getFiscalMentions(Invoice $invoice, FiscalRegime $regime): array
    {
        $mentions = [];

        // Base mentions applicable to all fiscal regimes
        $mentions[] = 'Document fiscal conformément aux dispositions en vigueur';
        $mentions[] = 'TVA ' . ($invoice->tax_xof > 0 ? 'non applicable' : 'exonérée');

        // Regime-specific mentions
        switch ($regime) {
            case FiscalRegime::EMECeF:
                $mentions[] = 'Format e-MECeF - République du Bénin';
                $mentions[] = 'Ce document est établis conformément à la loi n°2018-03 du 23 février 2018';
                break;
            case FiscalRegime::FNC:
                $mentions[] = 'Format FNC - République de Côte d\'Ivoire';
                $mentions[] = 'Taxe sur la Valeur Ajoutée (TVA) : ' . ($invoice->tax_xof > 0 ? 'Non assujetti' : 'Exonérée');
                break;
            case FiscalRegime::OHADA:
                $mentions[] = 'Régime OHADA - Plan Comptable OHADA';
                break;
        }

        return $mentions;
    }

    public function enrichPdfData(array $pdfData, Invoice $invoice): array
    {
        $fiscalData = $this->generateFiscalData($invoice);

        return array_merge($pdfData, $fiscalData);
    }
}