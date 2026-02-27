<?php

namespace App\Livewire\Stock;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ActivityLogService;
use App\Services\ProductService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProcurementModal extends Component
{
    public bool $show = false;

    public string $supplier_name = '';
    public string $invoice_number = '';
    public string $date = '';
    public string $notes = '';

    public array $items = [];

    // Search state
    public string $search = '';
    public array $searchResults = [];

    protected $listeners = ['openProcurementModal' => 'open'];

    public function mount()
    {
        $this->date = date('Y-m-d');
        $this->invoice_number = 'INV-' . date('Ymd') . '-' . rand(100, 999);
    }

    public function open()
    {
        $this->reset(['supplier_name', 'items', 'search', 'searchResults', 'notes']);
        $this->date = date('Y-m-d');
        $this->invoice_number = 'INV-' . date('Ymd') . '-' . rand(100, 999);
        $this->show = true;
    }

    public function close()
    {
        $this->show = false;
    }

    public function updatedSearch()
    {
        if (strlen($this->search) < 2) {
            $this->searchResults = [];
            return;
        }

        $this->searchResults = Product::query()
            ->with(['variants' => fn($q) => $q->where('is_active', true)])
            ->where('name', 'like', "%{$this->search}%")
            ->orWhere('sku', 'like', "%{$this->search}%")
            ->limit(10)
            ->get()
            ->map(function($product) {
                // Ensure has_variants is respected even if variants relation is empty
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'has_variants' => $product->has_variants,
                    'variants' => $product->variants->map(function($v) {
                        return [
                            'id' => $v->id,
                            'variant_name' => $v->variant_name,
                            'sku' => $v->sku,
                            'stock' => $v->stock,
                            'cost_price' => $v->cost_price
                        ];
                    })->toArray(),
                    'stock' => $product->stock,
                    'cost_price' => $product->cost_price
                ];
            })
            ->toArray();
    }

    public function addItem($productId, $variantId = null)
    {
        $product = Product::find($productId);
        if (!$product) return;

        // Prevent adding parent product if it has variants but no variant selected
        if ($product->has_variants && !$variantId) {
            $this->dispatch('notify', type: 'error', message: 'Produk ini memiliki varian. Silakan pilih varian.');
            return;
        }

        $variant = null;
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if (!$variant) return;
        }

        // Check if item already exists
        foreach ($this->items as $index => $item) {
            if ($item['product_id'] == $productId && $item['variant_id'] == $variantId) {
                $this->items[$index]['quantity']++;
                $this->updateSubtotal($index);
                $this->search = '';
                $this->searchResults = [];
                return;
            }
        }

        $costPrice = $variant ? $variant->cost_price : $product->cost_price;
        $currentStock = $variant ? $variant->stock : $product->stock;

        $this->items[] = [
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'name' => $product->name . ($variant ? " ({$variant->variant_name})" : ''),
            'sku' => $variant ? $variant->sku : $product->sku,
            'current_stock' => $currentStock,
            'quantity' => 1,
            'cost_price' => (float) $costPrice,
            'subtotal' => (float) $costPrice
        ];

        $this->search = '';
        $this->searchResults = [];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updateItem($index, $field, $value)
    {
        $this->items[$index][$field] = $value;
        $this->updateSubtotal($index);
    }

    public function updatedItems($value, $key)
    {
        // Parse key to get index (e.g., "0.cost_price" -> index 0)
        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = $parts[0];
            if (is_numeric($index) && isset($this->items[$index])) {
                $this->updateSubtotal($index);
            }
        }
    }

    public function updateSubtotal($index)
    {
        $qty = (int) ($this->items[$index]['quantity'] ?? 0);
        $cost = (float) ($this->items[$index]['cost_price'] ?? 0);
        $this->items[$index]['subtotal'] = $qty * $cost;
    }

    #[Computed]
    public function totalAmount()
    {
        return array_sum(array_column($this->items, 'subtotal'));
    }

    public function save()
    {
        $this->validate([
            'supplier_name' => 'nullable|string|max:255',
            'invoice_number' => 'required|string|max:50|unique:purchases,invoice_number',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
        ]);

        try {
            $itemCount = count($this->items);
            $totalAmount = $this->totalAmount();
            
            app(ProductService::class)->createProcurement([
                'supplier_name' => $this->supplier_name,
                'invoice_number' => $this->invoice_number,
                'date' => $this->date,
                'notes' => $this->notes,
            ], $this->items);

            ActivityLogService::logPurchaseCreated($this->invoice_number, $totalAmount);
            $this->close();
            $this->dispatch('procurement-saved'); // To refresh parent
            $this->dispatch('notify', type: 'success', message: 'Pengadaan berhasil disimpan');
            
            // Clear cache
            \Illuminate\Support\Facades\Cache::forget('stock:stats');

        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.stock.procurement-modal');
    }
}
