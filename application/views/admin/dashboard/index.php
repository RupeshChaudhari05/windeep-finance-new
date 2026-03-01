<!-- Notification Summary Bar -->
<?php if (!empty($unread_notifications_count) && $unread_notifications_count > 0): ?>
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info alert-dismissible fade show mb-0" role="alert">
            <i class="fas fa-bell mr-2"></i>
            You have <strong><?= $unread_notifications_count ?></strong> unread notification<?= $unread_notifications_count > 1 ? 's' : '' ?>.
            <a href="<?= site_url('admin/notifications') ?>" class="alert-link ml-2">View All</a>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Dashboard Content -->
<div class="row">
    <!-- Quick Stats Cards -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info dashboard-card" data-card="members" style="cursor:pointer" data-toggle="tooltip" data-placement="top" title="Click to view active members detail">
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
        <div class="small-box bg-success dashboard-card" data-card="savings" style="cursor:pointer" data-toggle="tooltip" data-placement="top" title="Click to view savings detail">
            <div class="inner">
                <h3><?= format_amount($stats['total_savings'], 0) ?></h3>
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
        <div class="small-box bg-warning dashboard-card" data-card="loans" style="cursor:pointer" data-toggle="tooltip" data-placement="top" title="Click to view loan outstanding detail">
            <div class="inner">
                <h3><?= format_amount($stats['total_outstanding'], 0) ?></h3>
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
        <div class="small-box bg-danger dashboard-card" data-card="overdue" style="cursor:pointer" data-toggle="tooltip" data-placement="top" title="Click to view overdue EMIs detail">
            <div class="inner">
                <h3><?= format_amount($stats['overdue_amount'], 0) ?></h3>
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
        <div class="info-box dashboard-card" data-card="applications" style="cursor:pointer" data-toggle="tooltip" title="Click to view pending applications">
            <span class="info-box-icon bg-primary"><i class="fas fa-file-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Applications</span>
                <span class="info-box-number"><?= $stats['pending_applications'] ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="info-box dashboard-card" data-card="collection" style="cursor:pointer" data-toggle="tooltip" title="Click to view monthly collection detail">
            <span class="info-box-icon bg-success"><i class="fas fa-rupee-sign"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">This Month Collection</span>
                <span class="info-box-number"><?= format_amount($monthly_summary['loans_collected'] + $monthly_summary['savings_collected'], 0) ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="info-box dashboard-card" data-card="disbursed" style="cursor:pointer" data-toggle="tooltip" title="Click to view disbursement detail">
            <span class="info-box-icon bg-warning"><i class="fas fa-money-check-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">This Month Disbursed</span>
                <span class="info-box-number"><?= format_amount($monthly_summary['loans_disbursed_amount'], 0) ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="info-box dashboard-card" data-card="fines" style="cursor:pointer" data-toggle="tooltip" title="Click to view pending fines detail">
            <span class="info-box-icon bg-danger"><i class="fas fa-gavel"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Fines</span>
                <span class="info-box-number"><?= format_amount($stats['pending_fines'], 0) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Fee Summary Row -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="info-box dashboard-card" data-card="fees" style="cursor:pointer" data-toggle="tooltip" title="Click to view membership fee detail">
            <span class="info-box-icon bg-teal"><i class="fas fa-id-badge"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Member Fee</span>
                <span class="info-box-number"><?= format_amount($fee_summary['membership_fee'] ?? 0, 0) ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box dashboard-card" data-card="fees" style="cursor:pointer" data-toggle="tooltip" title="Click to view other fees detail">
            <span class="info-box-icon bg-purple"><i class="fas fa-receipt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Other Members Fee</span>
                <span class="info-box-number"><?= format_amount($fee_summary['other_member_fee'] ?? 0, 0) ?></span>
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
                                    <td><?= format_amount($app->requested_amount, 0) ?></td>
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
                                        <span class="text-danger"><?= format_date($loan->due_date, 'd M') ?></span>
                                    </td>
                                    <td><?= format_amount($loan->emi_amount, 0) ?></td>
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
                            <span class="time"><i class="far fa-clock"></i> <?= format_date_time($activity->created_at, 'h:i A') ?></span>
                            <h3 class="timeline-header"><?= htmlspecialchars($activity->activity) ?></h3>
                            <?php if ($activity->description): ?>
                            <div class="timeline-body">
                                <?= htmlspecialchars($activity->description) ?>
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

