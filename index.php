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
$jam_pelajaran = get_jam_pelajaran();
require __DIR__ . '/templates/header.php'; // Render HTML pembuka (navbar, sidebar, dsb.)

$days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$dayColors = [
    'Senin'  => ['bg' => '#ebf4ff', 'color' => '#2b6cb0', 'badge' => '#3182ce'],
    'Selasa' => ['bg' => '#faf5ff', 'color' => '#6b46c1', 'badge' => '#805ad5'],
    'Rabu'   => ['bg' => '#f0fff4', 'color' => '#276749', 'badge' => '#38a169'],
    'Kamis'  => ['bg' => '#fffaf0', 'color' => '#c05621', 'badge' => '#dd6b20'],
    'Jumat'  => ['bg' => '#fff5f5', 'color' => '#c53030', 'badge' => '#e53e3e'],
    'Sabtu'  => ['bg' => '#f7fafc', 'color' => '#4a5568', 'badge' => '#718096'],
];

$year = $pdo->query('SELECT * FROM school_years WHERE active=1 LIMIT 1')->fetch();
$classes = $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
$teachers = $pdo->query('SELECT * FROM teachers ORDER BY name')->fetchAll();

if (!$year) {
    $year = ['name' => 'Belum ditentukan'];
}

// Ambil hari ini dalam format Indonesia (0=Minggu, 1=Senin, dst)
$todayNumber = (int)date('w');
$todayDayMap = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
$todayDay = $todayDayMap[$todayNumber];
// Jika hari ini Minggu, gunakan Senin sebagai default (karena Minggu tidak ada di $days)
$defaultDay = ($todayNumber === 0) ? 'Senin' : $todayDay;

$tab = $_GET['tab'] ?? 'day';
$selected_class_id   = isset($_GET['class_id']) ? (int)$_GET['class_id'] : ($classes[0]['id'] ?? 0);
$selected_teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : ($teachers[0]['id'] ?? 0);
$selected_day        = $_GET['day'] ?? $defaultDay;

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

<div class="page-header">
    <div class="page-header-inner">
        <h3 class="page-heading mb-0"><i class="bi bi-calendar3"></i> Lihat Jadwal</h3>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-3">
            Tahun Ajaran: <strong><?= e($year['name']) ?></strong>
        </span>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body p-2">
            <ul class="nav nav-pills nav-fill gap-2">
                <li class="nav-item">
                    <a class="nav-link py-2.5 <?= $tab === 'day' ? 'active bg-success text-white' : 'text-secondary' ?>" href="?tab=day&day=<?= urlencode($selected_day) ?>">
                        <i class="bi bi-calendar-day me-2"></i>Berdasarkan Hari
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2.5 <?= $tab === 'rombel' ? 'active bg-success text-white' : 'text-secondary' ?>" href="?tab=rombel&class_id=<?= $selected_class_id ?>">
                        <i class="bi bi-building me-2"></i>Berdasarkan Rombel
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

                <?php if ($tab === 'day'): ?>
                    <div class="col-md-8">
                        <label class="form-label">Pilih Hari</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($days as $d): ?>
                                <button type="submit" name="day" value="<?= e($d) ?>"
                                        class="btn <?= $selected_day === $d ? 'btn-success' : 'btn-outline-secondary' ?> py-2">
                                    <?= e($d) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ($tab === 'rombel'): ?>
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

                <?php if ($tab !== 'day'): ?>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-secondary py-2">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($tab === 'day'): ?>
        <div class="card overflow-hidden">
            <div class="grid-scroll-wrapper">
                <table class="table table-sm table-bordered text-center align-middle mb-0" style="font-size:.8rem;">
                    <thead class="grid-sticky-head">
                        <tr>
                            <th class="grid-sticky-col" style="min-width:100px;">JP</th>
                            <?php foreach ($classes as $c): ?>
                                <th><?= e($c['name']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($jp = 1; $jp <= 9; $jp++): ?>
                            <tr>
                                <td class="fw-bold text-success bg-light grid-sticky-col">
                                    JP <?= $jp ?>
                                    <?php if (get_setting_show_time() && isset($jam_pelajaran[$jp])): ?>
                                        <br><span class="text-muted" style="font-size: 0.7rem; font-weight: normal;"><?= e($jam_pelajaran[$jp]['start']) ?> - <?= e($jam_pelajaran[$jp]['end']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <?php foreach ($classes as $c): ?>
                                    <td class="schedule-cell">
                                        <?php
                                        $cell_found = false;
                                        foreach ($rows as $r) {
                                            if ((int)$r['class_id'] === (int)$c['id'] && (int)$r['jp'] === $jp) {
                                                $cell_found = true;
                                                $entryColors = $dayColors[$selected_day] ?? ['bg' => '#f7fafc', 'color' => '#4a5568', 'badge' => '#718096'];
                                                ?>
                                                <div class="cell-entry text-start"
                                                     style="background: linear-gradient(135deg, <?= $entryColors['bg'] ?>, #ffffff); border-left-color: <?= $entryColors['badge'] ?>;">
                                                    <strong style="color: #000;"><?= e($r['subject_name']) ?></strong>
                                                        <small style="color: #000; opacity: .8;"><?= e($r['teacher_name']) ?></small>
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
    <?php elseif ($tab === 'rombel' || $tab === 'teacher'): ?>
        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-sm table-bordered text-center align-middle mb-0" style="min-width: 750px;">
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
                                <td class="fw-bold text-success bg-light">
                                    JP <?= $jp ?>
                                    <?php if (get_setting_show_time() && isset($jam_pelajaran[$jp])): ?>
                                        <br><span class="text-muted" style="font-size: 0.7rem; font-weight: normal;"><?= e($jam_pelajaran[$jp]['start']) ?> - <?= e($jam_pelajaran[$jp]['end']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <?php foreach ($days as $d): ?>
                                    <td class="dashboard-cell p-2">
                                        <?php
                                        $cell_found = false;
                                        foreach ($rows as $r) {
                                            if ($r['day'] === $d && (int)$r['jp'] === $jp) {
                                                $cell_found = true;
                                                if ($tab === 'rombel') {
                                                    $entryColors = $dayColors[$d] ?? ['bg' => '#f7fafc', 'color' => '#4a5568', 'badge' => '#718096'];
                                                    ?>
                                                    <div class="cell-entry text-start"
                                                         style="background: linear-gradient(135deg, <?= $entryColors['bg'] ?>, #ffffff); border-left-color: <?= $entryColors['badge'] ?>;">
                                                        <strong class="d-block" style="color: #000;">
                                                            <?= e($r['subject_name']) ?>
                                                        </strong>
                                                        <small style="color: #000; opacity: .8;"><i class="bi bi-person me-1"></i><?= e($r['teacher_name']) ?></small>
                                                    </div>
                                                    <?php
                                                } else {
                                                    $entryColors = $dayColors[$d] ?? ['bg' => '#f7fafc', 'color' => '#4a5568', 'badge' => '#718096'];
                                                    ?>
                                                    <div class="cell-entry text-start"
                                                         style="background: linear-gradient(135deg, <?= $entryColors['bg'] ?>, #ffffff); border-left-color: <?= $entryColors['badge'] ?>;">
                                                        <strong class="d-block" style="color: #000;">
                                                            <?= e($r['class_name']) ?>
                                                        </strong>
                                                        <small style="color: #000; opacity: .8;"><i class="bi bi-book me-1"></i><?= e($r['subject_name']) ?></small>
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
    <?php endif; ?>
</div>

<?php require __DIR__ . '/templates/footer.php'; // Render HTML penutup ?>