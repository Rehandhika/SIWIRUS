<?php

namespace App\Livewire\Report;

use App\Exports\SaleItemsExport;
use App\Exports\SalesExport;
use App\Models\Product;
use App\Models\Sale;
use App\Services\ActivityLogService;
use App\Services\ShuPointService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Title('Laporan Penjualan')]
class SalesReport extends Component
{
    use WithPagination;

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $period = 'month';

    public ?int $selectedSaleId = null;

    public ?int $saleToDelete = null;

    public bool $showDeleteModal = false;

    // Cache key untuk invalidasi
    #[Locked]
    public string $cacheKey = '';

    public function mount()
    {
        $this->setPeriod('month');
    }

    public function setPeriod(string $period)
    {
        $this->period = $period;
        $now = now();

        [$this->dateFrom, $this->dateTo] = match ($period) {
            'today' => [$now->format('Y-m-d'), $now->format('Y-m-d')],
            'yesterday' => [$now->copy()->subDay()->format('Y-m-d'), $now->copy()->subDay()->format('Y-m-d')],
            'week' => [$now->copy()->startOfWeek()->format('Y-m-d'), $now->copy()->endOfWeek()->format('Y-m-d')],
            'month' => [$now->copy()->startOfMonth()->format('Y-m-d'), $now->copy()->endOfMonth()->format('Y-m-d')],
            'year' => [$now->copy()->startOfYear()->format('Y-m-d'), $now->copy()->endOfYear()->format('Y-m-d')],
            default => [$this->dateFrom, $this->dateTo],
        };

        $this->updateCacheKey();
        $this->resetPage();
        
        $this->dispatch('update-charts', 
            data: $this->chartData, 
            hourly: array_values($this->hourlySales),
            payment: $this->paymentSummary
        );
    }

    public function updatedDateFrom()
    {
        $this->updatePeriodBasedOnDates();
        $this->updateCacheKey();
        $this->resetPage();
        
        $this->dispatch('update-charts', 
            data: $this->chartData, 
            hourly: array_values($this->hourlySales),
            payment: $this->paymentSummary
        );
    }

    public function updatedDateTo()
    {
        $this->updatePeriodBasedOnDates();
        $this->updateCacheKey();
        $this->resetPage();
        
        $this->dispatch('update-charts', 
            data: $this->chartData, 
            hourly: array_values($this->hourlySales),
            payment: $this->paymentSummary
        );
    }

    private function updatePeriodBasedOnDates()
    {
        if (empty($this->dateFrom) || empty($this->dateTo)) {
            $this->period = 'custom';

            return;
        }

        $now = now();
        $dateFrom = \Carbon\Carbon::parse($this->dateFrom);
        $dateTo = \Carbon\Carbon::parse($this->dateTo);

        // Check if dates match predefined periods
        if ($dateFrom->format('Y-m-d') === $now->format('Y-m-d') &&
            $dateTo->format('Y-m-d') === $now->format('Y-m-d')) {
            $this->period = 'today';
        } elseif ($dateFrom->format('Y-m-d') === $now->copy()->subDay()->format('Y-m-d') &&
                  $dateTo->format('Y-m-d') === $now->copy()->subDay()->format('Y-m-d')) {
            $this->period = 'yesterday';
        } elseif ($dateFrom->format('Y-m-d') === $now->copy()->startOfWeek()->format('Y-m-d') &&
                  $dateTo->format('Y-m-d') === $now->copy()->endOfWeek()->format('Y-m-d')) {
            $this->period = 'week';
        } elseif ($dateFrom->format('Y-m-d') === $now->copy()->startOfMonth()->format('Y-m-d') &&
                  $dateTo->format('Y-m-d') === $now->copy()->endOfMonth()->format('Y-m-d')) {
            $this->period = 'month';
        } elseif ($dateFrom->format('Y-m-d') === $now->copy()->startOfYear()->format('Y-m-d') &&
                  $dateTo->format('Y-m-d') === $now->copy()->endOfYear()->format('Y-m-d')) {
            $this->period = 'year';
        } else {
            $this->period = 'custom';
        }
    }

    private function updateCacheKey()
    {
        $this->cacheKey = "sales_{$this->dateFrom}_{$this->dateTo}";
    }

