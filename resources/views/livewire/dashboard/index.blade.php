<div class="space-y-8">
    @if($this->isAdmin)
        <div class="space-y-6">
            @include('livewire.dashboard.user-dashboard')
        </div>

        <div class="space-y-6">
            @include('livewire.dashboard.admin-dashboard')
        </div>
    @else
        @include('livewire.dashboard.user-dashboard')
    @endif
</div>
