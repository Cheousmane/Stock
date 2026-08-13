<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdminActivityLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Événement',
            'Journal',
            'Utilisateur',
            'Email',
            'Entreprise',
            'Description',
            'Sujet',
            'Properties',
        ];
    }

    public function map($log): array
    {
        return [
            $log->created_at?->format('Y-m-d H:i:s'),
            $log->event ?? '',
            $log->log_name ?? '',
            $log->causer?->name ?? 'Système',
            $log->causer?->email ?? '',
            $log->causer?->company?->name ?? '',
            $log->description,
            $log->subject ? ($log->subject_type ? class_basename($log->subject_type) . ' #' . $log->subject_id : '') : '',
            $log->properties ? json_encode($log->properties) : '',
        ];
    }
}