    public function showDetail(int $saleId)
    {
        $this->selectedSaleId = $saleId;
    }

    public function closeDetail()
    {
        $this->selectedSaleId = null;
    }

    /**
     * Tampilkan modal konfirmasi hapus
     */
    public function confirmDelete(int $saleId): void
    {
        $this->saleToDelete = $saleId;
        $this->showDeleteModal = true;
    }

    /**
     * Tutup modal konfirmasi hapus
     */
    public function cancelDelete(): void
    {
        $this->saleToDelete = null;
        $this->showDeleteModal = false;
    }

    /**
     * Hapus transaksi dan kembalikan stok
     */
    public function deleteSale(): void
    {
        if (! $this->saleToDelete) {
            return;
        }

        try {
            DB::transaction(function () {
                $sale = Sale::with('items:id,sale_id,product_id,quantity')->findOrFail($this->saleToDelete);
                $invoiceNumber = $sale->invoice_number;

                app(ShuPointService::class)->reverseSalePoints($sale);

                // Kembalikan stok produk
                foreach ($sale->items as $item) {
                    Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }

                // Hapus items dan sale (soft delete)
                $sale->items()->delete();
                $sale->delete();

                // Log activity
                ActivityLogService::logSaleDeleted($invoiceNumber);
            });

            // Clear cache
            Cache::forget('pos_products_active');

            // Reset state
            $this->saleToDelete = null;
            $this->showDeleteModal = false;
            $this->selectedSaleId = null;

            // Update cache key untuk refresh computed properties
            $this->updateCacheKey();

            $this->dispatch('toast', message: 'Transaksi berhasil dihapus', type: 'success');

        } catch (\Exception $e) {
            Log::error('Delete Sale Error: '.$e->getMessage(), ['sale_id' => $this->saleToDelete]);
            $this->dispatch('toast', message: 'Gagal menghapus transaksi: '.$e->getMessage(), type: 'error');

            $this->saleToDelete = null;
            $this->showDeleteModal = false;
        }
    }

    #[Computed]
    public function selectedSale()
    {
        if (! $this->selectedSaleId) {
            return null;
        }

        return Sale::with(['items:id,sale_id,product_id,product_name,quantity,price,subtotal', 'items.product:id,name', 'cashier'])
            ->select('id', 'invoice_number', 'cashier_id', 'payment_method', 'total_amount', 'payment_amount', 'change_amount', 'notes', 'created_at')
            ->find($this->selectedSaleId);
    }

    /**
     * Single optimized query untuk semua statistik
     * Menggunakan raw query untuk performa maksimal
     */
    #[Computed]
    public function reportData()
    {
        // Single query untuk stats, payment breakdown, dan daily chart
        $result = DB::select("
            SELECT 
                COUNT(*) as total,
                COALESCE(SUM(total_amount), 0) as revenue,
                COALESCE(AVG(total_amount), 0) as avg_amount,
                COALESCE(MAX(total_amount), 0) as max_amount,
                SUM(payment_method = 'cash') as cash_count,
                SUM(payment_method = 'transfer') as transfer_count,
                SUM(payment_method = 'qris') as qris_count,
                SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END) as cash_amount,
                SUM(CASE WHEN payment_method = 'transfer' THEN total_amount ELSE 0 END) as transfer_amount,
                SUM(CASE WHEN payment_method = 'qris' THEN total_amount ELSE 0 END) as qris_amount
            FROM sales 
            WHERE date BETWEEN ? AND ? AND deleted_at IS NULL
        ", [$this->dateFrom, $this->dateTo]);

        return $result[0] ?? (object) [
            'total' => 0, 'revenue' => 0, 'avg_amount' => 0, 'max_amount' => 0,
            'cash_count' => 0, 'transfer_count' => 0, 'qris_count' => 0,
            'cash_amount' => 0, 'transfer_amount' => 0, 'qris_amount' => 0,
        ];
    }

