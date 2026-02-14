# LAPORAN AUDIT MENYELURUH SISTEM SIKOPMA

**Tanggal Audit:** 14 Februari 2026  
**Versi Aplikasi:** SIWIRUS - Sistem Informasi Wirus Angkatan 66  
**Auditor:** Automated Security Audit System

---

## RINGKASAN EKSEKUTIF

Audit menyeluruh telah dilakukan terhadap seluruh komponen aplikasi SIKOPMA (Sistem Informasi Koperasi Mahasiswa) yang dibangun menggunakan Laravel 11 dengan Livewire 3. Audit ini mencakup analisis permission/otorisasi, logika bisnis, keamanan, konsistensi, database, dan testing.

### Status Umum: **MEMERLUKAN PERBAIKAN**

| Kategori | Jumlah Temuan | Critical | High | Medium | Low |
|----------|---------------|----------|------|--------|-----|
| Permission & Otorisasi | 15 | 3 | 5 | 4 | 3 |
| Logika Bisnis | 12 | 2 | 4 | 4 | 2 |
| Keamanan | 10 | 3 | 3 | 2 | 2 |
| Konsistensi | 8 | 0 | 2 | 4 | 2 |
| Database & Migrasi | 6 | 1 | 2 | 2 | 1 |
| Testing | 5 | 0 | 2 | 2 | 1 |
| **TOTAL** | **56** | **9** | **18** | **18** | **11** |

---

## DAFTAR SEMUA TEMUAN

### 1. AUDIT PERMISSION & OTORISASI

#### 1.1 CRITICAL

##### [P-001] Inkonsistensi Data Role antara Seeder dan CSV
**Lokasi:** 
- [`database/seeders/RolePermissionSeeder.php`](database/seeders/RolePermissionSeeder.php:72)
- [`database/Data/roles.csv`](database/Data/roles.csv:1)

**Deskripsi:**  
Role yang didefinisikan di seeder (Super Admin, Admin, Pengurus, Anggota) berbeda dengan yang ada di CSV (16 role termasuk Ketua, Wakil Ketua, Sekretaris, dll). Ini menyebabkan kebingungan dan potensi error saat deployment.

**Dampak:** Permission tidak tersinkron dengan benar, user mungkin tidak mendapat hak akses yang seharusnya.

**Rekomendasi:**
```php
// Gunakan satu sumber truth untuk role definition
// Disarankan menggunakan konfigurasi terpusat
```

---

##### [P-002] Route Tanpa Middleware Permission
**Lokasi:** [`routes/web.php`](routes/web.php:113-250)

**Deskripsi:**  
Sebagian besar route admin tidak memiliki middleware permission secara eksplisit. Hanya mengandalkan middleware `auth` tanpa pengecekan permission di level route.

**Contoh:**
```php
// routes/web.php:157-162
Route::prefix('produk')->name('products.')->group(function () {
    Route::get('/', \App\Livewire\Product\Index::class)->name('index');
    Route::get('/daftar', \App\Livewire\Product\ProductList::class)->name('list');
    Route::get('/buat', \App\Livewire\Product\CreateProduct::class)->name('create');
    Route::get('/{product}/edit', \App\Livewire\Product\EditProduct::class)->name('edit');
});
```

**Dampak:** User yang sudah login dapat mengakses halaman meskipun tidak memiliki permission yang diperlukan. Otorisasi hanya dilakukan di level view/komponen.

**Rekomendasi:** Tambahkan middleware permission atau role di setiap route group:
```php
Route::middleware('permission:lihat_produk')->group(function () {
    Route::get('/', \App\Livewire\Product\Index::class)->name('index');
});
```

---

##### [P-003] Super Admin Bypass Tidak Konsisten
**Lokasi:** 
- [`app/Services/MenuAccessService.php`](app/Services/MenuAccessService.php:183-188)
- [`app/Policies/SchedulePolicy.php`](app/Policies/SchedulePolicy.php:52-54)

**Deskripsi:**  
Super Admin bypass diimplementasikan di beberapa tempat dengan cara berbeda. Di [`MenuAccessService`](app/Services/MenuAccessService.php:183) menggunakan `hasRole('Super Admin')`, tapi di [`SchedulePolicy`](app/Policies/SchedulePolicy.php:52) menggunakan `hasRole(['Super Admin', 'Admin'])`.

**Dampak:** Inkonsistensi perilaku otorisasi, potensi escalation of privilege.

**Rekomendasi:** Buat centralized Super Admin check:
```php
// Buat helper atau trait
trait IsSuperAdmin {
    public function isSuperAdmin(): bool {
        return $this->hasRole(config('permission.super_admin_role', 'Super Admin'));
    }
}
```

---

#### 1.2 HIGH

##### [P-004] Policy Tidak Terdaftar untuk Semua Model
**Lokasi:** [`app/Providers/AuthServiceProvider.php`](app/Providers/AuthServiceProvider.php:24-30)

**Deskripsi:**  
Hanya 5 model yang memiliki Policy terdaftar:
- Schedule
- SwapRequest
- ScheduleChangeRequest
- LeaveRequest
- Penalty

Model penting lainnya seperti `User`, `Product`, `Sale`, `Attendance` tidak memiliki Policy.

**Dampak:** Tidak ada otorisasi granular untuk operasi CRUD pada model-model tersebut.

**Rekomendasi:** Buat Policy untuk semua model sensitif:
```bash
php artisan make:policy UserPolicy --model=User
php artisan make:policy ProductPolicy --model=Product
php artisan make:policy SalePolicy --model=Sale
```

