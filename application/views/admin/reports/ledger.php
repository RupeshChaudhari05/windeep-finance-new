<!-- Ledger Report (Day Book) -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-book mr-1"></i> Ledger / Day Book</h3>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form action="" method="get" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date Range</label>
                        <input type="text" class="form-control" id="daterange" name="daterange" 
                               value="<?= $filters['start_date'] ?? date('Y-m-d') ?> - <?= $filters['end_date'] ?? date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Account Head</label>
                        <select class="form-control select2" name="account_id">
                            <option value="">All Accounts</option>
                            <?php foreach ($chart_of_accounts ?? [] as $acc): ?>
                            <option value="<?= $acc->id ?>" <?= ($filters['account_id'] ?? '') == $acc->id ? 'selected' : '' ?>>
                                <?= $acc->account_code ?> - <?= $acc->account_name ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Transaction Type</label>
                        <select class="form-control" name="entry_type">
                            <option value="">All Types</option>
                            <option value="debit" <?= ($filters['entry_type'] ?? '') == 'debit' ? 'selected' : '' ?>>Debit</option>
                            <option value="credit" <?= ($filters['entry_type'] ?? '') == 'credit' ? 'selected' : '' ?>>Credit</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                            <a href="<?= current_url() ?>?export=excel&<?= http_build_query($filters ?? []) ?>" class="btn btn-success">
                                <i class="fas fa-download"></i> Export
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="fas fa-arrow-circle-left"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Debit</span>
                        <span class="info-box-number">₹<?= number_format($summary['total_debit'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-arrow-circle-right"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Credit</span>
                        <span class="info-box-number">₹<?= number_format($summary['total_credit'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-<?= ($summary['total_debit'] ?? 0) >= ($summary['total_credit'] ?? 0) ? 'warning' : 'info' ?>">
                    <span class="info-box-icon"><i class="fas fa-balance-scale"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Net Balance</span>
                        <span class="info-box-number">
                            ₹<?= number_format(abs(($summary['total_debit'] ?? 0) - ($summary['total_credit'] ?? 0))) ?>
                            <?= ($summary['total_debit'] ?? 0) >= ($summary['total_credit'] ?? 0) ? 'Dr' : 'Cr' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ledger Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm" id="ledgerTable">
                <thead class="thead-dark">
                    <tr>
                        <th>Date</th>
                        <th>Voucher No</th>
                        <th>Account Head</th>
                        <th>Particulars</th>
                        <th>Reference</th>
                        <th class="text-right">Debit (₹)</th>
                        <th class="text-right">Credit (₹)</th>
                        <th class="text-right">Balance (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($filters['account_id'])): ?>
                    <tr class="table-info">
                        <td colspan="5"><strong>Opening Balance</strong></td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right"><strong>₹<?= number_format($opening_balance ?? 0) ?></strong></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (empty($ledger_entries)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">No ledger entries found for the selected period</td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $running_balance = $opening_balance ?? 0;
                        foreach ($ledger_entries as $entry): 
                            if ($entry->entry_type == 'debit') {
                                $running_balance += $entry->amount;
                            } else {
                                $running_balance -= $entry->amount;
                            }
                        ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($entry->transaction_date)) ?></td>
                            <td><small><?= $entry->voucher_number ?></small></td>
                            <td>
                                <small class="text-muted"><?= $entry->account_code ?></small><br>
                                <?= $entry->account_name ?>
                            </td>
                            <td><?= $entry->narration ?></td>
                            <td><small><?= $entry->reference_type ? ucfirst($entry->reference_type) . ' #' . $entry->reference_id : '-' ?></small></td>
                            <td class="text-right text-primary">
                                <?= $entry->entry_type == 'debit' ? '₹' . number_format($entry->amount) : '-' ?>
                            </td>
                            <td class="text-right text-success">
                                <?= $entry->entry_type == 'credit' ? '₹' . number_format($entry->amount) : '-' ?>
                            </td>
                            <td class="text-right">
                                ₹<?= number_format(abs($running_balance)) ?>
                                <?= $running_balance >= 0 ? 'Dr' : 'Cr' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <th colspan="5" class="text-right">Total:</th>
                        <th class="text-right">₹<?= number_format($summary['total_debit'] ?? 0) ?></th>
                        <th class="text-right">₹<?= number_format($summary['total_credit'] ?? 0) ?></th>
                        <th class="text-right">
                            ₹<?= number_format(abs(($summary['total_debit'] ?? 0) - ($summary['total_credit'] ?? 0))) ?>
                            <?= ($summary['total_debit'] ?? 0) >= ($summary['total_credit'] ?? 0) ? 'Dr' : 'Cr' ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
    
    $('#daterange').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        opens: 'right'
    });
    
    $('#ledgerTable').DataTable({
        pageLength: 50,
        order: [[0, 'asc']],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excel', title: 'Ledger_Report_<?= date('Y-m-d') ?>' },
            { extend: 'pdf', title: 'Ledger Report', orientation: 'landscape' },
            'print'
        ]
    });
});
</script>
