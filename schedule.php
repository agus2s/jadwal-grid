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
    <div class="view-switcher d-flex gap-2 align-items-center">
        <!-- Kelas CSS 'active-view' ditambahkan ke tombol yang sesuai dengan $view aktif -->
        <a href="?view=list"
           class="btn btn-sm btn-outline-secondary <?= $view === 'list' ? 'active-view' : '' ?>">
            <i class="bi bi-list-ul me-1"></i>Daftar
        </a>
        <a href="?view=grid"
           class="btn btn-sm btn-outline-secondary <?= $view === 'grid' ? 'active-view' : '' ?>">
            <i class="bi bi-table me-1"></i>Grid
        </a>
        <a href="schedule_export.php" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export ODS
        </a>
    </div>
</div>

<!-- ─── Pesan error / sukses (toast kanan bawah) ─── -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <?php if ($err): ?>
        <div class="toast align-items-center text-white bg-danger border-0 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4000">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($err) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($ok): ?>
        <div class="toast align-items-center text-white bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4000">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle-fill me-2"></i><?= e($ok) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
</div>

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
                                            <!-- Sel berisi: nama mapel + guru, dengan tombol hapus yang muncul saat diklik -->
                                            <div class="cell-entry" data-schedule-id="<?= (int) $r['id'] ?>">
                                                <div class="d-flex align-items-center justify-content-between gap-2">
                                                    <div class="text-start">
                                                        <strong><?= e($r['subject_name']) ?></strong>
                                                        <small class="d-block"><?= e($r['teacher_name']) ?></small>
                                                    </div>
                                                    <a class="btn btn-link btn-sm text-danger p-0 delete-cell-btn"
                                                       href="?delete=<?= (int) $r['id'] ?>"
                                                       onclick="return confirm('Hapus jadwal ini?')"
                                                       style="display:none; font-size:.7rem; line-height:1;">
                                                        <i class="bi bi-trash3"></i>
                                                    </a>
                                                </div>
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
    <div class="p-3 border-bottom">
        <div class="row g-2 justify-content-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label mb-1" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.4px;color:#718096;">
                    Cari Jadwal
                </label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="scheduleSearch" class="form-control" placeholder="Cari hari, rombel, mapel, guru...">
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
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
                    <tr class="empty-rows-message">
                        <td colspan="7" class="text-center py-5" style="color:#a0aec0;">
                            <div style="font-size:2rem;margin-bottom:.5rem;">📋</div>
                            Belum ada jadwal. Tambahkan melalui form di atas.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $r):
                        // Ambil warna badge sesuai hari; fallback ke warna abu-abu
                        $dc = $dayColors[$r['day']] ?? ['badge' => '#718096'];
                        $searchText = strtolower($r['day'] . ' ' . $r['class_name'] . ' ' . $r['subject_name'] . ' ' . $r['teacher_name'] . ' jp ' . $r['jp']);
                    ?>
                        <tr class="schedule-row" data-search="<?= e($searchText) ?>">
                            <!-- Nomor urut (1-based, bukan ID database) -->
                            <td style="color:#a0aec0;font-size:.8rem;">
                                <?= $i + 1 ?>
                            </td>

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
                    <tr class="no-results-row d-none">
                        <td colspan="7" class="text-center py-5" style="color:#a0aec0;">
                            <div style="font-size:2rem;margin-bottom:.5rem;">🔎</div>
                            Tidak ada jadwal yang cocok dengan pencarian.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toastEls = document.querySelectorAll('.toast');
    toastEls.forEach(function(toastEl) {
        const toast = new bootstrap.Toast(toastEl, {
            delay: 4000,
            autohide: true
        });
        toast.show();
    });

    /**
     * Simpan posisi scroll grid sebelum submit form.
     * Saat halaman dimuat ulang setelah tambah jadwal, posisi ini dipulihkan.
     */
    const scrollStorageKey = 'jadwal-grid-scroll';
    const form = document.querySelector('form[method="post"]');
    const gridWrapper = document.querySelector('.grid-scroll-wrapper');
    const searchInput = document.getElementById('scheduleSearch');

    if (searchInput) {
        const rows = Array.from(document.querySelectorAll('.schedule-row'));
        const emptyRow = document.querySelector('.empty-rows-message');
        const noResultsRow = document.querySelector('.no-results-row');

        const applySearch = () => {
            const keyword = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;

            rows.forEach(row => {
                const haystack = (row.dataset.search || '').toLowerCase();
                const matched = !keyword || haystack.includes(keyword);
                row.classList.toggle('d-none', !matched);
                if (matched) visibleCount++;
            });

            if (emptyRow) emptyRow.classList.toggle('d-none', true);
            if (noResultsRow) noResultsRow.classList.toggle('d-none', visibleCount !== 0);
        };

        searchInput.addEventListener('input', applySearch);
        applySearch();
    }

    if (form) {
        form.addEventListener('submit', function() {
            const scrollState = {
                x: gridWrapper ? gridWrapper.scrollLeft : window.scrollX,
                y: gridWrapper ? gridWrapper.scrollTop : window.scrollY,
                view: window.location.search.includes('view=grid') ? 'grid' : 'list'
            };
            sessionStorage.setItem(scrollStorageKey, JSON.stringify(scrollState));
        });
    }

    const saved = sessionStorage.getItem(scrollStorageKey);
    if (saved) {
        try {
            const scrollState = JSON.parse(saved);
            if (scrollState.view === 'grid' && gridWrapper) {
                requestAnimationFrame(function() {
                    gridWrapper.scrollLeft = scrollState.x || 0;
                    gridWrapper.scrollTop = scrollState.y || 0;
                });
            } else {
                window.scrollTo(scrollState.x || 0, scrollState.y || 0);
            }
            sessionStorage.removeItem(scrollStorageKey);
        } catch (e) {
            sessionStorage.removeItem(scrollStorageKey);
        }
    }

    <?php if ($view === 'grid'): ?>

    document.querySelectorAll('.cell-entry').forEach(function(entry) {
        const deleteBtn = entry.querySelector('.delete-cell-btn');
        if (!deleteBtn) return;

        entry.addEventListener('click', function(event) {
            if (event.target.closest('a')) return;

            document.querySelectorAll('.cell-entry').forEach(function(otherEntry) {
                const otherBtn = otherEntry.querySelector('.delete-cell-btn');
                if (otherBtn) otherBtn.style.display = 'none';
                otherEntry.classList.remove('is-active');
            });

            this.classList.add('is-active');
            deleteBtn.style.display = 'inline-flex';
        });

        entry.addEventListener('mouseleave', function() {
            if (!this.classList.contains('is-active')) {
                deleteBtn.style.display = 'none';
            }
        });
    });

    // Ambil semua cells yang kosong (tidak memiliki child .cell-entry)
    document.querySelectorAll('.schedule-cell').forEach(cell => {
        // Hanya tambah click handler ke cell yang kosong
        if (!cell.querySelector('.cell-entry')) {
            cell.style.cursor = 'pointer';
            cell.style.transition = 'background 0.2s';
            
            cell.addEventListener('click', function() {
                // Ambil data dari row header (kolom pertama)
                const row = cell.closest('tr');
                const rowHeaderCell = row.querySelector('.grid-sticky-col');
                
                // Extract hari dan JP dari text rowHeader
                // Format: "Senin\nJP 1" (dengan newline)
                const headerText = rowHeaderCell.innerText;
                const lines = headerText.split('\n');
                const hari = lines[0].trim();  // "Senin"
                const jpMatch = lines[1].match(/JP (\d+)/);
                const jp = jpMatch ? jpMatch[1] : null;
                
                // Ambil nama rombel dari header kolom (thead)
                const colIndex = Array.from(row.querySelectorAll('td')).indexOf(cell);
                const thead = document.querySelector('.grid-sticky-head tr');
                const thElements = thead.querySelectorAll('th');
                // Header index = colIndex + 1 (karena ada sticky col di awal)
                const classNameHeader = thElements[colIndex + 1];
                const rombel = classNameHeader ? classNameHeader.innerText.trim() : null;
                
                if (hari && jp && rombel) {
                    // Set dropdown nilai
                    const daySelect = document.querySelector('select[name="day"]');
                    const jpSelect = document.querySelector('select[name="jp"]');
                    const classSelect = document.querySelector('select[name="class_id"]');
                    
                    // Set hari
                    daySelect.value = hari;
                    
                    // Set JP
                    jpSelect.value = jp;
                    
                    // Set rombel (cari option dengan text yang cocok)
                    for (let option of classSelect.options) {
                        if (option.text.trim() === rombel) {
                            classSelect.value = option.value;
                            break;
                        }
                    }
                    
                    // Scroll ke form
                    const form = document.querySelector('form');
                    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    
                    // Focus ke dropdown mata pelajaran
                    const subjectSelect = document.querySelector('select[name="subject_id"]');
                    setTimeout(() => {
                        subjectSelect.focus();
                    }, 300);
                }
            });
            
            // Visual feedback saat hover
            cell.addEventListener('mouseover', function() {
                if (!this.querySelector('.cell-entry')) {
                    this.style.background = 'rgba(0, 154, 68, 0.08)';
                }
            });
            
            cell.addEventListener('mouseout', function() {
                this.style.background = '';
            });
        }
    });
    
    <?php endif; ?>
});
</script>

<?php require __DIR__ . '/templates/footer.php'; ?>
