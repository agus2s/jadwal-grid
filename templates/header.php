<?php $title=$title??'Jadwal MA Syamsul Huda';?>
<!doctype html><html lang=id>
    <head>
        <meta charset=utf-8>
        <meta name=viewport content="width=device-width,initial-scale=1">
        <title><?=e($title)?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel=stylesheet>
        <!-- Bootstrap Icons CDN — digunakan di seluruh halaman aplikasi -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel=stylesheet>
        <link rel=stylesheet href="assets/css/app.css">
    </head>
    <body>
        <nav class="navbar navbar-dark bg-success fixed-top">
            <div class=container-fluid>
                <div class="d-flex align-items-center gap-2">
                    <a class="navbar-brand fw-bold" href=index.php>
                        <i class="bi bi-calendar-week me-2"></i>MA Syamsul Huda
                    </a>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <a class="nav-link text-white fw-semibold" href=index.php>
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard (Lihat Jadwal)
                    </a>
                    <a class="nav-link text-white fw-semibold" href="master.php?type=teachers">
                        <i class="bi bi-gear me-1"></i>Master
                    </a>
                    <a class="nav-link text-white fw-semibold" href=schedule.php>
                        <i class="bi bi-calendar-plus me-1"></i>Edit Jadwal
                    </a>
                </div>
            </div>
        </nav>
        <div class=container-fluid py-3>