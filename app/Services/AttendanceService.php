<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Exceptions\BusinessException;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Penalty;
use App\Models\ScheduleAssignment;
use App\Repositories\AttendanceRepository;
use App\Services\StoreStatusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    protected AttendanceRepository $repository;

    protected PenaltyService $penaltyService;

    protected StoreStatusService $storeStatusService;

    public function __construct(
        AttendanceRepository $repository,
        PenaltyService $penaltyService,
        StoreStatusService $storeStatusService
    ) {
        $this->repository = $repository;
        $this->penaltyService = $penaltyService;
        $this->storeStatusService = $storeStatusService;
    }

    /**
     * Process check-in with validation and penalty calculation
     * FIXED: All validation is now inside transaction with pessimistic locking to prevent race conditions
     *
     * @throws \Exception
     */
    public function checkIn(int $userId, ?int $scheduleAssignmentId, ?string $notes = null): Attendance
    {
        // Use transaction with pessimistic locking to prevent race conditions
        return DB::transaction(function () use ($userId, $scheduleAssignmentId, $notes) {
            $schedule = null;
            $status = 'present';
            $lateMinutes = 0;
            $checkInTime = now();

            if ($scheduleAssignmentId) {
                // FIX: Lock the schedule row to prevent concurrent modifications
                $schedule = ScheduleAssignment::where('id', $scheduleAssignmentId)
                    ->lockForUpdate()
                    ->first();

                if (!$schedule) {
                    throw new BusinessException('Jadwal tidak ditemukan.', 'SCHEDULE_NOT_FOUND');
                }

                if ($schedule->user_id !== $userId) {
                    Log::warning('Unauthorized check-in attempt', [
                        'user_id' => $userId,
                        'schedule_id' => $scheduleAssignmentId,
                        'schedule_user_id' => $schedule->user_id,
                    ]);
                    throw new BusinessException('Jadwal tidak sesuai dengan user.', 'UNAUTHORIZED_SCHEDULE');
                }

                if (!$schedule->date->isToday()) {
                    throw new BusinessException('Hanya dapat check-in untuk jadwal hari ini.', 'INVALID_SCHEDULE_DATE');
                }

                // FIX: Check within transaction with lock
                $existing = Attendance::where('user_id', $userId)
                    ->where('schedule_assignment_id', $scheduleAssignmentId)
                    ->lockForUpdate()
                    ->first();

                if ($existing && $existing->check_in) {
                    throw new BusinessException('Anda sudah check-in untuk jadwal ini.', 'ALREADY_CHECKED_IN');
                }

                // FIX: Check other sessions within same transaction with lock
                $todayCheckedIn = Attendance::where('user_id', $userId)
                    ->whereDate('date', today())
                    ->whereNotNull('check_in')
                    ->whereNull('check_out')
                    ->where('schedule_assignment_id', '!=', $scheduleAssignmentId)
                    ->lockForUpdate()
                    ->first();

                if ($todayCheckedIn) {
                    throw new BusinessException('Anda masih memiliki sesi check-in aktif. Silakan checkout terlebih dahulu.', 'ACTIVE_SESSION_EXISTS');
                }
            } else {
                // Override Mode Check-in (No Schedule)
                if (!$this->storeStatusService->isOverrideActive()) {
                    throw new BusinessException('Check-in tanpa jadwal tidak diizinkan.', 'OVERRIDE_DISABLED');
                }

                $existingActive = Attendance::where('user_id', $userId)
                    ->whereNull('schedule_assignment_id')
                    ->whereDate('date', today())
                    ->whereNull('check_out')
                    ->lockForUpdate()
                    ->exists();

                if ($existingActive) {
                    throw new BusinessException('Anda masih memiliki sesi check-in aktif.', 'ALREADY_CHECKED_IN');
                }
            }

            // Check approved leave
            if ($schedule && $this->hasApprovedLeave($userId, $checkInTime->toDateString())) {
                $attendance = $this->repository->create([
                    'user_id' => $userId,
                    'schedule_assignment_id' => $scheduleAssignmentId,
                    'date' => today(),
                    'check_in' => $checkInTime,
                    'status' => 'excused',
                    'notes' => $notes,
                ]);

                $schedule->update(['status' => 'excused']);
                log_audit('check_in', $attendance);

                Log::info('User checked in with approved leave', [
                    'user_id' => $userId,
                    'attendance_id' => $attendance->id,
                    'status' => 'excused',
                ]);

                return $attendance;
            }

            // Determine status and late minutes
            $lateCategory = null;
            if ($schedule) {
                $statusData = $this->determineStatus($checkInTime, $schedule);
                $status = $statusData['status'];
                $lateMinutes = $statusData['late_minutes'];
                $lateCategory = $statusData['late_category'];
            }

            // Create attendance record
            $attendance = $this->repository->create([
                'user_id' => $userId,
                'schedule_assignment_id' => $scheduleAssignmentId,
                'date' => today(),
                'check_in' => $checkInTime,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'late_category' => $lateCategory,
                'notes' => $notes,
            ]);

            // Apply penalty if late
            if ($schedule && $status === 'late' && $lateMinutes > 0) {
                $this->applyLatePenalty($userId, $attendance, $lateMinutes, $lateCategory);
            }

            // Update schedule assignment status
            if ($schedule) {
                $schedule->update(['status' => 'in_progress']);
            }

            log_audit('check_in', $attendance);

            Log::info('User checked in successfully', [
                'user_id' => $userId,
                'attendance_id' => $attendance->id,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'is_override' => is_null($scheduleAssignmentId),
            ]);

            return $attendance;
        });
    }

    /**
     * Check if user has approved leave for the given date
     *
     * @param  string|Carbon  $date
     */
    public function hasApprovedLeave(int $userId, $date): bool
    {
        $dateCarbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return LeaveRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $dateCarbon)
            ->where('end_date', '>=', $dateCarbon)
            ->exists();
    }

    /**
     * Get active attendance for user (for UI layer state retrieval)
     * Centralized method for getting active session
     */
    public function getActiveAttendance(int $userId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->whereDate('date', today())
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->latest('check_in')
            ->first();
    }

    /**
     * Check if user has active session that should block new check-in
     * Returns: ['has_active' => bool, 'blocking_session' => ?ScheduleAssignment, 'message' => ?string]
     */
    public function getActiveSessionBlockingInfo(int $userId, ?int $targetAssignmentId = null): array
    {
        $activeAttendance = $this->getActiveAttendance($userId);
        
        if (!$activeAttendance || !$activeAttendance->schedule_assignment_id) {
            return ['has_active' => false, 'blocking_session' => null, 'message' => null];
        }

        $assignment = ScheduleAssignment::find($activeAttendance->schedule_assignment_id);
        
        if (!$assignment) {
            return ['has_active' => false, 'blocking_session' => null, 'message' => null];
        }

        // If targeting the same assignment, don't block
        if ($targetAssignmentId && $targetAssignmentId === $assignment->id) {
            return ['has_active' => false, 'blocking_session' => null, 'message' => null];
        }

        // Check if within valid time window
        $now = now();
        $start = Carbon::parse($assignment->time_start)->subMinutes(30);
        $end = Carbon::parse($assignment->time_end);

        if ($now->gte($start) && $now->lte($end)) {
            return [
                'has_active' => true,
                'blocking_session' => $assignment,
                'message' => 'Anda masih memiliki sesi aktif: Sesi '.$assignment->session.' ('.$assignment->time_start.'-'.$assignment->time_end.')'
            ];
        }

        return ['has_active' => false, 'blocking_session' => null, 'message' => null];
    }

    /**
     * Determine attendance status based on check-in time
     * Returns array with status and late_minutes
     *
     * @return array ['status' => string, 'late_minutes' => int]
     */
    public function determineStatus(Carbon $checkInTime, ScheduleAssignment $schedule): array
    {
        $scheduleStart = $this->getScheduleStartTime($schedule);
        $graceMinutes = (int) config('app-settings.attendance.grace_minutes', 9);

        // Minutes late: clamp to >= 0
        $minutesLate = (int) max(0, $scheduleStart->diffInMinutes($checkInTime, false));

        if ($minutesLate <= $graceMinutes) {
            return [
                'status' => 'present',
                'late_minutes' => 0,
                'late_category' => null,
            ];
        }

        // Determine category from config
        $lateCategory = 'C'; // Default
        $ranges = config('app-settings.attendance.late_ranges', [
            'A' => [10, 30],
            'B' => [31, 60],
            'C' => [61, null],
        ]);
        
        foreach ($ranges as $cat => $range) {
            $min = $range[0];
            $max = $range[1];
            
            if ($max === null) {
                if ($minutesLate >= $min) {
                    $lateCategory = $cat;
                    break;
                }
            } elseif ($minutesLate >= $min && $minutesLate <= $max) {
                $lateCategory = $cat;
                break;
            }
        }

        return [
            'status' => 'late',
            'late_minutes' => $minutesLate,
            'late_category' => $lateCategory,
        ];
    }

    /**
     * Apply penalty for late attendance based on late category
     */
    protected function applyLatePenalty(int $userId, Attendance $attendance, int $lateMinutes, string $lateCategory): void
    {
        $penaltyTypeCode = 'LATE_' . $lateCategory;
        $description = "Terlambat {$lateMinutes} menit (Kategori {$lateCategory}) pada " . $attendance->check_in->format('d/m/Y H:i');

        // Create penalty using PenaltyService with automatic threshold checking
        $this->penaltyService->createPenalty(
            $userId,
            $penaltyTypeCode,
            $description,
            'attendance',
            $attendance->id,
            $attendance->date
        );
    }

    /**
     * Process check-out
     *
     * @throws \Exception
     */
    public function checkOut(int $attendanceId, ?string $notes = null): Attendance
    {
        $attendance = Attendance::findOrFail($attendanceId);

        if (!$attendance->check_in) {
            throw new \Exception('Belum check-in.');
        }

        if ($attendance->check_out) {
            throw new \Exception('Sudah check-out.');
        }

        DB::transaction(function () use ($attendance, $notes) {
            $attendance->update([
                'check_out' => now(),
                'notes' => $notes ?? $attendance->notes,
            ]);

            // Update schedule assignment status to completed if it was in_progress
            if ($attendance->schedule_assignment_id) {
                $assignment = $attendance->scheduleAssignment;
                if ($assignment && $assignment->status === 'in_progress') {
                    $assignment->update(['status' => 'completed']);
                }
            }

            // Log audit
            log_audit('check_out', $attendance);
        });

        return $attendance->fresh();
    }

    /**
     * Mark absent for users who didn't check in
     * This method is called by ProcessAbsencesJob
     *
     * @throws Exception
     */
    public function markAbsent(ScheduleAssignment $assignment): Attendance
    {
        // Validate assignment exists and is valid
        if (!$assignment->exists) {
            throw new Exception('Invalid schedule assignment');
        }

        // Check if attendance already exists
        $existingAttendance = Attendance::where('user_id', $assignment->user_id)
            ->where('schedule_assignment_id', $assignment->id)
            ->first();

        if ($existingAttendance) {
            throw new Exception('Attendance record already exists for this assignment');
        }

        // Create absence attendance record
        return DB::transaction(function () use ($assignment) {
            $attendance = Attendance::create([
                'user_id' => $assignment->user_id,
                'schedule_assignment_id' => $assignment->id,
                'date' => $assignment->date,
                'status' => 'absent',
            ]);

            // Apply absent penalty using PenaltyService
            $this->penaltyService->createPenalty(
                $assignment->user_id,
                'ABSENT',
                'Tidak hadir pada '.$assignment->date->format('d/m/Y').' sesi '.$assignment->session,
                'attendance',
                $attendance->id,
                $assignment->date
            );

            // Update assignment status
            $assignment->update(['status' => 'missed']);

            Log::info('User marked absent', [
                'user_id' => $assignment->user_id,
                'assignment_id' => $assignment->id,
                'date' => $assignment->date->format('Y-m-d'),
            ]);

            return $attendance;
        });
    }

    /**
     * Process auto check-outs for users who forgot to check out
     * OPTIMIZED: Uses query-based approach instead of heavy eager loading
     * 
     * @return int Number of processed attendances
     */
    public function processAutoCheckOuts(): int
    {
        $now = now();
        $bufferHours = 3;
        $count = 0;

        // OPTIMIZED: Use query with join instead of eager loading entire relations
        // This avoids loading unnecessary data and reduces memory usage
        $pendingAttendances = Attendance::whereNull('attendances.check_out')
            ->whereNotNull('attendances.schedule_assignment_id')
            ->join('schedule_assignments', 'attendances.schedule_assignment_id', '=', 'schedule_assignments.id')
            ->select('attendances.*', 'schedule_assignments.time_end', 'schedule_assignments.status as assignment_status', 'schedule_assignments.session')
            ->whereRaw("CONCAT(attendances.date, ' ', schedule_assignments.time_end) < ?", [$now->copy()->subHours($bufferHours)->format('Y-m-d H:i:s')])
            ->cursor();

        foreach ($pendingAttendances as $attendance) {
            // Calculate end time from joined data
            $endTime = Carbon::parse($attendance->date.' '.$attendance->time_end);
            
            DB::transaction(function () use ($attendance, $endTime) {
                $checkIn = $attendance->check_in;
                $workHours = 0;
                if ($checkIn && $endTime->gt($checkIn)) {
                    $workHours = round($checkIn->diffInMinutes($endTime) / 60, 2);
                }

                $attendance->update([
                    'check_out' => $endTime,
                    'work_hours' => $workHours,
                    'notes' => ($attendance->notes ? $attendance->notes . "\n" : "") . "[Sistem: Auto-checkout (Lupa checkout)]",
                ]);

                // Update assignment if needed
                $assignment = $attendance->scheduleAssignment;
                if ($assignment && $assignment->status === 'in_progress') {
                    $assignment->update(['status' => 'completed']);
                }

                ActivityLogService::logCheckOut(
                    'Sesi '.$attendance->session ?? 'Unknown',
                    $endTime->format('H:i'),
                    number_format($workHours, 2),
                    $attendance->user_id
                );
            });
            
            $count++;
        }

        return $count;
    }

    /**
     * Get attendance summary for a user
     */
    public function getUserSummary(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        return $this->repository->getUserStats($userId, $startDate, $endDate);
    }

    /**
     * Get schedule start time
     */
    protected function getScheduleStartTime(ScheduleAssignment $schedule): Carbon
    {
        // Prefer exact assignment time if available
        if (!empty($schedule->time_start)) {
            return $schedule->date->copy()->setTimeFromTimeString($schedule->time_start);
        }

        // Fallback to session mapping
        $sessionTimes = [
            1 => '07:30',
            2 => '10:20',
            3 => '13:30',
        ];

        $timeString = $sessionTimes[$schedule->session] ?? '07:30';

        return $schedule->date->copy()->setTimeFromTimeString($timeString);
    }

    /**
     * Get attendance analytics
     */
    public function getAttendanceAnalytics(Carbon $startDate, Carbon $endDate, ?int $userId = null): array
    {
        $query = $this->repository->query()
            ->with(['penalties'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $attendances = $query->get();

        return [
            'total_days' => $startDate->diffInDays($endDate) + 1,
            'total_attendances' => $attendances->count(),
            'present_count' => $attendances->where('status', 'present')->count(),
            'late_count' => $attendances->where('status', 'late')->count(),
            'absent_count' => $attendances->where('status', 'absent')->count(),
            'attendance_rate' => $attendances->count() > 0
                ? round(($attendances->whereIn('status', ['present', 'late'])->count() / $attendances->count()) * 100, 2)
                : 0,
            'total_penalties' => $attendances->sum(function ($attendance) {
                return $attendance->penalties()->sum('points');
            }),
            'average_late_minutes' => $attendances->where('status', 'late')->avg('late_minutes') ?? 0,
        ];
    }

    /**
     * Validate photo proof
     */
    public function validatePhotoProof(string $photoPath): bool
    {
        try {
            if (! \Storage::disk('public')->exists($photoPath)) {
                return false;
            }

            $fileInfo = pathinfo($photoPath);
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (! in_array(strtolower($fileInfo['extension']), $allowedExtensions)) {
                return false;
            }

            $fileSize = \Storage::disk('public')->size($photoPath);
            $maxSize = 2 * 1024 * 1024; // 2MB

            if ($fileSize > $maxSize) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to validate photo proof', [
                'photo_path' => $photoPath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
