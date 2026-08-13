<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdminLoginLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $logs)
    {
    }

    public function collection(): Collection
    {
        return $this->logs;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Succès',
            'Email',
            'Entreprise',
            'IP',
            'Navigateur',
            'Méthode',
        ];
    }

    public function map($log): array
    {
        return [
            $log->created_at?->format('Y-m-d H:i:s'),
            $log->success ? 'Oui' : 'Non',
            $log->email,
            $log->company?->name ?? '',
            $log->ip_address ?? '',
            $log->user_agent ?? '',
            $log->method ?? '',
        ];
    }
}