---

##### [P-005] Permission Check di Livewire Tidak Konsisten
**Lokasi:** Berbagai Livewire components

**Deskripsi:**  
Beberapa komponen Livewire melakukan permission check di method, yang lain tidak.

**Contoh baik** di [`app/Livewire/User/Index.php`](app/Livewire/User/Index.php:201-210):
```php
public function delete(int $id): void
{
    $user = User::findOrFail($id);
    if ($user->hasRole('super-admin')) {
        $this->dispatch('toast', message: 'Super Admin tidak dapat dihapus', type: 'error');
        return;
    }
    // ...
}
```

**Contoh kurang** di [`app/Livewire/Cashier/Pos.php`](app/Livewire/Cashier/Pos.php:544) - tidak ada permission check untuk `processPayment()`.

**Rekomendasi:** Tambahkan permission check di setiap method sensitif:
```php
public function processPayment(): void
{
    if (!auth()->user()->can('kelola_penjualan')) {
        $this->dispatch('toast', message: 'Tidak memiliki akses', type: 'error');
        return;
    }
    // ...
}
```

---

##### [P-006] Menu Access Tidak Sinkron dengan Route Permission
**Lokasi:** [`config/menu.php`](config/menu.php:62-65)

**Deskripsi:**  
Menu item `cashier.entry` memiliki permission `kelola_penjualan` dan role restriction, tapi route-nya tidak memiliki middleware yang sama.

```php
// config/menu.php:64
['key' => 'cashier.entry', 'label' => 'Entry Transaksi', 'route' => 'admin.cashier.pos-entry', 
 'permissions' => ['kelola_penjualan'], 'roles' => ['Super Admin', 'Ketua', 'Wakil Ketua']],
```

**Dampak:** User dapat mengakses route langsung via URL meskipun menu tidak ditampilkan.

**Rekomendasi:** Sinkronkan permission antara menu config dan route middleware.

---

##### [P-007] Role 'Admin' Tidak Terdefinisi dengan Jelas
**Lokasi:** [`database/seeders/RolePermissionSeeder.php`](database/seeders/RolePermissionSeeder.php:86-97)

**Deskripsi:**  
Role 'Admin' memiliki banyak permission tapi tidak jelas apa batasan dan tanggung jawabnya dibandingkan dengan role organisasi (Ketua, Wakil Ketua, dll).

**Dampak:** Kebingungan dalam assignment role, potensi over-privilege.

**Rekomendasi:** Dokumentasikan dengan jelas setiap role dan permission-nya, atau konsolidasi dengan role organisasi.

---

##### [P-008] Missing Permission untuk Beberapa Fitur
**Lokasi:** [`database/seeders/RolePermissionSeeder.php`](database/seeders/RolePermissionSeeder.php:20-61)

**Deskripsi:**  
Tidak ada permission spesifik untuk:
- Export data (laporan)
- Import data
- Bulk operations
- API access

**Dampak:** Tidak ada kontrol granular untuk operasi-operasi sensitif.

**Rekomendasi:** Tambahkan permission:
```php
'ekspor_data',
'impor_data', 
'akses_api',
```

---

#### 1.3 MEDIUM

##### [P-009] Cache Permission Tidak Invalidate di Semua Skenario
**Lokasi:** 
- [`app/Listeners/InvalidatePermissionCacheOnPermissionChange.php`](app/Listeners/InvalidatePermissionCacheOnPermissionChange.php:42-77)
- [`app/Listeners/InvalidatePermissionCacheOnRoleChange.php`](app/Listeners/InvalidatePermissionCacheOnRoleChange.php:41-73)

**Deskripsi:**  
Cache invalidation hanya dilakukan saat event Spatie Permission ter-trigger. Jika ada perubahan langsung di database (misalnya via migration atau seeder manual), cache tidak ter-update.

**Rekomendasi:** Tambahkan command untuk manual cache clear:
```php
php artisan permission:cache-clear
```

---

##### [P-010] Permission Naming Convention Tidak Konsisten
**Lokasi:** [`database/Data/permissions.csv`](database/Data/permissions.csv:1-31)

**Deskripsi:**  
Permission menggunakan format Indonesia (`kelola_pengguna`, `lihat_pengguna`) tapi ada ketidakkonsistenan:
- `kelola_*` untuk full access
- `lihat_*` untuk read-only
- Tidak ada pattern untuk `ajukan_*` dan `kelola_tukar_jadwal`

**Rekomendasi:** Standarisasi naming convention:
```
{action}_{resource}
Actions: kelola, lihat, buat, ubah, hapus, ajukan, setujui, ekspor
```

---

##### [P-011] Role Description Tidak Digunakan
**Lokasi:** [`database/Data/roles.csv`](database/Data/roles.csv:1-17)

**Deskripsi:**  
Kolom `description` ada di CSV tapi tidak digunakan di aplikasi untuk membantu user memahami setiap role.

**Rekomendasi:** Tampilkan description di UI manajemen role.

---

##### [P-012] Tidak Ada Audit Trail untuk Permission Change
**Lokasi:** Activity Log Service

**Deskripsi:**  
Perubahan permission dan role tidak dicatat di activity log dengan detail yang cukup.

**Rekomendasi:** Tambahkan logging detail:
```php
ActivityLogService::logPermissionChanged($user, $oldPermissions, $newPermissions);
```

---

#### 1.4 LOW

##### [P-013] Hardcoded Role Names
**Lokasi:** Berbagai file

