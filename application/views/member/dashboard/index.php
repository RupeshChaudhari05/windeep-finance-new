<!-- Notification Summary Bar -->
<?php if (!empty($unread_notifications_count) && $unread_notifications_count > 0): ?>
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info alert-dismissible fade show mb-0" role="alert">
            <i class="fas fa-bell mr-2"></i>
            You have <strong><?= $unread_notifications_count ?></strong> unread notification<?= $unread_notifications_count > 1 ? 's' : '' ?>.
            <a href="<?= site_url('member/notifications') ?>" class="alert-link ml-2">View All</a>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
                <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tachometer-alt mr-1"></i> Overview</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="small-box bg-primary" data-toggle="tooltip" data-placement="top" title="Total number of your currently active (disbursed) loans">
                            <div class="inner">
                                <h3><?= number_format($loans_summary->total_loans ?? 0) ?></h3>
                                <p>Active Loans</p>
                            </div>
                            <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                            <a href="<?= site_url('member/loans') ?>" class="small-box-footer">View Loans <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small-box bg-success" data-toggle="tooltip" data-placement="top" title="Total remaining principal + interest amount across all your active loans">
                            <div class="inner">
                                <h3>₹<?= number_format($loans_summary->total_outstanding ?? 0, 2) ?></h3>
                                <p>Outstanding</p>
                            </div>
                            <div class="icon"><i class="fas fa-wallet"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small-box bg-warning" data-toggle="tooltip" data-placement="top" title="Total unpaid fines/penalties. Pay these to avoid further charges.">
                            <div class="inner">
                                <h3>₹<?= number_format($pending_fines->total_fines ?? 0, 2) ?></h3>
                                <p>Pending Fines</p>
                            </div>
                            <div class="icon"><i class="fas fa-gavel"></i></div>
                        </div>
                    </div>
                </div>

                <h5 class="mt-3">Upcoming Installments</h5>
                <?php if (empty($pending_installments)): ?>
                    <p class="text-muted">No upcoming installments.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th data-toggle="tooltip" title="The date by which this installment should be paid">Due Date</th>
                                    <th data-toggle="tooltip" title="Your loan reference number - click to view details">Loan</th>
                                    <th class="text-right" data-toggle="tooltip" title="Equated Monthly Installment - the fixed amount due each month">EMI</th>
                                    <th data-toggle="tooltip" title="Pending = not yet paid, Partial = partially paid, Paid = fully paid">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_installments as $inst): ?>
                                <tr>
                                    <td><?= format_date($inst->due_date, 'd M Y') ?></td>
                                    <td><a href="<?= site_url('member/loans/view/' . $inst->loan_id) ?>"><?= $inst->loan_number ?></a></td>
                                    <td class="text-right">₹<?= number_format($inst->emi_amount, 2) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $inst->status == 'pending' ? 'warning' : ($inst->status == 'partial' ? 'info' : 'secondary') ?>">
                                            <?= ucfirst($inst->status) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-1"></i> Recent Transactions</h3>
            </div>
            <div class="card-body">
                <?php if (empty($recent_transactions)): ?>
                    <p class="text-muted">No recent transactions.</p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Loan</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $t): ?>
                            <tr>
                                <td><?= format_date($t->payment_date, 'd M Y') ?></td>
                                <td><?= $t->loan_number ?? '-' ?></td>
                                <td class="text-right">₹<?= number_format($t->total_amount ?? $t->amount ?? 0, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h3 class="card-title">Savings Accounts</h3>
            </div>
            <div class="card-body">
                <?php if (empty($savings_accounts)): ?>
                    <p class="text-muted">No savings accounts.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($savings_accounts as $sa): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($sa->account_number) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($sa->scheme_name ?? '') ?></small>
                            </div>
                            <span class="badge badge-success">₹<?= number_format($sa->current_balance ?? 0, 2) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Quick Actions</h3></div>
            <div class="card-body">
                <a href="<?= site_url('member/loans/apply') ?>" class="btn btn-primary btn-block mb-2" data-toggle="tooltip" title="Submit a new loan application. You can track its status here.">
                    <i class="fas fa-plus-circle mr-1"></i> Apply for Loan
                </a>
                <a href="<?= site_url('member/savings') ?>" class="btn btn-success btn-block mb-2" data-toggle="tooltip" title="View your savings accounts, balances, and transaction history">
                    <i class="fas fa-piggy-bank mr-1"></i> View Savings
                </a>
                <a href="<?= site_url('member/installments') ?>" class="btn btn-warning btn-block" data-toggle="tooltip" title="View all your upcoming and past installment payments">
                    <i class="fas fa-calendar-check mr-1"></i> View Installments
                </a>
            </div>
        </div>
    </div>
</div>