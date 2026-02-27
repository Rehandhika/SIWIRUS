<?php

namespace App\Livewire\Schedule;

use App\Models\Schedule;
use App\Models\ScheduleAssignment;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\ConflictDetectionService;
use App\Services\ScheduleConfigurationService;
use App\Services\ScheduleEditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * EditSchedule Livewire Component
 *
 * Main interface for editing published schedules with multi-user slot support.
 * Provides real-time conflict detection, change tracking, and undo functionality.
 *
 * Features:
 * - Multi-user slot management (add, remove, update, clear)
 * - Real-time conflict detection and validation
 * - Change tracking with undo capability
 * - Audit trail recording
 * - User notifications
 * - Cache invalidation
 */
class EditSchedule extends Component
{
    /**
     * Schedule being edited
     */
    public Schedule $schedule;

    /**
     * Current assignments grouped by slot
     * Structure: [date][session] = Collection of assignments
     */
    public array $assignments = [];

    /**
     * Original assignments for change tracking
     */
    public array $originalAssignments = [];

    /**
     * Tracked changes for undo functionality
     * Structure: [{action, data, timestamp}, ...]
     */
    public array $changes = [];

    /**
     * Detected conflicts
     */
    public array $conflicts = [];

    /**
     * Edit mode: 'single', 'bulk', 'multi'
     */
    public string $editMode = 'single';

    /**
     * Currently selected slot
     */
    public ?array $selectedSlot = null;

    /**
     * Show user selector modal
     */
    public bool $showUserSelector = false;

    /**
     * Show multi-user management modal
     */
    public bool $showMultiUserModal = false;

    /**
     * Collection of affected users
     */
    public Collection $affectedUsers;

    /**
     * Flag for unsaved changes
     */
    public bool $hasUnsavedChanges = false;

    /**
     * Maximum users per slot (from configuration)
     */
    public int $maxUsersPerSlot = 5;

    /**
     * Allow empty slots (from configuration)
     */
    public bool $allowEmptySlots = true;

    /**
     * Available users for assignment
     */
    public Collection $availableUsers;

    /**
     * Search term for user filtering
     */
    public string $searchTerm = '';

    /**
     * Selected user IDs for bulk operations
     */
    public array $selectedUserIds = [];

    /**
     * Reason for changes
     */
    public string $reason = '';

    /**
     * Show conflicts panel (hidden by default for performance)
     */
    public bool $showConflicts = false;

    /**
     * Show statistics panel (hidden by default for performance)
     */
    public bool $showStatistics = false;

    /**
     * Schedule statistics
     */
    public array $statistics = [];

    /**
     * Services
     */
    protected ScheduleEditService $editService;

    protected ConflictDetectionService $conflictService;

    protected ScheduleConfigurationService $configService;

    /**
     * Component initialization
     */
    public function boot(
        ScheduleEditService $editService,
        ConflictDetectionService $conflictService,
        ScheduleConfigurationService $configService
    ): void {
        $this->editService = $editService;
        $this->conflictService = $conflictService;
        $this->configService = $configService;
    }

    /**
     * Mount component with schedule
     */
    public function mount(Schedule $schedule): void
    {
        // Authorize user
        $this->authorize('edit', $schedule);

        $this->schedule = $schedule;

        // Load configuration - Fix: handle null properly for unlimited slots
        $maxConfig = $this->configService->get('max_users_per_slot');
        $this->maxUsersPerSlot = ($maxConfig === null || $maxConfig === 0) ? 999 : (int) $maxConfig;
        $this->allowEmptySlots = $this->configService->get('allow_empty_slots', true);

        // Initialize collections
        $this->affectedUsers = collect();
        $this->availableUsers = collect();

        // Load initial data - simplified for performance
        $this->loadAssignments();

        Log::info('EditSchedule component mounted', [
            'schedule_id' => $schedule->id,
            'user' => auth()->user()->name,
            'max_users_per_slot' => $this->maxUsersPerSlot,
        ]);
    }

    /**
     * Lazy load data after component renders
     */
    public function loadData(): void
    {
        // Load users only when needed
        if ($this->availableUsers->isEmpty()) {
            $this->loadAvailableUsers();
        }
    }

