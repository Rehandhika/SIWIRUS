@props([
    'banners' => collect([])
])

@if($banners->isNotEmpty())
<div 
    x-data="{
        currentSlide: 0,
        totalSlides: {{ $banners->count() }},
        autoSlideInterval: null,
        isPaused: false,
        isTransitioning: false,
        touchStartX: 0,
        touchEndX: 0,
        
        init() {
            this.startAutoSlide();
            this.setupTouchEvents();
            this.setupKeyboardNav();
        },
        
        setupTouchEvents() {
            const container = this.$refs.slideContainer;
            
            container.addEventListener('touchstart', (e) => {
                this.touchStartX = e.touches[0].clientX;
                this.pauseAutoSlide();
            }, { passive: true });
            
            container.addEventListener('touchmove', (e) => {
                this.touchEndX = e.touches[0].clientX;
            }, { passive: true });
            
            container.addEventListener('touchend', () => {
                this.handleSwipe();
                this.resumeAutoSlide();
            });
        },
        
        setupKeyboardNav() {
            this.$el.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.previousSlide();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.nextSlide();
                }
            });
        },
        
        startAutoSlide() {
            if (this.totalSlides <= 1) return;
            this.autoSlideInterval = setInterval(() => {
                if (!this.isPaused && !this.isTransitioning) {
                    this.nextSlide();
                }
            }, 5000);
        },
        
        pauseAutoSlide() {
            this.isPaused = true;
        },
        
        resumeAutoSlide() {
            this.isPaused = false;
        },
        
        nextSlide() {
            if (this.isTransitioning) return;
            this.isTransitioning = true;
            this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
            setTimeout(() => this.isTransitioning = false, 600);
        },
        
        previousSlide() {
            if (this.isTransitioning) return;
            this.isTransitioning = true;
            this.currentSlide = this.currentSlide === 0 ? this.totalSlides - 1 : this.currentSlide - 1;
            setTimeout(() => this.isTransitioning = false, 600);
        },
        
        goToSlide(index) {
            if (this.isTransitioning || index === this.currentSlide) return;
            this.isTransitioning = true;
            this.currentSlide = index;
            setTimeout(() => this.isTransitioning = false, 600);
        },
        
        handleSwipe() {
            const threshold = 50;
            const diff = this.touchStartX - this.touchEndX;
            
            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    this.nextSlide();
                } else {
                    this.previousSlide();
                }
            }
        }
    }"
    @mouseenter="pauseAutoSlide()"
    @mouseleave="resumeAutoSlide()"
    class="relative w-full overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200"
    tabindex="0"
    role="region"
    aria-label="Banner promosi"
    aria-live="polite"
>
    <!-- Slides Container -->
    <div 
        x-ref="slideContainer"
        class="relative w-full"
        style="aspect-ratio: 16/5;"
    >
        @foreach($banners as $index => $banner)
        @php
            $pathInfo = pathinfo($banner->image_path);
            $filename = $pathInfo['filename'];
            $lastUnderscorePos = strrpos($filename, '_');
            $uuid = $lastUnderscorePos !== false ? substr($filename, 0, $lastUnderscorePos) : $filename;
            $directory = $pathInfo['dirname'];
            
            $mobileImage = "{$directory}/{$uuid}_480.jpg";
            $tabletImage = "{$directory}/{$uuid}_768.jpg";
            $desktopImage = "{$directory}/{$uuid}_1920.jpg";
        @endphp
        
        <div 
            x-show="currentSlide === {{ $index }}"
            x-transition:enter="transition-all ease-out duration-500"
            x-transition:enter-start="opacity-0 scale-105"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition-all ease-in duration-500"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute inset-0 w-full h-full"
        >
            <img 
                src="{{ Storage::url($banner->image_path) }}"
                srcset="{{ Storage::url($mobileImage) }} 480w,
                        {{ Storage::url($tabletImage) }} 768w,
                        {{ Storage::url($desktopImage) }} 1920w"
                sizes="100vw"
                alt="{{ $banner->title ?: 'Banner promosi SIWIRUS' }}"
                class="w-full h-full object-cover"
                @if($index > 1) loading="lazy" @endif
                draggable="false"
            />
            
            <!-- Gradient Overlay for better text readability -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent"></div>
            
            <!-- Banner Title (if exists) -->
            @if($banner->title)
            <div class="absolute bottom-0 left-0 right-0 p-4 md:p-8">
                <div class="max-w-7xl mx-auto">
                    <h3 class="text-white text-lg md:text-2xl lg:text-3xl font-bold drop-shadow-lg">
                        {{ $banner->title }}
                    </h3>
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    
    <!-- Navigation Arrows -->
    @if($banners->count() > 1)
    <button 
        @click="previousSlide()"
        class="absolute left-2 md:left-4 top-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 bg-white/90 hover:bg-white text-gray-800 rounded-full shadow-lg flex items-center justify-center transition-all duration-300 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 opacity-0 group-hover:opacity-100 hover:opacity-100 z-10"
        :class="{ 'opacity-70': true }"
        aria-label="Banner sebelumnya"
    >
        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
        </svg>
    </button>
    
    <button 
        @click="nextSlide()"
        class="absolute right-2 md:right-4 top-1/2 -translate-y-1/2 w-10 h-10 md:w-12 md:h-12 bg-white/90 hover:bg-white text-gray-800 rounded-full shadow-lg flex items-center justify-center transition-all duration-300 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 opacity-0 group-hover:opacity-100 hover:opacity-100 z-10"
        :class="{ 'opacity-70': true }"
        aria-label="Banner selanjutnya"
    >
        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
        </svg>
    </button>
    @endif
    
    <!-- Pagination Dots -->
    @if($banners->count() > 1)
    <div class="absolute bottom-3 md:bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 z-10" role="tablist" aria-label="Navigasi banner">
        @foreach($banners as $index => $banner)
        <button 
            @click="goToSlide({{ $index }})"
            class="group relative transition-all duration-300 focus:outline-none"
            role="tab"
            :aria-selected="currentSlide === {{ $index }}"
            aria-label="Tampilkan banner {{ $index + 1 }}"
        >
            <span 
                class="block rounded-full transition-all duration-300"
                :class="currentSlide === {{ $index }} 
                    ? 'w-8 h-2 bg-white shadow-lg' 
                    : 'w-2 h-2 bg-white/60 hover:bg-white/80'"
            ></span>
        </button>
        @endforeach
    </div>
    @endif
    
    <!-- Progress Bar -->
    @if($banners->count() > 1)
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-black/20">
        <div 
            class="h-full bg-white/80 transition-all duration-300"
            :style="{ width: ((currentSlide + 1) / totalSlides * 100) + '%' }"
        ></div>
    </div>
    @endif
</div>
@endif