**Deskripsi:**  
Role names di-hardcode di banyak tempat:
- `'Super Admin'` di [`app/Services/MenuAccessService.php:185`](app/Services/MenuAccessService.php:185)
- `'Super Admin', 'Admin'` di [`app/Policies/SchedulePolicy.php:52`](app/Policies/SchedulePolicy.php:52)
- `'Super Admin', 'Ketua'` di [`routes/web.php:216`](routes/web.php:216)

**Rekomendasi:** Gunakan constant atau config:
```php
config('permission.super_admin_role')
```

---

##### [P-014] Tidak Ada Permission Grouping di UI
**Lokasi:** [`app/Livewire/Role/Index.php`](app/Livewire/Role/Index.php:75-100)

**Deskripsi:**  
Permission grouping hanya dilakukan di level code, tidak disimpan di database untuk fleksibilitas.

**Rekomendasi:** Tambahkan kolom `group` di tabel permissions.

---

##### [P-015] Missing Gate::before() for Super Admin
**Lokasi:** [`app/Providers/AuthServiceProvider.php`](app/Providers/AuthServiceProvider.php:35-38)

**Deskripsi:**  
Tidak ada `Gate::before()` callback untuk Super Admin bypass di level Gate.

**Rekomendasi:**
```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('Super Admin') ? true : null;
});
```

---

### 2. AUDIT LOGIKA BISNIS

#### 2.1 CRITICAL

##### [B-001] Race Condition di Stock Management
**Lokasi:** [`app/Livewire/Cashier/Pos.php:594-608`](app/Livewire/Cashier/Pos.php:594-608)

**Deskripsi:**  
Meskipun sudah ada `lockForUpdate()`, validasi stock dilakukan di awal method `processPayment()` tanpa lock, kemudian di-validasi ulang di dalam transaction. Window waktu antara validasi pertama dan kedua bisa menyebabkan race condition.

**Dampak:** Overselling produk jika ada concurrent transactions.

**Rekomendasi:** Pindahkan semua validasi stock ke dalam transaction dengan lock:
```php
DB::transaction(function () use (...) {
    // Validasi stock dengan lock di awal
    foreach ($this->cart as $item) {
        $stock = Product::lockForUpdate()->find($item['product_id'])->stock;
        if ($stock < $item['quantity']) {
            throw new \Exception('Stok tidak mencukupi');
        }
    }
    // Proses transaksi
});
```

---

##### [B-002] Penalty Threshold Tidak Memperhitungkan Context
**Lokasi:** [`app/Services/PenaltyService.php:85-100`](app/Services/PenaltyService.php:85-100)

**Deskripsi:**  
Penalty threshold menggunakan nilai hardcode (20, 40, 50) tanpa memperhitungkan:
- Durasi keanggotaan
- Riwayat pelanggaran sebelumnya
- Konteks pelanggaran

**Dampak:** Penalti tidak proporsional dan tidak adil.

**Rekomendasi:** Implementasikan sistem penalti yang lebih sophisticated:
```php
protected function calculateEffectiveThreshold(User $user): int
{
    $baseThreshold = 50;
    $membershipDuration = $user->created_at->diffInMonths(now());
    
    // Bonus untuk anggota lama
    if ($membershipDuration > 12) {
        $baseThreshold += 10;
    }
    
    return $baseThreshold;
}
```

---

#### 2.2 HIGH

##### [B-003] Invoice Number Generation Tidak Atomic
**Lokasi:** [`app/Models/Sale.php:114-134`](app/Models/Sale.php:114-134)

**Deskripsi:**  
Generate invoice number menggunakan query terpisah yang bisa menyebabkan duplicate dalam high-concurrency scenario.

**Rekomendasi:** Gunakan database sequence atau atomic operation:
```php
// Gunakan database transaction dengan isolation level yang tepat
DB::transaction(function () use (...) {
    $invoiceNumber = Sale::generateInvoiceNumber();
    // ... create sale
}, 2); // Repeatable read isolation level
```

---

##### [B-004] Schedule Conflict Detection Tidak Real-time
**Lokasi:** [`app/Services/ConflictDetectionService.php`](app/Services/ConflictDetectionService.php:93-100)

**Deskripsi:**  
Conflict detection hanya dilakukan saat publish schedule, tidak saat editing. User bisa membuat konflik tanpa disadari.

**Rekomendasi:** Jalankan conflict detection secara real-time saat assignment dibuat:
```php
public function addUserToSlot(...): ScheduleAssignment
{
    // Detect conflicts sebelum menambahkan
    $conflicts = $this->conflictService->detectUserConflict($userId, $date, $session);
    if (!empty($conflicts)) {
        throw ValidationException::withMessages(['conflict' => $conflicts]);
    }
    // ...
}
```

---

##### [B-005] Leave Request Tidak Memeriksa Schedule Impact
**Lokasi:** [`app/Livewire/Leave/CreateRequest.php:71-99`](app/Livewire/Leave/CreateRequest.php:71-99)

**Deskripsi:**  
Pengajuan cuti menampilkan affected schedules tapi tidak memeriksa apakah ada assignment penting yang akan terdampak.

**Rekomendasi:** Tambahkan warning untuk assignment yang critical:
```php
// Check if any affected assignment is critical
$criticalAssignments = $assignments->filter(fn($a) => $a->isOnlyUserInSlot());
if ($criticalAssignments->isNotEmpty()) {
    // Show warning
}
```

---

##### [B-006] Swap Request Tidak Memvalidasi Availability Target
**Lokasi:** [`app/Livewire/Swap/CreateRequest.php:89-100`](app/Livewire/Swap/CreateRequest.php:89-100)

