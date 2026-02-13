<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SIWIRUS Application Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for SIWIRUS (Sistem Informasi Koperasi Mahasiswa)
    | This file contains all application-specific constants and settings.
    |
    */

    /**
     * Session time labels
     */
    'sessions' => [
        1 => '07:30 - 10:00',
        2 => '10:20 - 12:50',
        3 => '13:30 - 16:00',
    ],

    /**
     * Session start and end times
     */
    'session_times' => [
        1 => [
            'start' => '07:30',
            'end' => '10:00',
        ],
        2 => [
            'start' => '10:20',
            'end' => '12:50',
        ],
        3 => [
            'start' => '13:30',
            'end' => '16:00',
        ],
    ],

    /**
     * Late threshold in minutes
     * If check-in is more than this many minutes after session start, mark as late
     */
    'late_threshold_minutes' => 15,

    /**
     * Penalty point thresholds
     */
    'penalty' => [
        'warning_threshold' => 50,
        'suspension_threshold' => 100,
        'expiry_months' => 6,  // Penalty points expire after this many months
    ],

    /**
     * Attendance settings
     */
    'attendance' => [
        'override_mode' => true, // Allow check-in without schedule
        'auto_absent_after_hours' => 2,  // Mark as absent if no check-in after this many hours
        'allow_early_checkin_minutes' => 30,  // Allow check-in this many minutes before session start
        'require_photo' => true,  // Require photo for check-in
        'max_photo_size_mb' => 5,  // Maximum photo size in MB
    ],

    /**
     * Schedule settings
     */
    'schedule' => [
        'min_assignments_per_week' => 2,
        'max_assignments_per_week' => 5,
        'advance_generation_days' => 14,  // Generate schedule this many days in advance
    ],

    /**
     * Leave request settings
     */
    'leave' => [
        'max_days_per_month' => 5,
        'min_advance_notice_days' => 3,  // Must request leave at least this many days in advance
        'require_document' => true,  // Require supporting document for leave
    ],

    /**
     * Swap request settings
     */
    'swap' => [
        'require_target_approval' => true,
        'require_admin_approval' => true,
        'min_advance_notice_days' => 2,
    ],

    /**
     * Product/Inventory settings
     */
    'inventory' => [
        'low_stock_multiplier' => 1.0,  // Alert when stock <= min_stock * multiplier
        'auto_reorder' => false,
    ],

    /**
     * Sales/Cashier settings
     */
    'sales' => [
        'invoice_prefix' => 'INV',
        'allow_negative_stock' => false,
        'require_payment_proof' => false,
    ],

    /**
     * Purchase settings
     */
    'purchase' => [
        'invoice_prefix' => 'PO',
        'require_approval' => false,
        'auto_update_stock' => true,
    ],

    /**
     * Report settings
     */
    'reports' => [
        'default_format' => 'pdf',  // pdf, excel, csv
        'storage_path' => 'reports',
        'auto_delete_after_days' => 30,
    ],

    /**
     * Notification settings
     */
    'notifications' => [
        'auto_delete_read_after_days' => 30,
        'max_unread_display' => 10,
    ],

    /**
     * Pagination settings
     */
    'pagination' => [
        'per_page' => 15,
        'per_page_options' => [10, 15, 25, 50, 100],
    ],

    /**
     * Cache settings (in minutes)
     */
    'cache' => [
        'dashboard_stats' => 5,
        'user_stats' => 10,
        'product_list' => 15,
        'reports' => 60,
    ],

    /**
     * Date/Time formats
     * Note: These are fallback formats. The actual formats are managed
     * dynamically via System Settings (DateTimeHelper).
     * Use the helper functions format_date(), format_time(), format_datetime()
     * or Blade directives @formatDate, @formatTime, @formatDateTime
     */
    'formats' => [
        'date' => 'd/m/Y',
        'datetime' => 'd/m/Y H:i',
        'time' => 'H:i',
        'database_date' => 'Y-m-d',
        'database_datetime' => 'Y-m-d H:i:s',
    ],

    /**
     * File upload settings
     */
    'uploads' => [
        'max_size_mb' => 5,
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
        'path' => 'uploads',
    ],

];
