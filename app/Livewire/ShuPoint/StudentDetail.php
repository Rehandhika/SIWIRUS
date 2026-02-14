<?php

namespace App\Livewire\ShuPoint;

use App\Exports\ShuStudentTransactionsExport;
use App\Models\Student;
use App\Models\ShuPointTransaction;
use App\Services\ShuPointService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Title('Poin SHU - Detail Mahasiswa')]
class StudentDetail extends Component
{
    use WithPagination;

    public Student $student;

    public string $typeFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $search = '';

    public int $redeemPoints = 0;

    public ?int $redeemCashAmount = null;

    public string $redeemNotes = '';

    public int $adjustPoints = 0;

    public string $adjustNotes = '';

    protected int $perPage = 15;

    protected array $messages = [
        'redeemPoints.min' => 'Poin pencairan minimal 1',
        'redeemCashAmount.min' => 'Nominal pencairan tidak boleh negatif',
        'adjustPoints.not_in' => 'Perubahan poin tidak boleh 0',
    ];

    public function mount(Student $student): void
    {
        if (! auth()->user()->can('lihat_poin_shu')) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $this->student = $student;
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function transactions()
    {
        return ShuPointTransaction::query()
            ->with(['sale:id,invoice_number,total_amount', 'creator:id,name'])
            ->where('student_id', $this->student->id)
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('notes', 'like', "%{$this->search}%")
                        ->orWhereHas('sale', fn ($sq) => $sq->where('invoice_number', 'like', "%{$this->search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function redeem(): void
    {
        if (! auth()->user()->can('kelola_poin_shu')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki akses untuk pencairan poin.', type: 'error');
            return;
        }

        $this->validate([
            'redeemPoints' => ['required', 'integer', 'min:1'],
            'redeemCashAmount' => ['nullable', 'integer', 'min:0'],
            'redeemNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(ShuPointService::class)->redeemPoints(
                $this->student,
                $this->redeemPoints,
                $this->redeemCashAmount,
                $this->redeemNotes ?: null
            );

            $this->student->refresh();
            $this->reset(['redeemPoints', 'redeemCashAmount', 'redeemNotes']);
            $this->dispatch('toast', message: 'Pencairan poin berhasil dicatat', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage() ?: 'Gagal mencairkan poin', type: 'error');
        }
    }

    public function adjust(): void
    {
        if (! auth()->user()->can('kelola_poin_shu')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki akses untuk penyesuaian poin.', type: 'error');
            return;
        }

        $this->validate([
            'adjustPoints' => ['required', 'integer', 'not_in:0'],
            'adjustNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(ShuPointService::class)->adjustPoints(
                $this->student,
                $this->adjustPoints,
                $this->adjustNotes ?: null
            );

            $this->student->refresh();
            $this->reset(['adjustPoints', 'adjustNotes']);
            $this->dispatch('toast', message: 'Penyesuaian poin berhasil dicatat', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage() ?: 'Gagal menyesuaikan poin', type: 'error');
        }
    }

    public function exportExcel()
    {
        if (! auth()->user()->can('kelola_poin_shu')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki akses untuk export.', type: 'error');
            return;
        }

        return Excel::download(
            new ShuStudentTransactionsExport($this->student->id, $this->typeFilter, $this->dateFrom, $this->dateTo, $this->search),
            'poin-shu-transaksi-'.$this->student->nim.'.xlsx'
        );
    }

    public function render()
    {
        return view('livewire.shu-point.student-detail')
            ->layout('layouts.app')
            ->title('Poin SHU - Detail Mahasiswa');
    }
}

