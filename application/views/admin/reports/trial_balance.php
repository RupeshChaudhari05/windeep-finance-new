<!-- Trial Balance -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-balance-scale mr-1"></i> Trial Balance</h3>
        <div class="card-tools">
            <form class="form-inline" method="get">
                <label class="mr-2">As on:</label>
                <input type="date" name="as_on" class="form-control form-control-sm mr-2" value="<?= $as_on ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync"></i></button>
            </form>
            <a href="<?= site_url('admin/reports/trial_balance?export=excel&as_on=' . $as_on) ?>" class="btn btn-success btn-sm ml-2">
                <i class="fas fa-download"></i>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white text-center">
        <h4 class="mb-0">Trial Balance as on <?= format_date($as_on) ?></h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0" id="trialBalanceTable">
                <thead class="thead-dark">
                    <tr>
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th>Account Type</th>
                        <th class="text-right">Debit (₹)</th>
                        <th class="text-right">Credit (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trial_balance as $account): ?>
                    <?php 
                    $balance = $account->total_debit - $account->total_credit;
                    $debit = $balance > 0 ? $balance : 0;
                    $credit = $balance < 0 ? abs($balance) : 0;
                    ?>
                    <tr>
                        <td><code><?= $account->account_code ?></code></td>
                        <td><?= $account->account_name ?></td>
                        <td>
                            <span class="badge badge-<?= 
                                $account->account_type == 'asset' ? 'success' : 
                                ($account->account_type == 'liability' ? 'danger' : 
                                ($account->account_type == 'income' ? 'info' : 
                                ($account->account_type == 'expense' ? 'warning' : 'primary'))) ?>">
                                <?= ucfirst($account->account_type) ?>
                            </span>
                        </td>
                        <td class="text-right"><?= $debit > 0 ? number_format($debit, 2) : '-' ?></td>
                        <td class="text-right"><?= $credit > 0 ? number_format($credit, 2) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <th colspan="3" class="text-right">Total</th>
                        <th class="text-right"><?= number_format($totals['debit'], 2) ?></th>
                        <th class="text-right"><?= number_format($totals['credit'], 2) ?></th>
                    </tr>
                    <tr class="<?= abs($totals['debit'] - $totals['credit']) < 0.01 ? 'table-success' : 'table-danger' ?>">
                        <th colspan="3" class="text-right">Difference</th>
                        <th colspan="2" class="text-center">
                            <?php if (abs($totals['debit'] - $totals['credit']) < 0.01): ?>
                            <i class="fas fa-check-circle"></i> Balanced
                            <?php else: ?>
                            <i class="fas fa-exclamation-triangle"></i> <?= number_format(abs($totals['debit'] - $totals['credit']), 2) ?>
                            <?php endif; ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#trialBalanceTable').DataTable({
        "paging": false,
        "searching": true,
        "ordering": true,
        "order": [[0, "asc"]]
    });
});
</script>
