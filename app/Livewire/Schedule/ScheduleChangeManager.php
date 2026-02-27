<?php

namespace App\Livewire\Schedule;

use App\Models\ScheduleAssignment;
use App\Models\ScheduleChangeRequest;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Pengajuan Perubahan Jadwal')]
#[Layout('layouts.app')]
#[Lazy]
class ScheduleChangeManager extends Component
{
    use WithPagination;

    #[Url(as: 'tab')]
    public string $activeTab = 'my-requests';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    // Form state
    public bool $showForm = false;

    public ?int $selectedAssignment = null;

    public string $changeType = 'reschedule'; // reschedule atau cancel

    public string $requestedDate = '';

    public int $requestedSession = 0;

    public string $reason = '';

    // Modal state
    public ?int $viewingId = null;

    public ?int $reviewingId = null;

    public string $reviewAction = '';

    public string $reviewNotes = '';

    public function mount(): void
    {
        $this->requestedDate = now()->addDay()->format('Y-m-d');
    }

    public function placeholder(): string
    {
        return '<div class="animate-pulse space-y-4"><div class="h-8 bg-gray-200 rounded w-1/4"></div><div class="h-64 bg-gray-200 rounded"></div></div>';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    // === FORM ===
    public function openForm(): void
    {
        $this->reset(['selectedAssignment', 'requestedSession', 'reason']);
        $this->changeType = 'reschedule';
        $this->requestedDate = now()->addDay()->format('Y-m-d');
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetValidation();
    }

    public function submitForm(): void
    {
        $rules = [
            'selectedAssignment' => 'required|exists:schedule_assignments,id',
            'changeType' => 'required|in:reschedule,cancel',
            'reason' => 'required|string|min:10|max:500',
        ];

        // Jika reschedule, wajib pilih tanggal & sesi baru
        if ($this->changeType === 'reschedule') {
            $rules['requestedDate'] = 'required|date|after_or_equal:today';
            $rules['requestedSession'] = 'required|in:1,2,3';
        }

        $this->validate($rules, [
            'selectedAssignment.required' => 'Pilih jadwal yang ingin diubah',
            'requestedDate.required' => 'Pilih tanggal tujuan',
            'requestedSession.required' => 'Pilih sesi tujuan',
            'reason.min' => 'Alasan minimal 10 karakter',
        ]);

        $assignment = ScheduleAssignment::find($this->selectedAssignment);

        // Validasi: minimal 24 jam sebelum jadwal
        $deadline = $assignment->date->copy()->setTimeFromTimeString($assignment->time_start)->subHours(24);
        if (now()->gt($deadline)) {
            $this->addError('selectedAssignment', 'Pengajuan minimal 24 jam sebelum jadwal');

            return;
        }

        // Validasi: tidak ada pending request untuk jadwal yang sama
        $exists = ScheduleChangeRequest::where('user_id', Auth::id())
            ->where('original_assignment_id', $this->selectedAssignment)
            ->where('status', 'pending')
            ->exists();
        if ($exists) {
            $this->addError('selectedAssignment', 'Sudah ada pengajuan untuk jadwal ini');

            return;
        }

        // Jika reschedule, cek tidak bentrok dengan jadwal lain
        if ($this->changeType === 'reschedule') {
            $conflict = ScheduleAssignment::where('user_id', Auth::id())
                ->where('date', $this->requestedDate)
                ->where('session', $this->requestedSession)
                ->exists();
            if ($conflict) {
                $this->addError('requestedDate', 'Anda sudah punya jadwal di waktu tersebut');

                return;
            }
        }

        ScheduleChangeRequest::create([
            'user_id' => Auth::id(),
            'original_assignment_id' => $this->selectedAssignment,
            'change_type' => $this->changeType,
            'requested_date' => $this->changeType === 'reschedule' ? $this->requestedDate : null,
            'requested_session' => $this->changeType === 'reschedule' ? $this->requestedSession : null,
            'reason' => $this->reason,
            'status' => 'pending',
        ]);

        $this->closeForm();
        $this->clearCache();
        $this->dispatch('toast', message: 'Pengajuan berhasil dikirim', type: 'success');
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
        ScheduleChangeRequest::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $this->viewingId = null;
        $this->clearCache();
        $this->dispatch('toast', message: 'Pengajuan dibatalkan', type: 'success');
    }

    // === ADMIN REVIEW ===
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
        $request = ScheduleChangeRequest::with('originalAssignment')->find($this->reviewingId);
        if (! $request || $request->status !== 'pending') {
            return;
        }

        DB::beginTransaction();
        try {
            $newStatus = $this->reviewAction === 'approved' ? 'approved' : 'rejected';

            $request->update([
                'status' => $newStatus,
                'admin_response' => $this->reviewNotes,
                'admin_responded_by' => Auth::id(),
                'admin_responded_at' => now(),
                'completed_at' => $newStatus === 'approved' ? now() : null,
            ]);

            // Jika disetujui, proses perubahan jadwal
            if ($newStatus === 'approved') {
                $assignment = $request->originalAssignment;

                if ($request->change_type === 'cancel') {
                    // Batalkan jadwal - hapus assignment
                    $assignment->delete();
                } else {
                    // Pindah jadwal - update assignment
                    $assignment->update([
                        'date' => $request->requested_date,
                        'session' => $request->requested_session,
                        'time_start' => $this->getSessionTime($request->requested_session, 'start'),
                        'time_end' => $this->getSessionTime($request->requested_session, 'end'),
                        'edited_by' => Auth::id(),
                        'edited_at' => now(),
                        'edit_reason' => 'Pengajuan perubahan jadwal disetujui',
                    ]);
                }
            }

            DB::commit();

            // Log activity
            $requestUserName = $request->user->name ?? 'Unknown';
            $originalDate = $request->originalAssignment?->date?->format('d/m/Y') ?? 'N/A';
            if ($newStatus === 'approved') {
                ActivityLogService::log("Menyetujui pengajuan perubahan jadwal {$requestUserName} dari tanggal {$originalDate}");
            } else {
                ActivityLogService::log("Menolak pengajuan perubahan jadwal {$requestUserName} dari tanggal {$originalDate}");
            }

            $this->closeReview();
            $this->viewingId = null;
            $this->clearCache();

            $msg = $this->reviewAction === 'approved' ? 'Pengajuan disetujui dan diproses' : 'Pengajuan ditolak';
            $this->dispatch('toast', message: $msg, type: 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Gagal memproses: '.$e->getMessage(), type: 'error');
        }
    }

    private function getSessionTime(int $session, string $type): string
    {
        $times = [
            1 => ['start' => '07:30', 'end' => '10:00'],
            2 => ['start' => '10:20', 'end' => '12:50'],
            3 => ['start' => '13:30', 'end' => '16:00'],
        ];

        return $times[$session][$type] ?? '00:00';
    }

    private function clearCache(): void
    {
        $userId = Auth::id();
        Cache::forget("schedule_change_stats_{$userId}_my-requests");
        Cache::forget("schedule_change_stats_{$userId}_admin");
    }

    public function render()
    {
        $userId = Auth::id();
        $isAdmin = Auth::user()->hasAnyRole(['Super Admin', 'Ketua', 'Wakil Ketua', 'BPH']);

        // Stats
        $stats = Cache::remember("schedule_change_stats_{$userId}_{$this->activeTab}", 60, function () use ($userId) {
            if ($this->activeTab === 'my-requests') {
                return [
                    'pending' => ScheduleChangeRequest::where('user_id', $userId)->where('status', 'pending')->count(),
                    'approved' => ScheduleChangeRequest::where('user_id', $userId)->where('status', 'approved')->count(),
                    'rejected' => ScheduleChangeRequest::where('user_id', $userId)->whereIn('status', ['rejected', 'cancelled'])->count(),
                ];
            }

            return [
                'pending' => ScheduleChangeRequest::where('status', 'pending')->count(),
                'approved' => ScheduleChangeRequest::where('status', 'approved')->count(),
                'rejected' => ScheduleChangeRequest::where('status', 'rejected')->count(),
            ];
        });

        // Query
        $query = $this->activeTab === 'my-requests'
            ? ScheduleChangeRequest::where('user_id', $userId)
            : ScheduleChangeRequest::with('user:id,name,nim');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $requests = $query->with([
            'originalAssignment:id,date,session,time_start,time_end',
            'adminResponder:id,name',
        ])->latest()->paginate(10);

        // My assignments for form
        $myAssignments = $this->showForm
            ? ScheduleAssignment::where('user_id', $userId)
                ->where('date', '>=', now()->format('Y-m-d'))
                ->orderBy('date')
                ->get(['id', 'date', 'session', 'time_start', 'time_end'])
            : collect();

        // Viewing request
        $viewingRequest = $this->viewingId
            ? ScheduleChangeRequest::with([
                'user:id,name,nim',
                'originalAssignment:id,date,session,time_start,time_end',
                'adminResponder:id,name',
            ])->find($this->viewingId)
            : null;

        return view('livewire.schedule.schedule-change-manager', [
            'requests' => $requests,
            'stats' => $stats,
            'isAdmin' => $isAdmin,
            'myAssignments' => $myAssignments,
            'viewingRequest' => $viewingRequest,
        ]);
    }
}
