<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Route Access Tests
 *
 * Tests basic route accessibility for different user roles.
 * Note: The application uses view-level permission checks rather than
 * route-level middleware for most admin routes.
 */
class RouteAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions (using new Indonesian naming convention)
        $permissions = [
            'kelola_pengguna', 'lihat_pengguna',
            'kelola_produk', 'lihat_produk',
            'kelola_laporan', 'lihat_laporan',
            'kelola_penjualan', 'lihat_penjualan', 'ajukan_penjualan',
            'kelola_kehadiran', 'lihat_kehadiran',
            'kelola_hukuman', 'lihat_hukuman',
            'kelola_pengaturan',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Ketua role with admin permissions
        $ketuaRole = Role::firstOrCreate(['name' => 'Ketua']);
        $ketuaRole->givePermissionTo([
            'kelola_pengguna', 'lihat_pengguna',
            'kelola_produk', 'lihat_produk',
            'kelola_laporan', 'lihat_laporan',
            'kelola_penjualan', 'lihat_penjualan',
            'kelola_kehadiran', 'lihat_kehadiran',
            'kelola_hukuman', 'lihat_hukuman',
            'kelola_pengaturan',
        ]);

        // Create Anggota role with member permissions
        $anggotaRole = Role::firstOrCreate(['name' => 'Anggota']);
        $anggotaRole->givePermissionTo([
            'lihat_penjualan', 'ajukan_penjualan',
            'lihat_kehadiran',
            'lihat_hukuman',
        ]);
    }

    private function makeUserWithRole(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role).' User',
            'nim' => 'NIM'.strtoupper(substr($role, 0, 3)).rand(1000, 9999),
            'email' => strtolower($role).rand(1000, 9999).'@test.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = $this->makeUserWithRole('Ketua');

        // Test core admin routes
        $this->actingAs($admin)
            ->get('/admin/beranda')->assertOk();
        $this->actingAs($admin)
            ->get('/admin/pengguna')->assertOk();
        $this->actingAs($admin)
            ->get('/admin/laporan/absensi')->assertOk();
        $this->actingAs($admin)
            ->get('/admin/laporan/penjualan')->assertOk();
    }

    public function test_member_can_access_member_routes(): void
    {
        $member = $this->makeUserWithRole('Anggota');

        // Members can access dashboard and their own data
        $this->actingAs($member)
            ->get('/admin/beranda')->assertOk();
        $this->actingAs($member)
            ->get('/admin/absensi')->assertOk();
        $this->actingAs($member)
            ->get('/admin/jadwal/jadwal-saya')->assertOk();
        $this->actingAs($member)
            ->get('/admin/penalti/penalti-saya')->assertOk();
    }

    public function test_guest_cannot_access_admin_routes(): void
    {
        // Guest should be redirected to login
        $this->get('/admin/beranda')->assertRedirect('/admin/masuk');
        $this->get('/admin/pengguna')->assertRedirect('/admin/masuk');
        $this->get('/admin/produk')->assertRedirect('/admin/masuk');
    }

    public function test_store_settings_requires_specific_roles(): void
    {
        // Store settings has explicit role middleware
        $admin = $this->makeUserWithRole('Ketua');
        $member = $this->makeUserWithRole('Anggota');

        // Admin can access store settings
        $this->actingAs($admin)
            ->get('/admin/pengaturan/toko')->assertOk();

        // Member cannot access store settings (has role middleware)
        $this->actingAs($member)
            ->get('/admin/pengaturan/toko')->assertForbidden();
    }
}
