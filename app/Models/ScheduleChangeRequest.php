<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleChangeRequest extends Model
{
    use HasFactory;

    protected $table = 'schedule_change_requests';

    protected $fillable = [
        'user_id',
        'original_assignment_id',
        'requested_date',
        'requested_session',
        'change_type',
        'reason',
        'status',
        'admin_response',
        'admin_responded_by',
        'admin_responded_at',
        'completed_at',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'admin_responded_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
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
        return $query->where('status', 'rejected');
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
        return $this->status === 'rejected';
    }

    public function canCancel(): bool
    {
        return $this->status === 'pending';
    }

    public function getChangeTypeLabel(): string
    {
        return match ($this->change_type) {
            'swap' => 'Tukar Shift',
            'reschedule' => 'Pindah Jadwal',
            'cancel' => 'Batalkan Jadwal',
            default => $this->change_type,
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

    public function getSessionLabel(?int $session = null): string
    {
        $s = $session ?? $this->requested_session;

        return match ($s) {
            1 => 'Sesi 1 (07:30-10:00)',
            2 => 'Sesi 2 (10:20-12:50)',
            3 => 'Sesi 3 (13:30-16:00)',
            default => '-',
        };
    }
}
