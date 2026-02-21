<?php

namespace App\Livewire\Swap;

use App\Models\ScheduleAssignment;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Tukar Jadwal')]
#[Layout('layouts.app')]
#[Lazy]
class SwapManager extends Component
{
    use WithPagination;

    #[Url(as: 'tab')]
    public string $activeTab = 'my-requests'; // my-requests, received, admin

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    // Create form
    public bool $showForm = false;

    public ?int $selectedAssignment = null;

    public string $targetDate = '';

    public int $targetSession = 0;

    public ?int $selectedTarget = null;

    public string $reason = '';

    // Detail/Review modal
    public ?int $viewingId = null;

    public ?int $reviewingId = null;

    public string $reviewAction = '';

    public string $reviewNotes = '';

    public function mount(): void
    {
        $this->targetDate = now()->addDay()->format('Y-m-d');
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="animate-pulse space-y-4">
            <div class="h-8 bg-gray-200 rounded w-1/4"></div>
            <div class="h-32 bg-gray-200 rounded"></div>
        </div>
        HTML;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    // === CREATE FORM ===
    public function openForm(): void
    {
        $this->reset(['selectedAssignment', 'targetSession', 'selectedTarget', 'reason']);
        $this->targetDate = now()->addDay()->format('Y-m-d');
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function submitForm(): void
    {
        $this->validate([
            'selectedAssignment' => 'required|exists:schedule_assignments,id',
            'targetDate' => 'required|date|after_or_equal:today',
            'targetSession' => 'required|in:1,2,3',
            'selectedTarget' => 'required|exists:users,id',
            'reason' => 'required|string|min:10|max:500',
        ], [
            'selectedAssignment.required' => 'Pilih jadwal Anda yang ingin ditukar',
            'targetSession.required' => 'Pilih sesi target',
            'selectedTarget.required' => 'Pilih anggota target',
            'reason.min' => 'Alasan minimal 10 karakter',
        ]);

        // Validate business rules
        $myAssignment = ScheduleAssignment::find($this->selectedAssignment);

        // Check 24h deadline
        $deadline = $myAssignment->date->copy()->setTimeFromTimeString($myAssignment->time_start)->subHours(24);
        if (now()->gt($deadline)) {
            $this->addError('selectedAssignment', 'Minimal 24 jam sebelum shift');

            return;
        }

        // Check existing pending request
        $exists = SwapRequest::where('user_id', Auth::id())
            ->where('requester_assignment_id', $this->selectedAssignment)
            ->whereIn('status', ['pending', 'target_approved'])
            ->exists();
        if ($exists) {
            $this->addError('selectedAssignment', 'Sudah ada permintaan untuk jadwal ini');

            return;
        }

        // Get target assignment
        $targetAssignment = ScheduleAssignment::where('date', $this->targetDate)
            ->where('session', $this->targetSession)
            ->where('user_id', $this->selectedTarget)
            ->first();

        if (! $targetAssignment) {
            $this->addError('selectedTarget', 'Target tidak memiliki jadwal di waktu tersebut');

            return;
        }

        SwapRequest::create([
            'user_id' => Auth::id(),
            'target_id' => $this->selectedTarget,
            'requester_assignment_id' => $this->selectedAssignment,
            'target_assignment_id' => $targetAssignment->id,
            'reason' => $this->reason,
            'status' => 'pending',
        ]);

        $this->closeForm();
        $this->clearCache();
        $this->dispatch('toast', message: 'Permintaan tukar jadwal dikirim', type: 'success');
    }

    // === VIEW & ACTIONS ===
    public function viewRequest(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
    }

    public function cancelRequest(int $id): void
    {
        SwapRequest::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $this->viewingId = null;
        $this->clearCache();
        $this->dispatch('toast', message: 'Permintaan dibatalkan', type: 'success');
    }

    // Target user approve/reject
    public function targetApprove(int $id): void
    {
        SwapRequest::where('id', $id)
            ->where('target_id', Auth::id())
            ->where('status', 'pending')
            ->update([
                'status' => 'target_approved',
                'target_responded_at' => now(),
            ]);

        $this->viewingId = null;
        $this->clearCache();
        $this->dispatch('toast', message: 'Permintaan disetujui, menunggu admin', type: 'success');
    }

    public function targetReject(int $id): void
    {
        SwapRequest::where('id', $id)
            ->where('target_id', Auth::id())
            ->where('status', 'pending')
            ->update([
                'status' => 'target_rejected',
                'target_responded_at' => now(),
            ]);

        $this->viewingId = null;
        $this->clearCache();
        $this->dispatch('toast', message: 'Permintaan ditolak', type: 'success');
    }

    // Admin approve/reject
    public function openAdminReview(int $id, string $action): void
    {
        $this->reviewingId = $id;
        $this->reviewAction = $action;
        $this->reviewNotes = '';
    }

    public function closeAdminReview(): void
    {
        $this->reviewingId = null;
        $this->reviewAction = '';
        $this->reviewNotes = '';
    }

    public function submitAdminReview(): void
    {
        $swap = SwapRequest::with(['requesterAssignment', 'targetAssignment'])->find($this->reviewingId);
        if (! $swap || $swap->status !== 'target_approved') {
            return;
        }

        DB::beginTransaction();
        try {
            $newStatus = $this->reviewAction === 'approved' ? 'admin_approved' : 'admin_rejected';

            $swap->update([
                'status' => $newStatus,
                'admin_response' => $this->reviewNotes,
                'admin_responded_by' => Auth::id(),
                'admin_responded_at' => now(),
                'completed_at' => $newStatus === 'admin_approved' ? now() : null,
            ]);

            // If approved, swap the assignments
            if ($newStatus === 'admin_approved') {
                $reqAssignment = $swap->requesterAssignment;
                $tgtAssignment = $swap->targetAssignment;

                // Swap user_id
                $tempUserId = $reqAssignment->user_id;
                $reqAssignment->update(['user_id' => $tgtAssignment->user_id]);
                $tgtAssignment->update(['user_id' => $tempUserId]);
            }

            DB::commit();

            $this->closeAdminReview();
            $this->viewingId = null;
            $this->clearCache();

            $msg = $this->reviewAction === 'approved' ? 'Tukar jadwal disetujui dan diproses' : 'Permintaan ditolak';
            $this->dispatch('toast', message: $msg, type: 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Gagal memproses', type: 'error');
        }
    }

    private function clearCache(): void
    {
        Cache::forget('swap_stats_'.Auth::id());
    }

    public function render()
    {
        $userId = Auth::id();
        $isAdmin = Auth::user()->hasAnyRole(['Super Admin', 'Ketua', 'Wakil Ketua', 'BPH']);

        // Stats - cached
        $stats = Cache::remember("swap_stats_{$userId}_{$this->activeTab}", 60, function () use ($userId) {
            if ($this->activeTab === 'my-requests') {
                return [
                    'pending' => SwapRequest::where('user_id', $userId)->where('status', 'pending')->count(),
                    'approved' => SwapRequest::where('user_id', $userId)->whereIn('status', ['target_approved', 'admin_approved'])->count(),
                    'rejected' => SwapRequest::where('user_id', $userId)->whereIn('status', ['target_rejected', 'admin_rejected', 'cancelled'])->count(),
                ];
            } elseif ($this->activeTab === 'received') {
                return [
                    'pending' => SwapRequest::where('target_id', $userId)->where('status', 'pending')->count(),
                    'approved' => SwapRequest::where('target_id', $userId)->where('status', 'target_approved')->count(),
                    'rejected' => SwapRequest::where('target_id', $userId)->where('status', 'target_rejected')->count(),
                ];
            } else {
                return [
                    'pending' => SwapRequest::where('status', 'target_approved')->count(),
                    'approved' => SwapRequest::where('status', 'admin_approved')->count(),
                    'rejected' => SwapRequest::where('status', 'admin_rejected')->count(),
                ];
            }
        });

        // Query
        $query = match ($this->activeTab) {
            'my-requests' => SwapRequest::where('user_id', $userId),
            'received' => SwapRequest::where('target_id', $userId),
            'admin' => SwapRequest::whereIn('status', ['target_approved', 'admin_approved', 'admin_rejected']),
            default => SwapRequest::where('user_id', $userId),
        };

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $requests = $query->with([
            'requester:id,name,nim',
            'target:id,name,nim',
            'requesterAssignment:id,date,session,time_start,time_end',
            'targetAssignment:id,date,session,time_start,time_end',
        ])->latest()->paginate(10);

        // My assignments for form
        $myAssignments = $this->showForm
            ? ScheduleAssignment::where('user_id', $userId)
                ->where('date', '>=', now()->format('Y-m-d'))
                ->orderBy('date')
                ->get(['id', 'date', 'session', 'time_start', 'time_end'])
            : collect();

        // Available targets for form
        $availableTargets = ($this->showForm && $this->targetDate && $this->targetSession)
            ? ScheduleAssignment::where('date', $this->targetDate)
                ->where('session', $this->targetSession)
                ->where('user_id', '!=', $userId)
                ->with('user:id,name,nim')
                ->get()
                ->map(fn ($a) => ['id' => $a->user_id, 'name' => $a->user->name, 'nim' => $a->user->nim])
            : collect();

        // Viewing request
        $viewingRequest = $this->viewingId
            ? SwapRequest::with([
                'requester:id,name,nim',
                'target:id,name,nim',
                'requesterAssignment:id,date,session,time_start,time_end',
                'targetAssignment:id,date,session,time_start,time_end',
                'adminResponder:id,name',
            ])->find($this->viewingId)
            : null;

        return view('livewire.swap.swap-manager', [
            'requests' => $requests,
            'stats' => $stats,
            'isAdmin' => $isAdmin,
            'myAssignments' => $myAssignments,
            'availableTargets' => $availableTargets,
            'viewingRequest' => $viewingRequest,
        ]);
    }
}
