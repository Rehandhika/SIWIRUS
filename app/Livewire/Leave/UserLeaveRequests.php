<?php

namespace App\Livewire\Leave;

use App\Models\LeaveRequest;
use App\Services\LeaveService;
use App\Services\Storage\FileStorageServiceInterface;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Riwayat Cuti/Izin Saya')]
class UserLeaveRequests extends Component
{
    use WithPagination;

    public $statusFilter = 'all';

    protected FileStorageServiceInterface $fileStorageService;

    public function boot(FileStorageServiceInterface $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;
    }

    public function getAttachmentUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        try {
            return $this->fileStorageService->getUrl($path);
        } catch (\Exception $e) {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
            return null;
        }
    }

    public function cancel($id)
    {
        $request = LeaveRequest::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        try {
            app(LeaveService::class)->cancel($request);
            $this->dispatch('toast', message: 'Permohonan berhasil dibatalkan', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    #[On('leave-request-created')]
    public function refreshList()
    {
        $this->resetPage();
    }

    public function render()
    {
        $requests = LeaveRequest::where('user_id', auth()->id())
            ->with('reviewer')
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        $stats = [
            'total' => LeaveRequest::where('user_id', auth()->id())->count(),
            'pending' => LeaveRequest::where('user_id', auth()->id())->pending()->count(),
            'approved' => LeaveRequest::where('user_id', auth()->id())->approved()->count(),
            'rejected' => LeaveRequest::where('user_id', auth()->id())->rejected()->count(),
        ];

        return view('livewire.leave.user-leave-requests', [
            'requests' => $requests,
            'stats' => $stats,
        ])->layout('layouts.app');
    }
}