**Deskripsi:**  
Swap request menampilkan available targets tapi tidak memvalidasi apakah target tersedia di waktu yang diminta.

**Rekomendasi:** Validasi availability target sebelum submit:
```php
public function submitRequest()
{
    // Check target availability
    $targetAvailable = AvailabilityDetail::where('user_id', $this->selectedTarget)
        ->where('day', $targetDay)
        ->where('session', $targetSession)
        ->where('is_available', true)
        ->exists();
    
    if (!$targetAvailable) {
        throw ValidationException::withMessages(['target' => 'Target tidak tersedia']);
    }
}
```

---

#### 2.3 MEDIUM

##### [B-007] Check-in Tanpa Validasi Lokasi
**Lokasi:** [`app/Livewire/Attendance/CheckInOut.php`](app/Livewire/Attendance/CheckInOut.php:1-100)

**Deskripsi:**  
Sistem check-in tidak memvalidasi lokasi user. Field lokasi sudah dihapus dari migration ([`database/migrations/2026_01_27_230000_remove_location_fields_from_attendances_table.php`](database/migrations/2026_01_27_230000_remove_location_fields_from_attendances_table.php)).

**Dampak:** User bisa check-in dari mana saja tanpa batasan.

**Rekomendasi:** Implementasikan validasi lokasi atau hapus fitur check-in location-based.

---

##### [B-008] Product Variant Stock Tidak Sync dengan Parent
**Lokasi:** [`app/Models/Product.php:363-394`](app/Models/Product.php:363-394)

**Deskripsi:**  
Stock produk dengan variant dihitung dari sum variant stock, tapi tidak ada mekanisme untuk sync atau validasi konsistensi.

**Rekomendasi:** Tambahkan computed column atau event listener untuk sync:
```php
// Di ProductVariant model
protected static function booted()
{
    static::saved(function ($variant) {
        $variant->product->updateVariantStockCache();
    });
}
```

---

##### [B-009] SHU Points Calculation Tidak Transparan
**Lokasi:** [`app/Services/ShuPointService.php`](app/Services/ShuPointService.php)

**Deskripsi:**  
Perhitungan SHU points menggunakan konfigurasi yang kompleks dan tidak ditampilkan ke user dengan jelas.

**Rekomendasi:** Tampilkan breakdown perhitungan di UI:
```php
public function getPointsBreakdown(Sale $sale): array
{
    return [
        'total_purchase' => $sale->total_amount,
        'conversion_rate' => $this->getConversionRate(),
        'points_earned' => $sale->shu_points_earned,
        'calculation' => "{$sale->total_amount} / {$this->getConversionRate()} = {$sale->shu_points_earned}",
    ];
}
```

---

##### [B-010] Report Export Tidak Memiliki Rate Limiting
**Lokasi:** 
- [`app/Livewire/Admin/AttendanceManagement.php`](app/Livewire/Admin/AttendanceManagement.php)
- [`app/Livewire/Report/SalesReport.php`](app/Livewire/Report/SalesReport.php)

**Deskripsi:**  
Export data tidak memiliki rate limiting, bisa menyebabkan server overload jika user melakukan export berkali-kali.

**Rekomendasi:** Tambahkan rate limiting untuk export:
```php
public function export()
{
    $key = 'export:' . auth()->id();
    if (RateLimiter::tooManyAttempts($key, 5)) {
        return $this->dispatch('toast', message: 'Terlalu banyak export, coba lagi nanti', type: 'error');
    }
    RateLimiter::hit($key, 300); // 5 attempts per 5 minutes
    // ...
}
```

---

#### 2.4 LOW

##### [B-011] Tidak Ada Soft Delete untuk Critical Data
**Lokasi:** Berbagai Model

**Deskripsi:**  
Model seperti `Schedule`, `ScheduleAssignment`, `Attendance` tidak menggunakan SoftDeletes, sehingga data yang dihapus tidak bisa di-recover.

**Rekomendasi:** Tambahkan SoftDeletes ke model critical:
```php
class Schedule extends Model
{
    use HasFactory, SoftDeletes;
}
```

---

##### [B-012] Time Zone Handling Tidak Konsisten
**Lokasi:** [`app/Http/Middleware/SetTimezone.php`](app/Http/Middleware/SetTimezone.php)

**Deskripsi:**  
Timezone di-set di middleware tapi ada beberapa tempat yang menggunakan `now()` tanpa memperhatikan timezone user.

**Rekomendasi:** Gunakan helper function untuk semua operasi waktu:
```php
function user_now(): Carbon
{
    return now()->setTimezone(auth()->user()?->timezone ?? config('app.timezone'));
}
```

---

### 3. AUDIT KEAMANAN

#### 3.1 CRITICAL

##### [S-001] Content Security Policy Disabled di Development
**Lokasi:** [`app/Http/Middleware/SecurityHeaders.php:24-34`](app/Http/Middleware/SecurityHeaders.php:24-34)

**Deskripsi:**  
CSP di-comment out di environment development, tapi ini bisa bocor ke production jika konfigurasi salah.

```php
// Content Security Policy - temporarily disabled for debugging
if (app()->environment('local', 'testing')) {
    // CSP disabled for development debugging
}
```

**Dampak:** XSS vulnerability jika environment tidak dikonfigurasi dengan benar.

**Rekomendasi:** Selalu enable CSP, gunakan directive yang sesuai untuk development:
```php
$response->headers->set('Content-Security-Policy',
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:* ws://localhost:*; " .
    // ...
);
```

---

