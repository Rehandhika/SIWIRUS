# Style Guide UI (Public Pages)

Dokumen ini menjadi referensi konsistensi UI untuk halaman publik (Katalog, Detail Produk, Tentang).

## Stack UI
- Tailwind CSS + CSS Variables (shadcn/ui)
- Komponen UI: `resources/js/components/ui/*` (shadcn/ui)
- Layout publik React: `resources/js/react/components/PublicLayout.jsx`

## Desain Dasar
### Warna (Light & Dark Mode)
- Gunakan **semantic tokens** shadcn (Tailwind classes): `bg-background`, `text-foreground`, `bg-card`, `text-muted-foreground`, `border-border`, `bg-accent`, `text-accent-foreground`.
- Hindari hardcode warna seperti `bg-slate-*`, `text-slate-*`, `border-white/*` kecuali untuk badge/status yang memang bernuansa (merah/oranye/hijau).
- Aksen utama: `primary` (blue) via token shadcn `--primary` dan kelas `text-primary` / `bg-primary`.

### Tipografi
- Judul halaman: `text-4xl md:text-5xl font-bold tracking-tight`
- Subjudul: `text-base md:text-lg text-muted-foreground`
- Judul section/card: `text-2xl font-bold text-foreground`
- Body: `text-sm text-foreground/90 leading-relaxed`
- Label kecil: `text-xs uppercase tracking-wider text-muted-foreground font-bold`

### Radius, Spacing, Shadow
- Radius besar: `rounded-3xl` untuk section utama
- Radius sedang: `rounded-2xl` untuk card/thumbnail
- Spacing container: `max-w-7xl mx-auto px-4` (tambahkan `sm:px-6 lg:px-8` untuk halaman text-heavy)
- Shadow: gunakan seperlunya (mis. card utama `shadow-xl` atau `shadow-2xl`)

## Layout & Navigasi
### Struktur Halaman Publik
- Gunakan `PublicLayout` untuk semua halaman publik agar navbar/footer/background konsisten.
- Konten utama ditempatkan di dalam container `max-w-7xl mx-auto`.

### Navigasi
- Navbar (desktop): link `Katalog`, `Tentang`, dan CTA `Login`.
- Navbar (mobile): gunakan `Sheet` dengan item menu yang sama.
- Indikator status toko: gunakan `StoreStatusPopover` pada navbar.

## Komponen & Pola Interaksi
### Banner (Carousel)
- Gunakan komponen: `resources/js/react/components/BannerCarousel.tsx` untuk banner di halaman awal.
- Rasio gambar: wajib `16:9` (sudah di-handle komponen).
- Fitur default:
  - Autoplay (interval dapat diatur)
  - Prev/Next buttons
  - Dot indicators yang dapat diklik
  - Swipe gesture (touchscreen)
  - Lazy loading + skeleton saat loading + fallback saat gagal
- Props utama:
  - `autoplayIntervalMs` (ms)
  - `transitionDuration` (Embla duration 20–60; makin besar makin lambat)
  - `showArrows`, `showDots`, `loop`
  - `overlay` per slide atau `renderOverlay(slide, state)`

### Button
- Pakai shadcn `Button` untuk semua tombol/CTA.
- Utamakan varian:
  - Primary CTA: default (`variant` default)
  - Secondary action: `variant="secondary"` atau `variant="outline"`
  - Link/toolbar: `variant="ghost"`

### Input / Filter
- Gunakan shadcn `Input` untuk pencarian.
- Gunakan shadcn `Select` untuk filter kategori.
- Hindari dropdown custom baru kecuali ada kebutuhan khusus.

### Card
- Gunakan shadcn `Card` untuk card produk, card informasi, dan section konten.
- Untuk “glass card” gunakan kelas tambahan: `bg-card/60 backdrop-blur-xl border-border rounded-3xl`.

### Badge
- Gunakan shadcn `Badge` untuk status:
  - Featured: kuning (`bg-yellow-500/20 text-yellow-300 border-yellow-500/30`)
  - Stok habis: merah (light: `text-red-700`, dark: `text-red-300`)
  - Stok menipis: oranye (light: `text-orange-700`, dark: `text-orange-300`)
  - Stok tersedia: hijau (light: `text-emerald-700`, dark: `text-emerald-300`)

### Responsiveness (Breakpoint)
- Mobile-first.
- Grid umum:
  - 1 kolom di mobile
  - `md:grid-cols-3` untuk grid info
  - `lg:grid-cols-12` untuk halaman detail (visual + info)

## Checklist Konsistensi
- Semua halaman publik memakai `PublicLayout`.
- Semua tombol memakai shadcn `Button`.
- Semua input/filter memakai shadcn `Input`/`Select`.
- Semua card memakai shadcn `Card`.
- Hierarki tipografi mengikuti skala di atas.
- Kontras dan border konsisten dengan semantic token (`border-border`).

## Theming
### Preferensi Tema
- Preferensi pengguna disimpan di `localStorage` key `theme` dengan nilai `light|dark`.
- Default: `dark` jika belum ada preferensi.
- Implementasi: `resources/js/react/lib/theme.js`.

### Anti Flicker (Pre-hydration)
- Theme diterapkan di `\u003chtml\u003e` sebelum CSS/JS React berjalan melalui script kecil di head:
  - Template: `resources/views/public/react.blade.php`
  - Partial: `resources/views/public/partials/theme-init.blade.php`

### Toggle Tema
- Komponen: `resources/js/react/components/ThemeToggle.jsx` (ikon matahari/bulan).
- Lokasi: navbar desktop + menu mobile, agar selalu accessible di semua halaman publik.

### Catatan Vite React Refresh (Dev)
- Untuk HMR React, Blade template harus menyertakan `@viteReactRefresh` sebelum `@vite(...)`.

---

## Admin UI Components

Untuk dokumentasi lengkap komponen UI admin (Livewire + Alpine.js + Tailwind CSS), lihat:

**[Style Guide - Admin UI Components](./style-guide-admin-ui.md)**

Dokumentasi admin UI mencakup:
- Toast Notification System
- Button, Input, Select, Dropdown
- Modal, Badge, Alert, Card
- Table, Page Header, Filter Section
- Empty State, Loading Spinner
- Pola penggunaan dan best practices
- Migrasi dari komponen lama

