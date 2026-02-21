<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Use ScheduleChangeRequest instead
 * This model is kept for backward compatibility with existing code
 */
class SwapRequest extends Model
{
    use HasFactory;

    protected $table = 'schedule_change_requests';

    protected $fillable = [
        'user_id',
        'target_id',
        'original_assignment_id',
        'target_assignment_id',
        'requested_date',
        'requested_session',
        'change_type',
        'reason',
        'status',
        'target_response',
        'target_responded_at',
        'admin_response',
        'admin_responded_by',
        'admin_responded_at',
        'completed_at',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'admin_responded_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function target()
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function targetAssignment()
    {
        return $this->belongsTo(ScheduleAssignment::class, 'target_assignment_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requesterAssignment()
    {
        return $this->belongsTo(ScheduleAssignment::class, 'original_assignment_id');
    }

    public function originalAssignment()
    {
        return $this->belongsTo(ScheduleAssignment::class, 'original_assignment_id');
    }

    public function adminResponder()
    {
        return $this->belongsTo(User::class, 'admin_responded_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->whereIn('status', ['rejected', 'cancelled']);
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return in_array($this->status, ['rejected', 'cancelled']);
    }

    public function getChangeTypeLabel(): string
    {
        return match ($this->change_type) {
            'reschedule' => 'Pindah Jadwal',
            'cancel' => 'Batalkan Jadwal',
            default => $this->change_type ?? '-',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }
}
