<?php
/**
 * index.php — Dashboard
 * ---------------------
 * Halaman utama aplikasi. Menampilkan ringkasan jumlah data
 * dari tiap tabel utama: Guru, Mapel, Rombel, dan Jadwal.
 */

require __DIR__ . '/config/database.php'; // Memuat konfigurasi & koneksi database
$pdo   = db();           // Ambil objek PDO dari fungsi db()
$title = 'Dashboard';   // Judul halaman, digunakan di <title> oleh header.php
require __DIR__ . '/templates/header.php'; // Render HTML pembuka (navbar, sidebar, dsb.)
?>

<h3 class="page-heading"><i class="bi bi-speedometer2"></i> Dashboard</h3>

<div class="row">
    <?php
    /**
     * Loop melalui 4 tabel utama.
     * - Key   ($t) = nama tabel di database
     * - Value = [label, nama ikon Bootstrap Icons, warna aksen]
     *
     * Untuk setiap tabel, hitung jumlah baris dengan COUNT(*),
     * lalu tampilkan sebagai kartu statistik dengan ikon.
     */
    $cards = [
        'teachers' => ['Guru',         'bi-person-badge', '#3182ce', '#ebf4ff'],
        'subjects' => ['Mapel',        'bi-book',         '#38a169', '#f0fff4'],
        'classes'  => ['Rombel',       'bi-building',     '#dd6b20', '#fffaf0'],
        'schedules'=> ['Jadwal',       'bi-calendar-week','#805ad5', '#faf5ff'],
    ];
    foreach ($cards as $t => [$n, $icon, $color, $bg]):
        $x = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn(); // Ambil 1 nilai: jumlah baris
    ?>
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm" style="border-top: 3px solid <?= $color ?>;">
            <div class="card-body d-flex align-items-center gap-3">
                <!-- Ikon bulat berwarna sesuai tema tabel -->
                <div style="width:48px;height:48px;border-radius:12px;background:<?= $bg ?>;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi <?= $icon ?>" style="font-size:1.4rem;color:<?= $color ?>;"></i>
                </div>
                <div>
                    <div style="font-size:.78rem;font-weight:600;text-transform:uppercase;
                                letter-spacing:.4px;color:#718096;"><?= $n ?></div>
                    <b class="display-6" style="color:<?= $color ?>; line-height:1;"><?= $x ?></b>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/templates/footer.php'; // Render HTML penutup ?>