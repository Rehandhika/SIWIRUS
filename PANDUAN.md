# PANDUAN SIWIRUS - Simpel & Lengkap

Panduan lengkap untuk deploy dan maintenance SIWIRUS dengan upload manual (tanpa npm di server).

---

## 📦 PERSIAPAN LOKAL (Sebelum Upload)

### 1. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm install
```

### 2. Build Assets
```bash
npm run build
```

### 3. Optimize
```bash
php artisan config:cache
php artisan route:cache
composer dump-autoload --optimize
```

### 4. File yang Harus Di-Upload
```
✅ Upload semua KECUALI:
❌ node_modules/
❌ .git/
❌ .env (buat baru di server)
❌ storage/logs/* (buat folder kosong)
❌ vendor/ (optional, bisa upload atau install di server)
```

---

## 🚀 DEPLOY KE SERVER (Upload Manual)

### Step 1: Upload File
Upload semua file via FTP/SFTP ke folder web server (misal: `/public_html/siwirus`)

### Step 2: Buat .env di Server
Copy dari `.env.example`, lalu edit:
```env
APP_NAME=SIWIRUS
APP_ENV=production
APP_KEY=                          # Generate nanti
APP_DEBUG=false                   # PENTING: false!
APP_URL=https://domain-anda.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=user_database
DB_PASSWORD=password_database

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=email@gmail.com
MAIL_PASSWORD=app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@domain.com
MAIL_FROM_NAME=SIWIRUS

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

### Step 3: Install Composer (Jika Belum Upload vendor/)
```bash
composer install --no-dev --optimize-autoloader
```

### Step 4: Generate Key & Setup
```bash
php artisan key:generate
php artisan storage:link
```

### Step 5: Setup Database
```bash
# Buat database dulu via cPanel/phpMyAdmin
# Lalu jalankan:
php artisan migrate --force
php artisan db:seed --force
```

### Step 6: Set Permission
```bash
chmod -R 775 storage bootstrap/cache
chmod 600 .env
```

### Step 7: Cache Production
```bash
php artisan config:cache
php artisan route:cache
```

### Step 8: Test
Buka: `https://domain-anda.com`

**Login Default:**
- NIM: `00000000`
- Password: `password`

**⚠️ GANTI PASSWORD SEGERA!**

---

## 🔄 UPDATE APLIKASI (Deploy Ulang)

### Di Lokal:
```bash
# 1. Pull code terbaru
git pull

# 2. Install & build
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 3. Optimize
php artisan config:cache
php artisan route:cache
composer dump-autoload --optimize
```

### Di Server:
```bash
# 1. Backup database dulu!
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql

# 2. Upload file baru (overwrite)

# 3. Jalankan migration (jika ada)
php artisan migrate --force

# 4. Clear & rebuild cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# 5. Restart queue (jika pakai)
php artisan queue:restart
```

---

## 👥 MANAJEMEN USER

### Buat User Baru
```bash
php artisan tinker
```
```php
$user = App\Models\User::create([
    'nim' => '12345678',
    'name' => 'Nama User',
    'email' => 'email@example.com',
    'password' => bcrypt('password123'),
    'status' => 'active'
]);

// Assign role
$user->assignRole('Anggota');
```

### Kirim Email Kredensial (Satu User)
```bash
php artisan tinker
```
```php
$user = App\Models\User::where('nim', '12345678')->first();
$password = 'password123'; // Password yang mau dikirim

App\Jobs\SendInitialCredentialsJob::dispatch($user, $password);
```

### Kirim Email Kredensial (Semua User Baru)
```bash
php artisan tinker
```
```php
// Kirim ke semua user yang belum pernah login
$users = App\Models\User::whereNull('last_login_at')->get();

foreach ($users as $user) {
    $password = 'password123'; // Atau generate random
    App\Jobs\SendInitialCredentialsJob::dispatch($user, $password);
}
```

### Reset Password User
```bash
php artisan tinker
```
```php
$user = App\Models\User::where('nim', '12345678')->first();
$user->password = bcrypt('password_baru');
$user->save();
```

### Ganti Role User
```bash
php artisan tinker
```
```php
$user = App\Models\User::where('nim', '12345678')->first();
$user->syncRoles(['Ketua']); // Ganti role
```

### Nonaktifkan User
```bash
php artisan tinker
```
```php
$user = App\Models\User::where('nim', '12345678')->first();
$user->status = 'inactive';
$user->save();
```

---

## 📧 EMAIL & QUEUE

### Setup Email (Gmail)
1. Buat App Password di Google Account
2. Edit `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=email@gmail.com
MAIL_PASSWORD=app_password_dari_google
MAIL_ENCRYPTION=tls
```

### Test Kirim Email
```bash
php artisan tinker
```
```php
Mail::raw('Test email', function($msg) {
    $msg->to('tujuan@email.com')->subject('Test');
});
```

### Jalankan Queue Worker (Background Jobs)
```bash
# Development (manual)
php artisan queue:work

# Production (pakai screen/tmux)
screen -S queue
php artisan queue:work --sleep=3 --tries=3
# Tekan Ctrl+A lalu D untuk detach
```

### Cek Queue Status
```bash
# Lihat failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry all

# Hapus failed jobs
php artisan queue:flush
```

---

## 🗄️ DATABASE

### Backup Database
```bash
# Manual
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql

# Atau via cPanel Backup
```

### Restore Database
```bash
mysql -u user -p database < backup_20260129.sql
```

### Reset Database (HATI-HATI!)
```bash
php artisan migrate:fresh --seed --force
```

### Lihat Data
```bash
php artisan tinker
```
```php
// Lihat semua user
App\Models\User::all();

// Lihat user tertentu
App\Models\User::where('nim', '12345678')->first();

// Count data
App\Models\Product::count();
App\Models\Sale::count();
```

---

## 🔧 MAINTENANCE

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Rebuild Cache
```bash
php artisan config:cache
php artisan route:cache
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

### Disk Space
```bash
# Hapus old logs
rm storage/logs/laravel-*.log

# Hapus cache files
php artisan cache:clear
```

### Maintenance Mode
```bash
# Enable (tutup sementara)
php artisan down

# Disable (buka lagi)
php artisan up
```

---

## 📊 MONITORING

### Cek Status Aplikasi
```bash
# Database connection
php artisan db:show

# Routes
php artisan route:list

# Config
php artisan config:show

# Queue
php artisan queue:monitor
```

### Cek Error
```bash
# Lihat log
tail -50 storage/logs/laravel.log

# Lihat failed jobs
php artisan queue:failed
```

---

## 🔐 KEAMANAN

### Checklist Wajib
- [ ] `APP_DEBUG=false` di production
- [ ] `APP_ENV=production`
- [ ] Ganti password default (NIM: 00000000)
- [ ] File `.env` chmod 600
- [ ] Folder `storage/` chmod 775
- [ ] HTTPS aktif (SSL)
- [ ] Backup database rutin

### Ganti APP_KEY (Jika Perlu)
```bash
# Backup .env dulu!
cp .env .env.backup

# Generate key baru
php artisan key:generate
```

---

## 🐛 TROUBLESHOOTING

### Error: "No application encryption key"
```bash
php artisan key:generate
```

### Error: "Permission denied"
```bash
chmod -R 775 storage bootstrap/cache
```

### Error: "Class not found"
```bash
composer dump-autoload
```

### Error: "SQLSTATE connection refused"
- Cek DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD di `.env`
- Pastikan database sudah dibuat

### Error: "419 Page Expired" (CSRF)
```bash
php artisan config:clear
php artisan cache:clear
```

### Error: "500 Internal Server Error"
```bash
# Cek log
tail -50 storage/logs/laravel.log

# Clear cache
php artisan optimize:clear
```

### Queue Tidak Jalan
```bash
# Restart queue worker
php artisan queue:restart

# Atau kill dan start ulang
pkill -f "queue:work"
php artisan queue:work &
```

---

## 📝 COMMAND PENTING

### Artisan Commands
```bash
# Generate key
php artisan key:generate

# Migration
php artisan migrate --force
php artisan migrate:fresh --seed --force

# Cache
php artisan config:cache
php artisan route:cache
php artisan cache:clear

# Queue
php artisan queue:work
php artisan queue:restart
php artisan queue:failed
php artisan queue:retry all

# Maintenance
php artisan down
php artisan up

# Storage
php artisan storage:link

# Tinker (console)
php artisan tinker
```

### Composer Commands
```bash
# Install production
composer install --no-dev --optimize-autoloader

# Update
composer update --no-dev

# Dump autoload
composer dump-autoload --optimize
```

### NPM Commands (Lokal)
```bash
# Install
npm install

# Build production
npm run build

# Development
npm run dev
```

---

## 🎯 WORKFLOW HARIAN

### Pagi (Cek Status)
```bash
# Cek logs
tail -50 storage/logs/laravel.log

# Cek queue
php artisan queue:monitor

# Cek failed jobs
php artisan queue:failed
```

### Siang (Maintenance)
```bash
# Clear cache jika perlu
php artisan cache:clear

# Restart queue jika stuck
php artisan queue:restart
```

### Malam (Backup)
```bash
# Backup database
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql
```

---

## 📞 KONTAK DARURAT

Jika ada masalah:
1. Cek `storage/logs/laravel.log`
2. Cek `.env` configuration
3. Clear semua cache: `php artisan optimize:clear`
4. Restart queue: `php artisan queue:restart`
5. Maintenance mode: `php artisan down`

---

**Dibuat:** 29 Januari 2026  
**Untuk:** Deployment Manual (Tanpa NPM di Server)  
**Status:** Production Ready ✅
