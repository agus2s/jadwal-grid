<?php
require __DIR__ . '/config/database.php';
$pdo = db();
$title = 'Rekap Jam Pelajaran';
require __DIR__ . '/templates/header.php';

$year = $pdo->query('SELECT * FROM school_years WHERE active=1 LIMIT 1')->fetch();
$classes = $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
$classNames = array_column($classes, 'name');

if (!$year) {
    $year = ['name' => 'Belum ditentukan'];
}

$teacherReport = [];
$stmt = $pdo->prepare("SELECT s.teacher_id, t.name teacher_name, s.subject_id, sub.name subject_name, c.name class_name, COUNT(*) AS jp_count FROM schedules s JOIN teachers t ON t.id = s.teacher_id JOIN subjects sub ON sub.id = s.subject_id JOIN classes c ON c.id = s.class_id WHERE s.school_year_id = ? GROUP BY s.teacher_id, s.subject_id, c.id ORDER BY t.name, sub.name, c.name");
$stmt->execute([$year['id'] ?? 0]);
foreach ($stmt->fetchAll() as $row) {
    $key = $row['teacher_id'] . '|' . $row['subject_id'];
    if (!isset($teacherReport[$key])) {
        $teacherReport[$key] = [
            'teacher' => $row['teacher_name'],
            'subject' => $row['subject_name'],
            'per_class' => array_fill_keys($classNames, 0),
            'total' => 0,
        ];
    }

    $teacherReport[$key]['per_class'][$row['class_name']] = (int) $row['jp_count'];
    $teacherReport[$key]['total'] += (int) $row['jp_count'];
}

$subjectReport = [];
$stmt = $pdo->prepare("SELECT s.subject_id, sub.name subject_name, c.name class_name, COUNT(*) AS jp_count FROM schedules s JOIN subjects sub ON sub.id = s.subject_id JOIN classes c ON c.id = s.class_id WHERE s.school_year_id = ? GROUP BY s.subject_id, c.id ORDER BY sub.name, c.name");
$stmt->execute([$year['id'] ?? 0]);
foreach ($stmt->fetchAll() as $row) {
    $key = $row['subject_id'];
    if (!isset($subjectReport[$key])) {
        $subjectReport[$key] = [
            'subject' => $row['subject_name'],
            'per_class' => array_fill_keys($classNames, 0),
            'total' => 0,
        ];
    }

    $subjectReport[$key]['per_class'][$row['class_name']] = (int) $row['jp_count'];
    $subjectReport[$key]['total'] += (int) $row['jp_count'];
}
?>

<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="page-heading mb-0"><i class="bi bi-clipboard-data"></i> Rekap Jam Pelajaran</h3>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-3">
            Tahun Ajaran: <strong><?= e($year['name']) ?></strong>
        </span>
    </div>

    <?php $type = $_GET['type'] ?? 'teacher'; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <ul class="nav nav-pills nav-fill gap-2 mb-0 flex-grow-1">
                <li class="nav-item">
                    <a class="nav-link <?= $type === 'teacher' ? 'active bg-success text-white' : 'text-secondary' ?>" href="report.php?type=teacher">
                        <i class="bi bi-person-badge me-2"></i>Rekap Guru
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $type === 'subject' ? 'active bg-success text-white' : 'text-secondary' ?>" href="report.php?type=subject">
                        <i class="bi bi-book me-2"></i>Rekap Mapel
                    </a>
                </li>
            </ul>

            <a class="btn btn-outline-success btn-sm" href="report_export.php?type=<?= e($type) ?>">
                <i class="bi bi-download me-1"></i>Export ODS
            </a>
        </div>
    </div>

    <?php if ($type === 'subject'): ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 180px;">Mapel</th>
                                <?php foreach ($classNames as $className): ?>
                                    <th style="min-width: 60px;"><?= e($className) ?></th>
                                <?php endforeach; ?>
                                <th style="min-width: 100px;">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($subjectReport): ?>
                                <?php foreach ($subjectReport as $row): ?>
                                    <tr>
                                        <td class="fw-semibold text-start"><?= e($row['subject']) ?></td>
                                        <?php foreach ($classNames as $className): ?>
                                            <td><?= (int) ($row['per_class'][$className] ?? 0) ?></td>
                                        <?php endforeach; ?>
                                        <td class="fw-bold text-success"><?= (int) $row['total'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= count($classNames) + 2 ?>" class="text-center text-muted py-4">Belum ada data jadwal untuk direkap.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 180px;">Guru</th>
                                <th style="min-width: 180px;">Mapel</th>
                                <?php foreach ($classNames as $className): ?>
                                    <th style="min-width: 60px;"><?= e($className) ?></th>
                                <?php endforeach; ?>
                                <th style="min-width: 100px;">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($teacherReport): ?>
                                <?php foreach ($teacherReport as $row): ?>
                                    <tr>
                                        <td class="fw-semibold text-start"><?= e($row['teacher']) ?></td>
                                        <td class="text-start"><?= e($row['subject']) ?></td>
                                        <?php foreach ($classNames as $className): ?>
                                            <td><?= (int) ($row['per_class'][$className] ?? 0) ?></td>
                                        <?php endforeach; ?>
                                        <td class="fw-bold text-success"><?= (int) $row['total'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= count($classNames) + 3 ?>" class="text-center text-muted py-4">Belum ada data jadwal untuk direkap.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
