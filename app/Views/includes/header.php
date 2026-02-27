<!doctype html>
<html lang="en">
<!--begin::Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= $title ?? 'D.O.A.S Dashboard' ?></title>

    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <!--end::Accessibility Meta Tags-->
    <!--begin::Primary Meta Tags-->
    <meta name="title" content="D.O.A.S| Dashboard v2" />
    <meta name="author" content="ColorlibHQ" />
    <meta name="description" content="D.O.A.S" />
    <link rel="icon" href="<?= base_url('lukisan/logodit.webp') ?>" type="image/png">

    <meta name="keywords"
        content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard, accessible admin panel, WCAG compliant" />
    <!--end::Primary Meta Tags-->
    <!--begin::Accessibility Features-->
    <!-- Skip links will be dynamically added by accessibility.js -->
    <meta name="supported-color-schemes" content="light dark" />
    <link rel="preload" href="<?= base_url('template/AdminLTE4/dist/css/adminlte.css') ?>" as="style" />
    <!--end::Accessibility Features-->
    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
        integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" media="print"
        onload="this.media='all'" />
    <!--end::Fonts-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
        crossorigin="anonymous" />
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        crossorigin="anonymous" />
    <link rel="preload" as="image" href="<?= base_url('lukisan/logodit.webp') ?>">

    <!--end::Third Party Plugin(Bootstrap Icons)-->
    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="<?= base_url('template/AdminLTE4/dist/css/adminlte.css') ?>" />
    <!--end::Required Plugin(AdminLTE)-->
    <!-- apexcharts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
        integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0=" crossorigin="anonymous" />
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <script src="https://cdn.tiny.cloud/1/ewn91w0bk7pb1v5ft45ts1j9och2d0tiatvy4qujsp0i875z/tinymce/8/tinymce.min.js"
        referrerpolicy="origin" crossorigin="anonymous"></script>

