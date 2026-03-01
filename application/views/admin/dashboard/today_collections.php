<!-- Today's Collections Dashboard -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-money-bill"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Collected</span>
                <span class="info-box-number"><?= number_format($totals['collected'] ?? 0, 2) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Loan EMI</span>
                <span class="info-box-number"><?= number_format($totals['loan_emi'] ?? 0, 2) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-piggy-bank"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Security Deposit</span>
                <span class="info-box-number"><?= number_format($totals['savings'] ?? 0, 2) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-receipt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Transactions</span>
                <span class="info-box-number"><?= $totals['count'] ?? 0 ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Collection List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-1"></i> Today's Collections - <?= format_date(date('Y-m-d')) ?></h3>
        <div class="card-tools">
            <a href="<?= site_url('admin/dashboard/today_collections?export=excel') ?>" class="btn btn-success btn-sm">
                <i class="fas fa-download"></i> Export
            </a>
            <a href="<?= site_url('admin/dashboard/today_collections?print=1') ?>" class="btn btn-secondary btn-sm" target="_blank">
                <i class="fas fa-print"></i> Print
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="collectionsTable">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Time</th>
                        <th>Receipt No.</th>
                        <th>Member</th>
                        <th>Type</th>
                        <th>Account</th>
                        <th>Amount</th>
                        <th>Payment Mode</th>
                        <th>Collected By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($collections as $c): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= format_date_time($c->created_at, 'h:i A') ?></td>
                        <td><code><?= $c->receipt_number ?></code></td>
                        <td>
                            <a href="<?= site_url('admin/members/view/' . $c->member_id) ?>">
                                <?= $c->member_name ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-<?= $c->type == 'loan' ? 'primary' : ($c->type == 'savings' ? 'success' : 'warning') ?>">
                                <?= ucfirst($c->type) ?>
                            </span>
                        </td>
                        <td><?= $c->account_number ?></td>
                        <td><strong class="text-success"><?= number_format($c->amount, 2) ?></strong></td>
                        <td>
                            <span class="badge badge-secondary"><?= ucfirst($c->payment_mode ?? 'Cash') ?></span>
                        </td>
                        <td><?= $c->collected_by ?? 'Admin' ?></td>
                        <td>
                            <a href="<?= site_url('admin/receipts/view/' . $c->id) ?>" class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?= site_url('admin/receipts/print/' . $c->id) ?>" class="btn btn-sm btn-secondary" target="_blank" title="Print">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-success">
                    <tr>
                        <th colspan="6">Total</th>
                        <th><?= number_format($totals['collected'] ?? 0, 2) ?></th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Hourly Chart -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-chart-bar mr-1"></i> Hourly Collection Trend</h5>
    </div>
    <div class="card-body">
        <canvas id="hourlyChart" height="100"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    $('#collectionsTable').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 50
    });
    
    new Chart(document.getElementById('hourlyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($hourly_data ?? [])) ?>,
            datasets: [{
                label: 'Amount Collected',
                data: <?= json_encode(array_values($hourly_data ?? [])) ?>,
                backgroundColor: '#28a745'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });
});
</script>
