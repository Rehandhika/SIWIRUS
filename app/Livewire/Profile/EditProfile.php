<?php

namespace App\Livewire\Profile;

use App\Services\ActivityLogService;
use App\Services\Storage\FileStorageServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditProfile extends Component
{
    use WithFileUploads;

    public $user;

    // Profile fields
    public $name;

    public $email;

    public $nim;

    public $photo;

    public $photoPreview = null;

    public $current_photo;

    // Password fields
    public $current_password;

    public $new_password;

    public $new_password_confirmation;

    public $activeTab = 'profile';

    protected FileStorageServiceInterface $fileStorageService;

    public function boot(FileStorageServiceInterface $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;
    }

    public function mount()
    {
        $this->user = auth()->user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->nim = $this->user->nim;
        $this->current_photo = $this->user->photo;
    }

    public function updatedPhoto()
    {
        $this->validate([
            'photo' => 'image|max:2048',
        ]);

        if ($this->photo) {
            $this->photoPreview = $this->photo->temporaryUrl();
        }
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->user->id)],
            'nim' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($this->user->id)],
            'photo' => 'nullable|image|max:2048',
        ]);

        try {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'nim' => $this->nim,
            ];

            // Handle photo upload using FileStorageService
            if ($this->photo) {
                $photoPath = $this->uploadProfilePhoto();
                if ($photoPath) {
                    $data['photo'] = $photoPath;
                    $this->current_photo = $photoPath;
                }
            }

            $this->user->update($data);

            // Log activity
            ActivityLogService::logProfileUpdated();

            $this->dispatch('toast', message: 'Profil berhasil diperbarui', type: 'success');
            $this->photo = null;
            $this->photoPreview = null;
        } catch (\Exception $e) {
            Log::error('Profile update failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', message: 'Terjadi kesalahan: '.$e->getMessage(), type: 'error');
        }
    }

    /**
     * Upload profile photo using FileStorageService.
     *
     * @return string|null Photo path or null on failure
     */
    protected function uploadProfilePhoto(): ?string
    {
        try {
            // Upload new photo using FileStorageService
            $result = $this->fileStorageService->upload($this->photo, 'profile', [
                'old_path' => $this->current_photo,
                'user_id' => $this->user->id,
            ]);

            Log::info('Profile photo uploaded via FileStorageService', [
                'user_id' => $this->user->id,
                'path' => $result->path,
            ]);

            return $result->path;
        } catch (\Exception $e) {
            Log::warning('FileStorageService upload failed, using fallback', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to legacy upload
            return $this->uploadProfilePhotoLegacy();
        }
    }

    /**
     * Legacy method for uploading profile photo.
     * Used as fallback when FileStorageService fails.
     */
    protected function uploadProfilePhotoLegacy(): ?string
    {
        try {
            // Delete old photo
            if ($this->current_photo) {
                Storage::disk('public')->delete($this->current_photo);
            }

            // Store new photo
            return $this->photo->store('photos', 'public');
        } catch (\Exception $e) {
            Log::error('Legacy profile photo upload failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // Verify current password
            if (! Hash::check($this->current_password, $this->user->password)) {
                $this->addError('current_password', 'Password saat ini tidak sesuai');

                return;
            }

            // Update password
            $this->user->update([
                'password' => Hash::make($this->new_password),
            ]);

            // Reset password fields
            $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

            // Log activity
            ActivityLogService::logPasswordChanged();

            $this->dispatch('toast', message: 'Password berhasil diubah', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Terjadi kesalahan: '.$e->getMessage(), type: 'error');
        }
    }

    public function deletePhoto()
    {
        try {
            if ($this->current_photo) {
                // Try to delete using FileStorageService
                try {
                    $this->fileStorageService->delete($this->current_photo);
                } catch (\Exception $e) {
                    // Fallback to direct delete
                    Storage::disk('public')->delete($this->current_photo);
                }

                $this->user->update(['photo' => null]);
                $this->current_photo = null;

                // Log activity
                ActivityLogService::logProfilePhotoDeleted();

                $this->dispatch('toast', message: 'Foto profil berhasil dihapus', type: 'success');
            }
        } catch (\Exception $e) {
            Log::error('Profile photo delete failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', message: 'Terjadi kesalahan: '.$e->getMessage(), type: 'error');
        }
    }

    /**
     * Get profile photo URL.
     */
    public function getProfilePhotoUrl(): ?string
    {
        if (! $this->current_photo) {
            return null;
        }

        try {
            return $this->fileStorageService->getUrl($this->current_photo, 'medium');
        } catch (\Exception $e) {
            // Fallback to direct URL
            if (Storage::disk('public')->exists($this->current_photo)) {
                return Storage::disk('public')->url($this->current_photo);
            }

            return null;
        }
    }

    /**
     * Get profile photo thumbnail URL.
     */
    public function getProfilePhotoThumbnailUrl(): ?string
    {
        if (! $this->current_photo) {
            return null;
        }

        try {
            return $this->fileStorageService->getUrl($this->current_photo, 'small');
        } catch (\Exception $e) {
            // Fallback to direct URL
            if (Storage::disk('public')->exists($this->current_photo)) {
                return Storage::disk('public')->url($this->current_photo);
            }

            return null;
        }
    }

    public function render()
    {
        return view('livewire.profile.edit-profile', [
            'profilePhotoUrl' => $this->getProfilePhotoUrl(),
            'profilePhotoThumbnailUrl' => $this->getProfilePhotoThumbnailUrl(),
        ])
            ->layout('layouts.app')
            ->title('Edit Profil');
    }
}