    /**
     * Load assignments grouped by slot - Optimized for performance
     */
    public function loadAssignments(): void
    {
        $this->assignments = [];

        // Single optimized query with minimal data
        $allAssignments = ScheduleAssignment::where('schedule_id', $this->schedule->id)
            ->join('users', 'schedule_assignments.user_id', '=', 'users.id')
            ->select([
                'schedule_assignments.id',
                'schedule_assignments.user_id',
                'schedule_assignments.date',
                'schedule_assignments.session',
                'users.name as user_name',
            ])
            ->orderBy('schedule_assignments.date')
            ->orderBy('schedule_assignments.session')
            ->get();

        // Group by date and session
        foreach ($allAssignments as $assignment) {
            $date = $assignment->date->format('Y-m-d');
            $session = $assignment->session;

            if (! isset($this->assignments[$date])) {
                $this->assignments[$date] = [];
            }

            if (! isset($this->assignments[$date][$session])) {
                $this->assignments[$date][$session] = [];
            }

            $this->assignments[$date][$session][] = [
                'id' => $assignment->id,
                'user_id' => $assignment->user_id,
                'user_name' => $assignment->user_name,
            ];
        }

        // Store original state for change tracking
        $this->originalAssignments = $this->assignments;
    }

    /**
     * Load available users for assignment - Optimized with limit
     */
    public function loadAvailableUsers(): void
    {
        $query = User::where('status', 'active')
            ->select('id', 'name')
            ->orderBy('name');

        // Apply search filter if provided
        if (! empty($this->searchTerm)) {
            $query->where('name', 'like', '%'.$this->searchTerm.'%');
        }

        // Limit results for performance
        $this->availableUsers = $query->limit(50)->get();
    }

    /**
     * Add user to a slot - Optimized
     */
    public function addUserToSlot(string $date, int $session, int $userId, ?string $reason = null): void
    {
        try {
            // Use service to add user
            $assignment = $this->editService->addUserToSlot(
                $this->schedule,
                $date,
                $session,
                $userId,
                $reason ?? $this->reason
            );

            // Track change for undo
            $this->changes[] = ['action' => 'add_user', 'id' => $assignment->id];

            // Mark as having unsaved changes
            $this->hasUnsavedChanges = true;

            // Refresh assignments only
            $this->loadAssignments();

            // Close modal and show success
            $this->closeUserSelector();
            $this->dispatch('notify', type: 'success', message: 'User ditambahkan.');

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal: '.$e->getMessage());
        }
    }

    /**
     * Remove user from slot - Optimized
     */
    public function removeUserFromSlot(int $assignmentId, ?string $reason = null): void
    {
        try {
            $assignment = ScheduleAssignment::findOrFail($assignmentId);

            // Use service to remove user
            $this->editService->removeUserFromSlot($assignment, $reason ?? 'Removed');

            // Track change
            $this->changes[] = ['action' => 'remove_user', 'id' => $assignmentId];
            $this->hasUnsavedChanges = true;

            // Refresh assignments only
            $this->loadAssignments();

            $this->dispatch('notify', type: 'success', message: 'User dihapus.');

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal: '.$e->getMessage());
        }
    }

