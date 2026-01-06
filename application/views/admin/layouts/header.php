<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($page_title) ? $page_title . ' | ' : '' ?>Windeep Finance</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap/3.0.1/icheck-bootstrap.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqvmap/1.5.1/jqvmap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.13.1/css/OverlayScrollbars.min.css">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.css">
    <!-- Bootstrap Datepicker -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.8/sweetalert2.min.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>">
    
    <style>
        /* Layout safety fixes to align with AdminLTE behavior */
        /* Do NOT force the sidebar to be fixed here; AdminLTE handles positioning.
           Provide a safe content margin so the content doesn't get hidden under the sidebar. */
        .content-wrapper {
            min-height: calc(100vh - 57px);
            margin-left: 250px; /* fallback when sidebar is expanded */
            transition: margin-left .2s ease-in-out;
        }
        /* When sidebar is collapsed AdminLTE adds `sidebar-collapse` to body */
        body.sidebar-collapse .content-wrapper {
            margin-left: 80px;
        }
        /* Small screens should not offset content */
        @media (max-width: 767px) {
            .content-wrapper { margin-left: 0 !important; }
        }

        .nav-sidebar .nav-item > .nav-link {
            position: relative;
        }
        .nav-sidebar .nav-item > .nav-link .badge {
            position: absolute;
            right: 10px;
        }
    </style>
    
    <?php if (isset($extra_css)): ?>
        <?= $extra_css ?>
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">

    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center" style="display: none;">
        <img class="animation__shake" src="<?= base_url('assets/img/logo.png') ?>" alt="Windeep Finance" height="60" width="60" onerror="this.style.display='none'">
    </div>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" data-toggle="tooltip" title="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?= base_url('admin/dashboard') ?>" class="nav-link">Dashboard</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link text-muted">
                    <i class="far fa-calendar-alt mr-1"></i> 
                    FY: <?= isset($financial_year) ? $financial_year->year_code : date('Y') . '-' . (date('y') + 1) ?>
                </span>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Quick Actions -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" data-toggle="tooltip" title="Quick Actions">
                    <i class="fas fa-plus-circle"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header">Quick Actions</span>
                    <div class="dropdown-divider"></div>
                    <a href="<?= base_url('admin/members/create') ?>" class="dropdown-item">
                        <i class="fas fa-user-plus mr-2 text-primary"></i> New Member
                    </a>
                    <a href="<?= base_url('admin/loans/application') ?>" class="dropdown-item">
                        <i class="fas fa-file-invoice-dollar mr-2 text-success"></i> New Loan Application
                    </a>
                    <a href="<?= base_url('admin/savings/collection') ?>" class="dropdown-item">
                        <i class="fas fa-piggy-bank mr-2 text-info"></i> Savings Collection
                    </a>
                    <a href="<?= base_url('admin/payments/receive') ?>" class="dropdown-item">
                        <i class="fas fa-hand-holding-usd mr-2 text-warning"></i> Receive Payment
                    </a>
                </div>
            </li>

            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" data-toggle="tooltip" title="Notifications">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge notification-count">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header">Notifications</span>
                    <div class="dropdown-divider"></div>
                    <div class="notification-list">
                        <a href="#" class="dropdown-item text-center text-muted">
                            <small>No new notifications</small>
                        </a>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="<?= base_url('admin/notifications') ?>" class="dropdown-item dropdown-footer">See All Notifications</a>
                </div>
            </li>

            <!-- Fullscreen -->
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button" data-toggle="tooltip" title="Fullscreen">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>

            <!-- User Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user-circle mr-1"></i>
                    <span class="d-none d-md-inline"><?= isset($admin) ? $admin->full_name : 'Admin' ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="<?= base_url('admin/profile') ?>" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <a href="<?= base_url('admin/settings') ?>" class="dropdown-item">
                        <i class="fas fa-cog mr-2"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?= base_url('auth/logout') ?>" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->
