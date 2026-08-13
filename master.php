<?php
/**
 * master.php — Halaman Data Master (Guru / Mata Pelajaran / Rombel)
 * -----------------------------------------------------------------
 * Satu halaman ini menangani TIGA tabel master sekaligus,
 * ditentukan oleh query string ?type=teachers|subjects|classes
 *
 * Fitur:
 *   - Tambah data (INSERT)
 *   - Edit data (UPDATE)
 *   - Hapus data (DELETE)
 */

require __DIR__ . '/config/database.php'; // Koneksi database
$pdo  = db();

// Baca parameter ?type dari URL; default ke 'teachers' jika tidak ada
$type = $_GET['type'] ?? 'teachers';

/**
 * $m — Konfigurasi semua tabel master.
 *
 * Struktur: 'nama_tabel' => ['Judul Halaman', ['kolom_db' => 'Label Form'], 'icon-bi']
 *
 * Dengan satu array ini, halaman bisa menangani semua tabel
 * tanpa perlu menulis kode terpisah per tabel.
 */
$m = [
    'teachers' => ['Guru',            ['code' => 'Kode', 'name' => 'Nama Guru', 'nip' => 'NIP'], 'bi-person-badge'],
    'subjects' => ['Mata Pelajaran',  ['code' => 'Kode', 'name' => 'Nama Mapel'],                'bi-book'],
    'classes'  => ['Rombel / Ruang',  ['name' => 'Nama Rombel'],                                  'bi-building'],
];

// Jika $type tidak ada dalam daftar, hentikan program dengan pesan error
if (!isset($m[$type])) die('Master tidak ditemukan');

// Destructuring: ambil judul, daftar field, dan ikon dari konfigurasi
[$title, $fields, $icon] = $m[$type];

$err     = null; // Menampung pesan error dari try/catch
$editRow = null; // Menampung baris yang sedang diedit (null = mode tambah)

// ─────────────────────────────────────────────────
// HAPUS DATA
// Dipicu oleh link: ?type=teachers&delete=5
// ─────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    // Prepared statement: (int) mencegah SQL injection dengan memastikan ID berupa angka
    $pdo->prepare("DELETE FROM $type WHERE id=?")->execute([(int) $_GET['delete']]);
    // Redirect kembali ke halaman yang sama setelah hapus (PRG pattern)
    header("Location: master.php?type=$type");
    exit;
}

// ─────────────────────────────────────────────────
// LOAD DATA UNTUK DIEDIT
// Dipicu oleh link: ?type=teachers&edit=5
// ─────────────────────────────────────────────────
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM $type WHERE id=?");
    $stmt->execute([(int) $_GET['edit']]);
    $editRow = $stmt->fetch(); // Simpan baris ke $editRow; form akan diisi otomatis
}

// ─────────────────────────────────────────────────
// SIMPAN DATA (INSERT atau UPDATE)
// Dipicu saat form di-submit (method POST)
// ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        /**
         * Kumpulkan nilai dari form ke array $v.
         * Key = nama kolom DB, Value = input user (sudah di-trim).
         * Loop berdasarkan $fields agar hanya kolom yang relevan yang diproses.
         */
        $v = [];
        foreach ($fields as $f => $l) {
            $v[$f] = trim($_POST[$f] ?? '');
        }

        // Cek apakah ini mode EDIT (hidden field edit_id > 0) atau mode TAMBAH
        $editId = (int)($_POST['edit_id'] ?? 0);

        if ($editId > 0) {
            // ── UPDATE ──
            // Bangun "code=?, name=?, nip=?" secara dinamis dari array $v
            $setParts = implode(', ', array_map(fn($f) => "$f=?", array_keys($v)));
            $pdo->prepare("UPDATE $type SET $setParts WHERE id=?")
                // array_values($v) → nilai field, $editId → nilai WHERE id
                ->execute([...array_values($v), $editId]);
        } else {
            // ── INSERT ──
            $pdo->prepare(
                "INSERT INTO $type(" . implode(',', array_keys($v)) . ") VALUES(" .
                implode(',', array_fill(0, count($v), '?')) . ")"
            )->execute(array_values($v));
        }

        // Redirect setelah berhasil simpan (PRG pattern)
        header("Location: master.php?type=$type");
        exit;

    } catch (Throwable $e) {
        // Tangkap semua error (termasuk pelanggaran UNIQUE constraint dari DB)
        $err = $e->getMessage();
    }
}

