<?php

namespace App\Livewire\Admin;

use App\Models\Banner;
use App\Models\News;
use App\Services\ActivityLogService;
use App\Services\BannerService;
use App\Services\NewsService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class BannerNewsManagement extends Component
{
    use WithFileUploads, WithPagination;

    // Tab state
    public string $activeTab = 'banner';

    // Banner form properties
    #[Validate('nullable|string|max:255')]
    public $bannerTitle = '';

    #[Validate('required_without:editingBannerId|image|mimes:jpg,jpeg,png|max:5120')]
    public $bannerImage;

    #[Validate('required|integer|min:0')]
    public $bannerPriority = 0;

    public $editingBannerId = null;

    public $showBannerForm = false;

    public $bannerImagePreview = null;

    // News form properties
    #[Validate('nullable|string|max:255')]
    public $newsTitle = '';

    #[Validate('nullable|string')]
    public $newsContent = '';

    #[Validate('nullable|url|max:500')]
    public $newsLink = '';

    #[Validate('nullable|image|mimes:jpg,jpeg,png|max:5120')]
    public $newsImage;

    #[Validate('required|integer|min:0')]
    public $newsPriority = 0;

    #[Validate('nullable|date')]
    public $newsPublishedAt;

    #[Validate('nullable|date|after:newsPublishedAt')]
    public $newsExpiresAt;

    public $editingNewsId = null;

    public $showNewsForm = false;

    public $newsImagePreview = null;

    protected BannerService $bannerService;

    protected NewsService $newsService;

    public function boot(BannerService $bannerService, NewsService $newsService)
    {
        $this->bannerService = $bannerService;
        $this->newsService = $newsService;
    }

    public function mount()
    {
        // Check authorization - only Super Admin or Ketua can access
        if (! auth()->user()->hasAnyRole(['Super Admin', 'Ketua'])) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    /**
     * Switch between tabs
     */
    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage(); // Reset pagination when switching tabs
    }

    // ==================== BANNER METHODS ====================

    public function createBanner()
    {
        $this->resetBannerForm();
        $this->showBannerForm = true;
    }

    public function editBanner(int $id)
    {
        $banner = Banner::findOrFail($id);

        $this->editingBannerId = $banner->id;
        $this->bannerTitle = $banner->title;
        $this->bannerPriority = $banner->priority;
        $this->bannerImage = null;
        $this->bannerImagePreview = null;
        $this->showBannerForm = true;
    }

    public function updatedBannerImage()
    {
        $this->validateOnly('bannerImage');

        if ($this->bannerImage) {
            $this->bannerImagePreview = $this->bannerImage->temporaryUrl();
        }
    }

    public function removeBannerImage()
    {
        $this->bannerImage = null;
        $this->bannerImagePreview = null;
    }

    public function saveBanner()
    {
        $this->validate([
            'bannerTitle' => 'nullable|string|max:255',
            'bannerImage' => $this->editingBannerId ? 'nullable|image|mimes:jpg,jpeg,png|max:5120' : 'required|image|mimes:jpg,jpeg,png|max:5120',
            'bannerPriority' => 'required|integer|min:0',
        ]);

        try {
            if ($this->editingBannerId) {
                // Update existing banner
                $banner = Banner::findOrFail($this->editingBannerId);
                $this->bannerService->update($banner, [
                    'title' => $this->bannerTitle,
                    'priority' => $this->bannerPriority,
                ], $this->bannerImage);

                ActivityLogService::logBannerUpdated($this->bannerTitle);
                $this->dispatch('toast', message: 'Banner berhasil diperbarui', type: 'success');
            } else {
                // Create new banner
                $this->bannerService->store([
                    'title' => $this->bannerTitle,
                    'priority' => $this->bannerPriority,
                ], $this->bannerImage);

                ActivityLogService::logBannerCreated($this->bannerTitle);
                $this->dispatch('toast', message: 'Banner berhasil dibuat', type: 'success');
            }

            $this->resetBannerForm();
            $this->showBannerForm = false;

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal menyimpan banner: '.$e->getMessage(), type: 'error');
        }
    }

    public function deleteBanner(int $id)
    {
        try {
            $banner = Banner::findOrFail($id);
            $bannerTitle = $banner->title;
            $this->bannerService->delete($banner);

            ActivityLogService::logBannerDeleted($bannerTitle);
            $this->dispatch('toast', message: 'Banner berhasil dihapus', type: 'success');

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal menghapus banner: '.$e->getMessage(), type: 'error');
        }
    }

    public function toggleBannerStatus(int $id)
    {
        try {
            $banner = Banner::findOrFail($id);
            $this->bannerService->toggleStatus($banner);

            $freshBanner = $banner->fresh();
            $statusText = $freshBanner->is_active ? 'diaktifkan' : 'dinonaktifkan';
            ActivityLogService::logBannerStatusChanged($freshBanner->title, $freshBanner->is_active);
            $this->dispatch('toast', message: "Banner berhasil {$statusText}", type: 'success');

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal mengubah status banner: '.$e->getMessage(), type: 'error');
        }
    }

    public function cancelBannerEdit()
    {
        $this->resetBannerForm();
        $this->showBannerForm = false;
    }

    protected function resetBannerForm()
    {
        $this->bannerTitle = '';
        $this->bannerImage = null;
        $this->bannerImagePreview = null;
        $this->bannerPriority = 0;
        $this->editingBannerId = null;
        $this->resetValidation(['bannerTitle', 'bannerImage', 'bannerPriority']);
    }

    // ==================== NEWS METHODS ====================

    public function createNews()
    {
        $this->resetNewsForm();
        $this->showNewsForm = true;
    }

    public function editNews(int $id)
    {
        $news = News::findOrFail($id);

        $this->editingNewsId = $news->id;
        $this->newsTitle = $news->title ?? '';
        $this->newsContent = $news->content ?? '';
        $this->newsLink = $news->link ?? '';
        $this->newsPriority = $news->priority;
        $this->newsPublishedAt = $news->published_at?->format('Y-m-d\TH:i');
        $this->newsExpiresAt = $news->expires_at?->format('Y-m-d\TH:i');
        $this->newsImage = null;
        $this->newsImagePreview = null;
        $this->showNewsForm = true;
    }

    public function updatedNewsImage()
    {
        $this->validateOnly('newsImage');

        if ($this->newsImage) {
            $this->newsImagePreview = $this->newsImage->temporaryUrl();
        }
    }

    public function removeNewsImage()
    {
        $this->newsImage = null;
        $this->newsImagePreview = null;
    }

    public function saveNews()
    {
        $this->validate([
            'newsTitle' => 'nullable|string|max:255',
            'newsContent' => 'nullable|string',
            'newsLink' => 'nullable|url|max:500',
            'newsImage' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'newsPriority' => 'required|integer|min:0',
            'newsPublishedAt' => 'nullable|date',
            'newsExpiresAt' => 'nullable|date|after:newsPublishedAt',
        ]);

        try {
            $data = [
                'title' => $this->newsTitle ?: null,
                'content' => $this->newsContent ?: null,
                'link' => $this->newsLink ?: null,
                'priority' => $this->newsPriority,
                'published_at' => $this->newsPublishedAt ? \Carbon\Carbon::parse($this->newsPublishedAt) : now(),
                'expires_at' => $this->newsExpiresAt ? \Carbon\Carbon::parse($this->newsExpiresAt) : null,
            ];

            if ($this->editingNewsId) {
                // Update existing news
                $news = News::findOrFail($this->editingNewsId);
                $this->newsService->update($news, $data, $this->newsImage);

                ActivityLogService::logNewsUpdated($this->newsTitle);
                $this->dispatch('toast', message: 'Berita berhasil diperbarui', type: 'success');
            } else {
                // Create new news
                $this->newsService->store($data, $this->newsImage);

                ActivityLogService::logNewsCreated($this->newsTitle);
                $this->dispatch('toast', message: 'Berita berhasil dibuat', type: 'success');
            }

            $this->resetNewsForm();
            $this->showNewsForm = false;

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal menyimpan berita: '.$e->getMessage(), type: 'error');
        }
    }

    public function deleteNews(int $id)
    {
        try {
            $news = News::findOrFail($id);
            $newsTitle = $news->title;
            $this->newsService->delete($news);

            ActivityLogService::logNewsDeleted($newsTitle);
            $this->dispatch('toast', message: 'Berita berhasil dihapus', type: 'success');

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal menghapus berita: '.$e->getMessage(), type: 'error');
        }
    }

    public function toggleNewsStatus(int $id)
    {
        try {
            $news = News::findOrFail($id);
            $this->newsService->toggleStatus($news);

            $freshNews = $news->fresh();
            $statusText = $freshNews->is_active ? 'diaktifkan' : 'dinonaktifkan';
            ActivityLogService::logNewsStatusChanged($freshNews->title, $freshNews->is_active);
            $this->dispatch('toast', message: "Berita berhasil {$statusText}", type: 'success');

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Gagal mengubah status berita: '.$e->getMessage(), type: 'error');
        }
    }

    public function cancelNewsEdit()
    {
        $this->resetNewsForm();
        $this->showNewsForm = false;
    }

    protected function resetNewsForm()
    {
        $this->newsTitle = '';
        $this->newsContent = '';
        $this->newsLink = '';
        $this->newsImage = null;
        $this->newsImagePreview = null;
        $this->newsPriority = 0;
        $this->newsPublishedAt = null;
        $this->newsExpiresAt = null;
        $this->editingNewsId = null;
        $this->resetValidation(['newsTitle', 'newsContent', 'newsLink', 'newsImage', 'newsPriority', 'newsPublishedAt', 'newsExpiresAt']);
    }

    public function render()
    {
        $banners = Banner::with('creator')
            ->orderBy('priority', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'bannersPage');

        $news = News::with('creator')
            ->orderBy('priority', 'asc')
            ->orderBy('published_at', 'desc')
            ->paginate(10, ['*'], 'newsPage');

        return view('livewire.admin.banner-news-management', [
            'banners' => $banners,
            'news' => $news,
        ])
            ->layout('layouts.app')
            ->title('Kelola Banner & Berita');
    }
}
