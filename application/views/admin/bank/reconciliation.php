<!-- Bank Reconciliation Report -->

<!-- Filters -->
<div class="card card-outline card-primary">
    <div class="card-header py-2">
        <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Reconciliation Report</h3>
        <div class="card-tools">
            <a href="<?= site_url('admin/bank/statement') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back to Statement
            </a>
        </div>
    </div>
    <div class="card-body py-2">
        <form method="get" id="reconFilterForm">
            <div class="row">
                <div class="col-md-3">
                    <label class="small font-weight-bold">Financial Year</label>
                    <select class="form-control form-control-sm" name="fy_id">
                        <?php foreach ($financial_years as $fy): ?>
                        <option value="<?= $fy->id ?>" <?= ($selected_fy && $selected_fy->id == $fy->id) ? 'selected' : '' ?>>
                            <?= $fy->year_code ?>
                            <?php if (isset($fy->is_active) && $fy->is_active): ?> (Active)<?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold">Bank Account</label>
                    <select class="form-control form-control-sm" name="bank_id">
                        <option value="">All Accounts</option>
                        <?php foreach ($bank_accounts as $acc): ?>
                        <option value="<?= $acc->id ?>" <?= (isset($filters['bank_id']) && $filters['bank_id'] == $acc->id) ? 'selected' : '' ?>>
                            <?= $acc->account_name ?? $acc->bank_name ?> - <?= $acc->account_number ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Apply</button>
                        <a href="<?= site_url('admin/bank/reconciliation') ?>" class="btn btn-default btn-sm"><i class="fas fa-times mr-1"></i> Reset</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$s = isset($stats->totals) ? $stats->totals : new stdClass();
$total = isset($s->total_transactions) ? (int)$s->total_transactions : 0;
$mapped = isset($s->mapped_count) ? (int)$s->mapped_count : 0;
$unmapped = isset($s->unmapped_count) ? (int)$s->unmapped_count : 0;
$partial = isset($s->partial_count) ? (int)$s->partial_count : 0;
$ignored = isset($s->ignored_count) ? (int)$s->ignored_count : 0;
$total_credits = isset($s->total_credits) ? (float)$s->total_credits : 0;
$total_debits = isset($s->total_debits) ? (float)$s->total_debits : 0;
$mapped_credits = isset($s->mapped_credits) ? (float)$s->mapped_credits : 0;
$mapped_debits = isset($s->mapped_debits) ? (float)$s->mapped_debits : 0;
$unmapped_credits = isset($s->unmapped_credits) ? (float)$s->unmapped_credits : 0;
$unmapped_debits = isset($s->unmapped_debits) ? (float)$s->unmapped_debits : 0;
$reconciled = $mapped + $ignored;
$pct = $total > 0 ? round(($reconciled / $total) * 100) : 0;
?>

<!-- FY Info -->
<?php if ($selected_fy): ?>
<div class="alert alert-info py-2">
    <i class="fas fa-calendar-alt mr-1"></i>
    <strong>Financial Year: <?= $selected_fy->year_code ?></strong>
    &mdash; <?= format_date($selected_fy->start_date) ?> to <?= format_date($selected_fy->end_date) ?>
</div>
<?php endif; ?>

