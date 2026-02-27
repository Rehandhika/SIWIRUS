<?php

namespace App\Livewire\Stock;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Services\ProductService;
use App\Services\StockCalculationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Manajemen Stok')]
class StockManager extends Component
{
    use WithPagination;

    #[Url]
    public string $activeTab = 'products';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $stockFilter = 'all';

    public string $categoryFilter = '';

    public string $historySearch = '';

    public string $historyType = 'all';

    // Modal state
    public bool $readyToLoad = false;

    public function init(): void
    {
        $this->readyToLoad = true;
    }

    protected function rules(): array
    {
        return [
            // No validation needed for now
        ];
    }

    #[On('procurement-saved')]
    public function refreshStats(): void
    {
        Cache::forget('stock:stats');
        $this->resetPage();
        $this->resetPage('historyPage');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStockFilter(): void
    {
        $this->resetPage();
    }

    public function updatingHistorySearch(): void
    {
        $this->resetPage('historyPage');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    /**
     * Toggle expanded state for variant products
     */
    public function toggleExpand(int $productId): void
    {
        if (in_array($productId, $this->expandedProducts)) {
            $this->expandedProducts = array_values(array_diff($this->expandedProducts, [$productId]));
        } else {
            $this->expandedProducts[] = $productId;
        }
    }

    public function exportHistory()
    {
        ActivityLogService::log("Mengekspor data histori stok produk");
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StockHistoryExport($this->historySearch, $this->historyType),
            'riwayat-stok-' . date('Y-m-d-His') . '.xlsx'
        );
    }

    public function exportProducts()
    {
        ActivityLogService::log("Mengekspor data histori stok produk");
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StockProductsExport($this->search, $this->categoryFilter, $this->stockFilter),
            'stok-produk-' . date('Y-m-d-His') . '.xlsx'
        );
    }

    // Expanded variants
    public array $expandedProducts = [];

    // Detail Modal State
    public bool $showDetailModal = false;
    public ?\App\Models\Purchase $selectedProcurement = null;
    public ?StockAdjustment $selectedAdjustment = null;

    public function showDetail(int $adjustmentId)
    {
        $this->selectedAdjustment = StockAdjustment::with(['product', 'variant', 'user'])->find($adjustmentId);
        
        if ($this->selectedAdjustment) {
            $this->selectedProcurement = $this->selectedAdjustment->getProcurement();
            
            // If procurement found, load its items
            if ($this->selectedProcurement) {
                $this->selectedProcurement->load(['items.product', 'items.variant']);
            }
            
            $this->showDetailModal = true;
        }
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedProcurement = null;
        $this->selectedAdjustment = null;
    }

    #[Computed(persist: true)]
    public function stats(): array
    {
        // Always calculate stats, skip the readyToLoad check for stats to ensure they show up
        // The readyToLoad is mainly for heavy table data rendering
        
        return Cache::remember('stock:stats', 120, function () {
            return app(StockCalculationService::class)->calculateStockStats();
        });
    }

    #[Computed]
    public function categories(): array
    {
        return Cache::remember('product:categories', 300, fn () => Product::whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->toArray()
        );
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->select(['id', 'name', 'sku', 'category', 'stock', 'min_stock', 'price', 'cost_price', 'image', 'has_variants'])
            ->with(['variants' => fn ($q) => $q->where('is_active', true)->orderBy('variant_name')])
            ->when($this->search, fn ($q) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")
            ))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->stockFilter === 'low', fn ($q) => $q->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0))
            ->when($this->stockFilter === 'out', fn ($q) => $q->where('stock', '<=', 0))
            ->when($this->stockFilter === 'normal', fn ($q) => $q->whereColumn('stock', '>', 'min_stock'))
            ->orderByRaw('CASE WHEN stock <= 0 THEN 0 WHEN stock <= min_stock THEN 1 ELSE 2 END')
            ->orderBy('name')
            ->paginate(20);
    }

    #[Computed]
    public function selectedProduct(): ?Product
    {
        return $this->selectedProductId ? Product::with(['variants' => fn ($q) => $q->where('is_active', true)])->find($this->selectedProductId) : null;
    }

    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        return $this->selectedVariantId ? ProductVariant::find($this->selectedVariantId) : null;
    }

    #[Computed]
    public function adjustmentTarget(): ?object
    {
        if ($this->selectedVariantId) {
            return $this->selectedVariant;
        }

        return $this->selectedProduct;
    }

    #[Computed]
    public function adjustments()
    {
        return StockAdjustment::query()
            ->with(['product:id,name,sku', 'user:id,name', 'variant:id,variant_name'])
            ->when($this->historySearch, fn ($q) => $q->whereHas('product', fn ($sub) => $sub->where('name', 'like', "%{$this->historySearch}%")
            ))
            ->when($this->historyType !== 'all', fn ($q) => $q->where('type', $this->historyType))
            ->recent()
            ->paginate(15, pageName: 'historyPage');
    }

    public function render()
    {
        return view('livewire.stock.stock-manager');
    }
}