<!-- Dashboard Detail Modal -->
<div class="modal fade" id="dashboardDetailModal" tabindex="-1" role="dialog" aria-labelledby="dashboardDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dashboardDetailModalLabel"><i class="fas fa-info-circle mr-2"></i>Detail</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="dashboardDetailBody">
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-3 text-muted">Loading details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ─── Dashboard Card Click → Modal ───
    $(document).on('click', '.dashboard-card .inner, .dashboard-card .icon, .dashboard-card .info-box-content, .dashboard-card .info-box-icon', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var card = $(this).closest('.dashboard-card').data('card');
        if (!card) return;
        openCardModal(card);
    });
    
    function openCardModal(card) {
        var $modal = $('#dashboardDetailModal');
        var $body = $('#dashboardDetailBody');
        var $title = $('#dashboardDetailModalLabel');
        
        $body.html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-3 text-muted">Loading details...</p></div>');
        $modal.modal('show');
        
        var baseUrl = '<?= site_url("admin/dashboard/card_") ?>';
        
        $.getJSON(baseUrl + card, function(res) {
            var html = '';
            switch(card) {
                case 'members':
                    $title.html('<i class="fas fa-users mr-2 text-info"></i>Active Members (' + res.total + ')');
                    html = '<div class="table-responsive"><table class="table table-hover table-sm"><thead class="thead-light"><tr><th>Code</th><th>Name</th><th>Phone</th><th>Joined</th><th></th></tr></thead><tbody>';
                    $.each(res.data, function(i, m) {
                        html += '<tr><td><span class="badge badge-primary">' + m.member_code + '</span></td><td>' + m.first_name + ' ' + m.last_name + '</td><td>' + (m.phone || '-') + '</td><td>' + formatDate(m.created_at) + '</td><td><a href="<?= site_url("admin/members/view/") ?>' + m.id + '" class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i></a></td></tr>';
                    });
                    html += '</tbody></table></div>';
                    break;
                    
                case 'savings':
                    $title.html('<i class="fas fa-piggy-bank mr-2 text-success"></i>Total Savings Overview');
                    html = '<div class="row mb-3"><div class="col-md-4"><div class="callout callout-success"><h5>' + formatCurrency(res.totals.total_balance) + '</h5><small>Total Balance</small></div></div><div class="col-md-4"><div class="callout callout-info"><h5>' + formatCurrency(res.totals.total_deposited) + '</h5><small>Total Deposited</small></div></div><div class="col-md-4"><div class="callout callout-primary"><h5>' + res.totals.total_accounts + '</h5><small>Total Accounts</small></div></div></div>';
                    html += '<div class="table-responsive"><table class="table table-hover table-sm"><thead class="thead-light"><tr><th>Account</th><th>Member</th><th>Balance</th><th>Deposited</th><th></th></tr></thead><tbody>';
                    $.each(res.data, function(i, s) {
                        html += '<tr><td><code>' + s.account_number + '</code></td><td>' + s.member_code + ' - ' + s.first_name + ' ' + s.last_name + '</td><td class="text-right font-weight-bold text-success">' + formatCurrency(s.current_balance) + '</td><td class="text-right">' + formatCurrency(s.total_deposited) + '</td><td><a href="<?= site_url("admin/savings/view/") ?>' + s.id + '" class="btn btn-xs btn-outline-success"><i class="fas fa-eye"></i></a></td></tr>';
                    });
                    html += '</tbody></table></div>';
                    break;
                    
                case 'loans':
                    $title.html('<i class="fas fa-hand-holding-usd mr-2 text-warning"></i>Loan Outstanding Detail');
                    html = '<div class="row mb-3"><div class="col-md-3"><div class="callout callout-warning"><h5>' + formatCurrency(res.totals.total_principal) + '</h5><small>Outstanding Principal</small></div></div><div class="col-md-3"><div class="callout callout-info"><h5>' + formatCurrency(res.totals.total_interest) + '</h5><small>Outstanding Interest</small></div></div><div class="col-md-3"><div class="callout callout-success"><h5>' + formatCurrency(res.totals.total_disbursed) + '</h5><small>Total Disbursed</small></div></div><div class="col-md-3"><div class="callout callout-primary"><h5>' + res.totals.total_loans + '</h5><small>Active Loans</small></div></div></div>';
                    html += '<div class="table-responsive"><table class="table table-hover table-sm"><thead class="thead-light"><tr><th>Loan No.</th><th>Member</th><th>Product</th><th>Disbursed</th><th>Outstanding</th><th>EMI</th><th></th></tr></thead><tbody>';
                    $.each(res.data, function(i, l) {
                        html += '<tr><td><code>' + l.loan_number + '</code></td><td>' + l.member_code + ' - ' + l.first_name + ' ' + l.last_name + '</td><td>' + (l.product_name || '-') + '</td><td class="text-right">' + formatCurrency(l.principal_amount) + '</td><td class="text-right font-weight-bold text-danger">' + formatCurrency(parseFloat(l.outstanding_principal) + parseFloat(l.outstanding_interest)) + '</td><td class="text-right">' + formatCurrency(l.emi_amount) + '</td><td><a href="<?= site_url("admin/loans/view/") ?>' + l.id + '" class="btn btn-xs btn-outline-warning"><i class="fas fa-eye"></i></a></td></tr>';
                    });
                    html += '</tbody></table></div>';
                    break;
                    
                case 'overdue':
                    $title.html('<i class="fas fa-exclamation-triangle mr-2 text-danger"></i>Overdue EMIs Detail');
                    html = '<div class="table-responsive"><table class="table table-hover table-sm"><thead class="thead-light"><tr><th>Loan No.</th><th>Member</th><th>Phone</th><th>EMI #</th><th>Due Date</th><th>EMI Amt</th><th>Paid</th><th>Overdue</th><th></th></tr></thead><tbody>';
                    $.each(res.data, function(i, o) {
                        var days = Math.floor((new Date() - new Date(o.due_date)) / 86400000);
                        html += '<tr><td><code>' + o.loan_number + '</code></td><td>' + o.member_code + ' - ' + o.first_name + '</td><td>' + (o.phone || '-') + '</td><td>' + o.installment_number + '</td><td><span class="text-danger">' + formatDate(o.due_date) + '</span> <small class="badge badge-danger">' + days + 'd</small></td><td class="text-right">' + formatCurrency(o.emi_amount) + '</td><td class="text-right">' + formatCurrency(o.total_paid) + '</td><td class="text-right font-weight-bold text-danger">' + formatCurrency(o.overdue_amount) + '</td><td><a href="<?= site_url("admin/loans/collect/") ?>' + o.loan_id + '" class="btn btn-xs btn-outline-success" title="Collect"><i class="fas fa-rupee-sign"></i></a></td></tr>';
                    });
                    html += '</tbody></table></div>';
                    break;
                    
                case 'applications':
                    $title.html('<i class="fas fa-file-alt mr-2 text-primary"></i>Pending Loan Applications');
                    html = '<div class="table-responsive"><table class="table table-hover table-sm"><thead class="thead-light"><tr><th>App No.</th><th>Member</th><th>Amount</th><th>Purpose</th><th>Status</th><th>Applied On</th><th></th></tr></thead><tbody>';
                    $.each(res.data, function(i, a) {
                        var statusBadge = a.status === 'pending' ? 'warning' : (a.status === 'under_review' ? 'info' : 'secondary');
                        html += '<tr><td><code>' + a.application_number + '</code></td><td>' + a.member_code + ' - ' + a.first_name + ' ' + a.last_name + '</td><td class="text-right">' + formatCurrency(a.requested_amount) + '</td><td>' + (a.loan_purpose || '-') + '</td><td><span class="badge badge-' + statusBadge + '">' + a.status.replace('_', ' ') + '</span></td><td>' + formatDate(a.created_at) + '</td><td><a href="<?= site_url("admin/loans/view_application/") ?>' + a.id + '" class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i></a></td></tr>';
                    });
                    html += '</tbody></table></div>';
                    break;
                    
                case 'collection':
                    $title.html('<i class="fas fa-rupee-sign mr-2 text-success"></i>This Month\'s Collection');
                    html = '<h6 class="text-muted"><i class="fas fa-hand-holding-usd mr-1"></i>Loan EMI Collections</h6>';
                    html += '<div class="table-responsive"><table class="table table-hover table-sm mb-4"><thead class="thead-light"><tr><th>Date</th><th>Loan No.</th><th>Member</th><th>Principal</th><th>Interest</th><th>Total</th></tr></thead><tbody>';
                    $.each(res.loan_collections, function(i, c) {
                        html += '<tr><td>' + formatDate(c.payment_date) + '</td><td><code>' + c.loan_number + '</code></td><td>' + c.member_code + ' - ' + c.first_name + '</td><td class="text-right">' + formatCurrency(c.principal_component) + '</td><td class="text-right">' + formatCurrency(c.interest_component) + '</td><td class="text-right font-weight-bold">' + formatCurrency(c.total_amount) + '</td></tr>';
                    });
                    html += '</tbody></table></div>';
                    html += '<h6 class="text-muted"><i class="fas fa-piggy-bank mr-1"></i>Savings Deposits</h6>';
                    html += '<div class="table-responsive"><table class="table table-hover table-sm"><thead class="thead-light"><tr><th>Date</th><th>Account</th><th>Member</th><th>Amount</th></tr></thead><tbody>';
                    $.each(res.savings_collections, function(i, s) {
                        html += '<tr><td>' + formatDate(s.transaction_date) + '</td><td><code>' + s.account_number + '</code></td><td>' + s.member_code + ' - ' + s.first_name + '</td><td class="text-right font-weight-bold text-success">' + formatCurrency(s.amount) + '</td></tr>';
                    });
                    html += '</tbody></table></div>';
                    break;
                    
                case 'disbursed':
                    $title.html('<i class="fas fa-money-check-alt mr-2 text-warning"></i>This Month\'s Disbursements');
                    html = '<div class="table-responsive"><table class="table table-hover table-sm"><thead class="thead-light"><tr><th>Loan No.</th><th>Member</th><th>Product</th><th>Amount</th><th>EMI</th><th>Tenure</th><th>Date</th><th></th></tr></thead><tbody>';
                    $.each(res.data, function(i, d) {
                        html += '<tr><td><code>' + d.loan_number + '</code></td><td>' + d.member_code + ' - ' + d.first_name + ' ' + d.last_name + '</td><td>' + (d.product_name || '-') + '</td><td class="text-right font-weight-bold">' + formatCurrency(d.principal_amount) + '</td><td class="text-right">' + formatCurrency(d.emi_amount) + '</td><td>' + d.tenure_months + ' mo</td><td>' + formatDate(d.disbursement_date) + '</td><td><a href="<?= site_url("admin/loans/view/") ?>' + d.id + '" class="btn btn-xs btn-outline-warning"><i class="fas fa-eye"></i></a></td></tr>';
                    });
                    html += '</tbody></table></div>';
                    break;
                    
                case 'fines':
                    $title.html('<i class="fas fa-gavel mr-2 text-danger"></i>Pending Fines (Total: ' + formatCurrency(res.total) + ')');
                    html = '<div class="table-responsive"><table class="table table-hover table-sm"><thead class="thead-light"><tr><th>Fine Code</th><th>Member</th><th>Loan</th><th>Amount</th><th>Balance</th><th>Reason</th><th>Date</th><th>Status</th></tr></thead><tbody>';
                    $.each(res.data, function(i, f) {
                        var sBadge = f.status === 'pending' ? 'danger' : 'warning';
                        html += '<tr><td><code>' + f.fine_code + '</code></td><td>' + f.member_code + ' - ' + f.first_name + '</td><td>' + (f.loan_number || '-') + '</td><td class="text-right">' + formatCurrency(f.fine_amount) + '</td><td class="text-right font-weight-bold text-danger">' + formatCurrency(f.balance_amount) + '</td><td>' + (f.reason || '-') + '</td><td>' + formatDate(f.fine_date) + '</td><td><span class="badge badge-' + sBadge + '">' + f.status + '</span></td></tr>';
                    });
                    html += '</tbody></table></div>';
                    break;
                    
                case 'fees':
                    $title.html('<i class="fas fa-receipt mr-2 text-teal"></i>Fee Collections');
                    html = '<div class="table-responsive"><table class="table table-hover table-sm"><thead class="thead-light"><tr><th>Date</th><th>Member</th><th>Amount</th><th>Reference</th><th>Description</th></tr></thead><tbody>';
                    $.each(res.data, function(i, f) {
                        html += '<tr><td>' + formatDate(f.transaction_date) + '</td><td>' + (f.member_code ? f.member_code + ' - ' + f.first_name + ' ' + f.last_name : '-') + '</td><td class="text-right font-weight-bold">' + formatCurrency(f.amount) + '</td><td>' + (f.reference_number || '-') + '</td><td>' + (f.description || '-') + '</td></tr>';
                    });
                    html += '</tbody></table></div>';
                    break;
                    
                default:
                    html = '<div class="text-center text-muted py-4"><i class="fas fa-info-circle fa-2x mb-2"></i><p>No detail view available for this card.</p></div>';
            }
            $body.html(html);
        }).fail(function() {
            $body.html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>Failed to load details. Please try again.</div>');
        });
    }
    
    function formatCurrency(val) {
        var n = parseFloat(val) || 0;
        return '₹' + n.toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }
    
    function formatDate(d) {
        if (!d) return '-';
        var dt = new Date(d);
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return dt.getDate() + ' ' + months[dt.getMonth()] + ' ' + dt.getFullYear();
    }
    
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
                            return '<?= get_currency_symbol() ?>' + (value / 1000).toFixed(0) + 'K';
                        }
                    }
                }
            }
        }
    });
});
</script>
