<!-- NPA Report -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= count($report) ?></h3>
                <p>NPA Accounts</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= number_format(array_sum(array_column($report, 'outstanding_amount')), 2) ?></h3>
                <p>Total Outstanding</p>
            </div>
            <div class="icon"><i class="fas fa-rupee-sign"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= number_format(array_sum(array_column($report, 'overdue_amount')), 2) ?></h3>
                <p>Total Overdue</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3><?= $days ?>+</h3>
                <p>Days Criteria</p>
            </div>
            <div class="icon"><i class="fas fa-calendar-times"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-danger text-white">
        <h3 class="card-title"><i class="fas fa-exclamation-circle mr-1"></i> Non-Performing Assets (NPA) Report</h3>
        <div class="card-tools">
            <form class="form-inline" method="get">
                <select name="days" class="form-control form-control-sm mr-2">
                    <option value="30" <?= $days == 30 ? 'selected' : '' ?>>30+ Days</option>
                    <option value="60" <?= $days == 60 ? 'selected' : '' ?>>60+ Days</option>
                    <option value="90" <?= $days == 90 ? 'selected' : '' ?>>90+ Days</option>
                    <option value="180" <?= $days == 180 ? 'selected' : '' ?>>180+ Days</option>
                </select>
                <button type="submit" class="btn btn-light btn-sm"><i class="fas fa-filter"></i></button>
            </form>
            <a href="<?= site_url('admin/reports/npa?export=excel&days=' . $days) ?>" class="btn btn-light btn-sm ml-2">
                <i class="fas fa-download"></i> Export
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="npaTable">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Loan No.</th>
                        <th>Member</th>
                        <th>Product</th>
                        <th>Disbursed</th>
                        <th>Outstanding</th>
                        <th>Overdue</th>
                        <th>Days Overdue</th>
                        <th>Last Payment</th>
                        <th>NPA Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($report as $row): ?>
                    <?php 
                    $category = 'Standard';
                    $cat_class = 'secondary';
                    if ($row->days_overdue >= 180) { $category = 'Loss'; $cat_class = 'dark'; }
                    elseif ($row->days_overdue >= 90) { $category = 'Doubtful'; $cat_class = 'danger'; }
                    elseif ($row->days_overdue >= 60) { $category = 'Sub-Standard'; $cat_class = 'warning'; }
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <a href="<?= site_url('admin/loans/view/' . $row->id) ?>"><?= $row->loan_number ?></a>
                        </td>
                        <td>
                            <strong><?= $row->member_name ?></strong>
                            <br><small><?= $row->phone ?></small>
                        </td>
                        <td><?= $row->product_name ?></td>
                        <td><?= number_format($row->principal_amount, 2) ?></td>
                        <td><strong><?= number_format($row->outstanding_amount, 2) ?></strong></td>
                        <td class="text-danger"><strong><?= number_format($row->overdue_amount, 2) ?></strong></td>
                        <td>
                            <span class="badge badge-<?= $cat_class ?> badge-lg"><?= $row->days_overdue ?> days</span>
                        </td>
                        <td><?= $row->last_payment_date ? format_date($row->last_payment_date) : 'Never' ?></td>
                        <td>
                            <span class="badge badge-<?= $cat_class ?>"><?= $category ?></span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= site_url('admin/loans/view/' . $row->id) ?>" class="btn btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= site_url('admin/loans/collect/' . $row->id) ?>" class="btn btn-success" title="Collect">
                                    <i class="fas fa-money-bill"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- NPA Analysis -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-chart-bar mr-1"></i> NPA Category Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="categoryChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-table mr-1"></i> NPA Summary</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Category</th>
                            <th>Days Range</th>
                            <th>Accounts</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-warning">Sub-Standard</span></td>
                            <td>30-59 days</td>
                            <td><?= count(array_filter($report, function($r) { return $r->days_overdue >= 30 && $r->days_overdue < 60; })) ?></td>
                            <td><?= number_format(array_sum(array_map(function($r) { return ($r->days_overdue >= 30 && $r->days_overdue < 60) ? $r->outstanding_amount : 0; }, $report)), 2) ?></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-danger">Doubtful</span></td>
                            <td>60-89 days</td>
                            <td><?= count(array_filter($report, function($r) { return $r->days_overdue >= 60 && $r->days_overdue < 90; })) ?></td>
                            <td><?= number_format(array_sum(array_map(function($r) { return ($r->days_overdue >= 60 && $r->days_overdue < 90) ? $r->outstanding_amount : 0; }, $report)), 2) ?></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-danger">Doubtful</span></td>
                            <td>90-179 days</td>
                            <td><?= count(array_filter($report, function($r) { return $r->days_overdue >= 90 && $r->days_overdue < 180; })) ?></td>
                            <td><?= number_format(array_sum(array_map(function($r) { return ($r->days_overdue >= 90 && $r->days_overdue < 180) ? $r->outstanding_amount : 0; }, $report)), 2) ?></td>
                        </tr>
                        <tr class="table-dark">
                            <td><span class="badge badge-dark">Loss</span></td>
                            <td>180+ days</td>
                            <td><?= count(array_filter($report, function($r) { return $r->days_overdue >= 180; })) ?></td>
                            <td><?= number_format(array_sum(array_map(function($r) { return ($r->days_overdue >= 180) ? $r->outstanding_amount : 0; }, $report)), 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    $('#npaTable').DataTable({
        "order": [[7, "desc"]],
        "pageLength": 50
    });
    
    new Chart(document.getElementById('categoryChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Sub-Standard (30-59)', 'Doubtful (60-89)', 'Doubtful (90-179)', 'Loss (180+)'],
            datasets: [{
                label: 'Outstanding Amount',
                data: [
                    <?= array_sum(array_map(function($r) { return ($r->days_overdue >= 30 && $r->days_overdue < 60) ? $r->outstanding_amount : 0; }, $report)) ?>,
                    <?= array_sum(array_map(function($r) { return ($r->days_overdue >= 60 && $r->days_overdue < 90) ? $r->outstanding_amount : 0; }, $report)) ?>,
                    <?= array_sum(array_map(function($r) { return ($r->days_overdue >= 90 && $r->days_overdue < 180) ? $r->outstanding_amount : 0; }, $report)) ?>,
                    <?= array_sum(array_map(function($r) { return ($r->days_overdue >= 180) ? $r->outstanding_amount : 0; }, $report)) ?>
                ],
                backgroundColor: ['#ffc107', '#fd7e14', '#dc3545', '#343a40']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });
});
</script>
