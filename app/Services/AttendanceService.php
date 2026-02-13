<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Penalty;
use App\Models\ScheduleAssignment;
use App\Repositories\AttendanceRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    protected AttendanceRepository $repository;

    protected PenaltyService $penaltyService;

    public function __construct(AttendanceRepository $repository, PenaltyService $penaltyService)
    {
        $this->repository = $repository;
        $this->penaltyService = $penaltyService;
    }

    /**
     * Process check-in with validation and penalty calculation
     *
     * @throws \Exception
     */
    public function checkIn(int $userId, ?int $scheduleAssignmentId, ?string $notes = null): Attendance
    {
        try {
            $schedule = null;
            $status = 'present';
            $lateMinutes = 0;

            if ($scheduleAssignmentId) {
                // Validate schedule exists and belongs to user
                $schedule = ScheduleAssignment::findOrFail($scheduleAssignmentId);

                if ($schedule->user_id !== $userId) {
                    Log::warning('Unauthorized check-in attempt', [
                        'user_id' => $userId,
                        'schedule_id' => $scheduleAssignmentId,
                        'schedule_user_id' => $schedule->user_id,
                    ]);
                    throw new BusinessException('Jadwal tidak sesuai dengan user.', 'UNAUTHORIZED_SCHEDULE');
                }

                // Validate schedule date is today
                if (! $schedule->date->isToday()) {
                    throw new BusinessException('Hanya dapat check-in untuk jadwal hari ini.', 'INVALID_SCHEDULE_DATE');
                }

                // Check if already checked in for this schedule
                $existing = Attendance::where('user_id', $userId)
                    ->where('schedule_assignment_id', $scheduleAssignmentId)
                    ->first();

                if ($existing && $existing->check_in) {
                    throw new BusinessException('Anda sudah check-in untuk jadwal ini.', 'ALREADY_CHECKED_IN');
                }
            } else {
                // Override Mode Check-in (No Schedule)
                if (! config('siwirus.attendance.override_mode', false)) {
                    throw new BusinessException('Check-in tanpa jadwal tidak diizinkan.', 'OVERRIDE_DISABLED');
                }

                // Check for duplicate unscheduled check-in today?
                // Optional: Prevent multiple unscheduled check-ins?
                // For now, let's allow it but maybe limit to 1 per day?
                // Or check if user has an active attendance without check-out?
                $existingActive = Attendance::where('user_id', $userId)
                    ->whereNull('schedule_assignment_id')
                    ->whereDate('date', today())
                    ->whereNull('check_out')
                    ->exists();

                if ($existingActive) {
                     throw new BusinessException('Anda masih memiliki sesi check-in aktif.', 'ALREADY_CHECKED_IN');
                }
            }

            $checkInTime = now();

            // Check if user has approved leave for this date (Only relevant if schedule exists?)
            // If override check-in, user is present, so leave doesn't matter much unless we want to warn them.
            if ($schedule && $this->hasApprovedLeave($userId, $checkInTime->toDateString())) {
                // User has approved leave, mark as excused without penalty
                return DB::transaction(function () use ($userId, $scheduleAssignmentId, $notes, $checkInTime, $schedule) {
                    $attendance = $this->repository->create([
                        'user_id' => $userId,
                        'schedule_assignment_id' => $scheduleAssignmentId,
                        'date' => today(),
                        'check_in' => $checkInTime,
                        'status' => 'excused',
                        'notes' => $notes,
                    ]);

                    // Update schedule assignment status
                    $schedule->update(['status' => 'excused']);

                    // Log audit
                    log_audit('check_in', $attendance);

                    Log::info('User checked in with approved leave', [
                        'user_id' => $userId,
                        'attendance_id' => $attendance->id,
                        'status' => 'excused',
                    ]);

                    return $attendance;
                });
            }

            // Determine status and late minutes only if schedule exists
            if ($schedule) {
                $statusData = $this->determineStatus($checkInTime, $schedule);
                $status = $statusData['status'];
                $lateMinutes = $statusData['late_minutes'];
            }

            // Create attendance record within transaction
            return DB::transaction(function () use ($userId, $scheduleAssignmentId, $notes, $checkInTime, $status, $lateMinutes, $schedule) {
                $attendance = $this->repository->create([
                    'user_id' => $userId,
                    'schedule_assignment_id' => $scheduleAssignmentId,
                    'date' => today(),
                    'check_in' => $checkInTime,
                    'status' => $status,
                    'notes' => $notes,
                ]);

                // Apply penalty if late and schedule exists
                if ($schedule && $status === 'late' && $lateMinutes > 0) {
                    $this->applyLatePenalty($userId, $attendance, $lateMinutes);
                }

                // Update schedule assignment status if exists
                if ($schedule && ($status === 'present' || $status === 'late')) {
                    $schedule->update(['status' => 'completed']);
                }

                // Log audit
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

        } catch (BusinessException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Check-in failed', [
                'user_id' => $userId,
                'schedule_assignment_id' => $scheduleAssignmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new BusinessException('Terjadi kesalahan saat melakukan check-in. Silakan coba lagi.', 'CHECK_IN_FAILED');
        }
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
     * Determine attendance status based on check-in time
     * Returns array with status and late_minutes
     *
     * @return array ['status' => string, 'late_minutes' => int]
     */
    public function determineStatus(Carbon $checkInTime, ScheduleAssignment $schedule): array
    {
        $scheduleStart = $this->getScheduleStartTime($schedule);
        $gracePeriod = 5; // 5 minutes grace period

        // Calculate minutes late (negative if early)
        $minutesLate = $checkInTime->diffInMinutes($scheduleStart, false);

        // Within grace period (0-5 minutes late)
        if ($minutesLate <= $gracePeriod) {
            return [
                'status' => 'present',
                'late_minutes' => 0,
            ];
        }

        // Late (more than 5 minutes)
        return [
            'status' => 'late',
            'late_minutes' => (int) $minutesLate,
        ];
    }

    /**
     * Apply penalty for late attendance based on late minutes
     * Grace period 5 menit = present, no penalty
     * 6-15 menit = late, 5 poin
     * 16-30 menit = late, 10 poin
     * >30 menit = late, 15 poin
     */
    protected function applyLatePenalty(int $userId, Attendance $attendance, int $lateMinutes): void
    {
        // Determine penalty type and points based on late minutes
        if ($lateMinutes >= 6 && $lateMinutes <= 15) {
            $penaltyTypeCode = 'LATE_MINOR';
            $description = "Terlambat {$lateMinutes} menit pada ".$attendance->check_in->format('d/m/Y H:i');
        } elseif ($lateMinutes >= 16 && $lateMinutes <= 30) {
            $penaltyTypeCode = 'LATE_MODERATE';
            $description = "Terlambat {$lateMinutes} menit pada ".$attendance->check_in->format('d/m/Y H:i');
        } else { // > 30 minutes
            $penaltyTypeCode = 'LATE_SEVERE';
            $description = "Terlambat {$lateMinutes} menit pada ".$attendance->check_in->format('d/m/Y H:i');
        }

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

        if (! $attendance->check_in) {
            throw new \Exception('Belum check-in.');
        }

        if ($attendance->check_out) {
            throw new \Exception('Sudah check-out.');
        }

        $attendance->update([
            'check_out' => now(),
            'notes' => $notes ?? $attendance->notes,
        ]);

        // Log audit
        log_audit('check_out', $attendance);

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
        if (! $assignment->exists) {
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
                return $attendance->penalties()->sum('amount');
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
