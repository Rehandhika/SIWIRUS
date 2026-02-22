<?php

namespace App\Livewire\Cashier;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Student;
use App\Services\PaymentConfigurationService;
use App\Traits\AuthorizesLivewireRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Point of Sale')]
class PointOfSale extends Component
{
    use AuthorizesLivewireRequests;

    // Search & Filter
    public string $search = '';

    public string $category = '';

    // Cart - using array for better performance
    public array $cart = [];

    // Payment
    public string $paymentMethod = 'cash';

    public int $paymentAmount = 0;

    public string $studentNim = '';

    // UI State
    public bool $showCart = false;

    public bool $showPayment = false;

    // Variant Selection
    public bool $showVariantModal = false;

    public ?int $selectedProductId = null;

    public array $productVariants = [];

    public array $groupedVariants = [];

    public ?string $selectedProductName = null;

    // Quick amounts - locked to prevent tampering
    #[Locked]
    public array $quickAmounts = [10000, 20000, 50000, 100000];

    protected $listeners = ['barcode-scanned' => 'handleBarcode'];

    protected array $messages = [
        'studentNim.digits' => 'NIM harus 9 digit angka',
        'studentNim.exists' => 'NIM tidak terdaftar',
    ];

    #[Computed]
    public function products()
    {
        return Product::query()
            ->select(['id', 'name', 'sku', 'price', 'stock', 'category', 'image', 'has_variants', 'status']) // Add status to select
            ->with(['variants' => function ($q) {
                $q->select(['id', 'product_id', 'variant_name', 'price', 'stock', 'option_values', 'is_active'])
                    ->where('stock', '>', 0);
            }])
            // Removed ->where('status', 'active') to allow inactive products to be searched/scanned
            ->where(function ($q) {
                // Products without variants must have stock > 0
                // Products with variants must have at least one variant with stock > 0
                $q->where(function ($query) {
                    $query->where('has_variants', false)->where('stock', '>', 0);
                })->orWhere(function ($query) {
                    $query->where('has_variants', true)
                        ->whereHas('variants', fn ($v) => $v->where('stock', '>', 0));
                });
            })
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%");
                });
            })
            // Category filter - allow inactive products
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->orderBy('name')
            ->limit(30)
            ->get();
    }

    #[Computed(persist: true)]
    public function categories()
    {
        return Cache::remember('pos_categories', 300, function () {
            return Product::query()
                ->where('stock', '>', 0)
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->pluck('category')
                ->sort()
                ->values();
        });
    }

    #[Computed]
    public function cartTotal(): int
    {
        return (int) collect($this->cart)->sum(fn ($item) => $item['price'] * $item['quantity']);
    }

    #[Computed]
    public function cartItemsCount(): int
    {
        return (int) collect($this->cart)->sum('quantity');
    }

    #[Computed]
    public function change(): int
    {
        return max(0, $this->paymentAmount - $this->cartTotal);
    }

    /**
     * Get enabled payment methods from configuration
     * Requirements: 5.1, 6.4
     */
    #[Computed]
    public function paymentMethods(): array
    {
        return app(PaymentConfigurationService::class)->getEnabledMethods();
    }

    /**
     * Get full payment configuration including QRIS/transfer details
     * Requirements: 5.2, 5.3, 5.4
     */
    #[Computed]
    public function paymentConfig(): array
    {
        $service = app(PaymentConfigurationService::class);

        return [
            'config' => $service->getAll(),
            'transfer_details' => $service->getTransferDetails(),
            'qris_image_url' => $service->getQrisImageUrl(),
        ];
    }

    public function handleBarcode(string $barcode): void
    {
        // Removed status check to allow inactive products
        $product = Product::where('sku', trim($barcode))->first();

        if ($product) {
            $this->addToCart($product->id);
        } else {
            $this->dispatch('toast', message: 'Produk tidak ditemukan', type: 'error');
        }
    }

    public function addToCart(int $productId): void
    {
        $product = Product::select(['id', 'name', 'sku', 'price', 'stock', 'image', 'has_variants', 'status']) // Add status to select
            ->with(['variants' => fn ($q) => $q->where('stock', '>', 0)])
            ->find($productId);

        if (! $product) {
            $this->dispatch('toast', message: 'Produk tidak ditemukan', type: 'error');

            return;
        }

        // Removed status check - allow inactive products to be added

        // If product has variants, show variant selection modal
        if ($product->has_variants) {
            $this->selectedProductId = $productId;
            $this->selectedProductName = $product->name;
            $this->productVariants = $product->variants->toArray();
            $this->groupedVariants = $this->getGroupedVariantsData($this->productVariants);
            $this->showVariantModal = true;

            return;
        }

        // Non-variant product - add directly
        if ($product->stock < 1) {
            $this->dispatch('toast', message: 'Stok tidak tersedia', type: 'error');

            return;
        }

        $cartKey = (string) $productId;
        $currentQty = $this->cart[$cartKey]['quantity'] ?? 0;

        if ($currentQty >= $product->stock) {
            $this->dispatch('toast', message: 'Stok tidak mencukupi', type: 'error');

            return;
        }

        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity']++;
        } else {
            $this->cart[$cartKey] = [
                'product_id' => $product->id,
                'variant_id' => null,
                'name' => $product->name,
                'price' => (int) $product->price,
                'quantity' => 1,
                'stock' => $product->stock,
                'image' => $product->image_thumbnail_url,
            ];
        }

        $this->dispatch('item-added');
    }

    public function addVariantToCart(int $variantId): void
    {
        $variant = ProductVariant::with('product')->find($variantId);

        if (! $variant || $variant->stock < 1) {
            $this->dispatch('toast', message: 'Stok varian tidak tersedia', type: 'error');

            return;
        }

        // Use variant_id as cart key for variant products
        $cartKey = 'v_'.$variantId;
        $currentQty = $this->cart[$cartKey]['quantity'] ?? 0;

        // Real-time stock validation - check current stock from database
        $freshStock = ProductVariant::where('id', $variantId)->value('stock');

        if ($currentQty >= $freshStock) {
            $this->dispatch('toast', message: "Stok tidak mencukupi. Tersedia: {$freshStock}", type: 'error');

            return;
        }

        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity']++;
            $this->cart[$cartKey]['stock'] = $freshStock; // Update stock info
        } else {
            $this->cart[$cartKey] = [
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'name' => $variant->variant_name,
                'price' => (int) $variant->price,
                'quantity' => 1,
                'stock' => $freshStock,
                'image' => $variant->product->image_thumbnail_url ?? null,
            ];
        }

        $this->closeVariantModal();
        $this->dispatch('item-added');
    }

    public function closeVariantModal(): void
    {
        $this->showVariantModal = false;
        $this->selectedProductId = null;
        $this->productVariants = [];
        $this->groupedVariants = [];
        $this->selectedProductName = null;
    }

    /**
     * Group variants by option type for better display
     * Requirements: 3.1, 3.2
     */
    protected function getGroupedVariantsData(array $variants): array
    {
        $grouped = [];

        foreach ($variants as $variant) {
            $optionValues = $variant['option_values'] ?? [];

            // Build group key from option values
            foreach ($optionValues as $optionSlug => $optionData) {
                $optionName = $optionData['option_name'] ?? ucfirst($optionSlug);

                if (! isset($grouped[$optionSlug])) {
                    $grouped[$optionSlug] = [
                        'option_name' => $optionName,
                        'values' => [],
                    ];
                }

                $value = $optionData['value'] ?? '';
                if (! isset($grouped[$optionSlug]['values'][$value])) {
                    $grouped[$optionSlug]['values'][$value] = [
                        'value' => $value,
                        'variants' => [],
                        'total_stock' => 0,
                        'min_price' => PHP_INT_MAX,
                        'max_price' => 0,
                    ];
                }

                // Add variant to this value group
                $grouped[$optionSlug]['values'][$value]['variants'][] = $variant;
                $grouped[$optionSlug]['values'][$value]['total_stock'] += $variant['stock'];
                $grouped[$optionSlug]['values'][$value]['min_price'] = min(
                    $grouped[$optionSlug]['values'][$value]['min_price'],
                    $variant['price']
                );
                $grouped[$optionSlug]['values'][$value]['max_price'] = max(
                    $grouped[$optionSlug]['values'][$value]['max_price'],
                    $variant['price']
                );
            }
        }

        // Convert values to indexed array and sort
        foreach ($grouped as $optionSlug => &$optionData) {
            $optionData['values'] = array_values($optionData['values']);
            // Sort values alphabetically
            usort($optionData['values'], fn ($a, $b) => strcmp($a['value'], $b['value']));
        }

        return $grouped;
    }

    public function incrementQty(string $cartKey): void
    {
        if (! isset($this->cart[$cartKey])) {
            return;
        }

        // Get fresh stock from database for real-time validation
        $item = $this->cart[$cartKey];
        if (! empty($item['variant_id'])) {
            $currentStock = ProductVariant::where('id', $item['variant_id'])->value('stock') ?? 0;
        } else {
            $currentStock = Product::where('id', $item['product_id'])->value('stock') ?? 0;
        }

        // Update stock in cart
        $this->cart[$cartKey]['stock'] = $currentStock;

        if ($this->cart[$cartKey]['quantity'] >= $currentStock) {
            $this->dispatch('toast', message: "Stok tidak mencukupi. Tersedia: {$currentStock}", type: 'error');

            return;
        }

        $this->cart[$cartKey]['quantity']++;
    }

    public function decrementQty(string $cartKey): void
    {
        if (! isset($this->cart[$cartKey])) {
            return;
        }

        if ($this->cart[$cartKey]['quantity'] <= 1) {
            $this->removeFromCart($cartKey);
        } else {
            $this->cart[$cartKey]['quantity']--;
        }
    }

    public function updateQuantity(string $cartKey, int $quantity): void
    {
        if (! isset($this->cart[$cartKey])) {
            return;
        }

        if ($quantity < 1) {
            $this->removeFromCart($cartKey);

            return;
        }

        if ($quantity > $this->cart[$cartKey]['stock']) {
            $this->dispatch('toast', message: 'Stok tidak mencukupi', type: 'error');

            return;
        }

        $this->cart[$cartKey]['quantity'] = $quantity;
    }

    public function removeFromCart(string $cartKey): void
    {
        unset($this->cart[$cartKey]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->paymentAmount = 0;
        $this->showPayment = false;
    }

    public function setQuickAmount(int $amount): void
    {
        $this->paymentAmount = $amount;
    }

    public function setExactAmount(): void
    {
        $this->paymentAmount = $this->cartTotal;
    }

    public function openPayment(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('toast', message: 'Keranjang kosong', type: 'error');

            return;
        }

        // Check if any payment methods are enabled - Requirements: 5.5
        $enabledMethods = app(PaymentConfigurationService::class)->getEnabledMethods();
        if (empty($enabledMethods)) {
            $this->dispatch('toast', message: 'Tidak ada metode pembayaran yang tersedia. Hubungi admin.', type: 'error');

            return;
        }

        // Set default payment method to first enabled method
        $this->paymentMethod = $enabledMethods[0]['id'];

        // Validate stock availability before opening payment
        $stockIssues = $this->validateCartStock();
        if (! empty($stockIssues)) {
            $this->dispatch('toast', message: $stockIssues[0], type: 'error');

            return;
        }

        $this->paymentAmount = 0;
        $this->showPayment = true;
    }

    /**
     * Validate stock availability for all cart items
     * Requirements: 3.5, 3.6
     *
     * @return array List of stock issues (empty if all valid)
     */
    public function validateCartStock(): array
    {
        $issues = [];

        foreach ($this->cart as $cartKey => $item) {
            if (! empty($item['variant_id'])) {
                // Variant product - check variant stock
                $currentStock = ProductVariant::where('id', $item['variant_id'])->value('stock');

                if ($currentStock === null) {
                    $issues[] = "Varian '{$item['name']}' tidak ditemukan";

                    continue;
                }

                if ($currentStock < $item['quantity']) {
                    $issues[] = "Stok '{$item['name']}' tidak mencukupi. Tersedia: {$currentStock}, Di keranjang: {$item['quantity']}";
                    // Update cart with current stock
                    $this->cart[$cartKey]['stock'] = $currentStock;
                }
            } else {
                // Non-variant product - check product stock
                $currentStock = Product::where('id', $item['product_id'])->value('stock');

                if ($currentStock === null) {
                    $issues[] = "Produk '{$item['name']}' tidak ditemukan";

                    continue;
                }

                if ($currentStock < $item['quantity']) {
                    $issues[] = "Stok '{$item['name']}' tidak mencukupi. Tersedia: {$currentStock}, Di keranjang: {$item['quantity']}";
                    // Update cart with current stock
                    $this->cart[$cartKey]['stock'] = $currentStock;
                }
            }
        }

        return $issues;
    }

    /**
     * Refresh stock information for all cart items
     */
    public function refreshCartStock(): void
    {
        foreach ($this->cart as $cartKey => $item) {
            if (! empty($item['variant_id'])) {
                $currentStock = ProductVariant::where('id', $item['variant_id'])->value('stock') ?? 0;
            } else {
                $currentStock = Product::where('id', $item['product_id'])->value('stock') ?? 0;
            }

            $this->cart[$cartKey]['stock'] = $currentStock;

            // Auto-adjust quantity if exceeds stock
            if ($this->cart[$cartKey]['quantity'] > $currentStock) {
                if ($currentStock > 0) {
                    $this->cart[$cartKey]['quantity'] = $currentStock;
                    $this->dispatch('toast', message: "Kuantitas '{$item['name']}' disesuaikan ke {$currentStock}", type: 'warning');
                } else {
                    unset($this->cart[$cartKey]);
                    $this->dispatch('toast', message: "'{$item['name']}' dihapus karena stok habis", type: 'warning');
                }
            }
        }
    }

    public function closePayment(): void
    {
        $this->showPayment = false;
    }

    public function toggleCart(): void
    {
        $this->showCart = ! $this->showCart;
    }

    /**
     * Get all students for client-side search (cached)
     */
    #[Computed]
    public function allStudents(): array
    {
        return Cache::remember('pos_all_students', 300, function () {
            return Student::query()
                ->select(['nim', 'full_name', 'points_balance'])
                ->orderBy('nim')
                ->get()
                ->toArray();
        });
    }

    public function processPayment(\App\Actions\Sales\ProcessTransactionAction $processTransaction): void
    {
        // No permission check needed - all authenticated users can access POS (akses_kasir is self-service)
        // Route middleware handles authentication

        if (empty($this->cart)) {
            $this->dispatch('toast', message: 'Keranjang kosong', type: 'error');

            return;
        }

        $student = null;
        $studentNim = trim($this->studentNim);
        if ($studentNim !== '') {
            $this->studentNim = $studentNim;
            $this->validate([
                'studentNim' => ['digits:9', 'exists:students,nim'],
            ]);
        }

        $total = $this->cartTotal;

        // For QRIS and Transfer, set payment amount to exact total
        if ($this->paymentMethod === 'qris' || $this->paymentMethod === 'transfer') {
            $this->paymentAmount = $total;
        }

        if ($this->paymentAmount < $total) {
            $this->dispatch('toast', message: 'Pembayaran kurang dari total', type: 'error');

            return;
        }

        // Final stock validation before processing - Requirements: 3.5, 3.6
        $stockIssues = $this->validateCartStock();
        if (! empty($stockIssues)) {
            $this->dispatch('toast', message: 'Stok berubah: '.$stockIssues[0], type: 'error');
            $this->showPayment = false;

            return;
        }

        try {
            $transactionData = new \App\Data\Sales\TransactionData();
            $transactionData->cashier_id = auth()->id();
            $transactionData->payment_method = $this->paymentMethod;
            $transactionData->payment_amount = $this->paymentAmount;
            $transactionData->student_nim = $studentNim ?: null;
            $transactionData->cart_items = $this->cart;

            $result = $processTransaction->execute($transactionData);

            if ($result['shu_points'] > 0) {
                $this->dispatch('toast', message: "Transaksi berhasil! Poin SHU +{$result['shu_points']} untuk NIM {$result['student_nim']}", type: 'success');
            } else {
                $this->dispatch('toast', message: 'Transaksi berhasil!', type: 'success');
            }

            // Dispatch success event for frontend (printing, etc)
            $this->dispatch('payment-success', $result);

            // Reset state
            $this->reset(['cart', 'paymentAmount', 'showPayment', 'showCart', 'studentNim']);
            $this->paymentMethod = 'cash';

            // Clear categories cache to reflect stock changes
            Cache::forget('pos_categories');

        } catch (\Exception $e) {
            report($e);
            if (app()->environment('testing')) {
                throw $e;
            }
            $this->dispatch('toast', message: $e->getMessage() ?: 'Gagal memproses transaksi', type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.cashier.point-of-sale')->layout('layouts.pos');
    }
}
