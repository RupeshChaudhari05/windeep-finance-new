<!-- Profit & Loss Statement -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Profit & Loss Statement</h3>
        <div class="card-tools">
            <form class="form-inline" method="get">
                <label class="mr-2">From:</label>
                <input type="date" name="from_date" class="form-control form-control-sm mr-2" value="<?= $from_date ?>">
                <label class="mr-2">To:</label>
                <input type="date" name="to_date" class="form-control form-control-sm mr-2" value="<?= $to_date ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync"></i></button>
            </form>
            <a href="<?= site_url('admin/reports/profit_loss?export=pdf&from_date=' . $from_date . '&to_date=' . $to_date) ?>" class="btn btn-danger btn-sm ml-2">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white text-center">
        <h4 class="mb-0">Profit & Loss Statement</h4>
        <small>For the period <?= format_date($from_date) ?> to <?= format_date($to_date) ?></small>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Income -->
            <div class="col-md-6">
                <div class="card card-outline card-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-arrow-down mr-1"></i> Income</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php $total_income = 0; ?>
                                <?php foreach ($report['income'] ?? [] as $item): ?>
                                <?php $total_income += $item->amount; ?>
                                <tr>
                                    <td><?= $item->account_name ?></td>
                                    <td class="text-right"><?= number_format($item->amount, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-success">
                                <tr>
                                    <th>Total Income</th>
                                    <th class="text-right"><?= number_format($total_income, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Expenses -->
            <div class="col-md-6">
                <div class="card card-outline card-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-arrow-up mr-1"></i> Expenses</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php $total_expense = 0; ?>
                                <?php foreach ($report['expenses'] ?? [] as $item): ?>
                                <?php $total_expense += $item->amount; ?>
                                <tr>
                                    <td><?= $item->account_name ?></td>
                                    <td class="text-right"><?= number_format($item->amount, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-danger">
                                <tr>
                                    <th>Total Expenses</th>
                                    <th class="text-right"><?= number_format($total_expense, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Net Profit/Loss -->
        <?php $net = $total_income - $total_expense; ?>
        <div class="card mt-4 card-outline <?= $net >= 0 ? 'card-success' : 'card-danger' ?>">
            <div class="card-body text-center py-4">
                <h3 class="mb-1"><?= $net >= 0 ? 'Net Profit' : 'Net Loss' ?></h3>
                <h1 class="text-<?= $net >= 0 ? 'success' : 'danger' ?> mb-0">
                    <i class="fas fa-rupee-sign"></i> <?= number_format(abs($net), 2) ?>
                </h1>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-chart-pie mr-1"></i> Income Breakdown</h5>
            </div>
            <div class="card-body">
                <canvas id="incomeChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-chart-pie mr-1"></i> Expense Breakdown</h5>
            </div>
            <div class="card-body">
                <canvas id="expenseChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Income chart
    new Chart(document.getElementById('incomeChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($report['income'] ?? [], 'account_name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($report['income'] ?? [], 'amount')) ?>,
                backgroundColor: ['#28a745', '#20c997', '#17a2b8', '#6610f2', '#e83e8c']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
    
    // Expense chart
    new Chart(document.getElementById('expenseChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($report['expenses'] ?? [], 'account_name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($report['expenses'] ?? [], 'amount')) ?>,
                backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#6c757d', '#343a40']
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
});
</script>
