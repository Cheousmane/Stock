<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Company;

/**
 * Class TenantContext
 * Holds the current active company (tenant) in the request lifecycle.
 */
final class TenantContext
{
    private ?Company $company = null;

    /**
     * Set the current company.
     */
    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    /**
     * Get the current company.
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * Get the current company ID.
     */
    private function resolveCompanyId(): ?int
    {
        return $this->company?->id;
    }

    /**
     * Check if a company is set.
     */
    public function hasCompany(): bool
    {
        return $this->company !== null;
    }

    /**
     * Clear the current company.
     */
    private function clearCompany(): void
    {
        $this->company = null;
    }

    /**
     * Clear the current company statically.
     */
    public static function clear(): void
    {
        app(self::class)->clearCompany();
    }

    /**
     * Set the current company statically.
     */
    public static function set(Company $company): void
    {
        app(self::class)->setCompany($company);
    }

    /**
     * Get the current company statically.
     */
    public static function get(): ?Company
    {
        return app(self::class)->getCompany();
    }

    /**
     * Get the current company ID statically.
     */
    public static function getCompanyId(): ?int
    {
        return app(self::class)->resolveCompanyId();
    }

    /**
     * Check if a company is set statically.
     */
    public static function has(): bool
    {
        return app(self::class)->hasCompany();
    }
}