    /**
     * Update user in slot (replace with different user)
     */
    public function updateUserInSlot(int $assignmentId, int $newUserId, ?string $reason = null): void
    {
        try {
            $assignment = ScheduleAssignment::findOrFail($assignmentId);

            // Store old data for undo
            $oldData = [
                'assignment_id' => $assignmentId,
                'old_user_id' => $assignment->user_id,
                'new_user_id' => $newUserId,
                'date' => $assignment->date->format('Y-m-d'),
                'session' => $assignment->session,
                'reason' => $reason ?? $this->reason,
            ];

            // Use service to update user
            $this->editService->updateUserInSlot(
                $assignment,
                $newUserId,
                $reason ?? $this->reason
            );

            // Track change for undo
            $this->trackChange('update_user', $oldData);

            // Mark as having unsaved changes
            $this->hasUnsavedChanges = true;

            // Refresh data
            $this->refreshData();

            // Show success message
            $this->dispatch('notify', type: 'success', message: 'User berhasil diperbarui.');

            Log::info('User updated in slot via component', [
                'schedule_id' => $this->schedule->id,
                'assignment_id' => $assignmentId,
                'new_user_id' => $newUserId,
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal memperbarui user: '.$e->getMessage());

            Log::error('Failed to update user in slot', [
                'error' => $e->getMessage(),
                'assignment_id' => $assignmentId,
                'new_user_id' => $newUserId,
            ]);
        }
    }

    /**
     * Clear all users from a slot
     */
    public function clearSlot(string $date, int $session, ?string $reason = null): void
    {
        try {
            // Get current assignments for undo
            $currentAssignments = $this->getSlotAssignments($date, $session);

            // Use service to clear slot
            $this->editService->clearSlot(
                $this->schedule,
                $date,
                $session,
                $reason ?? $this->reason ?: 'Slot cleared'
            );

            // Track change for undo
            $this->trackChange('clear_slot', [
                'date' => $date,
                'session' => $session,
                'assignments' => $currentAssignments,
                'reason' => $reason ?? $this->reason,
            ]);

            // Mark as having unsaved changes
            $this->hasUnsavedChanges = true;

            // Refresh data
            $this->refreshData();

            // Show success message
            $this->dispatch('notify', type: 'success', message: 'Slot berhasil dikosongkan.');

            ActivityLogService::log("Mengosongkan slot jadwal tanggal {$date} sesi {$session}");
            Log::info('Slot cleared via component', [
                'schedule_id' => $this->schedule->id,
                'date' => $date,
                'session' => $session,
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal mengosongkan slot: '.$e->getMessage());

            Log::error('Failed to clear slot', [
                'error' => $e->getMessage(),
                'date' => $date,
                'session' => $session,
            ]);
        }
    }

    /**
     * Add multiple users to a slot at once
     */
    public function bulkAddUsers(string $date, int $session, array $userIds): void
    {
        try {
            // Use service to bulk add users
            $assignments = $this->editService->bulkAddUsersToSlot(
                $this->schedule,
                $date,
                $session,
                $userIds
            );

            // Track change for undo
            $this->trackChange('bulk_add_users', [
                'date' => $date,
                'session' => $session,
                'user_ids' => $userIds,
                'assignment_ids' => $assignments->pluck('id')->toArray(),
            ]);

            // Mark as having unsaved changes
            $this->hasUnsavedChanges = true;

            // Refresh data
            $this->refreshData();

            // Show success message
            $userCount = count($userIds);
            $this->dispatch('notify', type: 'success', message: $userCount.' user berhasil ditambahkan ke slot.');

            ActivityLogService::log("Menambahkan {$userCount} user ke slot jadwal tanggal {$date} sesi {$session}");
            Log::info('Bulk users added to slot via component', [
                'schedule_id' => $this->schedule->id,
                'date' => $date,
                'session' => $session,
                'count' => $userCount,
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal menambahkan users: '.$e->getMessage());

            Log::error('Failed to bulk add users to slot', [
                'error' => $e->getMessage(),
                'date' => $date,
                'session' => $session,
                'user_ids' => $userIds,
            ]);
        }
    }

    /**
     * Track a change for undo functionality
     */
    public function trackChange(string $action, array $data): void
    {
        $this->changes[] = [
            'action' => $action,
            'data' => $data,
            'timestamp' => now()->toDateTimeString(),
        ];

        Log::debug('Change tracked', [
            'action' => $action,
            'changes_count' => count($this->changes),
        ]);
    }

    /**
     * Detect conflicts in current schedule state - Optimized
     */
    public function detectConflicts(): void
    {
        $this->conflicts = $this->conflictService->detectAllConflicts($this->schedule);
    }

    /**
     * Validate user for slot before adding
     */
    public function validateUserForSlot(int $userId, string $date, int $session): array
    {
        return $this->editService->validateUserForSlot(
            $this->schedule,
            $userId,
            $date,
            $session
        );
    }

    /**
     * Check if user is already in slot (double booking)
     */
    public function checkUserDoubleBooking(int $userId, string $date, int $session): bool
    {
        return $this->editService->checkUserDoubleBooking($userId, $date, $session);
    }

    /**
     * Get conflicts for a specific slot
     */
    public function getSlotConflicts(string $date, int $session): array
    {
        return $this->conflictService->getConflictsForSlot($this->schedule, $date, $session);
    }

    /**
     * Get conflicts for a specific user
     */
    public function getUserConflicts(int $userId): array
    {
        return $this->conflictService->getConflictsForUser($this->schedule, $userId);
    }

    /**
     * Check if schedule has critical conflicts
     */
    public function hasCriticalConflicts(): bool
    {
        return $this->conflictService->hasCriticalConflicts($this->schedule);
    }

    /**
     * Get conflict count by severity
     */
    public function getConflictCount(string $severity = 'all'): int
    {
        return $this->conflictService->getConflictCount($this->schedule, $severity);
    }

    /**
     * Format conflict message for display
     */
    public function formatConflictMessage(array $conflict): string
    {
        return $this->conflictService->formatConflictMessage($conflict);
    }

    /**
     * Calculate schedule statistics - Optimized
     */
    public function calculateStatistics(): void
    {
        $totalSlots = 12; // 4 days × 3 sessions
        $filledSlots = 0;
        $totalAssignments = 0;

        foreach ($this->assignments as $date => $sessions) {
            foreach ($sessions as $session => $assignments) {
                if (count($assignments) > 0) {
                    $filledSlots++;
                }
                $totalAssignments += count($assignments);
            }
        }

        $emptySlots = $totalSlots - $filledSlots;
        $coverageRate = $totalSlots > 0 ? ($filledSlots / $totalSlots) * 100 : 0;
        $avgUsersPerSlot = $totalSlots > 0 ? $totalAssignments / $totalSlots : 0;
        $avgUsersPerFilledSlot = $filledSlots > 0 ? $totalAssignments / $filledSlots : 0;

        $this->statistics = [
            'total_slots' => $totalSlots,
            'filled_slots' => $filledSlots,
            'empty_slots' => $emptySlots,
            'total_assignments' => $totalAssignments,
            'coverage_rate' => round($coverageRate, 2),
            'avg_users_per_slot' => round($avgUsersPerSlot, 2),
            'avg_users_per_filled_slot' => round($avgUsersPerFilledSlot, 2),
        ];
    }

    /**
     * Save all changes to database - Optimized
     *
     * Note: Changes are already persisted individually through the service methods.
     * This method primarily handles final validation and notifications.
     */
    public function saveChanges(): void
    {
        try {
            DB::beginTransaction();

            // Final conflict check
            $this->detectConflicts();

            // Check for critical conflicts
            if ($this->hasCriticalConflicts()) {
                throw new \Exception('Tidak dapat menyimpan: terdapat konflik kritis yang harus diselesaikan terlebih dahulu.');
            }

            // Recalculate schedule coverage
            $this->schedule->calculateCoverage();

            // Mark changes as saved
            $this->hasUnsavedChanges = false;
            $this->changes = [];

            DB::commit();

            // Refresh data
            $this->refreshData();

            // Dispatch global event for other components (Dashboard, MySchedule, etc.)
            $this->dispatch('schedule-updated');

            // Show success message
            $this->dispatch('notify', type: 'success', message: 'Perubahan berhasil disimpan.');

            $weekDate = $this->schedule->week_start_date->format('d M Y');
            ActivityLogService::logScheduleUpdated("Jadwal Mingguan", $weekDate);
            Log::info('Schedule changes saved', [
                'schedule_id' => $this->schedule->id,
                'user' => auth()->user()->name,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('notify', type: 'error', message: 'Gagal menyimpan perubahan: '.$e->getMessage());

            Log::error('Failed to save schedule changes', [
                'schedule_id' => $this->schedule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Discard all unsaved changes and revert to original state
     *
     * Note: Since changes are persisted immediately, this will reload from database.
     * For true undo functionality, we would need to implement a transaction-based approach.
     */
    public function discardChanges(): void
    {
        try {
            // Clear tracked changes
            $this->changes = [];
            $this->hasUnsavedChanges = false;

            // Reload data from database
            $this->refreshData();

            // Show success message
            $this->dispatch('notify', type: 'info', message: 'Perubahan dibatalkan. Data dimuat ulang dari database.');

            Log::info('Schedule changes discarded', [
                'schedule_id' => $this->schedule->id,
                'user' => auth()->user()->name,
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal membatalkan perubahan: '.$e->getMessage());

            Log::error('Failed to discard schedule changes', [
                'schedule_id' => $this->schedule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Undo last change
     *
     * Note: This is a simplified undo that just removes the last tracked change.
     * For full undo functionality, we would need to implement reverse operations.
     */
    public function undoLastChange(): void
    {
        if (empty($this->changes)) {
            $this->dispatch('notify', type: 'warning', message: 'Tidak ada perubahan yang dapat dibatalkan.');

            return;
        }

        // Remove last change from tracking
        $lastChange = array_pop($this->changes);

        // Show info message
        $this->dispatch('notify', type: 'info', message: 'Perubahan terakhir dihapus dari tracking. Reload data untuk melihat state terbaru.');

        Log::info('Last change undone from tracking', [
            'schedule_id' => $this->schedule->id,
            'action' => $lastChange['action'],
        ]);

        // Note: To implement true undo, we would need to:
        // 1. Store reverse operations for each change
        // 2. Execute the reverse operation
        // 3. Update the database
        // This is left for future enhancement if needed
    }

    /**
     * Refresh all data
     */
    public function refreshData(): void
    {
        // Only reload assignments - other data loaded on demand
        $this->loadAssignments();
    }

    /**
     * Get assignments for a specific slot
     */
    public function getSlotAssignments(string $date, int $session): array
    {
        return $this->assignments[$date][$session] ?? [];
    }

    /**
     * Get count of users in a slot
     */
    public function getSlotUserCount(string $date, int $session): int
    {
        return count($this->getSlotAssignments($date, $session));
    }

    /**
     * Check if slot is full (at capacity) - Optimized
     */
    public function isSlotFull(string $date, int $session): bool
    {
        $count = count($this->assignments[$date][$session] ?? []);

        return $count >= $this->maxUsersPerSlot;
    }

    /**
     * Check if slot is empty (no users)
     */
    public function isSlotEmpty(string $date, int $session): bool
    {
        return empty($this->assignments[$date][$session]);
    }

    /**
     * Get slot statistics for display
     */
    public function getSlotStatistics(): array
    {
        $slotStats = [
            'slots_with_0_users' => 0,
            'slots_with_1_user' => 0,
            'slots_with_2_users' => 0,
            'slots_with_3_users' => 0,
            'slots_with_4_plus_users' => 0,
        ];

        // Generate all possible slots
        $startDate = \Carbon\Carbon::parse($this->schedule->week_start_date);
        for ($day = 0; $day < 4; $day++) {
            $date = $startDate->copy()->addDays($day)->format('Y-m-d');
            for ($session = 1; $session <= 3; $session++) {
                $userCount = $this->getSlotUserCount($date, $session);

                if ($userCount === 0) {
                    $slotStats['slots_with_0_users']++;
                } elseif ($userCount === 1) {
                    $slotStats['slots_with_1_user']++;
                } elseif ($userCount === 2) {
                    $slotStats['slots_with_2_users']++;
                } elseif ($userCount === 3) {
                    $slotStats['slots_with_3_users']++;
                } else {
                    $slotStats['slots_with_4_plus_users']++;
                }
            }
        }

        return $slotStats;
    }

    /**
     * Get session time information
     */
    public function getSessionTime(int $session): array
    {
        $sessionTimes = [
            1 => ['start' => '07:30', 'end' => '10:00', 'label' => 'Sesi 1'],
            2 => ['start' => '10:20', 'end' => '12:50', 'label' => 'Sesi 2'],
            3 => ['start' => '13:30', 'end' => '16:00', 'label' => 'Sesi 3'],
        ];

        return $sessionTimes[$session] ?? ['start' => '00:00', 'end' => '00:00', 'label' => 'Unknown'];
    }

    /**
     * Get day name in Indonesian
     */
    public function getDayName(string $date): string
    {
        return \Carbon\Carbon::parse($date)->locale('id')->dayName;
    }

    /**
     * Get formatted date
     */
    public function getFormattedDate(string $date): string
    {
        return \Carbon\Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM YYYY');
    }

    /**
     * Get all dates in schedule week
     */
    public function getScheduleDates(): array
    {
        $dates = [];
        $startDate = \Carbon\Carbon::parse($this->schedule->week_start_date);

        for ($day = 0; $day < 4; $day++) {
            $date = $startDate->copy()->addDays($day);
            $dates[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->locale('id')->dayName,
                'formatted' => $date->locale('id')->isoFormat('D MMM'),
            ];
        }

        return $dates;
    }

    /**
     * Get slot status (for color coding) - Simplified without edited status
     */
    public function getSlotStatus(string $date, int $session): string
    {
        $userCount = $this->getSlotUserCount($date, $session);
        $conflicts = $this->getSlotConflicts($date, $session);

        // Check for critical conflicts
        if (! empty($conflicts['critical'])) {
            return 'conflict'; // Red
        }

        // Check for warnings
        if (! empty($conflicts['warning'])) {
            return 'warning'; // Yellow
        }

        // Check if empty
        if ($userCount === 0) {
            return 'empty'; // Gray
        }

        // Check if overstaffed
        $overstaffedThreshold = $this->configService->get('overstaffed_threshold', 3);
        if ($userCount > $overstaffedThreshold) {
            return 'overstaffed'; // Yellow/Orange
        }

        // Normal state
        return 'normal'; // Green
    }

    /**
     * Toggle conflicts panel visibility
     */
    public function toggleConflicts(): void
    {
        $this->showConflicts = ! $this->showConflicts;
    }

    /**
     * Toggle statistics panel visibility
     */
    public function toggleStatistics(): void
    {
        $this->showStatistics = ! $this->showStatistics;
    }

    /**
     * Open slot management modal
     */
    public function openSlotModal(string $date, int $session): void
    {
        $this->selectedSlot = [
            'date' => $date,
            'session' => $session,
        ];
        $this->showMultiUserModal = true;
    }

    /**
     * Close slot management modal
     */
    public function closeSlotModal(): void
    {
        $this->selectedSlot = null;
        $this->showMultiUserModal = false;
        $this->selectedUserIds = [];
        $this->reason = '';
    }

    /**
     * Open user selector
     */
    public function openUserSelector(string $date, int $session): void
    {
        $this->selectedSlot = [
            'date' => $date,
            'session' => $session,
        ];
        $this->showUserSelector = true;
        $this->loadAvailableUsers();
    }

    /**
     * Close user selector
     */
    public function closeUserSelector(): void
    {
        $this->selectedSlot = null;
        $this->showUserSelector = false;
        $this->selectedUserIds = [];
        $this->searchTerm = '';
    }

    /**
     * Toggle user selection for bulk operations
     */
    public function toggleUserSelection(int $userId): void
    {
        if (in_array($userId, $this->selectedUserIds)) {
            $this->selectedUserIds = array_diff($this->selectedUserIds, [$userId]);
        } else {
            $this->selectedUserIds[] = $userId;
        }
    }

    /**
     * Select all available users
     */
    public function selectAllUsers(): void
    {
        $this->selectedUserIds = $this->availableUsers->pluck('id')->toArray();
    }

    /**
     * Clear user selection
     */
    public function clearUserSelection(): void
    {
        $this->selectedUserIds = [];
    }

    /**
     * Search users (triggered by wire:model)
     */
    public function updatedSearchTerm(): void
    {
        $this->loadAvailableUsers();
    }

    /**
     * Get change count
     */
    public function getChangeCount(): int
    {
        return count($this->changes);
    }

    /**
     * Check if can save (no critical conflicts)
     */
    public function canSave(): bool
    {
        return ! $this->hasCriticalConflicts();
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.schedule.edit-schedule');
    }
}
