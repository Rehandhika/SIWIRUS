<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveAffectedSchedule;
use App\Models\LeaveRequest;
use App\Models\ScheduleAssignment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    /**
     * Submit a new leave request
     *
     * @throws Exception
     */
    public function submitRequest(
        int $userId,
        string $leaveType,
        Carbon $startDate,
        Carbon $endDate,
        string $reason,
        ?string $attachmentPath = null
    ): LeaveRequest {
        // Validate date range
        if ($endDate->lt($startDate)) {
            throw new Exception('Tanggal selesai harus setelah atau sama dengan tanggal mulai.');
        }

        // Calculate total days
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Validate sick leave attachment requirement
        if ($leaveType === 'sick' && $totalDays > 1 && empty($attachmentPath)) {
            throw new Exception('Izin sakit lebih dari 1 hari wajib menyertakan surat keterangan dokter.');
        }

        // Create leave request
        $leaveRequest = LeaveRequest::create([
            'user_id' => $userId,
            'leave_type' => $leaveType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $reason,
            'attachment' => $attachmentPath,
            'status' => 'pending',
        ]);

        return $leaveRequest;
    }

    /**
     * Get schedule assignments affected by leave request
     */
    public function getAffectedAssignments(LeaveRequest $request): Collection
    {
        return ScheduleAssignment::where('user_id', $request->user_id)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->whereIn('status', ['scheduled', 'excused'])
            ->orderBy('date')
            ->orderBy('session')
            ->get();
    }

    /**
     * Approve leave request
     *
     * @throws Exception
     */
    public function approve(
        LeaveRequest $request,
        int $reviewerId,
        ?string $notes = null
    ): bool {
        if ($request->status !== 'pending') {
            throw new Exception('Hanya pengajuan status pending yang dapat disetujui.');
        }

        // Check if there are any existing attendance records that conflict
        $conflictingAttendances = Attendance::where('user_id', $request->user_id)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->whereIn('status', ['present', 'late', 'absent'])
            ->get();

        if ($conflictingAttendances->isNotEmpty()) {
            $dates = $conflictingAttendances->pluck('date')->map(function ($date) {
                return $date->format('d/m/Y');
            })->join(', ');

            throw new Exception("Tidak dapat menyetujui izin. User sudah memiliki absensi pada tanggal: {$dates}");
        }

        DB::beginTransaction();
        try {
            // Update leave request status
            $request->update([
                'status' => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);

            // Get affected assignments
            $affectedAssignments = $this->getAffectedAssignments($request);

            // Update all affected assignments to 'excused'
            foreach ($affectedAssignments as $assignment) {
                $assignment->update(['status' => 'excused']);

                // Create LeaveAffectedSchedule record
                LeaveAffectedSchedule::create([
                    'leave_request_id' => $request->id,
                    'schedule_assignment_id' => $assignment->id,
                ]);

                // Cleanup any existing penalties for this shift (e.g. if user was marked absent before leave was approved)
                \App\Models\Penalty::where('reference_type', 'attendance')
                    ->whereHas('reference', function ($query) use ($assignment) {
                        $query->where('schedule_assignment_id', $assignment->id);
                    })
                    ->delete();
            }

            // Send notification to user
            NotificationService::send(
                $request->user,
                'leave_approved',
                'Pengajuan Izin Disetujui',
                "Pengajuan izin Anda dari {$request->start_date->format('d/m/Y')} sampai {$request->end_date->format('d/m/Y')} telah disetujui.",
                ['leave_request_id' => $request->id],
                route('admin.leave.my-requests')
            );

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject leave request
     *
     * @throws Exception
     */
    public function reject(
        LeaveRequest $request,
        int $reviewerId,
        string $notes
    ): bool {
        if ($request->status !== 'pending') {
            throw new Exception('Hanya pengajuan status pending yang dapat ditolak.');
        }

        // Update leave request status
        $request->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        // Send notification to user
        NotificationService::send(
            $request->user,
            'leave_rejected',
            'Pengajuan Izin Ditolak',
            "Pengajuan izin Anda dari {$request->start_date->format('d/m/Y')} sampai {$request->end_date->format('d/m/Y')} telah ditolak. Alasan: {$notes}",
            ['leave_request_id' => $request->id],
            route('admin.leave.my-requests')
        );

        // Keep schedule assignments unchanged
        return true;
    }

    /**
     * Cancel leave request
     *
     * @throws Exception
     */
    public function cancel(LeaveRequest $request): bool
    {
        // Only pending or approved requests can be cancelled
        if (! in_array($request->status, ['pending', 'approved'])) {
            throw new Exception('Hanya pengajuan pending atau disetujui yang dapat dibatalkan.');
        }

        // Check if leave has already started
        if ($request->start_date->isPast()) {
            throw new Exception('Tidak dapat membatalkan izin yang sudah lewat.');
        }

        DB::beginTransaction();
        try {
            // If leave was approved, revert affected assignments
            if ($request->status === 'approved') {
                // Get affected schedules
                $affectedSchedules = LeaveAffectedSchedule::where('leave_request_id', $request->id)->get();

                foreach ($affectedSchedules as $affectedSchedule) {
                    // Revert assignment status to 'scheduled'
                    $assignment = ScheduleAssignment::find($affectedSchedule->schedule_assignment_id);
                    if ($assignment && $assignment->status === 'excused') {
                        $assignment->update(['status' => 'scheduled']);
                    }
                }

                // Delete affected schedule records
                LeaveAffectedSchedule::where('leave_request_id', $request->id)->delete();
            }

            // Update leave request status
            $request->update(['status' => 'cancelled']);

            // Send notification to user
            NotificationService::send(
                $request->user,
                'leave_cancelled',
                'Pengajuan Izin Dibatalkan',
                "Pengajuan izin Anda dari {$request->start_date->format('d/m/Y')} sampai {$request->end_date->format('d/m/Y')} telah dibatalkan.",
                ['leave_request_id' => $request->id],
                route('admin.leave.my-requests')
            );

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate sick leave requirements
     *
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateSickLeave(LeaveRequest $request): array
    {
        $errors = [];

        if ($request->leave_type === 'sick') {
            $totalDays = $request->start_date->diffInDays($request->end_date) + 1;

            if ($totalDays > 1 && empty($request->attachment)) {
                $errors[] = 'Sick leave longer than 1 day requires attachment (surat keterangan)';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
