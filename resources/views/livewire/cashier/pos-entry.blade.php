<div class="p-4 space-y-4"
    x-data="posEntry(@js($this->allStudents))"
    x-init="initProducts(@js($this->products), @js($this->selectedDate))"
    @date-changed.window="handleDateChange($event.detail.date)"
    @transactions-saved.window="onTransactionsSaved()"
    wire:ignore.self>
    
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-semibold text-gray-900">Entry Transaksi</h1>
            <input type="date" wire:model.live="selectedDate" max="{{ now()->format('Y-m-d') }}"
                class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            <span x-show="isDraft" x-cloak class="text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded">Draft</span>
        </div>
        <div class="text-right">
            <span class="text-sm text-gray-500">Total Hari Ini</span>
            <p class="text-xl font-bold text-blue-600">Rp {{ number_format($this->dailySummary['total'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Entry Form --}}
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
            <span class="font-medium text-gray-700">Entry Baru</span>
            <div class="flex items-center gap-3">
                <button x-show="isDraft" @click="clearDraft(); resetRows()" class="text-sm text-gray-500 hover:text-gray-700">
                    Reset
                </button>
                <button @click="submitAll()" :disabled="!hasValidRows() || isSubmitting"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="submitAll">Simpan Semua</span>
                    <span wire:loading wire:target="submitAll">Menyimpan...</span>
                </button>
            </div>
        </div>
        
        {{-- Table --}}
        <div class="overflow-visible">
            <table class="w-full">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium w-48">Produk</th>
                        <th class="px-4 py-3 text-left font-medium w-40">NIM</th>
                        <th class="px-4 py-3 text-center font-medium w-20">Qty</th>
                        <th class="px-4 py-3 text-right font-medium w-28">Total</th>
                        <th class="px-4 py-3 text-center font-medium w-28">Bayar</th>
                        <th class="px-4 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(row, idx) in rows" :key="row.id">
                        <tr :class="row.product_id ? 'bg-green-50/50' : ''" class="relative">
                            <td class="px-4 py-3 relative w-48" style="overflow: visible;">
                                <div class="relative">
                                    <input type="text" x-model="row.search"
                                        @focus="openDropdown(idx)"
                                        @input="openDropdown(idx); row.product_id = null; row.price = 0"
                                        @keydown.escape="closeDropdown(idx)"
                                        @keydown.enter.prevent="selectFirstProduct(idx)"
                                        placeholder="Cari produk..."
                                        class="w-full px-3 py-2 text-sm border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 truncate"
                                        :class="row.product_id ? 'border-green-400 bg-green-50' : 'border-gray-300'">
                                    
                                    <div x-show="row.showDropdown && row.search.length > 0" x-transition
                                        @click.away="closeDropdown(idx)"
                                        class="absolute left-0 right-0 mt-1 bg-white border rounded-md shadow-xl max-h-48 overflow-auto"
                                        style="z-index: 9999;">
                                        <template x-for="p in filterProducts(row.search)" :key="p.id">
                                            <button type="button" @click="selectProduct(idx, p)"
                                                class="w-full px-3 py-2 text-left text-sm hover:bg-blue-50 flex justify-between items-center border-b border-gray-100 last:border-0">
                                                <span x-text="p.name"></span>
                                                <span class="text-blue-600 font-medium" x-text="formatRupiah(p.price)"></span>
                                            </button>
                                        </template>
                                        <div x-show="filterProducts(row.search).length === 0" class="px-3 py-3 text-sm text-gray-400 text-center">
                                            Tidak ditemukan
                                        </div>
                                    </div>
                                </div>
                            </td>
                        <td class="px-4 py-3 relative w-40" style="overflow: visible;">
                            <div class="relative">
                                <input type="text" x-model="row.student_nim"
                                    @input="filterStudents(idx)"
                                    @focus="openStudentDropdown(idx)"
                                    @keydown.escape="closeStudentDropdown(idx)"
                                    @keydown.enter.prevent="selectFirstStudent(idx)"
                                    inputmode="numeric" maxlength="9"
                                    placeholder="NIM (opsional)"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                    :class="row.student_nim ? 'border-green-400 bg-green-50' : ''">

                                {{-- Student Suggestions Dropdown --}}
                                <div x-show="row.showStudentDropdown && row.student_nim.length >= 2" x-transition
                                    @click.away="closeStudentDropdown(idx)"
                                    class="absolute left-0 right-0 mt-1 bg-white border rounded-md shadow-xl max-h-48 overflow-auto"
                                    style="z-index: 9999;">
                                    <template x-for="s in filterStudentsList(row.student_nim)" :key="s.nim">
                                        <button type="button" @click="selectStudent(idx, s)"
                                            class="w-full px-3 py-2 text-left text-sm hover:bg-blue-50 flex justify-between items-center border-b border-gray-100 last:border-0">
                                            <div>
                                                <span class="font-medium" x-text="s.nim"></span>
                                                <span class="text-gray-500 text-xs block" x-text="s.full_name"></span>
                                            </div>
                                            <span class="text-blue-600 text-xs font-medium" x-text="s.points_balance.toLocaleString('id-ID') + ' poin'"></span>
                                        </button>
                                    </template>
                                    <div x-show="filterStudentsList(row.student_nim).length === 0" class="px-3 py-3 text-sm text-gray-400 text-center">
                                        Tidak ditemukan
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 w-20">
                            <input type="number" x-model.number="row.qty" min="1" :disabled="!row.product_id"
                                @change="validateQty(row); saveDraft()"
                                @keydown.enter.prevent="addRowAndFocus()"
                                class="w-full px-2 py-2 text-sm text-center border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                        </td>
                        <td class="px-4 py-3 text-right w-28">
                            <span class="text-sm font-semibold" :class="row.product_id ? 'text-gray-900' : 'text-gray-300'"
                                x-text="formatRupiah(row.price * row.qty)"></span>
                        </td>
                        <td class="px-4 py-3 w-28">
                            <select x-model="row.payment_method" @change="saveDraft()"
                                class="w-full px-2 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                <option value="cash">Cash</option>
                                <option value="transfer">Transfer</option>
                                <option value="qris">QRIS</option>
                            </select>
                        </td>
                        <td class="px-4 py-3 text-center w-10">
                            <button @click="removeRow(idx)" x-show="rows.length > 1"
                                class="p-1 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
            <tfoot class="bg-gray-50 border-t">
                <tr>
                    <td class="px-4 py-3">
                        <button @click="addRowAndFocus()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            + Tambah
                        </button>
                    </td>
                    <td class="px-4 py-3"></td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600" x-text="getTotalQty() + ' item'"></td>
                    <td class="px-4 py-3 text-right">
                        <span class="text-base font-bold text-blue-600" x-text="formatRupiah(getGrandTotal())"></span>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Saved Transactions --}}
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="px-4 py-3 bg-gray-50 border-b">
            <span class="font-medium text-gray-700">Tersimpan ({{ $this->dailySummary['count'] }})</span>
        </div>
        
        @if(count($this->transactions) > 0)
        <div class="max-h-80 overflow-y-auto">
            <table class="w-full">
                <tbody class="divide-y divide-gray-100">
                    @foreach($this->transactions as $tx)
                        @foreach($tx['items'] as $itemIdx => $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">{{ $item['product_name'] }}</td>
                            <td class="px-4 py-3 text-sm text-center text-gray-500 w-24">{{ $item['quantity'] }}x</td>
                            <td class="px-4 py-3 text-sm text-right font-medium w-32">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center w-32">
                                <span class="text-xs px-2 py-1 rounded-full 
                                    {{ $tx['payment_method'] === 'cash' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $tx['payment_method'] === 'transfer' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $tx['payment_method'] === 'qris' ? 'bg-blue-100 text-blue-700' : '' }}">
                                    {{ ucfirst($tx['payment_method']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center w-20">
                                @if($itemIdx === 0)
                                <div class="flex items-center justify-center gap-1">
                                    @if(!empty($tx['student_id']))
                                        <button type="button" wire:click="openShuAdjustment({{ $tx['id'] }})"
                                            class="p-1 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded" title="Penyesuaian Poin SHU">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    @endif
                                    <button type="button" wire:click="deleteTransaction({{ $tx['id'] }})" wire:confirm="Hapus transaksi ini?"
                                        class="p-1 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded" title="Hapus transaksi">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-4 py-12 text-center text-gray-400">
            Belum ada transaksi untuk tanggal ini
        </div>
        @endif
    </div>

    @if($showShuAdjustmentModal)
        <div class="fixed inset-0 z-50 flex items-end lg:items-center lg:justify-center p-0 lg:p-4">
            <div wire:click="closeShuAdjustment" class="absolute inset-0 bg-black/50"></div>

            <div class="relative w-full lg:max-w-md lg:mx-auto bg-white dark:bg-gray-800 rounded-t-2xl lg:rounded-2xl max-h-[85vh] lg:max-h-[80vh] flex flex-col" @click.stop>
                <header class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Penyesuaian Poin SHU</h3>
                    <button type="button" wire:click="closeShuAdjustment" class="p-1.5 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </header>

                <div class="flex-1 overflow-y-auto p-5 space-y-4">
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                        <div class="text-xs text-gray-500">Invoice</div>
                        <div class="font-mono font-semibold text-gray-900 dark:text-white">{{ $shuAdjustInvoiceNumber }}</div>
                        <div class="mt-2 text-xs text-gray-500">Mahasiswa</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $shuAdjustStudentNim }} - {{ $shuAdjustStudentName }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Poin Saat Ini</label>
                            <input type="number" value="{{ $shuAdjustOldPoints }}" disabled class="w-full px-4 py-2.5 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-200">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Poin Baru</label>
                            <input type="number" min="0" wire:model.defer="shuAdjustNewPoints" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl">
                            @error('shuAdjustNewPoints') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Catatan (Opsional)</label>
                        <input type="text" wire:model.defer="shuAdjustNotes" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl">
                        @error('shuAdjustNotes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <footer class="border-t border-gray-200 dark:border-gray-700 p-4 flex items-center justify-end gap-2 bg-gray-50 dark:bg-gray-900/40">
                    <button type="button" wire:click="closeShuAdjustment" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold rounded-xl">Batal</button>
                    <button type="button" wire:click="saveShuAdjustment" class="px-4 py-2.5 bg-primary-600 text-white font-semibold rounded-xl">Simpan</button>
                </footer>
            </div>
        </div>
    @endif

    {{-- Summary Footer --}}
    <div class="bg-white rounded-lg shadow-sm border p-4">
        <div class="flex items-center justify-between">
            <div class="flex gap-6 text-sm">
                <div>
                    <span class="text-gray-500">Cash</span>
                    <p class="font-semibold text-green-600">Rp {{ number_format($this->dailySummary['cash'], 0, ',', '.') }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Transfer</span>
                    <p class="font-semibold text-blue-600">Rp {{ number_format($this->dailySummary['transfer'], 0, ',', '.') }}</p>
                </div>
                <div>
                    <span class="text-gray-500">QRIS</span>
                    <p class="font-semibold text-blue-600">Rp {{ number_format($this->dailySummary['qris'], 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-gray-500 text-sm">Total</span>
                <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($this->dailySummary['total'], 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
</div>

@script
<script>
Alpine.data('posEntry', (students) => ({
    products: [],
    students: students || [],
    currentDate: '',
    rows: [],
    isDraft: false,
    counter: 0,
    isSubmitting: false,
    studentDebounceTimer: null,

    initProducts(products, date) {
        this.products = products || [];
        this.currentDate = date;
        this.loadDraft();
        if (!this.rows.length) this.addRow();

        document.addEventListener('keydown', e => {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                this.submitAll();
            }
        });
    },
    
    getDraftKey() {
        return `pos_draft_${this.currentDate}`;
    },
    
    loadDraft() {
        try {
            const saved = localStorage.getItem(this.getDraftKey());
            if (saved) {
                const data = JSON.parse(saved);
                this.rows = data.rows || [];
                this.counter = data.counter || 0;
                this.isDraft = this.rows.some(r => r.product_id);
            }
        } catch (e) {
            console.error('Load draft error:', e);
        }
    },
    
    saveDraft() {
        if (this.rows.some(r => r.product_id)) {
            localStorage.setItem(this.getDraftKey(), JSON.stringify({
                rows: this.rows,
                counter: this.counter
            }));
            this.isDraft = true;
        }
    },
    
    clearDraft() {
        localStorage.removeItem(this.getDraftKey());
        this.isDraft = false;
    },
    
    handleDateChange(newDate) {
        this.currentDate = newDate;
        this.rows = [];
        this.counter = 0;
        this.isDraft = false;
        this.loadDraft();
        if (!this.rows.length) this.addRow();
    },
    
    onTransactionsSaved() {
        this.clearDraft();
        this.rows = [];
        this.counter = 0;
        this.addRow();
    },
    
    addRow() {
        this.rows.push({
            id: ++this.counter,
            product_id: null,
            search: '',
            name: '',
            student_nim: '',
            qty: 1,
            price: 0,
            payment_method: 'cash',
            showDropdown: false,
            showStudentDropdown: false
        });
    },
    
    addRowAndFocus() {
        this.addRow();
        this.$nextTick(() => {
            const inputs = document.querySelectorAll('input[placeholder="Cari produk..."]');
            if (inputs.length > 0) {
                inputs[inputs.length - 1].focus();
            }
        });
    },
    
    removeRow(idx) {
        if (this.rows.length > 1) {
            this.rows.splice(idx, 1);
            this.saveDraft();
        }
    },
    
    resetRows() {
        this.rows = [];
        this.counter = 0;
        this.addRow();
    },
    
    openDropdown(idx) {
        this.rows[idx].showDropdown = true;
    },

    closeDropdown(idx) {
        this.rows[idx].showDropdown = false;
    },

    openStudentDropdown(idx) {
        if (this.rows[idx].student_nim.length >= 2) {
            this.rows[idx].showStudentDropdown = true;
        }
    },

    closeStudentDropdown(idx) {
        this.rows[idx].showStudentDropdown = false;
    },

    filterStudents(idx) {
        clearTimeout(this.studentDebounceTimer);
        this.studentDebounceTimer = setTimeout(() => {
            if (this.rows[idx].student_nim.length >= 2) {
                this.rows[idx].showStudentDropdown = true;
            } else {
                this.rows[idx].showStudentDropdown = false;
            }
            this.saveDraft();
        }, 150);
    },

    filterStudentsList(search) {
        if (!search || !this.students.length) return [];
        const term = search.toLowerCase().trim();
        return this.students
            .filter(s => s.nim.toLowerCase().includes(term) || s.full_name.toLowerCase().includes(term))
            .slice(0, 5);
    },

    selectStudent(idx, student) {
        this.rows[idx].student_nim = student.nim;
        this.rows[idx].showStudentDropdown = false;
        this.saveDraft();
    },

    selectFirstStudent(idx) {
        const filtered = this.filterStudentsList(this.rows[idx].student_nim);
        if (filtered.length > 0) {
            this.selectStudent(idx, filtered[0]);
        }
    },

    filterProducts(search) {
        if (!search || !this.products.length) return [];
        const term = search.toLowerCase();
        return this.products
            .filter(p => p.name.toLowerCase().includes(term) || (p.sku && p.sku.toLowerCase().includes(term)))
            .slice(0, 8);
    },
    
    selectProduct(idx, product) {
        this.rows[idx].product_id = product.id;
        this.rows[idx].name = product.name;
        this.rows[idx].search = product.name;
        this.rows[idx].price = parseFloat(product.price);
        this.rows[idx].showDropdown = false;
        this.saveDraft();
    },
    
    selectFirstProduct(idx) {
        const filtered = this.filterProducts(this.rows[idx].search);
        if (filtered.length > 0) {
            this.selectProduct(idx, filtered[0]);
        }
    },
    
    validateQty(row) {
        if (!row.product_id) return;
        const product = this.products.find(p => p.id === row.product_id);
        if (product) {
            row.qty = Math.max(1, Math.min(row.qty || 1, product.stock));
        }
    },
    
    getMaxStock(productId) {
        if (!productId) return 999;
        const product = this.products.find(p => p.id === productId);
        return product ? product.stock : 999;
    },
    
    hasValidRows() {
        return this.rows.some(r => r.product_id && r.qty > 0);
    },
    
    getTotalQty() {
        return this.rows.reduce((sum, r) => sum + (r.product_id ? r.qty : 0), 0);
    },
    
    getGrandTotal() {
        return this.rows.reduce((sum, r) => sum + (r.product_id ? r.price * r.qty : 0), 0);
    },
    
    formatRupiah(amount) {
        return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
    },
    
    async submitAll() {
        if (this.isSubmitting) return;
        
        const validRows = this.rows
            .filter(r => r.product_id && r.qty > 0)
            .map(r => ({
                product_id: r.product_id,
                student_nim: r.student_nim,
                qty: r.qty,
                payment_method: r.payment_method
            }));
        
        if (validRows.length === 0) return;
        
        this.isSubmitting = true;
        try {
            await this.$wire.submitAll(validRows);
        } finally {
            this.isSubmitting = false;
        }
    }
}));
</script>
@endscript
