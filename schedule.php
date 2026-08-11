<?php
/**
 * schedule.php — Penyusunan Jadwal Pelajaran
 * -------------------------------------------
 * Halaman untuk menambah, menghapus, dan melihat jadwal.
 *
 * Tersedia dua tampilan:
 *   - ?view=list  → Tabel daftar semua entri jadwal (default)
 *   - ?view=grid  → Tabel grid (Hari × JP) dengan kolom per rombel
 *
 * Validasi bentrok:
 *   - Rombel tidak boleh punya 2 mapel di hari & JP yang sama
 *   - Guru tidak boleh mengajar di 2 kelas di hari & JP yang sama
 */

require __DIR__ . '/config/database.php'; // Koneksi database
$pdo = db();

// Daftar hari sekolah — urutan menentukan urutan tampilan grid
$days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

/**
 * $dayColors — Warna tema per hari untuk tampilan badge dan grid.
 * 'bg'    : warna latar badge/baris
 * 'color' : warna teks
 * 'badge' : warna border badge
 */
$dayColors = [
    'Senin'  => ['bg' => '#ebf4ff', 'color' => '#2b6cb0', 'badge' => '#3182ce'],
    'Selasa' => ['bg' => '#faf5ff', 'color' => '#6b46c1', 'badge' => '#805ad5'],
    'Rabu'   => ['bg' => '#f0fff4', 'color' => '#276749', 'badge' => '#38a169'],
    'Kamis'  => ['bg' => '#fffaf0', 'color' => '#c05621', 'badge' => '#dd6b20'],
    'Jumat'  => ['bg' => '#fff5f5', 'color' => '#c53030', 'badge' => '#e53e3e'],
    'Sabtu'  => ['bg' => '#f7fafc', 'color' => '#4a5568', 'badge' => '#718096'],
];

// Ambil semua data master dari database (untuk isian dropdown form)
$classes  = $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
$teachers = $pdo->query('SELECT * FROM teachers ORDER BY name')->fetchAll();
$subjects = $pdo->query('SELECT * FROM subjects ORDER BY name')->fetchAll();

// Ambil tahun ajaran yang sedang aktif (active=1); hanya 1 tahun yang aktif
$year = $pdo->query('SELECT * FROM school_years WHERE active=1 LIMIT 1')->fetch();

$err = null; // Pesan error validasi bentrok
$ok  = null; // Pesan sukses setelah berhasil menyimpan

// ─────────────────────────────────────────────────
// HAPUS JADWAL
// Dipicu oleh URL: ?delete=<id>
// ─────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    // Cast ke int untuk mencegah SQL injection
    $pdo->prepare('DELETE FROM schedules WHERE id=?')->execute([(int) $_GET['delete']]);
    // Redirect ke halaman yang sama (PRG pattern: cegah hapus ulang saat refresh)
    header('Location: schedule.php');
    exit;
}

// ─────────────────────────────────────────────────
// TAMBAH JADWAL
// Dipicu saat form di-submit (method POST)
// ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /**
     * $p — Array parameter jadwal baru, berurutan sesuai query INSERT:
     * [0] school_year_id, [1] day, [2] jp, [3] class_id, [4] subject_id, [5] teacher_id
     */
    $p = [
        $year['id'],
        $_POST['day'],
        (int) $_POST['jp'],
        (int) $_POST['class_id'],
        (int) $_POST['subject_id'],
        (int) $_POST['teacher_id'],
    ];

    // ── Validasi 1: Apakah rombel sudah punya jadwal di hari & JP ini? ──
    $q = $pdo->prepare('SELECT COUNT(*) FROM schedules WHERE school_year_id=? AND day=? AND jp=? AND class_id=?');
    $q->execute([$p[0], $p[1], $p[2], $p[3]]);
    if ($q->fetchColumn()) {
        // fetchColumn() mengembalikan nilai COUNT(*) → > 0 berarti sudah ada
        $err = 'Rombel sudah terisi pada jam pelajaran tersebut.';
    }

    // ── Validasi 2: Apakah guru sudah mengajar di hari & JP yang sama? ──
    // Hanya cek jika validasi pertama lolos (supaya pesan error tidak menumpuk)
    if (!$err) {
        $q = $pdo->prepare('SELECT COUNT(*) FROM schedules WHERE school_year_id=? AND day=? AND jp=? AND teacher_id=?');
        $q->execute([$p[0], $p[1], $p[2], $p[5]]); // $p[5] = teacher_id
        if ($q->fetchColumn()) {
            $err = 'Guru sudah mengajar pada jam pelajaran tersebut.';
        }
    }

    // ── Simpan ke database jika tidak ada bentrok ──
    if (!$err) {
        $pdo->prepare('INSERT INTO schedules(school_year_id,day,jp,class_id,subject_id,teacher_id) VALUES(?,?,?,?,?,?)')
            ->execute($p);
        $ok = 'Jadwal berhasil ditambahkan!';
    }
}

