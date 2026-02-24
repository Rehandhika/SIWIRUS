<?php

namespace App\Livewire\Swap;

use App\Models\SwapRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SwapRequestList extends Component
{
    use WithPagination;

    public $tab = 'my-requests'; // my-requests, received, all

    public $search = '';

    public function cancelRequest($id)
    {
        $swap = SwapRequest::with(['requesterAssignment', 'targetAssignment'])->find($id);

        if ($swap && $swap->user_id === auth()->id() && $swap->status === 'pending') {
            $swap->update(['status' => 'cancelled']);

            // Log activity
            $date = $swap->requesterAssignment?->date?->format('d/m/Y') ?? 'N/A';
            ActivityLogService::log("Membatalkan permintaan tukar shift tanggal {$date}");

            $this->dispatch('toast', message: 'Permintaan tukar shift dibatalkan', type: 'success');
        }
    }

    public function acceptRequest($id)
    {
        $swap = SwapRequest::with(['requester', 'requesterAssignment'])->find($id);

        if ($swap && $swap->target_id === auth()->id() && $swap->status === 'pending') {
            DB::beginTransaction();
            try {
                $swap->update(['status' => 'target_approved', 'target_responded_at' => now()]);

                // If it's a direct swap request, perform the swap on assignments
                if ($swap->change_type === 'swap') {
                    $reqAssignment = $swap->requesterAssignment;
                    $tgtAssignment = $swap->targetAssignment;

                    if ($reqAssignment && $tgtAssignment) {
                        $reqUserId = $reqAssignment->user_id;
                        $tgtUserId = $tgtAssignment->user_id;

                        // Swap assignments
                        $reqAssignment->update([
                            'user_id' => $tgtUserId,
                            'swapped_to_user_id' => $reqUserId,
                        ]);

                        $tgtAssignment->update([
                            'user_id' => $reqUserId,
                            'swapped_to_user_id' => $tgtUserId,
                        ]);

                        // Automatically mark as admin_approved for peer-to-peer agreement
                        // (Adjust if admin final review is strictly required)
                        $swap->update([
                            'status' => 'admin_approved',
                            'completed_at' => now(),
                        ]);
                    }
                }

                DB::commit();

                // Log activity
                $date = $swap->requesterAssignment?->date?->format('d/m/Y') ?? 'N/A';
                ActivityLogService::logSwapApproved($swap->requester->name, $date);

                // Create notification for requester
                $this->createNotification($swap->user_id, 'swap_accepted', [
                    'title' => 'Permintaan Tukar Shift Disetujui',
                    'message' => auth()->user()->name.' menyetujui permintaan tukar shift Anda.',
                    'swap_request_id' => $swap->id,
                ]);

                // Notify admins for record
                $this->notifyAdminsForApproval($swap);

                $this->dispatch('toast', message: 'Permintaan tukar shift diterima dan jadwal diperbarui.', type: 'success');

            } catch (\Exception $e) {
                DB::rollBack();
                $this->dispatch('toast', message: 'Gagal memperbarui jadwal: '.$e->getMessage(), type: 'error');
            }
        }
    }

    public function rejectRequest($id)
    {
        $swap = SwapRequest::with(['requester', 'requesterAssignment'])->find($id);

        if ($swap && $swap->target_id === auth()->id() && $swap->status === 'pending') {
            $swap->update([
                'status' => 'target_rejected',
                'target_responded_at' => now(),
                'target_response' => 'Ditolak oleh target user',
            ]);

            // Log activity
            $date = $swap->requesterAssignment?->date?->format('d/m/Y') ?? 'N/A';
            ActivityLogService::logSwapRejected($swap->requester->name, $date);

            // Create notification for requester
            $this->createNotification($swap->user_id, 'swap_rejected', [
                'title' => 'Permintaan Tukar Shift Ditolak',
                'message' => auth()->user()->name.' menolak permintaan tukar shift Anda.',
                'swap_request_id' => $swap->id,
            ]);

            $this->dispatch('toast', message: 'Permintaan tukar shift ditolak', type: 'success');
        }
    }

    public function render()
    {
        $query = SwapRequest::query()
            ->when($this->tab === 'my-requests', fn ($q) => $q->where('user_id', auth()->id()))
            ->when($this->tab === 'received', fn ($q) => $q->where('target_id', auth()->id()))
            ->with([
                'requester:id,name,nim',
                'target:id,name,nim',
                'requesterAssignment',
                'targetAssignment',
            ])
            ->orderBy('created_at', 'desc');

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('requester', function ($subQuery) {
                    $subQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('nim', 'like', '%'.$this->search.'%');
                })
                    ->orWhereHas('target', function ($subQuery) {
                        $subQuery->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('nim', 'like', '%'.$this->search.'%');
                    });
            });
        }

        $swaps = $query->paginate(15);

        // Get statistics for current tab
        $stats = $this->getTabStats();

        return view('livewire.swap.swap-request-list', [
            'swaps' => $swaps,
            'stats' => $stats,
        ])->layout('layouts.app')->title('Tukar Shift');
    }

    private function getTabStats()
    {
        $query = SwapRequest::query();

        if ($this->tab === 'my-requests') {
            $query->where('user_id', auth()->id());
        } elseif ($this->tab === 'received') {
            $query->where('target_id', auth()->id());
        }

        return [
            'total' => $query->count(),
            'pending' => $query->where('status', 'pending')->count(),
            'approved' => $query->whereIn('status', ['target_approved', 'admin_approved'])->count(),
            'rejected' => $query->whereIn('status', ['target_rejected', 'admin_rejected'])->count(),
        ];
    }

    private function createNotification($userId, $type, $data)
    {
        try {
            $user = \App\Models\User::findOrFail($userId);
            \App\Services\NotificationService::createSwapNotification($user, $type, $data);
        } catch (\Exception $e) {
            \Log::error('Failed to create swap notification', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyAdminsForApproval($swapRequest)
    {
        // Get users with admin roles
        $adminUsers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['Super Admin', 'Ketua', 'Wakil Ketua', 'BPH']);
        })->get();

        foreach ($adminUsers as $admin) {
            $this->createNotification($admin->id, 'swap_admin_approval', [
                'title' => 'Persetajuan Tukar Shift Diperlukan',
                'message' => 'Permintaan tukar shift antara '.$swapRequest->requester->name.' dan '.$swapRequest->target->name.' menunggu persetujuan admin.',
                'swap_request_id' => $swapRequest->id,
            ]);
        }
    }
}
