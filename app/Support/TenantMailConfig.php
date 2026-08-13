<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Company;
use App\Models\Setting;

final class TenantMailConfig
{
    /**
     * Applique la configuration SMTP du tenant à la runtime config.
     * Utilisée par les commandes schedulées multi-tenant et les mailables.
     */
    public static function apply(Company $company): void
    {
        $settings = Setting::where('company_id', $company->id)->pluck('value', 'key')->toArray();

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings['mail_host'] ?? env('MAIL_HOST', ''),
            'mail.mailers.smtp.port' => $settings['mail_port'] ?? env('MAIL_PORT', '587'),
            'mail.mailers.smtp.username' => $settings['mail_username'] ?? env('MAIL_USERNAME', ''),
            'mail.mailers.smtp.password' => $settings['mail_password'] ?? env('MAIL_PASSWORD', ''),
            'mail.mailers.smtp.encryption' => $settings['mail_encryption'] ?? env('MAIL_ENCRYPTION', 'tls'),
            'mail.from.address' => $settings['mail_from_address'] ?? env('MAIL_FROM_ADDRESS', ''),
            'mail.from.name' => $settings['mail_from_name'] ?? $company->name,
        ]);
    }
}