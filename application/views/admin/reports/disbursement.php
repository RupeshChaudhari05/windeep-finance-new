<!-- Disbursement Report -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i> Disbursement Report</h3>
        <div class="card-tools">
            <a href="<?= site_url('admin/reports/disbursement?export=excel&from_date=' . $from_date . '&to_date=' . $to_date) ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <a href="<?= site_url('admin/reports/disbursement?export=pdf&from_date=' . $from_date . '&to_date=' . $to_date) ?>" class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Filter -->
        <form method="get" class="form-inline mb-4">
            <div class="form-group mr-3">
                <label class="mr-2">From:</label>
                <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
            </div>
            <div class="form-group mr-3">
                <label class="mr-2">To:</label>
                <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        </form>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-money-check-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Disbursed</span>
                        <span class="info-box-number"><?= number_format($report['total_amount'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-file-invoice"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Loans Disbursed</span>
                        <span class="info-box-number"><?= $report['count'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-calculator"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Avg Loan Size</span>
                        <span class="info-box-number"><?= number_format($report['average'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="fas fa-receipt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Processing Fees</span>
                        <span class="info-box-number"><?= number_format($report['fees'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Report Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="reportTable">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Loan No.</th>
                        <th>Member</th>
                        <th>Product</th>
                        <th>Principal</th>
                        <th>Interest Rate</th>
                        <th>Term</th>
                        <th>EMI</th>
                        <th>Processing Fee</th>
                        <th>Net Disbursed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($report['data'] ?? [] as $row): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= date('d M Y', strtotime($row->disbursement_date)) ?></td>
                        <td><a href="<?= site_url('admin/loans/view/' . $row->id) ?>"><?= $row->loan_number ?></a></td>
                        <td>
                            <a href="<?= site_url('admin/members/view/' . $row->member_id) ?>"><?= $row->member_name ?></a>
                        </td>
                        <td><?= $row->product_name ?></td>
                        <td><?= number_format($row->principal_amount, 2) ?></td>
                        <td><?= number_format($row->interest_rate, 2) ?>%</td>
                        <td><?= $row->loan_term ?> months</td>
                        <td><?= number_format($row->emi_amount, 2) ?></td>
                        <td><?= number_format($row->processing_fee, 2) ?></td>
                        <td><strong><?= number_format($row->net_disbursed, 2) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-dark">
                        <th colspan="5">Total</th>
                        <th><?= number_format($report['total_principal'] ?? 0, 2) ?></th>
                        <th>-</th>
                        <th>-</th>
                        <th><?= number_format($report['total_emi'] ?? 0, 2) ?></th>
                        <th><?= number_format($report['fees'] ?? 0, 2) ?></th>
                        <th><?= number_format($report['total_amount'] ?? 0, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Product-wise Summary -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Product-wise Summary</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <canvas id="productChart" height="200"></canvas>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Product</th>
                            <th>Count</th>
                            <th>Amount</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['by_product'] ?? [] as $product): ?>
                        <tr>
                            <td><?= $product->product_name ?></td>
                            <td><?= $product->count ?></td>
                            <td><?= number_format($product->amount, 2) ?></td>
                            <td><?= number_format(($product->amount / ($report['total_amount'] ?: 1)) * 100, 1) ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    $('#reportTable').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 50
    });
    
    // Product chart
    new Chart(document.getElementById('productChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($report['by_product'] ?? [], 'product_name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($report['by_product'] ?? [], 'amount')) ?>,
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
</script>
