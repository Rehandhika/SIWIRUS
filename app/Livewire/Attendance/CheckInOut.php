<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use App\Models\ScheduleAssignment;
use App\Services\ActivityLogService;
use App\Services\AttendanceService;
use App\Services\NotificationService;
use App\Services\Storage\FileStorageServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    public $checkInPhotoPreview = null;

    public $scheduleStatus; // 'active', 'upcoming', 'past'

    public $showPhotoPreview = false;

    protected FileStorageServiceInterface $fileStorageService;
    protected AttendanceService $attendanceService;
    protected \App\Services\StoreStatusService $storeStatusService;

    protected $rules = [
        'checkInPhoto' => 'required|image|max:5120', // 5MB max
    ];

    protected $messages = [
        'checkInPhoto.required' => 'Foto bukti check-in wajib diunggah.',
        'checkInPhoto.image' => 'File harus berupa gambar (jpg, jpeg, png).',
        'checkInPhoto.max' => 'Ukuran foto maksimal 5MB.',
    ];

    public function boot(
        FileStorageServiceInterface $fileStorageService,
        AttendanceService $attendanceService,
        \App\Services\StoreStatusService $storeStatusService
    ) {
        $this->fileStorageService = $fileStorageService;
        $this->attendanceService = $attendanceService;
        $this->storeStatusService = $storeStatusService;
    }

    public function mount()
    {
        $this->loadCurrentSchedule();
    }

    /**
     * Refresh schedule status (called by polling)
     */
    public function refreshSchedule()
    {
        $this->loadCurrentSchedule();
    }

    /**
     * Load current schedule and attendance data
     */
    public function loadCurrentSchedule()
    {
        $user = auth()->user();
        $now = now();
        $today = today();
        $currentTime = $now->format('H:i:s');

        // Find current active schedule assignment
        // Priority 1: Find assignment that is currently active (within time range)
        $this->currentSchedule = ScheduleAssignment::where('user_id', $user->id)
            ->where('date', $today)
            ->where('status', 'scheduled')
            ->whereHas('schedule', function ($query) {
                $query->where('status', 'published');
            })
            ->where('time_start', '<=', $currentTime)
            ->where('time_end', '>=', $currentTime)
            ->with(['schedule', 'user'])
            ->first();

        // Priority 2: If no active assignment, find today's next upcoming assignment
        if (! $this->currentSchedule) {
            $this->currentSchedule = ScheduleAssignment::where('user_id', $user->id)
                ->where('date', $today)
                ->where('status', 'scheduled')
                ->whereHas('schedule', function ($query) {
                    $query->where('status', 'published');
                })
                ->where('time_start', '>', $currentTime)
                ->with(['schedule', 'user'])
                ->orderBy('time_start')
                ->first();
        }

        // Priority 3: If no upcoming, check if there's a past assignment today (for late check-in)
        if (! $this->currentSchedule) {
            $this->currentSchedule = ScheduleAssignment::where('user_id', $user->id)
                ->where('date', $today)
                ->where('status', 'scheduled')
                ->whereHas('schedule', function ($query) {
                    $query->where('status', 'published');
                })
                ->with(['schedule', 'user'])
                ->orderBy('time_start', 'desc')
                ->first();
        }

        // Load attendance data and determine schedule status
        if ($this->currentSchedule) {
            // Determine schedule status
            $scheduleStart = $this->currentSchedule->date->copy()->setTimeFromTimeString($this->currentSchedule->time_start);
            $scheduleEnd = $this->currentSchedule->date->copy()->setTimeFromTimeString($this->currentSchedule->time_end);

            if ($now->between($scheduleStart, $scheduleEnd)) {
                $this->scheduleStatus = 'active';
            } elseif ($now->lt($scheduleStart)) {
                $this->scheduleStatus = 'upcoming';
            } else {
                $this->scheduleStatus = 'past';
            }

            $this->currentAttendance = Attendance::where('user_id', $user->id)
                ->where('schedule_assignment_id', $this->currentSchedule->id)
                ->first();
        } else {
            $this->scheduleStatus = null;

            // Fallback A: User has checked-in for a published schedule that was marked completed on check-in (no checkout yet)
            $activeAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->whereNotNull('schedule_assignment_id')
                ->whereNull('check_out')
                ->latest('check_in')
                ->first();

            if ($activeAttendance) {
                // Load the related schedule assignment even if status already moved from 'scheduled'
                $assignment = ScheduleAssignment::with(['schedule', 'user'])
                    ->find($activeAttendance->schedule_assignment_id);

                if ($assignment) {
                    $this->currentSchedule = $assignment;
                    $this->currentAttendance = $activeAttendance;
                    $this->scheduleStatus = 'active';
                }
            }

            // Fallback B: Override attendance (no schedule)
            if (! $this->currentSchedule && ! $this->currentAttendance) {
                // Get the latest one to handle multiple sessions correctly
                $this->currentAttendance = Attendance::where('user_id', $user->id)
                    ->where('date', $today)
                    ->whereNull('schedule_assignment_id')
                    ->latest()
                    ->first();
            }
        }

        if ($this->currentAttendance) {
            $this->checkInTime = $this->currentAttendance->check_in?->format('H:i');
            $this->checkOutTime = $this->currentAttendance->check_out?->format('H:i');
        }
    }

    /**
     * Handle check-in with photo upload
     */
    public function checkIn()
    {
        try {
            $isOverride = false;

            // Validate schedule exists or override is allowed
            if (! $this->currentSchedule) {
                if ($this->storeStatusService->isOverrideActive()) {
                    $isOverride = true;
                } else {
                    throw new \Exception('Tidak ada jadwal aktif saat ini.');
                }
            }

            // Check if already checked in
            if ($this->currentAttendance && $this->currentAttendance->check_in) {
                throw new \Exception('Anda sudah check-in.');
            }

            // If schedule exists, validate status and timing
            if ($this->currentSchedule) {
                // Check if schedule is published
                if ($this->currentSchedule->schedule->status !== 'published') {
                    throw new \Exception('Jadwal belum dipublikasikan.');
                }

                // Validate timing
                $scheduleStart = $this->currentSchedule->date->copy()->setTimeFromTimeString($this->currentSchedule->time_start);
                $now = now();
                $tolerance = config('app-settings.attendance.allow_early_checkin_minutes', 30);

                if ($now->lt($scheduleStart->copy()->subMinutes($tolerance))) {
                    throw new \Exception("Belum waktunya check-in. Check-in dapat dilakukan {$tolerance} menit sebelum jadwal dimulai.");
                }
            }

            // Validate photo and notes
            $this->validate();

            // Store photo using FileStorageService
            $photoPath = $this->storeCheckInPhoto();

            // Use AttendanceService to create check-in
            $this->currentAttendance = $this->attendanceService->checkIn(
                userId: auth()->id(),
                scheduleAssignmentId: $this->currentSchedule?->id,
                notes: null
            );
            
            // Update photo path since Service doesn't handle photo upload in checkIn method yet
            // Or we could pass it to notes, but better to update model directly here
            $this->currentAttendance->update(['check_in_photo' => $photoPath]);

            $now = $this->currentAttendance->check_in;
            $this->checkInTime = $now->format('H:i');

            // Log activity
            $sessionLabel = $this->currentSchedule ? ($this->currentSchedule->session_label ?? 'Sesi '.$this->currentSchedule->session) : 'Check-in Luar Jadwal';
            ActivityLogService::logCheckIn($sessionLabel, $now->format('H:i'));

            // Send notification
            $notifTitle = $isOverride ? 'Check-in Luar Jadwal Berhasil' : 'Check-in Berhasil';
            $notifMessage = $isOverride 
                ? "Check-in berhasil pada {$now->format('H:i')} (Tanpa Jadwal)"
                : "Check-in berhasil pada {$now->format('H:i')} untuk jadwal {$this->currentSchedule->day_label} {$this->currentSchedule->session_label}";

            NotificationService::send(
                auth()->user(),
                'check_in_success',
                $notifTitle,
                $notifMessage
            );

            // Create toast message with late details if applicable
            $toastMessage = 'Check-in berhasil!';
            if ($this->currentAttendance->status === 'late') {
                $toastMessage .= " Terlambat {$this->currentAttendance->late_category} ({$this->currentAttendance->late_minutes} menit)";
            } else {
                $toastMessage .= ' Waktu: '.$now->format('H:i');
            }

            $this->dispatch('toast', message: $toastMessage, type: $this->currentAttendance->status === 'late' ? 'warning' : 'success');

            // Reset form
            $this->reset(['checkInPhoto', 'checkInPhotoPreview', 'showPhotoPreview']);

            // Reload schedule data
            $this->loadCurrentSchedule();

            // Notify dashboards/widgets to refresh
            $this->dispatch('attendance-updated');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to show field errors
            throw $e;
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            Log::error('Check-in error: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'schedule_id' => $this->currentSchedule?->id,
            ]);
        }
    }

    /**
     * Store check-in photo using FileStorageService.
     *
     * @return string Photo path
     */
    protected function storeCheckInPhoto(): string
    {
        try {
            $result = $this->fileStorageService->upload($this->checkInPhoto, 'attendance', [
                'day' => now()->day,
                'user_id' => auth()->id(),
            ]);

            return $result->path;
        } catch (\Exception $e) {
            Log::warning('CheckInOut: FileStorageService upload failed, using fallback', [
                'error' => $e->getMessage(),
            ]);

            // Fallback to direct storage
            return $this->checkInPhoto->store('attendance/check-in', 'public');
        }
    }

    /**
     * Handle check-out (no photo required)
     */
    public function checkOut()
    {
        try {
            // Validate attendance exists
            if (! $this->currentAttendance || ! $this->currentAttendance->check_in) {
                throw new \Exception('Anda belum check-in.');
            }

            // Check if already checked out
            if ($this->currentAttendance->check_out) {
                throw new \Exception('Anda sudah check-out.');
            }

            // Use AttendanceService to process check-out (handles work hours and schedule status)
            $this->currentAttendance = $this->attendanceService->checkOut($this->currentAttendance->id);

            $now = $this->currentAttendance->check_out;
            $this->checkOutTime = $now->format('H:i');
            $workingHours = $this->currentAttendance->work_hours;

            // Log activity
            $sessionLabel = $this->currentSchedule ? ($this->currentSchedule->session_label ?? 'Sesi '.$this->currentSchedule->session) : 'Check-in Luar Jadwal';
            ActivityLogService::logCheckOut($sessionLabel, $now->format('H:i'), number_format($workingHours, 2));

            // Send notification
            NotificationService::send(
                auth()->user(),
                'check_out_success',
                'Check-out Berhasil',
                "Check-out berhasil pada {$now->format('H:i')}. Total jam kerja: ".number_format($workingHours, 2).' jam'
            );

            $this->dispatch('toast', message: 'Check-out berhasil! Total jam kerja: '.number_format($workingHours, 2).' jam', type: 'success');

            // Reload schedule data
            $this->loadCurrentSchedule();

            // Notify dashboards/widgets to refresh
            $this->dispatch('attendance-updated');

        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
            Log::error('Check-out error: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'attendance_id' => $this->currentAttendance?->id,
            ]);
        }
    }

    /**
     * Preview uploaded photo
     */
    public function updatedCheckInPhoto()
    {
        $this->validate([
            'checkInPhoto' => 'image|max:5120',
        ]);

        if ($this->checkInPhoto) {
            $this->checkInPhotoPreview = $this->checkInPhoto->temporaryUrl();
        }
        $this->showPhotoPreview = true;
    }

    /**
     * Remove uploaded photo
     */
    public function removePhoto()
    {
        $this->reset(['checkInPhoto', 'checkInPhotoPreview', 'showPhotoPreview']);
    }

    /**
     * Check if user can check-in now
     */
    public function canCheckInNow(): bool
    {
        if ($this->currentAttendance && $this->currentAttendance->check_in) {
            return false;
        }

        if (! $this->currentSchedule) {
            // Allow check-in if override mode is enabled
            return $this->storeStatusService->isOverrideActive();
        }

        // Allow check-in with tolerance
        $scheduleStart = $this->currentSchedule->date->copy()->setTimeFromTimeString($this->currentSchedule->time_start);
        $now = now();
        $tolerance = config('app-settings.attendance.allow_early_checkin_minutes', 30);

        return $now->gte($scheduleStart->copy()->subMinutes($tolerance));
    }

    /**
     * Get time until check-in is available
     */
    public function getTimeUntilCheckIn(): ?string
    {
        if (! $this->currentSchedule || $this->scheduleStatus !== 'upcoming') {
            return null;
        }

        $scheduleStart = $this->currentSchedule->date->copy()->setTimeFromTimeString($this->currentSchedule->time_start);
        $tolerance = config('app-settings.attendance.allow_early_checkin_minutes', 30);
        $checkInAvailable = $scheduleStart->copy()->subMinutes($tolerance);

        return $checkInAvailable->diffForHumans();
    }

    /**
     * Get check-in photo URL.
     */
    public function getCheckInPhotoUrl(): ?string
    {
        if (! $this->currentAttendance || ! $this->currentAttendance->check_in_photo) {
            return null;
        }

        try {
            return $this->fileStorageService->getUrl($this->currentAttendance->check_in_photo, 'thumbnail');
        } catch (\Exception $e) {
            // Fallback to direct URL
            if (Storage::disk('public')->exists($this->currentAttendance->check_in_photo)) {
                return Storage::disk('public')->url($this->currentAttendance->check_in_photo);
            }

            return null;
        }
    }

    public function render()
    {
        return view('livewire.attendance.check-in-out', [
            'canCheckIn' => $this->canCheckInNow(),
            'timeUntilCheckIn' => $this->getTimeUntilCheckIn(),
            'checkInPhotoUrl' => $this->getCheckInPhotoUrl(),
            'isOverrideActive' => $this->storeStatusService->isOverrideActive(),
        ])->layout('layouts.app');
    }
}
