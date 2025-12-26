<!-- General Ledger -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-book mr-1"></i> General Ledger</h3>
    </div>
    <div class="card-body">
        <form method="get" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Account</label>
                        <select name="account_id" class="form-control select2">
                            <option value="">All Accounts</option>
                            <?php foreach ($accounts as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($filters['account_id'] ?? '') == $acc->id ? 'selected' : '' ?>>
                                <?= $acc->account_code ?> - <?= $acc->account_name ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Voucher Type</label>
                        <select name="voucher_type" class="form-control">
                            <option value="">All</option>
                            <option value="receipt" <?= ($filters['voucher_type'] ?? '') == 'receipt' ? 'selected' : '' ?>>Receipt</option>
                            <option value="payment" <?= ($filters['voucher_type'] ?? '') == 'payment' ? 'selected' : '' ?>>Payment</option>
                            <option value="journal" <?= ($filters['voucher_type'] ?? '') == 'journal' ? 'selected' : '' ?>>Journal</option>
                            <option value="contra" <?= ($filters['voucher_type'] ?? '') == 'contra' ? 'selected' : '' ?>>Contra</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?= $filters['from_date'] ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?= $filters['to_date'] ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                            <a href="<?= site_url('admin/reports/general_ledger') ?>" class="btn btn-secondary"><i class="fas fa-times"></i></a>
                            <a href="<?= site_url('admin/reports/general_ledger?' . http_build_query($filters) . '&export=excel') ?>" class="btn btn-success">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">Ledger Entries</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0" id="ledgerTable">
                <thead class="thead-dark">
                    <tr>
                        <th>Date</th>
                        <th>Voucher No.</th>
                        <th>Type</th>
                        <th>Account</th>
                        <th>Narration</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $running_balance = 0;
                    foreach ($entries as $entry): 
                        $running_balance += ($entry->debit - $entry->credit);
                    ?>
                    <tr>
                        <td><?= format_date($entry->transaction_date) ?></td>
                        <td>
                            <a href="<?= site_url('admin/accounts/voucher/' . $entry->voucher_id) ?>">
                                <?= $entry->voucher_number ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-<?= 
                                $entry->voucher_type == 'receipt' ? 'success' : 
                                ($entry->voucher_type == 'payment' ? 'danger' : 
                                ($entry->voucher_type == 'journal' ? 'info' : 'secondary')) ?>">
                                <?= ucfirst($entry->voucher_type) ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted"><?= $entry->account_code ?></small><br>
                            <?= $entry->account_name ?>
                        </td>
                        <td><small><?= $entry->narration ?></small></td>
                        <td class="text-right"><?= $entry->debit > 0 ? number_format($entry->debit, 2) : '-' ?></td>
                        <td class="text-right"><?= $entry->credit > 0 ? number_format($entry->credit, 2) : '-' ?></td>
                        <td class="text-right <?= $running_balance >= 0 ? 'text-success' : 'text-danger' ?>">
                            <strong><?= number_format(abs($running_balance), 2) ?></strong>
                            <?= $running_balance >= 0 ? 'Dr' : 'Cr' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <th colspan="5">Total</th>
                        <th class="text-right"><?= number_format(array_sum(array_column($entries, 'debit')), 2) ?></th>
                        <th class="text-right"><?= number_format(array_sum(array_column($entries, 'credit')), 2) ?></th>
                        <th class="text-right">
                            <?= number_format(abs($running_balance), 2) ?>
                            <?= $running_balance >= 0 ? 'Dr' : 'Cr' ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#ledgerTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 100,
        "paging": true
    });
    
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: 'Select Account'
    });
});
</script>
