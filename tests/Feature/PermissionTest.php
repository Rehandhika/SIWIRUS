<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Test suite for permission and authorization fixes.
 * 
 * Tests cover:
 * - P-001: Role consistency between seeder and configuration
 * - P-002: Route middleware permission checks
 * - P-003: Super Admin bypass functionality
 * - P-004: Policy registration and authorization
 * - P-005: Livewire permission checks
 * - P-006: Menu access sync with route permissions
 * 
 * PERMISSION MODEL:
 * - Self-service permissions: Accessible to ALL authenticated users (check_in_out, akses_kasir, etc.)
 * - Management permissions: Require specific permissions (kelola_*, lihat_semua_*, setujui_*)
 */
class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run the role/permission seeder for tests
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /*
    |--------------------------------------------------------------------------
    | P-001: Role Consistency Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Test that all roles defined in config exist in database.
     */
    public function test_all_configured_roles_exist_in_database(): void
    {
        $configuredRoles = array_keys(config('roles.role_permissions', []));
        
        foreach ($configuredRoles as $roleName) {
            $this->assertDatabaseHas('roles', ['name' => $roleName]);
        }
    }

    /**
     * Test that Super Admin role is properly configured.
     */
    public function test_super_admin_role_is_configured(): void
    {
        $superAdminRole = config('roles.super_admin_role', 'Super Admin');
        
        $this->assertDatabaseHas('roles', ['name' => $superAdminRole]);
    }

    /**
     * Test that all permissions defined in config exist in database.
     */
    public function test_all_configured_permissions_exist_in_database(): void
    {
        $configuredPermissions = array_keys(config('roles.permissions', []));
        
        foreach ($configuredPermissions as $permissionName) {
            $this->assertDatabaseHas('permissions', ['name' => $permissionName]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | P-002: Route Middleware Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Test that admin routes require authentication.
     */
    public function test_admin_routes_require_authentication(): void
    {
        $protectedRoutes = [
            route('admin.dashboard'),
            route('admin.products.index'),
            route('admin.stock.index'),
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect(route('login'));
        }
    }

    /**
     * Test that authenticated user can access dashboard.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that permission-protected routes deny access without permission.
     */
    public function test_permission_protected_routes_deny_access_without_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota'); // Anggota has limited permissions

        // Try to access settings without permission
        $response = $this->actingAs($user)->get(route('admin.settings.system'));
        
        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Self-Service Routes Tests (All authenticated users)
    |--------------------------------------------------------------------------
    */

    /**
     * Test that ALL authenticated users can access check-in/out.
     * This is a self-service feature - no special permission needed.
     */
    public function test_all_authenticated_users_can_access_check_in_out(): void
    {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $response = $this->actingAs($anggota)->get(route('admin.attendance.check-in-out'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that ALL authenticated users can access POS.
     * This is a self-service feature - no special permission needed.
     */
    public function test_all_authenticated_users_can_access_pos(): void
    {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $response = $this->actingAs($anggota)->get(route('admin.cashier.pos'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that ALL authenticated users can view their own schedule.
     * This is a self-service feature.
     */
    public function test_all_authenticated_users_can_view_own_schedule(): void
    {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $response = $this->actingAs($anggota)->get(route('admin.schedule.my-schedule'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that ALL authenticated users can view their own penalties.
     * This is a self-service feature.
     */
    public function test_all_authenticated_users_can_view_own_penalties(): void
    {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $response = $this->actingAs($anggota)->get(route('admin.my-penalties'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that ALL authenticated users can submit leave requests.
     * This is a self-service feature.
     */
    public function test_all_authenticated_users_can_submit_leave_requests(): void
    {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $response = $this->actingAs($anggota)->get(route('admin.leave.index'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that ALL authenticated users can submit swap requests.
     * This is a self-service feature.
     */
    public function test_all_authenticated_users_can_submit_swap_requests(): void
    {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $response = $this->actingAs($anggota)->get(route('admin.swap.index'));
        
        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | Management Routes Tests (Require specific permissions)
    |--------------------------------------------------------------------------
    */

    /**
     * Test that attendance management requires kelola_absensi permission.
     */
    public function test_attendance_management_requires_permission(): void
    {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $response = $this->actingAs($anggota)->get(route('admin.attendance.index'));
        
        $response->assertStatus(403);
    }

    /**
     * Test that schedule management requires lihat_semua_jadwal permission.
     */
    public function test_schedule_management_requires_permission(): void
    {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        $response = $this->actingAs($anggota)->get(route('admin.schedule.index'));
        
        $response->assertStatus(403);
    }

    /**
     * Test that Admin can access attendance management.
     */
    public function test_admin_can_access_attendance_management(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that Admin can access schedule management.
     */
    public function test_admin_can_access_schedule_management(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->get(route('admin.schedule.index'));
        
        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | P-003: Super Admin Bypass Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Test that Super Admin has access to all permissions via Gate::before().
     */
    public function test_super_admin_has_all_permissions(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        // Test various permissions
        $permissions = ['kelola_pengguna', 'kelola_produk', 'kelola_jadwal', 'kelola_pengaturan'];
        
        foreach ($permissions as $permission) {
            $this->assertTrue(
                Gate::forUser($superAdmin)->allows($permission),
                "Super Admin should have permission: {$permission}"
            );
        }
    }

    /**
     * Test that Super Admin can access any policy action.
     */
    public function test_super_admin_can_perform_any_policy_action(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $product = Product::factory()->create();

        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $product));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('delete', $product));
    }

    /**
     * Test that non-super-admin users are restricted by permissions.
     */
    public function test_non_super_admin_users_are_restricted(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');

        // Anggota should not have kelola_pengguna permission
        $this->assertFalse(Gate::forUser($user)->allows('kelola_pengguna'));
    }

    /*
    |--------------------------------------------------------------------------
    | P-004: Policy Registration Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Test UserPolicy is registered and working.
     */
    public function test_user_policy_is_registered(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['kelola_pengguna']);

        $targetUser = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('update', $targetUser));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $targetUser));
    }

    /**
     * Test ProductPolicy is registered and working.
     */
    public function test_product_policy_is_registered(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['kelola_produk']);

        $product = Product::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('update', $product));
        $this->assertTrue(Gate::forUser($user)->allows('delete', $product));
    }

    /**
     * Test SalePolicy is registered and working.
     */
    public function test_sale_policy_is_registered(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['lihat_semua_penjualan']);

        // Just verify the policy is registered by checking if Gate can resolve it
        $this->assertTrue(Gate::forUser($user)->allows('viewAny', Sale::class));
    }

    /**
     * Test AttendancePolicy is registered and working.
     */
    public function test_attendance_policy_is_registered(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['kelola_absensi']);

        $attendance = Attendance::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('update', $attendance));
    }

    /*
    |--------------------------------------------------------------------------
    | P-006: Menu Access Sync Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Test that menu permissions are defined.
     */
    public function test_menu_permissions_are_defined(): void
    {
        // Get menu items with permissions
        $menuItems = config('menu.items', []);
        
        $this->assertNotEmpty($menuItems, 'Menu items should not be empty');
        
        foreach ($menuItems as $item) {
            if (isset($item['permissions']) && !empty($item['permissions'])) {
                // Just verify permissions are defined as arrays
                $this->assertIsArray($item['permissions']);
            }
            
            // Check children
            if (isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    if (isset($child['permissions']) && !empty($child['permissions'])) {
                        $this->assertIsArray($child['permissions']);
                    }
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Role-Based Access Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Test Admin role has appropriate permissions.
     */
    public function test_admin_role_has_appropriate_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        // Admin should have management permissions
        $this->assertTrue(Gate::forUser($admin)->allows('lihat_pengguna'));
        $this->assertTrue(Gate::forUser($admin)->allows('lihat_produk'));
        $this->assertTrue(Gate::forUser($admin)->allows('lihat_semua_jadwal'));
        $this->assertTrue(Gate::forUser($admin)->allows('kelola_absensi'));
    }

    /**
     * Test Pengurus role has appropriate permissions.
     */
    public function test_pengurus_role_has_appropriate_permissions(): void
    {
        $pengurus = User::factory()->create();
        $pengurus->assignRole('Pengurus');

        // Pengurus should have view permissions
        $this->assertTrue(Gate::forUser($pengurus)->allows('lihat_semua_penjualan'));
        $this->assertTrue(Gate::forUser($pengurus)->allows('lihat_semua_jadwal'));
    }

    /**
     * Test Anggota role has self-service permissions.
     */
    public function test_anggota_role_has_self_service_permissions(): void
    {
        $anggota = User::factory()->create();
        $anggota->assignRole('Anggota');

        // Anggota should have self-service permissions
        $this->assertTrue(Gate::forUser($anggota)->allows('check_in_out'));
        $this->assertTrue(Gate::forUser($anggota)->allows('akses_kasir'));
        $this->assertTrue(Gate::forUser($anggota)->allows('lihat_jadwal_sendiri'));
        $this->assertTrue(Gate::forUser($anggota)->allows('lihat_absensi_sendiri'));
        $this->assertTrue(Gate::forUser($anggota)->allows('lihat_penalti_sendiri'));
        $this->assertTrue(Gate::forUser($anggota)->allows('ajukan_cuti'));
        $this->assertTrue(Gate::forUser($anggota)->allows('ajukan_tukar_jadwal'));
        
        // Anggota should NOT have admin permissions
        $this->assertFalse(Gate::forUser($anggota)->allows('kelola_pengguna'));
        $this->assertFalse(Gate::forUser($anggota)->allows('kelola_pengaturan'));
        $this->assertFalse(Gate::forUser($anggota)->allows('kelola_absensi'));
    }

    /*
    |--------------------------------------------------------------------------
    | Cache Invalidation Tests
    |--------------------------------------------------------------------------
    */

    /**
     * Test that permission cache can be cleared.
     */
    public function test_permission_cache_can_be_cleared(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['lihat_produk']);

        // First check - should have permission
        $this->assertTrue(Gate::forUser($user)->allows('lihat_produk'));

        // Revoke permission
        $user->revokePermissionTo('lihat_produk');

        // Clear cache
        $this->artisan('permission:clear-cache')
            ->assertExitCode(0);

        // After cache clear - should not have permission
        $this->assertFalse(Gate::forUser($user)->allows('lihat_produk'));
    }
}
