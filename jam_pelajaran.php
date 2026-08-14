<?php
/**
 * jam_pelajaran.php — Kostumasi Jam Pelajaran
 * -------------------------------------------
 * Halaman untuk mengelola konfigurasi jam pelajaran (config/jam_pelajaran.json).
 */

require __DIR__ . '/config/database.php';
$pdo = db();
$title = 'Jam Pelajaran';

$jam_pelajaran = get_jam_pelajaran();

$err = null;
$editRow = null;
$editId = null;

// ─────────────────────────────────────────────────
// UPDATE SETTINGS
// ─────────────────────────────────────────────────
if (isset($_GET['update_settings'])) {
    $show_time = isset($_POST['show_time']) ? true : false;
    save_setting_show_time($show_time);
    header("Location: jam_pelajaran.php");
    exit;
}

// ─────────────────────────────────────────────────
// HAPUS DATA
// ─────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $deleteId = trim($_GET['delete']);
    if (isset($jam_pelajaran[$deleteId])) {
        unset($jam_pelajaran[$deleteId]);
        // Urutkan berdasarkan kunci numerik
        ksort($jam_pelajaran, SORT_NUMERIC);
        if (file_put_contents(__DIR__ . '/config/jam_pelajaran.json', json_encode($jam_pelajaran, JSON_PRETTY_PRINT)) !== false) {
            header("Location: jam_pelajaran.php");
            exit;
        } else {
            $err = "Gagal menyimpan perubahan ke jam_pelajaran.json";
        }
    }
}

// ─────────────────────────────────────────────────
// LOAD DATA UNTUK DIEDIT
// ─────────────────────────────────────────────────
if (isset($_GET['edit'])) {
    $editId = trim($_GET['edit']);
    if (isset($jam_pelajaran[$editId])) {
        $editRow = $jam_pelajaran[$editId];
    }
}

// ─────────────────────────────────────────────────
// SIMPAN DATA (INSERT atau UPDATE)
// ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $jp_number = trim($_POST['jp_number'] ?? '');
        $start = trim($_POST['start'] ?? '');
        $end = trim($_POST['end'] ?? '');

        if ($jp_number === '' || $start === '' || $end === '') {
            throw new Exception("Semua kolom harus diisi!");
        }

        if (!preg_match('/^\d+$/', $jp_number)) {
            throw new Exception("Nomor JP harus berupa angka!");
        }

        $edit_id = trim($_POST['edit_id'] ?? '');

        if ($edit_id !== '') {
            // Mode Edit: jika nomor JP diubah, hapus yang lama
            if ($edit_id !== $jp_number && isset($jam_pelajaran[$edit_id])) {
                unset($jam_pelajaran[$edit_id]);
            }
        }

        $jam_pelajaran[$jp_number] = [
            'start' => $start,
            'end' => $end
        ];

        // Urutkan berdasarkan nomor JP
        ksort($jam_pelajaran, SORT_NUMERIC);

        if (file_put_contents(__DIR__ . '/config/jam_pelajaran.json', json_encode($jam_pelajaran, JSON_PRETTY_PRINT)) !== false) {
            header("Location: jam_pelajaran.php");
            exit;
        } else {
            throw new Exception("Gagal menyimpan ke config/jam_pelajaran.json");
        }

    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

require __DIR__ . '/templates/header.php';
?>

<div class="container page-header">
    <div class="page-header-inner">
        <h3 class="page-heading mb-0"><i class="bi bi-speedometer2"></i> Statistik</h3>
    </div>
</div>

<div class="container mb-4">
    <div class="row">
        <?php
        $cards = [
            'teachers' => ['Guru',         'bi-person-badge', '#3182ce', '#ebf4ff'],
            'subjects' => ['Mapel',        'bi-book',         '#38a169', '#f0fff4'],
            'classes'  => ['Rombel',       'bi-building',     '#dd6b20', '#fffaf0'],
            'schedules'=> ['Jadwal',       'bi-calendar-week','#805ad5', '#faf5ff'],
        ];
        foreach ($cards as $t => [$n, $icon, $color, $bg]):
            $x = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        ?>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm" style="border-top: 3px solid <?= $color ?>;">
                <div class="card-body d-flex align-items-center gap-3">
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
</div>

