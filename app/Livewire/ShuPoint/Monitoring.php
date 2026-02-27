<?php

namespace App\Livewire\ShuPoint;

use App\Exports\ShuRedemptionsExport;
use App\Exports\ShuStudentsExport;
use App\Models\Student;
use App\Models\ShuPointTransaction;
use App\Services\ShuPointService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Title('Poin SHU - Monitoring & Pencairan')]
class Monitoring extends Component
{
    use WithPagination;

    // Tab navigation
    public string $activeTab = 'students'; // 'students', 'redemptions'

    // Students tab properties
    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortField = 'nim';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    // Redemptions tab properties
    public string $studentNim = '';

    public int $points = 0;

    public ?int $cash_amount = null;

    public string $notes = '';

    #[Url(as: 'rq')]
    public string $redemptionSearch = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    // Student modal CRUD
    public bool $showStudentModal = false;

    public bool $editMode = false;

    public ?int $studentId = null;

    public string $nim = '';

    public string $full_name = '';

    protected int $perPage = 15;

    protected array $messages = [
        'studentNim.required' => 'NIM wajib diisi',
        'studentNim.digits' => 'NIM harus 9 digit angka',
        'studentNim.exists' => 'NIM tidak terdaftar',
        'points.min' => 'Poin pencairan minimal 1',
        'cash_amount.min' => 'Nominal pencairan tidak boleh negatif',
        'nim.required' => 'NIM wajib diisi',
        'nim.digits' => 'NIM harus 9 digit angka',
        'nim.unique' => 'NIM sudah terdaftar',
        'full_name.required' => 'Nama lengkap wajib diisi',
    ];

    public function mount(): void
    {
        if (! auth()->user()->can('lihat_poin_shu')) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    // Tab switching
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // Students tab methods
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    #[Computed]
    public function students()
    {
        $allowedSorts = ['nim', 'full_name', 'points_balance', 'created_at'];
        $sortField = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'nim';
        $sortDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        return Student::query()
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('nim', 'like', "%{$this->search}%")
                        ->orWhere('full_name', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate($this->perPage);
    }

    // Student CRUD methods
    public function createStudent(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showStudentModal = true;
    }

    public function editStudent(int $id): void
    {
        $student = Student::findOrFail($id);
        $this->studentId = $student->id;
        $this->nim = $student->nim;
        $this->full_name = $student->full_name;
        $this->editMode = true;
        $this->showStudentModal = true;
    }

    public function saveStudent(): void
    {
        $this->validate([
            'nim' => [
                'required',
                'digits:9',
                Rule::unique('students', 'nim')->ignore($this->studentId),
            ],
            'full_name' => ['required', 'string', 'max:255'],
        ]);

        try {
            if ($this->editMode) {
                $student = Student::findOrFail($this->studentId);
                $student->update([
                    'nim' => $this->nim,
                    'full_name' => $this->full_name,
                ]);
                $message = 'Mahasiswa berhasil diperbarui';
            } else {
                Student::create([
                    'nim' => $this->nim,
                    'full_name' => $this->full_name,
                ]);
                $message = 'Mahasiswa berhasil ditambahkan';
            }

            $this->dispatch('toast', message: $message, type: 'success');
            $this->closeStudentModal();
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Terjadi kesalahan: '.$e->getMessage(), type: 'error');
        }
    }

    public function deleteStudent(int $id): void
    {
        try {
            $student = Student::findOrFail($id);
            if ($student->sales()->exists() || $student->shuPointTransactions()->exists()) {
                $this->dispatch('toast', message: 'Mahasiswa tidak dapat dihapus karena sudah memiliki transaksi/riwayat Poin SHU', type: 'error');
                return;
            }
            $student->delete();
            $this->dispatch('toast', message: 'Mahasiswa berhasil dihapus', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal menghapus: '.$e->getMessage(), type: 'error');
        }
    }

    public function closeStudentModal(): void
    {
        $this->showStudentModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->studentId = null;
        $this->nim = '';
        $this->full_name = '';
    }

    public function exportStudentsExcel()
    {
        if (! auth()->user()->can('kelola_poin_shu')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki akses untuk export.', type: 'error');
            return;
        }

        ActivityLogService::log("Mengekspor data Poin SHU Mahasiswa");
        return Excel::download(new ShuStudentsExport($this->search), 'poin-shu-mahasiswa.xlsx');
    }

    // Redemptions tab methods
    public function updatedRedemptionSearch(): void
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

    #[Computed]
    public function redemptions()
    {
        return ShuPointTransaction::query()
            ->with(['student:id,nim,full_name', 'creator:id,name'])
            ->where('type', 'redeem')
            ->when($this->redemptionSearch, function ($q) {
                $q->whereHas('student', function ($sq) {
                    $sq->where('nim', 'like', "%{$this->redemptionSearch}%")
                        ->orWhere('full_name', 'like', "%{$this->redemptionSearch}%");
                });
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function redeem(): void
    {
        if (! auth()->user()->can('kelola_poin_shu')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki akses untuk pencairan poin.', type: 'error');
            return;
        }

        $this->studentNim = trim($this->studentNim);

        $this->validate([
            'studentNim' => ['required', 'digits:9', 'exists:students,nim'],
            'points' => ['required', 'integer', 'min:1'],
            'cash_amount' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $student = Student::where('nim', $this->studentNim)->firstOrFail();
            app(ShuPointService::class)->redeemPoints($student, $this->points, $this->cash_amount, $this->notes ?: null);

            $this->reset(['studentNim', 'points', 'cash_amount', 'notes']);
            $this->dispatch('toast', message: 'Pencairan poin berhasil dicatat', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage() ?: 'Gagal mencairkan poin', type: 'error');
        }
    }

    public function exportRedemptionsExcel()
    {
        if (! auth()->user()->can('kelola_poin_shu')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki akses untuk export.', type: 'error');
            return;
        }

        ActivityLogService::log("Mengekspor data pencairan Poin SHU");
        return Excel::download(new ShuRedemptionsExport($this->redemptionSearch, $this->dateFrom, $this->dateTo), 'poin-shu-pencairan.xlsx');
    }

    public function render()
    {
        return view('livewire.shu-point.monitoring')
            ->layout('layouts.app')
            ->title('Poin SHU - Monitoring & Pencairan');
    }
}
