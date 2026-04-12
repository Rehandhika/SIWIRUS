<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PenaltiesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Collection $penalties;

    public function __construct(Collection $penalties)
    {
        $this->penalties = $penalties;
    }

    public function collection()
    {
        return $this->penalties;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama',
            'NIM',
            'Jenis Penalti',
            'Kode',
            'Poin',
            'Deskripsi',
            'Status',
            'Alasan Banding',
            'Direview Oleh',
            'Catatan Review',
        ];
    }

    public function map($penalty): array
    {
        $statusLabel = match($penalty->status) {
            'active' => 'Aktif',
            'appealed' => 'Banding',
            'dismissed' => 'Dibatalkan',
            'expired' => 'Kadaluarsa',
            default => $penalty->status
        };

        return [
            $penalty->date->format('d/m/Y'),
            $penalty->user->name ?? '-',
            $penalty->user->nim ?? '-',
            $penalty->penaltyType->name ?? '-',
            $penalty->penaltyType->code ?? '-',
            $penalty->points,
            $penalty->description ?? '-',
            $statusLabel,
            $penalty->appeal_reason ?? '-',
            $penalty->reviewer->name ?? '-',
            $penalty->review_notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Laporan Penalti';
    }
}
