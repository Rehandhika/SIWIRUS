<?php

namespace App\Livewire\Penalty;

use App\Models\Penalty;
use App\Services\ActivityLogService;
use App\Services\PenaltyService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class UserPenalties extends Component
{
    use WithPagination;

    public $selectedPenalty;

    public $appealReason = '';

    public $showAppealModal = false;

    protected PenaltyService $penaltyService;

    protected $rules = [
        'appealReason' => 'required|string|min:20|max:500',
    ];

    public function boot(PenaltyService $penaltyService)
    {
        $this->penaltyService = $penaltyService;
    }

    /**
     * Open appeal modal for a penalty
     */
    public function openAppealModal($penaltyId)
    {
        $this->selectedPenalty = Penalty::with(['penaltyType', 'reference'])
            ->where('user_id', auth()->id())
            ->findOrFail($penaltyId);

        // Check if penalty can be appealed
        if (! in_array($this->selectedPenalty->status, ['active', 'appealed'])) {
            $this->dispatch('toast', message: 'Penalti ini tidak dapat dibanding', type: 'error');

            return;
        }

        if ($this->selectedPenalty->status === 'appealed') {
            $this->dispatch('toast', message: 'Banding untuk penalti ini sedang dalam proses review', type: 'info');

            return;
        }

        $this->showAppealModal = true;
        $this->appealReason = '';
    }

    /**
     * Submit penalty appeal
     */
    public function submitAppeal()
    {
        $this->validate();

        try {
            $this->penaltyService->submitAppeal(
                $this->selectedPenalty,
                $this->appealReason
            );

            ActivityLogService::log("Mengajukan banding untuk penalti #{$this->selectedPenalty->id}");
            $this->dispatch('toast', message: 'Banding berhasil diajukan dan akan ditinjau oleh admin', type: 'success');
            $this->reset(['showAppealModal', 'appealReason', 'selectedPenalty']);

            // Refresh the page data
            $this->resetPage();
        } catch (\Exception $e) {
            Log::error('Error submitting penalty appeal', [
                'penalty_id' => $this->selectedPenalty->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', message: 'Gagal mengajukan banding: '.$e->getMessage(), type: 'error');
        }
    }

    /**
     * Get user's total active penalty points
     */
    public function getTotalPointsProperty()
    {
        return $this->penaltyService->getUserTotalPoints(auth()->id());
    }

    public function render()
    {
        $penalties = Penalty::with(['penaltyType', 'reference'])
            ->where('user_id', auth()->id())
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total_points' => $this->totalPoints,
            'active_count' => Penalty::where('user_id', auth()->id())
                ->where('status', 'active')
                ->count(),
            'appealed_count' => Penalty::where('user_id', auth()->id())
                ->where('status', 'appealed')
                ->count(),
            'dismissed_count' => Penalty::where('user_id', auth()->id())
                ->where('status', 'dismissed')
                ->count(),
        ];

        return view('livewire.penalty.user-penalties', [
            'penalties' => $penalties,
            'stats' => $stats,
        ])->layout('layouts.app')->title('Penalti Saya');
    }
}
