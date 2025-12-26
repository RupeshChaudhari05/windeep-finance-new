<!-- Unmatched Bank Transactions -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title"><i class="fas fa-unlink mr-1"></i> Unmatched Transactions</h3>
                <div class="card-tools">
                    <span class="badge badge-light"><?= count($transactions) ?> transactions</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="unmatchedTable">
                        <thead class="thead-light">
                            <tr>
                                <th width="50"><input type="checkbox" id="selectAll"></th>
                                <th>Date</th>
                                <th>Bank</th>
                                <th>Description</th>
                                <th>Reference</th>
                                <th class="text-right">Amount</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <p class="text-muted">All transactions have been matched!</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><input type="checkbox" class="txn-check" value="<?= $txn->id ?>"></td>
                                    <td><?= date('d M Y', strtotime($txn->transaction_date)) ?></td>
                                    <td>
                                        <small><?= $txn->bank_name ?></small><br>
                                        <small class="text-muted"><?= substr($txn->account_number, -4) ?></small>
                                    </td>
                                    <td><?= character_limiter($txn->description, 35) ?></td>
                                    <td><small><?= $txn->reference_number ?: '-' ?></small></td>
                                    <td class="text-right">
                                        <span class="text-<?= $txn->transaction_type == 'credit' ? 'success' : 'danger' ?> font-weight-bold">
                                            <?= $txn->transaction_type == 'credit' ? '+' : '-' ?>₹<?= number_format($txn->amount, 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-info btn-match" data-id="<?= $txn->id ?>" data-amount="<?= $txn->amount ?>">
                                                <i class="fas fa-link"></i>
                                            </button>
                                            <button class="btn btn-success btn-auto-match" data-id="<?= $txn->id ?>" title="Auto Match">
                                                <i class="fas fa-magic"></i>
                                            </button>
                                            <button class="btn btn-secondary btn-ignore" data-id="<?= $txn->id ?>" title="Mark as Ignored">
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if (!empty($transactions)): ?>
            <div class="card-footer">
                <button class="btn btn-info" id="bulkMatch" disabled>
                    <i class="fas fa-link mr-1"></i> Bulk Match Selected
                </button>
                <button class="btn btn-secondary" id="bulkIgnore" disabled>
                    <i class="fas fa-eye-slash mr-1"></i> Ignore Selected
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Potential Savings Matches -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="card-title"><i class="fas fa-piggy-bank mr-1"></i> Pending Savings</h3>
            </div>
            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0">
                    <?php if (empty($savings_payments)): ?>
                    <tr><td class="text-center text-muted py-3">No pending savings payments</td></tr>
                    <?php else: ?>
                        <?php foreach ($savings_payments as $sp): ?>
                        <tr class="potential-match" data-amount="<?= $sp->amount ?>">
                            <td>
                                <strong><?= $sp->member_code ?></strong><br>
                                <small><?= $sp->first_name ?> <?= $sp->last_name ?></small>
                            </td>
                            <td class="text-right">
                                ₹<?= number_format($sp->amount, 2) ?><br>
                                <small class="text-muted"><?= date('d M', strtotime($sp->payment_date ?? $sp->created_at)) ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <!-- Potential Loan Matches -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i> Pending EMIs</h3>
            </div>
            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0">
                    <?php if (empty($loan_payments)): ?>
                    <tr><td class="text-center text-muted py-3">No pending loan payments</td></tr>
                    <?php else: ?>
                        <?php foreach ($loan_payments as $lp): ?>
                        <tr class="potential-match" data-amount="<?= $lp->emi_amount ?>">
                            <td>
                                <strong><?= $lp->member_code ?></strong><br>
                                <small><?= $lp->loan_number ?></small>
                            </td>
                            <td class="text-right">
                                ₹<?= number_format($lp->emi_amount, 2) ?><br>
                                <small class="text-muted">Due: <?= date('d M', strtotime($lp->due_date)) ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="card bg-light">
            <div class="card-body">
                <h6><i class="fas fa-chart-bar text-primary"></i> Summary</h6>
                <?php
                $total_credits = 0;
                $total_debits = 0;
                foreach ($transactions as $t) {
                    if ($t->transaction_type == 'credit') $total_credits += $t->amount;
                    else $total_debits += $t->amount;
                }
                ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Unmatched Credits:</span>
                    <span class="text-success">₹<?= number_format($total_credits, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Unmatched Debits:</span>
                    <span class="text-danger">₹<?= number_format($total_debits, 2) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#unmatchedTable').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 25,
        "columnDefs": [{"orderable": false, "targets": [0, 6]}]
    });
    
    // Select all
    $('#selectAll').change(function() {
        $('.txn-check').prop('checked', $(this).is(':checked'));
        updateBulkButtons();
    });
    
    $('.txn-check').change(updateBulkButtons);
    
    function updateBulkButtons() {
        var checked = $('.txn-check:checked').length;
        $('#bulkMatch, #bulkIgnore').prop('disabled', checked == 0);
    }
    
    // Auto match
    $('.btn-auto-match').click(function() {
        var id = $(this).data('id');
        $.post('<?= site_url('admin/bank/auto_match') ?>', {transaction_id: id}, function(response) {
            if (response.success) {
                toastr.success('Transaction matched automatically');
                location.reload();
            } else {
                toastr.warning(response.message || 'No automatic match found');
            }
        }, 'json');
    });
    
    // Ignore
    $('.btn-ignore').click(function() {
        var id = $(this).data('id');
        if (confirm('Mark this transaction as ignored?')) {
            $.post('<?= site_url('admin/bank/ignore_transaction') ?>', {transaction_id: id}, function(response) {
                if (response.success) {
                    toastr.success('Transaction ignored');
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            }, 'json');
        }
    });
});
</script>
