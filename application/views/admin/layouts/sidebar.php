<?php
/**
 * Sidebar active-state.
 *
 * Worked out on the server from the actual controller/method, so exactly one
 * item lights up. This replaces the old client-side guess in custom.js, which
 * substring-matched the URL and therefore lit up every link whose href was a
 * prefix of the current page (and highlighted "Generate Passwords" under
 * Members whenever the Settings page was open).
 */
$nav_controller = strtolower((string) $this->uri->segment(2));
$nav_method     = strtolower((string) $this->uri->segment(3));
$nav_current    = trim($nav_controller . '/' . $nav_method, '/');

if (!function_exists('nav_is')) {
    /**
     * @param string|array $patterns  'loans' matches only the loans index,
     *                                'loans/overdue' matches that method,
     *                                'loans/*' matches anything in the controller.
     */
    function nav_is($patterns, $current, $controller) {
        foreach ((array) $patterns as $pattern) {
            $pattern = strtolower(trim($pattern));
            if ($pattern === '') { continue; }
            if (substr($pattern, -2) === '/*') {
                if ($controller === substr($pattern, 0, -2)) { return true; }
                continue;
            }
            if ($pattern === $current) { return true; }
        }
        return false;
    }
}
if (!function_exists('nav_active')) {
    function nav_active($patterns, $current, $controller) {
        return nav_is($patterns, $current, $controller) ? 'active' : '';
    }
}
?>
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
                        <a href="<?= base_url('admin/dashboard') ?>" class="nav-link <?= nav_active('dashboard/*', $nav_current, $nav_controller) ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <!-- Member Management -->
                    <?php $nav_members = nav_is(['members/*', 'settings/view_member_passwords'], $nav_current, $nav_controller); ?>
                    <li class="nav-item <?= $nav_members ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= $nav_members ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Members
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/members') ?>" class="nav-link <?= nav_active('members', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Members</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/members/create') ?>" class="nav-link <?= nav_active('members/create', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Add New Member</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/settings/view_member_passwords') ?>" class="nav-link <?= nav_active('settings/view_member_passwords', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><strong>Generate Passwords</strong></p>
                                </a>
                            </li>
                            <!-- <li class="nav-item">
                                <a href="<?= base_url('admin/members/kyc-pending') ?>" class="nav-link <?= nav_active('members/kyc-pending', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>KYC Pending</p>
                                </a>
                            </li> -->
                        </ul>
                    </li>

                    <!-- Non-Member Fund Providers -->
                    <li class="nav-item <?= nav_is(['non_members/*'], $nav_current, $nav_controller) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= nav_is(['non_members/*'], $nav_current, $nav_controller) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-user-tie"></i>
                            <p>
                                Fund Providers
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/non_members') ?>" class="nav-link <?= nav_active('non_members', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Providers</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/non_members/create') ?>" class="nav-link <?= nav_active('non_members/create', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Add Provider</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Security Deposit Management -->
                    <li class="nav-item <?= nav_is(['savings/*'], $nav_current, $nav_controller) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= nav_is(['savings/*'], $nav_current, $nav_controller) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-piggy-bank"></i>
                            <p>
                                Security Deposit
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/savings') ?>" class="nav-link <?= nav_active('savings', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Accounts</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/savings/collection') ?>" class="nav-link <?= nav_active('savings/collection', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Monthly Collection</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/savings/pending') ?>" class="nav-link <?= nav_active('savings/pending', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Pending Dues</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/savings/schemes') ?>" class="nav-link <?= nav_active('savings/schemes', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>SD Schemes</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/savings/bonus') ?>" class="nav-link <?= nav_active('savings/bonus', $nav_current, $nav_controller) ?>">
                                    <i class="fas fa-gift nav-icon text-success"></i>
                                    <p>Bonus</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Loan Management -->
                    <li class="nav-item <?= nav_is(['loans/*'], $nav_current, $nav_controller) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= nav_is(['loans/*'], $nav_current, $nav_controller) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-hand-holding-usd"></i>
                            <p>
                                Loans
                                <i class="fas fa-angle-left right"></i>
                                <span class="badge badge-warning right" id="pending-loans-badge"<?= ($pending_loan_count ?? 0) == 0 ? ' style="display:none"' : '' ?>><?= $pending_loan_count ?? 0 ?></span>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans') ?>" class="nav-link <?= nav_active('loans', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Loans</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/applications') ?>" class="nav-link <?= nav_active('loans/applications', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Applications</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/pending-approval') ?>" class="nav-link <?= nav_active('loans/pending-approval', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Pending Approval</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/disbursement') ?>" class="nav-link <?= nav_active('loans/disbursement', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Disbursement</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/overdue') ?>" class="nav-link <?= nav_active('loans/overdue', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Overdue Loans</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/repayment_history') ?>" class="nav-link <?= nav_active('loans/repayment_history', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Repayment History</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/products') ?>" class="nav-link <?= nav_active('loans/products', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Loan Products</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/loans/foreclosure_requests') ?>" class="nav-link <?= nav_active('loans/foreclosure_requests', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>
                                        Foreclosure Requests
                                        <?php
                                            $CI =& get_instance();
                                            $CI->load->model('Loan_model');
                                            $pending_foreclosure = count($CI->Loan_model->get_foreclosure_requests('pending'));
                                            if ($pending_foreclosure > 0):
                                        ?>
                                        <span class="badge badge-danger right"><?= $pending_foreclosure ?></span>
                                        <?php endif; ?>
                                    </p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- EMI / Installments -->
                    <li class="nav-item <?= nav_is(['installments/*', 'payments/*'], $nav_current, $nav_controller) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= nav_is(['installments/*', 'payments/*'], $nav_current, $nav_controller) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>
                                Installments
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <!-- <li class="nav-item">
                                <a href="<?= base_url('admin/installments') ?>" class="nav-link <?= nav_active('installments', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>EMI Schedule</p>
                                </a>
                            </li> -->
                            <!--
                            <li class="nav-item">
                                <a href="<?= base_url('admin/installments/due-today') ?>" class="nav-link <?= nav_active('installments/due-today', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Due Today</p>
                                </a>
                            </li>
                            -->
                            <li class="nav-item">
                                <a href="<?= base_url('admin/installments/upcoming') ?>" class="nav-link <?= nav_active('installments/upcoming', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Upcoming</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/installments/overdue') ?>" class="nav-link <?= nav_active('installments/overdue', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Overdue</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/payments/receive') ?>" class="nav-link <?= nav_active('payments/receive', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Receive Payment</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/payments/history') ?>" class="nav-link <?= nav_active('payments/history', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Payment History</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Fines & Penalties -->
                    <li class="nav-item <?= nav_is(['fines/*'], $nav_current, $nav_controller) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= nav_is(['fines/*'], $nav_current, $nav_controller) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-exclamation-triangle"></i>
                            <p>
                                Fines & Penalties
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/fines') ?>" class="nav-link <?= nav_active('fines', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Fines</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/fines/pending') ?>" class="nav-link <?= nav_active('fines/pending', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Pending Fines</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/fines/waiver-requests') ?>" class="nav-link <?= nav_active('fines/waiver-requests', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Waiver Requests</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/fines/rules') ?>" class="nav-link <?= nav_active('fines/rules', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Fine Rules</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Bank Statement Import -->
                    <li class="nav-item <?= nav_is(['bank/*'], $nav_current, $nav_controller) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= nav_is(['bank/*'], $nav_current, $nav_controller) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-university"></i>
                            <p>
                                Bank Import
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/bank/import') ?>" class="nav-link <?= nav_active('bank/import', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Import Statement</p>
                                </a>
                            </li>
                            <!-- <li class="nav-item">
                                <a href="<?= base_url('admin/bank/transactions') ?>" class="nav-link <?= nav_active('bank/transactions', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Transactions</p>
                                </a>
                            </li> -->
                            <!-- <li class="nav-item">
                                <a href="<?= base_url('admin/bank/mapping') ?>" class="nav-link <?= nav_active('bank/mapping', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Map Transactions</p>
                                </a>
                            </li> -->
                            <li class="nav-item">
                                <a href="<?= base_url('admin/bank/accounts') ?>" class="nav-link <?= nav_active('bank/accounts', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Bank Accounts</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/bank/ca_report') ?>" class="nav-link <?= ($this->uri->segment(3) == 'ca_report') ? 'active' : '' ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>CA Report</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Guarantors -->
                    <!-- <li class="nav-item">
                        <a href="<?= base_url('admin/reports/guarantor') ?>" class="nav-link <?= nav_active('reports/guarantor', $nav_current, $nav_controller) ?>">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>Guarantors</p>
                        </a>
                    </li> -->

                    <!-- Reports -->
                    <li class="nav-item <?= nav_is(['reports/*'], $nav_current, $nav_controller) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= nav_is(['reports/*'], $nav_current, $nav_controller) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>
                                Reports
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <!-- One-Click Exports -->
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/export_members') ?>" class="nav-link <?= nav_active('reports/export_members', $nav_current, $nav_controller) ?>">
                                     <i class="far fa-circle nav-icon"></i>
                                    <p>Export Members <i class="fas fa-download ml-1 text-warning"></i></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/export_loans_full') ?>" class="nav-link <?= nav_active('reports/export_loans_full', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Export Loans & Fines <i class="fas fa-download ml-1 text-warning"></i></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/export_savings_full') ?>" class="nav-link <?= nav_active('reports/export_savings_full', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Export Security Deposits <i class="fas fa-download ml-1 text-warning"></i></p>
                                </a>
                            </li>
                            <!-- Legacy Reports -->
                            <!-- <li class="nav-item">
                                <a href="<?= base_url('admin/reports/collection') ?>" class="nav-link <?= nav_active('reports/collection', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Collection Report</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/outstanding') ?>" class="nav-link <?= nav_active('reports/outstanding', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Outstanding Report</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/overdue') ?>" class="nav-link <?= nav_active('reports/overdue', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Overdue Report</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/member-statement') ?>" class="nav-link <?= nav_active('reports/member-statement', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Member Statement</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/reports/trial-balance') ?>" class="nav-link <?= nav_active('reports/trial-balance', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Trial Balance</p>
                                </a>
                            </li> -->
                        </ul>
                    </li>

                    <!-- Ledger & Accounting -->
                    <!-- <li class="nav-item <?= nav_is(['ledger/*', 'accounting/*'], $nav_current, $nav_controller) ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= nav_is(['ledger/*', 'accounting/*'], $nav_current, $nav_controller) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-book"></i>
                            <p>
                                Accounting
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/ledger/member') ?>" class="nav-link <?= nav_active('ledger/member', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Member Ledger</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/ledger/general') ?>" class="nav-link <?= nav_active('ledger/general', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>General Ledger</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/accounting/chart') ?>" class="nav-link <?= nav_active('accounting/chart', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Chart of Accounts</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/accounting/vouchers') ?>" class="nav-link <?= nav_active('accounting/vouchers', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Vouchers</p>
                                </a>
                            </li>
                        </ul>
                    </li> -->

                    <!-- Audit Logs -->
                    <!-- <li class="nav-item">
                        <a href="<?= base_url('admin/settings/audit_logs') ?>" class="nav-link <?= nav_active('settings/audit_logs', $nav_current, $nav_controller) ?>">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Audit Logs</p>
                        </a>
                    </li> -->

                    <!-- Bulk Data Import -->
                    <li class="nav-item">
                        <a href="<?= base_url('admin/import') ?>" class="nav-link <?= nav_active('import/*', $nav_current, $nav_controller) ?>">
                            <i class="nav-icon fas fa-file-import"></i>
                            <p>Bulk Import</p>
                        </a>
                    </li>

                    <li class="nav-header">ADMINISTRATION</li>

                    <!-- Admin Adjustments (Super Admin Only) -->
                    <?php if (isset($admin) && $admin->role === 'super_admin'): ?>
                    <li class="nav-item">
                        <a href="<?= base_url('admin/adjustments') ?>" class="nav-link <?= nav_active('adjustments/*', $nav_current, $nav_controller) ?>">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>Admin Adjustments</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Notifications -->
                    <li class="nav-item">
                        <a href="<?= base_url('admin/notifications') ?>" class="nav-link <?= nav_active('notifications/*', $nav_current, $nav_controller) ?>">
                            <i class="nav-icon fas fa-bell"></i>
                            <p>
                                Notifications
                                <?php
                                    $CI =& get_instance();
                                    $CI->load->model('Notification_model');
                                    $admin_id = $CI->session->userdata('admin_id');
                                    $unread_count = $CI->Notification_model->count_unread('admin', $admin_id);
                                    if ($unread_count > 0):
                                ?>
                                <span class="badge badge-danger right"><?= $unread_count ?></span>
                                <?php endif; ?>
                            </p>
                        </a>
                    </li>

                    <!-- Settings -->
                    <?php $nav_settings = nav_is(['settings/*', 'users/*'], $nav_current, $nav_controller)
                                          && !nav_is('settings/view_member_passwords', $nav_current, $nav_controller); ?>
                    <li class="nav-item <?= $nav_settings ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= $nav_settings ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>
                                Settings
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= base_url('admin/settings') ?>" class="nav-link <?= nav_active('settings', $nav_current, $nav_controller) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>General Settings</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= base_url('admin/settings/backup') ?>" class="nav-link <?= nav_active('settings/backup', $nav_current, $nav_controller) ?>">
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
                
                <!-- Flash messages are rendered once, in admin/layouts/footer.php.
                     Do not re-render them here or every toast appears twice. -->
