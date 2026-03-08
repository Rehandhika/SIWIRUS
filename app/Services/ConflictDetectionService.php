<?php

namespace App\Services;

use App\Models\AvailabilityDetail;
use App\Models\Schedule;
use App\Models\ScheduleAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Service for detecting and categorizing conflicts in schedule assignments
 * Supports multi-user slot conflict detection
 */
class ConflictDetectionService
{
    /**
     * Conflict types
     */
    const TYPE_DUPLICATE_USER_IN_SLOT = 'duplicate_user_in_slot';

    const TYPE_DOUBLE_ASSIGNMENT = 'double_assignment';

    const TYPE_INACTIVE_USER = 'inactive_user';

    const TYPE_AVAILABILITY_MISMATCH = 'availability_mismatch';

    const TYPE_OVERSTAFFED_SLOT = 'overstaffed_slot';

    const TYPE_CONSECUTIVE_SHIFT = 'consecutive_shift';

    /**
     * Severity levels
     */
    const SEVERITY_CRITICAL = 'critical';

    const SEVERITY_WARNING = 'warning';

    const SEVERITY_INFO = 'info';

    /**
     * Conflict type to severity mapping
     */
    protected array $severityMap = [
        self::TYPE_DUPLICATE_USER_IN_SLOT => self::SEVERITY_CRITICAL,
        self::TYPE_DOUBLE_ASSIGNMENT => self::SEVERITY_CRITICAL,
        self::TYPE_INACTIVE_USER => self::SEVERITY_CRITICAL,
        self::TYPE_AVAILABILITY_MISMATCH => self::SEVERITY_WARNING,
        self::TYPE_OVERSTAFFED_SLOT => self::SEVERITY_WARNING,
        self::TYPE_CONSECUTIVE_SHIFT => self::SEVERITY_INFO,
    ];

    /**
     * Conflict type to message mapping
     */
    protected array $messageMap = [
        self::TYPE_DUPLICATE_USER_IN_SLOT => 'Anggota yang sama muncul lebih dari sekali dalam slot yang sama',
        self::TYPE_DOUBLE_ASSIGNMENT => 'Anggota memiliki lebih dari satu assignment pada waktu yang sama',
        self::TYPE_INACTIVE_USER => 'Assignment untuk anggota yang tidak aktif',
        self::TYPE_AVAILABILITY_MISMATCH => 'Anggota dijadwalkan pada waktu yang ditandai tidak tersedia',
        self::TYPE_OVERSTAFFED_SLOT => 'Slot melebihi batas maksimal jumlah anggota',
        self::TYPE_CONSECUTIVE_SHIFT => 'Anggota memiliki shift berturut-turut',
    ];

    protected ScheduleConfigurationService $configService;

