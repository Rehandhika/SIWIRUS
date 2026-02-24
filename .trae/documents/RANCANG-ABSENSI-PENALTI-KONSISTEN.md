# Rencana Implementasi: Konsolidasi Absensi ↔ Penalti ↔ Laporan

## Tujuan
- Membuat aturan keterlambatan yang konsisten dan sederhana (A/B/C) di seluruh sistem.
- Memastikan deteksi keterlambatan otomatis saat check-in, dengan pembuatan penalti yang tepat.
- Menyamakan tampilan dan laporan (Laporan Absensi & Laporan Penalti) agar menampilkan kategori keterlambatan yang sama.
- Menata alur status jadwal dan monitor shift agar sesuai dengan kenyataan (aktif saat check-in, selesai saat check-out).

## Ringkasan Arsitektur (yang relevan)
- Absensi
  - Komponen check-in/out: `app/Livewire/Attendance/CheckInOut.php`
  - Layanan inti: `app/Services/AttendanceService.php` (determinasi status & penalti)
  - Model: `app/Models/Attendance.php`, `app/Models/ScheduleAssignment.php`
- Penalti
  - Layanan: `app/Services/PenaltyService.php`
  - Model: `app/Models/Penalty.php` (dan `PenaltyType`)
  - Laporan: `app/Livewire/Report/PenaltyReport.php`
- Laporan Absensi (Admin)
  - Komponen: `app/Livewire/Admin/AttendanceManagement.php`
  - Ekspor: `app/Exports/AttendanceExport.php`
- Monitor
  - Dashboard Admin: `app/Livewire/Dashboard/DashboardIndex.php`

## Aturan Keterlambatan Baru (Konsisten)
- Terlambat A: 10–30 menit (inklusif 10, inklusif 30).
- Terlambat B: 31–60 menit (lebih dari 30 hingga 60 menit inklusif).
- Terlambat C: >60 menit.
- Grace period: ≤9 menit dianggap hadir (present) tanpa penalti.

Catatan: Menghindari overlap 30 menit dengan menetapkan A (≤30) dan B (>30).

## Perubahan Back-end (Desain)
1) AttendanceService
- `determineStatus(checkInTime, schedule)`
  - Hitung keterlambatan berdasarkan waktu mulai aktual:
    - Gunakan `ScheduleAssignment->time_start` jika ada; fallback mapping sesi bila perlu.
  - `minutesLate = max(0, scheduleStart.diffInMinutes(checkInTime))`.
  - Kembalikan:
    - `status = 'present'` jika minutesLate ≤ 9.
    - `status = 'late'` jika minutesLate ≥ 10, dan sertakan `late_minutes`.
- Integrasi penalti pada check-in (tetap di `checkIn()`):
  - Jika `status === 'late'`:
    - Map minutesLate → kategori A/B/C (lihat aturan).
    - Panggil `PenaltyService::createPenalty` dengan kode penalti konsisten (lihat bagian Penalty Type).
  - Set `schedule_assignment`:
    - Saat check-in: status slot disarankan menjadi `in_progress` (opsional — jika ingin alur lebih natural).
    - Saat check-out: set `completed`.

2) Penalty Type (Mapping)
- Definisikan tiga kode penalti baru:
  - `LATE_A` (10–30) → poin sesuai kebijakan (mis. 5).
  - `LATE_B` (31–60) → poin (mis. 10).
  - `LATE_C` (>60) → poin (mis. 15).
- Jika saat ini ada `LATE_MINOR/MODERATE/SEVERE`:
  - Opsi A (disarankan): Tambahkan `LATE_A/B/C` dan gunakan ke depan. Biarkan kode lama untuk data historis (read-only).
  - Opsi B: Alias mapping lama → baru (membutuhkan migrasi data). 

3) Konsistensi Status Jadwal
- Tambahkan (opsional namun direkomendasikan) status `in_progress` pada `ScheduleAssignment`.
  - Transition:
    - `scheduled` → `in_progress` (check-in).
    - `in_progress` → `completed` (check-out).
  - `missed` tetap dari proses penandaan absen.

## Perubahan Front-end & Laporan
1) Laporan Absensi (Admin)
- Tambah kolom/indikator “Kategori Terlambat” pada tabel & ekspor Excel.
- Filter tambahan: Kategori (A/B/C) dan rentang menit terlambat.
- Statistik kartu: jumlah A, B, C (opsional).

2) Laporan Penalti
- Pastikan label & filter menampilkan `LATE_A/B/C` dengan deskripsi jelas.
- Tampilkan tautan referensi ke attendance (reference_type=attendance) bila tersedia.

3) Live Shift Monitor
- Tetap hanya menampilkan attendance aktif (check-in tanpa check-out), baik scheduled maupun override — sudah disesuaikan.

4) Check-in UI
- Notifikasi sukses menampilkan kategori jika terlambat (contoh: “Terlambat B (44 menit)”).

## Konfigurasi
- Tambah parameter di config (misal `config/app-settings.php`):
  - `attendance.grace_minutes = 9`
  - `attendance.late_ranges = ['A' => [10,30], 'B' => [31,60], 'C' => [61,null]]`
  - `penalty.points = ['LATE_A' => 5, 'LATE_B' => 10, 'LATE_C' => 15]`

## Migrasi & Data
- Seeder PenaltyType untuk `LATE_A/B/C` jika belum ada (name, code, points, is_active).
- (Opsional) Skrip backfill untuk mengonversi penalti lama ke kode baru, atau hanya memberikan alias pada laporan.

## Pengujian (Wajib)
- Unit test `determineStatus` (batas 9,10,30,31,60,61).
- Unit test `checkIn` menghasilkan penalti dengan kode & poin tepat.
- Test integrasi: check-in → dashboard monitor aktif → laporan absensi/penalti menampilkan kategori yang sama.
- Boundary: override mode, tanpa jadwal, jadwal yang berubah, multi-sesi per hari.

## Rencana Implementasi Bertahap
1) Tambah konfigurasi & PenaltyType (A/B/C) + seeder.
2) Update `AttendanceService` (penentuan status, mapping kategori, pembuatan penalti).
3) (Opsional) Tambah status `in_progress` & sesuaikan pembaruan status check-in/out.
4) Update UI & ekspor Laporan Absensi (kolom kategori) + filter.
5) Pastikan Laporan Penalti menampilkan keterangan kategori konsisten.
6) Tambah notifikasi keterlambatan (judul & deskripsi jelas).
7) Tambah & jalankan test.
8) UAT & verifikasi data di staging, lalu rilis.

## Jaminan Konsistensi
- Satu sumber kebenaran: aturan menit keterlambatan berada di konfigurasi.
- Backend mengonsolidasikan penentuan status & kategori; UI hanya menampilkan hasil.
- Kode penalti tunggal (LATE_A/B/C) digunakan di seluruh sistem (penalti, laporan, notifikasi).

## Risiko & Mitigasi
- Ketidaksesuaian data historis: biarkan kode lama tetap ada atau buat mapping di laporan.
- Jadwal yang telah diubah setelah check-in: gunakan cap waktu check-in & time_start saat itu; tampilkan catatan bila perlu.
- Performa laporan: pastikan query menggunakan indeks pada kolom tanggal/status.

## Keluaran Akhir
- Sistem absensi terintegrasi dengan penalti A/B/C yang konsisten.
- Laporan absensi & penalti menampilkan kategori yang sama + ekspor yang akurat.
- Monitor & notifikasi selaras dengan status real-time.

