<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoCheckOutCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:auto-checkout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis check-out absensi yang lupa di-checkout setelah 3 jam sesi berakhir';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\AttendanceService $attendanceService)
    {
        $this->info("Memulai proses auto-checkout...");
        
        $count = $attendanceService->processAutoCheckOuts();

        if ($count > 0) {
            $this->info("Berhasil memproses {$count} auto-checkout.");
        } else {
            $this->info("Tidak ada absensi yang perlu di-auto-checkout.");
        }
    }
}
