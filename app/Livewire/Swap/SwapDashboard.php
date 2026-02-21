<?php

namespace App\Livewire\Swap;

use App\Models\SwapRequest;
use Livewire\Component;

class SwapDashboard extends Component
{
    public $selectedPeriod = 'month'; // week, month, quarter, year

    public $stats = [];

    public $chartData = [];

    public $recentActivity = [];

    public $topSwappers = [];

    public $pendingApprovals = [];

    public function mount()
    {
        $this->loadDashboardData();
    }

    public function updatedSelectedPeriod()
    {
        $this->loadDashboardData();
    }

    private function loadDashboardData()
    {
        $this->stats = $this->getStats();
        $this->chartData = $this->getChartData();
        $this->recentActivity = $this->getRecentActivity();
        $this->topSwappers = $this->getTopSwappers();
        $this->pendingApprovals = $this->getPendingApprovals();
    }

    private function getStats()
    {
        $dateRange = $this->getDateRange();

        return [
            'total_requests' => SwapRequest::whereBetween('created_at', $dateRange)->count(),
            'pending_requests' => SwapRequest::where('status', 'pending')->count(),
            'approved_requests' => SwapRequest::whereIn('status', ['target_approved', 'admin_approved'])
                ->whereBetween('created_at', $dateRange)->count(),
            'completed_swaps' => SwapRequest::where('status', 'admin_approved')
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', $dateRange)->count(),
            'rejected_requests' => SwapRequest::whereIn('status', ['target_rejected', 'admin_rejected'])
                ->whereBetween('created_at', $dateRange)->count(),
            'success_rate' => $this->calculateSuccessRate($dateRange),
            'avg_processing_time' => $this->calculateAvgProcessingTime($dateRange),
        ];
    }

    private function getChartData()
    {
        $dateRange = $this->getDateRange();
        $groupBy = $this->getGroupByClause();

        $requests = SwapRequest::selectRaw($groupBy.', COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending')
            ->selectRaw('SUM(CASE WHEN status IN ("target_approved", "admin_approved") THEN 1 ELSE 0 END) as approved')
            ->selectRaw('SUM(CASE WHEN status IN ("target_rejected", "admin_rejected") THEN 1 ELSE 0 END) as rejected')
            ->whereBetween('created_at', $dateRange)
            ->groupBy($groupBy)
            ->orderBy('period')
            ->get();

        return $requests->map(function ($item) {
            return [
                'period' => $item->period,
                'total' => $item->total,
                'pending' => $item->pending,
                'approved' => $item->approved,
                'rejected' => $item->rejected,
            ];
        });
    }

    private function getRecentActivity()
    {
        return SwapRequest::with([
            'requester:id,name',
            'target:id,name',
            'adminResponder:id,name',
        ])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get(['id', 'user_id', 'target_id', 'status', 'created_at', 'admin_responded_at'])
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'requester' => $request->requester->name,
                    'target' => $request->target->name,
                    'status' => $this->getStatusText($request->status),
                    'status_color' => $this->getStatusColor($request->status),
                    'created_at' => $request->created_at->diffForHumans(),
                    'responded_at' => $request->admin_responded_at?->diffForHumans(),
                ];
            });
    }

    private function getTopSwappers()
    {
        $dateRange = $this->getDateRange();

        return SwapRequest::selectRaw('user_id, COUNT(*) as request_count')
            ->selectRaw('SUM(CASE WHEN status = "admin_approved" THEN 1 ELSE 0 END) as successful_swaps')
            ->whereBetween('created_at', $dateRange)
            ->groupBy('user_id')
            ->orderBy('successful_swaps', 'desc')
            ->take(5)
            ->with('requester:id,name,nim')
            ->get()
            ->map(function ($item) {
                return [
                    'user' => $item->requester->name,
                    'nim' => $item->requester->nim,
                    'total_requests' => $item->request_count,
                    'successful_swaps' => $item->successful_swaps,
                    'success_rate' => $item->request_count > 0
                        ? round(($item->successful_swaps / $item->request_count) * 100, 1)
                        : 0,
                ];
            });
    }

    private function getPendingApprovals()
    {
        return SwapRequest::with([
            'requester:id,name',
            'target:id,name',
            'requesterAssignment.schedule',
            'targetAssignment.schedule',
        ])
            ->where('status', 'target_approved')
            ->orderBy('created_at', 'asc')
            ->take(5)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'requester' => $request->requester->name,
                    'target' => $request->target->name,
                    'requester_shift' => $request->requesterAssignment->schedule->day.' '.
                        $request->requesterAssignment->time_start,
                    'target_shift' => $request->targetAssignment->schedule->day.' '.
                        $request->targetAssignment->time_start,
                    'waiting_time' => $request->created_at->diffForHumans(),
                ];
            });
    }

    private function getDateRange()
    {
        $now = now();

        return match ($this->selectedPeriod) {
            'week' => [$now->startOfWeek(), $now->endOfWeek()],
            'month' => [$now->startOfMonth(), $now->endOfMonth()],
            'quarter' => [$now->startOfQuarter(), $now->endOfQuarter()],
            'year' => [$now->startOfYear(), $now->endOfYear()],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };
    }

    private function getGroupByClause()
    {
        return match ($this->selectedPeriod) {
            'week' => 'DATE(created_at) as period',
            'month' => 'DATE(created_at) as period',
            'quarter' => 'DATE_FORMAT(created_at, "%Y-%u") as period',
            'year' => 'DATE_FORMAT(created_at, "%Y-%m") as period',
            default => 'DATE(created_at) as period',
        };
    }

    private function calculateSuccessRate($dateRange)
    {
        $total = SwapRequest::whereBetween('created_at', $dateRange)->count();
        $successful = SwapRequest::where('status', 'admin_approved')
            ->whereBetween('created_at', $dateRange)->count();

        return $total > 0 ? round(($successful / $total) * 100, 1) : 0;
    }

    private function calculateAvgProcessingTime($dateRange)
    {
        $requests = SwapRequest::selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, admin_responded_at)) as avg_hours')
            ->whereNotNull('admin_responded_at')
            ->whereBetween('created_at', $dateRange)
            ->first();

        return $requests->avg_hours ? round($requests->avg_hours, 1) : 0;
    }

    private function getStatusText($status)
    {
        return match ($status) {
            'pending' => 'Menunggu',
            'target_approved' => 'Disetujui Target',
            'admin_approved' => 'Disetujui Admin',
            'target_rejected' => 'Ditolak Target',
            'admin_rejected' => 'Ditolak Admin',
            'cancelled' => 'Dibatalkan',
            default => 'Unknown',
        };
    }

    private function getStatusColor($status)
    {
        return match ($status) {
            'pending' => 'yellow',
            'target_approved' => 'blue',
            'admin_approved' => 'green',
            'target_rejected', 'admin_rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function render()
    {
        return view('livewire.swap.dashboard', [
            'stats' => $this->stats,
            'chartData' => $this->chartData,
            'recentActivity' => $this->recentActivity,
            'topSwappers' => $this->topSwappers,
            'pendingApprovals' => $this->pendingApprovals,
        ])->layout('layouts.app')->title('Dashboard Tukar Shift');
    }
}
