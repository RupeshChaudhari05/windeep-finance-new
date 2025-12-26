<!-- Demand Report -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Monthly Demand Report</h3>
        <div class="card-tools">
            <form class="form-inline" method="get">
                <input type="month" name="month" class="form-control form-control-sm mr-2" value="<?= date('Y-m', safe_timestamp($month)) ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i></button>
            </form>
        </div>
    </div>
</div>

<!-- Summary -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Demand</span>
                <span class="info-box-number"><?= number_format($report['total_demand'] ?? 0, 2) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Collected</span>
                <span class="info-box-number"><?= number_format($report['collected'] ?? 0, 2) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending</span>
                <span class="info-box-number"><?= number_format($report['pending'] ?? 0, 2) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Collection %</span>
                <span class="info-box-number"><?= number_format((($report['collected'] ?? 0) / (($report['total_demand'] ?? 1) ?: 1)) * 100, 1) ?>%</span>
            </div>
        </div>
    </div>
</div>

<!-- Demand Breakdown -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-hand-holding-usd mr-1"></i> Loan EMI Demand</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Product</th>
                            <th>Accounts</th>
                            <th>Demand</th>
                            <th>Collected</th>
                            <th>Pending</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['loan_demand'] ?? [] as $item): ?>
                        <tr>
                            <td><?= $item->product_name ?></td>
                            <td><?= $item->accounts ?></td>
                            <td><?= number_format($item->demand, 2) ?></td>
                            <td class="text-success"><?= number_format($item->collected, 2) ?></td>
                            <td class="text-danger"><?= number_format($item->pending, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <th>Total</th>
                            <th><?= array_sum(array_column($report['loan_demand'] ?? [], 'accounts')) ?></th>
                            <th><?= number_format(array_sum(array_column($report['loan_demand'] ?? [], 'demand')), 2) ?></th>
                            <th><?= number_format(array_sum(array_column($report['loan_demand'] ?? [], 'collected')), 2) ?></th>
                            <th><?= number_format(array_sum(array_column($report['loan_demand'] ?? [], 'pending')), 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0"><i class="fas fa-piggy-bank mr-1"></i> Savings Demand</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Scheme</th>
                            <th>Accounts</th>
                            <th>Demand</th>
                            <th>Collected</th>
                            <th>Pending</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['savings_demand'] ?? [] as $item): ?>
                        <tr>
                            <td><?= $item->scheme_name ?></td>
                            <td><?= $item->accounts ?></td>
                            <td><?= number_format($item->demand, 2) ?></td>
                            <td class="text-success"><?= number_format($item->collected, 2) ?></td>
                            <td class="text-danger"><?= number_format($item->pending, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <th>Total</th>
                            <th><?= array_sum(array_column($report['savings_demand'] ?? [], 'accounts')) ?></th>
                            <th><?= number_format(array_sum(array_column($report['savings_demand'] ?? [], 'demand')), 2) ?></th>
                            <th><?= number_format(array_sum(array_column($report['savings_demand'] ?? [], 'collected')), 2) ?></th>
                            <th><?= number_format(array_sum(array_column($report['savings_demand'] ?? [], 'pending')), 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Daily Demand -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-day mr-1"></i> Daily Demand Schedule</h3>
        <div class="card-tools">
            <a href="<?= site_url('admin/reports/demand?export=excel&month=' . $month) ?>" class="btn btn-success btn-sm">
                <i class="fas fa-download"></i> Export
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0" id="dailyDemandTable">
                <thead class="thead-light">
                    <tr>
                        <th>Date</th>
                        <th>Loan EMI</th>
                        <th>Savings</th>
                        <th>Total</th>
                        <th>Collected</th>
                        <th>Pending</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['daily'] ?? [] as $day): ?>
                    <tr class="<?= safe_timestamp($day->date) < safe_timestamp('today') ? ($day->pending > 0 ? 'table-warning' : 'table-success') : '' ?>">
                        <td><?= format_date($day->date, 'd M (D)') ?></td>
                        <td><?= number_format($day->loan_demand, 2) ?></td>
                        <td><?= number_format($day->savings_demand, 2) ?></td>
                        <td><strong><?= number_format($day->total_demand, 2) ?></strong></td>
                        <td class="text-success"><?= number_format($day->collected, 2) ?></td>
                        <td class="text-danger"><?= number_format($day->pending, 2) ?></td>
                        <td>
                            <div class="progress" style="height: 15px;">
                                <div class="progress-bar bg-success" style="width: <?= ($day->collected / ($day->total_demand ?: 1)) * 100 ?>%">
                                    <?= number_format(($day->collected / ($day->total_demand ?: 1)) * 100, 0) ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#dailyDemandTable').DataTable({
        "paging": false,
        "searching": false,
        "ordering": false
    });
});
</script>
