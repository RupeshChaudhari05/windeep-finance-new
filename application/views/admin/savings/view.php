<div class="row">
    <!-- Account Info Card -->
    <div class="col-md-4">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-piggy-bank mr-1"></i> Savings Account Details</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="mb-0"><?= $account->account_number ?></h4>
                    <span class="badge badge-<?= $account->status == 'active' ? 'success' : 'secondary' ?> badge-lg">
                        <?= ucfirst($account->status) ?>
                    </span>
                </div>
                
                <table class="table table-sm table-borderless">
                    <tr>
                        <th>Member:</th>
                        <td>
                            <a href="<?= site_url('admin/members/view/' . $account->member_id) ?>">
                                <?= $member->first_name ?> <?= $member->last_name ?>
                            </a>
                            <br><small class="text-muted"><?= $member->member_code ?></small>
                        </td>
                    </tr>
                    <tr>
                        <th>Scheme:</th>
                        <td><span class="badge badge-info"><?= $scheme->scheme_name ?></span></td>
                    </tr>
                    <tr>
                        <th>Monthly Amt:</th>
                        <td class="font-weight-bold">₹<?= number_format($account->monthly_amount) ?></td>
                    </tr>
                    <tr>
                        <th>Interest Rate:</th>
                        <td><?= $scheme->interest_rate ?>% p.a.</td>
                    </tr>
                    <tr>
                        <th>Due Date:</th>
                        <td><?= $account->due_date ?>th of every month</td>
                    </tr>
                    <tr>
                        <th>Opened On:</th>
                        <td><?= date('d M Y', strtotime($account->created_at)) ?></td>
                    </tr>
                    <tr>
                        <th>Maturity Date:</th>
                        <td><?= $account->maturity_date ? date('d M Y', strtotime($account->maturity_date)) : '-' ?></td>
                    </tr>
                </table>
                
                <hr>
                
                <!-- Balance Summary -->
                <div class="info-box bg-success mb-2">
                    <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Current Balance</span>
                        <span class="info-box-number">₹<?= number_format($account->current_balance, 2) ?></span>
                    </div>
                </div>
                
                <div class="info-box bg-info mb-2">
                    <span class="info-box-icon"><i class="fas fa-coins"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Deposited</span>
                        <span class="info-box-number">₹<?= number_format($account->total_deposited, 2) ?></span>
                    </div>
                </div>
                
                <div class="info-box bg-primary mb-2">
                    <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Interest Earned</span>
                        <span class="info-box-number">₹<?= number_format($account->interest_earned, 2) ?></span>
                    </div>
                </div>
                
                <?php if ($pending_dues > 0): ?>
                <div class="info-box bg-danger mb-2">
                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pending Dues</span>
                        <span class="info-box-number"><?= $pending_dues ?> installment(s)</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($account->status == 'active'): ?>
                <hr>
                <a href="<?= site_url('admin/savings/collect/' . $account->id) ?>" class="btn btn-success btn-block">
                    <i class="fas fa-rupee-sign mr-1"></i> Collect Payment
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Transactions & Schedule -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link active" href="#transactions" data-toggle="tab">
                            <i class="fas fa-history mr-1"></i> Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#schedule" data-toggle="tab">
                            <i class="fas fa-calendar mr-1"></i> Schedule
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Transactions Tab -->
                    <div class="active tab-pane" id="transactions">
                        <?php if (empty($transactions)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-receipt fa-3x mb-3"></i>
                                <p>No transactions found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Receipt No</th>
                                            <th>Type</th>
                                            <th class="text-right">Credit</th>
                                            <th class="text-right">Debit</th>
                                            <th class="text-right">Balance</th>
                                            <th>Mode</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $txn): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($txn->transaction_date)) ?></td>
                                            <td><small><?= $txn->receipt_number ?></small></td>
                                            <td>
                                                <?php
                                                $type_badges = [
                                                    'deposit' => 'success',
                                                    'withdrawal' => 'danger',
                                                    'interest' => 'info',
                                                    'penalty' => 'warning'
                                                ];
                                                ?>
                                                <span class="badge badge-<?= $type_badges[$txn->transaction_type] ?? 'secondary' ?>">
                                                    <?= ucfirst($txn->transaction_type) ?>
                                                </span>
                                            </td>
                                            <td class="text-right text-success">
                                                <?= $txn->credit_amount > 0 ? '₹' . number_format($txn->credit_amount) : '-' ?>
                                            </td>
                                            <td class="text-right text-danger">
                                                <?= $txn->debit_amount > 0 ? '₹' . number_format($txn->debit_amount) : '-' ?>
                                            </td>
                                            <td class="text-right font-weight-bold">
                                                ₹<?= number_format($txn->running_balance) ?>
                                            </td>
                                            <td><small><?= ucfirst($txn->payment_mode) ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Schedule Tab -->
                    <div class="tab-pane" id="schedule">
                        <?php if (empty($schedule)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-calendar fa-3x mb-3"></i>
                                <p>No schedule generated</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover">
                                    <thead class="thead-light" style="position: sticky; top: 0;">
                                        <tr>
                                            <th>Month</th>
                                            <th>Due Date</th>
                                            <th class="text-right">Amount</th>
                                            <th>Status</th>
                                            <th>Paid Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schedule as $sch): ?>
                                        <tr class="<?= $sch->status == 'pending' && strtotime($sch->due_date) < time() ? 'table-danger' : '' ?>">
                                            <td><?= date('M Y', strtotime($sch->due_date)) ?></td>
                                            <td><?= date('d M Y', strtotime($sch->due_date)) ?></td>
                                            <td class="text-right">₹<?= number_format($sch->amount) ?></td>
                                            <td>
                                                <?php
                                                $sch_status = [
                                                    'pending' => 'warning',
                                                    'paid' => 'success',
                                                    'partial' => 'info',
                                                    'skipped' => 'secondary'
                                                ];
                                                ?>
                                                <span class="badge badge-<?= $sch_status[$sch->status] ?? 'secondary' ?>">
                                                    <?= ucfirst($sch->status) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= $sch->paid_date ? date('d M Y', strtotime($sch->paid_date)) : '-' ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
