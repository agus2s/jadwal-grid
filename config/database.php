<?php
/**
 * config/database.php
 * -------------------
 * File konfigurasi koneksi database.
 * Di-require oleh setiap halaman PHP sebelum mengakses data.
 */

declare(strict_types=1); // Aktifkan strict type: PHP akan error jika tipe data tidak sesuai

/**
 * Buat koneksi PDO ke database SQLite.
 * SQLite menyimpan seluruh data dalam satu file (.sqlite),
 * sehingga tidak perlu instalasi server database terpisah.
 */
$pdo = new PDO('sqlite:' . __DIR__ . '/../database/jadwal.sqlite');

// Jika terjadi error SQL, lempar Exception (bukan diam-diam gagal)
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Hasil query dikembalikan sebagai array asosiatif, contoh: $row['name']
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Aktifkan foreign key constraint di SQLite (secara default SQLite tidak mengaktifkannya)
$pdo->exec('PRAGMA foreign_keys=ON');

/**
 * Fungsi db() — getter koneksi database.
 * Digunakan di semua halaman dengan: $pdo = db();
 * Menggunakan variabel global $pdo agar satu koneksi dipakai bersama.
 */
function db(): PDO
{
    global $pdo;
    return $pdo;
}

/**
 * Fungsi e() — escape output HTML (XSS prevention).
 * Selalu gunakan e() saat menampilkan data dari database ke HTML,
 * agar karakter seperti <, >, " tidak diinterpretasikan sebagai HTML.
 *
 * Contoh: <?= e($row['name']) ?> → aman dari serangan XSS.
 */
function e(?string $v): string
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Mengambil data jam pelajaran dari config/jam_pelajaran.json.
 */
function get_jam_pelajaran(): array
{
    static $jam_pelajaran = null;
    if ($jam_pelajaran === null) {
        $path = __DIR__ . '/jam_pelajaran.json';
        if (file_exists($path)) {
            $jam_pelajaran = json_decode(file_get_contents($path), true) ?? [];
        } else {
            $jam_pelajaran = [];
        }
    }
    return $jam_pelajaran;
}

/**
 * Mengambil setting show_time. Default true.
 */
function get_setting_show_time(): bool
{
    static $show_time = null;
    if ($show_time === null) {
        $path = __DIR__ . '/settings.json';
        if (file_exists($path)) {
            $settings = json_decode(file_get_contents($path), true);
            $show_time = isset($settings['show_time']) ? (bool)$settings['show_time'] : true;
        } else {
            $show_time = true;
        }
    }
    return $show_time;
}

/**
 * Menyimpan setting show_time.
 */
function save_setting_show_time(bool $show): bool
{
    $path = __DIR__ . '/settings.json';
    $settings = [];
    if (file_exists($path)) {
        $settings = json_decode(file_get_contents($path), true) ?? [];
    }
    $settings['show_time'] = $show;
    return file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT)) !== false;
}