    #[Computed]
    public function topProducts()
    {
        return DB::select('
            SELECT p.name, SUM(si.quantity) as total_qty, SUM(si.subtotal) as total_revenue
            FROM sale_items si
            INNER JOIN sales s ON si.sale_id = s.id
            INNER JOIN products p ON si.product_id = p.id
            WHERE s.date BETWEEN ? AND ? AND s.deleted_at IS NULL
            GROUP BY p.id, p.name
            ORDER BY total_revenue DESC
            LIMIT 5
        ', [$this->dateFrom, $this->dateTo]);
    }

    #[Computed]
    public function chartData()
    {
        // Query daily revenue dalam satu query
        $dailyData = collect(DB::select('
            SELECT DATE(date) as day, SUM(total_amount) as revenue
            FROM sales 
            WHERE date BETWEEN ? AND ? AND deleted_at IS NULL
            GROUP BY DATE(date)
        ', [$this->dateFrom, $this->dateTo]))->pluck('revenue', 'day')->toArray();

        $labels = [];
        $revenue = [];

        // Generate labels untuk semua tanggal dalam range
        $start = \Carbon\Carbon::parse($this->dateFrom);
        $end = \Carbon\Carbon::parse($this->dateTo);

        while ($start <= $end) {
            $key = $start->format('Y-m-d');
            $labels[] = $start->format('d/m');
            $revenue[] = (float) ($dailyData[$key] ?? 0);
            $start->addDay();
        }

        return ['labels' => $labels, 'revenue' => $revenue];
    }

    #[Computed]
    public function hourlySales()
    {
        $data = collect(DB::select('
            SELECT HOUR(created_at) as hour, COUNT(*) as count
            FROM sales 
            WHERE date BETWEEN ? AND ? AND deleted_at IS NULL
            GROUP BY HOUR(created_at)
        ', [$this->dateFrom, $this->dateTo]))->pluck('count', 'hour')->toArray();

        // Fill semua 24 jam
        $hourly = array_fill(0, 24, 0);
        foreach ($data as $hour => $count) {
            $hourly[(int) $hour] = (int) $count;
        }

        return $hourly;
    }

    #[Computed]
    public function peakHour()
    {
        $hourly = $this->hourlySales;
        $maxCount = max($hourly);
        if ($maxCount === 0) {
            return null;
        }

        return [
            'hour' => array_search($maxCount, $hourly),
            'count' => $maxCount,
        ];
    }

    #[Computed]
    public function paymentSummary()
    {
        $stats = $this->reportData;
        if ($stats->total == 0) {
            return [];
        }

        $methods = [
            ['name' => 'Cash', 'count' => (int) $stats->cash_count, 'amount' => (float) $stats->cash_amount, 'color' => 'emerald'],
            ['name' => 'Transfer', 'count' => (int) $stats->transfer_count, 'amount' => (float) $stats->transfer_amount, 'color' => 'blue'],
            ['name' => 'QRIS', 'count' => (int) $stats->qris_count, 'amount' => (float) $stats->qris_amount, 'color' => 'violet'],
        ];

        return collect($methods)
            ->filter(fn ($m) => $m['count'] > 0)
            ->map(fn ($m) => array_merge($m, [
                'percentage' => round(($m['count'] / $stats->total) * 100, 1),
            ]))
            ->values()
            ->toArray();
    }

    /**
     * Export laporan penjualan ke Excel (per transaksi)
     */
    public function exportSales()
    {
        $filename = 'laporan_penjualan_' . $this->dateFrom . '_' . $this->dateTo . '.xlsx';

        return Excel::download(
            new SalesExport($this->dateFrom, $this->dateTo),
            $filename
        );
    }

    /**
     * Export detail item penjualan ke Excel
     */
    public function exportSaleItems()
    {
        $filename = 'detail_item_penjualan_' . $this->dateFrom . '_' . $this->dateTo . '.xlsx';

        return Excel::download(
            new SaleItemsExport($this->dateFrom, $this->dateTo),
            $filename
        );
    }

    public function render()
    {
        // Query paginated dengan optimasi
        $sales = Sale::query()
            ->whereBetween('date', [$this->dateFrom, $this->dateTo])
            ->with('cashier')
            ->withCount('items')
            ->withSum('items', 'quantity')
            ->select('id', 'invoice_number', 'cashier_id', 'payment_method', 'total_amount', 'created_at')
            ->latest('created_at')
            ->paginate(15);

        return view('livewire.report.sales-report', ['sales' => $sales])
            ->layout('layouts.app');
    }
}
