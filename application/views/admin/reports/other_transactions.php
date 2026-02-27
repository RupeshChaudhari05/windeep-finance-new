<!-- Other Transactions Report -->
<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-receipt mr-1"></i> Member Other Transactions</h3>
                <div class="card-tools">
                    <div class="btn-group">
                        <a href="<?= site_url('admin/reports/export_other_transactions?format=csv&from_date=' . $from_date . '&to_date=' . $to_date . '&type=' . $type . '&member_id=' . $member_id) ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-file-csv mr-1"></i> Export CSV
                        </a>
                        <a href="<?= site_url('admin/reports/export_other_transactions?format=excel&from_date=' . $from_date . '&to_date=' . $to_date . '&type=' . $type . '&member_id=' . $member_id) ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-file-excel mr-1"></i> Export Excel
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="get" class="mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="small font-weight-bold">From Date</label>
                            <input type="date" name="from_date" class="form-control form-control-sm" value="<?= $from_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small font-weight-bold">To Date</label>
                            <input type="date" name="to_date" class="form-control form-control-sm" value="<?= $to_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small font-weight-bold">Transaction Type</label>
                            <select name="type" class="form-control form-control-sm">
                                <option value="">-- All Types --</option>
                                <option value="membership_fee" <?= ($type == 'membership_fee') ? 'selected' : '' ?>>Membership Fee</option>
                                <option value="joining_fee" <?= ($type == 'joining_fee') ? 'selected' : '' ?>>Joining Fee</option>
                                <option value="processing_fee" <?= ($type == 'processing_fee') ? 'selected' : '' ?>>Processing Fee</option>
                                <option value="admission_fee" <?= ($type == 'admission_fee') ? 'selected' : '' ?>>Admission Fee</option>
                                <option value="share_capital" <?= ($type == 'share_capital') ? 'selected' : '' ?>>Share Capital</option>
                                <option value="penalty" <?= ($type == 'penalty') ? 'selected' : '' ?>>Penalty</option>
                                <option value="bonus" <?= ($type == 'bonus') ? 'selected' : '' ?>>Bonus</option>
                                <option value="reward" <?= ($type == 'reward') ? 'selected' : '' ?>>Reward</option>
                                <option value="late_fee" <?= ($type == 'late_fee') ? 'selected' : '' ?>>Late Fee</option>
                                <option value="other" <?= ($type == 'other') ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="small font-weight-bold">Member ID</label>
                            <input type="text" name="member_id" class="form-control form-control-sm" value="<?= $member_id ?>" placeholder="Member ID">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-primary btn-block">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Summary Cards -->
                <?php if (!empty($summary)): ?>
                <div class="row mb-3">
                    <?php 
                    $total_all = 0;
                    foreach ($summary as $s): 
                        $total_all += floatval($s->total);
                    ?>
                    <div class="col-md-2 col-sm-4 mb-2">
                        <div class="info-box bg-light mb-0">
                            <div class="info-box-content px-2 py-1">
                                <span class="info-box-text text-muted small"><?= ucwords(str_replace('_', ' ', $s->transaction_type)) ?></span>
                                <span class="info-box-number text-primary"><?= format_amount($s->total) ?></span>
                                <small class="text-muted"><?= $s->count ?> txns</small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="col-md-2 col-sm-4 mb-2">
                        <div class="info-box bg-primary mb-0">
                            <div class="info-box-content px-2 py-1">
                                <span class="info-box-text text-white small">Grand Total</span>
                                <span class="info-box-number text-white"><?= format_amount($total_all) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Transactions Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm" id="otherTxnTable">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Member Code</th>
                                <th>Member Name</th>
                                <th>Transaction Type</th>
                                <th class="text-right">Amount (<?= get_currency_symbol() ?>)</th>
                                <th>Payment Mode</th>
                                <th>Receipt No</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($transactions)): ?>
                                <?php $sr = 1; $grand_total = 0; foreach ($transactions as $txn): $grand_total += floatval($txn->amount); ?>
                                <tr>
                                    <td><?= $sr++ ?></td>
                                    <td><?= format_date($txn->transaction_date) ?></td>
                                    <td>
                                        <a href="<?= site_url('admin/members/view/' . $txn->member_id) ?>">
                                            <?= htmlspecialchars($txn->member_code ?? '') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars(($txn->first_name ?? '') . ' ' . ($txn->last_name ?? '')) ?></td>
                                    <td>
                                        <span class="badge badge-<?= get_txn_type_badge($txn->transaction_type) ?>">
                                            <?= ucwords(str_replace('_', ' ', $txn->transaction_type)) ?>
                                        </span>
                                    </td>
                                    <td class="text-right font-weight-bold"><?= format_amount($txn->amount) ?></td>
                                    <td><?= ucfirst($txn->payment_mode ?? '-') ?></td>
                                    <td><?= htmlspecialchars($txn->receipt_number ?? '-') ?></td>
                                    <td><small><?= htmlspecialchars($txn->description ?? '-') ?></small></td>
                                    <td>
                                        <span class="badge badge-<?= ($txn->status == 'completed') ? 'success' : (($txn->status == 'pending') ? 'warning' : 'danger') ?>">
                                            <?= ucfirst($txn->status) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        No transactions found for the selected filters.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($transactions)): ?>
                        <tfoot>
                            <tr class="bg-light font-weight-bold">
                                <td colspan="5" class="text-right">Total:</td>
                                <td class="text-right"><?= format_amount($grand_total) ?></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function get_txn_type_badge($type) {
    $map = [
        'membership_fee'  => 'info',
        'joining_fee'     => 'primary',
        'processing_fee'  => 'warning',
        'admission_fee'   => 'secondary',
        'share_capital'   => 'dark',
        'penalty'         => 'danger',
        'bonus'           => 'success',
        'reward'          => 'success',
        'late_fee'        => 'danger',
        'other'           => 'secondary'
    ];
    return $map[$type] ?? 'secondary';
}
?>

<script>
$(document).ready(function() {
    $('#otherTxnTable').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 50,
        "lengthMenu": [[25, 50, 100, -1], [25, 50, 100, "All"]],
        "dom": '<"row"<"col-md-6"l><"col-md-6"f>>rtip'
    });
});
</script>
