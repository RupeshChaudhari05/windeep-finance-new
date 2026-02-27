<!-- Disbursement Queue -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-money-bill-wave mr-1"></i> Ready for Disbursement</h3>
        <div class="card-tools">
            <a href="<?= site_url('admin/loans') ?>" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Loans
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="disbursementTable">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Application No</th>
                        <th>Member</th>
                        <th>Product</th>
                        <th class="text-right">Approved Amount</th>
                        <th>Tenure</th>
                        <th>Interest Rate</th>
                        <th class="text-right">EMI</th>
                        <th>Approved Date</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No applications ready for disbursement</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php $i = 1; $total_amount = 0; foreach ($applications as $app): ?>
                        <?php $total_amount += $app->approved_amount; ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td>
                                <a href="<?= site_url('admin/loans/view_application/' . $app->id) ?>" class="font-weight-bold">
                                    <?= $app->application_number ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= site_url('admin/members/view/' . $app->member_id) ?>">
                                    <?= $app->member_code ?><br>
                                    <small><?= $app->first_name ?> <?= $app->last_name ?></small>
                                </a>
                                <br><small class="text-muted"><?= $app->phone ?></small>
                            </td>
                            <td><?= $app->product_name ?? 'N/A' ?></td>
                            <td class="text-right font-weight-bold text-success"><?= format_amount($app->approved_amount) ?></td>
                            <td><?= $app->approved_tenure_months ?> months</td>
                            <td><?= number_format($app->approved_interest_rate, 2) ?>% p.a.</td>
                            <td class="text-right">
                                <?php 
                                // Calculate EMI
                                $P = $app->approved_amount;
                                $r = ($app->approved_interest_rate / 100) / 12;
                                $n = $app->approved_tenure_months;
                                if ($r > 0) {
                                    $emi = ($P * $r * pow(1 + $r, $n)) / (pow(1 + $r, $n) - 1);
                                } else {
                                    $emi = $P / $n;
                                }
                                ?>
                                <?= format_amount($emi) ?>
                            </td>
                            <td><?= format_date($app->admin_approved_at) ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= site_url('admin/loans/view_application/' . $app->id) ?>" class="btn btn-info btn-sm" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= site_url('admin/loans/disburse/' . $app->id) ?>" class="btn btn-success btn-sm" title="Disburse">
                                        <i class="fas fa-money-bill-wave"></i> Disburse
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($applications)): ?>
                <tfoot class="bg-light">
                    <tr>
                        <th colspan="4" class="text-right">Total Disbursement Required:</th>
                        <th class="text-right text-success"><?= format_amount($total_amount) ?></th>
                        <th colspan="5"></th>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Summary Stats -->
<div class="row mt-3">
    <div class="col-md-4">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-check-double"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ready for Disbursement</span>
                <span class="info-box-number"><?= count($applications ?? []) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-rupee-sign"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Amount</span>
                <span class="info-box-number"><?= format_amount($total_amount ?? 0) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-calendar-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Disbursed This Month</span>
                <span class="info-box-number"><?= format_amount($stats['month_disbursement'] ?? 0, 0) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Disbursements -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Recent Disbursements</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Loan Number</th>
                        <th>Member</th>
                        <th class="text-right">Amount</th>
                        <th>Disbursement Date</th>
                        <th>Mode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_disbursements)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">No recent disbursements</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recent_disbursements as $loan): ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('admin/loans/view/' . $loan->id) ?>"><?= $loan->loan_number ?></a>
                            </td>
                            <td><?= $loan->first_name ?> <?= $loan->last_name ?></td>
                            <td class="text-right"><?= format_amount($loan->principal_amount, 0) ?></td>
                            <td><?= format_date($loan->disbursement_date) ?></td>
                            <td><?= ucfirst($loan->disbursement_mode ?? 'N/A') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#disbursementTable').DataTable({
        "order": [[8, "asc"]],
        "pageLength": 25
    });
});
</script>
