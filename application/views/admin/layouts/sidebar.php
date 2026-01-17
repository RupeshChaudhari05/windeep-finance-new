    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="<?= base_url('admin/dashboard') ?>" class="brand-link">
            <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Windeep Finance" class="brand-image img-circle elevation-3" style="opacity: .8" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIGZpbGw9IiMwMDdiZmYiIHJ4PSI4Ii8+CiAgPHRleHQgeD0iMjAiIHk9IjI1IiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTYiIGZvbnQtd2VpZ2h0PSJib2xkIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSJ3aGl0ZSI+V0Y8L3RleHQ+Cjwvc3ZnPg=='">
            <span class="brand-text font-weight-light"><strong>Windeep</strong> Finance</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="<?= isset($admin->photo) && $admin->photo ? base_url($admin->photo) : base_url('assets/img/avatar.svg') ?>" class="img-circle elevation-2" alt="Admin" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8Y2lyY2xlIGN4PSIyMCIgY3k9IjIwIiByPSIyMCIgZmlsbD0iIzZjNzU3ZCIvPgogIDxjaXJjbGUgY3g9IjIwIiBjeT0iMTUiIHI9IjciIGZpbGw9IiNmZmZmZmYiLz4KICA8cGF0aCBkPSJNOCAzNSBROCAyNSA4IDI1IEwzMiAyNSBMMzIgMzUgWiIgZmlsbD0iI2ZmZmZmYi8+Cjwvc3ZnPg=='">
                </div>
                <div class="info">
                    <a href="<?= base_url('admin/profile') ?>" class="d-block"><?= isset($admin) ? $admin->full_name : 'Administrator' ?></a>
                    <small class="text-muted"><?= isset($admin) ? ucfirst(str_replace('_', ' ', $admin->role)) : 'Admin' ?></small>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
                    
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="<?= base_url('admin/dashboard') ?>" class="nav-link <?= $this->uri->segment(2) == 'dashboard' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <!-- Member Management -->
                    <li class="nav-item <?= in_array($this->uri->segment(2), ['members']) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= in_array($this->uri->segment(2), ['members']) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Members
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/members') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Members</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/members/create') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Add New Member</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/members/kyc-pending') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>KYC Pending</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Savings Management -->
                    <li class="nav-item <?= in_array($this->uri->segment(2), ['savings']) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= in_array($this->uri->segment(2), ['savings']) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-piggy-bank"></i>
                            <p>
                                Savings
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/savings') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Accounts</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/savings/collection') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Monthly Collection</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/savings/pending') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Pending Dues</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/savings/schemes') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Savings Schemes</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Loan Management -->
                    <li class="nav-item <?= in_array($this->uri->segment(2), ['loans']) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= in_array($this->uri->segment(2), ['loans']) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-hand-holding-usd"></i>
                            <p>
                                Loans
                                <i class="fas fa-angle-left right"></i>
                                <span class="badge badge-warning right" id="pending-loans-badge">0</span>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Loans</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/applications') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Applications</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/pending-approval') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Pending Approval</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/disbursement') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Disbursement</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/overdue') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Overdue Loans</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/repayment_history') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Repayment History</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/products') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Loan Products</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- EMI / Installments -->
                    <li class="nav-item <?= in_array($this->uri->segment(2), ['installments', 'payments']) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= in_array($this->uri->segment(2), ['installments', 'payments']) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>
                                Installments
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/installments') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>EMI Schedule</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/installments/due-today') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Due Today</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/installments/upcoming') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Upcoming</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/installments/overdue') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Overdue</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/payments/receive') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Receive Payment</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/payments/history') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Payment History</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Fines & Penalties -->
                    <li class="nav-item <?= in_array($this->uri->segment(2), ['fines']) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= in_array($this->uri->segment(2), ['fines']) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-exclamation-triangle"></i>
                            <p>
                                Fines & Penalties
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/fines') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Fines</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/fines/pending') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Pending Fines</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/fines/waiver-requests') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Waiver Requests</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/fines/rules') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Fine Rules</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Bank Statement Import -->
                    <li class="nav-item <?= in_array($this->uri->segment(2), ['bank']) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= in_array($this->uri->segment(2), ['bank']) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-university"></i>
                            <p>
                                Bank Import
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/bank/import') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Import Statement</p>
                                </a>
                            </li>
                            <!-- <li class="nav-item">
                                <a href="<?= base_url('admin/bank/transactions') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Transactions</p>
                                </a>
                            </li> -->
                            <!-- <li class="nav-item">
                                <a href="<?= base_url('admin/bank/mapping') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Map Transactions</p>
                                </a>
                            </li> -->
                            <li class="nav-item">
                                <a href="<?= base_url('admin/bank/accounts') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Bank Accounts</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Guarantors -->
                    <li class="nav-item">
                        <a href="<?= base_url('admin/reports/guarantor') ?>" class="nav-link <?= ($this->uri->segment(2) == 'reports' && $this->uri->segment(3) == 'guarantor') ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>Guarantors</p>
                        </a>
                    </li>

                    <!-- Reports -->
                    <li class="nav-item <?= in_array($this->uri->segment(2), ['reports']) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= in_array($this->uri->segment(2), ['reports']) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>
                                Reports
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/collection') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Collection Report</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/outstanding') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Outstanding Report</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/overdue') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Overdue Report</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/member-statement') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Member Statement</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/guarantor-exposure') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Guarantor Exposure</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/trial-balance') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Trial Balance</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Ledger & Accounting -->
                    <li class="nav-item <?= in_array($this->uri->segment(2), ['ledger', 'accounting']) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= in_array($this->uri->segment(2), ['ledger', 'accounting']) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-book"></i>
                            <p>
                                Accounting
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/ledger/member') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Member Ledger</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/ledger/general') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>General Ledger</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/accounting/chart') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Chart of Accounts</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/accounting/vouchers') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Vouchers</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Audit Logs -->
                    <li class="nav-item">
                        <a href="<?= base_url('admin/settings/audit_logs') ?>" class="nav-link <?= ($this->uri->segment(2) == 'settings' && $this->uri->segment(3) == 'audit_logs') ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Audit Logs</p>
                        </a>
                    </li>

                    <li class="nav-header">ADMINISTRATION</li>

                    <!-- Settings -->
                    <li class="nav-item <?= in_array($this->uri->segment(2), ['settings', 'users']) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= in_array($this->uri->segment(2), ['settings', 'users']) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>
                                Settings
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/settings/general') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>General Settings</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/settings/backup') ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Backup & Restore</p>
                                </a>
                            </li>
                        </ul>
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
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?= isset($page_title) ? $page_title : 'Dashboard' ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
                            <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                                <?php foreach ($breadcrumbs as $crumb): ?>
                                    <?php if (isset($crumb['url'])): ?>
                                        <li class="breadcrumb-item"><a href="<?= $crumb['url'] ?>"><?= $crumb['title'] ?></a></li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item active"><?= $crumb['title'] ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                
                <!-- Flash Messages -->
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-check-circle mr-2"></i>
                        <?= $this->session->flashdata('success') ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?= $this->session->flashdata('error') ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($this->session->flashdata('warning')): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?= $this->session->flashdata('warning') ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($this->session->flashdata('info')): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-info-circle mr-2"></i>
                        <?= $this->session->flashdata('info') ?>
                    </div>
                <?php endif; ?>
