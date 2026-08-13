<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Support\Money;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

/**
 * Shared PDF rendering: locale, currency, branding, QR verification code,
 * watermark and common layout data are resolved here so every document
 * template stays thin and consistent.
 */
final class DocumentPdfService
{
    private const ACCENT_DEFAULT = '#2563eb';

    public function render(string $template, array $data): string
    {
        $company = $data['company'] ?? TenantContext::get();
        $meta = $company->metadata ?? [];

        $locale = $meta['locale'] ?? config('app.locale', 'fr');
        $currency = $meta['currency'] ?? 'XOF';

        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        try {
            $viewData = array_merge($data, [
                'locale' => $locale,
                'currency' => $currency,
                'money' => Money::class,
                'companyMeta' => $meta,
                'accent' => $this->sanitizeColor($meta['brand_color'] ?? null, $data['accent'] ?? self::ACCENT_DEFAULT),
                'logoSrc' => $this->logoSource($company),
                'qrSrc' => $this->qrSource($data['verificationUrl'] ?? null),
                'generatedAt' => now()->format('d/m/Y \à H:i'),
            ]);

            return Pdf::loadView($template, $viewData)->output();
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    /**
     * Group item tax amounts per rate, e.g. [['rate' => '18', 'amount' => 3600], ...].
     */
    public function taxBreakdown(iterable $items): array
    {
        $grouped = [];
        foreach ($items as $item) {
            if ((float) $item->tax_rate > 0) {
                $rate = rtrim(rtrim(number_format((float) $item->tax_rate, 2), '0'), '.');
                $grouped[$rate] = ($grouped[$rate] ?? 0) + (int) $item->tax_xof;
            }
        }

        $breakdown = [];
        foreach ($grouped as $rate => $amount) {
            $breakdown[] = ['rate' => $rate, 'amount' => $amount];
        }

        return $breakdown;
    }

    private function sanitizeColor(?string $color, string $default): string
    {
        if ($color && preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return $color;
        }

        return $default;
    }

    private function logoSource(Company $company): ?string
    {
        $logo = $company->metadata['logo'] ?? null;

        return $logo ? Storage::disk('public')->path($logo) : null;
    }

    private function qrSource(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        try {
            $qr = new QrCode(data: $url, size: 200, margin: 2);

            $result = (new PngWriter)->write($qr);

            return 'data:image/png;base64,'.base64_encode($result->getString());
        } catch (\Throwable) {
            return null;
        }
    }
}
