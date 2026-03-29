<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\ScheduleAssignment;
use App\Services\AttendanceService;
use App\Services\PenaltyService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAbsencesJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:process-absences {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process absences for scheduled assignments without attendance and without approved leave. Default: yesterday. Use "today" to process today\'s ended sessions.';

    protected PenaltyService $penaltyService;

    protected AttendanceService $attendanceService;

    /**
     * Create a new command instance.
     */
    public function __construct(PenaltyService $penaltyService, AttendanceService $attendanceService)
    {
        parent::__construct();
        $this->penaltyService = $penaltyService;
        $this->attendanceService = $attendanceService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dateArg = $this->argument('date');
        
        // Support "today" keyword for convenience
        if ($dateArg === 'today') {
            $date = today();
        } else {
            $dateString = $dateArg ?? Carbon::yesterday()->toDateString();
            $date = Carbon::parse($dateString);
        }

        $isToday = $date->isToday();
        $currentTime = now()->format('H:i:s');

        $this->info("Processing absences for date: {$date->format('Y-m-d')} ({$date->locale('id')->isoFormat('dddd')})");
        if ($isToday) {
            $this->warn("⚠  Processing for TODAY — only ended sessions (time_end < {$currentTime}) will be processed.");
        }

        // Build query for scheduled assignments
        $query = ScheduleAssignment::where('date', $date)
            ->where('status', 'scheduled')
            ->whereHas('schedule', fn($q) => $q->where('status', 'published'));

        // If processing today, only consider sessions that have already ended
        if ($isToday) {
            $query->where('time_end', '<', $currentTime);
        }

        $scheduledAssignments = $query->get();

        $this->info("Found {$scheduledAssignments->count()} scheduled assignments to check");

        if ($scheduledAssignments->isEmpty()) {
            $this->info("No assignments to process.");
            
            // Show diagnostic info
            $totalForDate = ScheduleAssignment::where('date', $date)->count();
            $scheduledForDate = ScheduleAssignment::where('date', $date)->where('status', 'scheduled')->count();
            
            $this->newLine();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total assignments on this date', $totalForDate],
                    ['Status = scheduled', $scheduledForDate],
                    ['Day of week', $date->locale('id')->isoFormat('dddd')],
                    ['Is workday (Mon-Thu)', in_array($date->dayOfWeek, [1, 2, 3, 4]) ? 'Yes' : 'No'],
                ]
            );

            if ($totalForDate === 0) {
                $this->warn("⚠  No schedule assignments exist for this date at all.");
                $this->warn("   Possible causes: no schedule published, or this is not a workday.");
            } elseif ($scheduledForDate === 0) {
                $this->info("ℹ  All assignments have been processed (status ≠ 'scheduled').");
            } elseif ($isToday) {
                $this->info("ℹ  {$scheduledForDate} assignments are still 'scheduled' but their sessions haven't ended yet.");
            }

            return Command::SUCCESS;
        }

        $processedCount = 0;
        $skippedCount = 0;

        foreach ($scheduledAssignments as $assignment) {
            // Check if there's already an attendance record
            $hasAttendance = Attendance::where('user_id', $assignment->user_id)
                ->where('schedule_assignment_id', $assignment->id)
                ->exists();

            if ($hasAttendance) {
                $this->line("  Skipping assignment #{$assignment->id} (user #{$assignment->user_id}) - already has attendance");
                $skippedCount++;
                continue;
            }

            // Check if user has approved leave for this date
            $hasApprovedLeave = $this->attendanceService->hasApprovedLeave($assignment->user_id, $date);

            if ($hasApprovedLeave) {
                $this->line("  Skipping assignment #{$assignment->id} (user #{$assignment->user_id}) - has approved leave");
                $skippedCount++;
                continue;
            }

            // Process absence
            try {
                $this->processAbsence($assignment, $date);
                $processedCount++;
                $this->info("  ✓ Processed absence for user #{$assignment->user_id}, assignment #{$assignment->id} (Sesi {$assignment->session})");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to process absence for assignment #{$assignment->id}: {$e->getMessage()}");
                Log::error('Failed to process absence', [
                    'assignment_id' => $assignment->id,
                    'user_id' => $assignment->user_id,
                    'date' => $date->toDateString(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        $this->info('Absence processing completed:');
        $this->info("  - Processed: {$processedCount}");
        $this->info("  - Skipped: {$skippedCount}");
        $this->info("  - Total checked: {$scheduledAssignments->count()}");

        return Command::SUCCESS;
    }

    /**
     * Process absence for a single assignment
     * Creates attendance with status 'absent', creates ABSENT penalty, and updates assignment status to 'missed'
     *
     * @throws \Exception
     */
    protected function processAbsence(ScheduleAssignment $assignment, Carbon $date): void
    {
        DB::transaction(function () use ($assignment, $date) {
            // Create attendance with status 'absent'
            $attendance = Attendance::create([
                'user_id' => $assignment->user_id,
                'schedule_assignment_id' => $assignment->id,
                'date' => $date,
                'status' => 'absent',
                'notes' => 'Tidak hadir - diproses otomatis oleh sistem',
            ]);

            // Create ABSENT penalty
            $this->penaltyService->createPenalty(
                $assignment->user_id,
                'ABSENT',
                "Tidak hadir pada {$date->locale('id')->isoFormat('dddd, D MMMM Y')} - Sesi {$assignment->session}",
                'attendance',
                $attendance->id,
                $date
            );

            // Update assignment status to 'missed'
            $assignment->update(['status' => 'missed']);

            Log::info('Absence processed successfully', [
                'assignment_id' => $assignment->id,
                'user_id' => $assignment->user_id,
                'attendance_id' => $attendance->id,
                'date' => $date->toDateString(),
            ]);
        });
    }
}
