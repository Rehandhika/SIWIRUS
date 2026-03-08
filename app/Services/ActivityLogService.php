<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogService
{
    /**
     * Log an activity with detailed description and optional metadata
     *
     * @param  string  $activity  The activity description
     * @param  array|null  $metadata  Optional structured data
     * @param  int|null  $userId  Optional user ID to log for (defaults to Auth::id())
     */
    public static function log(string $activity, ?array $metadata = null, ?int $userId = null): ?ActivityLog
    {
        try {
            return ActivityLog::create([
                'user_id' => $userId ?? Auth::id(),
                'activity' => $activity,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $metadata,
            ]);
        } catch (\Exception $e) {
            // Log error but don't interrupt user action
            Log::error('Failed to create activity log: '.$e->getMessage());

            return null;
        }
    }

    // ==========================================
    // Authentication
    // ==========================================

    /**
     * Log user login
     */
    public static function logLogin(): void
    {
        self::log('Masuk ke sistem');
    }

    /**
     * Log user logout
     */
    public static function logLogout(): void
    {
        self::log('Keluar dari sistem');
    }

    // ==========================================
    // Schedule Management
    // ==========================================

    /**
     * Log schedule creation
     */
    public static function logScheduleCreated(string $scheduleName, string $date): void
    {
        self::log("Membuat jadwal '{$scheduleName}' untuk tanggal {$date}");
    }

    /**
     * Log schedule update
     */
    public static function logScheduleUpdated(string $scheduleName, string $date): void
    {
        self::log("Mengubah jadwal '{$scheduleName}' tanggal {$date}");
    }

    /**
     * Log schedule deletion
     */
    public static function logScheduleDeleted(string $scheduleName, string $date): void
    {
        self::log("Menghapus jadwal '{$scheduleName}' tanggal {$date}");
    }

    /**
     * Log schedule publish
     */
    public static function logSchedulePublished(string $date): void
    {
        self::log("Mempublish jadwal minggu {$date}");
    }

    // ==========================================
    // Leave Request Management
    // ==========================================

    /**
     * Log leave request creation
     */
    public static function logLeaveCreated(string $userName, string $startDate, string $endDate): void
    {
        self::log("Mengajukan cuti dari {$startDate} sampai {$endDate}");
    }

    /**
     * Log leave request approval
     */
    public static function logLeaveApproved(string $userName, string $startDate): void
    {
        self::log("Menyetujui cuti {$userName} mulai {$startDate}");
    }

    /**
     * Log leave request rejection
     */
    public static function logLeaveRejected(string $userName, string $startDate): void
    {
        self::log("Menolak cuti {$userName} mulai {$startDate}");
    }

    // ==========================================
    // Sales/Transaction
    // ==========================================

    /**
     * Log sale creation
     */
    public static function logSaleCreated(string $invoiceNumber, float $totalAmount, ?string $studentNim = null): void
    {
        $formattedAmount = number_format($totalAmount, 0, ',', '.');
        $activity = "Membuat transaksi #{$invoiceNumber} senilai Rp {$formattedAmount}";
        
        if ($studentNim) {
            $activity .= " (Mahasiswa: {$studentNim})";
        }

        self::log($activity, [
            'invoice' => $invoiceNumber,
            'amount' => $totalAmount,
            'student_nim' => $studentNim,
        ]);
    }

    /**
     * Log sale deletion
     */
    public static function logSaleDeleted(string $invoiceNumber): void
    {
        self::log("Menghapus transaksi #{$invoiceNumber}");
    }

    // ==========================================
    // Product Management
    // ==========================================

    /**
     * Log product creation
     */
    public static function logProductCreated(string $productName): void
    {
        self::log("Menambah produk baru '{$productName}'");
    }

    /**
     * Log product update
     */
    public static function logProductUpdated(string $productName): void
    {
        self::log("Mengubah produk '{$productName}'");
    }

    /**
     * Log product deletion
     */
    public static function logProductDeleted(string $productName): void
    {
        self::log("Menghapus produk '{$productName}'");
    }

    // ==========================================
    // Stock Management
    // ==========================================

    /**
     * Log stock adjustment
     */
    public static function logStockAdjusted(string $productName, int $quantity, string $type): void
    {
        self::log("Menyesuaikan stok '{$productName}' {$type} {$quantity} unit");
    }

    // ==========================================
    // Penalty Management
    // ==========================================

    /**
     * Log penalty creation
     */
    public static function logPenaltyCreated(string $userName, int $points, string $reason): void
    {
        self::log("Memberikan penalti {$points} poin kepada {$userName}: {$reason}");
    }

    /**
     * Log penalty removal
     */
    public static function logPenaltyRemoved(string $userName, int $points): void
    {
        self::log("Menghapus penalti {$points} poin dari {$userName}");
    }

    // ==========================================
    // User Management
    // ==========================================

    /**
     * Log user creation
     */
    public static function logUserCreated(string $userName): void
    {
        self::log("Menambah pengguna baru '{$userName}'");
    }

    /**
     * Log user update
     */
    public static function logUserUpdated(string $userName): void
    {
        self::log("Mengubah data pengguna '{$userName}'");
    }

    /**
     * Log user deletion
     */
    public static function logUserDeleted(string $userName): void
    {
        self::log("Menghapus pengguna '{$userName}'");
    }

    /**
     * Log user role change
     */
    public static function logUserRoleChanged(string $userName, string $newRole): void
    {
        self::log("Mengubah role {$userName} menjadi {$newRole}");
    }

    // ==========================================
    // Swap/Schedule Change Request
    // ==========================================

    /**
     * Log swap request creation
     */
    public static function logSwapCreated(string $fromDate, string $toDate): void
    {
        self::log("Mengajukan perubahan jadwal dari {$fromDate} ke {$toDate}");
    }

    /**
     * Log swap request approval
     */
    public static function logSwapApproved(string $userName, string $date): void
    {
        self::log("Menyetujui perubahan jadwal {$userName} tanggal {$date}");
    }

    /**
     * Log swap request rejection
     */
    public static function logSwapRejected(string $userName, string $date): void
    {
        self::log("Menolak perubahan jadwal {$userName} tanggal {$date}");
    }

    // ==========================================
    // Settings
    // ==========================================

    /**
     * Log settings update
     */
    public static function logSettingsUpdated(string $settingGroup): void
    {
        self::log("Mengubah pengaturan {$settingGroup}");
    }

    // ==========================================
    // Profile Management
    // ==========================================

    /**
     * Log profile update
     */
    public static function logProfileUpdated(): void
    {
        self::log('Mengubah profil');
    }

    /**
     * Log password change
     */
    public static function logPasswordChanged(): void
    {
        self::log('Mengubah password');
    }

    /**
     * Log profile photo update
     */
    public static function logProfilePhotoUpdated(): void
    {
        self::log('Mengubah foto profil');
    }

    /**
     * Log profile photo deletion
     */
    public static function logProfilePhotoDeleted(): void
    {
        self::log('Menghapus foto profil');
    }

    // ==========================================
    // User Status Management
    // ==========================================

    /**
     * Log user status change
     */
    public static function logUserStatusChanged(string $userName, string $newStatus): void
    {
        $statusText = $newStatus === 'active' ? 'mengaktifkan' : 'menonaktifkan';
        self::log("Berhasil {$statusText} pengguna '{$userName}'");
    }

    // ==========================================
    // Role & Permission Management
    // ==========================================

    /**
     * Log role creation
     */
    public static function logRoleCreated(string $roleName): void
    {
        self::log("Membuat role baru '{$roleName}'");
    }

    /**
     * Log role update
     */
    public static function logRoleUpdated(string $roleName): void
    {
        self::log("Mengubah role '{$roleName}'");
    }

    /**
     * Log role deletion
     */
    public static function logRoleDeleted(string $roleName): void
    {
        self::log("Menghapus role '{$roleName}'");
    }

    // ==========================================
    // Banner Management
    // ==========================================

    /**
     * Log banner creation
     */
    public static function logBannerCreated(string $title): void
    {
        $displayTitle = $title ?: 'Tanpa Judul';
        self::log("Membuat banner baru '{$displayTitle}'");
    }

    /**
     * Log banner update
     */
    public static function logBannerUpdated(string $title): void
    {
        $displayTitle = $title ?: 'Tanpa Judul';
        self::log("Mengubah banner '{$displayTitle}'");
    }

    /**
     * Log banner deletion
     */
    public static function logBannerDeleted(string $title): void
    {
        $displayTitle = $title ?: 'Tanpa Judul';
        self::log("Menghapus banner '{$displayTitle}'");
    }

    /**
     * Log banner status toggle
     */
    public static function logBannerStatusChanged(string $title, bool $isActive): void
    {
        $displayTitle = $title ?: 'Tanpa Judul';
        $statusText = $isActive ? 'mengaktifkan' : 'menonaktifkan';
        self::log("Berhasil {$statusText} banner '{$displayTitle}'");
    }

    // ==========================================
    // News Management
    // ==========================================

    /**
     * Log news creation
     */
    public static function logNewsCreated(?string $title): void
    {
        $displayTitle = $title ?: 'Tanpa Judul';
        self::log("Membuat berita baru '{$displayTitle}'");
    }

    /**
     * Log news update
     */
    public static function logNewsUpdated(?string $title): void
    {
        $displayTitle = $title ?: 'Tanpa Judul';
        self::log("Mengubah berita '{$displayTitle}'");
    }

    /**
     * Log news deletion
     */
    public static function logNewsDeleted(?string $title): void
    {
        $displayTitle = $title ?: 'Tanpa Judul';
        self::log("Menghapus berita '{$displayTitle}'");
    }

    /**
     * Log news status toggle
     */
    public static function logNewsStatusChanged(?string $title, bool $isActive): void
    {
        $displayTitle = $title ?: 'Tanpa Judul';
        $statusText = $isActive ? 'mengaktifkan' : 'menonaktifkan';
        self::log("Berhasil {$statusText} berita '{$displayTitle}'");
    }

    // ==========================================
    // Attendance Management
    // ==========================================

    /**
     * Log attendance check-in
     * 
     * @param string $session
     * @param string $time
     * @param int|null $userId
     */
    public static function logCheckIn(string $session, string $time, ?int $userId = null): void
    {
        self::log("Check-in {$session} pada pukul {$time}", null, $userId);
    }

    /**
     * Log attendance check-out
     * 
     * @param string $session
     * @param string $time
     * @param string $workHours
     * @param int|null $userId
     */
    public static function logCheckOut(string $session, string $time, string $workHours, ?int $userId = null): void
    {
        self::log("Check-out {$session} pada pukul {$time} (total {$workHours} jam)", null, $userId);
    }

    /**
     * Log attendance edit
     */
    public static function logAttendanceEdited(string $userName, string $date): void
    {
        self::log("Mengubah data absensi {$userName} tanggal {$date}");
    }

    /**
     * Log attendance export
     */
    public static function logAttendanceExported(string $dateFrom, string $dateTo): void
    {
        self::log("Mengekspor data absensi periode {$dateFrom} - {$dateTo}");
    }

    // ==========================================
    // Maintenance Mode
    // ==========================================

    /**
     * Log maintenance mode toggle
     */
    public static function logMaintenanceModeChanged(bool $enabled): void
    {
        $statusText = $enabled ? 'mengaktifkan' : 'menonaktifkan';
        self::log("Berhasil {$statusText} mode maintenance");
    }

    // ==========================================
    // Purchase Management
    // ==========================================

    /**
     * Log purchase creation
     */
    public static function logPurchaseCreated(string $invoiceNumber, float $totalAmount): void
    {
        $formattedAmount = number_format($totalAmount, 0, ',', '.');
        self::log("Membuat purchase order #{$invoiceNumber} senilai Rp {$formattedAmount}");
    }

    /**
     * Log purchase approval
     */
    public static function logPurchaseApproved(string $invoiceNumber): void
    {
        self::log("Menyetujui purchase order #{$invoiceNumber}");
    }

    // ==========================================
    // Report Export
    // ==========================================

    /**
     * Log report export
     */
    public static function logReportExported(string $reportType, string $period): void
    {
        self::log("Mengekspor laporan {$reportType} periode {$period}");
    }
}
