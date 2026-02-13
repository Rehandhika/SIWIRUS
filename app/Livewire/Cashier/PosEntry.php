<?php

namespace App\Livewire\Cashier;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ShuPointTransaction;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\ShuPointService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PosEntry extends Component
{
    public string $selectedDate;

    public bool $showShuAdjustmentModal = false;

    public ?int $shuAdjustSaleId = null;

    public string $shuAdjustInvoiceNumber = '';

    public string $shuAdjustStudentNim = '';

    public string $shuAdjustStudentName = '';

    public int $shuAdjustOldPoints = 0;

    public int $shuAdjustNewPoints = 0;

    public string $shuAdjustNotes = '';

    public function mount(): void
    {
        if (! auth()->user()->hasAnyRole(['Super Admin', 'Ketua', 'Wakil Ketua'])) {
            $this->dispatch('toast', message: 'Akses ditolak.', type: 'error');
            $this->redirect(route('admin.dashboard'));

            return;
        }

        $this->selectedDate = now()->format('Y-m-d');
    }

    /**
     * Handle date change - notify frontend to clear draft for different date
     */
    public function updatedSelectedDate(): void
    {
        $this->dispatch('date-changed', date: $this->selectedDate);
    }

    #[Computed]
    public function products(): array
    {
        // Clear old cache keys if exist
        Cache::forget('pos_products_v2');

        return Cache::remember('pos_products_active', 300, fn () => Product::query()
            ->select('id', 'name', 'sku', 'price', 'stock')
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->toArray()
        );
    }

    /**
     * Get all students for client-side NIM autocomplete (cached)
     */
    #[Computed]
    public function allStudents(): array
    {
        return Cache::remember('pos_entry_all_students', 300, function () {
            return Student::query()
                ->select(['nim', 'full_name', 'points_balance'])
                ->orderBy('nim')
                ->get()
                ->toArray();
        });
    }