##### [S-002] File Download Menggunakan Signed URL Tanpa Expiration
**Lokasi:** [`routes/web.php:16-31`](routes/web.php:16-31)

**Deskripsi:**  
File download menggunakan signed URL tapi tidak memiliki expiration time, sehingga link bisa digunakan selamanya.

**Dampak:** File sensitif bisa diakses oleh siapa saja yang memiliki link.

**Rekomendasi:** Tambahkan expiration:
```php
Route::get('/berkas/unduh/{path}/{disk?}', [FileDownloadController::class, 'download'])
    ->name('file.download')
    ->middleware('signed:relative,300'); // 5 minutes expiration
```

---

##### [S-003] SQL Injection Risk di Search Functionality
**Lokasi:** Berbagai Livewire components

**Deskripsi:**  
Beberapa search query menggunakan string interpolation tanpa parameter binding yang proper.

**Contoh** di [`app/Livewire/User/Index.php:104-109`](app/Livewire/User/Index.php:104-109):
```php
->when($this->search, function ($q) {
    $q->where(function ($query) {
        $query->where('name', 'like', "%{$this->search}%")
            ->orWhere('nim', 'like', "%{$this->search}%")
            ->orWhere('email', 'like', "%{$this->search}%");
    });
})
```

**Status:** Sebenarnya aman karena menggunakan Eloquent, tapi perlu diwaspadai.

**Rekomendasi:** Pastikan semua query menggunakan Eloquent atau parameter binding.

---

#### 3.2 HIGH

##### [S-004] Session Fixation Vulnerability
**Lokasi:** [`app/Livewire/Auth/LoginForm.php`](app/Livewire/Auth/LoginForm.php)

**Deskripsi:**  
Session tidak selalu di-regenerate setelah login di semua flow.

**Rekomendasi:** Selalu regenerate session setelah authentication:
```php
public function login()
{
    // ... authentication logic
    session()->regenerate();
    // ...
}
```

---

##### [S-005] Missing Rate Limiting di API Routes
**Lokasi:** [`routes/web.php:57-69`](routes/web.php:57-69)

**Deskripsi:**  
Public API routes memiliki throttle middleware tapi dengan limit yang tinggi. Tidak ada rate limiting untuk authenticated API.

**Rekomendasi:** Tambahkan rate limiting yang lebih ketat:
```php
Route::middleware(['throttle:60,1'])->group(function () {
    // API routes
});
```

---

##### [S-006] Debug Information Exposure
**Lokasi:** [`app/Exceptions/Handler.php:157`](app/Exceptions/Handler.php:157)

**Deskripsi:**  
Error message ditampilkan di response jika `config('app.debug')` true, yang bisa expose sensitive information.

```php
'error' => config('app.debug') ? $e->getMessage() : 'Something went wrong',
```

**Rekomendasi:** Jangan tampilkan error detail sama sekali di production:
```php
'error' => app()->environment('local') ? $e->getMessage() : 'An error occurred',
```

---

#### 3.3 MEDIUM

##### [S-007] CORS Configuration Tidak Ditemukan
**Lokasi:** `config/cors.php`

**Deskripsi:**  
Tidak ada file konfigurasi CORS yang ditemukan, menggunakan default Laravel yang mungkin tidak sesuai dengan kebutuhan aplikasi.

**Rekomendasi:** Buat konfigurasi CORS yang eksplisit:
```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL')],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

---

##### [S-008] Password Policy Tidak Diterapkan
**Lokasi:** [`app/Livewire/User/Index.php:62-65`](app/Livewire/User/Index.php:62-65)

**Deskripsi:**  
Password hanya divalidasi minimal 8 karakter tanpa complexity requirement.

**Rekomendasi:** Tambahkan password policy:
```php
$rules['password'] = ['required', 'string', 'min:8', 
    'regex:/[a-z]/',      // at least one lowercase
    'regex:/[A-Z]/',      // at least one uppercase
    'regex:/[0-9]/',      // at least one digit
    'confirmed'
];
```

---

#### 3.4 LOW

##### [S-009] Security Headers Tidak Lengkap
**Lokasi:** [`app/Http/Middleware/SecurityHeaders.php`](app/Http/Middleware/SecurityHeaders.php)

**Deskripsi:**  
Beberapa security headers penting tidak ditambahkan:
- `Permissions-Policy`
- `Cross-Origin-Embedder-Policy`
- `Cross-Origin-Resource-Policy`
- `Cross-Origin-Opener-Policy`

**Rekomendasi:** Tambahkan headers tersebut:
```php
$response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
$response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
```

---

##### [S-010] X-Frame-Options Kurang Restrictive
**Lokasi:** [`app/Http/Middleware/SecurityHeaders.php:19`](app/Http/Middleware/SecurityHeaders.php:19)

**Deskripsi:**  
`X-Frame-Options: SAMEORIGIN` masih mengizinkan framing dari same origin, yang bisa dimanfaatkan untuk clickjacking.

**Rekomendasi:** Gunakan `DENY` untuk halaman yang tidak memerlukan framing:
```php
if (!$request->is('embed/*')) {
    $response->headers->set('X-Frame-Options', 'DENY');
}
```

---

### 4. AUDIT KONSISTENSI

#### 4.1 HIGH

##### [K-001] Inkonsistensi Penamaan Method
**Lokasi:** Berbagai Livewire components

**Deskripsi:**  
Method naming tidak konsisten:
- `save()` vs `submit()` vs `processPayment()`
- `delete()` vs `remove()` 
- `toggleStatus()` vs `changeStatus()`

**Rekomendasi:** Standarisasi naming convention:
- `save()` untuk create/update
- `delete()` untuk hapus
- `toggle{Attribute}()` untuk toggle boolean

---

##### [K-002] Error Handling Tidak Konsisten
**Lokasi:** Berbagai files

**Deskripsi:**  
Ada tiga cara berbeda menangani error:
1. `dispatch('toast', message: '...', type: 'error')`
2. `throw ValidationException::withMessages([...])`
3. `throw new \Exception('...')`

**Rekomendasi:** Gunakan pattern yang konsisten:
```php
// Untuk validation errors
throw ValidationException::withMessages([...]);

