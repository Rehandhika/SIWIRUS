<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'user_id',
        'day',
        'session',
        'date',
        'time_start',
        'time_end',
        'status',
        'swapped_to_user_id',
        'notes',
        'edited_by',
        'edited_at',
        'edit_reason',
        'previous_values',
    ];

    protected $casts = [
        'date' => 'date',
        'edited_at' => 'datetime',
        'previous_values' => 'array',
        'session' => 'integer',
        'time_start' => 'string',
        'time_end' => 'string',
    ];

    // Relationships
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function swappedToUser()
    {
        return $this->belongsTo(User::class, 'swapped_to_user_id');
    }

    public function attendance()
    {
        return $this->hasOne(Attendance::class);
    }

    public function swapRequests()
    {
        return $this->hasMany(SwapRequest::class, 'original_assignment_id');
    }

    public function targetSwapRequests()
    {
        return $this->hasMany(SwapRequest::class, 'target_assignment_id');
    }

    public function editHistory()
    {
        return $this->hasMany(AssignmentEditHistory::class, 'assignment_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function leaveAffectedSchedules()
    {
        return $this->hasMany(LeaveAffectedSchedule::class);
    }

    /**
     * Get slotmates (other users assigned to the same slot).
     */
    public function slotmates()
    {
        return $this->hasMany(self::class)
            ->where('date', $this->date)
            ->where('session', $this->session)
            ->where('id', '!=', $this->id);
    }

    /**
     * Scope to eager load slotmates with user.
     */
    public function scopeWithSlotmates($query)
    {
        return $query->with(['slotmates' => fn($q) => $q->with('user')]);
    }

    /**
     * Scope to eager load common relationships.
     */
    public function scopeWithCommonRelations($query)
    {
        return $query->with([
            'schedule',
            'user',
            'attendance',
            'slotmates',
        ]);
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeMissed($query)
    {
        return $query->where('status', 'missed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString())
            ->where('status', 'scheduled');
    }

    public function scopeForSlot($query, string $date, int $session)
    {
        return $query->where('date', $date)->where('session', $session);
    }

    // Helpers
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isMissed(): bool
    {
        return $this->status === 'missed';
    }

    public function canSwap(): bool
    {
        return $this->isScheduled() &&
               $this->date->isFuture() &&
               $this->date->diffInHours(now()) > 24;
    }

    public function getSessionLabelAttribute(): string
    {
        $labels = [
            '1' => '07:30 - 10:00',
            '2' => '10:20 - 12:50',
            '3' => '13:30 - 16:00',
        ];

        return $labels[$this->session] ?? '';
    }

    public function getDayLabelAttribute(): string
    {
        $labels = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
        ];

        return $labels[$this->day] ?? '';
    }

    /**
     * Check if assignment is available for swap
     */
    public function isAvailableForSwap(): bool
    {
        return $this->canSwap() && ! $this->swapped_to_user_id;
    }

    /**
     * Get conflict status
     */
    public function getConflictStatusAttribute(): ?string
    {
        // Check for double assignment
        $doubleAssignment = self::where('user_id', $this->user_id)
            ->where('date', $this->date)
            ->where('session', $this->session)
            ->where('id', '!=', $this->id)
            ->exists();

        if ($doubleAssignment) {
            return 'double_assignment';
        }

        // Check if user is inactive
        if ($this->user && $this->user->status !== 'active') {
            return 'inactive_user';
        }

        // Check availability mismatch
        $weekStart = $this->date->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString();
        $availability = Availability::where('user_id', $this->user_id)
            ->where('week_start_date', $weekStart)
            ->where('status', 'submitted')
            ->whereHas('details', function ($query) {
                $dayName = strtolower($this->date->englishDayOfWeek);
                $query->where('day', $dayName)
                    ->where('session', $this->session)
                    ->where('is_available', false);
            })
            ->exists();

        if ($availability) {
            return 'availability_mismatch';
        }

        return null;
    }

    /**
     * Check if user is available for this assignment
     */
    public function checkUserAvailability(): bool
    {
        $dayName = strtolower($this->date->englishDayOfWeek);
        $weekStart = $this->date->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString();

        return AvailabilityDetail::whereHas('availability', function ($query) use ($weekStart) {
            $query->where('user_id', $this->user_id)
                ->where('week_start_date', $weekStart)
                ->where('status', 'submitted');
        })
            ->where('day', $dayName)
            ->where('session', $this->session)
            ->where('is_available', true)
            ->exists();
    }

    /**
     * Get formatted date and session
     */
    public function getFormattedSlotAttribute(): string
    {
        return $this->date->locale('id')->isoFormat('dddd, D MMMM Y').' - Sesi '.$this->session;
    }

    /**
     * Multi-user slot helper methods
     */

    /**
     * Get other users in the same slot (slotmates)
     */
    public function getSlotmates()
    {
        return self::where('schedule_id', $this->schedule_id)
            ->where('date', $this->date)
            ->where('session', $this->session)
            ->where('id', '!=', $this->id)
            ->with('user')
            ->get();
    }

    /**
     * Get count of users in this slot
     */
    public function getSlotUserCount(): int
    {
        return self::where('schedule_id', $this->schedule_id)
            ->where('date', $this->date)
            ->where('session', $this->session)
            ->count();
    }

    /**
     * Check if this is the only user in the slot
     */
    public function isOnlyUserInSlot(): bool
    {
        return $this->getSlotUserCount() === 1;
    }

    /**
     * Check if there are other users in the slot
     */
    public function hasSlotmates(): bool
    {
        return $this->getSlotUserCount() > 1;
    }

    /**
     * Check if assignment has been edited
     */
    public function hasBeenEdited(): bool
    {
        return ! is_null($this->edited_by) && ! is_null($this->edited_at);
    }

    /**
     * Get count of edits
     */
    public function getEditCount(): int
    {
        return $this->editHistory()->count();
    }

    /**
     * Get last edit record
     */
    public function getLastEdit(): ?AssignmentEditHistory
    {
        return $this->editHistory()->latest()->first();
    }
}
