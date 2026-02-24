<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromQuery, ShouldAutoSize, WithChunkReading, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private string $dateFrom,
        private string $dateTo,
        private string $status = '',
        private string $search = ''
    ) {}

    public function query()
    {
        return Attendance::query()
            ->with(['user:id,name,nim'])
            ->select(['id', 'user_id', 'date', 'check_in', 'check_out', 'work_hours', 'status', 'late_minutes', 'late_category'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->search, function ($q) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%")
                    ->orWhere('nim', 'like', "%{$this->search}%")
                );
            })
            ->orderByDesc('date')
            ->orderByDesc('check_in');
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Hari',
            'NIM',
            'Nama',
            'Check-in',
            'Check-out',
            'Jam Kerja',
            'Status',
            'Menit Terlambat',
            'Kategori Terlambat',
        ];
    }

    public function map($attendance): array
    {
        static $no = 0;
        $no++;

        $statusMap = [
            'present' => 'Hadir',
            'late' => 'Terlambat',
            'absent' => 'Tidak Hadir',
            'excused' => 'Izin',
        ];

        return [
            $no,
            $attendance->date->format('d/m/Y'),
            $attendance->date->locale('id')->dayName,
            $attendance->user?->nim ?? '-',
            $attendance->user?->name ?? '-',
            $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : '-',
            $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : '-',
            $attendance->work_hours ? number_format($attendance->work_hours, 2).' jam' : '-',
            $statusMap[$attendance->status] ?? $attendance->status,
            $attendance->late_minutes ?? 0,
            $attendance->late_category ?? '-',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ];
    }
}
