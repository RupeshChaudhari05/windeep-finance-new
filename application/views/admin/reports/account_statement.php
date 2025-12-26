<!-- Account Statement -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> Account Statement</h3>
    </div>
    <div class="card-body">
        <form method="get" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Select Account <span class="text-danger">*</span></label>
                        <select name="account_id" class="form-control select2" required>
                            <option value="">Choose Account</option>
                            <?php foreach ($accounts ?? [] as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($filters['account_id'] ?? '') == $acc->id ? 'selected' : '' ?>>
                                <?= $acc->account_code ?> - <?= $acc->account_name ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?= $filters['from_date'] ?? date('Y-m-01') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?= $filters['to_date'] ?? date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Generate</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (isset($statement) && $statement): ?>
<!-- Account Info -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><?= $account->account_name ?></h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Account Code:</td>
                        <td><strong><?= $account->account_code ?></strong></td>
                    </tr>
                    <tr>
                        <td>Account Type:</td>
                        <td><span class="badge badge-info"><?= ucfirst($account->account_type) ?></span></td>
                    </tr>
                    <tr>
                        <td>Period:</td>
                        <td><?= format_date($filters['from_date']) ?> to <?= format_date($filters['to_date']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="row">
            <div class="col-md-6">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-arrow-left"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Debit</span>
                        <span class="info-box-number"><?= number_format($statement['total_debit'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-arrow-right"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Credit</span>
                        <span class="info-box-number"><?= number_format($statement['total_credit'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="info-box bg-<?= ($statement['closing_balance'] ?? 0) >= 0 ? 'primary' : 'warning' ?>">
            <span class="info-box-icon"><i class="fas fa-balance-scale"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Closing Balance</span>
                <span class="info-box-number">
                    <?= number_format(abs($statement['closing_balance'] ?? 0), 2) ?>
                    <?= ($statement['closing_balance'] ?? 0) >= 0 ? 'Dr' : 'Cr' ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Statement Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Transaction Details</h5>
        <div class="card-tools">
            <a href="<?= site_url('admin/reports/account_statement?' . http_build_query($filters) . '&print=1') ?>" class="btn btn-secondary btn-sm" target="_blank">
                <i class="fas fa-print"></i> Print
            </a>
            <a href="<?= site_url('admin/reports/account_statement?' . http_build_query($filters) . '&export=excel') ?>" class="btn btn-success btn-sm">
                <i class="fas fa-download"></i> Export
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Date</th>
                        <th>Voucher</th>
                        <th>Particulars</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance -->
                    <tr class="table-secondary">
                        <td><?= format_date($filters['from_date']) ?></td>
                        <td>-</td>
                        <td><strong>Opening Balance</strong></td>
                        <td class="text-right"><?= ($statement['opening_balance'] ?? 0) > 0 ? number_format($statement['opening_balance'], 2) : '-' ?></td>
                        <td class="text-right"><?= ($statement['opening_balance'] ?? 0) < 0 ? number_format(abs($statement['opening_balance']), 2) : '-' ?></td>
                        <td class="text-right">
                            <strong><?= number_format(abs($statement['opening_balance'] ?? 0), 2) ?></strong>
                            <?= ($statement['opening_balance'] ?? 0) >= 0 ? 'Dr' : 'Cr' ?>
                        </td>
                    </tr>
                    
                    <?php 
                    $balance = $statement['opening_balance'] ?? 0;
                    foreach ($statement['entries'] ?? [] as $entry): 
                        $balance += ($entry->debit - $entry->credit);
                    ?>
                    <tr>
                        <td><?= format_date($entry->transaction_date) ?></td>
                        <td><small><?= $entry->voucher_number ?></small></td>
                        <td><?= $entry->narration ?></td>
                        <td class="text-right"><?= $entry->debit > 0 ? number_format($entry->debit, 2) : '-' ?></td>
                        <td class="text-right"><?= $entry->credit > 0 ? number_format($entry->credit, 2) : '-' ?></td>
                        <td class="text-right">
                            <?= number_format(abs($balance), 2) ?>
                            <?= $balance >= 0 ? 'Dr' : 'Cr' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <th colspan="3">Closing Balance</th>
                        <th class="text-right"><?= number_format($statement['total_debit'] ?? 0, 2) ?></th>
                        <th class="text-right"><?= number_format($statement['total_credit'] ?? 0, 2) ?></th>
                        <th class="text-right">
                            <?= number_format(abs($statement['closing_balance'] ?? 0), 2) ?>
                            <?= ($statement['closing_balance'] ?? 0) >= 0 ? 'Dr' : 'Cr' ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: 'Choose Account'
    });
});
</script>
