<?php

/**
 * Menu Configuration for Sidebar Navigation
 *
 * Each menu item supports:
 * - key: Unique identifier for the menu item
 * - label: Display label in Indonesian
 * - icon: Heroicon name (used with x-ui.icon component)
 * - route: Laravel route name (null for parent-only menus)
 * - permissions: Array of required permissions (empty = accessible to all authenticated users)
 * - permission_logic: 'any' (OR) or 'all' (AND) - default is 'any'
 * - children: Nested menu items for dropdown menus
 * - type: 'divider' for separator items
 * - roles: Additional role-based restriction (optional)
 * 
 * PERMISSION STRUCTURE:
 * - Empty permissions [] = Self-service, accessible to ALL authenticated users
 * - lihat_* = View all data (management view)
 * - kelola_* = Full CRUD operations (management)
 * - setujui_* = Approval workflows
 * 
 * NOTE: Permissions must match those defined in config/roles.php
 */

return [
    'items' => [
        // ============================================================
        // SELF-SERVICE MENU (All authenticated users)
        // ============================================================
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'home',
            'route' => 'admin.dashboard',
            'permissions' => [], // All authenticated users
        ],
        [
            'key' => 'attendance',
            'label' => 'Absensi',
            'icon' => 'clipboard-list',
            'route' => null,
            'permissions' => [], // All authenticated users (self-service)
            'children' => [
                ['key' => 'attendance.checkin', 'label' => 'Check In/Out', 'route' => 'admin.attendance.check-in-out', 'permissions' => []],
                ['key' => 'attendance.history', 'label' => 'Riwayat Saya', 'route' => 'admin.attendance.history', 'permissions' => []],
            ],
        ],
        [
            'key' => 'schedule',
            'label' => 'Jadwal',
            'icon' => 'calendar',
            'route' => null,
            'permissions' => [], // All authenticated users (self-service)
            'children' => [
                ['key' => 'schedule.my', 'label' => 'Jadwal Saya', 'route' => 'admin.schedule.my-schedule', 'permissions' => []],
                ['key' => 'schedule.availability', 'label' => 'Ketersediaan', 'route' => 'admin.schedule.availability', 'permissions' => []],
                ['key' => 'schedule.manage', 'label' => 'Kelola Jadwal', 'route' => 'admin.schedule.index', 'active_routes' => ['admin.schedule.index', 'admin.schedule.create', 'admin.schedule.edit', 'admin.schedule.history', 'admin.schedule.calendar', 'admin.schedule.statistics'], 'permissions' => ['lihat_semua_jadwal']],
                ['key' => 'schedule.leave', 'label' => 'Izin/Cuti', 'route' => 'admin.leave.index', 'active_routes' => ['admin.leave.*'], 'permissions' => []],
                ['key' => 'schedule.swap', 'label' => 'Perubahan Jadwal', 'route' => 'admin.swap.index', 'active_routes' => ['admin.swap.*'], 'permissions' => []],
            ],
        ],
        [
            'key' => 'cashier',
            'label' => 'Kasir / POS',
            'icon' => 'currency-dollar',
            'route' => null,
            'permissions' => [], // All authenticated users
            'children' => [
                ['key' => 'cashier.pos', 'label' => 'POS Kasir', 'route' => 'admin.cashier.pos', 'permissions' => []],
                ['key' => 'cashier.entry', 'label' => 'Entry Transaksi', 'route' => 'admin.cashier.pos-entry', 'permissions' => [], 'roles' => ['Super Admin', 'Ketua', 'Wakil Ketua']],
            ],
        ],
        [
            'key' => 'penalties',
            'label' => 'Penalti',
            'icon' => 'exclamation-triangle',
            'route' => null,
            'permissions' => [], // All authenticated users (self-service)
            'children' => [
                ['key' => 'penalties.my', 'label' => 'Penalti Saya', 'route' => 'admin.penalties.my-penalties', 'permissions' => []],
                ['key' => 'penalties.list', 'label' => 'Daftar Penalti', 'route' => 'admin.penalties.index', 'permissions' => ['lihat_semua_penalti']],
                ['key' => 'penalties.manage', 'label' => 'Kelola Penalti', 'route' => 'admin.penalties.manage', 'permissions' => ['kelola_penalti']],
            ],
        ],
        
        // ============================================================
        // MANAGEMENT MENU (Requires specific permissions)
        // ============================================================
        ['key' => 'divider.management', 'type' => 'divider'],
        [
            'key' => 'shu',
            'label' => 'Poin SHU',
            'icon' => 'heart',
            'route' => 'admin.poin-shu.monitoring',
            'permissions' => ['lihat_poin_shu'],
        ],
        [
            'key' => 'inventory',
            'label' => 'Inventaris',
            'icon' => 'cube',
            'route' => null,
            'permissions' => ['lihat_produk', 'lihat_stok'],
            'permission_logic' => 'any',
            'children' => [
                ['key' => 'inventory.products', 'label' => 'Daftar Produk', 'route' => 'admin.products.index', 'active_routes' => ['admin.products.*'], 'permissions' => ['lihat_produk']],
                ['key' => 'inventory.stock', 'label' => 'Manajemen Stok', 'route' => 'admin.stock.index', 'active_routes' => ['admin.stock.*'], 'permissions' => ['lihat_stok']],
            ],
        ],
        [
            'key' => 'reports',
            'label' => 'Laporan',
            'icon' => 'document',
            'route' => null,
            'permissions' => ['lihat_laporan'],
            'children' => [
                ['key' => 'reports.attendance', 'label' => 'Laporan Absensi', 'route' => 'admin.reports.attendance', 'permissions' => ['lihat_laporan']],
                ['key' => 'reports.sales', 'label' => 'Laporan Penjualan', 'route' => 'admin.reports.sales', 'permissions' => ['lihat_laporan']],
                ['key' => 'reports.penalties', 'label' => 'Laporan Penalti', 'route' => 'admin.reports.penalties', 'permissions' => ['lihat_laporan']],
            ],
        ],
        
        // ============================================================
        // ADMIN MENU (High-level permissions required)
        // ============================================================
        ['key' => 'divider.admin', 'type' => 'divider'],
        [
            'key' => 'activity-log',
            'label' => 'Log Aktivitas',
            'icon' => 'clipboard-document-list',
            'route' => 'admin.activity-log',
            'permissions' => ['lihat_log_aktivitas'],
            'roles' => ['Super Admin', 'Ketua'],
        ],
        [
            'key' => 'users',
            'label' => 'Manajemen User',
            'icon' => 'user-group',
            'route' => 'admin.users.index',
            'active_routes' => ['admin.users.*'],
            'permissions' => ['lihat_pengguna'],
        ],
        [
            'key' => 'roles',
            'label' => 'Role & Permission',
            'icon' => 'check-circle',
            'route' => 'admin.roles.index',
            'active_routes' => ['admin.roles.*'],
            'permissions' => ['lihat_peran'],
        ],
        [
            'key' => 'settings',
            'label' => 'Pengaturan',
            'icon' => 'cog',
            'route' => null,
            'permissions' => ['kelola_pengaturan'],
            'children' => [
                ['key' => 'settings.system', 'label' => 'Pengaturan Sistem', 'route' => 'admin.settings.system', 'permissions' => ['kelola_pengaturan']],
                ['key' => 'settings.store', 'label' => 'Pengaturan Toko', 'route' => 'admin.settings.store', 'permissions' => ['kelola_pengaturan'], 'roles' => ['Super Admin', 'Ketua', 'Wakil Ketua']],
                ['key' => 'settings.banners', 'label' => 'Banner & Berita', 'route' => 'admin.settings.banners', 'permissions' => ['kelola_pengaturan'], 'roles' => ['Super Admin', 'Ketua']],
                ['key' => 'settings.payment', 'label' => 'Pengaturan Pembayaran', 'route' => 'admin.settings.payment', 'permissions' => ['kelola_pengaturan'], 'roles' => ['Super Admin', 'Ketua', 'Wakil Ketua']],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('MENU_CACHE_ENABLED', true),
        'ttl' => env('MENU_CACHE_TTL', 3600),
        'prefix' => 'menu_access',
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin Role
    |--------------------------------------------------------------------------
    | Must match the value in config/roles.php
    */
    'super_admin_role' => env('SUPER_ADMIN_ROLE', 'Super Admin'),
];
