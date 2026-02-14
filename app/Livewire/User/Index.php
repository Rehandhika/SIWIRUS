<?php

namespace App\Livewire\User;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Title('Manajemen Anggota')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'role')]
    public string $roleFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public bool $showModal = false;

    public bool $editMode = false;

    public ?int $userId = null;

    public string $nim = '';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public array $selectedRoles = [];

    public string $status = 'active';

    protected int $perPage = 15;

    protected function rules(): array
    {
        $rules = [
            'nim' => ['required', 'string', 'max:20', 'unique:users,nim'.($this->editMode ? ','.$this->userId : '')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'.($this->editMode ? ','.$this->userId : '')],
            'selectedRoles' => ['required', 'array', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
        ];

        if ($this->editMode) {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }

    protected array $messages = [
        'nim.required' => 'NIM wajib diisi',
        'nim.unique' => 'NIM sudah terdaftar',
        'name.required' => 'Nama wajib diisi',
        'email.required' => 'Email wajib diisi',
        'email.email' => 'Format email tidak valid',
        'email.unique' => 'Email sudah terdaftar',
        'password.required' => 'Password wajib diisi',
        'password.min' => 'Password minimal 8 karakter',
        'password.confirmed' => 'Konfirmasi password tidak cocok',
        'selectedRoles.required' => 'Pilih minimal satu role',
        'selectedRoles.min' => 'Pilih minimal satu role',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('roles:id,name')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('nim', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->roleFilter, function ($q) {
                $q->whereHas('roles', fn ($query) => $query->where('name', $this->roleFilter));
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->orderByRaw("FIELD(name, 'super-admin', 'ketua', 'wakil-ketua', 'bph', 'anggota') DESC")
            ->orderBy('name')
            ->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $user = User::with('roles:id,name')->findOrFail($id);

        $this->userId = $user->id;
        $this->nim = $user->nim ?? '';
        $this->name = $user->name;
        $this->email = $user->email;
        $this->status = $user->status ?? 'active';
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
        $this->password = '';
        $this->password_confirmation = '';

        $this->editMode = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        try {
            if ($this->editMode) {
                $user = User::findOrFail($this->userId);

                $user->update([
                    'nim' => $this->nim,
                    'name' => $this->name,
                    'email' => $this->email,
                    'status' => $this->status,
                ]);

                if ($this->password) {
                    $user->update(['password' => Hash::make($this->password)]);
                }

                $user->syncRoles($this->selectedRoles);
                $message = 'Anggota berhasil diperbarui';

                // Log activity
                ActivityLogService::logUserUpdated($this->name);
            } else {
                $user = User::create([
                    'nim' => $this->nim,
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make($this->password),
                    'status' => $this->status,
                ]);

                $user->syncRoles($this->selectedRoles);
                $message = 'Anggota berhasil ditambahkan';

                // Log activity
                ActivityLogService::logUserCreated($this->name);
            }

            $this->dispatch('toast', message: $message, type: 'success');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Terjadi kesalahan: '.$e->getMessage(), type: 'error');
        }
    }

    public function delete(int $id): void
    {
        try {
            $user = User::findOrFail($id);

            if ($user->hasRole('super-admin')) {
                $this->dispatch('toast', message: 'Super Admin tidak dapat dihapus', type: 'error');

                return;
            }

            if ($user->id === auth()->id()) {
                $this->dispatch('toast', message: 'Anda tidak dapat menghapus akun sendiri', type: 'error');

                return;
            }

            $user->delete();

            // Log activity
            ActivityLogService::logUserDeleted($user->name);

            $this->dispatch('toast', message: 'Anggota berhasil dihapus', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Terjadi kesalahan: '.$e->getMessage(), type: 'error');
        }
    }

    public function toggleStatus(int $id): void
    {
        try {
            $user = User::findOrFail($id);

            if ($user->hasRole('super-admin')) {
                $this->dispatch('toast', message: 'Status Super Admin tidak dapat diubah', type: 'error');

                return;
            }

            $user->update([
                'status' => $user->status === 'active' ? 'inactive' : 'active',
            ]);

            // Log activity
            ActivityLogService::logUserStatusChanged($user->name, $user->status);

            $this->dispatch('toast', message: 'Status berhasil diubah', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Terjadi kesalahan: '.$e->getMessage(), type: 'error');
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'roleFilter', 'statusFilter']);
        $this->resetPage();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'userId', 'nim', 'name', 'email',
            'password', 'password_confirmation', 'selectedRoles',
        ]);
        $this->status = 'active';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.user.index')->layout('layouts.app');
    }
}
