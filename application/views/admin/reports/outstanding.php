<!-- Outstanding Report -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Outstanding Report</h3>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form action="" method="get" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Report Type</label>
                        <select class="form-control" name="type" id="reportType">
                            <option value="loan" <?= ($filters['type'] ?? 'loan') == 'loan' ? 'selected' : '' ?>>Loan Outstanding</option>
                            <option value="savings" <?= ($filters['type'] ?? '') == 'savings' ? 'selected' : '' ?>>Savings Due</option>
                            <option value="fine" <?= ($filters['type'] ?? '') == 'fine' ? 'selected' : '' ?>>Pending Fines</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>As On Date</label>
                        <input type="date" class="form-control" name="as_on_date" value="<?= $filters['as_on_date'] ?? date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Aging Filter</label>
                        <select class="form-control" name="aging">
                            <option value="">All</option>
                            <option value="0-30" <?= ($filters['aging'] ?? '') == '0-30' ? 'selected' : '' ?>>0-30 Days</option>
                            <option value="31-60" <?= ($filters['aging'] ?? '') == '31-60' ? 'selected' : '' ?>>31-60 Days</option>
                            <option value="61-90" <?= ($filters['aging'] ?? '') == '61-90' ? 'selected' : '' ?>>61-90 Days</option>
                            <option value="90+" <?= ($filters['aging'] ?? '') == '90+' ? 'selected' : '' ?>>90+ Days (NPA)</option>
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
            <div class="col-md-3">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-rupee-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Outstanding</span>
                        <span class="info-box-number"><?= format_amount($summary['total_outstanding'] ?? 0, 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Overdue Amount</span>
                        <span class="info-box-number"><?= format_amount($summary['overdue_amount'] ?? 0, 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-secondary">
                    <span class="info-box-icon"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Affected Members</span>
                        <span class="info-box-number"><?= $summary['member_count'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-dark">
                    <span class="info-box-icon"><i class="fas fa-ban"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">NPA (90+ Days)</span>
                        <span class="info-box-number"><?= format_amount($summary['npa_amount'] ?? 0, 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Aging Analysis Chart -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Aging Analysis</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="agingChart" height="150"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Aging Summary</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Aging Bucket</th>
                                    <th class="text-right">Count</th>
                                    <th class="text-right">Amount</th>
                                    <th class="text-right">% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $aging_buckets = $aging_summary ?? [
                                    ['bucket' => '0-30 Days', 'count' => 0, 'amount' => 0],
                                    ['bucket' => '31-60 Days', 'count' => 0, 'amount' => 0],
                                    ['bucket' => '61-90 Days', 'count' => 0, 'amount' => 0],
                                    ['bucket' => '90+ Days', 'count' => 0, 'amount' => 0]
                                ];
                                $total = $summary['total_outstanding'] ?? 1;
                                foreach ($aging_buckets as $bucket): 
                                ?>
                                <tr>
                                    <td><?= $bucket['bucket'] ?></td>
                                    <td class="text-right"><?= $bucket['count'] ?></td>
                                    <td class="text-right"><?= format_amount($bucket['amount'], 0) ?></td>
                                    <td class="text-right"><?= round(($bucket['amount'] / max($total, 1)) * 100, 1) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Data Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm" id="outstandingTable">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Member</th>
                        <th>Account/Loan No</th>
                        <th>Type</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th class="text-right">Principal Due</th>
                        <th class="text-right">Interest Due</th>
                        <th class="text-right">Fine Due</th>
                        <th class="text-right">Total Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($outstanding_records)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-4">No outstanding records found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($outstanding_records as $key => $rec): ?>
                        <tr class="<?= $rec->days_overdue > 90 ? 'table-danger' : ($rec->days_overdue > 30 ? 'table-warning' : '') ?>">
                            <td><?= $key + 1 ?></td>
                            <td>
                                <a href="<?= site_url('admin/members/view/' . $rec->member_id) ?>">
                                    <?= $rec->member_name ?>
                                </a>
                                <br><small class="text-muted"><?= $rec->member_code ?></small>
                            </td>
                            <td><?= $rec->account_number ?></td>
                            <td><span class="badge badge-<?= $rec->type == 'loan' ? 'primary' : 'success' ?>"><?= ucfirst($rec->type) ?></span></td>
                            <td><?= format_date($rec->due_date) ?></td>
                            <td>
                                <span class="badge badge-<?= $rec->days_overdue > 90 ? 'danger' : ($rec->days_overdue > 30 ? 'warning' : 'secondary') ?>">
                                    <?= $rec->days_overdue ?> days
                                </span>
                            </td>
                            <td class="text-right"><?= format_amount($rec->principal_due ?? 0, 0) ?></td>
                            <td class="text-right"><?= format_amount($rec->interest_due ?? 0, 0) ?></td>
                            <td class="text-right"><?= format_amount($rec->fine_due ?? 0, 0) ?></td>
                            <td class="text-right font-weight-bold"><?= format_amount($rec->total_due, 0) ?></td>
                            <td>
                                <?php if ($rec->days_overdue > 90): ?>
                                    <span class="badge badge-danger">NPA</span>
                                <?php elseif ($rec->days_overdue > 0): ?>
                                    <span class="badge badge-warning">Overdue</span>
                                <?php else: ?>
                                    <span class="badge badge-info">Upcoming</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <th colspan="6" class="text-right">Total:</th>
                        <th class="text-right"><?= format_amount($summary['total_principal'] ?? 0, 0) ?></th>
                        <th class="text-right"><?= format_amount($summary['total_interest'] ?? 0, 0) ?></th>
                        <th class="text-right"><?= format_amount($summary['total_fine'] ?? 0, 0) ?></th>
                        <th class="text-right"><?= format_amount($summary['total_outstanding'] ?? 0, 0) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Aging Chart
    var agingCtx = document.getElementById('agingChart').getContext('2d');
    new Chart(agingCtx, {
        type: 'bar',
        data: {
            labels: ['0-30 Days', '31-60 Days', '61-90 Days', '90+ Days'],
            datasets: [{
                label: 'Outstanding Amount',
                data: <?= json_encode($aging_chart_data ?? [0, 0, 0, 0]) ?>,
                backgroundColor: ['#28a745', '#ffc107', '#fd7e14', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: { callback: function(v) { return '<?= get_currency_symbol() ?>' + v.toLocaleString(); } }
                }
            }
        }
    });
    
    // DataTable
    $('#outstandingTable').DataTable({
        pageLength: 25,
        order: [[5, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excel', title: 'Outstanding_Report_<?= date('Y-m-d') ?>' },
            { extend: 'pdf', title: 'Outstanding Report' },
            'print'
        ]
    });
});
</script>
