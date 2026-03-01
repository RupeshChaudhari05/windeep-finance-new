<!-- Savings Account Summary -->
<div class="row">
    <div class="col-md-4">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-piggy-bank mr-1"></i> Account Details</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th>Account No:</th>
                        <td><strong><?= $account->account_number ?></strong></td>
                    </tr>
                    <tr>
                        <th>Scheme:</th>
                        <td><span class="badge badge-info"><?= $account->scheme_name ?></span></td>
                    </tr>
                    <?php if (!empty($account->interest_rate)): ?>
                    <tr>
                        <th>Interest Rate:</th>
                        <td><?= $account->interest_rate ?>% p.a.</td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Monthly Amount:</th>
                        <td><?= format_amount($account->monthly_amount ?? $account->scheme_monthly_amount ?? 0) ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <span class="badge badge-<?= $account->status == 'active' ? 'success' : 'secondary' ?> badge-lg">
                                <?= strtoupper($account->status) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Opened On:</th>
                        <td><?= format_date($account->start_date ?? $account->created_at) ?></td>
                    </tr>
                    <?php if (!empty($account->maturity_date)): ?>
                    <tr>
                        <th>Maturity:</th>
                        <td><?= format_date($account->maturity_date) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <hr>
                
                <div class="info-box bg-success mb-2">
                    <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Current Balance</span>
                        <span class="info-box-number"><?= format_amount($account->current_balance ?? 0) ?></span>
                    </div>
                </div>
                
                <div class="info-box bg-primary mb-2">
                    <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Deposited</span>
                        <span class="info-box-number"><?= format_amount($total_deposits) ?></span>
                    </div>
                </div>
                
                <?php if ($total_withdrawals > 0): ?>
                <div class="info-box bg-warning mb-2">
                    <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Withdrawn</span>
                        <span class="info-box-number"><?= format_amount($total_withdrawals) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($account->interest_earned)): ?>
                <div class="info-box bg-info mb-2">
                    <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Interest Earned</span>
                        <span class="info-box-number"><?= format_amount($account->interest_earned ?? 0) ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Transactions -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exchange-alt mr-1"></i> Transaction History</h3>
                <div class="card-tools">
                    <form method="get" class="form-inline">
                        <input type="date" name="date_from" class="form-control form-control-sm mr-1" value="<?= $date_from ?>" placeholder="From">
                        <input type="date" name="date_to" class="form-control form-control-sm mr-1" value="<?= $date_to ?>" placeholder="To">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($transactions)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-exchange-alt fa-3x mb-3"></i>
                        <p>No transactions found for the selected period.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-sm table-hover table-striped mb-0">
                            <thead class="thead-light" style="position: sticky; top: 0;">
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction Code</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th class="text-right">Amount</th>
                                    <th class="text-right">Balance After</th>
                                    <th>Mode</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $current_date = '';
                                foreach ($transactions as $t): 
                                    $tx_date = format_date($t->transaction_date);
                                    $is_new_date = ($tx_date !== $current_date);
                                    $current_date = $tx_date;
                                ?>
                                <?php if ($is_new_date): ?>
                                <tr class="bg-light">
                                    <td colspan="7" class="font-weight-bold text-muted py-1">
                                        <i class="far fa-calendar-alt mr-1"></i> <?= $tx_date ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><small><?= date('h:i A', strtotime($t->created_at)) ?></small></td>
                                    <td><small class="text-primary"><?= $t->transaction_code ?? '-' ?></small></td>
                                    <td>
                                        <?php if ($t->transaction_type == 'deposit'): ?>
                                            <span class="badge badge-success"><i class="fas fa-arrow-down mr-1"></i>Deposit</span>
                                        <?php elseif ($t->transaction_type == 'withdrawal'): ?>
                                            <span class="badge badge-danger"><i class="fas fa-arrow-up mr-1"></i>Withdrawal</span>
                                        <?php elseif ($t->transaction_type == 'interest'): ?>
                                            <span class="badge badge-info"><i class="fas fa-percentage mr-1"></i>Interest</span>
                                        <?php elseif ($t->transaction_type == 'loan_adjustment'): ?>
                                            <span class="badge badge-warning"><i class="fas fa-exchange-alt mr-1"></i>Loan Adj.</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary"><?= ucfirst(str_replace('_', ' ', $t->transaction_type)) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= htmlspecialchars($t->narration ?? '-') ?></small></td>
                                    <td class="text-right font-weight-bold <?= $t->transaction_type == 'deposit' || $t->transaction_type == 'interest' ? 'text-success' : 'text-danger' ?>">
                                        <?= ($t->transaction_type == 'deposit' || $t->transaction_type == 'interest') ? '+' : '-' ?><?= format_amount($t->amount) ?>
                                    </td>
                                    <td class="text-right"><?= format_amount($t->balance_after ?? 0) ?></td>
                                    <td>
                                        <?php
                                        $mode = $t->payment_mode ?? 'cash';
                                        $mode_icons = [
                                            'cash' => 'money-bill-wave',
                                            'bank_transfer' => 'university',
                                            'cheque' => 'file-invoice-dollar',
                                            'upi' => 'mobile-alt',
                                            'auto_debit' => 'credit-card'
                                        ];
                                        $icon = $mode_icons[$mode] ?? 'rupee-sign';
                                        ?>
                                        <small><i class="fas fa-<?= $icon ?> mr-1"></i><?= ucfirst(str_replace('_', ' ', $mode)) ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <small class="text-muted">Showing <?= count($transactions) ?> transaction(s) from <?= format_date($date_from) ?> to <?= format_date($date_to) ?></small>
            </div>
        </div>
        
        <!-- Payment Schedule -->
        <?php if (!empty($schedule)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Payment Schedule</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light" style="position: sticky; top: 0;">
                            <tr>
                                <th>Due Date</th>
                                <th class="text-right">Due Amount</th>
                                <th class="text-right">Paid</th>
                                <th>Status</th>
                                <th>Paid Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedule as $s): ?>
                            <tr class="<?= $s->status == 'paid' ? 'table-success' : ($s->status == 'overdue' || (in_array($s->status, ['pending', 'partial']) && strtotime($s->due_date) < time()) ? 'table-danger' : '') ?>">
                                <td><?= format_date($s->due_date) ?></td>
                                <td class="text-right"><?= format_amount($s->due_amount) ?></td>
                                <td class="text-right"><?= format_amount($s->paid_amount ?? 0) ?></td>
                                <td>
                                    <?php
                                    $sch_status = ['pending' => 'warning', 'paid' => 'success', 'partial' => 'info', 'overdue' => 'danger'];
                                    ?>
                                    <span class="badge badge-<?= $sch_status[$s->status] ?? 'secondary' ?>"><?= ucfirst($s->status) ?></span>
                                </td>
                                <td><?= !empty($s->paid_date) ? format_date($s->paid_date) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
