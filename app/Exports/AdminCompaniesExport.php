<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdminCompaniesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $companies)
    {
    }

    public function collection(): Collection
    {
        return $this->companies;
    }

    public function headings(): array
    {
        return [
            'Nom',
            'Slug',
            'Statut',
            'Taille',
            'Secteur',
            'Téléphone',
            'Email',
            'Plan',
            'Prix plan',
            'Utilisateurs',
            'Inscriptions',
            'Créé le',
        ];
    }

    public function map($company): array
    {
        $metadata = $company->metadata ?? [];

        return [
            $company->name,
            $company->slug,
            $company->status,
            $company->size ?? '',
            $company->industry ?? '',
            $metadata['phone'] ?? '',
            $metadata['email'] ?? '',
            $company->plan?->name ?? 'Aucun',
            $company->plan?->price_xof ?? 0,
            $company->users_count ?? 0,
            $company->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}