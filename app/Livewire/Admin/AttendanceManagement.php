<?php

namespace App\Livewire\Admin;

use App\Exports\AttendanceExport;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Services\ActivityLogService;
use App\Traits\AuthorizesLivewireRequests;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Lazy]
class AttendanceManagement extends Component
{
    use WithPagination;
    use AuthorizesLivewireRequests;

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    #[Url(as: 'q')]
    public string $search = '';

    public string $datePreset = 'today';

    // Photo Modal
    public bool $showPhotoModal = false;

    public ?string $selectedPhoto = null;

    public ?string $selectedUserName = null;

    // Detail Modal
    public bool $showDetailModal = false;

    public ?array $detailData = null;

    // Edit Modal
    public bool $showEditModal = false;

    public ?int $editId = null;

    public string $editStatus = '';

    public ?string $editCheckIn = null;

    public ?string $editCheckOut = null;

    public function mount(): void
    {
        if (empty($this->dateFrom) && empty($this->dateTo)) {
            $this->dateFrom = Carbon::today()->format('Y-m-d');
            $this->dateTo = Carbon::today()->format('Y-m-d');
        }
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="space-y-6 animate-pulse">
            <div class="h-8 bg-gray-200 rounded w-1/4"></div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="h-24 bg-gray-200 rounded-lg"></div>
                <div class="h-24 bg-gray-200 rounded-lg"></div>
                <div class="h-24 bg-gray-200 rounded-lg"></div>
                <div class="h-24 bg-gray-200 rounded-lg"></div>
            </div>
            <div class="h-96 bg-gray-200 rounded-lg"></div>
        </div>
        HTML;
    }

    #[Computed]
    public function weeklyScheduleData()
    {
        $schedule = Schedule::published()->currentWeek()->first();

        if (! $schedule) {
            return [];
        }

        $startOfWeek = Carbon::parse($schedule->week_start_date);
        $endOfWeek = Carbon::parse($schedule->week_end_date);

        // Fetch assignments
        $assignments = $schedule->assignments()
            ->with('user:id,name,nim,photo')
            ->orderBy('date')
            ->orderBy('session')
            ->get();

        // Fetch attendances for this week keyed by schedule_assignment_id (lebih akurat untuk multi-sesi)
        $attendancesByAssignment = Attendance::whereBetween('date', [$startOfWeek, $endOfWeek])
            ->whereNotNull('schedule_assignment_id')
            ->get()
            ->keyBy('schedule_assignment_id');

        // Fetch approved leaves overlapping with this week
        $leaves = LeaveRequest::approved()
            ->where(function ($query) use ($startOfWeek, $endOfWeek) {
                $query->whereBetween('start_date', [$startOfWeek, $endOfWeek])
                    ->orWhereBetween('end_date', [$startOfWeek, $endOfWeek])
                    ->orWhere(function ($q) use ($startOfWeek, $endOfWeek) {
                        $q->where('start_date', '<', $startOfWeek)
                            ->where('end_date', '>', $endOfWeek);
                    });
            })
            ->get();

        $sessionTimes = config('app-settings.session_times', [
            1 => ['start' => '07:30', 'end' => '10:00'],
            2 => ['start' => '10:20', 'end' => '12:50'],
            3 => ['start' => '13:30', 'end' => '16:00'],
        ]);

        $now = now();

        return $assignments->map(function ($assignment) use ($attendancesByAssignment, $leaves, $sessionTimes, $now) {
            // Ambil attendance persis untuk assignment ini
            $attendance = $attendancesByAssignment->get($assignment->id);
            
            $status = 'upcoming'; // Default
            $statusLabel = '-';
            $statusColor = 'gray';

            // Check Attendance
            if ($attendance) {
                $status = $attendance->status ?? 'present';
                $statusLabel = match ($status) {
                    'present' => 'Hadir',
                    'late' => 'Terlambat',
                    'absent' => 'Tidak Hadir',
                    'excused' => 'Izin',
                    default => ucfirst($status),
                };
                $statusColor = match ($status) {
                    'present' => 'success',
                    'late' => 'warning',
                    'absent' => 'danger',
                    'excused' => 'info',
                    default => 'gray',
                };
            } else {
                // Check Leave
                $isOnLeave = $leaves->filter(function ($leave) use ($assignment) {
                    return $leave->user_id === $assignment->user_id &&
                           $assignment->date->between($leave->start_date, $leave->end_date);
                })->isNotEmpty();

                if ($isOnLeave) {
                    $status = 'excused';
                    $statusLabel = 'Izin/Cuti';
                    $statusColor = 'info';
                } else {
                    // Check Time for Absent
                    $sessionEndStr = $sessionTimes[$assignment->session]['end'] ?? '16:00';
                    $sessionEnd = Carbon::parse($assignment->date->format('Y-m-d').' '.$sessionEndStr);
                    
                    if ($now->greaterThan($sessionEnd)) {
                        $status = 'absent';
                        $statusLabel = 'Absen';
                        $statusColor = 'danger';
                    }
                }
            }

            return [
                'id' => $assignment->id,
                'user_name' => $assignment->user->name,
                'user_photo' => $assignment->user->photo ? \Storage::url($assignment->user->photo) : null,
                'date' => $assignment->date,
                'day_name' => $assignment->date->locale('id')->dayName,
                'session' => $assignment->session,
                'time_range' => $assignment->session_label,
                'status' => $status,
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
            ];
        })->groupBy(fn ($item) => $item['date']->format('Y-m-d'));
    }

