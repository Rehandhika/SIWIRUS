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
 */

return [
    'items' => [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'home',
            'route' => 'admin.dashboard',
            'permissions' => [],
        ],
        [
            'key' => 'attendance',
            'label' => 'Absensi',
            'icon' => 'clipboard-list',
            'route' => null,
            'permissions' => ['kelola_kehadiran', 'lihat_kehadiran'],
            'permission_logic' => 'any',
            'children' => [
                ['key' => 'attendance.checkin', 'label' => 'Check In/Out', 'route' => 'admin.attendance.check-in-out', 'permissions' => ['lihat_kehadiran']],
                ['key' => 'attendance.list', 'label' => 'Daftar Absensi', 'route' => 'admin.attendance.index', 'permissions' => ['kelola_kehadiran']],
                ['key' => 'attendance.history', 'label' => 'Riwayat', 'route' => 'admin.attendance.history', 'permissions' => ['kelola_kehadiran', 'lihat_kehadiran'], 'permission_logic' => 'any'],
            ],
        ],
        [
            'key' => 'schedule',
            'label' => 'Jadwal',
            'icon' => 'calendar',
            'route' => null,
            'permissions' => ['kelola_jadwal', 'lihat_jadwal', 'ajukan_tukar_jadwal'],
            'permission_logic' => 'any',
            'children' => [
                ['key' => 'schedule.manage', 'label' => 'Kelola Jadwal', 'route' => 'admin.schedule.index', 'active_routes' => ['admin.schedule.index', 'admin.schedule.create', 'admin.schedule.edit', 'admin.schedule.history'], 'permissions' => ['kelola_jadwal', 'lihat_jadwal'], 'permission_logic' => 'any'],
                ['key' => 'schedule.my', 'label' => 'Jadwal Saya', 'route' => 'admin.schedule.my-schedule', 'permissions' => ['lihat_jadwal']],
                ['key' => 'schedule.availability', 'label' => 'Ketersediaan', 'route' => 'admin.schedule.availability', 'permissions' => ['lihat_jadwal']],
                ['key' => 'schedule.leave', 'label' => 'Izin/Cuti', 'route' => 'admin.leave.index', 'active_routes' => ['admin.leave.*'], 'permissions' => ['kelola_cuti', 'ajukan_cuti'], 'permission_logic' => 'any'],
                ['key' => 'schedule.swap', 'label' => 'Perubahan Jadwal', 'route' => 'admin.swap.index', 'active_routes' => ['admin.swap.*'], 'permissions' => ['kelola_tukar_jadwal', 'ajukan_tukar_jadwal'], 'permission_logic' => 'any'],
            ],
        ],
        [
            'key' => 'cashier',
            'label' => 'Kasir / POS',
            'icon' => 'currency-dollar',
            'route' => null,
            'permissions' => ['kelola_penjualan', 'lihat_penjualan'],
            'permission_logic' => 'any',
            'children' => [
                ['key' => 'cashier.pos', 'label' => 'POS Kasir', 'route' => 'admin.cashier.pos', 'permissions' => ['lihat_penjualan']],
                ['key' => 'cashier.entry', 'label' => 'Entry Transaksi', 'route' => 'admin.cashier.pos-entry', 'permissions' => ['kelola_penjualan'], 'roles' => ['Super Admin', 'Ketua', 'Wakil Ketua']],
            ],
        ],
        [
            'key' => 'shu',
            'label' => 'Poin SHU',
            'icon' => 'heart',
            'route' => 'admin.poin-shu.monitoring',
            'permissions' => ['kelola_poin_shu', 'lihat_poin_shu'],
            'permission_logic' => 'any',
        ],
        [
            'key' => 'inventory',
            'label' => 'Inventaris',
            'icon' => 'cube',
            'route' => null,
            'permissions' => ['kelola_produk', 'lihat_produk', 'kelola_stok'],
            'permission_logic' => 'any',
            'children' => [
                ['key' => 'inventory.products', 'label' => 'Daftar Produk', 'route' => 'admin.products.index', 'active_routes' => ['admin.products.*'], 'permissions' => ['kelola_produk', 'lihat_produk'], 'permission_logic' => 'any'],
                ['key' => 'inventory.stock', 'label' => 'Manajemen Stok', 'route' => 'admin.stock.index', 'active_routes' => ['admin.stock.*'], 'permissions' => ['kelola_stok', 'lihat_pembelian'], 'permission_logic' => 'any'],
            ],
        ],
        [
            'key' => 'penalties',
            'label' => 'Penalti',
            'icon' => 'exclamation-triangle',
            'route' => 'admin.penalties.index',
            'active_routes' => ['admin.penalties.*'],
            'permissions' => ['kelola_pelanggaran', 'lihat_pelanggaran'],
            'permission_logic' => 'any',
        ],
        [
            'key' => 'reports',
            'label' => 'Laporan',
            'icon' => 'document',
            'route' => null,
            'permissions' => ['kelola_laporan', 'lihat_laporan'],
            'permission_logic' => 'any',
            'children' => [
                ['key' => 'reports.attendance', 'label' => 'Laporan Absensi', 'route' => 'admin.reports.attendance', 'permissions' => ['kelola_laporan', 'lihat_laporan'], 'permission_logic' => 'any'],
                ['key' => 'reports.sales', 'label' => 'Laporan Penjualan', 'route' => 'admin.reports.sales', 'permissions' => ['kelola_laporan', 'lihat_laporan'], 'permission_logic' => 'any'],
                ['key' => 'reports.penalties', 'label' => 'Laporan Penalti', 'route' => 'admin.reports.penalties', 'permissions' => ['kelola_laporan', 'kelola_pelanggaran'], 'permission_logic' => 'any'],
            ],
        ],
        ['key' => 'divider.admin', 'type' => 'divider'],
        [
            'key' => 'activity-log',
            'label' => 'Log Aktivitas',
            'icon' => 'clipboard-document-list',
            'route' => 'admin.activity-log',
            'permissions' => ['lihat_log_audit'],
            'roles' => ['Super Admin', 'Ketua'],
        ],
        [
            'key' => 'users',
            'label' => 'Manajemen User',
            'icon' => 'user-group',
            'route' => 'admin.users.index',
            'active_routes' => ['admin.users.*'],
            'permissions' => ['kelola_pengguna', 'lihat_pengguna'],
            'permission_logic' => 'any',
        ],
        [
            'key' => 'roles',
            'label' => 'Role & Permission',
            'icon' => 'check-circle',
            'route' => 'admin.roles.index',
            'active_routes' => ['admin.roles.*'],
            'permissions' => ['kelola_peran', 'lihat_peran'],
            'permission_logic' => 'any',
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
        [
            'key' => 'profile',
            'label' => 'Profil Saya',
            'icon' => 'user',
            'route' => 'admin.profile.edit',
            'active_routes' => ['admin.profile.*'],
            'permissions' => [],
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
    */
    'super_admin_role' => 'Super Admin',
];
