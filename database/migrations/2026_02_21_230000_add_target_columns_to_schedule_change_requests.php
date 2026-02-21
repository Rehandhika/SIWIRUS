<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_change_requests', function (Blueprint $table) {
            $table->foreignId('target_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('target_assignment_id')->nullable()->constrained('schedule_assignments')->onDelete('cascade');
            $table->text('target_response')->nullable();
            $table->timestamp('target_responded_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_change_requests', function (Blueprint $table) {
            $table->dropForeign(['target_id']);
            $table->dropForeign(['target_assignment_id']);
            $table->dropColumn(['target_id', 'target_assignment_id', 'target_response', 'target_responded_at']);
        });
    }
};
