<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\ScheduleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceRepository
{
    /**
     * Get attendance statistics for a user
     */
    public function getUserStats(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('check_in', [$startDate, $endDate])
            ->get();

        return [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
            'attendance_rate' => $this->calculateAttendanceRate($userId, $startDate, $endDate),
        ];
    }

    /**
     * Calculate attendance rate percentage
     */
    public function calculateAttendanceRate(int $userId, Carbon $startDate, Carbon $endDate): float
    {
        $totalScheduled = ScheduleAssignment::where('user_id', $userId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->count();

        if ($totalScheduled === 0) {
            return 0;
        }

        $attended = Attendance::where('user_id', $userId)
            ->whereBetween('check_in', [$startDate, $endDate])
            ->whereIn('status', ['present', 'late'])
            ->count();

        return round(($attended / $totalScheduled) * 100, 2);
    }

    /**
     * Get late attendance records for a user
     */
    public function getLateRecords(int $userId, int $limit = 10): Collection
    {
        return Attendance::where('user_id', $userId)
            ->where('status', 'late')
            ->with(['user', 'scheduleAssignment.schedule'])
            ->orderBy('check_in', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get attendance records for a specific date range
     */
    public function getByDateRange(Carbon $startDate, Carbon $endDate, array $filters = []): Collection
    {
        $query = Attendance::whereBetween('check_in', [$startDate, $endDate])
            ->with(['user', 'scheduleAssignment']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('check_in', 'desc')->get();
    }

    /**
     * Get today's attendance summary
     */
    public function getTodaySummary(): array
    {
        $today = today();

        return [
            'scheduled' => ScheduleAssignment::where('date', $today)->count(),
            'checked_in' => Attendance::whereDate('check_in', $today)->count(),
            'present' => Attendance::whereDate('check_in', $today)->where('status', 'present')->count(),
            'late' => Attendance::whereDate('check_in', $today)->where('status', 'late')->count(),
            'absent' => $this->getAbsentCount($today),
        ];
    }

    /**
     * Get absent count for a specific date
     */
    private function getAbsentCount(Carbon $date): int
    {
        // Count schedule assignments that don't have a corresponding attendance record
        return ScheduleAssignment::where('date', $date)
            ->whereDoesntHave('attendance')
            ->count();
    }

    /**
     * Get users who haven't checked in today
     */
    public function getNotCheckedInToday(): Collection
    {
        // Find schedule assignments for today that don't have an attendance record
        $assignmentUserIds = ScheduleAssignment::where('date', today())
            ->whereDoesntHave('attendance')
            ->pluck('user_id')
            ->unique();

        return \App\Models\User::whereIn('id', $assignmentUserIds)
            ->with('roles')
            ->get();
    }

    /**
     * Create attendance record
     */
    public function create(array $data): Attendance
    {
        return Attendance::create($data);
    }

    /**
     * Update attendance record
     */
    public function update(int $id, array $data): bool
    {
        return Attendance::where('id', $id)->update($data);
    }
}