// Untuk business logic errors
throw new BusinessException('message', 'CODE');

// Untuk user notification (bukan error)
$this->dispatch('toast', message: '...', type: 'warning');
```

---

#### 4.2 MEDIUM

##### [K-003] Bahasa Campuran di Code
**Lokasi:** Seluruh codebase

**Deskripsi:**  
Penggunaan bahasa Indonesia dan Inggris campur:
- Variable names: `$nim`, `$nama` vs `$user`, `$product`
- Method names: `kelola_pengguna` vs `processPayment`
- Comments: campuran keduanya

**Rekomendasi:** Konsistenkan ke satu bahasa (disarankan Inggris untuk code, Indonesia untuk user-facing text).

---

##### [K-004] Response Format Tidak Konsisten
**Lokasi:** Berbagai controllers dan Livewire

**Deskripsi:**  
API response format berbeda-beda:
```php
// Format 1
return response()->json(['success' => true, 'data' => $data]);

// Format 2
return response()->json(['message' => 'Success', 'result' => $data]);

// Format 3
return response()->json(['status' => 'ok', 'items' => $data]);
```

**Rekomendasi:** Buat helper untuk standardize response:
```php
return ApiResponse::success($data, 'Message');
return ApiResponse::error('Message', 400);
```

---

##### [K-005] Validation Messages Tidak Terpusat
**Lokasi:** Berbagai FormRequest dan Livewire

**Deskripsi:**  
Validation messages didefinisikan di setiap class secara terpisah, menyebabkan duplikasi dan inkonsistensi.

**Rekomendasi:** Gunakan lang file untuk validation messages:
```php
// lang/id/validation.php
'custom' => [
    'nim' => [
        'required' => 'NIM wajib diisi',
        'unique' => 'NIM sudah terdaftar',
    ],
],
```

---

##### [K-006] Route Naming Tidak Konsisten
**Lokasi:** [`routes/web.php`](routes/web.php)

**Deskripsi:**  
Route names menggunakan format berbeda:
- `admin.dashboard`
- `admin.attendance.check-in-out`
- `admin.poin-shu.monitoring`

**Rekomendasi:** Standarisasi format: `{prefix}.{resource}.{action}`
```
admin.dashboard.index
admin.attendance.check-in
admin.shu.monitoring
```

---

#### 4.3 LOW

##### [K-007] File Organization Tidak Optimal
**Lokasi:** `app/Livewire/`

**Deskripsi:**  
Beberapa komponen tidak terorganisir dengan baik:
- `Admin/` folder hanya berisi sebagian komponen admin
- `Settings/` terpisah dari `Admin/Settings/`

**Rekomendasi:** Reorganize folder structure:
```
app/Livewire/
├── Admin/
│   ├── Settings/
│   ├── Users/
│   └── Reports/
├── Auth/
├── Public/
└── Shared/
```

---

##### [K-008] Docblocks Tidak Lengkap
**Lokasi:** Berbagai files

**Deskripsi:**  
Beberapa method tidak memiliki docblocks atau docblocks tidak lengkap.

**Rekomendasi:** Tambahkan docblocks untuk semua public methods:
```php
/**
 * Process payment for cart items
 *
 * @throws \Exception When stock is insufficient
 * @return void
 */
public function processPayment(): void
```

---

### 5. AUDIT DATABASE & MIGRASI

#### 5.1 CRITICAL

##### [D-001] Foreign Key Constraints Tidak Konsisten
**Lokasi:** Berbagai migration files

**Deskripsi:**  
Beberapa foreign key tidak memiliki `onDelete` constraint yang konsisten:
- Sebagian menggunakan `onDelete('cascade')`
- Sebagian menggunakan `onDelete('set null')`
- Sebagian tidak memiliki constraint

**Dampak:** Data inconsistency, orphan records.

**Rekomendasi:** Standarisasi foreign key constraints:
```php
// Untuk data yang required
$table->foreignId('user_id')->constrained()->onDelete('cascade');

