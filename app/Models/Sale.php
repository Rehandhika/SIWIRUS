<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cashier_id',
        'student_id',
        'invoice_number',
        'date',
        'total_amount',
        'payment_method',
        'payment_amount',
        'change_amount',
        'shu_points_earned',
        'shu_percentage_bps', // Now stores conversion amount
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'shu_points_earned' => 'integer',
        'shu_percentage_bps' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function shuPointTransactions()
    {
        return $this->hasMany(ShuPointTransaction::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date', now()->month)
            ->whereYear('date', now()->year);
    }

    public function scopeByCashier($query, $cashierId)
    {
        return $query->where('cashier_id', $cashierId);
    }

    // Helpers
    public function calculateTotal()
    {
        $this->total_amount = $this->items()->sum(DB::raw('quantity * price'));
        $this->save();
    }

    public function calculateChange()
    {
        $this->change_amount = $this->payment_amount - $this->total_amount;
        $this->save();
    }

    public static function generateInvoiceNumber(?string $forDate = null): string
    {
        $date = $forDate ? \Carbon\Carbon::parse($forDate)->format('Ymd') : now()->format('Ymd');
        $prefix = "INV-{$date}-";

        $driver = DB::connection()->getDriverName();
        $sequenceSql = $driver === 'sqlite'
            ? "MAX(CAST(substr(invoice_number, -4) AS INTEGER)) as max_seq"
            : "MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) as max_seq";

        // Get max sequence with row locking
        $maxSeq = static::withTrashed()
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->selectRaw($sequenceSql)
            ->value('max_seq');

        $newNumber = ($maxSeq ?? 0) + 1;

        return $prefix.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate multiple unique invoice numbers for batch insert
     * Must be called within a database transaction for proper locking
     */
    public static function generateBatchInvoiceNumbers(int $count, ?string $forDate = null): array
    {
        if ($count <= 0) {
            return [];
        }

        $date = $forDate ? \Carbon\Carbon::parse($forDate)->format('Ymd') : now()->format('Ymd');
        $prefix = "INV-{$date}-";

        $driver = DB::connection()->getDriverName();
        $sequenceSql = $driver === 'sqlite'
            ? "MAX(CAST(substr(invoice_number, -4) AS INTEGER)) as max_seq"
            : "MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) as max_seq";

        // Get max sequence with row locking to prevent race conditions
        $maxSeq = static::withTrashed()
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->selectRaw($sequenceSql)
            ->value('max_seq');

        $startNumber = ($maxSeq ?? 0) + 1;

        $invoices = [];
        for ($i = 0; $i < $count; $i++) {
            $invoices[] = $prefix.str_pad($startNumber + $i, 4, '0', STR_PAD_LEFT);
        }

        return $invoices;
    }
}
