<!-- Reports Dashboard -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Reports & Analytics</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Collection Reports -->
                    <div class="col-md-4 mb-4">
                        <div class="card bg-success text-white h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-hand-holding-usd mr-1"></i> Collection Reports</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/collection') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Daily Collection Report
                                        </a>
                                    </li>
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/collection?type=savings') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Savings Collection
                                        </a>
                                    </li>
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/collection?type=loan') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Loan EMI Collection
                                        </a>
                                    </li>
                                    <li class="py-2">
                                        <a href="<?= site_url('admin/reports/collection?type=fine') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Fine Collection
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loan Reports -->
                    <div class="col-md-4 mb-4">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-file-contract mr-1"></i> Loan Reports</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/disbursement') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Disbursement Report
                                        </a>
                                    </li>
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/outstanding') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Outstanding Report
                                        </a>
                                    </li>
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/overdue') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Overdue/Demand Report
                                        </a>
                                    </li>
                                    <li class="py-2">
                                        <a href="<?= site_url('admin/reports/npa') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> NPA Report
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Member Reports -->
                    <div class="col-md-4 mb-4">
                        <div class="card bg-info text-white h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-users mr-1"></i> Member Reports</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/member_statement') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Member Statement
                                        </a>
                                    </li>
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/member_summary') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Member Summary
                                        </a>
                                    </li>
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/guarantor') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Guarantor Exposure
                                        </a>
                                    </li>
                                    <li class="py-2">
                                        <a href="<?= site_url('admin/reports/kyc_pending') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> KYC Pending
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Financial Reports -->
                    <div class="col-md-4 mb-4">
                        <div class="card bg-warning text-dark h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-calculator mr-1"></i> Financial Reports</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="py-2 border-bottom">
                                        <a href="<?= site_url('admin/reports/trial_balance') ?>" class="text-dark">
                                            <i class="fas fa-arrow-right mr-2"></i> Trial Balance
                                        </a>
                                    </li>
                                    <li class="py-2 border-bottom">
                                        <a href="<?= site_url('admin/reports/profit_loss') ?>" class="text-dark">
                                            <i class="fas fa-arrow-right mr-2"></i> Profit & Loss
                                        </a>
                                    </li>
                                    <li class="py-2 border-bottom">
                                        <a href="<?= site_url('admin/reports/balance_sheet') ?>" class="text-dark">
                                            <i class="fas fa-arrow-right mr-2"></i> Balance Sheet
                                        </a>
                                    </li>
                                    <li class="py-2">
                                        <a href="<?= site_url('admin/reports/general_ledger') ?>" class="text-dark">
                                            <i class="fas fa-arrow-right mr-2"></i> General Ledger
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Demand Reports -->
                    <div class="col-md-4 mb-4">
                        <div class="card bg-danger text-white h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-bell mr-1"></i> Demand Reports</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/demand') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Today's Demand
                                        </a>
                                    </li>
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/demand?period=week') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Weekly Demand
                                        </a>
                                    </li>
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/demand?period=month') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Monthly Demand
                                        </a>
                                    </li>
                                    <li class="py-2">
                                        <a href="<?= site_url('admin/reports/ageing') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Ageing Analysis
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Other Reports -->
                    <div class="col-md-4 mb-4">
                        <div class="card bg-secondary text-white h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-file-alt mr-1"></i> Other Reports</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/audit_log') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Audit Trail
                                        </a>
                                    </li>
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/cash_book') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Cash Book
                                        </a>
                                    </li>
                                    <li class="py-2 border-bottom border-light">
                                        <a href="<?= site_url('admin/reports/bank_reconciliation') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Bank Reconciliation
                                        </a>
                                    </li>
                                    <li class="py-2">
                                        <a href="<?= site_url('admin/reports/custom') ?>" class="text-white">
                                            <i class="fas fa-arrow-right mr-2"></i> Custom Report Builder
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-day mr-1"></i> Today's Summary</h3>
                <span class="float-right text-muted"><?= date('d M Y') ?></span>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tr>
                        <td><i class="fas fa-sign-in-alt text-success mr-2"></i> Collections</td>
                        <td class="text-right font-weight-bold text-success">₹<?= number_format($today_summary['collections'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-sign-out-alt text-danger mr-2"></i> Disbursements</td>
                        <td class="text-right font-weight-bold text-danger">₹<?= number_format($today_summary['disbursements'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-user-plus text-info mr-2"></i> New Members</td>
                        <td class="text-right font-weight-bold"><?= $today_summary['new_members'] ?? 0 ?></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-file-alt text-warning mr-2"></i> Loan Applications</td>
                        <td class="text-right font-weight-bold"><?= $today_summary['loan_applications'] ?? 0 ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Month-to-Date</h3>
                <span class="float-right text-muted"><?= date('M Y') ?></span>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tr>
                        <td><i class="fas fa-piggy-bank text-success mr-2"></i> Savings Collection</td>
                        <td class="text-right font-weight-bold text-success">₹<?= number_format($mtd_summary['savings_collection'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-rupee-sign text-primary mr-2"></i> Loan Collection</td>
                        <td class="text-right font-weight-bold text-primary">₹<?= number_format($mtd_summary['loan_collection'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-money-bill text-danger mr-2"></i> Loan Disbursed</td>
                        <td class="text-right font-weight-bold text-danger">₹<?= number_format($mtd_summary['loan_disbursed'] ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-percentage text-warning mr-2"></i> Interest Earned</td>
                        <td class="text-right font-weight-bold text-warning">₹<?= number_format($mtd_summary['interest_earned'] ?? 0) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