<!-- Overall Reconciliation Status -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-gradient-primary text-white py-2">
                <h5 class="card-title mb-0"><i class="fas fa-tasks mr-1"></i> Reconciliation Status</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="mb-0"><?= $pct ?>% <small class="text-muted">Reconciled</small></h2>
                    <div class="text-right">
                        <span class="badge badge-success badge-lg p-2"><?= $mapped ?> Mapped</span>
                        <span class="badge badge-warning badge-lg p-2 ml-1"><?= $partial ?> Partial</span>
                        <span class="badge badge-dark badge-lg p-2 ml-1"><?= $ignored ?> Ignored</span>
                        <span class="badge badge-secondary badge-lg p-2 ml-1"><?= $unmapped ?> Unmapped</span>
                    </div>
                </div>
                <div class="progress mb-3" style="height: 30px;">
                    <?php if ($total > 0): ?>
                    <div class="progress-bar bg-success" style="width: <?= round(($mapped/$total)*100) ?>%" title="Mapped"><?= $mapped ?></div>
                    <div class="progress-bar bg-warning" style="width: <?= round(($partial/$total)*100) ?>%" title="Partial"><?= $partial ?></div>
                    <div class="progress-bar bg-dark" style="width: <?= round(($ignored/$total)*100) ?>%" title="Ignored"><?= $ignored ?></div>
                    <div class="progress-bar bg-secondary" style="width: <?= round(($unmapped/$total)*100) ?>%" title="Unmapped"><?= $unmapped ?></div>
                    <?php endif; ?>
                </div>

                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="border rounded p-2">
                            <h4 class="mb-0"><?= $total ?></h4>
                            <small class="text-muted">Total Transactions</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2">
                            <h4 class="mb-0 text-success"><?= format_amount($total_credits, 0) ?></h4>
                            <small class="text-muted">Total Credits</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2">
                            <h4 class="mb-0 text-danger"><?= format_amount($total_debits, 0) ?></h4>
                            <small class="text-muted">Total Debits</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2">
                            <h4 class="mb-0 text-primary"><?= format_amount($total_credits - $total_debits, 0) ?></h4>
                            <small class="text-muted">Net Balance</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mapping Type Breakdown -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-gradient-success text-white py-2">
                <h5 class="card-title mb-0"><i class="fas fa-chart-pie mr-1"></i> Mapping Breakdown</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Type</th>
                            <th class="text-right">Count</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stats->by_type)): ?>
                        <?php foreach ($stats->by_type as $bt): ?>
                        <tr>
                            <td>
                                <?php
                                $badge_class = 'secondary';
                                switch($bt->mapping_type) {
                                    case 'emi': case 'loan_payment': $badge_class = 'primary'; break;
                                    case 'savings': $badge_class = 'success'; break;
                                    case 'fine': $badge_class = 'danger'; break;
                                    case 'disbursement': $badge_class = 'info'; break;
                                    case 'internal_transfer': case 'bank_charge': $badge_class = 'dark'; break;
                                }
                                ?>
                                <span class="badge badge-<?= $badge_class ?>">
                                    <?= ucwords(str_replace('_', ' ', $bt->mapping_type)) ?>
                                </span>
                            </td>
                            <td class="text-right"><?= $bt->count ?></td>
                            <td class="text-right font-weight-bold"><?= format_amount($bt->total_amount, 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">No mapping data</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Credit / Debit Reconciliation -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-gradient-success text-white py-2">
                <h5 class="card-title mb-0"><i class="fas fa-arrow-down mr-1"></i> Credits Reconciliation</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Total Credits</td>
                        <td class="text-right font-weight-bold"><?= format_amount($total_credits) ?></td>
                    </tr>
                    <tr class="text-success">
                        <td>Mapped Credits</td>
                        <td class="text-right font-weight-bold"><?= format_amount($mapped_credits) ?></td>
                    </tr>
                    <tr class="text-danger">
                        <td>Unmapped Credits</td>
                        <td class="text-right font-weight-bold"><?= format_amount($unmapped_credits) ?></td>
                    </tr>
                    <tr>
                        <td>Reconciliation %</td>
                        <td class="text-right font-weight-bold">
                            <?php $cr_pct = $total_credits > 0 ? round(($mapped_credits / $total_credits) * 100) : 0; ?>
                            <div class="progress" style="height: 16px;">
                                <div class="progress-bar bg-success" style="width: <?= $cr_pct ?>%"><?= $cr_pct ?>%</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-gradient-danger text-white py-2">
                <h5 class="card-title mb-0"><i class="fas fa-arrow-up mr-1"></i> Debits Reconciliation</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Total Debits</td>
                        <td class="text-right font-weight-bold"><?= format_amount($total_debits) ?></td>
                    </tr>
                    <tr class="text-success">
                        <td>Mapped Debits</td>
                        <td class="text-right font-weight-bold"><?= format_amount($mapped_debits) ?></td>
                    </tr>
                    <tr class="text-danger">
                        <td>Unmapped Debits</td>
                        <td class="text-right font-weight-bold"><?= format_amount($unmapped_debits) ?></td>
                    </tr>
                    <tr>
                        <td>Reconciliation %</td>
                        <td class="text-right font-weight-bold">
                            <?php $dr_pct = $total_debits > 0 ? round(($mapped_debits / $total_debits) * 100) : 0; ?>
                            <div class="progress" style="height: 16px;">
                                <div class="progress-bar bg-danger" style="width: <?= $dr_pct ?>%"><?= $dr_pct ?>%</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Trend -->
<div class="card">
    <div class="card-header bg-gradient-info text-white py-2">
        <h5 class="card-title mb-0"><i class="fas fa-chart-line mr-1"></i> Monthly Reconciliation Trend</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped table-bordered mb-0" id="monthlyTrendTable">
                <thead class="thead-light">
                    <tr>
                        <th>Month</th>
                        <th class="text-right">Total Txns</th>
                        <th class="text-right">Mapped</th>
                        <th class="text-right">Unmapped</th>
                        <th class="text-right">Credits</th>
                        <th class="text-right">Debits</th>
                        <th class="text-right">Net</th>
                        <th width="150">Reconciliation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stats->monthly)): ?>
                    <?php foreach ($stats->monthly as $m): ?>
                    <?php $month_pct = $m->total > 0 ? round(($m->mapped / $m->total) * 100) : 0; ?>
                    <tr>
                        <td><strong><?= date('M Y', strtotime($m->month . '-01')) ?></strong></td>
                        <td class="text-right"><?= $m->total ?></td>
                        <td class="text-right text-success"><?= $m->mapped ?></td>
                        <td class="text-right text-danger"><?= $m->unmapped ?></td>
                        <td class="text-right text-success"><?= format_amount($m->credits, 0) ?></td>
                        <td class="text-right text-danger"><?= format_amount($m->debits, 0) ?></td>
                        <td class="text-right font-weight-bold"><?= format_amount($m->credits - $m->debits, 0) ?></td>
                        <td>
                            <div class="progress" style="height: 16px;">
                                <div class="progress-bar bg-<?= $month_pct >= 90 ? 'success' : ($month_pct >= 50 ? 'warning' : 'danger') ?>" style="width: <?= $month_pct ?>%">
                                    <?= $month_pct ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="8" class="text-center text-muted py-3">No monthly data available</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Top Members -->
