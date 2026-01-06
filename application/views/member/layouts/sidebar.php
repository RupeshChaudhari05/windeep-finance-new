    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="<?= site_url('member/dashboard') ?>" class="brand-link">
            <span class="brand-text font-weight-light">Windeep Member</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- User panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="<?= site_url('member/profile') ?>" class="d-block">
                        <?= htmlspecialchars($member->first_name . ' ' . $member->last_name) ?>
                        <br>
                        <small class="text-muted"><?= htmlspecialchars($member->member_code) ?></small>
                    </a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="<?= site_url('member/dashboard') ?>" class="nav-link <?= uri_string() == 'member/dashboard' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= site_url('member/profile') ?>" class="nav-link <?= strpos(uri_string(), 'member/profile') === 0 ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-user"></i>
                            <p>Profile</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= site_url('member/loans') ?>" class="nav-link <?= strpos(uri_string(), 'member/loans') === 0 ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>Loans</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= site_url('member/savings') ?>" class="nav-link <?= strpos(uri_string(), 'member/savings') === 0 ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-piggy-bank"></i>
                            <p>Savings</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= site_url('member/installments') ?>" class="nav-link <?= strpos(uri_string(), 'member/installments') === 0 ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>Installments</p>
                        </a>
                    </li>

                    <li class="nav-item mt-3">
                        <a href="<?= site_url('member/logout') ?>" class="nav-link text-danger">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Logout</p>
                        </a>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?= $page_title ?? 'Member Portal' ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= site_url('member/dashboard') ?>">Home</a></li>
                            <?php if (isset($breadcrumb)): ?>
                                <li class="breadcrumb-item active"><?= $breadcrumb ?></li>
                            <?php endif; ?>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">