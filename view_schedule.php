<?php
/**
 * view_schedule.php — Tampilan Jadwal Pelajaran
 * -------------------------------------------
 * Halaman untuk melihat jadwal berdasarkan:
 *   - Rombel (Kelas)
 *   - Hari
 *   - Guru
 */

require __DIR__ . '/config/database.php';
$pdo = db();

$days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

$dayColors = [
    'Senin'  => ['bg' => '#ebf4ff', 'color' => '#2b6cb0', 'badge' => '#3182ce'],
    'Selasa' => ['bg' => '#faf5ff', 'color' => '#6b46c1', 'badge' => '#805ad5'],
    'Rabu'   => ['bg' => '#f0fff4', 'color' => '#276749', 'badge' => '#38a169'],
    'Kamis'  => ['bg' => '#fffaf0', 'color' => '#c05621', 'badge' => '#dd6b20'],
    'Jumat'  => ['bg' => '#fff5f5', 'color' => '#c53030', 'badge' => '#e53e3e'],
    'Sabtu'  => ['bg' => '#f7fafc', 'color' => '#4a5568', 'badge' => '#718096'],
];

// Data master
$classes  = $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
$teachers = $pdo->query('SELECT * FROM teachers ORDER BY name')->fetchAll();
$year     = $pdo->query('SELECT * FROM school_years WHERE active=1 LIMIT 1')->fetch();

if (!$year) {
    die('Tahun ajaran aktif belum ditentukan. Silakan aktifkan tahun ajaran di menu utama.');
}

// Ambil tab aktif (default: rombel)
$tab = $_GET['tab'] ?? 'rombel';

// Inisialisasi variabel filter
$selected_class_id   = isset($_GET['class_id']) ? (int)$_GET['class_id'] : ($classes[0]['id'] ?? 0);
$selected_teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : ($teachers[0]['id'] ?? 0);
$selected_day        = $_GET['day'] ?? 'Senin';

$rows = [];
if ($tab === 'rombel' && $selected_class_id) {
    $stmt = $pdo->prepare("
        SELECT s.*, t.name teacher_name, sub.name subject_name
        FROM schedules s
        JOIN teachers t   ON t.id   = s.teacher_id
        JOIN subjects sub ON sub.id = s.subject_id
        WHERE s.school_year_id = ? AND s.class_id = ?
    ");
    $stmt->execute([$year['id'], $selected_class_id]);
    $rows = $stmt->fetchAll();
} elseif ($tab === 'teacher' && $selected_teacher_id) {
    $stmt = $pdo->prepare("
        SELECT s.*, c.name class_name, sub.name subject_name
        FROM schedules s
        JOIN classes c    ON c.id   = s.class_id
        JOIN subjects sub ON sub.id = s.subject_id
        WHERE s.school_year_id = ? AND s.teacher_id = ?
    ");
    $stmt->execute([$year['id'], $selected_teacher_id]);
    $rows = $stmt->fetchAll();
} elseif ($tab === 'day') {
    $stmt = $pdo->prepare("
        SELECT s.*, c.name class_name, t.name teacher_name, sub.name subject_name
        FROM schedules s
        JOIN classes  c   ON c.id   = s.class_id
        JOIN teachers t   ON t.id   = s.teacher_id
        JOIN subjects sub ON sub.id = s.subject_id
        WHERE s.school_year_id = ? AND s.day = ?
    ");
    $stmt->execute([$year['id'], $selected_day]);
    $rows = $stmt->fetchAll();
}

$title = 'Lihat Jadwal';
require __DIR__ . '/templates/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h3 class="page-heading mb-0"><i class="bi bi-calendar3"></i> Lihat Jadwal</h3>
    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-3">
        Tahun Ajaran: <strong><?= e($year['name']) ?></strong>
    </span>
</div>

<!-- Tabs Switcher -->
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

<!-- Filter Form -->
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

<!-- Schedule Display -->
<?php if ($tab === 'rombel' || $tab === 'teacher'): ?>
    <!-- Grid view of Hari vs JP for one Rombel or one Teacher -->
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
    <!-- Grid view of Class vs JP for one Day -->
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

<?php require __DIR__ . '/templates/footer.php'; ?>
