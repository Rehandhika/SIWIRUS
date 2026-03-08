<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_start_date',
        'week_end_date',
        'status',
        'generated_by',
        'generated_at',
        'published_at',
        'published_by',
        'total_slots',
        'filled_slots',
        'coverage_rate',
        'notes',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
        'total_slots' => 'integer',
        'filled_slots' => 'integer',
        'coverage_rate' => 'decimal:2',
    ];

    // Relationships
    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function publishedBy()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function availabilities()
    {
        return $this->hasMany(Availability::class);
    }

    public function assignments()
    {
        return $this->hasMany(ScheduleAssignment::class);
    }

    public function histories()
    {
        return $this->hasMany(AssignmentHistory::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeCurrentWeek($query)
    {
        $monday = Carbon::now()->startOfWeek();

        return $query->where('week_start_date', $monday->toDateString());
    }

    // Helpers
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public static function forDate(string $date): ?self
    {
        $monday = Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->toDateString();

        return self::where('week_start_date', $monday)->first();
    }

    public function canEdit(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Get assignment grid (4 days × 3 sessions) - Updated for multi-user slots
     */
    public function getAssignmentGrid(): array
    {
        $assignments = $this->assignments()
            ->with('user:id,name,photo')
            ->get()
            ->groupBy(function ($assignment) {
                return $assignment->date->format('Y-m-d').'_'.$assignment->session;
            });

        $grid = [];
        $startDate = Carbon::parse($this->week_start_date);

        for ($day = 0; $day < 4; $day++) {
            $date = $startDate->copy()->addDays($day);
            $dateStr = $date->format('Y-m-d');

            for ($session = 1; $session <= 3; $session++) {
                $key = $dateStr.'_'.$session;
                // Return collection of assignments instead of single assignment
                $grid[$dateStr][$session] = $assignments->get($key, collect());
            }
        }

        return $grid;
    }

    /**
     * Calculate and update coverage rate - Updated for multi-user slots
     * Coverage now counts slots with at least 1 user (not total assignments)
     */
    public function calculateCoverage(): float
    {
        // Count unique slots that have at least one user
        $filledSlots = $this->assignments()
            ->select('date', 'session')
            ->distinct()
            ->count();

        $this->filled_slots = $filledSlots;
        $this->coverage_rate = $this->total_slots > 0
            ? ($this->filled_slots / $this->total_slots) * 100
            : 0;

        $this->save();

        // Invalidate cache after updating coverage
        $this->invalidateCache();

        return $this->coverage_rate;
    }

    /**
     * Invalidate all caches related to this schedule
     */
    public function invalidateCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget("schedule_grid_{$this->id}");
        \Illuminate\Support\Facades\Cache::forget("schedule_conflicts_{$this->id}");
        \Illuminate\Support\Facades\Cache::forget("schedule_statistics_{$this->id}");

        \Illuminate\Support\Facades\Log::debug('Schedule cache invalidated', [
            'schedule_id' => $this->id,
        ]);
    }

    /**
     * Check if schedule can be published
     */
    public function canPublish(): bool
    {
        if (! $this->isDraft()) {
            return false;
        }

        // Check minimum coverage (at least 50%)
        if ($this->coverage_rate < 50) {
            return false;
        }

        // Check for conflicts
        $conflicts = $this->detectConflicts();
        if (! empty($conflicts['critical'])) {
            return false;
        }

        return true;
    }

    /**
     * Detect conflicts in assignments - Updated for multi-user slots
     */
    public function detectConflicts(): array
    {
        $conflicts = [
            'critical' => [],
            'warning' => [],
            'info' => [],
        ];

        // Check for duplicate users in same slot (same user appears multiple times in one slot)
        $duplicateUsersInSlot = $this->assignments()
            ->select('user_id', 'date', 'session')
            ->groupBy('user_id', 'date', 'session')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicateUsersInSlot->isNotEmpty()) {
            $conflicts['critical'][] = [
                'type' => 'duplicate_user_in_slot',
                'message' => 'Terdapat anggota yang muncul lebih dari sekali dalam slot yang sama',
                'count' => $duplicateUsersInSlot->count(),
            ];
        }

        // Check for inactive users
        $inactiveUsers = $this->assignments()
            ->whereHas('user', function ($query) {
                $query->where('status', '!=', 'active');
            })
            ->count();

        if ($inactiveUsers > 0) {
            $conflicts['critical'][] = [
                'type' => 'inactive_users',
                'message' => 'Terdapat assignment untuk user yang tidak aktif',
                'count' => $inactiveUsers,
            ];
        }

        // Check for overstaffed slots (if max_users_per_slot is configured)
        $maxUsersPerSlot = ScheduleConfiguration::getValue('max_users_per_slot');
        if ($maxUsersPerSlot !== null) {
            $overstaffedSlots = $this->assignments()
                ->select('date', 'session')
                ->selectRaw('COUNT(*) as user_count')
                ->groupBy('date', 'session')
                ->havingRaw('COUNT(*) > ?', [$maxUsersPerSlot])
                ->get();

            if ($overstaffedSlots->isNotEmpty()) {
                $conflicts['warning'][] = [
                    'type' => 'overstaffed_slots',
                    'message' => 'Terdapat slot dengan jumlah anggota melebihi batas maksimal',
                    'count' => $overstaffedSlots->count(),
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        $assignments = $this->assignments()->with('user')->get();

        $userCounts = $assignments->groupBy('user_id')->map(function ($group) {
            return [
                'user' => $group->first()->user,
                'count' => $group->count(),
            ];
        })->sortByDesc('count');

        $sessionCounts = $assignments->groupBy('session')->map->count();

        return [
            'total_assignments' => $assignments->count(),
            'coverage_rate' => $this->coverage_rate,
            'unique_users' => $assignments->pluck('user_id')->unique()->count(),
            'assignments_per_user' => $userCounts->values()->toArray(),
            'assignments_per_session' => $sessionCounts->toArray(),
            'unassigned_slots' => $this->total_slots - $this->filled_slots,
        ];
    }

    /**
     * Get slot statistics for multi-user slots
     */
    public function getSlotStatistics(): array
    {
        $assignments = $this->assignments()->with('user')->get();

        // Group by slot (date + session)
        $slotGroups = $assignments->groupBy(function ($assignment) {
            return $assignment->date->format('Y-m-d').'_'.$assignment->session;
        });

        $slotStats = [];
        $userCounts = [];

        foreach ($slotGroups as $slotKey => $slotAssignments) {
            $userCount = $slotAssignments->count();
            $userCounts[] = $userCount;

            [$date, $session] = explode('_', $slotKey);

            $slotStats[] = [
                'date' => $date,
                'session' => (int) $session,
                'user_count' => $userCount,
                'users' => $slotAssignments->pluck('user.name')->toArray(),
            ];
        }

        // Calculate statistics
        $totalSlots = $this->total_slots;
        $filledSlots = count($slotGroups);
        $emptySlots = $totalSlots - $filledSlots;

        $avgUsersPerSlot = $filledSlots > 0 ? array_sum($userCounts) / $filledSlots : 0;
        $maxUsersInSlot = ! empty($userCounts) ? max($userCounts) : 0;
        $minUsersInSlot = ! empty($userCounts) ? min($userCounts) : 0;

        // Count slots by user count
        $slotsByUserCount = [];
        foreach ($userCounts as $count) {
            $slotsByUserCount[$count] = ($slotsByUserCount[$count] ?? 0) + 1;
        }

        return [
            'total_slots' => $totalSlots,
            'filled_slots' => $filledSlots,
            'empty_slots' => $emptySlots,
            'coverage_rate' => $this->coverage_rate,
            'total_assignments' => $assignments->count(),
            'avg_users_per_slot' => round($avgUsersPerSlot, 2),
            'max_users_in_slot' => $maxUsersInSlot,
            'min_users_in_slot' => $minUsersInSlot,
            'slots_by_user_count' => $slotsByUserCount,
            'slot_details' => $slotStats,
        ];
    }
}
