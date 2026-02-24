<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adding 'in_progress' to the enum
        // Using raw SQL as altering enum in Laravel can be tricky across different DB drivers
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE schedule_assignments MODIFY COLUMN status ENUM('scheduled', 'in_progress', 'completed', 'missed', 'swapped', 'excused') DEFAULT 'scheduled'");
        } else {
            // Fallback for sqlite/others if supported
            Schema::table('schedule_assignments', function (Blueprint $table) {
                $table->string('status')->default('scheduled')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE schedule_assignments MODIFY COLUMN status ENUM('scheduled', 'completed', 'missed', 'swapped', 'excused') DEFAULT 'scheduled'");
        } else {
            Schema::table('schedule_assignments', function (Blueprint $table) {
                $table->enum('status', ['scheduled', 'completed', 'missed', 'swapped', 'excused'])->default('scheduled')->change();
            });
        }
    }
};
