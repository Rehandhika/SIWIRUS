import React from 'react'
import { Search } from 'lucide-react'

import { Input } from '@/components/ui/input'
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'

import PublicLayout from '@/react/components/PublicLayout'
import useDebouncedValue from '@/react/hooks/useDebouncedValue'
import { api } from '@/react/lib/api'
import { formatRupiah } from '@/react/lib/format'

const BannerCarousel = React.lazy(() => import('@/react/components/BannerCarousel'))
const NewsPopup = React.lazy(() => import('@/react/components/NewsPopup'))

function buildPageList(currentPage, lastPage) {
    const pages = []
    const clampedCurrent = Math.max(1, Math.min(lastPage, currentPage))

    pages.push(1)

    const windowStart = Math.max(2, clampedCurrent - 1)
    const windowEnd = Math.min(lastPage - 1, clampedCurrent + 1)

    if (windowStart > 2) pages.push('…')
    for (let p = windowStart; p <= windowEnd; p += 1) pages.push(p)
    if (windowEnd < lastPage - 1) pages.push('…')

    if (lastPage > 1) pages.push(lastPage)

    return pages
}

function BannerSection({ initialBanners }) {
    const [banners, setBanners] = React.useState(() => (Array.isArray(initialBanners) ? initialBanners : []))
    const [loading, setLoading] = React.useState(() => !Array.isArray(initialBanners))

    React.useEffect(() => {
        if (Array.isArray(initialBanners)) return
        ;(async () => {
            try {
                const res = await api.get('/api/publik/banner')
                setBanners(res.data?.data ?? [])
            } finally {
                setLoading(false)
            }
        })()
    }, [])

    if (loading) {
        return (
            <div className="w-full relative">
                <div className="aspect-[16/9] w-full">
                    <Skeleton className="h-full w-full rounded-none" />
                </div>
            </div>
        )
    }

    if (!banners.length) return null

    const slides = banners.map((banner) => ({
        id: banner.id,
        title: banner.title || null,
        alt: banner.title || 'Banner promosi SIWIRUS',
        href: null,
        images: {
            default: banner.images?.default ?? '',
            '1920': banner.images?.['1920'],
            '768': banner.images?.['768'],
            '480': banner.images?.['480'],
        },
    }))

    return (
        <div className="w-full relative">
            <div className="absolute inset-0 bg-gradient-to-b from-background/0 to-background pointer-events-none z-10" />
            <div className="max-w-7xl mx-auto px-4">
                <React.Suspense
                    fallback={
                        <div className="overflow-hidden rounded-2xl border border-border bg-card/20">
                            <div className="aspect-[16/9] w-full">
                                <Skeleton className="h-full w-full rounded-none" />
                            </div>
                        </div>
                    }
                >
                    <BannerCarousel
                        slides={slides}
                        loop={slides.length > 1}
                        autoplayIntervalMs={5000}
                        transitionDuration={40}
                        showArrows={slides.length > 1}
                        showDots={slides.length > 1}
                    />
                </React.Suspense>
            </div>
        </div>
    )
}

function NewsSection({ initialNews }) {
    const [news, setNews] = React.useState(() => (Array.isArray(initialNews) ? initialNews : []))
    const [loading, setLoading] = React.useState(() => !Array.isArray(initialNews))
    const [showPopup, setShowPopup] = React.useState(false)

    React.useEffect(() => {
        if (Array.isArray(initialNews)) {
            // Check if there are unviewed news items
            const hasUnviewedNews = checkForUnviewedNews(initialNews)
            setShowPopup(hasUnviewedNews)
            return
        }

        // Fetch news from API if not provided
        ;(async () => {
            try {
                const res = await api.get('/api/publik/berita')
                const fetchedNews = res.data?.data ?? []
                setNews(fetchedNews)
                
                // Check if there are unviewed news items
                const hasUnviewedNews = checkForUnviewedNews(fetchedNews)
                setShowPopup(hasUnviewedNews)
            } catch (error) {
                console.error('Failed to fetch news:', error)
            } finally {
                setLoading(false)
            }
        })()
    }, [initialNews])

    // Check if there are any unviewed news items
    const checkForUnviewedNews = (newsItems) => {
        if (!newsItems || newsItems.length === 0) return false
        
        try {
            const viewedIds = JSON.parse(sessionStorage.getItem('viewedNewsIds') || '[]')
            return newsItems.some(item => !viewedIds.includes(item.id))
        } catch {
            return newsItems.length > 0
        }
    }

    const handleClosePopup = () => {
        setShowPopup(false)
    }

    // Don't render anything if loading or no news
    if (loading || news.length === 0) {
        return null
    }

    return (
        <>
            {showPopup && (
                <React.Suspense fallback={null}>
                    <NewsPopup news={news} onClose={handleClosePopup} />
                </React.Suspense>
            )}
        </>
    )
}

