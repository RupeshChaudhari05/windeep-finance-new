<!-- Dashboard Content -->
<div class="row">
    <!-- Quick Stats Cards -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= number_format($stats['total_members']) ?></h3>
                <p>Active Members</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="<?= site_url('admin/members') ?>" class="small-box-footer">
                View All <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>₹<?= number_format($stats['total_savings']) ?></h3>
                <p>Total Savings</p>
            </div>
            <div class="icon">
                <i class="fas fa-piggy-bank"></i>
            </div>
            <a href="<?= site_url('admin/savings') ?>" class="small-box-footer">
                View Details <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>₹<?= number_format($stats['total_outstanding']) ?></h3>
                <p>Loan Outstanding</p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <a href="<?= site_url('admin/loans') ?>" class="small-box-footer">
                View Loans <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>₹<?= number_format($stats['overdue_amount']) ?></h3>
                <p>Overdue Amount</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <a href="<?= site_url('admin/loans/overdue') ?>" class="small-box-footer">
                View Overdue <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Second Row Stats -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-file-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Applications</span>
                <span class="info-box-number"><?= $stats['pending_applications'] ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-rupee-sign"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">This Month Collection</span>
                <span class="info-box-number">₹<?= number_format($monthly_summary['loans_collected'] + $monthly_summary['savings_collected']) ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-money-check-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">This Month Disbursed</span>
                <span class="info-box-number">₹<?= number_format($monthly_summary['loans_disbursed_amount']) ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-gavel"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Fines</span>
                <span class="info-box-number">₹<?= number_format($stats['pending_fines']) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Monthly Trend Chart -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line mr-1"></i>
                    Monthly Trend (<?= date('Y') ?>)
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="trendChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt mr-1"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a href="<?= site_url('admin/members/create') ?>" class="nav-link">
                            <i class="fas fa-user-plus mr-2"></i> Add New Member
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= site_url('admin/loans/apply') ?>" class="nav-link">
                            <i class="fas fa-file-invoice-dollar mr-2"></i> New Loan Application
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= site_url('admin/savings/create') ?>" class="nav-link">
                            <i class="fas fa-piggy-bank mr-2"></i> Open Savings Account
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= site_url('admin/loans/collect') ?>" class="nav-link">
                            <i class="fas fa-hand-holding-usd mr-2"></i> Collect EMI
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= site_url('admin/savings/collect') ?>" class="nav-link">
                            <i class="fas fa-rupee-sign mr-2"></i> Collect Savings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= site_url('admin/reports') ?>" class="nav-link">
                            <i class="fas fa-chart-bar mr-2"></i> View Reports
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Today's Summary -->
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title">
                    <i class="fas fa-calendar-day mr-1"></i>
                    Today's Summary
                </h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <span>New Members:</span>
                    <strong><?= $monthly_summary['new_members'] ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Loans Disbursed:</span>
                    <strong><?= $monthly_summary['loans_disbursed_count'] ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>EMIs Due Today:</span>
                    <strong><?= count($due_today) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Pending Applications -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title">
                    <i class="fas fa-clock mr-1"></i>
                    Pending Loan Applications
                </h3>
                <div class="card-tools">
                    <span class="badge badge-light"><?= count($pending_applications) ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pending_applications)): ?>
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <p class="mb-0">No pending applications</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>App No.</th>
                                    <th>Member</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($pending_applications, 0, 5) as $app): ?>
                                <tr>
                                    <td><?= $app->application_number ?></td>
                                    <td>
                                        <small><?= $app->member_code ?></small><br>
                                        <?= $app->first_name ?>
                                    </td>
                                    <td>₹<?= number_format($app->requested_amount) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $app->status == 'pending' ? 'warning' : 'info' ?>">
                                            <?= ucfirst(str_replace('_', ' ', $app->status)) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('admin/loans/view_application/' . $app->id) ?>" class="btn btn-xs btn-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (count($pending_applications) > 5): ?>
            <div class="card-footer text-center">
                <a href="<?= site_url('admin/loans/applications') ?>">View All (<?= count($pending_applications) ?>)</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Overdue Loans -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-danger">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Overdue EMIs
                </h3>
                <div class="card-tools">
                    <span class="badge badge-light"><?= count($overdue_loans) ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($overdue_loans)): ?>
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <p class="mb-0">No overdue loans</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Loan No.</th>
                                    <th>Member</th>
                                    <th>Due Date</th>
                                    <th>EMI</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($overdue_loans, 0, 5) as $loan): ?>
                                <tr>
                                    <td><?= $loan->loan_number ?></td>
                                    <td>
                                        <small><?= $loan->member_code ?></small><br>
                                        <?= $loan->first_name ?>
                                    </td>
                                    <td>
                                        <span class="text-danger"><?= date('d M', strtotime($loan->due_date)) ?></span>
                                    </td>
                                    <td>₹<?= number_format($loan->emi_amount) ?></td>
                                    <td>
                                        <a href="<?= site_url('admin/loans/collect/' . $loan->id) ?>" class="btn btn-xs btn-success" title="Collect">
                                            <i class="fas fa-rupee-sign"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (count($overdue_loans) > 5): ?>
            <div class="card-footer text-center">
                <a href="<?= site_url('admin/loans/overdue') ?>">View All (<?= count($overdue_loans) ?>)</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-1"></i>
                    Recent Activities
                </h3>
            </div>
            <div class="card-body">
                <div class="timeline timeline-inverse">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div>
                        <i class="fas fa-circle bg-primary"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="far fa-clock"></i> <?= date('h:i A', strtotime($activity->created_at)) ?></span>
                            <h3 class="timeline-header"><?= htmlspecialchars($activity->activity) ?></h3>
                            <?php if ($activity->details): ?>
                            <div class="timeline-body">
                                <?= htmlspecialchars($activity->details) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div>
                        <i class="far fa-clock bg-gray"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Trend Chart
    var ctx = document.getElementById('trendChart').getContext('2d');
    var trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($monthly_trend['labels']) ?>,
            datasets: [
                {
                    label: 'Savings Collection',
                    data: <?= json_encode($monthly_trend['savings']) ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Loan Disbursement',
                    data: <?= json_encode($monthly_trend['loans_disbursed']) ?>,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'EMI Collection',
                    data: <?= json_encode($monthly_trend['loans_collected']) ?>,
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + (value / 1000).toFixed(0) + 'K';
                        }
                    }
                }
            }
        }
    });
});
</script>
