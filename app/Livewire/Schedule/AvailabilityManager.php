<?php

namespace App\Livewire\Schedule;

use App\Models\Availability;
use App\Models\AvailabilityDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AvailabilityManager extends Component
{
    public int $weekOffset = 1;

    public array $grid = [];

    private const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday'];

    private const DAY_LABELS = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
    ];

    private const SESSIONS = [
        '1' => '07:30 - 10:00',
        '2' => '10:20 - 12:50',
        '3' => '13:30 - 16:00',
    ];

    public function mount(): void
    {
        $this->loadWeekData();
    }

    #[Computed]
    public function weekStart(): Carbon
    {
        return now()->startOfWeek(Carbon::MONDAY)->addWeeks($this->weekOffset);
    }

    #[Computed]
    public function weekEnd(): Carbon
    {
        return $this->weekStart->copy()->addDays(3)->endOfDay();
    }

    #[Computed]
    public function weekLabel(): string
    {
        return $this->weekStart->format('d M').' - '.$this->weekEnd->format('d M Y');
    }

    #[Computed]
    public function days(): array
    {
        return self::DAY_LABELS;
    }

    #[Computed]
    public function sessions(): array
    {
        return self::SESSIONS;
    }

    #[Computed]
    public function totalSelected(): int
    {
        $count = 0;
        foreach ($this->grid as $sessions) {
            foreach ($sessions as $val) {
                if ($val) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Check if current week is already submitted
     */
    public function getIsSubmittedProperty(): bool
    {
        return Availability::query()
            ->where('user_id', auth()->id())
            ->where('status', 'submitted')
            ->where('week_start_date', $this->weekStart->format('Y-m-d'))
            ->exists();
    }

    /**
     * Check if user can edit current week
     */
    public function getCanEditProperty(): bool
    {
        return $this->weekOffset >= 0 && ! $this->isSubmitted;
    }

    public function updatedWeekOffset(): void
    {
        $this->loadWeekData();
    }

    public function toggle(string $day, string $session): void
    {
        if (! $this->canEdit) {
            return;
        }
        $this->grid[$day][$session] = ! ($this->grid[$day][$session] ?? false);
    }

    public function toggleDay(string $day): void
    {
        if (! $this->canEdit) {
            return;
        }

        $allSelected = collect(array_keys(self::SESSIONS))
            ->every(fn ($s) => $this->grid[$day][$s] ?? false);

        foreach (array_keys(self::SESSIONS) as $s) {
            $this->grid[$day][$s] = ! $allSelected;
        }
    }

    public function submit(): void
    {
        // Double check - fresh query
        $alreadySubmitted = Availability::query()
            ->where('user_id', auth()->id())
            ->where('status', 'submitted')
            ->where('week_start_date', $this->weekStart->format('Y-m-d'))
            ->exists();

        if ($alreadySubmitted) {
            $this->dispatch('toast', message: 'Ketersediaan sudah dikirim untuk minggu ini.', type: 'error');

            return;
        }

        if ($this->totalSelected === 0) {
            $this->dispatch('toast', message: 'Pilih minimal satu sesi.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () {
                // Delete any existing for THIS specific week
                $existingIds = Availability::query()
                    ->where('user_id', auth()->id())
                    ->where('week_start_date', $this->weekStart->format('Y-m-d'))
                    ->pluck('id');

                if ($existingIds->isNotEmpty()) {
                    AvailabilityDetail::whereIn('availability_id', $existingIds)->delete();
                    Availability::whereIn('id', $existingIds)->delete();
                }

                // Create new submitted availability with week_start_date
                $availability = Availability::create([
                    'user_id' => auth()->id(),
                    'schedule_id' => null,
                    'week_start_date' => $this->weekStart->format('Y-m-d'),
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'total_available_sessions' => $this->totalSelected,
                ]);

                // Bulk insert details
                $details = [];
                $now = now();
                foreach ($this->grid as $day => $sessions) {
                    foreach ($sessions as $session => $isAvailable) {
                        $details[] = [
                            'availability_id' => $availability->id,
                            'day' => $day,
                            'session' => $session,
                            'is_available' => (bool) $isAvailable,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
                AvailabilityDetail::insert($details);

                // Clear caches
                Cache::forget("members_availability_{$this->weekStart->format('Y-m-d')}");
            });

            $this->dispatch('toast', message: 'Ketersediaan berhasil dikirim!', type: 'success');

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal menyimpan: '.$e->getMessage(), type: 'error');
        }
    }

    private function loadWeekData(): void
    {
        // Initialize empty grid
        $this->grid = [];
        foreach (self::DAYS as $day) {
            foreach (array_keys(self::SESSIONS) as $session) {
                $this->grid[$day][$session] = false;
            }
        }

        // Load existing data for THIS specific week
        $existing = Availability::query()
            ->where('user_id', auth()->id())
            ->where('week_start_date', $this->weekStart->format('Y-m-d'))
            ->with('details:id,availability_id,day,session,is_available')
            ->first();

        if ($existing) {
            foreach ($existing->details as $detail) {
                if (isset($this->grid[$detail->day])) {
                    $this->grid[$detail->day][$detail->session] = (bool) $detail->is_available;
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.schedule.availability-manager')
            ->layout('layouts.app')
            ->title('Ketersediaan Jadwal');
    }
}
