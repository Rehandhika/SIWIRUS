# Panduan Pengiriman Email - SIWIRUS

Dokumen ini menjelaskan mekanisme, konfigurasi, dan cara pengujian fitur pengiriman email di aplikasi SIWIRUS.

## 1. Konfigurasi Environment (`.env`)

Untuk mengirim email secara nyata (misalnya menggunakan Gmail), pastikan variabel berikut telah diatur di file `.env` Anda:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=email-anda@gmail.com
MAIL_PASSWORD=app-password-dari-google
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="noreply@siwirus.com"
MAIL_FROM_NAME="${APP_NAME}"
```

> **Catatan Penting untuk Gmail:**
> - Pastikan **2-Step Verification** aktif di akun Google Anda.
> - Gunakan **App Password** (16 digit kode) yang dibuat melalui [Google Account Security](https://myaccount.google.com/apppasswords), bukan password login email biasa.

---

## 2. Mekanisme Antrean (Queue)

Aplikasi ini dirancang untuk mengirim email secara asinkron di latar belakang agar pengguna tidak perlu menunggu proses pengiriman selesai di browser.

### Pengaturan Driver Queue
Pastikan driver queue di `.env` sudah diatur ke `database`:
```env
QUEUE_CONNECTION=database
```

### Menjalankan Queue Worker
Email tidak akan terkirim jika worker tidak berjalan. Jalankan perintah ini di terminal:
```powershell
php artisan queue:work --queue=emails,default
```
*Gunakan opsi `--queue=emails,default` karena mailable di proyek ini diarahkan khusus ke queue bernama `emails`.*

---

## 3. Komponen Email dalam Kode

### Kelas Mailable (`app/Mail`)
Kelas ini mendefinisikan tampilan dan data email:
*   **`InitialCredentialsMail.php`**: Mengirim kredensial (NIM & Password) ke user baru.
*   **`ScheduleNotification.php`**: Notifikasi terkait jadwal piket/kerja.

### Kelas Job (`app/Jobs`)
Kadang email dipicu melalui Job khusus untuk kontrol lebih mendalam:
*   **`SendInitialCredentialsJob.php`**: Job yang membungkus pengiriman email kredensial dengan fitur *retry* otomatis jika gagal.

---

## 4. Cara Pengujian (Testing)

### Via Laravel Tinker
Ini adalah cara tercepat untuk mengetes apakah SMTP dan Queue berfungsi:

1. Buka Tinker:
   ```powershell
   php artisan tinker
   ```
2. Jalankan perintah pengiriman (ganti email tujuan):
   ```php
   $user = \App\Models\User::first();
   Mail::to('email-tujuan@gmail.com')->send(new \App\Mail\InitialCredentialsMail($user, 'password-test-123'));
   ```

### Memantau Status Pengiriman
*   **Database:** Cek tabel `jobs` untuk melihat apakah ada antrean yang tertunda.
*   **Log:** Cek file `storage/logs/laravel.log` jika terjadi error koneksi SMTP.
*   **Terminal Worker:** Perhatikan output `Processing` dan `Processed` pada jendela terminal yang menjalankan `queue:work`.

---

## 5. Troubleshooting (Masalah Umum)

| Masalah | Solusi |
| :--- | :--- |
| **Email masuk ke Spam** | Tambahkan email pengirim ke kontak atau gunakan domain email yang valid (bukan gmail gratisan jika untuk produksi). |
| **Authentication Failed** | Pastikan App Password benar dan `MAIL_ENCRYPTION` sesuai (SSL untuk port 465, TLS untuk port 587). |
| **Connection Timeout** | Pastikan server Anda (atau penyedia hosting) tidak memblokir port SMTP (465/587). |
| **Job Terhenti (Failed)** | Cek tabel `failed_jobs` dan jalankan `php artisan queue:retry all` setelah memperbaiki masalah. |
