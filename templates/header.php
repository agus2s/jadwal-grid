<?php
$title = $title ?? 'Jadwal MA Syamsul Huda';
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$activeNav = '';

if ($currentPage === 'index.php') {
    $activeNav = 'dashboard';
} elseif ($currentPage === 'report.php') {
    $activeNav = 'report';
} elseif ($currentPage === 'master.php') {
    $activeNav = 'master';
} elseif ($currentPage === 'schedule.php') {
    $activeNav = 'schedule';
}
?>
<!doctype html><html lang=id>
    <head>
        <meta charset=utf-8>
        <meta name=viewport content="width=device-width,initial-scale=1">
        <title><?=e($title)?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel=stylesheet>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
        <!-- Bootstrap Icons CDN — digunakan di seluruh halaman aplikasi -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel=stylesheet>
        <link rel=stylesheet href="assets/css/app.css">
    </head>
    <body>
        <nav class="navbar navbar-dark bg-success">
            <div class=container-fluid>
                <div class="d-flex align-items-center gap-2">
                    <a class="navbar-brand fw-bold" href=index.php>
                        <i class="bi bi-calendar-week me-2"></i>MA Syamsul Huda
                    </a>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <a class="nav-link text-white fw-semibold <?= $activeNav === 'dashboard' ? 'active-nav' : '' ?>" href=index.php>
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                    <a class="nav-link text-white fw-semibold <?= $activeNav === 'report' ? 'active-nav' : '' ?>" href=report.php>
                        <i class="bi bi-clipboard-data me-1"></i>Rekap
                    </a>
                    <a class="nav-link text-white fw-semibold <?= $activeNav === 'master' ? 'active-nav' : '' ?>" href="master.php?type=teachers">
                        <i class="bi bi-gear me-1"></i>Master
                    </a>
                    <a class="nav-link text-white fw-semibold <?= $activeNav === 'schedule' ? 'active-nav' : '' ?>" href=schedule.php>
                        <i class="bi bi-calendar-plus me-1"></i>Penyusunan
                    </a>
                </div>
            </div>
        </nav>
        <div class=container-fluid py-2>