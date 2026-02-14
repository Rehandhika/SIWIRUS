<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions (30 permissions - without division permissions)
        $permissions = [
            // Pengguna (4)
            'kelola_pengguna',
            'lihat_pengguna',
            'kelola_peran',
            'lihat_peran',
            // Kehadiran (2)
            'kelola_kehadiran',
            'lihat_kehadiran',
            // Jadwal (4)
            'kelola_jadwal',
            'lihat_jadwal',
            'kelola_tukar_jadwal',
            'ajukan_tukar_jadwal',
            // Cuti (2)
            'kelola_cuti',
            'ajukan_cuti',
            // Pelanggaran (2)
            'kelola_pelanggaran',
            'lihat_pelanggaran',
            // Transaksi (7)
            'kelola_penjualan',
            'lihat_penjualan',
            'kelola_produk',
            'lihat_produk',
            'kelola_pembelian',
            'lihat_pembelian',
            'kelola_stok',
            // Laporan (2)
            'kelola_laporan',
            'lihat_laporan',
            // Keuangan (2)
            'kelola_keuangan',
            'lihat_keuangan',
            // Sistem (3)
            'kelola_pengaturan',
            'lihat_log_audit',
            'kelola_notifikasi',
            // Poin SHU (2)
            'kelola_poin_shu',
            'lihat_poin_shu',
        ];

        // Create permissions
        foreach ($permissions as $permName) {
            Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => 'web']
            );
        }

        // Define role-permission mappings
        // Role 1 = Super Admin, 2 = Admin, 3 = Pengurus, 4 = Anggota
        $rolePermissions = [
            'Super Admin' => [
                'kelola_pengguna', 'lihat_pengguna', 'kelola_peran', 'lihat_peran',
                'kelola_kehadiran', 'lihat_kehadiran',
                'kelola_jadwal', 'lihat_jadwal', 'kelola_tukar_jadwal', 'ajukan_tukar_jadwal',
                'kelola_cuti', 'ajukan_cuti',
                'kelola_pelanggaran', 'lihat_pelanggaran',
                'kelola_penjualan', 'lihat_penjualan', 'kelola_produk', 'lihat_produk',
                'kelola_pembelian', 'lihat_pembelian', 'kelola_stok',
                'kelola_laporan', 'lihat_laporan',
                'kelola_keuangan', 'lihat_keuangan',
                'kelola_pengaturan', 'lihat_log_audit', 'kelola_notifikasi',
                'kelola_poin_shu', 'lihat_poin_shu',
            ],
            'Admin' => [
                'lihat_pengguna', 'lihat_peran',
                'kelola_kehadiran', 'lihat_kehadiran',
                'kelola_jadwal', 'lihat_jadwal', 'kelola_tukar_jadwal', 'ajukan_tukar_jadwal',
                'kelola_cuti', 'ajukan_cuti',
                'kelola_pelanggaran', 'lihat_pelanggaran',
                'kelola_penjualan', 'lihat_penjualan', 'kelola_produk', 'lihat_produk',
                'kelola_pembelian', 'lihat_pembelian', 'kelola_stok',
                'kelola_laporan', 'lihat_laporan',
                'lihat_keuangan',
                'kelola_poin_shu', 'lihat_poin_shu',
            ],
            'Pengurus' => [
                'lihat_pengguna',
                'lihat_kehadiran',
                'lihat_jadwal', 'ajukan_tukar_jadwal',
                'ajukan_cuti',
                'lihat_pelanggaran',
                'lihat_penjualan', 'lihat_produk',
                'lihat_pembelian',
                'lihat_laporan',
                'lihat_poin_shu',
            ],
            'Anggota' => [
                'lihat_pengguna',
                'lihat_kehadiran',
                'lihat_jadwal', 'ajukan_tukar_jadwal',
                'ajukan_cuti',
                'lihat_pelanggaran',
                'lihat_penjualan', 'lihat_produk',
                'lihat_laporan',
            ],
        ];

        // Assign permissions to roles
        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }

        $this->command->info('Permissions and roles seeded successfully.');
    }
}
