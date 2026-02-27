<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Wirus Angkatan 66 - Data Anggota
     *
     * Struktur Organisasi:
     * - Ketua: Pimpinan tertinggi
     * - Wakil Ketua: Pendamping ketua
     * - Sekretaris I & II: Administrasi
     * - Bendahara Umum: Keuangan utama
     * - Bendahara Kegiatan: Keuangan kegiatan
     * - Bendahara Toko: Keuangan toko
     * - Koordinator Divisi: Kepala divisi masing-masing
     * - Anggota: Member biasa
     */
    public function run(): void
    {
        $csvPath = database_path('Data/kredensial.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->error("❌ File CSV kredensial tidak ditemukan di: $csvPath");
            return;
        }

        $csvData = array_map('str_getcsv', file($csvPath));
        $header = array_shift($csvData);
        
        $count = 0;
        foreach ($csvData as $row) {
            $data = array_combine($header, $row);
            
            // Cari role dari data statis lama atau default ke 'Anggota'
            $oldMembers = collect($this->getMembers());
            $oldMember = $oldMembers->where('nim', $data['nim'])->first();
            $role = $oldMember['role'] ?? 'Anggota';

            // Create or Update User
            $nim = trim((string) $data['nim']);
            $user = User::withTrashed()->where('nim', $nim)->first();
            
            if ($user) {
                $user->restore(); // Restore if soft-deleted
                $user->update([
                    'name' => trim($data['nama']),
                    'email' => trim($data['email']),
                    'password' => Hash::make($data['password']),
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);
            } else {
                $user = User::create([
                    'nim' => $nim,
                    'name' => trim($data['nama']),
                    'email' => trim($data['email']),
                    'password' => Hash::make($data['password']),
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);
            }

            // Sync Role
            $user->syncRoles([$role]);

            // Dispatch Email Job
            \App\Jobs\SendInitialCredentialsJob::dispatch($user, $data['password']);
            
            $count++;
        }

        $this->command->info("✅ Berhasil memproses $count anggota dari CSV.");
        $this->command->info("📧 Email kredensial telah masuk ke antrean (queue).");
        $this->command->info("🚀 Jalankan 'php artisan queue:work --queue=emails,default' untuk mengirim.");
    }

    /**
     * Get member data array.
     *
     * @return array<int, array{name: string, nim: string, jabatan: string, role: string, email: string}>
     */
    private function getMembers(): array
    {
        return [
            // =====================================================
            // PIMPINAN INTI
            // =====================================================
            [
                'name' => 'Diva Afdholia R.',
                'nim' => '222413550',
                'jabatan' => 'Ketua',
                'role' => 'Ketua',
                'email' => 'diva.afdholia@siwirus.test',
            ],
            [
                'name' => 'Fikri Adi Nugraha',
                'nim' => '222413577',
                'jabatan' => 'Wakil Ketua',
                'role' => 'Wakil Ketua',
                'email' => 'fikri.adi@siwirus.test',
            ],

            // =====================================================
            // SEKRETARIAT
            // =====================================================
            [
                'name' => 'Defila Cahyati',
                'nim' => '222413540',
                'jabatan' => 'Sekretaris I',
                'role' => 'Sekretaris',
                'email' => 'defila.cahyati@siwirus.test',
            ],
            [
                'name' => 'Raziq Alzam Fadlullah',
                'nim' => '112413751',
                'jabatan' => 'Sekretaris II',
                'role' => 'Sekretaris',
                'email' => 'raziq.alzam@siwirus.test',
            ],

            // =====================================================
            // BENDAHARA
            // =====================================================
            [
                'name' => 'Siti Rahmadhani Zaskya Mantika',
                'nim' => '222413785',
                'jabatan' => 'Bendahara Umum',
                'role' => 'Bendahara Umum',
                'email' => 'siti.rahmadhani@siwirus.test',
            ],
            [
                'name' => 'Mei Indriyanti Syamsi',
                'nim' => '222413652',
                'jabatan' => 'Bendahara Kegiatan',
                'role' => 'Bendahara Kegiatan',
                'email' => 'mei.indriyanti@siwirus.test',
            ],
            [
                'name' => 'Putra Irvan Kala\'padang',
                'nim' => '222413736',
                'jabatan' => 'Bendahara Toko',
                'role' => 'Bendahara Toko',
                'email' => 'putra.irvan@siwirus.test',
            ],

            // =====================================================
            // KOORDINATOR DIVISI
            // =====================================================
            [
                'name' => 'Diah Puji Pramesti',
                'nim' => '222413547',
                'jabatan' => 'Koordinator Toko',
                'role' => 'Koordinator Toko',
                'email' => 'diah.puji@siwirus.test',
            ],
            [
                'name' => 'Ego Stiven Rafliza',
                'nim' => '222413552',
                'jabatan' => 'Koordinator PSDA',
                'role' => 'Koordinator PSDA',
                'email' => 'ego.stiven@siwirus.test',
            ],
            [
                'name' => 'Desvita Prabawaningrum',
                'nim' => '222413544',
                'jabatan' => 'Koordinator Humsar',
                'role' => 'Koordinator Humsar',
                'email' => 'desvita.prabawaningrum@siwirus.test',
            ],
            [
                'name' => 'Fatimah Az Zahra',
                'nim' => '222413569',
                'jabatan' => 'Koordinator Produksi dan Pengadaan',
                'role' => 'Koordinator Produksi',
                'email' => 'fatimah.azzahra@siwirus.test',
            ],
            [
                'name' => 'Rehandhika Arya Pratama',
                'nim' => '222413752',
                'jabatan' => 'Koordinator IT',
                'role' => 'Super Admin', // Super Admin karena IT
                'email' => 'rehandhika.arya@siwirus.test',
            ],
            [
                'name' => 'Risyda Azifatil Maghfira',
                'nim' => '222413763',
                'jabatan' => 'Koordinator Desain',
                'role' => 'Koordinator Desain',
                'email' => 'risyda.azifatil@siwirus.test',
            ],

            // =====================================================
            // ANGGOTA
            // =====================================================
            [
                'name' => 'Rahmat Budiyanto',
                'nim' => '222413742',
                'jabatan' => 'Anggota Produksi dan Pengadaan',
                'role' => 'Anggota',
                'email' => 'rahmat.budiyanto@siwirus.test',
            ],
        ];
    }
}
