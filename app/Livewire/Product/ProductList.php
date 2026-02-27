<?php

namespace App\Livewire\Product;

use App\Models\Product;
use App\Services\ActivityLogService;
use App\Services\ProductService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Manajemen Produk')]
class ProductList extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = 'all';

    public $categoryFilter = 'all';

    public $stockFilter = 'all';

    // Form properties (moved to ProductForm component)
    
    protected $listeners = ['product-saved' => '$refresh'];

    public function mount($id = null)
    {
        // Route-based logic removed as we are using separate pages for create/edit
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // Methods create, edit, save, resetForm removed as they are moved to ProductForm


    /**
     * Delete product
     *
     * @param  int  $id
     * @return void
     */
    public function delete($id)
    {
        $productService = app(ProductService::class);

        try {
            $product = Product::findOrFail($id);
            $productName = $product->name;
            $productService->delete($id);
            ActivityLogService::logProductDeleted($productName);
            $this->dispatch('toast', message: 'Produk berhasil dihapus', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    /**
     * Toggle product status
     *
     * @param  int  $id
     * @return void
     */
    public function toggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $newStatus = $product->status === 'active' ? 'inactive' : 'active';

        $productService = app(ProductService::class);
        $productService->update($id, ['status' => $newStatus]);

        ActivityLogService::log("Mengubah status produk '{$product->name}' menjadi {$newStatus}");
        $this->dispatch('toast', message: 'Status produk berhasil diubah', type: 'success');
    }

    // resetForm moved to ProductForm


    public function getCategoriesProperty()
    {
        return Product::distinct()
            ->whereNotNull('category')
            ->pluck('category')
            ->sort();
    }

    public function render()
    {
        $products = Product::query()
            // Use scopeWithVariantStats for eager loading variant statistics
            // Requirements: 1.3 - Load variant counts dalam single query
            ->withVariantStats()
            // Eager load activeVariants for low stock variant display
            // Requirements: 2.4 - Show which variants are low
            ->with(['activeVariants' => function ($query) {
                $query->select('id', 'product_id', 'variant_name', 'stock', 'min_stock');
            }])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%')
                        ->orWhere('category', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->stockFilter === 'low', fn ($q) => $q->lowStock())
            ->when($this->stockFilter === 'out', fn ($q) => $q->outOfStock())
            ->when($this->stockFilter === 'available', fn ($q) => $q->inStock())
            ->when($this->stockFilter === 'low_variant', fn ($q) => $q->withLowStockVariants())
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => Product::count(),
            'active' => Product::active()->count(),
            'low_stock' => Product::lowStock()->count(),
            'out_of_stock' => Product::outOfStock()->count(),
            'low_variant_stock' => Product::withLowStockVariants()->count(),
        ];

        return view('livewire.product.product-list', [
            'products' => $products,
            'stats' => $stats,
            'categories' => $this->categories,
        ])->layout('layouts.app');
    }
}
