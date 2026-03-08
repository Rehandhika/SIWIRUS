<?php

namespace App\Services;

use App\Models\AssignmentEditHistory;
use App\Models\Schedule;
use App\Models\ScheduleAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * ScheduleEditService
 *
 * Service for managing multi-user slot editing operations.
 * Handles adding, removing, updating, and clearing users in schedule slots
 * with full audit trail and validation support.
 *
 * Features:
 * - Multi-user slot management
 * - Duplicate user prevention
 * - Slot capacity validation
 * - Audit trail recording
 * - Cache invalidation
 * - User notifications
 */
class ScheduleEditService
{
    /**
     * Schedule configuration service
     */
    protected ScheduleConfigurationService $configService;

    /**
     * Constructor
     */
    public function __construct(ScheduleConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Add a user to a slot
     *
     * @param  string  $date  Date in Y-m-d format
     * @param  int  $session  Session number (1, 2, or 3)
     * @param  int  $userId  User ID to add
     * @param  string|null  $reason  Reason for adding
     *
     * @throws ValidationException
     */
    public function addUserToSlot(
        Schedule $schedule,
        string $date,
        int $session,
        int $userId,
        ?string $reason = null
    ): ScheduleAssignment {
        DB::beginTransaction();

        try {
            // Validate user
            $user = User::find($userId);

            if (! $user) {
                throw ValidationException::withMessages([
                    'user_id' => 'User tidak ditemukan.',
                ]);
            }

            if (! $user->isActive()) {
                throw ValidationException::withMessages([
                    'user_id' => 'User tidak aktif dan tidak dapat dijadwalkan.',
                ]);
            }

            // Check for duplicate user in same slot
            if ($this->checkUserDoubleBooking($userId, $date, $session)) {
                throw ValidationException::withMessages([
                    'user_id' => 'User sudah ada dalam slot ini.',
                ]);
            }

            // Check slot capacity
            $maxUsersPerSlot = $this->configService->get('max_users_per_slot');
            if ($maxUsersPerSlot !== null && $maxUsersPerSlot > 0 && $this->isSlotFull($schedule, $date, $session)) {
                throw ValidationException::withMessages([
                    'slot' => "Slot sudah penuh (maksimal {$maxUsersPerSlot} user). Tidak dapat menambahkan user lagi.",
                ]);
            }

            // Determine day name from date
            $dayName = strtolower(\Carbon\Carbon::parse($date)->englishDayOfWeek);

            // Get session times from configuration or use defaults
            $sessionTimes = [
                1 => ['07:30:00', '10:00:00'],
                2 => ['10:20:00', '12:50:00'],
                3 => ['13:30:00', '16:00:00'],
            ];

            [$timeStart, $timeEnd] = $sessionTimes[$session] ?? ['00:00:00', '00:00:00'];

            // Create assignment
            $assignment = ScheduleAssignment::create([
                'schedule_id' => $schedule->id,
                'user_id' => $userId,
                'day' => $dayName,
                'session' => $session,
                'date' => $date,
                'time_start' => $timeStart,
                'time_end' => $timeEnd,
                'status' => 'scheduled',
                'edited_by' => auth()->id(),
                'edited_at' => now(),
                'edit_reason' => $reason,
            ]);

            // Record change in audit trail
            $this->recordChange(
                $schedule,
                'created',
                [
                    'assignment_id' => $assignment->id,
                    'user_id' => $userId,
                    'date' => $date,
                    'session' => $session,
                    'day' => $dayName,
                ],
                $reason
            );

            // Update schedule coverage
            $schedule->calculateCoverage();

            // Invalidate cache
            $this->invalidateScheduleCache($schedule);

            // Send notification
            $this->notifyUser(
                $user,
                'assignment_added',
                [
                    'schedule_id' => $schedule->id,
                    'date' => $date,
                    'session' => $session,
                    'assignment_id' => $assignment->id,
                ],
                $reason
            );

            DB::commit();

            Log::info('User added to slot', [
                'schedule_id' => $schedule->id,
                'user_id' => $userId,
                'date' => $date,
                'session' => $session,
                'editor' => auth()->user()?->name,
            ]);

            return $assignment->fresh(['user']);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to add user to slot', [
                'schedule_id' => $schedule->id,
                'user_id' => $userId,
                'date' => $date,
                'session' => $session,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Remove a user from a slot
     *
     * @param  string  $reason  Reason for removal
     *
     * @throws ValidationException
     */
    public function removeUserFromSlot(
        ScheduleAssignment $assignment,
        string $reason
    ): bool {
        DB::beginTransaction();

        try {
            $schedule = $assignment->schedule;
            $user = $assignment->user;

            // Store assignment data before deletion
            $assignmentData = [
                'assignment_id' => $assignment->id,
                'user_id' => $assignment->user_id,
                'date' => $assignment->date->format('Y-m-d'),
                'session' => $assignment->session,
                'day' => $assignment->day,
            ];

            // Record change in audit trail before deletion
            $this->recordChange(
                $schedule,
                'deleted',
                $assignmentData,
                $reason
            );

            // Delete assignment
            $assignment->delete();

            // Update schedule coverage
            $schedule->calculateCoverage();

            // Invalidate cache
            $this->invalidateScheduleCache($schedule);

            // Send notification
            $this->notifyUser(
                $user,
                'assignment_removed',
                [
                    'schedule_id' => $schedule->id,
                    'date' => $assignmentData['date'],
                    'session' => $assignmentData['session'],
                ],
                $reason
            );

            DB::commit();

            Log::info('User removed from slot', [
                'schedule_id' => $schedule->id,
                'user_id' => $assignmentData['user_id'],
                'date' => $assignmentData['date'],
                'session' => $assignmentData['session'],
                'reason' => $reason,
                'editor' => auth()->user()?->name,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to remove user from slot', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update user in a slot (replace with different user)
     *
     * @param  int  $newUserId  New user ID
     * @param  string|null  $reason  Reason for update
     *
     * @throws ValidationException
     */
    public function updateUserInSlot(
        ScheduleAssignment $assignment,
        int $newUserId,
        ?string $reason = null
    ): ScheduleAssignment {
        DB::beginTransaction();

        try {
            $schedule = $assignment->schedule;
            $oldUser = $assignment->user;

            // Validate new user
            $newUser = User::find($newUserId);

            if (! $newUser) {
                throw ValidationException::withMessages([
                    'user_id' => 'User baru tidak ditemukan.',
                ]);
            }

            if (! $newUser->isActive()) {
                throw ValidationException::withMessages([
                    'user_id' => 'User baru tidak aktif dan tidak dapat dijadwalkan.',
                ]);
            }

            // Check if new user is already in the same slot (excluding current assignment)
            $date = $assignment->date->format('Y-m-d');
            $session = $assignment->session;

            if ($this->checkUserDoubleBooking($newUserId, $date, $session, $assignment->id)) {
                throw ValidationException::withMessages([
                    'user_id' => 'User baru sudah ada dalam slot ini.',
                ]);
            }

            // Store old values for audit trail
            $oldValues = [
                'user_id' => $assignment->user_id,
                'user_name' => $oldUser->name,
            ];

            $newValues = [
                'user_id' => $newUserId,
                'user_name' => $newUser->name,
            ];

            // Store previous values in assignment
            $assignment->previous_values = $oldValues;

            // Update assignment
            $assignment->user_id = $newUserId;
            $assignment->edited_by = auth()->id();
            $assignment->edited_at = now();
            $assignment->edit_reason = $reason;
            $assignment->save();

            // Record change in audit trail
            $this->recordChange(
                $schedule,
                'updated',
                [
                    'assignment_id' => $assignment->id,
                    'date' => $date,
                    'session' => $session,
                    'day' => $assignment->day,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                ],
                $reason
            );

            // Invalidate cache
            $this->invalidateScheduleCache($schedule);

            // Send notifications to both users
            $this->notifyUser(
                $oldUser,
                'assignment_removed',
                [
                    'schedule_id' => $schedule->id,
                    'date' => $date,
                    'session' => $session,
                ],
                $reason
            );

            $this->notifyUser(
                $newUser,
                'assignment_added',
                [
                    'schedule_id' => $schedule->id,
                    'date' => $date,
                    'session' => $session,
                    'assignment_id' => $assignment->id,
                ],
                $reason
            );

            DB::commit();

            Log::info('User updated in slot', [
                'schedule_id' => $schedule->id,
                'old_user_id' => $oldValues['user_id'],
                'new_user_id' => $newUserId,
                'date' => $date,
                'session' => $session,
                'reason' => $reason,
                'editor' => auth()->user()?->name,
            ]);

            return $assignment->fresh(['user']);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update user in slot', [
                'assignment_id' => $assignment->id,
                'new_user_id' => $newUserId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Clear all users from a slot
     *
     * @param  string  $date  Date in Y-m-d format
     * @param  int  $session  Session number (1, 2, or 3)
     * @param  string  $reason  Reason for clearing
     *
     * @throws ValidationException
     */
    public function clearSlot(
        Schedule $schedule,
        string $date,
        int $session,
        string $reason
    ): bool {
        DB::beginTransaction();

        try {
            // Get all assignments for the slot
            $assignments = $this->getSlotAssignments($schedule, $date, $session);

            if ($assignments->isEmpty()) {
                Log::info('No assignments to clear in slot', [
                    'schedule_id' => $schedule->id,
                    'date' => $date,
                    'session' => $session,
                ]);

                return true;
            }

            // Store assignment data for audit trail
            $assignmentData = $assignments->map(function ($assignment) {
                return [
                    'assignment_id' => $assignment->id,
                    'user_id' => $assignment->user_id,
                    'user_name' => $assignment->user->name,
                ];
            })->toArray();

            // Record bulk change in audit trail
            $this->recordChange(
                $schedule,
                'bulk_deleted',
                [
                    'date' => $date,
                    'session' => $session,
                    'assignments' => $assignmentData,
                    'count' => $assignments->count(),
                ],
                $reason
            );

            // Send notifications to all affected users
            foreach ($assignments as $assignment) {
                $this->notifyUser(
                    $assignment->user,
                    'assignment_removed',
                    [
                        'schedule_id' => $schedule->id,
                        'date' => $date,
                        'session' => $session,
                    ],
                    $reason
                );
            }

            // Delete all assignments in the slot
            ScheduleAssignment::where('schedule_id', $schedule->id)
                ->where('date', $date)
                ->where('session', $session)
                ->delete();

            // Update schedule coverage
            $schedule->calculateCoverage();

            // Invalidate cache
            $this->invalidateScheduleCache($schedule);

            DB::commit();

            Log::info('Slot cleared', [
                'schedule_id' => $schedule->id,
                'date' => $date,
                'session' => $session,
                'count' => count($assignmentData),
                'reason' => $reason,
                'editor' => auth()->user()?->name,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to clear slot', [
                'schedule_id' => $schedule->id,
                'date' => $date,
                'session' => $session,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Add multiple users to a slot at once
     *
     * @param  string  $date  Date in Y-m-d format
     * @param  int  $session  Session number (1, 2, or 3)
     * @param  array  $userIds  Array of user IDs to add
     * @return Collection Collection of created assignments
     *
     * @throws ValidationException
     */
    public function bulkAddUsersToSlot(
        Schedule $schedule,
        string $date,
        int $session,
        array $userIds
    ): Collection {
        DB::beginTransaction();

        try {
            if (empty($userIds)) {
                throw ValidationException::withMessages([
                    'user_ids' => 'Tidak ada user yang dipilih.',
                ]);
            }

            // Validate all users first
            $users = User::whereIn('id', $userIds)->get();

            if ($users->count() !== count($userIds)) {
                throw ValidationException::withMessages([
                    'user_ids' => 'Beberapa user tidak ditemukan.',
                ]);
            }

            // Check all users are active
            $inactiveUsers = $users->filter(fn ($user) => ! $user->isActive());

            if ($inactiveUsers->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'user_ids' => 'Beberapa user tidak aktif: '.$inactiveUsers->pluck('name')->join(', '),
                ]);
            }

            // Check for duplicates in the slot
            $existingUserIds = ScheduleAssignment::where('schedule_id', $schedule->id)
                ->where('date', $date)
                ->where('session', $session)
                ->pluck('user_id')
                ->toArray();

            $duplicateUserIds = array_intersect($userIds, $existingUserIds);

            if (! empty($duplicateUserIds)) {
                $duplicateUsers = $users->whereIn('id', $duplicateUserIds);
                throw ValidationException::withMessages([
                    'user_ids' => 'Beberapa user sudah ada dalam slot ini: '.$duplicateUsers->pluck('name')->join(', '),
                ]);
            }

            // Check slot capacity
            $currentCount = $this->getSlotUserCount($schedule, $date, $session);
            $maxUsersPerSlot = $this->configService->get('max_users_per_slot');

            // Only check capacity if limit is set and greater than 0
            if ($maxUsersPerSlot !== null && $maxUsersPerSlot > 0 && ($currentCount + count($userIds)) > $maxUsersPerSlot) {
                throw ValidationException::withMessages([
                    'slot' => "Slot hanya dapat menampung maksimal {$maxUsersPerSlot} user. Saat ini ada {$currentCount} user.",
                ]);
            }

            // Determine day name from date
            $dayName = strtolower(\Carbon\Carbon::parse($date)->englishDayOfWeek);

            // Get session times
            $sessionTimes = [
                1 => ['07:30:00', '10:00:00'],
                2 => ['10:20:00', '12:50:00'],
                3 => ['13:30:00', '16:00:00'],
            ];

            [$timeStart, $timeEnd] = $sessionTimes[$session] ?? ['00:00:00', '00:00:00'];

            // Create assignments for all users
            $createdAssignments = collect();
            $assignmentData = [];

            foreach ($users as $user) {
                $assignment = ScheduleAssignment::create([
                    'schedule_id' => $schedule->id,
                    'user_id' => $user->id,
                    'day' => $dayName,
                    'session' => $session,
                    'date' => $date,
                    'time_start' => $timeStart,
                    'time_end' => $timeEnd,
                    'status' => 'scheduled',
                    'edited_by' => auth()->id(),
                    'edited_at' => now(),
                    'edit_reason' => 'Bulk add users',
                ]);

                $createdAssignments->push($assignment);

                $assignmentData[] = [
                    'assignment_id' => $assignment->id,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ];

                // Send notification to each user
                $this->notifyUser(
                    $user,
                    'assignment_added',
                    [
                        'schedule_id' => $schedule->id,
                        'date' => $date,
                        'session' => $session,
                        'assignment_id' => $assignment->id,
                    ],
                    'Bulk add users'
                );
            }

            // Record bulk change in audit trail
            $this->recordChange(
                $schedule,
                'bulk_created',
                [
                    'date' => $date,
                    'session' => $session,
                    'day' => $dayName,
                    'assignments' => $assignmentData,
                    'count' => count($assignmentData),
                ],
                'Bulk add users'
            );

            // Update schedule coverage
            $schedule->calculateCoverage();

            // Invalidate cache
            $this->invalidateScheduleCache($schedule);

            DB::commit();

            Log::info('Bulk users added to slot', [
                'schedule_id' => $schedule->id,
                'date' => $date,
                'session' => $session,
                'count' => count($assignmentData),
                'editor' => auth()->user()?->name,
            ]);

            return $createdAssignments->fresh(['user']);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to bulk add users to slot', [
                'schedule_id' => $schedule->id,
                'date' => $date,
                'session' => $session,
                'user_ids' => $userIds,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get all assignments for a specific slot
     *
     * @param  string  $date  Date in Y-m-d format
     * @param  int  $session  Session number (1, 2, or 3)
     */
    public function getSlotAssignments(
        Schedule $schedule,
        string $date,
        int $session
    ): Collection {
        return ScheduleAssignment::where('schedule_id', $schedule->id)
            ->where('date', $date)
            ->where('session', $session)
            ->with('user:id,name,photo,status')
            ->get();
    }

    /**
     * Get count of users in a slot
     *
     * @param  string  $date  Date in Y-m-d format
     * @param  int  $session  Session number (1, 2, or 3)
     */
    public function getSlotUserCount(
        Schedule $schedule,
        string $date,
        int $session
    ): int {
        return ScheduleAssignment::where('schedule_id', $schedule->id)
            ->where('date', $date)
            ->where('session', $session)
            ->count();
    }

    /**
     * Validate if a user can be added to a slot
     *
     * @param  int  $userId  User ID to validate
     * @param  string  $date  Date in Y-m-d format
     * @param  int  $session  Session number (1, 2, or 3)
     * @param  int|null  $excludeAssignmentId  Assignment ID to exclude from duplicate check
     * @return array Array of validation errors (empty if valid)
     */
    public function validateUserForSlot(
        Schedule $schedule,
        int $userId,
        string $date,
        int $session,
        ?int $excludeAssignmentId = null
    ): array {
        $errors = [];

        // Check if user exists
        $user = User::find($userId);

        if (! $user) {
            $errors['user_id'] = 'User tidak ditemukan.';

            return $errors;
        }

        // Check if user is active
        if (! $user->isActive()) {
            $errors['user_status'] = 'User tidak aktif dan tidak dapat dijadwalkan.';
        }

        // Check for duplicate user in same slot
        if ($this->checkUserDoubleBooking($userId, $date, $session, $excludeAssignmentId)) {
            $errors['duplicate'] = 'User sudah ada dalam slot ini.';
        }

        // Check slot capacity
        $maxUsersPerSlot = $this->configService->get('max_users_per_slot');
        if ($maxUsersPerSlot !== null && $maxUsersPerSlot > 0 && $this->isSlotFull($schedule, $date, $session)) {
            $errors['capacity'] = "Slot sudah penuh (maksimal {$maxUsersPerSlot} user).";
        }

        // Check user availability (warning, not error)
        $dayName = strtolower(\Carbon\Carbon::parse($date)->englishDayOfWeek);

        $isAvailable = $this->checkUserAvailability($userId, $date, $dayName, $session);

        if (! $isAvailable) {
            $errors['availability'] = 'User tidak menandai diri tersedia untuk slot ini (warning).';
        }

        return $errors;
    }

    /**
     * Check if user is available for a specific slot with caching
     *
     * @param  int  $userId  User ID to check
     * @param  string  $date  Date in Y-m-d format
     * @param  string  $dayName  Day name (monday, tuesday, etc.)
     * @param  int  $session  Session number (1, 2, or 3)
     */
    protected function checkUserAvailability(int $userId, string $date, string $dayName, int $session): bool
    {
        // Get week start date from the given date
        $weekStart = \Carbon\Carbon::parse($date)->startOfWeek()->format('Y-m-d');
        $cacheKey = "user_availability_{$userId}_{$weekStart}";
        $cacheTTL = 3600; // 1 hour

        // Try to get from cache
        $availabilityMap = Cache::get($cacheKey);

        if ($availabilityMap === null) {
            // Cache miss - load from database
            $availabilityMap = $this->loadUserAvailabilityMap($userId, $weekStart);

            // Cache the availability map
            Cache::put($cacheKey, $availabilityMap, $cacheTTL);

            Log::debug('User availability loaded from database and cached', [
                'user_id' => $userId,
                'week_start' => $weekStart,
                'cache_key' => $cacheKey,
            ]);
        }

        // Check availability for the specific day and session
        $key = $dayName.'_'.$session;

        return $availabilityMap[$key] ?? false;
    }

    /**
     * Load user availability map from database
     *
     * @param  int  $userId  User ID
     * @param  string  $weekStart  Week start date in Y-m-d format
     * @return array Availability map [day_session => bool]
     */
    protected function loadUserAvailabilityMap(int $userId, string $weekStart): array
    {
        $availabilityMap = [];

        // Get user's availability for the week
        $availability = \App\Models\Availability::where('user_id', $userId)
            ->where('week_start_date', $weekStart)
            ->where('status', 'submitted')
            ->with('details')
            ->first();

        if ($availability && $availability->details) {
            foreach ($availability->details as $detail) {
                $key = $detail->day.'_'.$detail->session;
                $availabilityMap[$key] = $detail->is_available;
            }
        }

        return $availabilityMap;
    }

    /**
     * Check if user is already in the slot (double booking)
     *
     * @param  int  $userId  User ID to check
     * @param  string  $date  Date in Y-m-d format
     * @param  int  $session  Session number (1, 2, or 3)
     * @param  int|null  $excludeAssignmentId  Assignment ID to exclude from check
     */
    public function checkUserDoubleBooking(
        int $userId,
        string $date,
        int $session,
        ?int $excludeAssignmentId = null
    ): bool {
        $query = ScheduleAssignment::where('user_id', $userId)
            ->where('date', $date)
            ->where('session', $session);

        if ($excludeAssignmentId !== null) {
            $query->where('id', '!=', $excludeAssignmentId);
        }

        return $query->exists();
    }

    /**
     * Check if slot is full (at capacity)
     *
     * @param  string  $date  Date in Y-m-d format
     * @param  int  $session  Session number (1, 2, or 3)
     */
    public function isSlotFull(
        Schedule $schedule,
        string $date,
        int $session
    ): bool {
        $maxUsersPerSlot = $this->configService->get('max_users_per_slot');

        // If no limit is set or is 0 (unlimited), slot is never full
        if ($maxUsersPerSlot === null || $maxUsersPerSlot === 0) {
            return false;
        }

        $currentCount = $this->getSlotUserCount($schedule, $date, $session);

        return $currentCount >= $maxUsersPerSlot;
    }

    /**
     * Record a change in the audit trail
     *
     * @param  string  $action  Action type (created, updated, deleted, etc.)
     * @param  array  $data  Change data
     * @param  string|null  $reason  Reason for change
     */
    public function recordChange(
        Schedule $schedule,
        string $action,
        array $data,
        ?string $reason = null
    ): AssignmentEditHistory {
        // Determine assignment_id based on action type
        $assignmentId = null;

        if (isset($data['assignment_id'])) {
            $assignmentId = $data['assignment_id'];
        }

        // For bulk operations, assignment_id might be null
        // We'll still record the change at the schedule level

        // Prepare old and new values
        $oldValues = null;
        $newValues = null;

        switch ($action) {
            case 'created':
                // For creation, new values are the assignment data
                $newValues = [
                    'user_id' => $data['user_id'] ?? null,
                    'date' => $data['date'] ?? null,
                    'session' => $data['session'] ?? null,
                    'day' => $data['day'] ?? null,
                ];
                break;

            case 'updated':
                // For updates, we have both old and new values
                $oldValues = $data['old_values'] ?? null;
                $newValues = $data['new_values'] ?? null;
                break;

            case 'deleted':
                // For deletion, old values are the assignment data
                $oldValues = [
                    'user_id' => $data['user_id'] ?? null,
                    'date' => $data['date'] ?? null,
                    'session' => $data['session'] ?? null,
                    'day' => $data['day'] ?? null,
                ];
                break;

            case 'bulk_created':
            case 'bulk_deleted':
                // For bulk operations, store the full data
                $newValues = [
                    'date' => $data['date'] ?? null,
                    'session' => $data['session'] ?? null,
                    'assignments' => $data['assignments'] ?? [],
                    'count' => $data['count'] ?? 0,
                ];
                break;
        }

        // Create audit trail record
        $history = AssignmentEditHistory::create([
            'assignment_id' => $assignmentId,
            'schedule_id' => $schedule->id,
            'edited_by' => auth()->id() ?? 1, // Default to system user if not authenticated
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
        ]);

        Log::debug('Audit trail recorded', [
            'history_id' => $history->id,
            'schedule_id' => $schedule->id,
            'action' => $action,
            'editor' => auth()->user()?->name ?? 'System',
        ]);

        return $history;
    }

    /**
     * Send notification to a user about assignment change
     *
     * @param  User  $user  User to notify
     * @param  string  $notificationType  Type of notification (assignment_added, assignment_removed, assignment_updated, schedule_published)
     * @param  array  $data  Notification data
     * @param  string|null  $reason  Reason for change
     */
    public function notifyUser(
        User $user,
        string $notificationType,
        array $data,
        ?string $reason = null
    ): void {
        try {
            // Skip notification if user has no email
            if (empty($user->email)) {
                Log::warning('Cannot send notification - user has no email', [
                    'user_id' => $user->id,
                    'type' => $notificationType,
                ]);

                return;
            }

            // Prepare notification data based on type
            $notificationData = $this->prepareNotificationData($notificationType, $data, $reason);

            // Get slotmates if assignment_id is provided
            if (isset($data['assignment_id'])) {
                $assignment = ScheduleAssignment::find($data['assignment_id']);
                if ($assignment) {
                    $slotmates = $assignment->getSlotmates();
                    $notificationData['slotmates'] = $slotmates->map(function ($mate) {
                        return [
                            'name' => $mate->user->name,
                            'photo' => $mate->user->photo,
                        ];
                    })->toArray();
                    $notificationData['slotmate_count'] = $slotmates->count();
                }
            }

            // Send email notification using ScheduleNotification mailable
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\ScheduleNotification(
                    $notificationData['title'],
                    $notificationData['message'],
                    $notificationData['data'],
                    $notificationType,
                    $notificationData['priority']
                )
            );

            Log::info('Notification sent to user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'type' => $notificationType,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'user_id' => $user->id,
                'type' => $notificationType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Prepare notification data based on notification type
     */
    protected function prepareNotificationData(
        string $notificationType,
        array $data,
        ?string $reason = null
    ): array {
        $sessionTimes = [
            1 => ['start' => '07:30', 'end' => '10:00', 'label' => 'Sesi 1'],
            2 => ['start' => '10:20', 'end' => '12:50', 'label' => 'Sesi 2'],
            3 => ['start' => '13:30', 'end' => '16:00', 'label' => 'Sesi 3'],
        ];

        $session = $data['session'] ?? null;
        $date = $data['date'] ?? null;
        $sessionInfo = $sessionTimes[$session] ?? null;

        // Format date to Indonesian format
        $formattedDate = $date ? \Carbon\Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM YYYY') : '';

        // Get day name in Indonesian
        $dayName = $date ? \Carbon\Carbon::parse($date)->locale('id')->dayName : '';

        switch ($notificationType) {
            case 'assignment_added':
                return [
                    'title' => '✅ Jadwal Baru Ditambahkan',
                    'message' => "Anda telah dijadwalkan untuk bertugas pada {$formattedDate}, {$sessionInfo['label']} ({$sessionInfo['start']} - {$sessionInfo['end']}).",
                    'data' => [
                        'Tanggal' => $formattedDate,
                        'Hari' => $dayName,
                        'Sesi' => $sessionInfo['label'],
                        'Waktu' => "{$sessionInfo['start']} - {$sessionInfo['end']}",
                        'Alasan' => $reason ?? 'Penjadwalan otomatis',
                        'schedule_info' => 'Pastikan Anda hadir tepat waktu. Jika berhalangan, segera hubungi admin.',
                        'action_url' => url("/schedule/{$data['schedule_id']}"),
                        'action_text' => 'Lihat Jadwal Lengkap',
                    ],
                    'priority' => 'normal',
                ];

            case 'assignment_removed':
                return [
                    'title' => '❌ Jadwal Dihapus',
                    'message' => "Jadwal Anda pada {$formattedDate}, {$sessionInfo['label']} ({$sessionInfo['start']} - {$sessionInfo['end']}) telah dihapus.",
                    'data' => [
                        'Tanggal' => $formattedDate,
                        'Hari' => $dayName,
                        'Sesi' => $sessionInfo['label'],
                        'Waktu' => "{$sessionInfo['start']} - {$sessionInfo['end']}",
                        'Alasan' => $reason ?? 'Perubahan jadwal',
                        'schedule_info' => 'Jadwal Anda telah diperbarui. Silakan cek jadwal terbaru untuk melihat perubahan.',
                        'action_url' => url("/schedule/{$data['schedule_id']}"),
                        'action_text' => 'Lihat Jadwal Lengkap',
                    ],
                    'priority' => 'high',
                ];

            case 'assignment_updated':
                $oldDate = $data['old_date'] ?? $date;
                $oldSession = $data['old_session'] ?? $session;
                $oldSessionInfo = $sessionTimes[$oldSession] ?? $sessionInfo;
                $oldFormattedDate = $oldDate ? \Carbon\Carbon::parse($oldDate)->locale('id')->isoFormat('dddd, D MMMM YYYY') : '';

                return [
                    'title' => '🔄 Jadwal Diperbarui',
                    'message' => 'Jadwal Anda telah diperbarui.',
                    'data' => [
                        'Jadwal Lama' => "{$oldFormattedDate}, {$oldSessionInfo['label']} ({$oldSessionInfo['start']} - {$oldSessionInfo['end']})",
                        'Jadwal Baru' => "{$formattedDate}, {$sessionInfo['label']} ({$sessionInfo['start']} - {$sessionInfo['end']})",
                        'Alasan' => $reason ?? 'Perubahan jadwal',
                        'schedule_info' => 'Pastikan Anda hadir sesuai jadwal baru. Jika berhalangan, segera hubungi admin.',
                        'action_url' => url("/schedule/{$data['schedule_id']}"),
                        'action_text' => 'Lihat Jadwal Lengkap',
                    ],
                    'priority' => 'high',
                ];

            case 'schedule_published':
                $assignmentCount = $data['assignment_count'] ?? 0;
                $assignments = $data['assignments'] ?? [];

                $assignmentList = '';
                foreach ($assignments as $assignment) {
                    $aDate = \Carbon\Carbon::parse($assignment['date'])->locale('id')->isoFormat('dddd, D MMM');
                    $aSessionInfo = $sessionTimes[$assignment['session']] ?? ['label' => 'Sesi ?', 'start' => '00:00', 'end' => '00:00'];
                    $assignmentList .= "• {$aDate}, {$aSessionInfo['label']} ({$aSessionInfo['start']} - {$aSessionInfo['end']})\n";
                }

                return [
                    'title' => '📅 Jadwal Baru Dipublikasikan',
                    'message' => "Jadwal baru telah dipublikasikan. Anda memiliki {$assignmentCount} jadwal tugas.",
                    'data' => [
                        'Total Jadwal' => $assignmentCount,
                        'Daftar Jadwal' => $assignmentList ?: 'Tidak ada jadwal',
                        'schedule_info' => 'Silakan cek jadwal lengkap untuk melihat detail dan rekan kerja Anda di setiap sesi.',
                        'action_url' => url("/schedule/{$data['schedule_id']}"),
                        'action_text' => 'Lihat Jadwal Lengkap',
                    ],
                    'priority' => 'normal',
                ];

            default:
                return [
                    'title' => '🔔 Notifikasi Jadwal',
                    'message' => 'Ada perubahan pada jadwal Anda.',
                    'data' => array_merge($data, ['Alasan' => $reason ?? '-']),
                    'priority' => 'normal',
                ];
        }
    }

    /**
     * Send notifications to all users in a slot
     *
     * @param  string  $date  Date in Y-m-d format
     * @param  int  $session  Session number (1, 2, or 3)
     * @param  string  $notificationType  Type of notification
     * @param  string|null  $reason  Reason for change
     */
    public function notifySlotUsers(
        Schedule $schedule,
        string $date,
        int $session,
        string $notificationType,
        ?string $reason = null
    ): void {
        $assignments = $this->getSlotAssignments($schedule, $date, $session);

        if ($assignments->isEmpty()) {
            Log::info('No users to notify in slot', [
                'schedule_id' => $schedule->id,
                'date' => $date,
                'session' => $session,
            ]);

            return;
        }

        Log::info('Notifying slot users', [
            'schedule_id' => $schedule->id,
            'date' => $date,
            'session' => $session,
            'user_count' => $assignments->count(),
            'type' => $notificationType,
        ]);

        foreach ($assignments as $assignment) {
            $this->notifyUser(
                $assignment->user,
                $notificationType,
                [
                    'schedule_id' => $schedule->id,
                    'date' => $date,
                    'session' => $session,
                    'assignment_id' => $assignment->id,
                ],
                $reason
            );
        }
    }

    /**
     * Invalidate caches related to a schedule
     */
    protected function invalidateScheduleCache(Schedule $schedule): void
    {
        // Use simple cache forget instead of tags (file cache doesn't support tagging)
        Cache::forget("schedule_{$schedule->id}");
        Cache::forget("schedule_assignments_{$schedule->id}");
        Cache::forget("schedule_statistics_{$schedule->id}");

        Log::debug('Schedule cache invalidated', [
            'schedule_id' => $schedule->id,
        ]);
    }
}
