<?php

namespace App\Livewire\Schedule;

use App\Models\Availability;
use App\Models\AvailabilityDetail;
use App\Models\ScheduleAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ScheduleGenerator extends Component
{
    public $startDate;

    public $endDate;

    public $sessionId = 1;

    public $autoAssign = true;

    public $generateStatus = '';

    public $generationStatus = '';

    public $generatedCount = 0;

    public $isGenerating = false;

    public $showPreview = false;

    public $previewAssignments = [];

    public $selectedWeekOffset = 0;

    #[Computed]
    public function scheduleTemplates()
    {
        return \App\Models\Schedule::where('is_active', true)
            ->orderBy('day')
            ->orderBy('session')
            ->get();
    }

    #[Computed]
    public function weekRange()
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        return $start->format('d M Y').' - '.$end->format('d M Y');
    }

    #[Computed]
    public function availableUsers()
    {
        $weekStart = Carbon::parse($this->startDate)->startOfWeek(Carbon::MONDAY);

        return User::where('status', 'active')
            ->whereHas('availabilities', function ($query) use ($weekStart) {
                $query->where('week_start_date', $weekStart->format('Y-m-d'))
                    ->where('status', 'submitted');
            })
            ->get();
    }

    public function mount()
    {
        $this->startDate = now()->startOfWeek()->format('Y-m-d');
        $this->endDate = now()->addWeeks(1)->endOfWeek()->format('Y-m-d');
    }

    public function updatedSelectedWeekOffset($value)
    {
        $start = now()->addWeeks($value)->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

        $this->startDate = $start->format('Y-m-d');
        $this->endDate = $end->format('Y-m-d');
    }

    public function generatePreview()
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $this->previewAssignments = $this->generateScheduleAssignments(true);
        $this->showPreview = true;
    }

    public function generateSchedule()
    {
        $this->validate([
            'startDate' => 'required|date|before_or_equal:endDate',
            'endDate' => 'required|date|after_or_equal:startDate|within_date_range:90',
        ], [
            'startDate.before_or_equal' => 'Tanggal mulai harus sebelum atau sama dengan tanggal selesai.',
            'endDate.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
        ]);

        if ($this->scheduleTemplates->isEmpty()) {
            $this->dispatch('toast', message: 'Tidak ada template jadwal yang aktif. Silakan buat template terlebih dahulu.', type: 'error');

            return;
        }

        $this->isGenerating = true;

        try {
            DB::beginTransaction();

            // Clear existing assignments for the period
            ScheduleAssignment::whereBetween('date', [
                $this->startDate,
                $this->endDate,
            ])->delete();

            // Generate new assignments
            $assignments = $this->generateScheduleAssignments(false);

            if (! empty($assignments)) {
                // Batch insert for performance
                ScheduleAssignment::insert($assignments);

                // Create notifications for assigned users
                $this->createScheduleNotifications($assignments);
            }

            DB::commit();

            $this->generatedCount = count($assignments);
            $this->generationStatus = 'success';
            $this->showPreview = false;
            $this->previewAssignments = [];

            $this->dispatch('toast', message: "Berhasil generate {$this->generatedCount} jadwal!", type: 'success');
            $this->dispatch('schedule-generated');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->generationStatus = 'error';
            $this->dispatch('toast', message: 'Gagal generate jadwal: '.$e->getMessage(), type: 'error');
        } finally {
            $this->isGenerating = false;
        }
    }

    private function generateScheduleAssignments($isPreview = false)
    {
        $assignments = [];
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        $current = $startDate->copy();

        $currentWeekStart = null;
        $userAvailabilities = collect();

        // Track assignments per user for balancing
        $userAssignmentCounts = [];

        while ($current <= $endDate) {
            $dayName = strtolower($current->englishName);

            if (! in_array($dayName, ['monday', 'tuesday', 'wednesday', 'thursday'])) {
                $current->addDay();
                continue;
            }

            $weekStart = $current->copy()->startOfWeek(Carbon::MONDAY);
            $weekStartDateStr = $weekStart->format('Y-m-d');

            if ($weekStartDateStr !== $currentWeekStart) {
                $currentWeekStart = $weekStartDateStr;
                $userAvailabilities = $this->getUserAvailabilities($weekStart);
            }

            // Get templates for this day
            $dayTemplates = $this->scheduleTemplates->where('day', $dayName);

            foreach ($dayTemplates as $template) {
                $user = $this->selectOptimalUser($template, $current, $userAvailabilities, $userAssignmentCounts);

                if ($user) {
                    $assignments[] = [
                        'user_id' => $user->id,
                        'date' => $current->format('Y-m-d'),
                        'day' => $dayName,
                        'schedule_id' => $template->id,
                        'session' => $template->session,
                        'time_start' => $template->time_start,
                        'time_end' => $template->time_end,
                        'status' => 'scheduled',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Track assignment count
                    if (! isset($userAssignmentCounts[$user->id])) {
                        $userAssignmentCounts[$user->id] = 0;
                    }
                    $userAssignmentCounts[$user->id]++;
                }
            }

            $current->addDay();
        }

        return $assignments;
    }

    private function getUserAvailabilities($weekStart)
    {
        return AvailabilityDetail::whereHas('availability', function ($query) use ($weekStart) {
            $query->where('week_start_date', $weekStart->format('Y-m-d'))
                ->where('status', 'submitted');
        })
            ->with('availability.user')
            ->get()
            ->groupBy(function ($detail) {
                return $detail->availability->user_id;
            })
            ->map(function ($userDetails) {
                return [
                    'user_id' => $userDetails->first()->availability->user_id,
                    'user' => $userDetails->first()->availability->user,
                    'available_days' => $userDetails->pluck('day')->unique()->toArray(),
                    'available_sessions' => $userDetails->map(function ($detail) {
                        return [
                            'day' => $detail->day,
                            'session' => $detail->session,
                        ];
                    })->groupBy('day'),
                ];
            });
    }

    private function selectOptimalUser($template, $date, $userAvailabilities, $userAssignmentCounts)
    {
        $dayName = strtolower($date->englishName);
        $availableUsers = [];

        foreach ($userAvailabilities as $userId => $availability) {
            // Check if user is available on this day
            if (! in_array($dayName, $availability['available_days'])) {
                continue;
            }

            // Check if user is available during the template session
            $daySessions = $availability['available_sessions']->get($dayName, collect());
            $isSessionAvailable = $daySessions->contains('session', $template->session);

            if (! $isSessionAvailable) {
                continue;
            }

            // Check if user already has assignment for this date and session
            $hasConflict = ScheduleAssignment::where('user_id', $userId)
                ->where('date', $date->format('Y-m-d'))
                ->where('session', $template->session)
                ->exists();

            if ($hasConflict) {
                continue;
            }

            $availableUsers[] = [
                'user' => $availability['user'],
                'assignment_count' => $userAssignmentCounts[$userId] ?? 0,
            ];
        }

        if (empty($availableUsers)) {
            return null;
        }

        // Sort by assignment count (fewer assignments first)
        usort($availableUsers, function ($a, $b) {
            return $a['assignment_count'] - $b['assignment_count'];
        });

        return $availableUsers[0]['user'];
    }

    private function createScheduleNotifications($assignments)
    {
        $userAssignments = collect($assignments)->groupBy('user_id');

        foreach ($userAssignments as $userId => $userSchedules) {
            $user = User::find($userId);
            if ($user) {
                // Create notification for user
                // This would integrate with your notification system
                // For now, we'll just log it
                \Log::info("Schedule notification created for user {$user->name}", [
                    'schedules_count' => count($userSchedules),
                    'week_start' => $this->startDate,
                    'week_end' => $this->endDate,
                ]);
            }
        }
    }

    public function clearGeneratedSchedules()
    {
        try {
            $deletedCount = ScheduleAssignment::whereBetween('date', [
                $this->startDate,
                $this->endDate,
            ])->delete();

            $this->dispatch('toast', message: "Berhasil menghapus {$deletedCount} jadwal.", type: 'success');
            $this->generatedCount = 0;
            $this->generationStatus = '';
            $this->showPreview = false;
            $this->previewAssignments = [];

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal menghapus jadwal: '.$e->getMessage(), type: 'error');
        }
    }

    public function getDayName($day)
    {
        $days = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
        ];

        return $days[$day] ?? $day;
    }

    public function getPreviewStats()
    {
        if (empty($this->previewAssignments)) {
            return [
                'total_assignments' => 0,
                'unique_users' => 0,
                'assignments_per_user' => 0,
                'coverage_rate' => 0,
            ];
        }

        $assignments = collect($this->previewAssignments);
        $totalPossible = $this->scheduleTemplates->count() * 7; // templates * days
        $uniqueUsers = $assignments->pluck('user_id')->unique()->count();

        return [
            'total_assignments' => $assignments->count(),
            'unique_users' => $uniqueUsers,
            'assignments_per_user' => $uniqueUsers > 0 ? round($assignments->count() / $uniqueUsers, 1) : 0,
            'coverage_rate' => $totalPossible > 0 ? round(($assignments->count() / $totalPossible) * 100, 1) : 0,
        ];
    }

    public function render()
    {
        return view('livewire.schedule.schedule-generator', [
            'weekRange' => $this->weekRange,
            'previewStats' => $this->getPreviewStats(),
            'isGenerating' => $this->isGenerating,
        ])->layout('layouts.app')->title('Generator Jadwal');
    }
}
