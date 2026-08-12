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

$days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

$year = $pdo->query('SELECT * FROM school_years WHERE active=1 LIMIT 1')->fetch();
$classes = $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
$teachers = $pdo->query('SELECT * FROM teachers ORDER BY name')->fetchAll();

if (!$year) {
    $year = ['name' => 'Belum ditentukan'];
}

$tab = $_GET['tab'] ?? 'rombel';
$selected_class_id   = isset($_GET['class_id']) ? (int)$_GET['class_id'] : ($classes[0]['id'] ?? 0);
$selected_teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : ($teachers[0]['id'] ?? 0);
$selected_day        = $_GET['day'] ?? 'Senin';

$rows = [];
if ($tab === 'rombel' && $selected_class_id) {
    $stmt = $pdo->prepare("SELECT s.*, t.name teacher_name, sub.name subject_name FROM schedules s JOIN teachers t ON t.id = s.teacher_id JOIN subjects sub ON sub.id = s.subject_id WHERE s.school_year_id = ? AND s.class_id = ?");
    $stmt->execute([$year['id'] ?? 0, $selected_class_id]);
    $rows = $stmt->fetchAll();
} elseif ($tab === 'teacher' && $selected_teacher_id) {
    $stmt = $pdo->prepare("SELECT s.*, c.name class_name, sub.name subject_name FROM schedules s JOIN classes c ON c.id = s.class_id JOIN subjects sub ON sub.id = s.subject_id WHERE s.school_year_id = ? AND s.teacher_id = ?");
    $stmt->execute([$year['id'] ?? 0, $selected_teacher_id]);
    $rows = $stmt->fetchAll();
} elseif ($tab === 'day') {
    $stmt = $pdo->prepare("SELECT s.*, c.name class_name, t.name teacher_name, sub.name subject_name FROM schedules s JOIN classes c ON c.id = s.class_id JOIN teachers t ON t.id = s.teacher_id JOIN subjects sub ON sub.id = s.subject_id WHERE s.school_year_id = ? AND s.day = ?");
    $stmt->execute([$year['id'] ?? 0, $selected_day]);
    $rows = $stmt->fetchAll();
}
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

<div class="mt-4 pt-3 border-top">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="page-heading mb-0"><i class="bi bi-calendar3"></i> Lihat Jadwal</h3>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-3">
            Tahun Ajaran: <strong><?= e($year['name']) ?></strong>
        </span>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body p-2">
            <ul class="nav nav-pills nav-fill gap-2">
                <li class="nav-item">
                    <a class="nav-link py-2.5 <?= $tab === 'rombel' ? 'active bg-success text-white' : 'text-secondary' ?>" href="?tab=rombel&class_id=<?= $selected_class_id ?>">
                        <i class="bi bi-building me-2"></i>Berdasarkan Rombel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2.5 <?= $tab === 'day' ? 'active bg-success text-white' : 'text-secondary' ?>" href="?tab=day&day=<?= urlencode($selected_day) ?>">
                        <i class="bi bi-calendar-day me-2"></i>Berdasarkan Hari
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2.5 <?= $tab === 'teacher' ? 'active bg-success text-white' : 'text-secondary' ?>" href="?tab=teacher&teacher_id=<?= $selected_teacher_id ?>">
                        <i class="bi bi-person-badge me-2"></i>Berdasarkan Guru
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end justify-content-start">
                <input type="hidden" name="tab" value="<?= e($tab) ?>">

                <?php if ($tab === 'rombel'): ?>
                    <div class="col-md-4">
                        <label class="form-label">Pilih Rombel / Kelas</label>
                        <select name="class_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $selected_class_id === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php elseif ($tab === 'day'): ?>
                    <div class="col-md-4">
                        <label class="form-label">Pilih Hari</label>
                        <select name="day" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($days as $d): ?>
                                <option value="<?= e($d) ?>" <?= $selected_day === $d ? 'selected' : '' ?>>
                                    <?= e($d) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php elseif ($tab === 'teacher'): ?>
                    <div class="col-md-4">
                        <label class="form-label">Pilih Guru</label>
                        <select name="teacher_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $selected_teacher_id === (int)$t['id'] ? 'selected' : '' ?>>
                                    <?= e($t['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100 py-2">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($tab === 'rombel' || $tab === 'teacher'): ?>
        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle mb-0" style="min-width: 750px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;">JP</th>
                            <?php foreach ($days as $d): ?>
                                <th><?= e($d) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($jp = 1; $jp <= 9; $jp++): ?>
                            <tr>
                                <td class="fw-bold text-success bg-light">JP <?= $jp ?></td>
                                <?php foreach ($days as $d): ?>
                                    <td class="p-2" style="height: 75px; vertical-align: top;">
                                        <?php
                                        $cell_found = false;
                                        foreach ($rows as $r) {
                                            if ($r['day'] === $d && (int)$r['jp'] === $jp) {
                                                $cell_found = true;
                                                if ($tab === 'rombel') {
                                                    ?>
                                                    <div class="cell-entry text-start">
                                                        <strong class="d-block"><?= e($r['subject_name']) ?></strong>
                                                        <small class="text-muted"><i class="bi bi-person me-1"></i><?= e($r['teacher_name']) ?></small>
                                                    </div>
                                                    <?php
                                                } else {
                                                    ?>
                                                    <div class="cell-entry text-start" style="border-left-color: #805ad5;">
                                                        <strong class="d-block" style="color: #6b46c1;"><?= e($r['class_name']) ?></strong>
                                                        <small class="text-muted"><i class="bi bi-book me-1"></i><?= e($r['subject_name']) ?></small>
                                                    </div>
                                                    <?php
                                                }
                                                break;
                                            }
                                        }
                                        if (!$cell_found): ?>
                                            <span class="text-muted" style="font-size: 0.75rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($tab === 'day'): ?>
        <div class="card overflow-hidden">
            <div class="grid-scroll-wrapper">
                <table class="table table-bordered text-center align-middle mb-0" style="font-size:.8rem;">
                    <thead class="grid-sticky-head">
                        <tr>
                            <th class="grid-sticky-col" style="min-width:90px;">JP</th>
                            <?php foreach ($classes as $c): ?>
                                <th><?= e($c['name']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($jp = 1; $jp <= 9; $jp++): ?>
                            <tr>
                                <td class="fw-bold text-success bg-light grid-sticky-col">JP <?= $jp ?></td>
                                <?php foreach ($classes as $c): ?>
                                    <td class="schedule-cell">
                                        <?php
                                        $cell_found = false;
                                        foreach ($rows as $r) {
                                            if ((int)$r['class_id'] === (int)$c['id'] && (int)$r['jp'] === $jp) {
                                                $cell_found = true;
                                                ?>
                                                <div class="cell-entry text-start">
                                                    <strong><?= e($r['subject_name']) ?></strong>
                                                    <small><?= e($r['teacher_name']) ?></small>
                                                </div>
                                                <?php
                                                break;
                                            }
                                        }
                                        if (!$cell_found): ?>
                                            <span class="text-muted" style="font-size: 0.75rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/templates/footer.php'; // Render HTML penutup ?>