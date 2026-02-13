<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'app_name',
                'value' => 'SIWIRUS',
                'type' => 'string',
                'description' => 'Nama aplikasi',
            ],
            [
                'key' => 'app_description',
                'value' => 'Sistem Informasi Koperasi Mahasiswa',
                'type' => 'string',
                'description' => 'Deskripsi aplikasi',
            ],
            [
                'key' => 'penalty.reset_period_months',
                'value' => '6',
                'type' => 'integer',
                'description' => 'Periode reset poin penalty dalam bulan',
            ],
            [
                'key' => 'penalty.max_points_threshold',
                'value' => '50',
                'type' => 'integer',
                'description' => 'Batas maksimal poin penalty sebelum suspend',
            ],
            [
                'key' => 'penalty.warning_threshold',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Batas poin penalty untuk peringatan',
            ],
            [
                'key' => 'schedule.auto_publish',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Otomatis publish jadwal setelah generate',
            ],
            [
                'key' => 'schedule.work_days',
                'value' => 'monday,tuesday,wednesday,thursday',
                'type' => 'string',
                'description' => 'Hari kerja dalam seminggu',
            ],
            [
                'key' => 'schedule.sessions_per_day',
                'value' => '3',
                'type' => 'integer',
                'description' => 'Jumlah sesi per hari',
            ],
            [
                'key' => 'schedule.session_1_start',
                'value' => '07:30',
                'type' => 'time',
                'description' => 'Waktu mulai sesi 1',
            ],
            [
                'key' => 'schedule.session_1_end',
                'value' => '10:00',
                'type' => 'time',
                'description' => 'Waktu selesai sesi 1',
            ],
            [
                'key' => 'schedule.session_2_start',
                'value' => '10:20',
                'type' => 'time',
                'description' => 'Waktu mulai sesi 2',
            ],
            [
                'key' => 'schedule.session_2_end',
                'value' => '12:50',
                'type' => 'time',
                'description' => 'Waktu selesai sesi 2',
            ],
            [
                'key' => 'schedule.session_3_start',
                'value' => '13:30',
                'type' => 'time',
                'description' => 'Waktu mulai sesi 3',
            ],
            [
                'key' => 'schedule.session_3_end',
                'value' => '16:00',
                'type' => 'time',
                'description' => 'Waktu selesai sesi 3',
            ],
            [
                'key' => 'attendance.grace_period_minutes',
                'value' => '15',
                'type' => 'integer',
                'description' => 'Grace period untuk check-in (menit)',
            ],
            [
                'key' => 'attendance.late_threshold_minutes',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Batas terlambat check-in (menit)',
            ],
            [
                'key' => 'notification.email_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Aktifkan notifikasi email',
            ],
            [
                'key' => 'notification.database_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Aktifkan notifikasi database',
            ],
            [
                'key' => 'currency',
                'value' => 'IDR',
                'type' => 'string',
                'description' => 'Mata uang default',
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Jakarta',
                'type' => 'string',
                'description' => 'Timezone aplikasi',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