<div class="card">
    <div class="card-header bg-gradient-warning py-2">
        <h5 class="card-title mb-0"><i class="fas fa-trophy mr-1"></i> Top 10 Members by Transaction Amount</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Member Code</th>
                        <th>Member Name</th>
                        <th class="text-right">Transactions</th>
                        <th class="text-right">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stats->top_members)): ?>
                    <?php $rank = 0; foreach ($stats->top_members as $tm): $rank++; ?>
                    <tr>
                        <td><?= $rank ?></td>
                        <td><strong><?= $tm->member_code ?></strong></td>
                        <td><?= $tm->member_name ?></td>
                        <td class="text-right"><?= $tm->transaction_count ?></td>
                        <td class="text-right font-weight-bold text-primary"><?= format_amount($tm->total_amount) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No member data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Disbursement Tracking -->
<?php if (!empty($disbursements)): ?>
<div class="card">
    <div class="card-header py-2" style="background: linear-gradient(135deg, #6f42c1, #9b59b6); color: white;">
        <h5 class="card-title mb-0"><i class="fas fa-hand-holding-usd mr-1"></i> Disbursement Tracking (<?= count($disbursements) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0" id="disbursementTable">
                <thead class="thead-light">
                    <tr>
                        <th>Date</th>
                        <th>Loan Number</th>
                        <th>Member</th>
                        <th class="text-right">Amount</th>
                        <th>Bank</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($disbursements as $d): ?>
                    <tr>
                        <td><?= format_date($d->disbursement_date) ?></td>
                        <td><strong><?= $d->loan_number ?></strong></td>
                        <td><?= $d->member_code ?> - <?= $d->member_name ?></td>
                        <td class="text-right font-weight-bold"><?= format_amount($d->amount) ?></td>
                        <td><small><?= $d->bank_name ?> <?= $d->bank_account_number ?></small></td>
                        <td>
                            <span class="badge badge-<?= $d->status == 'completed' ? 'success' : 'warning' ?>">
                                <?= ucfirst($d->status) ?>
                            </span>
                        </td>
                        <td><small><?= $d->remarks ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Internal Transactions -->
<?php if (!empty($internal_transactions)): ?>
<div class="card">
    <div class="card-header bg-gradient-secondary text-white py-2">
        <h5 class="card-title mb-0"><i class="fas fa-exchange-alt mr-1"></i> Internal Transactions (<?= count($internal_transactions) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0" id="internalTable">
                <thead class="thead-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($internal_transactions as $it): ?>
                    <tr>
                        <td><?= format_date($it->transaction_date) ?></td>
                        <td>
                            <span class="badge badge-dark"><?= ucwords(str_replace('_', ' ', $it->transaction_type)) ?></span>
                        </td>
                        <td><?= $it->description ?></td>
                        <td class="text-right font-weight-bold"><?= format_amount($it->amount) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function() {
    $('#monthlyTrendTable').DataTable({
        "paging": false,
        "searching": false,
        "info": false,
        "order": [[0, "asc"]]
    });
    if ($('#disbursementTable').length) {
        $('#disbursementTable').DataTable({ "pageLength": 25, "order": [[0, "desc"]] });
    }
    if ($('#internalTable').length) {
        $('#internalTable').DataTable({ "pageLength": 25, "order": [[0, "desc"]] });
    }
});
</script>
