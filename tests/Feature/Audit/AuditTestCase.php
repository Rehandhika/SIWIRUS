<?php

namespace Tests\Feature\Audit;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\Penalty;
use App\Models\PenaltyType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Schedule;
use App\Models\ScheduleAssignment;
use App\Models\StoreSetting;
use App\Models\SwapRequest;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Base test case for QA Comprehensive Audit tests.
 * Provides common setup methods, user creation helpers for all roles,
 * and test data seeding methods.
 */
abstract class AuditTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     */
    protected $seed = false;

    /**
     * Force migrations instead of using schema dump.
     */
    protected function shouldSeed(): bool
    {
        return false;
    }

    /**
     * Test users for each role
     */
    protected User $superAdmin;

    protected User $ketua;

    protected User $wakilKetua;

    protected User $bph;

    protected User $anggota;

    /**
     * Default password for test users
     */
    protected string $defaultPassword = 'password';

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRolesAndPermissions();
        $this->createTestUsers();
    }

    /**
     * Set up roles and permissions for testing.
     */
    protected function setUpRolesAndPermissions(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        $permissions = $this->getAllPermissions();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $this->createSuperAdminRole();
        $this->createKetuaRole();
        $this->createWakilKetuaRole();
        $this->createBPHRole();
        $this->createAnggotaRole();
    }

    /**
     * Get all system permissions.
     */
    protected function getAllPermissions(): array
    {
        return [
            // User management
            'kelola_pengguna', 'lihat_pengguna',
            // Role management
            'kelola_peran', 'lihat_peran',
            // Attendance
            'kelola_kehadiran', 'lihat_kehadiran',
            // Schedule management
            'kelola_jadwal', 'lihat_jadwal',
            'kelola_ketersediaan', 'lihat_ketersediaan',
            // Swap requests
            'kelola_tukar_jadwal', 'lihat_tukar_jadwal', 'ajukan_tukar_jadwal',
            // Leave requests
            'kelola_cuti', 'lihat_cuti', 'ajukan_cuti',
            // Penalties
            'kelola_hukuman', 'lihat_hukuman', 'ajukan_hukuman',
            // Sales/Cashier
            'kelola_penjualan', 'lihat_penjualan', 'ajukan_penjualan',
            // Products
            'kelola_produk', 'lihat_produk',
            // Stock
            'kelola_stok', 'lihat_stok',
            // Reports
            'kelola_laporan', 'lihat_laporan',
            // SHU Points
            'kelola_poin_shu', 'lihat_poin_shu',
            // System settings
            'kelola_pengaturan',
            // Audit logs
            'kelola_log_audit', 'lihat_log_audit',
        ];
    }

    /**
     * Create Super Admin role with all permissions.
     */
    protected function createSuperAdminRole(): Role
    {
        $role = Role::firstOrCreate(['name' => 'Super Admin']);
        $role->givePermissionTo(Permission::all());

        return $role;
    }

    /**
     * Create Ketua role with management permissions.
     */
    protected function createKetuaRole(): Role
    {
        $role = Role::firstOrCreate(['name' => 'Ketua']);
        $role->givePermissionTo([
            'kelola_pengguna', 'lihat_pengguna',
            'kelola_kehadiran', 'lihat_kehadiran',
            'kelola_jadwal', 'lihat_jadwal',
            'kelola_tukar_jadwal', 'lihat_tukar_jadwal',
            'kelola_cuti', 'lihat_cuti',
            'kelola_hukuman', 'lihat_hukuman',
            'kelola_penjualan', 'lihat_penjualan',
            'kelola_produk', 'lihat_produk',
            'kelola_laporan', 'lihat_laporan',
            'kelola_pengaturan',
            'lihat_log_audit',
        ]);

        return $role;
    }

    /**
     * Create Wakil Ketua role with deputy permissions.
     */
    protected function createWakilKetuaRole(): Role
    {
        $role = Role::firstOrCreate(['name' => 'Wakil Ketua']);
        $role->givePermissionTo([
            'lihat_pengguna',
            'kelola_kehadiran', 'lihat_kehadiran',
            'kelola_jadwal', 'lihat_jadwal',
            'kelola_tukar_jadwal', 'lihat_tukar_jadwal',
            'kelola_cuti', 'lihat_cuti',
            'kelola_hukuman', 'lihat_hukuman',
            'kelola_penjualan', 'lihat_penjualan',
            'kelola_produk', 'lihat_produk',
            'kelola_laporan', 'lihat_laporan',
        ]);

        return $role;
    }

    /**
     * Create BPH role with board member permissions.
     */
    protected function createBPHRole(): Role
    {
        $role = Role::firstOrCreate(['name' => 'BPH']);
        $role->givePermissionTo([
            'lihat_kehadiran',
            'kelola_jadwal', 'lihat_jadwal',
            'kelola_tukar_jadwal', 'lihat_tukar_jadwal',
            'kelola_cuti', 'lihat_cuti',
            'lihat_hukuman',
            'lihat_penjualan',
            'lihat_produk',
            'lihat_laporan',
        ]);

        return $role;
    }

    /**
     * Create Anggota role with member permissions.
     */
    protected function createAnggotaRole(): Role
    {
        $role = Role::firstOrCreate(['name' => 'Anggota']);
        $role->givePermissionTo([
            'lihat_kehadiran',
            'lihat_jadwal',
            'lihat_ketersediaan',
            'lihat_tukar_jadwal', 'ajukan_tukar_jadwal',
            'lihat_cuti', 'ajukan_cuti',
            'lihat_hukuman',
            'lihat_penjualan', 'ajukan_penjualan',
        ]);

        return $role;
    }

    /**
     * Create test users for all roles.
     */
    protected function createTestUsers(): void
    {
        $this->superAdmin = $this->createUserWithRole('Super Admin', [
            'nim' => '00000000',
            'name' => 'Super Administrator',
            'email' => 'superadmin@test.com',
        ]);

        $this->ketua = $this->createUserWithRole('Ketua', [
            'nim' => '11111111',
            'name' => 'Ketua KOPMA',
            'email' => 'ketua@test.com',
        ]);

        $this->wakilKetua = $this->createUserWithRole('Wakil Ketua', [
            'nim' => '22222222',
            'name' => 'Wakil Ketua KOPMA',
            'email' => 'wakil@test.com',
        ]);

        $this->bph = $this->createUserWithRole('BPH', [
            'nim' => '33333333',
            'name' => 'BPH Member',
            'email' => 'bph@test.com',
        ]);

        $this->anggota = $this->createUserWithRole('Anggota', [
            'nim' => '44444444',
            'name' => 'Anggota KOPMA',
            'email' => 'anggota@test.com',
        ]);
    }

    /**
     * Create a user with a specific role.
     */
    protected function createUserWithRole(string $roleName, array $attributes = []): User
    {
        $defaultAttributes = [
            'nim' => 'NIM'.fake()->unique()->numerify('########'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make($this->defaultPassword),
            'status' => 'active',
        ];

        $user = User::create(array_merge($defaultAttributes, $attributes));
        $user->assignRole($roleName);

        return $user;
    }

    /**
     * Create an inactive user.
     */
    protected function createInactiveUser(string $roleName = 'Anggota'): User
    {
        return $this->createUserWithRole($roleName, [
            'status' => 'inactive',
        ]);
    }

    /**
     * Create a suspended user.
     */
    protected function createSuspendedUser(string $roleName = 'Anggota'): User
    {
        return $this->createUserWithRole($roleName, [
            'status' => 'suspended',
        ]);
    }

    // ==========================================
    // Test Data Seeding Methods
    // ==========================================

    /**
     * Seed test products.
     */
    protected function seedProducts(int $count = 5, bool $public = true): array
    {
        $products = [];
        for ($i = 0; $i < $count; $i++) {
            $products[] = Product::create([
                'name' => fake()->words(3, true),
                'slug' => fake()->unique()->slug(),
                'description' => fake()->paragraph(),
                'price' => fake()->numberBetween(1000, 100000),
                'stock' => fake()->numberBetween(10, 100),
                'minimum_stock' => fake()->numberBetween(1, 10),
                'sku' => fake()->unique()->ean8(),
                'is_active' => true,
                'is_public' => $public,
            ]);
        }

        return $products;
    }

    /**
     * Seed a single product with specific attributes.
     */
    protected function seedProduct(array $attributes = []): Product
    {
        $defaults = [
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(1000, 100000),
            'stock' => fake()->numberBetween(10, 100),
            'minimum_stock' => fake()->numberBetween(1, 10),
            'sku' => fake()->unique()->ean8(),
            'is_active' => true,
            'is_public' => true,
        ];

        return Product::create(array_merge($defaults, $attributes));
    }

    /**
     * Seed a schedule with assignments.
     */
    protected function seedSchedule(array $attributes = [], array $assignedUsers = []): Schedule
    {
        $weekStart = now()->startOfWeek();
        $defaults = [
            'week_start_date' => $weekStart,
            'week_end_date' => $weekStart->copy()->addDays(3),
            'status' => 'published',
            'generated_by' => $this->ketua->id ?? null,
            'total_slots' => 12, // 4 days × 3 sessions
            'filled_slots' => 0,
            'coverage_rate' => 0,
        ];

        $schedule = Schedule::create(array_merge($defaults, $attributes));

        // Create assignments for provided users
        foreach ($assignedUsers as $user) {
            $this->seedScheduleAssignment($schedule, $user);
        }

        return $schedule;
    }

    /**
     * Seed a schedule assignment.
     */
    protected function seedScheduleAssignment(Schedule $schedule, User $user, array $attributes = []): ScheduleAssignment
    {
        $date = now();
        $defaults = [
            'schedule_id' => $schedule->id,
            'user_id' => $user->id,
            'day' => strtolower($date->englishDayOfWeek),
            'session' => 1,
            'date' => $date->format('Y-m-d'),
            'time_start' => '07:30:00',
            'time_end' => '10:00:00',
            'status' => 'scheduled',
        ];

        return ScheduleAssignment::create(array_merge($defaults, $attributes));
    }

    /**
     * Seed an attendance record.
     */
    protected function seedAttendance(User $user, ?ScheduleAssignment $assignment = null, array $attributes = []): Attendance
    {
        $defaults = [
            'user_id' => $user->id,
            'schedule_assignment_id' => $assignment?->id,
            'date' => today(),
            'check_in' => now(),
            'status' => 'present',
        ];

        return Attendance::create(array_merge($defaults, $attributes));
    }

    /**
     * Seed penalty types.
     */
    protected function seedPenaltyTypes(): array
    {
        $types = [
            ['code' => 'LATE', 'name' => 'Late Check-in', 'points' => 1, 'description' => 'Arrived late for shift', 'is_active' => true],
            ['code' => 'ABSENT', 'name' => 'Absent', 'points' => 3, 'description' => 'Did not show up for shift', 'is_active' => true],
            ['code' => 'EARLY', 'name' => 'Early Leave', 'points' => 2, 'description' => 'Left before shift ended', 'is_active' => true],
        ];

        $penaltyTypes = [];
        foreach ($types as $type) {
            $penaltyTypes[] = PenaltyType::create($type);
        }

        return $penaltyTypes;
    }

    /**
     * Seed a penalty for a user.
     */
    protected function seedPenalty(User $user, ?PenaltyType $type = null, array $attributes = []): Penalty
    {
        if (! $type) {
            $type = PenaltyType::first() ?? $this->seedPenaltyTypes()[0];
        }

        $defaults = [
            'user_id' => $user->id,
            'penalty_type_id' => $type->id,
            'points' => $type->points,
            'description' => fake()->sentence(),
            'date' => today(),
            'status' => 'active',
        ];

        return Penalty::create(array_merge($defaults, $attributes));
    }

    /**
     * Seed a notification for a user.
     */
    protected function seedNotification(User $user, array $attributes = []): Notification
    {
        $defaults = [
            'user_id' => $user->id,
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'type' => 'info',
            'read_at' => null,
        ];

        return Notification::create(array_merge($defaults, $attributes));
    }

    /**
     * Seed a sale with items.
     */
    protected function seedSale(User $cashier, array $products = [], array $attributes = []): Sale
    {
        if (empty($products)) {
            $products = $this->seedProducts(3);
        }

        $totalAmount = 0;
        $items = [];

        foreach ($products as $product) {
            $quantity = fake()->numberBetween(1, 5);
            $subtotal = $product->price * $quantity;
            $totalAmount += $subtotal;
            $items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'subtotal' => $subtotal,
            ];
        }

        $defaults = [
            'cashier_id' => $cashier->id,
            'invoice_number' => Sale::generateInvoiceNumber(),
            'date' => today(),
            'total_amount' => $totalAmount,
            'payment_method' => 'cash',
            'payment_amount' => $totalAmount,
            'change_amount' => 0,
        ];

        $sale = Sale::create(array_merge($defaults, $attributes));

        // Create sale items
        foreach ($items as $item) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $item['product']->id,
                'product_name' => $item['product']->name,
                'quantity' => $item['quantity'],
                'price' => $item['unit_price'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        return $sale;
    }

    /**
     * Seed a swap request.
     */
    protected function seedSwapRequest(User $requester, User $target, ScheduleAssignment $requesterAssignment, ScheduleAssignment $targetAssignment, array $attributes = []): SwapRequest
    {
        $defaults = [
            'user_id' => $requester->id,
            'target_id' => $target->id,
            'original_assignment_id' => $requesterAssignment->id,
            'target_assignment_id' => $targetAssignment->id,
            'reason' => fake()->sentence(),
            'status' => 'pending',
        ];

        return SwapRequest::create(array_merge($defaults, $attributes));
    }

    /**
     * Seed a leave request.
     */
    protected function seedLeaveRequest(User $user, array $attributes = []): LeaveRequest
    {
        $startDate = now()->addDays(1);
        $endDate = now()->addDays(3);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        $defaults = [
            'user_id' => $user->id,
            'leave_type' => 'permission',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => fake()->sentence(),
            'status' => 'pending',
        ];

        return LeaveRequest::create(array_merge($defaults, $attributes));
    }

    /**
     * Seed store settings.
     */
    protected function seedStoreSettings(): StoreSetting
    {
        return StoreSetting::firstOrCreate(
            ['key' => 'store_name'],
            [
                'value' => 'KOPMA Test Store',
                'type' => 'string',
            ]
        );
    }

    /**
     * Seed system settings.
     */
    protected function seedSystemSettings(): array
    {
        $settings = [
            ['key' => 'late_threshold_minutes', 'value' => '15', 'group' => 'attendance'],
            ['key' => 'max_login_attempts', 'value' => '5', 'group' => 'security'],
            ['key' => 'lockout_duration_minutes', 'value' => '1', 'group' => 'security'],
        ];

        $created = [];
        foreach ($settings as $setting) {
            $created[] = SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        return $created;
    }

    // ==========================================
    // Helper Assertion Methods
    // ==========================================

    /**
     * Assert that a user can access a route.
     */
    protected function assertUserCanAccess(User $user, string $route, string $method = 'get'): void
    {
        $response = $this->actingAs($user)->{$method}($route);
        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            "Expected user to access {$route}, got status {$response->status()}"
        );
    }

    /**
     * Assert that a user cannot access a route (403 Forbidden).
     */
    protected function assertUserCannotAccess(User $user, string $route, string $method = 'get'): void
    {
        $response = $this->actingAs($user)->{$method}($route);
        $this->assertEquals(
            403,
            $response->status(),
            "Expected 403 Forbidden for {$route}, got status {$response->status()}"
        );
    }

    /**
     * Assert that a guest is redirected to login.
     */
    protected function assertGuestRedirectedToLogin(string $route, string $method = 'get'): void
    {
        $response = $this->{$method}($route);
        $response->assertRedirect('/login');
    }

    /**
     * Get all test users as an array.
     */
    protected function getAllTestUsers(): array
    {
        return [
            'Super Admin' => $this->superAdmin,
            'Ketua' => $this->ketua,
            'Wakil Ketua' => $this->wakilKetua,
            'BPH' => $this->bph,
            'Anggota' => $this->anggota,
        ];
    }

    /**
     * Get admin users (users with elevated privileges).
     */
    protected function getAdminUsers(): array
    {
        return [
            'Super Admin' => $this->superAdmin,
            'Ketua' => $this->ketua,
            'Wakil Ketua' => $this->wakilKetua,
            'BPH' => $this->bph,
        ];
    }

    /**
     * Get regular member users.
     */
    protected function getMemberUsers(): array
    {
        return [
            'Anggota' => $this->anggota,
        ];
    }
}