function ProductsSection({ initialCategories, initialProducts }) {
    const [search, setSearch] = React.useState('')
    const debouncedSearch = useDebouncedValue(search, 350)
    const [category, setCategory] = React.useState('')

    const [categories, setCategories] = React.useState(() =>
        Array.isArray(initialCategories) ? initialCategories : [],
    )
    const [products, setProducts] = React.useState(() => initialProducts ?? null)
    const [loading, setLoading] = React.useState(() => !initialProducts)
    const [page, setPage] = React.useState(1)
    const initialAppliedRef = React.useRef(false)

    React.useEffect(() => {
        if (Array.isArray(initialCategories)) return
        ;(async () => {
            const res = await api.get('/api/publik/kategori')
            setCategories(res.data?.data ?? [])
        })()
    }, [initialCategories])

    React.useEffect(() => {
        setPage(1)
    }, [debouncedSearch, category])

    React.useEffect(() => {
        let cancelled = false

        if (
            !initialAppliedRef.current &&
            initialProducts &&
            !debouncedSearch &&
            !category &&
            page === 1
        ) {
            initialAppliedRef.current = true
            setLoading(false)
            return () => {
                cancelled = true
            }
        }

        ;(async () => {
            setLoading(true)
            try {
                const res = await api.get('/api/publik/produk', {
                    params: {
                        search: debouncedSearch || undefined,
                        category: category || undefined,
                        page,
                        per_page: 12,
                    },
                })
                if (!cancelled) {
                    setProducts(res.data ?? null)
                }
            } finally {
                if (!cancelled) setLoading(false)
            }
        })()

        return () => {
            cancelled = true
        }
    }, [debouncedSearch, category, page, initialProducts])

    const items = Array.isArray(products?.data) ? products.data : []
    const currentPage = Number(products?.current_page ?? page)
    const lastPage = Number(products?.last_page ?? 1)

    return (
        <div className="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-6 sm:py-8 md:py-12 relative z-20">
            {/* Header Section */}
            <div className="flex flex-col gap-1 sm:gap-2 mb-6 sm:mb-8 md:mb-10">
                <h1 className="text-2xl sm:text-3xl font-bold text-foreground tracking-tight">
                    Katalog Produk
                </h1>
                <p className="text-sm sm:text-base text-muted-foreground">
                    Temukan kebutuhan kampus dan harianmu
                </p>
            </div>

            {/* Search & Filter Bar */}
            <div className="bg-card/50 backdrop-blur-sm border border-border/50 rounded-xl sm:rounded-2xl p-3 sm:p-4 mb-6 sm:mb-8 md:mb-10">
                <div className="flex flex-col sm:flex-row gap-3 sm:gap-4">
                    <div className="flex-1 relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                            <Search className="h-4 w-4" />
                        </div>
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari produk..."
                            className="h-11 sm:h-12 pl-10 bg-background/60 border-border text-foreground placeholder:text-muted-foreground/60 focus-visible:ring-ring/40 text-sm sm:text-base"
                        />
                    </div>

                    <div className="sm:w-48 md:w-56">
                        <Select
                            value={category ? category : 'all'}
                            onValueChange={(v) => setCategory(v === 'all' ? '' : v)}
                        >
                            <SelectTrigger className="h-11 sm:h-12 bg-background/60 border-border text-foreground focus:ring-ring/40 text-sm sm:text-base">
                                <SelectValue placeholder="Semua Kategori" />
                            </SelectTrigger>
                            <SelectContent className="bg-popover border-border">
                                <SelectItem value="all">Semua Kategori</SelectItem>
                                {categories.filter((c) => c && c.trim() !== '').map((c) => (
                                    <SelectItem key={c} value={c}>
                                        {c}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            {loading ? (
                <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-3 sm:gap-4 md:gap-5 lg:gap-6 mb-8 md:mb-12">
                    {Array.from({ length: 12 }).map((_, idx) => (
                        <div
                            key={idx}
                            className="overflow-hidden bg-card/40 border border-border/40 rounded-2xl"
                        >
                            <div className="aspect-[4/3] sm:aspect-square">
                                <Skeleton className="h-full w-full rounded-none" />
                            </div>
                            <div className="p-3 sm:p-4 space-y-2 sm:space-y-3">
                                <Skeleton className="h-2.5 sm:h-3 w-16 sm:w-20" />
                                <Skeleton className="h-3.5 sm:h-4 w-full" />
                                <Skeleton className="h-3.5 sm:h-4 w-3/4" />
                                <Skeleton className="h-5 sm:h-6 w-1/2 mt-2" />
                            </div>
                        </div>
                    ))}
                </div>
            ) : items.length ? (
                <>
                    <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-3 sm:gap-4 md:gap-5 lg:gap-6 mb-8 md:mb-12">
                        {items.map((p) => {
                            const stock = Number(p.stock ?? 0)
                            const minStock = Number(p.min_stock ?? 0)
                            const isOut = stock <= 0
                            const isLow = !isOut && stock <= minStock

                            return (
                                <a
                                    key={p.id}
                                    href={`/produk/${p.slug}`}
                                    className="group relative flex flex-col overflow-hidden bg-card/40 hover:bg-card/60 border border-border/40 hover:border-primary/30 rounded-2xl transition-all duration-300 ease-out hover:shadow-lg hover:shadow-primary/5 hover:-translate-y-0.5 active:scale-[0.98]"
                                >
                                    {/* Image Container */}
                                    <div className="aspect-[4/3] sm:aspect-square relative overflow-hidden bg-muted/50">
                                        {p.image_medium_url ? (
                                            <img
                                                src={p.image_thumbnail_url || p.image_medium_url}
                                                alt={p.name}
                                                className="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                                                loading="lazy"
                                                fetchPriority="low"
                                                sizes="(max-width: 640px) 50vw, (max-width: 768px) 33vw, 25vw"
                                                decoding="async"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex items-center justify-center text-muted-foreground/40 bg-muted/30">
                                                <svg className="w-10 h-10 sm:w-12 sm:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                        )}

                                        {/* Badges Container */}
                                        <div className="absolute inset-x-0 top-0 p-2 sm:p-3 flex justify-between items-start pointer-events-none">
                                            {/* Featured Badge */}
                                            <div>
                                                {p.is_featured ? (
                                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 sm:px-2.5 sm:py-1 text-[10px] sm:text-xs font-semibold bg-amber-500/90 text-white rounded-full shadow-sm">
                                                        <svg className="w-2.5 h-2.5 sm:w-3 sm:h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                        <span className="hidden sm:inline">Featured</span>
                                                    </span>
                                                ) : null}
                                            </div>

                                            {/* Stock Badge */}
                                            <div>
                                                {isOut ? (
                                                    <span className="inline-flex items-center px-2 py-0.5 sm:px-2.5 sm:py-1 text-[10px] sm:text-xs font-medium bg-red-500/90 text-white rounded-full shadow-sm">
                                                        Habis
                                                    </span>
                                                ) : isLow ? (
                                                    <span className="inline-flex items-center px-2 py-0.5 sm:px-2.5 sm:py-1 text-[10px] sm:text-xs font-medium bg-orange-500/90 text-white rounded-full shadow-sm">
                                                        Sisa {stock}
                                                    </span>
                                                ) : null}
                                            </div>
                                        </div>
                                    </div>

                                    {/* Content Container */}
                                    <div className="flex flex-col flex-1 p-3 sm:p-4">
                                        {/* Category */}
                                        {p.category ? (
                                            <span className="text-[10px] sm:text-xs text-primary/70 font-medium uppercase tracking-wider mb-1 sm:mb-1.5 truncate">
                                                {p.category}
                                            </span>
                                        ) : null}

                                        {/* Product Name */}
                                        <h3 className="text-xs sm:text-sm font-medium text-card-foreground line-clamp-2 leading-snug mb-2 sm:mb-3 group-hover:text-primary transition-colors duration-200 min-h-[2rem] sm:min-h-[2.5rem]">
                                            {p.name}
                                        </h3>

                                        {/* Price & Stock - Push to bottom */}
                                        <div className="mt-auto pt-2 sm:pt-3 border-t border-border/30">
                                            <div className="flex items-end justify-between gap-2">
                                                <div className="flex flex-col min-w-0">
                                                    <span className="text-base sm:text-lg font-bold text-foreground group-hover:text-primary transition-colors duration-200 truncate">
                                                        Rp {formatRupiah(p.price)}
                                                    </span>
                                                </div>
                                                <span className="text-[10px] sm:text-xs text-muted-foreground whitespace-nowrap">
                                                    Stok: {stock}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            )
                        })}
                    </div>

                    {/* Pagination */}
                    <div className="mt-6 sm:mt-8 p-3 sm:p-4 bg-card/40 rounded-xl border border-border/40">
                        <Pagination>
                            <PaginationContent className="flex-wrap gap-1">
                                <PaginationItem>
                                    <PaginationPrevious
                                        href="#"
                                        onClick={(e) => {
                                            e.preventDefault()
                                            setPage((p) => Math.max(1, p - 1))
                                        }}
                                        className={[
                                            'text-foreground h-9 sm:h-10 text-xs sm:text-sm',
                                            currentPage <= 1 ? 'pointer-events-none opacity-50' : '',
                                        ].join(' ')}
                                    />
                                </PaginationItem>

                                {buildPageList(currentPage, lastPage).map((p, idx) => (
                                    <PaginationItem key={`${p}-${idx}`} className="hidden sm:block">
                                        {p === '…' ? (
                                            <span className="px-2 text-muted-foreground text-sm">…</span>
                                        ) : (
                                            <PaginationLink
                                                href="#"
                                                isActive={p === currentPage}
                                                onClick={(e) => {
                                                    e.preventDefault()
                                                    setPage(p)
                                                }}
                                                className={[
                                                    'border-border h-9 sm:h-10 w-9 sm:w-10 text-xs sm:text-sm',
                                                    p === currentPage
                                                        ? 'bg-primary text-primary-foreground'
                                                        : 'text-muted-foreground hover:text-foreground',
                                                ].join(' ')}
                                            >
                                                {p}
                                            </PaginationLink>
                                        )}
                                    </PaginationItem>
                                ))}

                                {/* Mobile page indicator */}
                                <PaginationItem className="sm:hidden">
                                    <span className="px-3 py-2 text-sm text-muted-foreground">
                                        {currentPage} / {lastPage}
                                    </span>
                                </PaginationItem>

                                <PaginationItem>
                                    <PaginationNext
                                        href="#"
                                        onClick={(e) => {
                                            e.preventDefault()
                                            setPage((p) => Math.min(lastPage, p + 1))
                                        }}
                                        className={[
                                            'text-foreground h-9 sm:h-10 text-xs sm:text-sm',
                                            currentPage >= lastPage ? 'pointer-events-none opacity-50' : '',
                                        ].join(' ')}
                                    />
                                </PaginationItem>
                            </PaginationContent>
                        </Pagination>
                    </div>
                </>
            ) : (
                /* Empty State */
                <div className="bg-card/30 rounded-2xl sm:rounded-3xl border border-dashed border-border/60 p-10 sm:p-16 md:p-20 text-center">
                    <div className="w-16 h-16 sm:w-20 sm:h-20 bg-muted/30 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6 text-muted-foreground/50">
                        <Search className="h-6 w-6 sm:h-8 sm:w-8" />
                    </div>
                    <h3 className="text-lg sm:text-xl font-semibold text-foreground mb-2">Tidak Ada Produk</h3>
                    <p className="text-sm sm:text-base text-muted-foreground max-w-sm mx-auto">
                        Coba ubah kata kunci pencarian atau kategori.
                    </p>
                </div>
            )}
        </div>
    )
}

export default function HomePage({ initialData }) {
    const initialBanners = initialData?.banners
    const initialCategories = initialData?.categories
    const initialProducts = initialData?.products
    const initialNews = initialData?.news

    return (
        <PublicLayout>
            <NewsSection initialNews={initialNews} />
            <BannerSection initialBanners={initialBanners} />
            <ProductsSection initialCategories={initialCategories} initialProducts={initialProducts} />
        </PublicLayout>
    )
}

