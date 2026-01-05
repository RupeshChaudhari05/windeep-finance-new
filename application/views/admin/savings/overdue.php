<!-- Overdue Savings Payments -->
<div class="row">
    <div class="col-12">
        <!-- Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= $total_overdue ?? 0 ?></h3>
                        <p>Total Overdue Accounts</p>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= number_format($overdue_amount ?? 0, 2) ?></h3>
                        <p>Total Overdue Amount</p>
                    </div>
                    <div class="icon"><i class="fas fa-rupee-sign"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $over_30_days ?? 0 ?></h3>
                        <p>Over 30 Days</p>
                    </div>
                    <div class="icon"><i class="fas fa-calendar-times"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3><?= $over_90_days ?? 0 ?></h3>
                        <p>Over 90 Days (Critical)</p>
                    </div>
                    <div class="icon"><i class="fas fa-skull-crossbones"></i></div>
                </div>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter Overdue</h3>
            </div>
            <div class="card-body py-2">
                <form method="get" class="form-inline">
                    <div class="form-group mr-3">
                        <label class="mr-2">Scheme:</label>
                        <select name="scheme_id" class="form-control form-control-sm">
                            <option value="">All</option>
                            <?php foreach ($schemes ?? [] as $scheme): ?>
                            <option value="<?= $scheme->id ?>" <?= (isset($_GET['scheme_id']) && $_GET['scheme_id'] == $scheme->id) ? 'selected' : '' ?>><?= $scheme->scheme_name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mr-3">
                        <label class="mr-2">Days Overdue:</label>
                        <select name="days" class="form-control form-control-sm">
                            <option value="">All</option>
                            <option value="7" <?= (isset($_GET['days']) && $_GET['days'] == '7') ? 'selected' : '' ?>>7+ days</option>
                            <option value="15" <?= (isset($_GET['days']) && $_GET['days'] == '15') ? 'selected' : '' ?>>15+ days</option>
                            <option value="30" <?= (isset($_GET['days']) && $_GET['days'] == '30') ? 'selected' : '' ?>>30+ days</option>
                            <option value="60" <?= (isset($_GET['days']) && $_GET['days'] == '60') ? 'selected' : '' ?>>60+ days</option>
                            <option value="90" <?= (isset($_GET['days']) && $_GET['days'] == '90') ? 'selected' : '' ?>>90+ days</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
                    <a href="<?= site_url('admin/savings/overdue') ?>" class="btn btn-secondary btn-sm ml-2"><i class="fas fa-times"></i> Clear</a>
                </form>
            </div>
        </div>
        
        <!-- Overdue List -->
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h3 class="card-title"><i class="fas fa-exclamation-circle mr-1"></i> Overdue Savings Accounts</h3>
                <div class="card-tools">
                    <button class="btn btn-warning btn-sm" id="sendBulkReminders">
                        <i class="fas fa-bell"></i> Send Bulk Reminders
                    </button>
                    <a href="<?= site_url('admin/savings/overdue?export=excel') ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-download"></i> Export
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="overdueTable">
                        <thead class="thead-dark">
                            <tr>
                                <th width="30"><input type="checkbox" id="selectAll"></th>
                                <th>Member</th>
                                <th>Account</th>
                                <th>Scheme</th>
                                <th>Last Payment</th>
                                <th>Due Since</th>
                                <th>Days Overdue</th>
                                <th>Overdue Amount</th>
                                <th>Total Arrears</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overdue as $item): ?>
                            <?php 
                            $days = floor((time() - safe_timestamp($item->due_date)) / 86400);
                            $severity = $days > 90 ? 'danger' : ($days > 30 ? 'warning' : 'info');
                            ?>
                            <tr class="table-<?= $severity ?>">
                                <td><input type="checkbox" class="row-select" value="<?= $item->member_id ?>"></td>
                                <td>
                                    <a href="<?= site_url('admin/members/view/' . $item->member_id) ?>" class="text-dark">
                                        <strong><?= $item->member_name ?></strong>
                                    </a>
                                    <br><small><?= $item->phone ?></small>
                                </td>
                                <td><code><?= $item->account_number ?></code></td>
                                <td><?= $item->scheme_name ?></td>
                                <td>
                                    <?= $item->last_payment_date ? format_date($item->last_payment_date) : 'Never' ?> 
                                </td>
                                <td><?= format_date($item->due_date) ?></td>
                                <td>
                                    <span class="badge badge-<?= $severity ?> badge-lg"><?= $days ?> days</span>
                                </td>
                                <td><?= number_format($item->overdue_amount, 2) ?></td>
                                <td>
                                    <strong class="text-danger"><?= number_format($item->total_arrears ?? $item->overdue_amount, 2) ?></strong>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= site_url('admin/savings/collect/' . $item->savings_account_id) ?>" class="btn btn-success" title="Collect">
                                            <i class="fas fa-money-bill"></i> Collect
                                        </a>
                                        <a href="<?= site_url('admin/savings/view/' . $item->savings_account_id) ?>" class="btn btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-warning btn-reminder" data-id="<?= $item->member_id ?>" title="Reminder">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Aging Summary -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Aging Summary</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <canvas id="agingChart" height="200"></canvas>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Aging Bucket</th>
                            <th>Accounts</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1-7 days</td>
                            <td><?= $aging['1_7']['count'] ?? 0 ?></td>
                            <td><?= number_format($aging['1_7']['amount'] ?? 0, 2) ?></td>
                        </tr>
                        <tr>
                            <td>8-15 days</td>
                            <td><?= $aging['8_15']['count'] ?? 0 ?></td>
                            <td><?= number_format($aging['8_15']['amount'] ?? 0, 2) ?></td>
                        </tr>
                        <tr>
                            <td>16-30 days</td>
                            <td><?= $aging['16_30']['count'] ?? 0 ?></td>
                            <td><?= number_format($aging['16_30']['amount'] ?? 0, 2) ?></td>
                        </tr>
                        <tr class="table-warning">
                            <td>31-60 days</td>
                            <td><?= $aging['31_60']['count'] ?? 0 ?></td>
                            <td><?= number_format($aging['31_60']['amount'] ?? 0, 2) ?></td>
                        </tr>
                        <tr class="table-danger">
                            <td>61-90 days</td>
                            <td><?= $aging['61_90']['count'] ?? 0 ?></td>
                            <td><?= number_format($aging['61_90']['amount'] ?? 0, 2) ?></td>
                        </tr>
                        <tr class="table-dark">
                            <td>90+ days</td>
                            <td><?= $aging['90_plus']['count'] ?? 0 ?></td>
                            <td><?= number_format($aging['90_plus']['amount'] ?? 0, 2) ?></td>
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
    $('#overdueTable').DataTable({
        "order": [[6, "desc"]],
        "pageLength": 50
    });
    
    // Select all
    $('#selectAll').change(function() {
        $('.row-select').prop('checked', $(this).is(':checked'));
    });
    
    // Send bulk reminders
    $('#sendBulkReminders').click(function() {
        var ids = [];
        $('.row-select:checked').each(function() {
            ids.push($(this).val());
        });
        
        if (ids.length === 0) {
            toastr.warning('Please select at least one member');
            return;
        }
        
        if (confirm('Send reminder to ' + ids.length + ' members?')) {
            $.post('<?= site_url('admin/savings/bulk_reminder') ?>', {member_ids: ids}, function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Reminders sent successfully');
                } else {
                    toastr.error(response.message || 'Failed to send reminders');
                }
            }, 'json');
        }
    });
    
    // Individual reminder
    $('.btn-reminder').click(function() {
        var id = $(this).data('id');
        $.post('<?= site_url('admin/savings/send_reminder') ?>', {member_id: id}, function(response) {
            if (response.success) {
                toastr.success('Reminder sent');
            } else {
                toastr.error(response.message || 'Failed');
            }
        }, 'json');
    });
    
    // Aging chart
    new Chart(document.getElementById('agingChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['1-7 days', '8-15 days', '16-30 days', '31-60 days', '61-90 days', '90+ days'],
            datasets: [{
                label: 'Amount',
                data: [
                    <?= $aging['1_7']['amount'] ?? 0 ?>,
                    <?= $aging['8_15']['amount'] ?? 0 ?>,
                    <?= $aging['16_30']['amount'] ?? 0 ?>,
                    <?= $aging['31_60']['amount'] ?? 0 ?>,
                    <?= $aging['61_90']['amount'] ?? 0 ?>,
                    <?= $aging['90_plus']['amount'] ?? 0 ?>
                ],
                backgroundColor: ['#17a2b8', '#17a2b8', '#17a2b8', '#ffc107', '#fd7e14', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
