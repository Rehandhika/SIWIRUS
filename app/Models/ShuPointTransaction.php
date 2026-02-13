<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShuPointTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'sale_id',
        'type',
        'amount',
        'percentage_bps', // Now stores conversion amount
        'points',
        'cash_amount',
        'notes',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'percentage_bps' => 'integer',
        'points' => 'integer',
        'cash_amount' => 'integer',
        'created_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