<div class="container mt-4 pt-2 border-top">
    <div class="card shadow-sm mb-3">
        <div class="card-body p-2">
            <ul class="nav nav-pills nav-fill gap-2">
                <li class="nav-item">
                    <a class="nav-link py-2.5 text-secondary" href="master.php?type=teachers">
                        <i class="bi bi-person-badge me-2"></i>Guru
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2.5 text-secondary" href="master.php?type=subjects">
                        <i class="bi bi-book me-2"></i>Mata Pelajaran
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2.5 text-secondary" href="master.php?type=classes">
                        <i class="bi bi-building me-2"></i>Rombel / Ruang
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2.5 active bg-success text-white" href="jam_pelajaran.php">
                        <i class="bi bi-clock me-2"></i>Jam Pelajaran
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Card Setting Tampilkan Jam -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-eye text-success" style="font-size: 1.2rem;"></i>
                <label class="form-check-label fw-semibold mb-0" for="show_time" style="cursor: pointer;">
                    Tampilkan Jam Mulai &amp; Selesai pada Jadwal
                </label>
            </div>
            <form method="post" action="?update_settings=1" class="mb-0">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="show_time" name="show_time" value="1" 
                           style="width: 2.5em; height: 1.25em; cursor: pointer;"
                           <?= get_setting_show_time() ? 'checked' : '' ?> 
                           onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="page-heading mb-0">
            <i class="bi bi-clock"></i> Jam Pelajaran
        </h3>
        <a href="?new=1" class="btn btn-success">
            <i class="bi bi-plus-lg me-1"></i>Tambah
        </a>
    </div>

    <?php if ($err): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= e($err) ?>
        </div>
    <?php endif; ?>

    <div class="modal fade" id="jpModal" tabindex="-1" aria-labelledby="jpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="jpModalLabel">
                            <?= $editRow ? 'Edit Jam Pelajaran' : 'Tambah Jam Pelajaran' ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <?php if ($editRow): ?>
                            <input type="hidden" name="edit_id" value="<?= e($editId) ?>">
                        <?php endif; ?>

                        <div class="col-md-12">
                            <label class="form-label">Nomor JP</label>
                            <input type="number" class="form-control" name="jp_number" min="1" max="99" required
                                   value="<?= $editRow ? e($editId) : '' ?>">
                            <small class="text-muted">Masukkan angka urutan JP (misal: 1, 2, dst.)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" class="form-control" name="start" required
                                   value="<?= $editRow ? e($editRow['start']) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" class="form-control" name="end" required
                                   value="<?= $editRow ? e($editRow['end']) : '' ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Batal
                        </button>
                        <button class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i><?= $editRow ? 'Simpan' : 'Tambah' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabel daftar semua data -->
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 100px;">JP</th>
                        <th>Jam Mulai</th>
                        <th>Jam Selesai</th>
                        <th>Durasi</th>
                        <th style="width: 200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jam_pelajaran)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">Belum ada data jam pelajaran.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jam_pelajaran as $jp => $times): 
                            $start_time = strtotime($times['start']);
                            $end_time = strtotime($times['end']);
                            $duration = '';
                            if ($start_time && $end_time) {
                                $diff_mins = round(($end_time - $start_time) / 60);
                                $duration = $diff_mins . ' Menit';
                            }
                        ?>
                            <tr <?= ($editId === (string)$jp) ? 'class="table-warning"' : '' ?>>
                                <td class="fw-bold text-success">JP <?= e($jp) ?></td>
                                <td><?= e($times['start']) ?></td>
                                <td><?= e($times['end']) ?></td>
                                <td><span class="badge bg-secondary"><?= e($duration) ?></span></td>
                                <td class="d-flex gap-1 flex-wrap">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="?edit=<?= e($jp) ?>">
                                        <i class="bi bi-pencil-square me-1"></i>Edit
                                    </a>
                                    <a class="btn btn-sm btn-outline-danger"
                                       href="?delete=<?= e($jp) ?>"
                                       onclick="return confirm('Hapus JP <?= e($jp) ?>?')">
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
</div>

<?php if ($editRow || isset($_GET['new'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = new bootstrap.Modal(document.getElementById('jpModal'));
        modal.show();
    });
</script>
<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>