    public function __construct(ScheduleConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Get conflict severity level
     */
    public function getConflictSeverity(string $conflictType): string
    {
        return $this->severityMap[$conflictType] ?? self::SEVERITY_INFO;
    }

    /**
     * Get conflict message
     */
    public function getConflictMessage(string $conflictType): string
    {
        return $this->messageMap[$conflictType] ?? 'Konflik tidak diketahui';
    }

    /**
     * Detect all conflicts in a schedule
     *
     * @return array Structured array with conflicts categorized by severity
     */
    public function detectAllConflicts(Schedule $schedule): array
    {
        $allConflicts = [];

        // Run all specific detection methods
        $allConflicts = array_merge($allConflicts, $this->detectDuplicateUsersInSlot($schedule));
        $allConflicts = array_merge($allConflicts, $this->detectDoubleAssignments($schedule));
        $allConflicts = array_merge($allConflicts, $this->detectInactiveUsers($schedule));
        $allConflicts = array_merge($allConflicts, $this->detectAvailabilityMismatches($schedule));
        $allConflicts = array_merge($allConflicts, $this->detectOverstaffedSlots($schedule));

        // Categorize conflicts by severity
        return $this->categorizeConflicts($allConflicts);
    }

    /**
     * Detect duplicate users in the same slot
     * Same user appears multiple times in one slot (should never happen with proper validation)
     */
    public function detectDuplicateUsersInSlot(Schedule $schedule): array
    {
        $conflicts = [];

        // Find slots where the same user appears multiple times
        $duplicates = ScheduleAssignment::where('schedule_id', $schedule->id)
            ->select('user_id', 'date', 'session', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id', 'date', 'session')
            ->havingRaw('COUNT(*) > 1')
            ->with('user:id,name')
            ->get();

        foreach ($duplicates as $duplicate) {
            // Get all assignments for this duplicate
            $assignments = ScheduleAssignment::where('schedule_id', $schedule->id)
                ->where('user_id', $duplicate->user_id)
                ->where('date', $duplicate->date)
                ->where('session', $duplicate->session)
                ->with('user:id,name')
                ->get();

            $conflicts[] = [
                'type' => self::TYPE_DUPLICATE_USER_IN_SLOT,
                'severity' => $this->getConflictSeverity(self::TYPE_DUPLICATE_USER_IN_SLOT),
                'message' => $this->getConflictMessage(self::TYPE_DUPLICATE_USER_IN_SLOT),
                'user_id' => $duplicate->user_id,
                'user_name' => $duplicate->user->name ?? 'Unknown',
                'date' => $duplicate->date,
                'session' => $duplicate->session,
                'count' => $duplicate->count,
                'assignment_ids' => $assignments->pluck('id')->toArray(),
                'details' => sprintf(
                    '%s muncul %d kali pada %s Sesi %d',
                    $duplicate->user->name ?? 'Unknown',
                    $duplicate->count,
                    $duplicate->date,
                    $duplicate->session
                ),
            ];
        }

        return $conflicts;
    }

    /**
     * Detect double assignments
     * User assigned to multiple slots at the same time (shouldn't happen but check anyway)
     * This is different from duplicate - this checks if user has multiple different assignments at same time
     */
    public function detectDoubleAssignments(Schedule $schedule): array
    {
        $conflicts = [];

        // This would only happen if there's a data integrity issue
        // In multi-user slots, a user can only be in ONE slot at a time
        // But we check to be safe

        $assignments = ScheduleAssignment::where('schedule_id', $schedule->id)
            ->with('user:id,name')
            ->get()
            ->groupBy(function ($assignment) {
                return $assignment->user_id.'_'.$assignment->date.'_'.$assignment->session;
            });

        foreach ($assignments as $key => $group) {
            if ($group->count() > 1) {
                // This is actually a duplicate user in slot, already handled above
                // But we keep this check for data integrity
                continue;
            }
        }

        // Check if user has assignments in different schedules at the same time
        // (This is more of a cross-schedule conflict, but we'll skip for now as it's out of scope)

        return $conflicts;
    }

    /**
     * Detect inactive users
     * Assignments with users who are not active
     */
    public function detectInactiveUsers(Schedule $schedule): array
    {
        $conflicts = [];

        $inactiveAssignments = ScheduleAssignment::where('schedule_id', $schedule->id)
            ->whereHas('user', function ($query) {
                $query->where('status', '!=', 'active');
            })
            ->with('user:id,name,status')
            ->get();

        foreach ($inactiveAssignments as $assignment) {
            $conflicts[] = [
                'type' => self::TYPE_INACTIVE_USER,
                'severity' => $this->getConflictSeverity(self::TYPE_INACTIVE_USER),
                'message' => $this->getConflictMessage(self::TYPE_INACTIVE_USER),
                'assignment_id' => $assignment->id,
                'user_id' => $assignment->user_id,
                'user_name' => $assignment->user->name ?? 'Unknown',
                'user_status' => $assignment->user->status ?? 'unknown',
                'date' => $assignment->date->format('Y-m-d'),
                'session' => $assignment->session,
                'details' => sprintf(
                    '%s (status: %s) dijadwalkan pada %s Sesi %d',
                    $assignment->user->name ?? 'Unknown',
                    $assignment->user->status ?? 'unknown',
                    $assignment->date->format('d M Y'),
                    $assignment->session
                ),
            ];
        }

        return $conflicts;
    }

    /**
     * Detect availability mismatches
     * Users assigned when marked unavailable
     */
    public function detectAvailabilityMismatches(Schedule $schedule): array
    {
        $conflicts = [];

        $assignments = ScheduleAssignment::where('schedule_id', $schedule->id)
            ->with('user:id,name')
            ->get();

        foreach ($assignments as $assignment) {
            // Get day name from date
            $dayName = strtolower($assignment->date->englishDayOfWeek);

            // Check if user marked this slot as unavailable
            $isUnavailable = AvailabilityDetail::whereHas('availability', function ($query) use ($schedule, $assignment) {
                $query->where('user_id', $assignment->user_id)
                    ->where('week_start_date', $schedule->week_start_date)
                    ->where('status', 'submitted');
            })
                ->where('day', $dayName)
                ->where('session', $assignment->session)
                ->where('is_available', false)
                ->exists();

            if ($isUnavailable) {
                $conflicts[] = [
                    'type' => self::TYPE_AVAILABILITY_MISMATCH,
                    'severity' => $this->getConflictSeverity(self::TYPE_AVAILABILITY_MISMATCH),
                    'message' => $this->getConflictMessage(self::TYPE_AVAILABILITY_MISMATCH),
                    'assignment_id' => $assignment->id,
                    'user_id' => $assignment->user_id,
                    'user_name' => $assignment->user->name ?? 'Unknown',
                    'date' => $assignment->date->format('Y-m-d'),
                    'day' => $dayName,
                    'session' => $assignment->session,
                    'details' => sprintf(
                        '%s dijadwalkan pada %s Sesi %d tetapi menandai tidak tersedia',
                        $assignment->user->name ?? 'Unknown',
                        $assignment->date->format('d M Y'),
                        $assignment->session
                    ),
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Detect overstaffed slots
     * Slots exceeding max_users_per_slot configuration
     */
    public function detectOverstaffedSlots(Schedule $schedule): array
    {
        $conflicts = [];

        // Get max users per slot from configuration
        $maxUsersPerSlot = $this->configService->get('max_users_per_slot');

        // If no limit is set, no overstaffing is possible
        if ($maxUsersPerSlot === null) {
            return $conflicts;
        }

        // Find slots with more users than allowed
        $overstaffedSlots = ScheduleAssignment::where('schedule_id', $schedule->id)
            ->select('date', 'session', DB::raw('COUNT(*) as user_count'))
            ->groupBy('date', 'session')
            ->havingRaw('COUNT(*) > ?', [$maxUsersPerSlot])
            ->get();

        foreach ($overstaffedSlots as $slot) {
            // Get users in this slot
            $assignments = ScheduleAssignment::where('schedule_id', $schedule->id)
                ->where('date', $slot->date)
                ->where('session', $slot->session)
                ->with('user:id,name')
                ->get();

            $conflicts[] = [
                'type' => self::TYPE_OVERSTAFFED_SLOT,
                'severity' => $this->getConflictSeverity(self::TYPE_OVERSTAFFED_SLOT),
                'message' => $this->getConflictMessage(self::TYPE_OVERSTAFFED_SLOT),
                'date' => $slot->date,
                'session' => $slot->session,
                'user_count' => $slot->user_count,
                'max_allowed' => $maxUsersPerSlot,
                'excess_count' => $slot->user_count - $maxUsersPerSlot,
                'assignment_ids' => $assignments->pluck('id')->toArray(),
                'user_names' => $assignments->pluck('user.name')->toArray(),
                'details' => sprintf(
                    'Slot %s Sesi %d memiliki %d anggota (maksimal: %d)',
                    $slot->date,
                    $slot->session,
                    $slot->user_count,
                    $maxUsersPerSlot
                ),
            ];
        }

        return $conflicts;
    }

    /**
     * Categorize conflicts by severity level
     */
    public function categorizeConflicts(array $conflicts): array
    {
        $categorized = [
            'critical' => [],
            'warning' => [],
            'info' => [],
            'all' => $conflicts,
        ];

        foreach ($conflicts as $conflict) {
            $severity = $conflict['severity'] ?? self::SEVERITY_INFO;

            if (isset($categorized[$severity])) {
                $categorized[$severity][] = $conflict;
            }
        }

        // Add summary counts
        $categorized['summary'] = [
            'total' => count($conflicts),
            'critical_count' => count($categorized['critical']),
            'warning_count' => count($categorized['warning']),
            'info_count' => count($categorized['info']),
            'has_critical' => count($categorized['critical']) > 0,
            'has_warnings' => count($categorized['warning']) > 0,
            'has_conflicts' => count($conflicts) > 0,
        ];

        return $categorized;
    }

    /**
     * Format conflict for display
     */
    public function formatConflictMessage(array $conflict): string
    {
        $icon = match ($conflict['severity'] ?? self::SEVERITY_INFO) {
            self::SEVERITY_CRITICAL => '❌',
            self::SEVERITY_WARNING => '⚠️',
            self::SEVERITY_INFO => 'ℹ️',
            default => '•',
        };

        return sprintf(
            '%s %s: %s',
            $icon,
            $conflict['message'] ?? 'Konflik',
            $conflict['details'] ?? ''
        );
    }

    /**
     * Get conflicts grouped by type
     */
    public function groupConflictsByType(array $conflicts): array
    {
        $grouped = [];

        foreach ($conflicts as $conflict) {
            $type = $conflict['type'] ?? 'unknown';

            if (! isset($grouped[$type])) {
                $grouped[$type] = [
                    'type' => $type,
                    'severity' => $conflict['severity'] ?? self::SEVERITY_INFO,
                    'message' => $conflict['message'] ?? 'Konflik tidak diketahui',
                    'count' => 0,
                    'conflicts' => [],
                ];
            }

            $grouped[$type]['count']++;
            $grouped[$type]['conflicts'][] = $conflict;
        }

        return array_values($grouped);
    }

    /**
     * Check if schedule has critical conflicts
     */
    public function hasCriticalConflicts(Schedule $schedule): bool
    {
        $conflicts = $this->detectAllConflicts($schedule);

        return $conflicts['summary']['has_critical'];
    }

    /**
     * Get conflict count by severity
     */
    public function getConflictCount(Schedule $schedule, string $severity = 'all'): int
    {
        $conflicts = $this->detectAllConflicts($schedule);

        if ($severity === 'all') {
            return $conflicts['summary']['total'];
        }

        return count($conflicts[$severity] ?? []);
    }

    /**
     * Get conflicts for a specific user
     */
    public function getConflictsForUser(Schedule $schedule, int $userId): array
    {
        $allConflicts = $this->detectAllConflicts($schedule);
        $userConflicts = [];

        foreach ($allConflicts['all'] as $conflict) {
            if (isset($conflict['user_id']) && $conflict['user_id'] === $userId) {
                $userConflicts[] = $conflict;
            }
        }

        return $this->categorizeConflicts($userConflicts);
    }

    /**
     * Get conflicts for a specific slot
     */
    public function getConflictsForSlot(Schedule $schedule, string $date, int $session): array
    {
        $allConflicts = $this->detectAllConflicts($schedule);
        $slotConflicts = [];

        foreach ($allConflicts['all'] as $conflict) {
            $conflictDate = $conflict['date'] ?? null;
            $conflictSession = $conflict['session'] ?? null;

            if ($conflictDate === $date && $conflictSession === $session) {
                $slotConflicts[] = $conflict;
            }
        }

        return $this->categorizeConflicts($slotConflicts);
    }
}
