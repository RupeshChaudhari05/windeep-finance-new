<!-- Pending Savings Payments -->
<div class="row">
    <div class="col-md-12">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pending This Month</span>
                        <span class="info-box-number"><?= $pending_this_month ?? 0 ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-rupee-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Expected Amount</span>
                        <span class="info-box-number"><?= number_format($expected_amount ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Collected Today</span>
                        <span class="info-box-number"><?= number_format($collected_today ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Overdue</span>
                        <span class="info-box-number"><?= $overdue_count ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Card -->
        <div class="card card-outline card-primary mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <form method="get" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Scheme</label>
                                <select name="scheme_id" class="form-control">
                                    <option value="">All Schemes</option>
                                    <?php foreach ($schemes ?? [] as $scheme): ?>
                                    <option value="<?= $scheme->id ?>" <?= (isset($_GET['scheme_id']) && $_GET['scheme_id'] == $scheme->id) ? 'selected' : '' ?>><?= $scheme->scheme_name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Month</label>
                                <input type="month" name="month" class="form-control" value="<?= $_GET['month'] ?? date('Y-m') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="pending" <?= (!isset($_GET['status']) || $_GET['status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                                    <option value="partial" <?= (isset($_GET['status']) && $_GET['status'] == 'partial') ? 'selected' : '' ?>>Partial</option>
                                    <option value="all" <?= (isset($_GET['status']) && $_GET['status'] == 'all') ? 'selected' : '' ?>>All</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                                    <a href="<?= site_url('admin/savings/pending') ?>" class="btn btn-secondary"><i class="fas fa-times"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Pending List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-1"></i> Pending Savings Payments</h3>
                <div class="card-tools">
                    <button class="btn btn-success btn-sm" id="bulkCollect" disabled>
                        <i class="fas fa-money-bill"></i> Bulk Collect Selected
                    </button>
                    <a href="<?= site_url('admin/savings/pending?export=excel') ?>" class="btn btn-info btn-sm">
                        <i class="fas fa-download"></i> Export
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="pendingTable">
                        <thead class="thead-light">
                            <tr>
                                <th width="30"><input type="checkbox" id="selectAll"></th>
                                <th>Member</th>
                                <th>Account No.</th>
                                <th>Scheme</th>
                                <th>Due Date</th>
                                <th>Due Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending as $item): ?>
                            <tr data-id="<?= $item->id ?>">
                                <td><input type="checkbox" class="row-select" value="<?= $item->id ?>"></td>
                                <td>
                                    <a href="<?= site_url('admin/members/view/' . $item->member_id) ?>">
                                        <strong><?= $item->member_name ?></strong>
                                    </a>
                                    <br><small class="text-muted"><?= $item->phone ?></small>
                                </td>
                                <td><code><?= $item->account_number ?></code></td>
                                <td>
                                    <span class="badge badge-info"><?= $item->scheme_name ?></span>
                                </td>
                                <td>
                                    <?= format_date($item->due_date) ?> 
                                    <?php 
                                    $days_overdue = floor((time() - safe_timestamp($item->due_date)) / 86400);
                                    if ($days_overdue > 0): 
                                    ?>
                                    <br><small class="text-danger"><?= $days_overdue ?> days overdue</small>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= number_format($item->due_amount, 2) ?></strong></td>
                                <td><?= number_format($item->paid_amount ?? 0, 2) ?></td>
                                <td>
                                    <strong class="text-danger"><?= number_format($item->due_amount - ($item->paid_amount ?? 0), 2) ?></strong>
                                </td>
                                <td>
                                    <?php if (($item->paid_amount ?? 0) == 0): ?>
                                    <span class="badge badge-warning">Pending</span>
                                    <?php elseif ($item->paid_amount < $item->due_amount): ?>
                                    <span class="badge badge-info">Partial</span>
                                    <?php else: ?>
                                    <span class="badge badge-success">Paid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= site_url('admin/savings/collect/' . $item->savings_id) ?>" class="btn btn-sm btn-success" title="Collect">
                                        <i class="fas fa-money-bill"></i>
                                    </a>
                                    <a href="<?= site_url('admin/savings/view/' . $item->savings_id) ?>" class="btn btn-sm btn-info" title="View Account">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-secondary btn-send-reminder" data-id="<?= $item->member_id ?>" title="Send Reminder">
                                        <i class="fas fa-bell"></i>
                                    </button>
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

<script>
$(document).ready(function() {
    $('#pendingTable').DataTable({
        "order": [[4, "asc"]],
        "pageLength": 50
    });
    
    // Select all
    $('#selectAll').change(function() {
        $('.row-select').prop('checked', $(this).is(':checked'));
        toggleBulkButton();
    });
    
    $('.row-select').change(function() {
        toggleBulkButton();
    });
    
    function toggleBulkButton() {
        var selected = $('.row-select:checked').length;
        $('#bulkCollect').prop('disabled', selected === 0);
        if (selected > 0) {
            $('#bulkCollect').html('<i class="fas fa-money-bill"></i> Collect ' + selected + ' Selected');
        } else {
            $('#bulkCollect').html('<i class="fas fa-money-bill"></i> Bulk Collect Selected');
        }
    }
    
    // Bulk collect
    $('#bulkCollect').click(function() {
        var ids = [];
        $('.row-select:checked').each(function() {
            ids.push($(this).val());
        });
        if (ids.length > 0) {
            window.location.href = '<?= site_url('admin/savings/bulk_collect') ?>?ids=' + ids.join(',');
        }
    });
    
    // Send reminder
    $('.btn-send-reminder').click(function() {
        var memberId = $(this).data('id');
        if (confirm('Send payment reminder to this member?')) {
            $.post('<?= site_url('admin/savings/send_reminder') ?>', {member_id: memberId}, function(response) {
                if (response.success) {
                    toastr.success('Reminder sent successfully');
                } else {
                    toastr.error(response.message || 'Failed to send reminder');
                }
            }, 'json');
        }
    });
});
</script>