// Ambil semua baris dari tabel yang aktif, diurutkan alfabetis
$rows = $pdo->query("SELECT * FROM $type ORDER BY name")->fetchAll();

require __DIR__ . '/templates/header.php'; // Render header HTML
?>

<div class="container page-header">
    <div class="page-header-inner">
        <h3 class="page-heading mb-0"><i class="bi bi-speedometer2"></i> Statistik</h3>
    </div>
</div>

<div class="container mb-4">
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
</div>

<div class="container mt-4 pt-2 border-top">
    <div class="card shadow-sm mb-3">
        <div class="card-body p-2">
            <ul class="nav nav-pills nav-fill gap-2">
                <?php foreach ($m as $key => $meta): ?>
                    <?php [$label, $fieldList, $menuIcon] = $meta; ?>
                    <li class="nav-item">
                        <a class="nav-link py-2.5 <?= $type === $key ? 'active bg-success text-white' : 'text-secondary' ?>"
                           href="?type=<?= $key ?>">
                            <i class="bi <?= $menuIcon ?> me-2"></i><?= $label ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="page-heading mb-0">
            <i class="bi <?= $icon ?>"></i> <?= $title ?>
        </h3>
        <a href="?type=<?= $type ?>&new=1" class="btn btn-success">
            <i class="bi bi-plus-lg me-1"></i>Tambah
        </a>
    </div>

    <?php if ($err): ?>
        <!-- Tampilkan pesan error jika ada (misal: kode sudah dipakai) -->
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= e($err) ?>
        </div>
    <?php endif; ?>

    <div class="modal fade" id="masterModal" tabindex="-1" aria-labelledby="masterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="masterModalLabel">
                            <?= $editRow ? 'Edit ' . $title : 'Tambah ' . $title ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <?php if ($editRow): ?>
                            <input type="hidden" name="edit_id" value="<?= $editRow['id'] ?>">
                        <?php endif; ?>

                        <?php foreach ($fields as $f => $l): ?>
                            <div class="col-md-<?= count($fields) > 1 ? 6 : 12 ?>">
                                <label class="form-label"><?= $l ?></label>
                                <input class="form-control" name="<?= $f ?>"
                                       value="<?= $editRow ? e($editRow[$f]) : '' ?>">
                            </div>
                        <?php endforeach; ?>
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
                <tr>
                    <th style="width: 70px;">No</th>
                    <?php foreach ($fields as $l): ?>
                        <th><?= $l ?></th>
                    <?php endforeach; ?>
                    <th style="width: 200px;">Aksi</th>
                </tr>

                <?php foreach ($rows as $i => $r): ?>
                    <!--
                        Highlight baris kuning jika ID-nya sama dengan yang sedang diedit,
                        sehingga user tahu baris mana yang sedang dimodifikasi.
                    -->
                    <tr <?= ($editRow && $editRow['id'] == $r['id']) ? 'class="table-warning"' : '' ?>>
                        <td style="color:#a0aec0;font-size:.8rem;"><?= $i + 1 ?></td>

                        <?php foreach ($fields as $f => $l): ?>
                            <td><?= e($r[$f]) ?></td>
                        <?php endforeach; ?>

                        <td class="d-flex gap-1 flex-wrap">
                            <!-- Tombol Edit: buka modal edit -->
                            <a class="btn btn-sm btn-outline-primary"
                               href="?type=<?= $type ?>&edit=<?= $r['id'] ?>">
                                <i class="bi bi-pencil-square me-1"></i>Edit
                            </a>
                            <!-- Tombol Hapus: konfirmasi dulu sebelum menghapus -->
                            <a class="btn btn-sm btn-outline-danger"
                               href="?type=<?= $type ?>&delete=<?= $r['id'] ?>"
                               onclick="return confirm('Hapus?')">
                                <i class="bi bi-trash3 me-1"></i>Hapus
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

<?php if ($editRow || isset($_GET['new'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = new bootstrap.Modal(document.getElementById('masterModal'));
        modal.show();
    });
</script>
<?php endif; ?>

<?php require __DIR__ . '/templates/footer.php'; ?>