    public function setDatePreset(string $preset): void
    {
        $this->datePreset = $preset;
        $today = Carbon::today();

        match ($preset) {
            'today' => [$this->dateFrom, $this->dateTo] = [$today->format('Y-m-d'), $today->format('Y-m-d')],
            'yesterday' => [$this->dateFrom, $this->dateTo] = [$today->copy()->subDay()->format('Y-m-d'), $today->copy()->subDay()->format('Y-m-d')],
            'week' => [$this->dateFrom, $this->dateTo] = [$today->copy()->startOfWeek()->format('Y-m-d'), $today->format('Y-m-d')],
            'month' => [$this->dateFrom, $this->dateTo] = [$today->copy()->startOfMonth()->format('Y-m-d'), $today->format('Y-m-d')],
            default => null,
        };

        $this->resetPage();
        $this->clearStatsCache();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
        $this->clearStatsCache();
    }

    public function updatedDateFrom(): void
    {
        $this->datePreset = 'custom';
        $this->resetPage();
        $this->clearStatsCache();
    }

    public function updatedDateTo(): void
    {
        $this->datePreset = 'custom';
        $this->resetPage();
        $this->clearStatsCache();
    }

    public function applyFilter(): void
    {
        $this->datePreset = 'custom';
        $this->resetPage();
        $this->clearStatsCache();
    }

    public function resetFilters(): void
    {
        $this->dateFrom = Carbon::today()->format('Y-m-d');
        $this->dateTo = Carbon::today()->format('Y-m-d');
        $this->filterStatus = '';
        $this->search = '';
        $this->datePreset = 'today';
        $this->resetPage();
        $this->clearStatsCache();
    }

    private function clearStatsCache(): void
    {
        Cache::forget($this->getStatsCacheKey());
    }

    private function getStatsCacheKey(): string
    {
        return "admin_attendance_stats_{$this->dateFrom}_{$this->dateTo}_{$this->filterStatus}";
    }

    // === Photo Modal ===
    public function viewPhoto(string $photoUrl, string $userName): void
    {
        $this->selectedPhoto = $photoUrl;
        $this->selectedUserName = $userName;
        $this->showPhotoModal = true;
    }

    public function closePhotoModal(): void
    {
        $this->showPhotoModal = false;
        $this->selectedPhoto = null;
        $this->selectedUserName = null;
    }

    // === Detail Modal ===
    public function showDetail(int $id): void
    {
        $attendance = Attendance::with(['user:id,name,nim,email,photo', 'scheduleAssignment.schedule'])
            ->select(['id', 'user_id', 'schedule_assignment_id', 'date', 'check_in', 'check_in_photo', 'check_out', 'work_hours', 'status', 'notes', 'created_at'])
            ->find($id);

        if (! $attendance) {
            $this->dispatch('toast', message: 'Data tidak ditemukan', type: 'error');

            return;
        }

        $this->detailData = [
            'id' => $attendance->id,
            'user_name' => $attendance->user?->name ?? '-',
            'user_nim' => $attendance->user?->nim ?? '-',
            'user_email' => $attendance->user?->email ?? '-',
            'user_photo' => $attendance->user?->photo ? \Storage::url($attendance->user->photo) : null,
            'date' => $attendance->date->format('d M Y'),
            'day' => $attendance->date->locale('id')->dayName,
            'check_in' => $attendance->check_in?->format('H:i:s'),
            'check_out' => $attendance->check_out?->format('H:i:s'),
            'check_in_photo' => $attendance->check_in_photo_url,
            'work_hours' => $attendance->work_hours ? number_format($attendance->work_hours, 2) : null,
            'status' => $attendance->status,
            'late_minutes' => $attendance->late_minutes,
            'late_category' => $attendance->late_category,
            'notes' => $attendance->notes,
            'schedule' => $attendance->scheduleAssignment?->schedule?->name ?? '-',
            'created_at' => $attendance->created_at->format('d M Y H:i'),
        ];

        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailData = null;
    }

