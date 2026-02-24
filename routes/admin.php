<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard\DashboardIndex;

/*
|--------------------------------------------------------------------------
| Admin / Management Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->prefix('admin')->name('admin.')->group(function () {
    
    // ============================================================
    // SELF-SERVICE ROUTES (All authenticated users)
    // ============================================================
    
    // Dashboard (Beranda) - All authenticated users
    Route::get('/beranda', DashboardIndex::class)->name('dashboard');

    // Profile (Profil) - All authenticated users (ubah_profil)
    Route::prefix('profil')->name('profile.')->group(function () {
        Route::get('/ubah', \App\Livewire\Profile\EditProfile::class)->name('edit');
    });

    // Notifications (Notifikasi) - All authenticated users
    Route::prefix('notifikasi')->name('notifications.')->group(function () {
        Route::get('/', \App\Livewire\Notification\NotificationList::class)->name('index');
        Route::get('/notifikasi-saya', \App\Livewire\Notification\UserNotifications::class)->name('my-notifications');
    });

    // Attendance Self-Service (check_in_out, lihat_absensi_sendiri)
    Route::prefix('absensi')->name('attendance.')->group(function () {
        // Self-service - check-in/out for own attendance
        Route::get('/check-in-out', \App\Livewire\Attendance\CheckInOut::class)->name('check-in-out');
        // Self-service - view own attendance history
        Route::get('/riwayat', \App\Livewire\Attendance\AttendanceHistory::class)->name('history');
    });

    // Schedule Self-Service (lihat_jadwal_sendiri, input_ketersediaan)
    Route::prefix('jadwal')->name('schedule.')->group(function () {
        // Self-service - view own schedule
        Route::get('/jadwal-saya', \App\Livewire\Schedule\UserSchedule::class)->name('my-schedule');
        // Self-service - input availability
        Route::get('/ketersediaan', \App\Livewire\Schedule\AvailabilityManager::class)->name('availability');
        
        // Management - requires lihat_semua_jadwal or kelola_jadwal permission
        Route::middleware('can:lihat_semua_jadwal')->group(function () {
            Route::get('/', \App\Livewire\Schedule\ScheduleList::class)->name('index');
            Route::get('/kalender', \App\Livewire\Schedule\ScheduleCalendar::class)->name('calendar');
            Route::get('/statistik', \App\Livewire\Schedule\ScheduleStatistics::class)->name('statistics');
        });
        
        // Create/Edit schedule - requires kelola_jadwal permission
        Route::middleware('can:kelola_jadwal')->group(function () {
            Route::get('/buat', \App\Livewire\Schedule\CreateSchedule::class)->name('create');
            Route::get('/generator', \App\Livewire\Schedule\ScheduleGenerator::class)->name('generator');
            Route::get('/template', \App\Livewire\Schedule\ScheduleTemplates::class)->name('templates');
            Route::get('/{schedule}/edit', \App\Livewire\Schedule\EditSchedule::class)->name('edit');
            Route::get('/{schedule}/riwayat', \App\Livewire\Schedule\EditHistory::class)->name('history');
            Route::get('/perubahan', \App\Livewire\Schedule\ScheduleChangeManager::class)->name('changes');
        });
    });

    // Cashier/POS (akses_kasir) - All authenticated users can access POS
    Route::prefix('kasir')->name('cashier.')->group(function () {
        Route::get('/pos', \App\Livewire\Cashier\PointOfSale::class)->name('pos');
        Route::get('/entry', \App\Livewire\Cashier\PosEntry::class)->name('pos-entry');
    });

    // Leave Requests Self-Service (ajukan_cuti)
    Route::prefix('cuti')->name('leave.')->group(function () {
        // Self-service - submit and view own leave requests
        Route::get('/', \App\Livewire\Leave\LeaveRequestList::class)->name('index');
        Route::get('/pengajuan-baru', \App\Livewire\Leave\CreateLeaveRequest::class)->name('create');
        Route::get('/pengajuan-saya', \App\Livewire\Leave\UserLeaveRequests::class)->name('my-requests');
        
        // Approval routes - requires setujui_cuti permission
        Route::middleware('can:setujui_cuti')->group(function () {
            Route::get('/persetujuan', \App\Livewire\Leave\PendingApprovals::class)->name('approvals');
        });
        
        // Management - requires kelola_cuti permission
        Route::middleware('can:kelola_cuti')->group(function () {
            Route::get('/manajemen', \App\Livewire\Leave\LeaveManager::class)->name('manager');
        });
    });

    // Swap Requests Self-Service (ajukan_tukar_jadwal)
    Route::prefix('tukar-jadwal')->name('swap.')->group(function () {
        // Self-service - submit and view own swap requests
        Route::get('/', \App\Livewire\Swap\SwapRequestList::class)->name('index');
        Route::get('/dashboard', \App\Livewire\Swap\SwapDashboard::class)->name('dashboard');
        Route::get('/pengajuan-baru', \App\Livewire\Swap\CreateSwapRequest::class)->name('create');
        Route::get('/pengajuan-saya', \App\Livewire\Swap\UserSwapRequests::class)->name('my-requests');
        
        // Approval routes - requires setujui_tukar_jadwal permission
        Route::middleware('can:setujui_tukar_jadwal')->group(function () {
            Route::get('/persetujuan', \App\Livewire\Swap\SwapApprovals::class)->name('approvals');
        });
        
        // Management - requires kelola_tukar_jadwal permission
        Route::middleware('can:kelola_tukar_jadwal')->group(function () {
            Route::get('/manajemen', \App\Livewire\Swap\SwapManager::class)->name('manager');
        });
    });

    // Penalties Self-Service (lihat_penalti_sendiri)
    Route::get('/penalti-saya', \App\Livewire\Penalty\UserPenalties::class)->name('my-penalties');

    // ============================================================
    // MANAGEMENT ROUTES (Require specific permissions)
    // ============================================================

    // Stock Management (lihat_stok, kelola_stok)
    Route::prefix('stok')->name('stock.')->middleware('can:lihat_stok')->group(function () {
        Route::get('/', \App\Livewire\Stock\StockManager::class)->name('index');
    });

    // Products (lihat_produk, kelola_produk)
    Route::prefix('produk')->name('products.')->middleware('can:lihat_produk')->group(function () {
        Route::get('/', \App\Livewire\Product\ProductList::class)->name('index');
        Route::get('/daftar', \App\Livewire\Product\ProductList::class)->name('list');
        
        // Create/Edit products - requires kelola_produk permission
        Route::middleware('can:kelola_produk')->group(function () {
            Route::get('/buat', \App\Livewire\Product\CreateProduct::class)->name('create');
            Route::get('/{product}/edit', \App\Livewire\Product\EditProduct::class)->name('edit');
        });
    });

    // Purchases (lihat_pembelian, kelola_pembelian)
    Route::prefix('pembelian')->name('purchase.')->middleware('can:lihat_pembelian')->group(function () {
        Route::get('/', \App\Livewire\Purchase\PurchaseList::class)->name('index');
    });

    // Reports (lihat_laporan)
    Route::prefix('laporan')->name('reports.')->middleware('can:lihat_laporan')->group(function () {
        Route::get('/absensi', \App\Livewire\Admin\AttendanceManagement::class)->name('attendance');
        Route::get('/penjualan', \App\Livewire\Report\SalesReport::class)->name('sales');
        Route::get('/penalti', \App\Livewire\Report\PenaltyReport::class)->name('penalties');
    });

    // Users Management (lihat_pengguna, kelola_pengguna)
    Route::prefix('pengguna')->name('users.')->middleware('can:lihat_pengguna')->group(function () {
        Route::get('/', \App\Livewire\User\UserList::class)->name('index');
    });

    // Roles & Permissions (lihat_peran, kelola_peran)
    Route::prefix('peran')->name('roles.')->middleware('can:lihat_peran')->group(function () {
        Route::get('/', \App\Livewire\Role\RoleList::class)->name('index');
    });

    // Activity Log (lihat_log_aktivitas) - Admin level
    Route::get('/log-aktivitas', \App\Livewire\Admin\ActivityLogViewer::class)
        ->middleware('can:lihat_log_aktivitas')
        ->name('activity-log');

    // Settings (kelola_pengaturan)
    Route::prefix('pengaturan')->name('settings.')->middleware('can:kelola_pengaturan')->group(function () {
        Route::get('/sistem', \App\Livewire\Settings\SystemSettings::class)->name('system');
        Route::get('/toko', \App\Livewire\Admin\Settings\StoreSettings::class)->name('store');
        Route::get('/banner', \App\Livewire\Admin\BannerNewsManagement::class)->name('banners');
        Route::get('/pembayaran', \App\Livewire\Settings\PaymentSettings::class)->name('payment');
    });

    // Poin SHU (lihat_poin_shu, kelola_poin_shu)
    Route::prefix('poin-shu')->name('poin-shu.')->middleware('can:lihat_poin_shu')->group(function () {
        Route::get('/monitoring', \App\Livewire\ShuPoint\Monitoring::class)->name('monitoring');
        Route::get('/mahasiswa/{student}', \App\Livewire\ShuPoint\StudentDetail::class)->name('student');
    });
});
