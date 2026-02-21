<?php

namespace App\Livewire\Leave;

use App\Models\LeaveRequest;
use App\Services\LeaveService;
use App\Services\Storage\FileStorageServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Izin & Cuti')]
#[Layout('layouts.app')]
#[Lazy]
class LeaveManager extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'tab')]
    public string $activeTab = 'my-requests';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    // Form state - simple properties
    public bool $showForm = false;

    public string $leave_type = 'permission';

    public string $start_date = '';

    public string $end_date = '';

    public string $reason = '';

    public $attachment;

    // Modal state
    public ?int $viewingId = null;

    public ?int $reviewingId = null;

    public string $reviewAction = '';

    public string $reviewNotes = '';

    protected FileStorageServiceInterface $fileStorageService;
    protected LeaveService $leaveService;

    public function boot(FileStorageServiceInterface $fileStorageService, LeaveService $leaveService)
    {
        $this->fileStorageService = $fileStorageService;
        $this->leaveService = $leaveService;
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

    public function mount(): void
    {
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    // Placeholder for lazy loading
    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="animate-pulse space-y-4">
            <div class="h-8 bg-gray-200 rounded w-1/4"></div>
            <div class="h-32 bg-gray-200 rounded"></div>
            <div class="h-64 bg-gray-200 rounded"></div>
        </div>
        HTML;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function openForm(): void
    {
        $this->reset(['leave_type', 'reason', 'attachment']);
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function submitForm(): void
    {
        $this->validate([
            'leave_type' => 'required|in:sick,permission,emergency,other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:10|max:500',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        try {
            $path = null;
            if ($this->attachment) {
                $result = $this->fileStorageService->upload($this->attachment, 'leave', ['user_id' => Auth::id()]);
                $path = $result->path;
            }

            $this->leaveService->submitRequest(
                Auth::id(),
                $this->leave_type,
                $start,
                $end,
                $this->reason,
                $path
            );

            $this->closeForm();
            $this->dispatch('toast', message: 'Pengajuan berhasil dikirim', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal mengirim pengajuan: ' . $e->getMessage(), type: 'error');
        }
    }

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
        $leave = LeaveRequest::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($leave) {
            try {
                $this->leaveService->cancel($leave);
                $this->viewingId = null;
                $this->dispatch('toast', message: 'Pengajuan dibatalkan', type: 'success');
            } catch (\Exception $e) {
                $this->dispatch('toast', message: 'Gagal membatalkan: ' . $e->getMessage(), type: 'error');
            }
        }
    }

    public function openReview(int $id, string $action): void
    {
        $this->reviewingId = $id;
        $this->reviewAction = $action;
        $this->reviewNotes = '';
    }

    public function closeReview(): void
    {
        $this->reviewingId = null;
        $this->reviewAction = '';
        $this->reviewNotes = '';
    }

    public function submitReview(): void
    {
        $leave = LeaveRequest::find($this->reviewingId);
        
        if (!$leave) {
            $this->closeReview();
            return;
        }

        try {
            if ($this->reviewAction === 'approved') {
                $this->leaveService->approve($leave, Auth::id(), $this->reviewNotes);
                $msg = 'Pengajuan disetujui';
            } else {
                $this->leaveService->reject($leave, Auth::id(), $this->reviewNotes);
                $msg = 'Pengajuan ditolak';
            }

            $this->closeReview();
            $this->viewingId = null;
            $this->dispatch('toast', message: $msg, type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal memproses review: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $userId = Auth::id();
        $isAdmin = Auth::user()->hasAnyRole(['Super Admin', 'Ketua', 'Wakil Ketua', 'BPH']);

        // Simple stats - cached
        $stats = Cache::remember("leave_stats_{$userId}_{$this->activeTab}", 60, function () use ($userId) {
            if ($this->activeTab === 'my-requests') {
                return [
                    'pending' => LeaveRequest::where('user_id', $userId)->where('status', 'pending')->count(),
                    'approved' => LeaveRequest::where('user_id', $userId)->where('status', 'approved')->count(),
                    'rejected' => LeaveRequest::where('user_id', $userId)->where('status', 'rejected')->count(),
                ];
            }

            return [
                'pending' => LeaveRequest::where('status', 'pending')->count(),
                'approved' => LeaveRequest::where('status', 'approved')->count(),
                'rejected' => LeaveRequest::where('status', 'rejected')->count(),
            ];
        });

        // Query with eager loading
        $query = $this->activeTab === 'my-requests'
            ? LeaveRequest::where('user_id', $userId)
            : LeaveRequest::with('user:id,name,nim');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $requests = $query->latest()->paginate(10);

        // Get viewing request if needed
        $viewingRequest = $this->viewingId
            ? LeaveRequest::with(['user:id,name,nim', 'reviewer:id,name'])->find($this->viewingId)
            : null;

        return view('livewire.leave.leave-manager', [
            'requests' => $requests,
            'stats' => $stats,
            'isAdmin' => $isAdmin,
            'viewingRequest' => $viewingRequest,
        ]);
    }
}
