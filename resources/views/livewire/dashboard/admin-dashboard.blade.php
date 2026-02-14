<div class="space-y-6">
    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Revenue Today --}}
        <x-ui.card class="relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pendapatan Hari Ini</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ format_currency($this->adminStats['revenue_today']) }}
                    </h3>
                    <p class="text-xs text-emerald-600 mt-1 flex items-center">
                        <x-ui.icon name="trending-up" class="w-3 h-3 mr-1" />
                        {{ $this->adminStats['transaction_count'] }} Transaksi
                    </p>
                </div>
                <div class="p-2 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                    <x-ui.icon name="currency-dollar" class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
            </div>
        </x-ui.card>

        {{-- Attendance --}}
        <x-ui.card>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kehadiran Shift</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ $this->adminStats['attendance_percentage'] }}%
                    </h3>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2 dark:bg-gray-700">
                        <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $this->adminStats['attendance_percentage'] }}%"></div>
                    </div>
                </div>
                <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                    <x-ui.icon name="user-group" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>
        </x-ui.card>

        {{-- Revenue Month --}}
        <x-ui.card>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pendapatan Bulan Ini</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ format_currency($this->adminStats['revenue_month']) }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Akumulasi berjalan</p>
                </div>
                <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <x-ui.icon name="chart-bar" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
            </div>
        </x-ui.card>

        {{-- Low Stock --}}
        <x-ui.card class="{{ $this->adminStats['low_stock_count'] > 0 ? 'border-l-4 border-l-red-500' : '' }}">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Stok Menipis</p>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ $this->adminStats['low_stock_count'] }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Produk perlu restock</p>
                </div>
                <div class="p-2 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <x-ui.icon name="exclamation-triangle" class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
            </div>
            @if($this->adminStats['low_stock_count'] > 0)
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <a href="{{ route('admin.products.index') }}" class="text-xs text-red-600 hover:text-red-700 font-medium flex items-center">
                        Lihat Produk
                        <x-ui.icon name="arrow-right" class="w-3 h-3 ml-1" />
                    </a>
                </div>
            @endif
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Sales Chart --}}
        <div class="lg:col-span-3">
            <x-ui.card title="Tren Penjualan (7 Hari Terakhir)">
                <div class="h-72" wire:ignore>
                    <canvas id="salesChart"></canvas>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:initialized', () => {
        const ctx = document.getElementById('salesChart');
        const chartData = @json($this->salesChartData);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Pendapatan',
                    data: chartData.data,
                    borderColor: '#0ea5e9', // primary-500
                    backgroundColor: 'rgba(14, 165, 233, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
