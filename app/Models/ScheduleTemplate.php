<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'pattern',
        'is_public',
        'usage_count',
    ];

    protected $casts = [
        'pattern' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * Relationship: Template creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope: Public templates
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: User's templates
     */
    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope: Popular templates
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Check if user can edit this template
     */
    public function canEdit(User $user): bool
    {
        return $this->created_by === $user->id || $user->can('kelola_jadwal');
    }

    /**
     * Check if user can delete this template
     */
    public function canDelete(User $user): bool
    {
        return $this->created_by === $user->id || $user->can('kelola_jadwal');
    }

    /**
     * Get pattern summary
     */
    public function getPatternSummary(): array
    {
        $pattern = $this->pattern ?? [];

        return [
            'total_assignments' => count($pattern),
            'unique_users' => count(array_unique(array_column($pattern, 'user_id'))),
            'days_covered' => count(array_unique(array_column($pattern, 'day'))),
            'sessions_covered' => count(array_unique(array_column($pattern, 'session'))),
        ];
    }
}
