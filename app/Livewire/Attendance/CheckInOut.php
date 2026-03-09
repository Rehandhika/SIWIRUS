<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use App\Models\ScheduleAssignment;
use App\Services\ActivityLogService;
use App\Services\AttendanceService;
use App\Services\StoreStatusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class CheckInOut extends Component
{
    use WithFileUploads;

    public $currentSchedule;
    public $currentAttendance;
    public $checkInTime;
    public $checkOutTime;
    public $checkInPhoto;
    public $scheduleStatus; 

    // Upload state management — driven by Alpine via events
    public bool $photoReady = false;

    public function mount()
    {
        $this->loadCurrentSchedule();
    }

    public function loadCurrentSchedule()
    {
        $user = auth()->user();
        if (!$user) return;

        $today = today();
        $currentTime = now()->format('H:i:s');

        // PRIORITY 0: Active attendance session (any day)
        $this->currentAttendance = Attendance::where('user_id', $user->id)
            ->whereNull('check_out')
            ->latest('check_in')
            ->first();

        if ($this->currentAttendance) {
            if ($this->currentAttendance->schedule_assignment_id) {
                $this->currentSchedule = ScheduleAssignment::with(['schedule'])->find($this->currentAttendance->schedule_assignment_id);
            }
            $this->scheduleStatus = 'active';
            $this->checkInTime = $this->currentAttendance->check_in?->format('H:i');
            return;
        }

        // Priority 1: Current active assignment
        $this->currentSchedule = ScheduleAssignment::where('user_id', $user->id)
            ->where('date', $today)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->whereDoesntHave('attendance')
            ->whereHas('schedule', fn($q) => $q->where('status', 'published'))
            ->where('time_start', '<=', $currentTime)
            ->where('time_end', '>=', $currentTime)
            ->first();

        // Priority 2: Next upcoming assignment
        if (! $this->currentSchedule) {
            $this->currentSchedule = ScheduleAssignment::where('user_id', $user->id)
                ->where('date', $today)
                ->where('status', 'scheduled')
                ->whereDoesntHave('attendance')
                ->whereHas('schedule', fn($q) => $q->where('status', 'published'))
                ->where('time_start', '>', $currentTime)
                ->orderBy('time_start')
                ->first();
        }

        if ($this->currentSchedule) {
            $start = Carbon::parse($this->currentSchedule->time_start);
            $end = Carbon::parse($this->currentSchedule->time_end);
            $now = now();

            if ($now->between($start, $end)) {
                $this->scheduleStatus = 'active';
            } elseif ($now->lt($start)) {
                $this->scheduleStatus = 'upcoming';
            } else {
                $this->scheduleStatus = 'past';
            }
        } else {
            $this->scheduleStatus = null;
            $this->currentAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->whereNull('schedule_assignment_id')
                ->whereNull('check_out')
                ->latest()
                ->first();
        }

        if ($this->currentAttendance) {
            $this->checkInTime = $this->currentAttendance->check_in?->format('H:i');
            $this->checkOutTime = $this->currentAttendance->check_out?->format('H:i');
        }
    }

    /**
     * Called by Alpine after Livewire temp upload finishes successfully.
     * This sets the flag that enables the check-in button.
     */
    public function markPhotoReady()
    {
        if ($this->checkInPhoto) {
            $this->photoReady = true;
        }
    }

    /**
     * Called when user wants to remove selected photo and retry.
     */
    public function removePhoto()
    {
        $this->reset(['checkInPhoto', 'photoReady']);
    }

    public function checkIn()
    {
        try {
            $storeStatus = app(StoreStatusService::class);
            if (!$this->currentSchedule && !$storeStatus->isOverrideActive()) {
                throw new \Exception('Tidak ada jadwal aktif.');
            }

            if ($this->currentAttendance?->check_in) {
                throw new \Exception('Anda sudah check-in.');
            }

            // Strict backend validation
            $this->validate([
                'checkInPhoto' => 'required|image|max:10240', // max 10MB
            ], [
                'checkInPhoto.required' => 'Foto check-in wajib diunggah.',
                'checkInPhoto.image' => 'File harus berupa gambar.',
                'checkInPhoto.max' => 'Ukuran foto maksimal 10MB.',
            ]);

            $photoPath = $this->checkInPhoto->store('attendance/check-in', 'public');

            $attendanceService = app(AttendanceService::class);
            $this->currentAttendance = $attendanceService->checkIn(
                userId: auth()->id(),
                scheduleAssignmentId: $this->currentSchedule?->id
            );
            
            $this->currentAttendance->update(['check_in_photo' => $photoPath]);

            ActivityLogService::logCheckIn(
                $this->currentSchedule ? ($this->currentSchedule->session_label ?? 'Sesi '.$this->currentSchedule->session) : 'Luar Jadwal',
                now()->format('H:i')
            );

            $this->reset(['checkInPhoto', 'photoReady']);
            $this->loadCurrentSchedule();
            $this->dispatch('toast', message: 'Check-in berhasil!', type: 'success');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw so Livewire shows @error messages
            throw $e;
        } catch (\Exception $e) {
            Log::error('CheckIn Error: ' . $e->getMessage());
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function checkOut()
    {
        try {
            if (!$this->currentAttendance?->check_in) throw new \Exception('Belum check-in.');
            
            $attendanceService = app(AttendanceService::class);
            $this->currentAttendance = $attendanceService->checkOut($this->currentAttendance->id);

            ActivityLogService::logCheckOut(
                $this->currentSchedule ? ($this->currentSchedule->session_label ?? 'Sesi '.$this->currentSchedule->session) : 'Luar Jadwal',
                now()->format('H:i'),
                number_format($this->currentAttendance->work_hours, 2)
            );

            $this->dispatch('toast', message: 'Check-out berhasil!', type: 'success');
            $this->loadCurrentSchedule();
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function canCheckInNow(): bool
    {
        if ($this->currentAttendance?->check_in) return false;
        if (!$this->currentSchedule) return app(StoreStatusService::class)->isOverrideActive();

        $now = now();
        $start = Carbon::parse($this->currentSchedule->time_start)->subMinutes(30);
        $end = Carbon::parse($this->currentSchedule->time_end);

        return $now->gte($start) && $now->lte($end);
    }

    public function getTimeUntilCheckIn(): ?string
    {
        if (!$this->currentSchedule || $this->scheduleStatus !== 'upcoming') return null;
        return Carbon::parse($this->currentSchedule->time_start)->subMinutes(30)->diffForHumans();
    }

    public function render()
    {
        return view('livewire.attendance.check-in-out', [
            'canCheckIn' => $this->canCheckInNow(),
            'timeUntilCheckIn' => $this->getTimeUntilCheckIn(),
            'isOverrideActive' => app(StoreStatusService::class)->isOverrideActive(),
        ])->layout('layouts.app');
    }
}
