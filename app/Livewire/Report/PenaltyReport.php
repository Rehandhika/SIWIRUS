<?php

namespace App\Livewire\Report;

use App\Models\Penalty;
use App\Services\PenaltyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Laporan Penalti')]
class PenaltyReport extends Component
{
    use WithPagination;
    use \App\Traits\AuthorizesLivewireRequests;

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $userFilter = 'all';

    public string $statusFilter = 'all';

    public string $period = 'month';

    public $selectedPenalty;

    public $reviewNotes = '';

    public $showReviewModal = false;

    protected PenaltyService $penaltyService;

    protected $rules = [
        'reviewNotes' => 'required|string|min:10|max:500',
    ];

    public function boot(PenaltyService $penaltyService)
    {
        $this->penaltyService = $penaltyService;
    }

    public function mount()
    {
        $this->authorizePermission('lihat_laporan');
        $this->setPeriod('month');
    }

    /**
     * Open review modal for an appealed penalty
     */
    public function openReviewModal($penaltyId)
    {
        $this->selectedPenalty = Penalty::with(['user', 'penaltyType', 'reference'])
            ->findOrFail($penaltyId);

        if ($this->selectedPenalty->status !== 'appealed') {
            $this->dispatch('toast', message: 'Penalti ini tidak dalam status banding', type: 'error');

            return;
        }

        $this->showReviewModal = true;
        $this->reviewNotes = '';
    }

    /**
     * Approve penalty appeal
     */
    public function approveAppeal()
    {
        // Ensure user has permission to manage penalties
        if (!auth()->user()->can('kelola_penalti')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk mengelola penalti', type: 'error');
            return;
        }

        $this->validate();

        try {
            $this->penaltyService->reviewAppeal(
                $this->selectedPenalty,
                true,
                $this->reviewNotes,
                auth()->id()
            );

            $this->dispatch('toast', message: 'Banding disetujui, penalti telah dibatalkan', type: 'success');
            $this->reset(['showReviewModal', 'reviewNotes', 'selectedPenalty']);

            // Refresh the page data
            $this->resetPage();
        } catch (\Exception $e) {
            Log::error('Error approving penalty appeal', [
                'penalty_id' => $this->selectedPenalty->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', message: 'Gagal menyetujui banding: '.$e->getMessage(), type: 'error');
        }
    }

    /**
     * Reject penalty appeal
     */
    public function rejectAppeal()
    {
        // Ensure user has permission to manage penalties
        if (!auth()->user()->can('kelola_penalti')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk mengelola penalti', type: 'error');
            return;
        }

        $this->validate();

        try {
            $this->penaltyService->reviewAppeal(
                $this->selectedPenalty,
                false,
                $this->reviewNotes,
                auth()->id()
            );

            $this->dispatch('toast', message: 'Banding ditolak, penalti tetap aktif', type: 'success');
            $this->reset(['showReviewModal', 'reviewNotes', 'selectedPenalty']);

            // Refresh the page data
            $this->resetPage();
        } catch (\Exception $e) {
            Log::error('Error rejecting penalty appeal', [
                'penalty_id' => $this->selectedPenalty->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', message: 'Gagal menolak banding: '.$e->getMessage(), type: 'error');
        }
    }

    public function setPeriod(string $period)
    {
        $this->period = $period;
        $now = now();

        [$this->dateFrom, $this->dateTo] = match ($period) {
            'today' => [$now->format('Y-m-d'), $now->format('Y-m-d')],
            'yesterday' => [$now->copy()->subDay()->format('Y-m-d'), $now->copy()->subDay()->format('Y-m-d')],
            'week' => [$now->copy()->startOfWeek()->format('Y-m-d'), $now->copy()->endOfWeek()->format('Y-m-d')],
            'month' => [$now->copy()->startOfMonth()->format('Y-m-d'), $now->copy()->endOfMonth()->format('Y-m-d')],
            'year' => [$now->copy()->startOfYear()->format('Y-m-d'), $now->copy()->endOfYear()->format('Y-m-d')],
            default => [$this->dateFrom, $this->dateTo],
        };

        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->updatePeriodBasedOnDates();
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->updatePeriodBasedOnDates();
        $this->resetPage();
    }

    private function updatePeriodBasedOnDates()
    {
        if (empty($this->dateFrom) || empty($this->dateTo)) {
            $this->period = 'custom';

            return;
        }

        $now = now();
        $dateFrom = \Carbon\Carbon::parse($this->dateFrom);
        $dateTo = \Carbon\Carbon::parse($this->dateTo);

        // Check if dates match predefined periods
        if ($dateFrom->format('Y-m-d') === $now->format('Y-m-d') &&
            $dateTo->format('Y-m-d') === $now->format('Y-m-d')) {
            $this->period = 'today';
        } elseif ($dateFrom->format('Y-m-d') === $now->copy()->subDay()->format('Y-m-d') &&
                  $dateTo->format('Y-m-d') === $now->copy()->subDay()->format('Y-m-d')) {
            $this->period = 'yesterday';
        } elseif ($dateFrom->format('Y-m-d') === $now->copy()->startOfWeek()->format('Y-m-d') &&
                  $dateTo->format('Y-m-d') === $now->copy()->endOfWeek()->format('Y-m-d')) {
            $this->period = 'week';
        } elseif ($dateFrom->format('Y-m-d') === $now->copy()->startOfMonth()->format('Y-m-d') &&
                  $dateTo->format('Y-m-d') === $now->copy()->endOfMonth()->format('Y-m-d')) {
            $this->period = 'month';
        } elseif ($dateFrom->format('Y-m-d') === $now->copy()->startOfYear()->format('Y-m-d') &&
                  $dateTo->format('Y-m-d') === $now->copy()->endOfYear()->format('Y-m-d')) {
            $this->period = 'year';
        } else {
            $this->period = 'custom';
        }
    }

    public function updatedUserFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    /**
     * Single optimized query untuk statistik
     */
    #[Computed]
    public function stats()
    {
        $userCondition = $this->userFilter !== 'all' ? 'AND user_id = ?' : '';
        $params = [$this->dateFrom, $this->dateTo];

        if ($this->userFilter !== 'all') {
            $params[] = $this->userFilter;
        }

        $result = DB::select("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'appealed' THEN 1 ELSE 0 END) as appealed,
                SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed,
                SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
                COALESCE(SUM(points), 0) as total_points
            FROM penalties 
            WHERE date BETWEEN ? AND ? {$userCondition}
        ", $params);

        return $result[0] ?? (object) [
            'total' => 0, 'active' => 0, 'appealed' => 0,
            'dismissed' => 0, 'expired' => 0, 'total_points' => 0,
        ];
    }

    /**
     * Cache users list - jarang berubah
     */
    #[Computed]
    public function users()
    {
        return DB::select('SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name');
    }

    public function render()
    {
        $penalties = Penalty::query()
            ->whereBetween('date', [$this->dateFrom, $this->dateTo])
            ->when($this->userFilter !== 'all', fn ($q) => $q->where('user_id', $this->userFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->with(['user:id,name,nim', 'penaltyType:id,name,code'])
            ->select('id', 'user_id', 'penalty_type_id', 'date', 'points', 'description', 'status', 'reference_type', 'reference_id')
            ->latest('date')
            ->paginate(15);

        return view('livewire.report.penalty-report', ['penalties' => $penalties])
            ->layout('layouts.app');
    }
}
