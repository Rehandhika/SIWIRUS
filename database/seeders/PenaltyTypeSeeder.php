<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PenaltyType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PenaltyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $penaltyTypes = $this->getPenaltyTypes();
        $now = now();

        // Prepare data for bulk upsert
        $data = collect($penaltyTypes)->map(fn($type) => [
            'code' => $type['code'],
            'name' => $type['name'],
            'description' => $type['description'],
            'points' => $type['points'],
            'is_active' => $type['is_active'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        // Bulk upsert for performance
        DB::table('penalty_types')->upsert(
            $data,
            ['code'], // Unique constraint
            ['name', 'description', 'points', 'is_active', 'updated_at'] // Fields to update
        );

        $this->command->info('✅ ' . count($penaltyTypes) . ' penalty types berhasil di-seed!');
    }

    /**
     * Get penalty types data.
     *
     * @return array<int, array{code: string, name: string, description: string, points: int, is_active: bool}>
     */
    private function getPenaltyTypes(): array
    {
        return [
            // Unified late categories (A/B/C)
            [
                'code' => 'LATE_A',
                'name' => 'Terlambat A (10-30 menit)',
                'description' => 'Terlambat antara 10 sampai 30 menit',
                'points' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'LATE_B',
                'name' => 'Terlambat B (31-60 menit)',
                'description' => 'Terlambat antara 31 sampai 60 menit',
                'points' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'LATE_C',
                'name' => 'Terlambat C (>60 menit)',
                'description' => 'Terlambat lebih dari 60 menit',
                'points' => 15,
                'is_active' => true,
            ],
            // New integrated penalty types
            [
                'code' => 'LATE_MINOR',
                'name' => 'Terlambat Ringan',
                'description' => 'Terlambat 6-15 menit',
                'points' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'LATE_MODERATE',
                'name' => 'Terlambat Sedang',
                'description' => 'Terlambat 16-30 menit',
                'points' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'LATE_SEVERE',
                'name' => 'Terlambat Berat',
                'description' => 'Terlambat lebih dari 30 menit',
                'points' => 15,
                'is_active' => true,
            ],
            [
                'code' => 'ABSENT',
                'name' => 'Tidak Hadir',
                'description' => 'Tidak hadir tanpa izin',
                'points' => 20,
                'is_active' => true,
            ],
            [
                'code' => 'NO_CHECKOUT',
                'name' => 'Tidak Checkout',
                'description' => 'Tidak melakukan checkout',
                'points' => 5,
                'is_active' => true,
            ],
            // Legacy penalty types (kept for backward compatibility)
            [
                'code' => 'MISSED_SCHEDULE',
                'name' => 'Tidak Hadir Jadwal',
                'description' => 'Tidak hadir pada jadwal yang telah ditentukan',
                'points' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'LATE_CHECKIN',
                'name' => 'Terlambat Check-in',
                'description' => 'Check-in melebihi batas waktu yang ditentukan',
                'points' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'EARLY_CHECKOUT',
                'name' => 'Check-out Awal',
                'description' => 'Check-out sebelum waktu jadwal berakhir',
                'points' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'UNAUTHORIZED_ABSENCE',
                'name' => 'Tidak Hadir Tanpa Izin',
                'description' => 'Tidak hadir tanpa mengajukan izin terlebih dahulu',
                'points' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'SWAP_VIOLATION',
                'name' => 'Pelanggaran Tukar Jadwal',
                'description' => 'Melanggar aturan tukar jadwal yang telah disepakati',
                'points' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'EQUIPMENT_DAMAGE',
                'name' => 'Kerusakan Peralatan',
                'description' => 'Merusak peralatan atau fasilitas koperasi',
                'points' => 15,
                'is_active' => true,
            ],
            [
                'code' => 'CASH_DISCREPANCY',
                'name' => 'Selisih Kas',
                'description' => 'Terdapat selisih dalam pencatatan kas',
                'points' => 8,
                'is_active' => true,
            ],
            [
                'code' => 'BAD_BEHAVIOR',
                'name' => 'Perilaku Tidak Pantas',
                'description' => 'Perilaku yang tidak sesuai dengan norma koperasi',
                'points' => 7,
                'is_active' => true,
            ],
        ];
    }
}