    #[Computed]
    public function dailySummary(): array
    {
        $result = Sale::query()
            ->whereDate('date', $this->selectedDate)
            ->selectRaw('
                COALESCE(SUM(total_amount), 0) as total,
                COUNT(*) as count,
                COALESCE(SUM(CASE WHEN payment_method = "cash" THEN total_amount ELSE 0 END), 0) as cash,
                COALESCE(SUM(CASE WHEN payment_method = "transfer" THEN total_amount ELSE 0 END), 0) as transfer,
                COALESCE(SUM(CASE WHEN payment_method = "qris" THEN total_amount ELSE 0 END), 0) as qris
            ')
            ->first();

        return [
            'total' => (float) ($result->total ?? 0),
            'count' => (int) ($result->count ?? 0),
            'cash' => (float) ($result->cash ?? 0),
            'transfer' => (float) ($result->transfer ?? 0),
            'qris' => (float) ($result->qris ?? 0),
        ];
    }

    #[Computed]
    public function transactions(): array
    {
        return Sale::query()
            ->with(['items:id,sale_id,product_name,quantity,price,subtotal'])
            ->whereDate('date', $this->selectedDate)
            ->select('id', 'invoice_number', 'student_id', 'shu_points_earned', 'total_amount', 'payment_method', 'created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->toArray();
    }

    public function openShuAdjustment(int $saleId): void
    {
        if (! auth()->user()->can('adjust.shu')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki akses untuk penyesuaian poin.', type: 'error');
            return;
        }

        $sale = Sale::query()
            ->with('student:id,nim,full_name')
            ->select('id', 'invoice_number', 'student_id', 'shu_points_earned')
            ->find($saleId);

        if (! $sale) {
            $this->dispatch('toast', message: 'Transaksi tidak ditemukan.', type: 'error');
            return;
        }

        if (! $sale->student_id || ! $sale->student) {
            $this->dispatch('toast', message: 'Transaksi ini tidak terkait mahasiswa.', type: 'error');
            return;
        }

        $this->shuAdjustSaleId = $sale->id;
        $this->shuAdjustInvoiceNumber = (string) ($sale->invoice_number ?? '');
        $this->shuAdjustStudentNim = (string) ($sale->student->nim ?? '');
        $this->shuAdjustStudentName = (string) ($sale->student->full_name ?? '');
        $this->shuAdjustOldPoints = (int) $sale->shu_points_earned;
        $this->shuAdjustNewPoints = (int) $sale->shu_points_earned;
        $this->shuAdjustNotes = '';
        $this->showShuAdjustmentModal = true;
    }

    public function closeShuAdjustment(): void
    {
        $this->reset([
            'showShuAdjustmentModal',
            'shuAdjustSaleId',
            'shuAdjustInvoiceNumber',
            'shuAdjustStudentNim',
            'shuAdjustStudentName',
            'shuAdjustOldPoints',
            'shuAdjustNewPoints',
            'shuAdjustNotes',
        ]);
        $this->resetValidation();
    }

    public function saveShuAdjustment(): void
    {
        if (! auth()->user()->can('adjust.shu')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki akses untuk penyesuaian poin.', type: 'error');
            return;
        }

        $this->validate([
            'shuAdjustSaleId' => ['required', 'integer'],
            'shuAdjustNewPoints' => ['required', 'integer', 'min:0'],
            'shuAdjustNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $sale = Sale::findOrFail($this->shuAdjustSaleId);
            app(ShuPointService::class)->adjustSalePoints($sale, $this->shuAdjustNewPoints, $this->shuAdjustNotes ?: null);

            $this->dispatch('toast', message: 'Penyesuaian poin berhasil disimpan.', type: 'success');
            $this->closeShuAdjustment();
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage() ?: 'Gagal menyimpan penyesuaian poin.', type: 'error');
        }
    }

    /**
     * Submit all valid rows as transactions
     */
    public function submitAll(array $rows): void
    {
        if (empty($rows)) {
            $this->dispatch('toast', message: 'Tidak ada data untuk disimpan.', type: 'error');

            return;
        }

        try {
            // Validate rows first
            $validationResult = $this->validateRows($rows);
            if (! empty($validationResult['errors'])) {
                $this->dispatch('toast', message: implode(', ', $validationResult['errors']), type: 'error');

                return;
            }

            $count = $this->processBatchInsert($validationResult['validRows']);

            // Clear cache and refresh
            Cache::forget('pos_products_active');

            $this->dispatch('toast', message: "Berhasil menyimpan {$count} transaksi.", type: 'success');
            $this->dispatch('transactions-saved');

        } catch (\Exception $e) {
            Log::error('POS Entry Error: '.$e->getMessage(), [
                'rows' => $rows,
                'date' => $this->selectedDate,
                'user_id' => auth()->id(),
            ]);
            $this->dispatch('toast', message: 'Gagal menyimpan: '.$e->getMessage(), type: 'error');
        }
    }

    /**
     * Validate rows before processing
     */
    private function validateRows(array $rows): array
    {
        $errors = [];
        $validRows = [];

        // Get current stock for validation
        $productIds = array_unique(array_filter(array_column($rows, 'product_id')));
        if (empty($productIds)) {
            return ['errors' => ['Tidak ada produk yang dipilih'], 'validRows' => []];
        }

        $products = Product::whereIn('id', $productIds)
            ->select('id', 'name', 'price', 'stock')
            ->get()
            ->keyBy('id');

        $studentNims = array_unique(array_filter(array_map(function ($row) {
            $nim = $row['student_nim'] ?? '';
            $nim = preg_replace('/\D+/', '', trim((string) $nim));
            return $nim !== '' ? $nim : null;
        }, $rows)));

        $studentsByNim = [];
        if (! empty($studentNims)) {
            $studentsByNim = Student::query()
                ->select('id', 'nim')
                ->whereIn('nim', $studentNims)
                ->get()
                ->keyBy('nim')
                ->toArray();
        }

        $stockUsage = [];

        foreach ($rows as $index => $row) {
            $productId = $row['product_id'] ?? null;
            if (! $productId) {
                continue;
            }

            $product = $products->get($productId);
            if (! $product) {
                $errors[] = 'Baris '.($index + 1).': Produk tidak ditemukan';

                continue;
            }

            $nim = preg_replace('/\D+/', '', trim((string) ($row['student_nim'] ?? '')));
            $studentId = null;
            if ($nim !== '') {
                if (strlen($nim) !== 9) {
                    $errors[] = 'Baris '.($index + 1).': NIM harus 9 digit angka';
                    continue;
                }

                $studentId = $studentsByNim[$nim]['id'] ?? null;
                if (! $studentId) {
                    $errors[] = 'Baris '.($index + 1).': NIM tidak terdaftar';
                    continue;
                }
            }

            $qty = max(1, (int) ($row['qty'] ?? 1));
            $stockUsage[$productId] = ($stockUsage[$productId] ?? 0) + $qty;

            // Check stock availability
            if ($stockUsage[$productId] > $product->stock) {
                $errors[] = "{$product->name}: Stok tidak cukup (tersedia: {$product->stock})";

                continue;
            }

            $validRows[] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'student_id' => $studentId,
                'qty' => $qty,
                'price' => (float) $product->price,
                'payment_method' => in_array($row['payment_method'] ?? '', ['cash', 'transfer', 'qris'])
                    ? $row['payment_method']
                    : 'cash',
            ];
        }

        return ['errors' => $errors, 'validRows' => $validRows];
    }

    /**
     * Process batch insert with proper transaction handling
     */
    private function processBatchInsert(array $salesData): int
    {
        if (empty($salesData)) {
            throw new \Exception('Tidak ada data valid untuk disimpan.');
        }

        return DB::transaction(function () use ($salesData) {
            $now = now();
            $cashierId = auth()->id();
            $date = $this->selectedDate;
            $conversionAmount = app(ShuPointService::class)->getConversionAmount();

            // Generate invoice numbers inside transaction with lock
            $invoices = Sale::generateBatchInvoiceNumbers(count($salesData), $date);

            // Prepare sales data
            $salesToInsert = [];
            $stockUpdates = [];
            $transactionsToInsert = [];
            $pointsByStudent = [];

            foreach ($salesData as $i => $data) {
                $subtotal = $data['qty'] * $data['price'];
                $amount = (int) round($subtotal);
                $studentId = $data['student_id'] ?? null;
                $points = $studentId ? app(ShuPointService::class)->computeEarnedPoints($amount, $conversionAmount) : 0;
                if ($studentId) {
                    $pointsByStudent[$studentId] = ($pointsByStudent[$studentId] ?? 0) + $points;
                }

                $salesToInsert[] = [
                    'cashier_id' => $cashierId,
                    'student_id' => $studentId,
                    'invoice_number' => $invoices[$i],
                    'date' => $date,
                    'total_amount' => $subtotal,
                    'payment_method' => $data['payment_method'],
                    'payment_amount' => $subtotal,
                    'change_amount' => 0,
                    'shu_points_earned' => $points,
                    'shu_percentage_bps' => $studentId ? $conversionAmount : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $stockUpdates[$data['product_id']] = ($stockUpdates[$data['product_id']] ?? 0) + $data['qty'];
            }

            // Insert sales
            Sale::insert($salesToInsert);

            $saleIdsByInvoice = Sale::query()
                ->whereIn('invoice_number', $invoices)
                ->pluck('id', 'invoice_number')
                ->toArray();

            if (count($saleIdsByInvoice) !== count($invoices)) {
                throw new \Exception('Gagal mengambil ID transaksi yang baru dibuat.');
            }

            // Prepare sale items
            $itemsToInsert = [];
            foreach ($salesData as $i => $data) {
                $saleId = $saleIdsByInvoice[$invoices[$i]] ?? null;
                if (! $saleId) {
                    throw new \Exception('Gagal memetakan transaksi tersimpan.');
                }

                $itemsToInsert[] = [
                    'sale_id' => $saleId,
                    'product_id' => $data['product_id'],
                    'product_name' => $data['product_name'],
                    'quantity' => $data['qty'],
                    'price' => $data['price'],
                    'subtotal' => $data['qty'] * $data['price'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $studentId = $data['student_id'] ?? null;
                if ($studentId) {
                    $amount = (int) round($data['qty'] * $data['price']);
                    $points = app(ShuPointService::class)->computeEarnedPoints($amount, $conversionAmount);

                    $transactionsToInsert[] = [
                        'student_id' => $studentId,
                        'sale_id' => $saleId,
                        'type' => 'earn',
                        'amount' => $amount,
                        'percentage_bps' => $conversionAmount,
                        'points' => $points,
                        'cash_amount' => null,
                        'notes' => null,
                        'created_by' => $cashierId,
                        'created_at' => $now,
                    ];
                }
            }

            SaleItem::insert($itemsToInsert);

            if (! empty($pointsByStudent)) {
                $lockedStudents = Student::query()
                    ->whereIn('id', array_keys($pointsByStudent))
                    ->lockForUpdate()
                    ->get(['id', 'points_balance']);

                foreach ($lockedStudents as $lockedStudent) {
                    $lockedStudent->points_balance += (int) ($pointsByStudent[$lockedStudent->id] ?? 0);
                    $lockedStudent->save();
                }

                if (! empty($transactionsToInsert)) {
                    ShuPointTransaction::insert($transactionsToInsert);
                }
            }

            // Update stock
            foreach ($stockUpdates as $productId => $qty) {
                Product::where('id', $productId)->decrement('stock', $qty);
            }

            return count($salesData);
        });
    }

    /**
     * Delete a transaction and restore stock
     */
    public function deleteTransaction(int $id): void
    {
        try {
            DB::transaction(function () use ($id) {
                $sale = Sale::with('items:id,sale_id,product_id,quantity')->findOrFail($id);
                $invoiceNumber = $sale->invoice_number;

                app(ShuPointService::class)->reverseSalePoints($sale);

                // Restore stock
                foreach ($sale->items as $item) {
                    Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }

                // Delete items and sale
                $sale->items()->delete();
                $sale->delete();

                // Log activity
                ActivityLogService::logSaleDeleted($invoiceNumber);
            });

            Cache::forget('pos_products_active');
            $this->dispatch('toast', message: 'Transaksi berhasil dihapus.', type: 'success');

        } catch (\Exception $e) {
            Log::error('Delete Transaction Error: '.$e->getMessage(), ['sale_id' => $id]);
            $this->dispatch('toast', message: 'Gagal menghapus: '.$e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.cashier.pos-entry')
            ->layout('layouts.app')
            ->title('Entry Transaksi');
    }
}
