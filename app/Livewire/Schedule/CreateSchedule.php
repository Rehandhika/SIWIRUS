<?php

namespace App\Livewire\Schedule;

use App\Models\Schedule;
use App\Models\ScheduleAssignment;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Buat Jadwal Baru')]
#[Layout('layouts.app')]
class CreateSchedule extends Component
{
    // Schedule data
    public $weekStartDate;

    public $weekEndDate;

    public $notes = '';

    // Multi-user assignments: [date][session] = [user1, user2, ...]
    public $assignments = [];

    // UI state
    public $selectedDate = null;

    public $selectedSession = null;

    public $showUserSelector = false;

    public $availableUsers = [];

    // Statistics
    public $totalAssignments = 0;

    public $coverageRate = 0;

    public $assignmentsPerUser = [];

    public $emptySlots = 0;

    // Conflicts
    public $conflicts = [];

    // Loading states
    public $isSaving = false;

    // Undo/Redo functionality
    public $history = [];

    public $historyIndex = -1;

    public $maxHistorySteps = 20;

    public $canUndo = false;

    public $canRedo = false;

    protected function rules()
    {
        return [
            'weekStartDate' => 'required|date',
            'weekEndDate' => 'required|date|after:weekStartDate',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function mount()
    {
        // Set default dates (next Monday)
        $nextMonday = Carbon::now()->next(Carbon::MONDAY);
        $this->weekStartDate = $nextMonday->format('Y-m-d');
        $this->weekEndDate = $nextMonday->copy()->addDays(3)->format('Y-m-d');

        $this->initializeGrid();
        $this->recalculateStatistics();
        $this->detectConflicts();
        $this->saveToHistory();
    }

    /**
     * Handle weekStartDate change - reinitialize grid with new dates
     * This is the KEY fix for "Slot tidak valid" error
     */
    public function updatedWeekStartDate($value): void
    {
        // Validate the date is parseable
        try {
            $startDate = Carbon::parse($value);
        } catch (\Exception $e) {
            return;
        }

        // Auto-adjust to Monday if not already Monday
        if ($startDate->dayOfWeek !== Carbon::MONDAY) {
            $startDate = $startDate->startOfWeek(Carbon::MONDAY);
            $this->weekStartDate = $startDate->format('Y-m-d');
        }

        // Auto-update weekEndDate to Thursday (+3 days)
        $this->weekEndDate = $startDate->copy()->addDays(3)->format('Y-m-d');

        // Store old assignments before reinitializing
        $oldAssignments = $this->assignments;

        // Reinitialize grid with new dates
        $this->initializeGrid();

        // Migrate any assignments that still fall within the new date range
        foreach ($oldAssignments as $date => $sessions) {
            if (isset($this->assignments[$date])) {
                // This date exists in new grid, preserve assignments
                foreach ($sessions as $session => $users) {
                    if (isset($this->assignments[$date][$session]) && ! empty($users)) {
                        $this->assignments[$date][$session] = $users;
                    }
                }
            }
        }

        // Reset UI state to prevent stale selection
        $this->selectedDate = null;
        $this->selectedSession = null;
        $this->showUserSelector = false;
        $this->availableUsers = [];

        // Recalculate everything
        $this->recalculateStatistics();
        $this->detectConflicts();
        $this->saveToHistory();
    }

    /**
     * Handle weekEndDate change
     */
    public function updatedWeekEndDate($value): void
    {
        // Validate the date is parseable
        try {
            $endDate = Carbon::parse($value);
            $startDate = Carbon::parse($this->weekStartDate);
        } catch (\Exception $e) {
            return;
        }

        // Ensure end date is after start date
        if ($endDate->lte($startDate)) {
            $this->weekEndDate = $startDate->copy()->addDays(3)->format('Y-m-d');
        }

        // For this system, we always use 4 days (Mon-Thu), so weekEndDate change
        // doesn't affect the grid structure. Just validate it.
    }

    /**
     * Initialize empty grid for multi-user slots
     * Creates a fresh grid structure based on current weekStartDate
     */
    public function initializeGrid(): void
    {
        // Clear existing assignments first to prevent stale data
        $this->assignments = [];

        $startDate = Carbon::parse($this->weekStartDate);

        for ($day = 0; $day < 4; $day++) {
            $date = $startDate->copy()->addDays($day);
            $dateStr = $date->format('Y-m-d');

            $this->assignments[$dateStr] = [];

            for ($session = 1; $session <= 3; $session++) {
                $this->assignments[$dateStr][$session] = []; // Empty array for multi-user
            }
        }
    }

    /**
     * Check if a slot exists in the current grid
     */
    private function isValidSlot(string $date, int $session): bool
    {
        return isset($this->assignments[$date]) && isset($this->assignments[$date][$session]);
    }

    /**
     * Ensure grid is synchronized with current weekStartDate
     * Returns true if grid was reinitialized
     */
    private function ensureGridSync(): bool
    {
        $startDate = Carbon::parse($this->weekStartDate);
        $expectedFirstDate = $startDate->format('Y-m-d');

        // Check if grid has the expected first date
        $gridDates = array_keys($this->assignments);

        if (empty($gridDates) || ! in_array($expectedFirstDate, $gridDates)) {
            // Grid is out of sync, reinitialize
            $this->initializeGrid();

            return true;
        }

        return false;
    }

    /**
     * Get slot assignments (multi-user support)
     * Safe accessor with null handling
     */
    public function getSlotAssignments(string $date, int $session): array
    {
        // Ensure grid is in sync before returning data
        if (! $this->isValidSlot($date, $session)) {
            return [];
        }

        return $this->assignments[$date][$session] ?? [];
    }

    /**
     * Select a cell to assign user
     * With defensive checks to prevent "Slot tidak valid" error
     */
    public function selectCell(string $date, int $session): void
    {
        // Ensure grid is in sync with current weekStartDate
        $wasResynced = $this->ensureGridSync();

        // Validate the slot exists in current grid
        if (! $this->isValidSlot($date, $session)) {
            // The date doesn't belong to current week period
            $this->dispatch('toast', message: 'Tanggal tidak sesuai dengan periode jadwal. Grid telah disegarkan.', type: 'warning');

            // Reset selection state
            $this->selectedDate = null;
            $this->selectedSession = null;
            $this->showUserSelector = false;
            $this->availableUsers = [];

            return;
        }

        $this->selectedDate = $date;
        $this->selectedSession = $session;
        $this->showUserSelector = true;
        $this->loadAvailableUsers($date, $session);
    }

    /**
     * Load available users for a slot
     * With defensive validation
     */
    private function loadAvailableUsers(string $date, int $session): void
    {
        // Validate slot exists
        if (! $this->isValidSlot($date, $session)) {
            $this->availableUsers = [];

            return;
        }

        $dayName = strtolower(Carbon::parse($date)->englishDayOfWeek);

        $this->availableUsers = User::where('status', 'active')
            ->with(['availabilities' => function ($query) {
                $query->where('week_start_date', $this->weekStartDate)
                    ->where('status', 'submitted');
            }, 'availabilities.details' => function ($query) use ($dayName, $session) {
                $query->where('day', $dayName)
                    ->where('session', $session);
            }])
            ->get()
            ->map(function ($user) use ($date, $session, $dayName) {
                // Check if user already in this slot (with null safety)
                $currentSlot = $this->assignments[$date][$session] ?? [];
                $hasConflict = collect($currentSlot)->contains('user_id', $user->id);

                // Count current assignments
                $currentAssignments = 0;
                foreach ($this->assignments as $dateAssignments) {
                    foreach ($dateAssignments as $sessionAssignments) {
                        $currentAssignments += collect($sessionAssignments)->where('user_id', $user->id)->count();
                    }
                }

                // Check availability
                $isAvailable = false;
                $isNotAvailable = false;

                foreach ($user->availabilities as $availability) {
                    foreach ($availability->details as $detail) {
                        if ($detail->day === $dayName && $detail->session == $session) {
                            if ($detail->is_available) {
                                $isAvailable = true;
                            } else {
                                $isNotAvailable = true;
                            }
                        }
                    }
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nim' => $user->nim,
                    'photo' => $user->photo,
                    'is_available' => $isAvailable,
                    'is_not_available' => $isNotAvailable,
                    'has_conflict' => $hasConflict,
                    'current_assignments' => $currentAssignments,
                ];
            })
            ->sortBy([
                ['has_conflict', 'asc'],
                ['is_not_available', 'asc'],
                ['is_available', 'desc'],
                ['current_assignments', 'asc'],
            ])
            ->values()
            ->toArray();
    }

    /**
     * Add user to slot (multi-user support)
     * With comprehensive validation to prevent "Slot tidak valid" error
     */
    public function assignUser(int $userId): void
    {
        // Validate selection exists
        if (! $this->selectedDate || ! $this->selectedSession) {
            $this->dispatch('toast', message: 'Pilih slot terlebih dahulu.', type: 'error');

            return;
        }

        // Ensure grid is synchronized
        $this->ensureGridSync();

        // Check if date and session exist in assignments
        if (! $this->isValidSlot($this->selectedDate, $this->selectedSession)) {
            // Try to recover by closing modal and showing helpful message
            $this->showUserSelector = false;
            $this->selectedDate = null;
            $this->selectedSession = null;
            $this->availableUsers = [];

            $this->dispatch('toast', message: 'Slot tidak valid. Grid telah disegarkan, silakan pilih slot kembali.', type: 'error');

            return;
        }

        $user = User::find($userId);
        if (! $user || $user->status !== 'active') {
            $this->dispatch('toast', message: 'User tidak valid atau tidak aktif.', type: 'error');

            return;
        }

        // Check if user already in this slot
        $currentSlot = $this->assignments[$this->selectedDate][$this->selectedSession];
        if (collect($currentSlot)->contains('user_id', $user->id)) {
            $this->dispatch('toast', message: 'User sudah ada di slot ini.', type: 'error');

            return;
        }

        // Check availability warning
        $availabilityWarning = $this->checkAvailabilityMismatch($userId, $this->selectedDate, $this->selectedSession);

        // Add user to slot
        $this->assignments[$this->selectedDate][$this->selectedSession][] = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_nim' => $user->nim,
            'user_photo' => $user->photo,
            'has_availability_warning' => $availabilityWarning,
        ];

        $this->recalculateStatistics();
        $this->detectConflicts();
        $this->saveToHistory();

        $this->showUserSelector = false;
        $this->dispatch('toast', message: 'User berhasil ditambahkan.', type: 'success');
    }

    /**
     * Remove user from slot
     * With defensive validation
     */
    public function removeUserFromSlot(string $date, int $session, int $userId): void
    {
        // Ensure grid is synchronized
        $this->ensureGridSync();

        // Validate slot exists
        if (! $this->isValidSlot($date, $session)) {
            $this->dispatch('toast', message: 'Slot tidak valid. Grid telah disegarkan.', type: 'error');

            return;
        }

        $slot = $this->assignments[$date][$session] ?? [];

        // Check if user exists in slot before removing
        $userExists = collect($slot)->contains('user_id', $userId);
        if (! $userExists) {
            $this->dispatch('toast', message: 'User tidak ditemukan di slot ini.', type: 'warning');

            return;
        }

        $this->assignments[$date][$session] = collect($slot)
            ->reject(fn ($assignment) => $assignment['user_id'] == $userId)
            ->values()
            ->toArray();

        $this->recalculateStatistics();
        $this->detectConflicts();
        $this->saveToHistory();

        $this->dispatch('toast', message: 'User berhasil dihapus.', type: 'success');
    }

    /**
     * Check availability mismatch
     */
    private function checkAvailabilityMismatch(int $userId, string $date, int $session): bool
    {
        $carbonDate = Carbon::parse($date);
        $dayName = strtolower($carbonDate->englishDayOfWeek);
        $weekStart = $carbonDate->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $user = User::with(['availabilities' => function ($query) use ($weekStart) {
            $query->where('week_start_date', $weekStart)->where('status', 'submitted');
        }, 'availabilities.details' => function ($query) use ($dayName, $session) {
            $query->where('day', $dayName)->where('session', $session);
        }])->find($userId);

        if (! $user) {
            return false;
        }

        foreach ($user->availabilities as $availability) {
            foreach ($availability->details as $detail) {
                if ($detail->day === $dayName && $detail->session == $session && ! $detail->is_available) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Detect conflicts
     */
    private function detectConflicts(): void
    {
        $this->conflicts = ['critical' => [], 'warning' => []];

        // Check for availability mismatches
        foreach ($this->assignments as $date => $sessions) {
            foreach ($sessions as $session => $users) {
                foreach ($users as $assignment) {
                    if ($assignment['has_availability_warning'] ?? false) {
                        $this->conflicts['warning'][] = [
                            'message' => "User {$assignment['user_name']} tidak tersedia pada ".
                                         Carbon::parse($date)->format('d M')." Sesi {$session}",
                        ];
                    }
                }
            }
        }

        // Check for overloaded users
        $userCounts = [];
        foreach ($this->assignments as $sessions) {
            foreach ($sessions as $users) {
                foreach ($users as $assignment) {
                    $userId = $assignment['user_id'];
                    $userCounts[$userId] = ($userCounts[$userId] ?? 0) + 1;
                }
            }
        }

        foreach ($userCounts as $userId => $count) {
            if ($count > 4) {
                $userName = collect($this->assignments)->flatten(2)->firstWhere('user_id', $userId)['user_name'] ?? 'Unknown';
                $this->conflicts['warning'][] = [
                    'message' => "User {$userName} memiliki terlalu banyak shift ({$count} shift)",
                ];
            }
        }
    }

    /**
     * Recalculate statistics
     */
    public function recalculateStatistics(): void
    {
        $this->totalAssignments = 0;
        $this->emptySlots = 0;
        $userCounts = [];

        foreach ($this->assignments as $sessions) {
            foreach ($sessions as $users) {
                if (empty($users)) {
                    $this->emptySlots++;
                } else {
                    $this->totalAssignments += count($users);
                    foreach ($users as $assignment) {
                        $userId = $assignment['user_id'];
                        if (! isset($userCounts[$userId])) {
                            $userCounts[$userId] = [
                                'user_name' => $assignment['user_name'],
                                'count' => 0,
                            ];
                        }
                        $userCounts[$userId]['count']++;
                    }
                }
            }
        }

        $this->coverageRate = round((($this->totalAssignments > 0 ? 12 - $this->emptySlots : 0) / 12) * 100, 1);
        $this->assignmentsPerUser = collect($userCounts)->sortByDesc('count')->values()->toArray();
    }

    /**
     * Save to history for undo/redo
     */
    private function saveToHistory(): void
    {
        $snapshot = json_decode(json_encode($this->assignments), true);

        if ($this->historyIndex < count($this->history) - 1) {
            $this->history = array_slice($this->history, 0, $this->historyIndex + 1);
        }

        $this->history[] = $snapshot;

        if (count($this->history) > $this->maxHistorySteps) {
            array_shift($this->history);
        } else {
            $this->historyIndex++;
        }

        $this->updateUndoRedoState();
    }

    public function undo(): void
    {
        if (! $this->canUndo) {
            return;
        }

        $this->historyIndex--;
        $this->restoreFromHistory();
        $this->dispatch('toast', message: 'Undo berhasil.', type: 'success');
    }

    public function redo(): void
    {
        if (! $this->canRedo) {
            return;
        }

        $this->historyIndex++;
        $this->restoreFromHistory();
        $this->dispatch('toast', message: 'Redo berhasil.', type: 'success');
    }

    private function restoreFromHistory(): void
    {
        if ($this->historyIndex >= 0 && $this->historyIndex < count($this->history)) {
            $this->assignments = $this->history[$this->historyIndex];
            $this->recalculateStatistics();
            $this->detectConflicts();
            $this->updateUndoRedoState();
        }
    }

    private function updateUndoRedoState(): void
    {
        $this->canUndo = $this->historyIndex > 0;
        $this->canRedo = $this->historyIndex < count($this->history) - 1;
    }

    /**
     * Save as draft
     */
    public function saveDraft()
    {
        $this->validate();

        if ($this->totalAssignments === 0) {
            $this->dispatch('toast', message: 'Tidak ada assignment untuk disimpan.', type: 'warning');

            return;
        }

        $this->isSaving = true;

        try {
            DB::beginTransaction();

            $schedule = Schedule::create([
                'week_start_date' => $this->weekStartDate,
                'week_end_date' => $this->weekEndDate,
                'notes' => $this->notes,
                'status' => 'draft',
                'total_slots' => 12,
            ]);

            $this->saveAssignments($schedule);

            DB::commit();

            // Log activity
            ActivityLogService::logScheduleCreated('Draft', Carbon::parse($this->weekStartDate)->format('d M Y'));

            // Dispatch global event for other components to listen
            $this->dispatch('schedule-updated');

            $this->dispatch('toast', message: 'Jadwal berhasil disimpan sebagai draft!', type: 'success');

            return $this->redirect(route('admin.schedule.index'), navigate: true);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Gagal menyimpan: '.$e->getMessage(), type: 'error');
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Publish schedule
     */
    public function publish()
    {
        $this->validate();

        if ($this->totalAssignments === 0) {
            $this->dispatch('toast', message: 'Tidak ada assignment untuk dipublish.', type: 'error');

            return;
        }

        if (! empty($this->conflicts['critical'])) {
            $this->dispatch('toast', message: 'Tidak dapat publish dengan critical conflicts.', type: 'error');

            return;
        }

        $this->isSaving = true;

        try {
            DB::beginTransaction();

            $schedule = Schedule::create([
                'week_start_date' => $this->weekStartDate,
                'week_end_date' => $this->weekEndDate,
                'notes' => $this->notes,
                'status' => 'published',
                'total_slots' => 12,
                'published_at' => now(),
                'published_by' => auth()->id(),
            ]);

            $this->saveAssignments($schedule);

            DB::commit();

            // Log activity
            ActivityLogService::logScheduleCreated('Published', Carbon::parse($this->weekStartDate)->format('d M Y'));

            // Dispatch global event for other components to listen
            $this->dispatch('schedule-updated');

            $this->dispatch('toast', message: 'Jadwal berhasil dipublish!', type: 'success');

            return $this->redirect(route('admin.schedule.index'), navigate: true);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Gagal publish: '.$e->getMessage(), type: 'error');
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Save assignments to database
     */
    private function saveAssignments(Schedule $schedule): void
    {
        foreach ($this->assignments as $date => $sessions) {
            foreach ($sessions as $session => $users) {
                foreach ($users as $assignment) {
                    ScheduleAssignment::create([
                        'schedule_id' => $schedule->getKey(),
                        'user_id' => $assignment['user_id'],
                        'date' => $date,
                        'session' => $session,
                        'day' => strtolower(Carbon::parse($date)->englishDayOfWeek),
                        'time_start' => $this->getSessionStartTime($session),
                        'time_end' => $this->getSessionEndTime($session),
                    ]);
                }
            }
        }
    }

    private function getSessionStartTime(int $session): string
    {
        return ['07:30', '10:20', '13:30'][$session - 1] ?? '07:30';
    }

    private function getSessionEndTime(int $session): string
    {
        return ['10:00', '12:50', '16:00'][$session - 1] ?? '10:00';
    }

    public function getSessionTime(int $session): string
    {
        $times = [
            1 => '07:30 - 10:00',
            2 => '10:20 - 12:50',
            3 => '13:30 - 16:00',
        ];

        return $times[$session] ?? '';
    }

    public function render()
    {
        return view('livewire.schedule.create-schedule');
    }
}
