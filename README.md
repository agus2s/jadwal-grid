# Jadwal MA Syamsul Huda — Aplikasi Penjadwalan

Proyek sederhana untuk manajemen jadwal pelajaran dengan ruang tetap per rombel.

Fitur utama:
- Ruang mengikuti nama rombel (ruang tetap).
- Validasi bentrok antar rombel dan guru.
- Tampilan grid jadwal (contoh: 12 rombel × 6 hari × 9 JP).

Struktur proyek (file/katalog penting):
- `index.php` — halaman utama
- `schedule.php`, `view_schedule.php` — manajemen dan tampilan jadwal
- `master.php` — halaman master data
- `report.php`, `report_export.php` — laporan dan ekspor
- `schedule_export.php` — ekspor jadwal
- `assets/` — CSS dan aset frontend (lihat `assets/css/app.css`)
- `config/database.php` — konfigurasi koneksi database
- `config/jam_pelajaran.json` — konfigurasi jam pelajaran
- `database/schema.sql` — skema/inisialisasi database
- `templates/` — `header.php` dan `footer.php`

Persyaratan:
- PHP 7.4+ (atau versi yang tersedia di XAMPP)
- Web server (disarankan XAMPP/Apache) atau built-in PHP server

Menjalankan lokal (opsi):

- Dengan XAMPP (direkomendasikan):
	1. Salin folder proyek ke `htdocs` (mis. `d:\xampp\htdocs\jadwal-grid`).
	2. Mulai Apache di XAMPP Control Panel.
	3. Buka `http://localhost/jadwal-grid/` di browser.

- Dengan server bawaan PHP (untuk pengembangan cepat):
```
php -S 127.0.0.1:8000
```
	lalu buka `http://127.0.0.1:8000`.

Konfigurasi awal:
- Sesuaikan koneksi database di `config/database.php`.
- Impor skema database dari `database/schema.sql`.
- Sesuaikan jam pelajaran di `config/jam_pelajaran.json` jika perlu.

Catatan data contoh: biasanya menggunakan 12 rombel, ~25 guru, ~22 mapel, 6 hari, 9 JP/hari.

Jika butuh bantuan menjalankan atau memperbarui README lebih rinci, beri tahu saya.
