<!-- Summary Cards -->
<?php if (!empty($installments)): ?>
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h4>₹<?= number_format($summary['total_emi'], 0) ?></h4>
                <p>Total EMI</p>
            </div>
            <div class="icon"><i class="fas fa-calculator"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h4>₹<?= number_format($summary['total_paid'], 0) ?></h4>
                <p>Total Paid (<?= $summary['paid_count'] ?> EMIs)</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h4>₹<?= number_format($summary['total_outstanding'], 0) ?></h4>
                <p>Outstanding (<?= $summary['pending_count'] ?> Pending)</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-<?= $summary['overdue_count'] > 0 ? 'danger' : 'secondary' ?>">
            <div class="inner">
                <h4><?= $summary['overdue_count'] ?></h4>
                <p>Overdue EMIs</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> EMI Schedule</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-sm btn-default" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="form-inline mb-3">
            <label class="mr-2">Loan</label>
            <select name="loan_id" class="form-control form-control-sm mr-2">
                <option value="">All Loans</option>
                <?php foreach ($member_loans as $l): ?>
                <option value="<?= $l->id ?>" <?= $this->input->get('loan_id') == $l->id ? 'selected' : '' ?>><?= $l->loan_number ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
            <?php if ($this->input->get('loan_id')): ?>
                <a href="<?= site_url('member/installments') ?>" class="btn btn-default btn-sm ml-2">Clear</a>
            <?php endif; ?>
        </form>

        <?php if (empty($installments)): ?>
            <div class="text-center py-4">
                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                <p class="text-muted">No installments found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm table-striped mb-0" id="installmentsTable">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center">#</th>
                            <th>Loan</th>
                            <th>Due Date</th>
                            <th class="text-right">Principal</th>
                            <th class="text-right">Interest</th>
                            <th class="text-right">EMI</th>
                            <th class="text-right">Fine</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Balance</th>
                            <th class="text-right">Outstanding</th>
                            <th class="text-center">Status</th>
                            <th>Paid Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($installments as $inst):
                            $balance = (float) $inst->emi_amount + (float) ($inst->fine_amount ?? 0) - (float) ($inst->total_paid ?? 0);
                            if ($balance < 0) $balance = 0;
                            $is_overdue = $inst->status === 'overdue';
                            $is_today = ($inst->due_date === date('Y-m-d'));
                            $row_class = $is_overdue ? 'table-danger' : ($is_today ? 'table-warning' : ($inst->status === 'paid' ? '' : ''));
                        ?>
                        <tr class="<?= $row_class ?>">
                            <td class="text-center">
                                <span class="badge badge-light"><?= $inst->installment_number ?></span>
                            </td>
                            <td>
                                <a href="<?= site_url('member/loans/view/' . $inst->loan_id) ?>" class="text-primary">
                                    <?= $inst->loan_number ?>
                                </a>
                                <br><small class="text-muted"><?= $inst->product_name ?></small>
                            </td>
                            <td>
                                <?php
                                    $due = new DateTime($inst->due_date);
                                    $today = new DateTime();
                                    $days_diff = (int) $today->diff($due)->format('%r%a');
                                ?>
                                <span class="<?= $is_overdue ? 'text-danger font-weight-bold' : '' ?>">
                                    <?= format_date($inst->due_date) ?>
                                </span>
                                <?php if ($is_overdue && $inst->days_late > 0): ?>
                                    <br><small class="text-danger"><?= $inst->days_late ?> days late</small>
                                <?php elseif ($inst->status === 'upcoming' && $days_diff >= 0 && $days_diff <= 7): ?>
                                    <br><small class="text-info">Due in <?= $days_diff ?> day<?= $days_diff != 1 ? 's' : '' ?></small>
                                <?php elseif ($is_today): ?>
                                    <br><small class="text-warning font-weight-bold">Due Today</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                ₹<?= number_format($inst->principal_amount, 2) ?>
                            </td>
                            <td class="text-right">
                                ₹<?= number_format($inst->interest_amount, 2) ?>
                            </td>
                            <td class="text-right font-weight-bold">
                                ₹<?= number_format($inst->emi_amount, 2) ?>
                            </td>
                            <td class="text-right <?= ($inst->fine_amount ?? 0) > 0 ? 'text-danger' : 'text-muted' ?>">
                                ₹<?= number_format($inst->fine_amount ?? 0, 2) ?>
                            </td>
                            <td class="text-right text-success">
                                ₹<?= number_format($inst->total_paid ?? 0, 2) ?>
                            </td>
                            <td class="text-right <?= $balance > 0 ? 'text-danger font-weight-bold' : 'text-muted' ?>">
                                ₹<?= number_format($balance, 2) ?>
                            </td>
                            <td class="text-right">
                                <small class="text-muted">₹<?= number_format($inst->outstanding_principal_after, 2) ?></small>
                            </td>
                            <td class="text-center">
                                <?php
                                $status_map = [
                                    'paid' => ['success', 'check-circle'],
                                    'partial' => ['info', 'adjust'],
                                    'pending' => ['warning', 'clock'],
                                    'overdue' => ['danger', 'exclamation-circle'],
                                    'upcoming' => ['secondary', 'calendar'],
                                    'skipped' => ['dark', 'forward'],
                                    'waived' => ['primary', 'hand-holding-heart'],
                                    'interest_only' => ['info', 'percentage'],
                                ];
                                $s = $status_map[$inst->status] ?? ['secondary', 'question'];
                                ?>
                                <span class="badge badge-<?= $s[0] ?>">
                                    <i class="fas fa-<?= $s[1] ?> mr-1"></i><?= ucfirst(str_replace('_', ' ', $inst->status)) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($inst->paid_date)): ?>
                                    <?= format_date($inst->paid_date) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="3" class="text-right">Totals:</td>
                            <td class="text-right">₹<?= number_format($summary['total_principal'], 2) ?></td>
                            <td class="text-right">₹<?= number_format($summary['total_interest'], 2) ?></td>
                            <td class="text-right">₹<?= number_format($summary['total_emi'], 2) ?></td>
                            <td class="text-right text-danger">₹<?= number_format($summary['total_fine'], 2) ?></td>
                            <td class="text-right text-success">₹<?= number_format($summary['total_paid'], 2) ?></td>
                            <td class="text-right text-danger">₹<?= number_format($summary['total_outstanding'], 2) ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
#installmentsTable th { white-space: nowrap; font-size: 0.85rem; }
#installmentsTable td { vertical-align: middle; font-size: 0.85rem; }
</style>