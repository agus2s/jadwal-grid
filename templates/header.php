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
                    <button class="navbar-toggler d-lg-none border-0 px-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <a class="navbar-brand fw-bold" href=index.php>
                        <i class="bi bi-calendar-week me-2"></i>MA Syamsul Huda
                    </a>
                </div>
                <span class="text-white d-none d-sm-inline">
                    <i class="bi bi-grid-3x3-gap me-1 opacity-75"></i>Manajemen Jadwal Pelajaran
                </span>
            </div>
        </nav>
        <div class=container-fluid>
            <div class="row g-3 py-3">
                <aside class="col-lg-2 offcanvas-lg offcanvas-start bg-light" id="sidebarMenu" tabindex="-1" aria-labelledby="sidebarMenuLabel">
                    <div class="offcanvas-header border-bottom d-lg-none">
                        <h5 class="offcanvas-title fw-bold text-success" id="sidebarMenuLabel">
                            <i class="bi bi-calendar-week me-2"></i>Menu Utama
                        </h5>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body p-0">
                        <div class="list-group w-100 border-0 rounded-0 rounded-lg-3">
                            <a class=list-group-item href=index.php>
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                            <a class=list-group-item href="master.php?type=teachers">
                                <i class="bi bi-person-badge me-2"></i>Guru
                            </a>
                            <a class=list-group-item href="master.php?type=subjects">
                                <i class="bi bi-book me-2"></i>Mata Pelajaran
                            </a>
                            <a class=list-group-item href="master.php?type=classes">
                                <i class="bi bi-building me-2"></i>Rombel / Ruang
                            </a>
                            <a class=list-group-item href=view_schedule.php>
                                <i class="bi bi-calendar3 me-2"></i>Lihat Jadwal
                            </a>
                            <a class=list-group-item href=schedule.php>
                                <i class="bi bi-calendar-plus me-2"></i>Penyusunan Jadwal
                            </a>
                            <a class=list-group-item href="schedule.php?view=grid">
                                <i class="bi bi-table me-2"></i>Jadwal Grid
                            </a>
                        </div>
                    </div>
                </aside>
                <main class=col-lg-10>