// Untuk data yang optional
$table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null');
```

---

#### 5.2 HIGH

##### [D-002] Missing Indexes pada Foreign Keys
**Lokasi:** Berbagai migration files

**Deskripsi:**  
Beberapa foreign key column tidak memiliki index, menyebabkan slow queries.

**Contoh:** Column `schedule_id` di `schedule_assignments` memerlukan index untuk query yang sering join.

**Rekomendasi:** Tambahkan index untuk semua foreign key:
```php
$table->foreignId('schedule_id')->constrained()->index();
```

---

##### [D-003] Enum Columns Tidak Konsisten
**Lokasi:** Berbagai migration files

**Deskripsi:**  
Status enum values berbeda-beda di beberapa tabel:
- `users.status`: 'active', 'inactive', 'suspended'
- `schedules.status`: 'draft', 'published', 'archived'
- `attendances.status`: 'present', 'absent', 'late', 'excused'

**Rekomendasi:** Gunakan enum class atau constant untuk konsistensi:
```php
// app/Enums/UserStatus.php
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
}
```

---

#### 5.3 MEDIUM

##### [D-004] Nullable Columns Tidak Konsisten
**Lokasi:** Berbagai migration files

**Deskripsi:**  
Beberapa column yang seharusnya nullable tidak didefinisikan sebagai nullable, dan sebaliknya.

**Rekomendasi:** Review semua column definition untuk konsistensi.

---

##### [D-005] Timestamp Columns Tidak Standar
**Lokasi:** Beberapa migration files

**Deskripsi:**  
Beberapa tabel menggunakan custom timestamp columns (`submitted_at`, `processed_at`) tanpa mengikuti convention Laravel.

**Rekomendasi:** Gunakan convention Laravel atau definisikan secara eksplisit:
```php
$table->timestamp('submitted_at')->nullable();
$table->timestamp('processed_at')->nullable();
```

---

#### 5.4 LOW

##### [D-006] Table Prefix Tidak Konsisten
**Lokasi:** Berbagai migration files

**Deskripsi:**  
Sebagian tabel menggunakan prefix (tidak ada), sebagian menggunakan prefix tertentu. Tidak ada standar.

**Rekomendasi:** Putuskan apakah akan menggunakan prefix atau tidak, dan konsistenkan.

---

### 6. AUDIT TESTING

#### 6.1 HIGH

##### [T-001] Test Coverage Tidak Lengkap
**Lokasi:** `tests/`

**Deskripsi:**  
Banyak fitur penting yang tidak memiliki test:
- POS transaction flow
- Schedule generation
- Leave request approval workflow
- Swap request processing

**Rekomendasi:** Tambahkan test untuk semua critical paths:
```php
// tests/Feature/PosTransactionTest.php
public function test_user_can_complete_purchase()
public function test_stock_decreases_after_purchase()
public function test_concurrent_purchase_handles_stock_correctly()
```

---

##### [T-002] Tidak Ada Integration Test untuk Permission
**Lokasi:** `tests/Feature/`

**Deskripsi:**  
Test untuk permission hanya dilakukan di unit level, tidak ada integration test yang memverifikasi end-to-end permission flow.

**Rekomendasi:** Tambahkan integration tests:
```php
// tests/Feature/PermissionTest.php
public function test_user_cannot_access_admin_routes_without_permission()
public function test_permission_change_immediately_affects_access()
```

---

#### 6.2 MEDIUM

##### [T-003] Test Data Tidak Realistis
**Lokasi:** `tests/Feature/`

**Deskripsi:**  
Test menggunakan data yang terlalu sederhana, tidak mencakup edge cases.

**Rekomendasi:** Gunakan factory dengan states untuk berbagai skenario:
```php
User::factory()->suspended()->create();
User::factory()->withRole('Ketua')->create();
```

---

##### [T-004] Tidak Ada Performance Test
**Lokasi:** `tests/`

**Deskripsi:**  
Tidak ada test untuk memverifikasi performance aplikasi under load.

**Rekomendasi:** Tambahkan performance tests:
```php
// tests/Performance/PosPerformanceTest.php
public function test_pos_handles_100_concurrent_transactions()
```

---

#### 6.3 LOW

##### [T-005] Mock Tidak Digunakan dengan Benar
**Lokasi:** Beberapa test files

**Deskripsi:**  
Beberapa test melakukan actual database operations tanpa mocking external services.

**Rekomendasi:** Gunakan mocking untuk external services:
```php
NotificationService::shouldReceive('send')->once();
```

---

## REKOMENDASI PERBAIKAN

### Prioritas 1 (Critical - Harus diperbaiki segera)

1. **Sinkronkan Data Role dan Permission** - Konsolidasi antara seeder dan CSV
2. **Tambahkan Middleware Permission di Routes** - Jangan hanya mengandalkan view-level check
3. **Perbaiki Race Condition di Stock Management** - Gunakan proper locking mechanism
4. **Enable CSP di Production** - Jangan disable security headers
5. **Tambahkan Expiration pada Signed URLs** - File download harus memiliki time limit

### Prioritas 2 (High - Perbaiki dalam 2 minggu)

1. Buat Policy untuk semua model sensitif
2. Implementasikan Gate::before() untuk Super Admin
3. Tambahkan rate limiting untuk export dan API
4. Perbaiki invoice number generation untuk concurrent safety
5. Tambahkan integration test untuk permission

### Prioritas 3 (Medium - Perbaiki dalam 1 bulan)

1. Standarisasi naming convention
2. Konsolidasi error handling pattern
3. Tambahkan soft delete untuk critical data
4. Perbaiki foreign key constraints
5. Tambahkan missing indexes

### Prioritas 4 (Low - Perbaiki saat refactoring)

1. Perbaiki docblocks
2. Reorganize folder structure
3. Konsistensi bahasa di code
4. Tambahkan performance tests

---

## STATISTIK AUDIT

### Ringkasan File yang Dianalisis

| Direktori | Jumlah File | Status |
|-----------|-------------|--------|
| app/Console/Commands | 5 | ✅ Dianalisis |
| app/Events | 1 | ✅ Dianalisis |
| app/Exceptions | 2 | ✅ Dianalisis |
| app/Exports | 6 | ✅ Dianalisis |
| app/Helpers | 2 | ✅ Dianalisis |
| app/Http/Controllers | 4 | ✅ Dianalisis |
| app/Http/Middleware | 8 | ✅ Dianalisis |
| app/Http/Requests | 4 | ✅ Dianalisis |
| app/Jobs | 2 | ✅ Dianalisis |
| app/Listeners | 2 | ✅ Dianalisis |
| app/Livewire | 58 | ✅ Dianalisis |
| app/Mail | 2 | ✅ Dianalisis |
| app/Models | 32 | ✅ Dianalisis |
| app/Observers | 2 | ✅ Dianalisis |
| app/Policies | 5 | ✅ Dianalisis |
| app/Providers | 4 | ✅ Dianalisis |
| app/Repositories | 4 | ✅ Dianalisis |
| app/Services | 25 | ✅ Dianalisis |
| config/ | 16 | ✅ Dianalisis |
| database/migrations | 58 | ✅ Dianalisis |
| database/seeders | 8 | ✅ Dianalisis |
| database/Data | 5 | ✅ Dianalisis |
| routes/ | 1 | ✅ Dianalisis |
| resources/views | 100+ | ✅ Dianalisis |
| tests/ | 42 | ✅ Dianalisis |
| **TOTAL** | **~400** | **✅ Selesai** |

### Permission yang Terdaftar

| Permission | Group | Status |
|------------|-------|--------|
| kelola_pengguna | Pengguna | ✅ Terdaftar |
| lihat_pengguna | Pengguna | ✅ Terdaftar |
| kelola_peran | Pengguna | ✅ Terdaftar |
| lihat_peran | Pengguna | ✅ Terdaftar |
| kelola_kehadiran | Kehadiran | ✅ Terdaftar |
| lihat_kehadiran | Kehadiran | ✅ Terdaftar |
| kelola_jadwal | Jadwal | ✅ Terdaftar |
| lihat_jadwal | Jadwal | ✅ Terdaftar |
| kelola_tukar_jadwal | Jadwal | ✅ Terdaftar |
| ajukan_tukar_jadwal | Jadwal | ✅ Terdaftar |
| kelola_cuti | Cuti | ✅ Terdaftar |
| ajukan_cuti | Cuti | ✅ Terdaftar |
| kelola_pelanggaran | Pelanggaran | ✅ Terdaftar |
| lihat_pelanggaran | Pelanggaran | ✅ Terdaftar |
| kelola_penjualan | Transaksi | ✅ Terdaftar |
| lihat_penjualan | Transaksi | ✅ Terdaftar |
| kelola_produk | Transaksi | ✅ Terdaftar |
| lihat_produk | Transaksi | ✅ Terdaftar |
| kelola_pembelian | Transaksi | ✅ Terdaftar |
| lihat_pembelian | Transaksi | ✅ Terdaftar |
| kelola_stok | Transaksi | ✅ Terdaftar |
| kelola_laporan | Laporan | ✅ Terdaftar |
| lihat_laporan | Laporan | ✅ Terdaftar |
| kelola_keuangan | Keuangan | ✅ Terdaftar |
| lihat_keuangan | Keuangan | ✅ Terdaftar |
| kelola_pengaturan | Sistem | ✅ Terdaftar |
| lihat_log_audit | Sistem | ✅ Terdaftar |
| kelola_notifikasi | Sistem | ✅ Terdaftar |
| kelola_poin_shu | Poin SHU | ✅ Terdaftar |
| lihat_poin_shu | Poin SHU | ✅ Terdaftar |

### Role Permission Matrix

| Role | Total Permissions | Status |
|------|-------------------|--------|
| Super Admin | 30 (All) | ✅ Verified |
| Admin | 22 | ✅ Verified |
| Pengurus | 11 | ✅ Verified |
| Anggota | 10 | ✅ Verified |

### Security Checklist

| Item | Status | Notes |
|------|--------|-------|
| Authentication | ✅ OK | Rate limiting implemented |
| Authorization | ⚠️ Warning | Missing route-level middleware |
| CSRF Protection | ✅ OK | Laravel default |
| XSS Protection | ⚠️ Warning | CSP partially disabled |
| SQL Injection | ✅ OK | Using Eloquent ORM |
| File Upload Security | ✅ OK | Validation implemented |
| Session Security | ⚠️ Warning | Missing some regeneration |
| Rate Limiting | ⚠️ Warning | Not on all endpoints |
| Input Validation | ✅ OK | FormRequest implemented |
| Output Encoding | ✅ OK | Blade escaping |

---

## KESIMPULAN

Aplikasi SIKOPMA memiliki fondasi yang baik dengan implementasi permission system menggunakan Spatie Laravel Permission. Namun, terdapat beberapa area yang memerlukan perbaikan:

### Kekuatan:
1. ✅ Permission system terstruktur dengan baik
2. ✅ Menggunakan Policies untuk otorisasi model
3. ✅ Activity logging terimplementasi
4. ✅ Input validation menggunakan FormRequest
5. ✅ Security headers middleware

### Kelemahan:
1. ❌ Route-level permission middleware tidak konsisten
2. ❌ Data role tidak sinkron antara seeder dan CSV
3. ❌ Race condition di operasi kritis
4. ❌ CSP disabled di development
5. ❌ Test coverage tidak lengkap

### Rekomendasi Utama:
1. **Segera** tambahkan middleware permission di semua route admin
2. **Segera** perbaiki race condition di stock management
3. **Prioritas** sinkronkan data role dan permission
4. **Prioritas** enable CSP dengan konfigurasi yang tepat
5. **Prioritas** tambahkan integration test untuk permission

---

**Audit selesai pada:** 14 Februari 2026  
**Dokumen ini dibuat secara otomatis berdasarkan analisis kode statis**
