<?php

namespace App\Livewire\Swap;

use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class UserSwapRequests extends Component
{
    use WithPagination;

    public $filter = 'all'; // all, pending, approved, rejected, completed

    public $search = '';

    public $selectedRequest = null;

    public $showDetailModal = false;

    public $cancelReason = '';

    public $showCancelModal = false;

    protected $queryString = ['filter', 'search'];

    protected $listeners = ['swap-request-created' => '$refresh'];

    public function getStatsProperty()
    {
        return SwapRequest::where('user_id', auth()->id())
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "target_approved" THEN 1 ELSE 0 END) as target_approved,
                SUM(CASE WHEN status = "admin_approved" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status IN ("target_rejected", "admin_rejected") THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled
            ')
            ->first();
    }

    public function cancelRequest($id)
    {
        $request = SwapRequest::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();

        if (! $request) {
            $this->dispatch('toast', message: 'Permintaan tidak ditemukan atau tidak dapat dibatalkan.', type: 'error');

            return;
        }

        // Check if deadline has passed
        $deadline = $request->requesterAssignment->date->copy()
            ->setTimeFromTimeString($request->requesterAssignment->time_start)
            ->subHours(24);

        if (now()->greaterThan($deadline)) {
            $this->dispatch('toast', message: 'Tidak dapat membatalkan permintaan dalam 24 jam sebelum shift.', type: 'error');

            return;
        }

        $this->selectedRequest = $request;
        $this->showCancelModal = true;
    }

    public function confirmCancel()
    {
        if (! $this->selectedRequest) {
            return;
        }

        try {
            DB::beginTransaction();

            $this->selectedRequest->update([
                'status' => 'cancelled',
                'admin_response' => 'Dibatalkan oleh pemohon: '.$this->cancelReason,
                'admin_responded_by' => auth()->id(),
                'admin_responded_at' => now(),
            ]);

            // Create notification for target user
            $this->createNotification($this->selectedRequest->target_id, 'swap_cancelled', [
                'title' => 'Permintaan Tukar Shift Dibatalkan',
                'message' => auth()->user()->name.' membatalkan permintaan tukar shift.',
                'swap_request_id' => $this->selectedRequest->id,
            ]);

            DB::commit();

            $this->dispatch('toast', message: 'Permintaan tukar shift berhasil dibatalkan.', type: 'success');
            $this->reset(['showCancelModal', 'cancelReason', 'selectedRequest']);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Gagal membatalkan permintaan: '.$e->getMessage(), type: 'error');
        }
    }

    public function viewDetails($id)
    {
        $this->selectedRequest = SwapRequest::with([
            'requester:id,name,nim',
            'target:id,name,nim',
            'requesterAssignment.schedule',
            'targetAssignment.schedule',
            'adminResponder:id,name',
        ])->find($id);

        if ($this->selectedRequest && $this->selectedRequest->user_id === auth()->id()) {
            $this->showDetailModal = true;
        } else {
            $this->dispatch('toast', message: 'Permintaan tidak ditemukan.', type: 'error');
        }
    }

    private function createNotification($userId, $type, $data)
    {
        // Implementation depends on your notification system
    }

    public function getStatusColor($status)
    {
        return match ($status) {
            'pending' => 'yellow',
            'target_approved' => 'blue',
            'admin_approved' => 'green',
            'target_rejected', 'admin_rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }

    public function getStatusText($status)
    {
        return match ($status) {
            'pending' => 'Menunggu Persetujuan',
            'target_approved' => 'Disetujui Target',
            'admin_approved' => 'Disetujui Admin',
            'target_rejected' => 'Ditolak Target',
            'admin_rejected' => 'Ditolak Admin',
            'cancelled' => 'Dibatalkan',
            default => 'Unknown'
        };
    }

    public function render()
    {
        $query = SwapRequest::where('user_id', auth()->id())
            ->with([
                'target:id,name,nim',
                'requesterAssignment.schedule',
                'targetAssignment.schedule',
            ]);

        // Apply filter
        if ($this->filter !== 'all') {
            switch ($this->filter) {
                case 'pending':
                    $query->where('status', 'pending');
                    break;
                case 'approved':
                    $query->whereIn('status', ['target_approved', 'admin_approved']);
                    break;
                case 'rejected':
                    $query->whereIn('status', ['target_rejected', 'admin_rejected']);
                    break;
                case 'completed':
                    $query->where('status', 'admin_approved')
                        ->whereNotNull('completed_at');
                    break;
            }
        }

        // Apply search
        if ($this->search) {
            $query->whereHas('target', function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('nim', 'like', '%'.$this->search.'%');
            });
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.swap.user-swap-requests', [
            'requests' => $requests,
            'stats' => $this->stats,
        ])->layout('layouts.app')->title('Permintaan Tukar Shift Saya');
    }
}
