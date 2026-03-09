<?php

namespace App\Livewire\Schedule;

use App\Models\Availability;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Manajemen Jadwal')]
#[Layout('layouts.app')]
class ScheduleList extends Component
{
    use WithPagination;

    // Member availability modal
    public bool $showMemberModal = false;

    public ?array $selectedMemberAvailability = null;

    public ?string $selectedMemberName = null;

    /**
     * Listen for schedule-updated event to refresh data
     */
    #[On('schedule-updated')]
    public function onScheduleUpdated(): void
    {
        // Clear computed property cache
        unset($this->membersWithAvailability);
        unset($this->availabilityStats);

        // Clear the cache for members availability (using next week's date to match current default)
        $weekStart = now()->addWeek()->startOfWeek(Carbon::MONDAY);
        Cache::forget("members_availability_{$weekStart->format('Y-m-d')}");
    }

    #[Computed]
    public function currentWeekStart(): Carbon
    {
        return now()->addWeek()->startOfWeek(Carbon::MONDAY);
    }

    #[Computed]
    public function currentWeekEnd(): Carbon
    {
        return now()->addWeek()->endOfWeek(Carbon::SUNDAY);
    }

    #[Computed]
    public function membersWithAvailability(): array
    {
        $weekStart = $this->currentWeekStart;

        return Cache::remember(
            "members_availability_{$weekStart->format('Y-m-d')}",
            60, // 1 minute cache
            function () use ($weekStart) {
                // Get all active users
                $members = User::query()
                    ->where('status', 'active')
                    ->select('id', 'name', 'nim')
                    ->orderBy('name')
                    ->get();

                // Get submitted availabilities for current week by week_start_date
                $availabilities = Availability::query()
                    ->where('status', 'submitted')
                    ->where('week_start_date', $weekStart->format('Y-m-d'))
                    ->with('details:id,availability_id,day,session,is_available')
                    ->get()
                    ->keyBy('user_id');

                return $members->map(function ($member) use ($availabilities) {
                    $availability = $availabilities->get($member->id);

                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'nim' => $member->nim,
                        'has_submitted' => $availability !== null,
                        'total_sessions' => $availability?->total_available_sessions ?? 0,
                        'submitted_at' => $availability?->submitted_at?->format('d M H:i'),
                    ];
                })->toArray();
            }
        );
    }

    #[Computed]
    public function availabilityStats(): array
    {
        $members = $this->membersWithAvailability;
        $total = count($members);
        $submitted = collect($members)->where('has_submitted', true)->count();

        return [
            'total' => $total,
            'submitted' => $submitted,
            'pending' => $total - $submitted,
            'percentage' => $total > 0 ? round(($submitted / $total) * 100) : 0,
        ];
    }

    public function viewMemberAvailability(int $userId): void
    {
        $weekStart = $this->currentWeekStart;

        $user = User::find($userId);
        $availability = Availability::query()
            ->where('user_id', $userId)
            ->where('status', 'submitted')
            ->where('week_start_date', $weekStart->format('Y-m-d'))
            ->with('details')
            ->first();

        if (! $availability) {
            $this->dispatch('toast', message: 'Anggota belum mengisi ketersediaan.', type: 'warning');

            return;
        }

        $this->selectedMemberName = $user->name;
        $this->selectedMemberAvailability = [];

        foreach ($availability->details as $detail) {
            $this->selectedMemberAvailability[$detail->day][$detail->session] = $detail->is_available;
        }

        $this->showMemberModal = true;
    }

    public function closeMemberModal(): void
    {
        $this->showMemberModal = false;
        $this->selectedMemberAvailability = null;
        $this->selectedMemberName = null;
    }

    public function publish(int $scheduleId): void
    {
        try {
            $schedule = Schedule::findOrFail($scheduleId);

            if ($schedule->status === 'published') {
                $this->dispatch('toast', message: 'Jadwal sudah dipublish.', type: 'warning');

                return;
            }

            $schedule->update(['status' => 'published']);

            // Log activity
            $weekDate = Carbon::parse($schedule->week_start_date)->locale('id')->isoFormat('D MMMM YYYY');
            ActivityLogService::logSchedulePublished($weekDate);

            // Dispatch global event for other components
            $this->dispatch('schedule-updated');

            $this->dispatch('toast', message: 'Jadwal berhasil dipublish!', type: 'success');

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal publish jadwal: '.$e->getMessage(), type: 'error');
        }
    }

    public function delete(int $scheduleId): void
    {
        try {
            DB::beginTransaction();

            $schedule = Schedule::findOrFail($scheduleId);
            $weekDate = Carbon::parse($schedule->week_start_date)->locale('id')->isoFormat('D MMMM YYYY');

            // Delete assignments first
            $schedule->assignments()->delete();

            // Delete schedule
            $schedule->delete();

            // Log activity
            ActivityLogService::logScheduleDeleted('Jadwal', $weekDate);

            DB::commit();

            // Dispatch global event for other components
            $this->dispatch('schedule-updated');

            $this->dispatch('toast', message: 'Jadwal berhasil dihapus!', type: 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Gagal menghapus jadwal: '.$e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $schedules = Schedule::query()
            ->withCount('assignments')
            ->latest('week_start_date')
            ->paginate(10);

        return view('livewire.schedule.schedule-list', compact('schedules'));
    }
}
