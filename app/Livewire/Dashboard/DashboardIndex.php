<?php

namespace App\Livewire\Dashboard;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\Penalty;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ScheduleAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class DashboardIndex extends Component
{
    /**
     * Listen for schedule-updated event to refresh dashboard data
     */
    #[On('schedule-updated')]
    public function onScheduleUpdated(): void
    {
        // Refresh component
        $this->dispatch('$refresh');
    }

    /**
     * Listen for attendance-updated event
     */
    #[On('attendance-updated')]
    public function onAttendanceUpdated(): void
    {
        $this->dispatch('$refresh');
    }

    #[Computed]
    public function isAdmin(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return method_exists($user, 'hasAnyRole')
            ? $user->hasAnyRole(['Super Admin', 'Ketua', 'Wakil Ketua', 'BPH'])
            : false;
    }

    #[Computed]
    public function user(): User
    {
        return auth()->user();
    }

    // ==========================================
    // ADMIN DASHBOARD DATA
    // ==========================================

    #[Computed]
    public function adminStats(): array
    {
        if (! $this->isAdmin) {
            return [];
        }

        $today = now()->format('Y-m-d');
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        // Revenue Today
        $revenueToday = Sale::whereDate('date', $today)->sum('total_amount');
        
        // Revenue This Month
        $revenueMonth = Sale::whereBetween('date', [$startOfMonth, $endOfMonth])->sum('total_amount');

        // Attendance Percentage Today
        $totalScheduledToday = ScheduleAssignment::where('date', $today)->count();
        $totalPresentToday = Attendance::whereDate('date', $today)->whereIn('status', ['present', 'late'])->count();
        $attendancePercentage = $totalScheduledToday > 0 ? ($totalPresentToday / $totalScheduledToday) * 100 : 0;

        // Low Stock
        $lowStockCount = Product::whereColumn('stock', '<=', 'min_stock')->count();

        return [
            'revenue_today' => $revenueToday,
            'revenue_month' => $revenueMonth,
            'attendance_percentage' => round($attendancePercentage, 1),
            'low_stock_count' => $lowStockCount,
            'transaction_count' => Sale::whereDate('date', $today)->count(),
        ];
    }

    #[Computed]
    public function salesChartData(): array
    {
        if (! $this->isAdmin) {
            return [];
        }

        // Last 7 days sales
        $dates = collect(range(6, 0))->map(function ($days) {
            return now()->subDays($days)->format('Y-m-d');
        });

        $data = [];
        $labels = [];

        foreach ($dates as $date) {
            $labels[] = \Carbon\Carbon::parse($date)->isoFormat('dddd');
            $data[] = Sale::whereDate('date', $date)->sum('total_amount');
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    #[Computed]
    public function activeShifts(): \Illuminate\Support\Collection
    {
        $today = now()->toDateString();

        // Ambil semua attendance aktif (sudah check-in, belum check-out) hari ini
        $activeAttendances = Attendance::whereDate('date', $today)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->with([
                'user:id,name,photo',
                'scheduleAssignment.schedule',
            ])
            ->get();

        // Petakan ke struktur yang digunakan view
        return $activeAttendances->map(function ($attendance) {
            if ($attendance->schedule_assignment_id) {
                $assignment = $attendance->scheduleAssignment;

                return (object) [
                    'user' => $attendance->user,
                    'session' => $assignment?->session ?? '-',
                    'schedule' => (object) [
                        'name' => $assignment?->schedule?->name ?? 'Jadwal',
                        'start_time' => $assignment?->time_start ?? null,
                        'end_time' => $assignment?->time_end ?? null,
                    ],
                    'type' => 'scheduled',
                ];
            }

            // Override (tanpa schedule)
            return (object) [
                'user' => $attendance->user,
                'session' => 'Override',
                'schedule' => (object) [
                    'name' => 'Luar Jadwal (Override)',
                    'start_time' => optional($attendance->check_in)->format('H:i'),
                    'end_time' => '-',
                ],
                'type' => 'override',
            ];
        })->values();
    }

    #[Computed]
    public function pendingApprovals(): array
    {
        if (! $this->isAdmin) {
            return [];
        }

        return [
            'leaves' => LeaveRequest::where('status', 'pending')->count(),
            // 'swaps' => SwapRequest::where('status', 'pending')->count(), // Assuming SwapRequest model exists
        ];
    }

    // ==========================================
    // USER DASHBOARD DATA
    // ==========================================

    #[Computed]
    public function nextShift()
    {
        // if ($this->isAdmin) return null; // Admin juga bisa punya shift

        return ScheduleAssignment::where('user_id', $this->user->id)
            ->where('date', '>=', now()->format('Y-m-d'))
            ->where('status', 'scheduled')
            ->orderBy('date')
            ->orderBy('session')
            ->with('schedule')
            ->first();
    }

    #[Computed]
    public function weeklySchedule()
    {
        // if ($this->isAdmin) return collect(); // Admin juga bisa punya jadwal

        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return ScheduleAssignment::where('user_id', $this->user->id)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->orderBy('date')
            ->orderBy('session')
            ->with('schedule')
            ->get();
    }

    #[Computed]
    public function fullWeeklySchedule()
    {
        // if ($this->isAdmin) return collect(); // Admin juga bisa punya jadwal

        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return ScheduleAssignment::whereBetween('date', [$startOfWeek, $endOfWeek])
            ->with(['user:id,name,photo', 'schedule'])
            ->orderBy('date')
            ->orderBy('session')
            ->get()
            ->groupBy(function ($item) {
                return $item->date->format('Y-m-d');
            });
    }

    #[Computed]
    public function userStats(): array
    {
        // if ($this->isAdmin) return []; // Admin juga butuh stats pribadi

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $present = Attendance::where('user_id', $this->user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'present')
            ->count();
            
        $late = Attendance::where('user_id', $this->user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'late')
            ->count();

        $penalty = Penalty::where('user_id', $this->user->id)
            ->where('status', 'active')
            ->sum('points');

        // Notification count
        $notifCount = Notification::where('user_id', $this->user->id)
            ->whereNull('read_at')
            ->count();

        // Recent notifications
        $notifications = Notification::where('user_id', $this->user->id)
            ->whereNull('read_at')
            ->latest()
            ->limit(5)
            ->get();

        return [
            'present' => $present,
            'late' => $late,
            'penalty' => $penalty,
            'notificationCount' => $notifCount,
            'notifications' => $notifications,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard-index')->layout('layouts.app');
    }
}