    // === Edit Modal ===
    public function openEdit(int $id): void
    {
        $attendance = Attendance::select(['id', 'status', 'check_in', 'check_out'])->find($id);

        if (! $attendance) {
            $this->dispatch('toast', message: 'Data tidak ditemukan', type: 'error');

            return;
        }

        $this->editId = $attendance->id;
        $this->editStatus = $attendance->status;
        $this->editCheckIn = $attendance->check_in?->format('H:i');
        $this->editCheckOut = $attendance->check_out?->format('H:i');
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->reset(['editId', 'editStatus', 'editCheckIn', 'editCheckOut']);
    }

    public function saveEdit(): void
    {
        // Authorization check - requires kelola_absensi permission
        $this->authorizePermission('kelola_absensi', 'Anda tidak memiliki izin untuk mengubah data absensi.');

        $this->validate([
            'editStatus' => 'required|in:present,late,absent,excused',
            'editCheckIn' => 'nullable|date_format:H:i',
            'editCheckOut' => 'nullable|date_format:H:i',
        ]);

        $attendance = Attendance::find($this->editId);

        if (! $attendance) {
            $this->dispatch('toast', message: 'Data tidak ditemukan', type: 'error');

            return;
        }

        $updateData = ['status' => $this->editStatus];

        if ($this->editCheckIn) {
            $updateData['check_in'] = Carbon::parse($attendance->date->format('Y-m-d').' '.$this->editCheckIn);
        }

        if ($this->editCheckOut) {
            $updateData['check_out'] = Carbon::parse($attendance->date->format('Y-m-d').' '.$this->editCheckOut);

            if (isset($updateData['check_in']) || $attendance->check_in) {
                $checkIn = $updateData['check_in'] ?? $attendance->check_in;
                $updateData['work_hours'] = Carbon::parse($checkIn)->diffInMinutes($updateData['check_out']) / 60;
            }
        }

        $oldStatus = $attendance->status;
        $attendance->update($updateData);

        // If status changed from 'late' to something else, remove the related penalty
        if ($oldStatus === 'late' && $this->editStatus !== 'late') {
            $attendance->penalties()->delete();
        }

        // If status changed to 'absent', create the penalty if it doesn't exist
        if ($this->editStatus === 'absent' && $oldStatus !== 'absent') {
            app(\App\Services\PenaltyService::class)->createPenalty(
                $attendance->user_id,
                'ABSENT',
                'Ditetapkan absen secara manual oleh admin pada ' . $attendance->date->format('d/m/Y'),
                'attendance',
                $attendance->id,
                $attendance->date
            );
        }

        // Log activity
        $user = $attendance->user;
        ActivityLogService::logAttendanceEdited(
            $user ? $user->name : 'Unknown',
            $attendance->date->format('d M Y')
        );

        $this->closeEditModal();
        $this->clearStatsCache();
        $this->dispatch('toast', message: 'Data berhasil diperbarui', type: 'success');
    }

    // === Export Excel ===
    public function export()
    {
        // Authorization check - requires ekspor_data permission
        $this->authorizePermission('ekspor_data', 'Anda tidak memiliki izin untuk mengekspor data.');

        // Log activity
        ActivityLogService::logAttendanceExported($this->dateFrom, $this->dateTo);

        $filename = 'absensi_'.$this->dateFrom.'_'.$this->dateTo.'.xlsx';

        return Excel::download(
            new AttendanceExport($this->dateFrom, $this->dateTo, $this->filterStatus, $this->search),
            $filename
        );
    }

