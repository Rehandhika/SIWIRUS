<?php

namespace App\Livewire\Admin;

use App\Exports\AttendanceExport;
use App\Models\Attendance;
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

        $attendance->update($updateData);

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
        return Cache::remember($this->getStatsCacheKey(), 60, function () {
            $query = $this->buildBaseQuery();

            $result = $query->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused
            ")->first();

            $total = $result->total ?: 1;

            return [
                'total' => (int) $result->total,
                'present' => (int) $result->present,
                'late' => (int) $result->late,
                'absent' => (int) $result->absent,
                'excused' => (int) $result->excused,
                'attendance_rate' => round((($result->present + $result->late) / $total) * 100, 1),
            ];
        });
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
        $attendances = $this->buildBaseQuery()
            ->with(['user:id,name,nim,photo'])
            ->select(['id', 'user_id', 'date', 'check_in', 'check_in_photo', 'check_out', 'status'])
            ->orderByDesc('date')
            ->orderByDesc('check_in')
            ->paginate(20);

        return view('livewire.admin.attendance-management', [
            'attendances' => $attendances,
        ])->layout('layouts.app')->title('Manajemen Absensi');
    }
}
