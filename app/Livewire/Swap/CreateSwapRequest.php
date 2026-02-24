<?php

namespace App\Livewire\Swap;

use App\Models\ScheduleAssignment;
use App\Models\SwapRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CreateSwapRequest extends Component
{
    use WithPagination;

    public $myAssignments = [];

    public $selectedAssignment = null;

    public $targetDate;

    public $targetSession;

    public $availableTargets = [];

    public $selectedTarget = null;

    public $reason;

    public $showConfirmation = false;

    public $searchTarget = '';

    public $sessionOptions = [];

    protected $rules = [
        'selectedAssignment' => 'required|integer|exists:schedule_assignments,id',
        'targetDate' => 'required|date|after_or_equal:today',
        'targetSession' => 'required|integer|min:1|max:3',
        'selectedTarget' => 'required|integer|exists:users,id',
        'reason' => 'required|string|min:10|max:500',
    ];

    protected $messages = [
        'selectedAssignment.required' => 'Pilih shift Anda yang ingin ditukar.',
        'targetDate.after_or_equal' => 'Tanggal target harus hari ini atau setelahnya.',
        'targetSession.required' => 'Pilih sesi shift target.',
        'selectedTarget.required' => 'Pilih pengguna target untuk tukar shift.',
        'reason.min' => 'Alasan harus minimal 10 karakter.',
        'reason.max' => 'Alasan maksimal 500 karakter.',
    ];

    public function mount()
    {
        $this->sessionOptions = [
            1 => 'Sesi 1 (Pagi)',
            2 => 'Sesi 2 (Siang)',
            3 => 'Sesi 3 (Sore)',
        ];
        $this->loadMyAssignments();
    }

    public function loadMyAssignments()
    {
        $this->myAssignments = ScheduleAssignment::where('user_id', auth()->id())
            ->where('date', '>=', today())
            ->with(['schedule'])
            ->orderBy('date')
            ->orderBy('time_start')
            ->get(['id', 'date', 'session', 'time_start', 'time_end', 'schedule_id']);
    }

    public function updatedTargetDate()
    {
        $this->targetSession = null;
        $this->availableTargets = [];
        $this->selectedTarget = null;

        if ($this->targetDate) {
            $this->loadAvailableTargets();
        }
    }

    public function updatedTargetSession()
    {
        $this->availableTargets = [];
        $this->selectedTarget = null;

        if ($this->targetDate && $this->targetSession) {
            $this->loadAvailableTargets();
        }
    }

    public function loadAvailableTargets()
    {
        if (! $this->targetDate || ! $this->targetSession) {
            return;
        }

        // Get users assigned to the target date and session
        $this->availableTargets = ScheduleAssignment::where('date', $this->targetDate)
            ->where('session', $this->targetSession)
            ->where('user_id', '!=', auth()->id())
            ->with(['user:id,name,nim,status'])
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->user->id,
                    'name' => $assignment->user->name,
                    'nim' => $assignment->user->nim,
                    'status' => $assignment->user->status,
                    'assignment_id' => $assignment->id,
                ];
            })
            ->filter(function ($user) {
                return $user['status'] === 'active';
            })
            ->values();
    }

    public function validateSwapRequest()
    {
        $this->validate();

        // Additional business logic validation
        $this->validateBusinessRules();

        $this->showConfirmation = true;
    }

    private function validateBusinessRules()
    {
        // Check if requester already has a pending swap for the same assignment
        $existingRequest = SwapRequest::where('user_id', auth()->id())
            ->where('original_assignment_id', $this->selectedAssignment)
            ->whereIn('status', ['pending', 'target_approved'])
            ->exists();

        if ($existingRequest) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'selectedAssignment' => 'Anda sudah memiliki permintaan tukar shift untuk jadwal ini.',
            ]);
        }

        // Check if target user already has a pending swap for the same assignment
        $targetAssignment = ScheduleAssignment::where('date', $this->targetDate)
            ->where('session', $this->targetSession)
            ->where('user_id', $this->selectedTarget)
            ->first();

        if ($targetAssignment) {
            $existingTargetRequest = SwapRequest::where('target_id', $this->selectedTarget)
                ->where('target_assignment_id', $targetAssignment->id)
                ->whereIn('status', ['pending', 'target_approved'])
                ->exists();

            if ($existingTargetRequest) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'selectedTarget' => 'Target pengguna sudah memiliki permintaan tukar shift untuk jadwal ini.',
                ]);
            }
        }

        // Check if swap deadline has passed (e.g., 24 hours before shift)
        $requesterAssignment = ScheduleAssignment::find($this->selectedAssignment);
        if ($requesterAssignment) {
            $deadline = $requesterAssignment->date->copy()
                ->setTimeFromTimeString($requesterAssignment->time_start)
                ->subHours(24);

            if (now()->greaterThan($deadline)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'selectedAssignment' => 'Permintaan tukar shift harus diajukan minimal 24 jam sebelum shift dimulai.',
                ]);
            }
        }
    }

    public function createSwapRequest()
    {
        $this->validate();
        $this->validateBusinessRules();

        try {
            DB::beginTransaction();

            $targetAssignment = ScheduleAssignment::where('date', $this->targetDate)
                ->where('session', $this->targetSession)
                ->where('user_id', $this->selectedTarget)
                ->first();

            if (! $targetAssignment) {
                throw new \Exception('Target assignment tidak ditemukan.');
            }

            $swapRequest = SwapRequest::create([
                'user_id' => auth()->id(),
                'target_id' => $this->selectedTarget,
                'original_assignment_id' => $this->selectedAssignment,
                'target_assignment_id' => $targetAssignment->id,
                'change_type' => 'swap',
                'reason' => $this->reason,
                'status' => 'pending',
            ]);

            // Create notification for target user
            $this->createNotification($this->selectedTarget, 'swap_request', [
                'title' => 'Permintaan Tukar Shift',
                'message' => auth()->user()->name.' ingin menukar shift dengan Anda.',
                'swap_request_id' => $swapRequest->id,
            ]);

            DB::commit();

            // Log activity
            $requesterAssignment = ScheduleAssignment::find($this->selectedAssignment);
            ActivityLogService::logSwapCreated(
                $requesterAssignment->date->format('d M Y'),
                Carbon::parse($this->targetDate)->format('d M Y')
            );

            $this->dispatch('toast', message: 'Permintaan tukar shift berhasil dikirim.', type: 'success');
            $this->resetForm();
            $this->dispatch('swap-request-created');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Gagal membuat permintaan: '.$e->getMessage(), type: 'error');
        }
    }

    private function createNotification($userId, $type, $data)
    {
        // Implementation depends on your notification system
        // This is a placeholder for notification creation
    }

    public function resetForm()
    {
        $this->reset([
            'selectedAssignment',
            'targetDate',
            'targetSession',
            'availableTargets',
            'selectedTarget',
            'reason',
            'showConfirmation',
            'searchTarget',
        ]);
    }

    public function render()
    {
        return view('livewire.swap.create-swap-request', [
            'myAssignments' => $this->myAssignments,
            'availableTargets' => $this->availableTargets,
        ])->layout('layouts.app')->title('Buat Permintaan Tukar Shift');
    }
}
