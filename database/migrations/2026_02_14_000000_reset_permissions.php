<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define new permissions (30 permissions)
        $newPermissions = [
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

        // Delete all old permissions that are not in the new list
        Permission::whereNotIn('name', $newPermissions)->delete();

        // Create new permissions if they don't exist
        foreach ($newPermissions as $permName) {
            Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => 'web']
            );
        }

        // Clear cache again
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define role-permission mappings
        $rolePermissions = [
            'Super Admin' => $newPermissions, // All permissions
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

        // Sync permissions to roles
        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->syncPermissions($perms);
            }
        }

        // Clear cache one more time
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is destructive, we can't easily reverse it
        // You would need to restore from a backup
    }
};