    #[Computed]
    public function stats(): array
    {
        // Fetch all assignments in the period
        $assignments = \App\Models\ScheduleAssignment::whereBetween('date', [$this->dateFrom, $this->dateTo])
            ->when($this->search, function ($q) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%")
                    ->orWhere('nim', 'like', "%{$this->search}%")
                );
            })
            ->get();

        // Fetch all attendances in the period
        $attendances = \App\Models\Attendance::whereBetween('date', [$this->dateFrom, $this->dateTo])
            ->when($this->search, function ($q) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%")
                    ->orWhere('nim', 'like', "%{$this->search}%")
                );
            })
            ->get();

        $stats = [
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'excused' => 0,
            'total' => 0,
        ];

        // 1. Process existing attendance records first
        foreach ($attendances as $att) {
            match ($att->status) {
                'present' => $stats['present']++,
                'late' => $stats['late']++,
                'absent' => $stats['absent']++,
                'excused' => $stats['excused']++,
                default => null,
            };
        }

        // 2. Identify "Virtual Absences" and "Pending Assignments"
        // We only care about assignments that DON'T have an attendance record yet
        $attendanceAssignmentIds = $attendances->pluck('schedule_assignment_id')->filter()->toArray();
        
        $sessionTimes = config('app-settings.session_times', [
            1 => ['start' => '07:30', 'end' => '10:00'],
            2 => ['start' => '10:20', 'end' => '12:50'],
            3 => ['start' => '13:30', 'end' => '16:00'],
        ]);
        $now = now();

        foreach ($assignments as $assignment) {
            // If this assignment already has an attendance record, skip it (already counted above)
            if (in_array($assignment->id, $attendanceAssignmentIds)) {
                continue;
            }

            // Check if this assignment should be counted as Absent or Excused
            if ($assignment->status === 'excused') {
                $stats['excused']++;
            } elseif ($assignment->status === 'missed') {
                $stats['absent']++;
            } elseif ($assignment->status === 'scheduled') {
                // If it's still 'scheduled', check if time has passed
                $sessionEndStr = $sessionTimes[$assignment->session]['end'] ?? '16:00';
                $sessionEnd = Carbon::parse($assignment->date->format('Y-m-d').' '.$sessionEndStr);
                if ($now->greaterThan($sessionEnd)) {
                    $stats['absent']++;
                }
            }
        }

        $stats['total'] = $stats['present'] + $stats['late'] + $stats['absent'] + $stats['excused'];
        $attended = $stats['present'] + $stats['late'];
        $stats['attendance_rate'] = $stats['total'] > 0 ? round(($attended / $stats['total']) * 100, 1) : 0;

        return $stats;
    }

    private function buildBaseQuery()
    {
        return Attendance::query()
            ->when($this->dateFrom, fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, function ($q) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%")
                    ->orWhere('nim', 'like', "%{$this->search}%")
                );
            });
    }

    public function render()
    {
        // 1. Get Actual Attendance Records
        $attendances = $this->buildBaseQuery()
            ->with(['user:id,name,nim,photo'])
            ->select(['id', 'user_id', 'date', 'check_in', 'check_in_photo', 'check_out', 'status', 'late_minutes', 'late_category', 'schedule_assignment_id'])
            ->orderByDesc('date')
            ->orderByDesc('check_in')
            ->get();

        $attendanceAssignmentIds = $attendances->pluck('schedule_assignment_id')->filter()->toArray();

        // 2. Get Missed Assignments (Virtual Absences)
        // Only if we are not filtering by a status that excludes absences
        $virtualAbsences = collect();
        if (empty($this->filterStatus) || $this->filterStatus === 'absent' || $this->filterStatus === 'excused') {
            $sessionTimes = config('app-settings.session_times', [
                1 => ['end' => '10:00'],
                2 => ['end' => '12:50'],
                3 => ['end' => '16:00'],
            ]);
            $now = now();

            $query = \App\Models\ScheduleAssignment::whereBetween('date', [$this->dateFrom, $this->dateTo])
                ->whereNotIn('id', $attendanceAssignmentIds)
                ->with(['user:id,name,nim,photo']);

            if ($this->search) {
                $query->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%")
                    ->orWhere('nim', 'like', "%{$this->search}%")
                );
            }

            $missedAssignments = $query->get();

            foreach ($missedAssignments as $assignment) {
                $status = null;
                if ($assignment->status === 'excused' && ($this->filterStatus === '' || $this->filterStatus === 'excused')) {
                    $status = 'excused';
                } elseif ($assignment->status === 'missed' && ($this->filterStatus === '' || $this->filterStatus === 'absent')) {
                    $status = 'absent';
                } elseif ($assignment->status === 'scheduled') {
                    $sessionEndStr = $sessionTimes[$assignment->session]['end'] ?? '16:00';
                    $sessionEnd = Carbon::parse($assignment->date->format('Y-m-d').' '.$sessionEndStr);
                    if ($now->greaterThan($sessionEnd) && ($this->filterStatus === '' || $this->filterStatus === 'absent')) {
                        $status = 'absent';
                    }
                }

                if ($status) {
                    $virtualAbsences->push((object)[
                        'id' => 'v' . $assignment->id,
                        'user_id' => $assignment->user_id,
                        'user' => $assignment->user,
                        'date' => $assignment->date,
                        'check_in' => null,
                        'check_out' => null,
                        'status' => $status,
                        'late_minutes' => null,
                        'late_category' => null,
                        'is_virtual' => true,
                        'original_id' => $assignment->id
                    ]);
                }
            }
        }

        // 3. Combine and Paginate
        $combined = collect($attendances)->concat($virtualAbsences)
            ->sortByDesc(fn($item) => $item->date->format('Y-m-d') . ($item->check_in ?? '00:00:00'));

        $currentPage = $this->getPage();
        $perPage = 20;
        $paginatedItems = new \Illuminate\Pagination\LengthAwarePaginator(
            $combined->forPage($currentPage, $perPage),
            $combined->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Support\Facades\Request::url(), 'query' => \Illuminate\Support\Facades\Request::query()]
        );

        return view('livewire.admin.attendance-management', [
            'attendances' => $paginatedItems,
        ])->layout('layouts.app')->title('Manajemen Absensi');
    }
}