</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">

        <!--begin::Header-->
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">

                <!-- LEFT NAV -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="<?= site_url('/') ?>" class="nav-link">Home</a>
                    </li>
                </ul>

                <!-- RIGHT NAV -->
                <ul class="navbar-nav ms-auto">

                    <!-- Fullscreen -->
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                            <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                            <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display:none"></i>
                        </a>
                    </li>

                    <!-- User Menu -->
                    <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <img src="<?= base_url('lukisan/logodit.webp') ?>" class="user-image rounded-circle"
                                width="32" height="32" loading="lazy" decoding="async" alt="User Image">

                            <span class="d-none d-md-inline"><?= $nama ?></span>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                            <li class="user-header text-bg-primary">
                                <img src="<?= base_url('lukisan/logodit.webp') ?>" class="rounded-circle"
                                    alt="User Image">
                                <p>
                                    <?= $nama ?>
                                    <small>Member since Nov. 2023</small>
                                </p>
                            </li>

                            <li class="user-body">
                                <?php
                                $roleLabels = [
                                    1 => 'Super Admin',
                                    2 => 'Admin Utama',
                                    3 => 'Admin User',
                                    4 => 'Admin Berita',
                                    5 => 'Admin Anggaran',
                                    6 => 'Admin kantor',
                                    7 => 'Admin laporan',
                                ];
                                ?>
                                <p class="text-center"><?= esc($roleLabels[$role] ?? 'Role tidak dikenal') ?></p>
                            </li>

                            <li class="user-footer">
                                <a href="<?= site_url('profile') ?>" class="btn btn-default btn-flat">Profile</a>
                                <a href="<?= site_url('logout') ?>" class="btn btn-default btn-flat float-end"
                                    onclick="return confirm('Apakah Anda yakin ingin sign out?')">
                                    Sign out
                                </a>

                            </li>
                        </ul>
                    </li>

                </ul>

            </div>
        </nav>
        <!--end::Header-->
        <!--begin::Sidebar-->
        <aside class="app-sidebar" data-bs-theme="dark">
            <!--begin::Sidebar Brand-->
            <div class="sidebar-brand">
                <!--begin::Brand Link-->
                <a href="<?= site_url('/') ?>" class="brand-link">
                    <!--begin::Brand Image-->
                    <img src="<?= base_url('lukisan/logodit.webp') ?>" alt="DOAS Logo"
                        class="brand-image opacity-75 " />
                    <!--end::Brand Image-->
                    <!--begin::Brand Text-->
                    <span class="brand-text fw-light">D.O.A.S</span>
                    <!--end::Brand Text-->
                </a>
                <!--end::Brand Link-->
            </div>
            <!--end::Sidebar Brand-->
            <!--begin::Sidebar Wrapper-->
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <!--begin::Sidebar Menu-->
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation"
                        aria-label="Main navigation" data-accordion="false" id="navigation">
                        <?php $menuRole = (int) ($role ?? 0); ?>

                        <?php if (in_array($menuRole, [1, 2], true)): ?>
                        <li class="nav-item menu-open">
                            <a href="<?= site_url('/') ?>" class="nav-link active">
                                <i class="nav-icon bi bi-speedometer"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= site_url('absensi/laporan') ?>" class="nav-link">
                                <i class="nav-icon bi bi-box-seam-fill"></i>
                                <p>Laporan</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= site_url('usnmanajemen') ?>" class="nav-link">
                                <i class="bi bi-file-medical-fill"></i>
                                <p>User Manajemen</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= site_url('apadoas') ?>" class="nav-link">
                                <i class="bi bi-file-pdf"></i>
                                <p>Apa itu DOAS</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= site_url('kantor') ?>" class="nav-link">
                                <i class="bi bi-geo-alt"></i>
                                <p>Set Data Kantor</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= site_url('berita') ?>" class="nav-link">
                                <i class="nav-icon bi bi-patch-check-fill"></i>
                                <p>Berita</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= site_url('anggaran') ?>" class="nav-link">
                                <i class="bi bi-file-earmark-check"></i>
                                <p>Anggaran</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-journal-text"></i>
                                <p>
                                    Log
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="<?= site_url('login_log') ?>" class="nav-link">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Log Login</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= site_url('logout_log') ?>" class="nav-link">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Log Logout</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= site_url('user_management_log') ?>" class="nav-link">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Log user management</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= site_url('log_kantor') ?>" class="nav-link">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Log Perubahan Kantor</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= site_url('log_berita') ?>" class="nav-link">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Log Berita</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= site_url('log_anggaran') ?>" class="nav-link">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Log Anggaran</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= site_url('log_about') ?>" class="nav-link">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Log About</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="./docs/license.html" class="nav-link">
                                <i class="bi bi-hand-index-thumb"></i>
                                <p>Reset TK</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="./docs/license.html" class="nav-link">
                                <i class="bi bi-pc-display"></i>
                                <p>Proses TK</p>
                            </a>
                        </li>
                        <?php elseif ($menuRole === 3): ?>
                        <li class="nav-item">
                            <a href="<?= site_url('usnmanajemen') ?>" class="nav-link">
                                <i class="bi bi-file-medical-fill"></i>
                                <p>User Manajemen</p>
                            </a>
                        </li>
                        <?php elseif ($menuRole === 4): ?>
                        <li class="nav-item">
                            <a href="<?= site_url('berita') ?>" class="nav-link">
                                <i class="nav-icon bi bi-patch-check-fill"></i>
                                <p>Berita</p>
                            </a>
                        </li>
                        <?php elseif ($menuRole === 5): ?>
                        <li class="nav-item">
                            <a href="<?= site_url('anggaran') ?>" class="nav-link">
                                <i class="bi bi-file-earmark-check"></i>
                                <p>Anggaran</p>
                            </a>
                        </li>
                        <?php elseif ($menuRole === 6): ?>
                        <li class="nav-item">
                            <a href="<?= site_url('kantor') ?>" class="nav-link">
                                <i class="bi bi-geo-alt"></i>
                                <p>Set Data Kantor</p>
                            </a>
                        </li>
                        <?php elseif ($menuRole === 7): ?>
                        <li class="nav-item">
                            <a href="<?= site_url('absensi/laporan') ?>" class="nav-link">
                                <i class="nav-icon bi bi-box-seam-fill"></i>
                                <p>Laporan</p>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <!--end::Sidebar Menu-->
                </nav>
            </div>
            <!--end::Sidebar Wrapper-->
        </aside>

        <style>
        /* background sidebar */
        .app-sidebar .sidebar-wrapper {
            background-color: #0b2545;
            /* biru gelap */
        }

        /* text sidebar */
        .app-sidebar .nav-link,
        .app-sidebar .nav-header,
        .app-sidebar .brand-text {
            color: #ffffff;
        }

        /* active menu */
        .app-sidebar .nav-link.active {
            background-color: #133e7c;
            color: #ffffff;
        }
        </style>
        <style>
        /* background kotak logo (yang kamu lingkari) */
        .app-sidebar .sidebar-brand {
            background-color: #3b0202;
            /* merah gelap */
        }

        /* teks brand */
        .app-sidebar .brand-text {
            color: #ffffff;
            font-weight: 600;
        }

        /* logo biar kontras */
        .app-sidebar .brand-image {
            filter: brightness(1.1);
        }

        /* garis aksen header */
        .app-header {
            border-bottom: 2px solid #0b2545;
            background-color: #f8f9fa;
        }

        /* ikon */
        .app-header .nav-link i {
            font-size: 1.1rem;
            opacity: .85;
        }

        .app-header .nav-link:hover i {
            opacity: 1;
        }

        /* nama user */
        .app-header .user-menu .nav-link span {
            font-weight: 600;
        }
        </style>
        <style>
        /* Header polish */
        .app-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #0b2545;
        }

        /* Icon header */
        .app-header .nav-link i {
            font-size: 1.1rem;
            opacity: .85;
        }

        .app-header .nav-link:hover i {
            opacity: 1;
        }

        /* Username */
        .app-header .user-menu .nav-link span {
            font-weight: 600;
            letter-spacing: .3px;
        }

        /* FIX INP header avatar */
        .app-header .user-image {
            box-shadow: none !important;
            will-change: transform;
        }
        </style>