/**
 * Ambil semua jadwal tahun aktif dengan JOIN ke tabel master
 * untuk mendapatkan nama rombel, guru, dan mapel (bukan hanya ID).
 *
 * Alias kolom:
 *   c.name  → class_name
 *   t.name  → teacher_name
 *   sub.name → subject_name
 */
$rows = $pdo->query("
    SELECT s.*, c.name class_name, t.name teacher_name, sub.name subject_name
    FROM schedules s
    JOIN classes  c   ON c.id   = s.class_id
    JOIN teachers t   ON t.id   = s.teacher_id
    JOIN subjects sub ON sub.id = s.subject_id
    WHERE s.school_year_id = " . (int) $year['id'] . "
    ORDER BY s.day, s.jp, c.name
")->fetchAll();

// Baca mode tampilan dari URL (?view=grid atau default 'list')
$view = $_GET['view'] ?? 'list';

require __DIR__ . '/templates/header.php'; // Render header HTML
?>

<!-- ─── Heading + tombol toggle tampilan ─── -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="page-heading mb-0"><i class="bi bi-calendar-plus"></i> Penyusunan Jadwal</h3>
    <div class="view-switcher d-flex gap-2">
        <!-- Kelas CSS 'active-view' ditambahkan ke tombol yang sesuai dengan $view aktif -->
        <a href="?view=list"
           class="btn btn-sm btn-outline-secondary <?= $view === 'list' ? 'active-view' : '' ?>">
            <i class="bi bi-list-ul me-1"></i>Daftar
        </a>
        <a href="?view=grid"
           class="btn btn-sm btn-outline-secondary <?= $view === 'grid' ? 'active-view' : '' ?>">
            <i class="bi bi-table me-1"></i>Grid
        </a>
    </div>
</div>

<!-- ─── Pesan error / sukses ─── -->
<?php if ($err): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?>
    </div>
<?php endif; ?>
<?php if ($ok): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-check-circle-fill"></i> <?= e($ok) ?>
    </div>
<?php endif; ?>

<!-- ─── Form Tambah Jadwal ─── -->
<div class="card mb-3">
    <div class="card-body">
        <p class="fw-semibold mb-3" style="color:#718096;font-size:.8rem;text-transform:uppercase;letter-spacing:.4px;">
            <i class="bi bi-plus-circle me-1"></i>Tambah Jadwal
        </p>
        <form method="post" class="row g-3 align-items-end">

            <!-- Dropdown Hari: diisi dari array $days -->
            <div class="col-md-2">
                <label class="form-label">Hari</label>
                <select name="day" class="form-select">
                    <?php foreach ($days as $d): ?>
                        <option><?= e($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dropdown JP (Jam Pelajaran): 1 – 9 -->
            <div class="col-md-1">
                <label class="form-label">JP</label>
                <select name="jp" class="form-select">
                    <?php for ($i = 1; $i <= 9; $i++): ?>
                        <option><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Dropdown Rombel: nama rombel = nama ruang (ruang tetap) -->
            <div class="col-md-2">
                <label class="form-label">Rombel / Ruang</label>
                <select name="class_id" class="form-select">
                    <?php foreach ($classes as $x): ?>
                        <option value="<?= $x['id'] ?>"><?= e($x['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dropdown Mata Pelajaran -->
            <div class="col-md-3">
                <label class="form-label">Mata Pelajaran</label>
                <select name="subject_id" class="form-select">
                    <?php foreach ($subjects as $x): ?>
                        <option value="<?= $x['id'] ?>"><?= e($x['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dropdown Guru -->
            <div class="col-md-3">
                <label class="form-label">Guru</label>
                <select name="teacher_id" class="form-select">
                    <?php foreach ($teachers as $x): ?>
                        <option value="<?= $x['id'] ?>"><?= e($x['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-1">
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>

        </form>
    </div>
</div>

<?php if ($view === 'grid'): ?>
<!-- ═══════════════════════════════════════════════════════
     TAMPILAN GRID
     Sumbu X (kolom) = Rombel, Sumbu Y (baris) = Hari × JP
     Setiap sel menampilkan mapel + guru jika ada jadwal.
     Loop: foreach hari → for JP 1..9 → foreach rombel
════════════════════════════════════════════════════════ -->
<div class="card">
    <!-- Wrapper scroll dua arah: horizontal (lebar 12 rombel) + vertikal (54 baris) -->
    <div class="grid-scroll-wrapper">
        <table class="table table-bordered table-sm text-center mb-0" style="font-size:.8rem;">
            <!-- sticky: thead tetap terlihat meski scroll ke bawah -->
            <thead class="grid-sticky-head">
                <tr>
                    <!-- Pojok kiri-atas: sticky di X dan Y sekaligus -->
                    <th class="grid-sticky-col" style="min-width:90px;">Hari / JP</th>
                    <!-- Header kolom: satu kolom per rombel -->
                    <?php foreach ($classes as $c): ?>
                        <th><?= e($c['name']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $d):
                    $dc      = $dayColors[$d] ?? [];            // Warna tema hari ini
                    $daySlug = 'day-' . strtolower($d);         // Kelas CSS, misal "day-senin"
                ?>
                    <?php for ($jp = 1; $jp <= 9; $jp++): ?>
                        <tr>
                            <!-- Kolom paling kiri: sticky di sumbu X agar tetap terlihat saat scroll kanan -->
                            <th class="<?= $daySlug ?> grid-sticky-col" style="white-space:nowrap;font-size:.75rem;font-weight:600;">
                                <?= $d ?><br>
                                <span style="font-weight:400;opacity:.75;">JP <?= $jp ?></span>
                            </th>

                            <!-- Satu sel per rombel pada hari & JP ini -->
                            <?php foreach ($classes as $cl): ?>
                                <td class="schedule-cell">
                                    <?php
                                    /**
                                     * Cari dari $rows apakah ada jadwal yang cocok:
                                     * hari = $d, JP = $jp, class_id = $cl['id']
                                     *
                                     * Catatan: ini adalah linear search O(n) di PHP.
                                     * Cukup untuk data kecil; bisa dioptimasi dengan
                                     * indexing jika data besar.
                                     */
                                    foreach ($rows as $r):
                                        if (
                                            $r['day']           === $d &&
                                            (int)$r['jp']        === $jp &&
                                            (int)$r['class_id']  === (int)$cl['id']
                                        ): ?>
                                            <!-- Sel berisi: nama mapel (tebal) + nama guru (kecil) -->
                                            <div class="cell-entry">
                                                <strong><?= e($r['subject_name']) ?></strong>
                                                <small><?= e($r['teacher_name']) ?></small>
                                            </div>
                                        <?php endif;
                                    endforeach; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endfor; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════
     TAMPILAN LIST (default)
     Menampilkan semua entri jadwal dalam bentuk tabel baris.
════════════════════════════════════════════════════════ -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Hari</th>
                    <th>JP</th>
                    <th>Rombel / Ruang</th>
                    <th>Mata Pelajaran</th>
                    <th>Guru</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <!-- Tampilkan pesan jika belum ada jadwal sama sekali -->
                    <tr>
                        <td colspan="7" class="text-center py-5" style="color:#a0aec0;">
                            <div style="font-size:2rem;margin-bottom:.5rem;">📋</div>
                            Belum ada jadwal. Tambahkan melalui form di atas.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $r):
                        // Ambil warna badge sesuai hari; fallback ke warna abu-abu
                        $dc = $dayColors[$r['day']] ?? ['badge' => '#718096'];
                    ?>
                        <tr>
                            <!-- Nomor urut (1-based, bukan ID database) -->
                            <td style="color:#a0aec0;font-size:.8rem;"><?= $i + 1 ?></td>

                            <!-- Badge hari berwarna sesuai $dayColors -->
                            <td>
                                <span class="badge-hari"
                                      style="background:<?= $dc['bg'] ?? '#f7fafc' ?>;
                                             color:<?= $dc['badge'] ?>;
                                             border:1px solid <?= $dc['badge'] ?>22;">
                                    <?= e($r['day']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="fw-bold" style="color:#009a44;">JP <?= $r['jp'] ?></span>
                            </td>

                            <td><?= e($r['class_name']) ?></td>    <!-- Dari JOIN ke tabel classes -->
                            <td><?= e($r['subject_name']) ?></td>  <!-- Dari JOIN ke tabel subjects -->
                            <td><?= e($r['teacher_name']) ?></td>  <!-- Dari JOIN ke tabel teachers -->

                            <td>
                                <!-- Konfirmasi JavaScript sebelum mengirim request hapus -->
                                <a class="btn btn-sm btn-outline-danger"
                                   href="?delete=<?= $r['id'] ?>"
                                   onclick="return confirm('Hapus jadwal ini?')">
                                    <i class="bi bi-trash3 me-1"></i>Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
