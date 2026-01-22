<!-- Cron Job Logs -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-clock text-warning"></i> Cron Job Logs
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/system') ?>">System</a></li>
                    <li class="breadcrumb-item active">Cron Logs</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Cron Schedule Info -->
        <div class="callout callout-info">
            <h5><i class="fas fa-info-circle"></i> Cron Schedule</h5>
            <div class="row">
                <div class="col-md-3">
                    <strong>Hourly:</strong> Process emails, check consents
                </div>
                <div class="col-md-3">
                    <strong>Daily (2 AM):</strong> Fines, overdues, reminders
                </div>
                <div class="col-md-3">
                    <strong>Weekly (Sun 3 AM):</strong> Extend schedules, reports
                </div>
                <div class="col-md-3">
                    <strong>Monthly (1st 4 AM):</strong> Interest, backup
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-outline card-primary collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filters</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Job Name</label>
                                <select name="job" class="form-control">
                                    <option value="">All Jobs</option>
                                    <?php foreach ($jobs as $job): ?>
                                        <option value="<?= $job->job_name ?>"><?= $job->job_name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="started">Started</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                    <a href="<?= current_url() ?>" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Execution Logs</h3>
                <div class="card-tools">
                    <span class="badge badge-info"><?= number_format($total) ?> records</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th width="15%">Date/Time</th>
                                <th>Job Name</th>
                                <th width="10%">Status</th>
                                <th width="10%">Duration</th>
                                <th width="10%">Records</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fas fa-clock fa-3x mb-3"></i>
                                        <p>No cron logs found</p>
                                        <small>Cron jobs haven't run yet or logging is not configured</small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <small>
                                                <?= date('M d, Y', strtotime($log->created_at)) ?><br>
                                                <span class="text-muted"><?= date('H:i:s', strtotime($log->created_at)) ?></span>
                                            </small>
                                        </td>
                                        <td>
                                            <i class="fas fa-cog text-info mr-1"></i>
                                            <code><?= $log->job_name ?></code>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = 'secondary';
                                            $status_icon = 'circle';
                                            switch ($log->status) {
                                                case 'started':
                                                    $status_class = 'info';
                                                    $status_icon = 'play';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'success';
                                                    $status_icon = 'check';
                                                    break;
                                                case 'failed':
                                                    $status_class = 'danger';
                                                    $status_icon = 'times';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge badge-<?= $status_class ?>">
                                                <i class="fas fa-<?= $status_icon ?> mr-1"></i>
                                                <?= ucfirst($log->status) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($log->duration_seconds): ?>
                                                <?php if ($log->duration_seconds >= 60): ?>
                                                    <?= floor($log->duration_seconds / 60) ?>m <?= $log->duration_seconds % 60 ?>s
                                                <?php else: ?>
                                                    <?= $log->duration_seconds ?>s
                                                <?php endif; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($log->records_processed !== null): ?>
                                                <span class="badge badge-primary"><?= number_format($log->records_processed) ?></span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($log->message): ?>
                                                <?php if ($log->status === 'failed'): ?>
                                                    <span class="text-danger"><?= htmlspecialchars($log->message) ?></span>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($log->message) ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($total_pages > 1): ?>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $current_page - 1 ?>">&laquo;</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                        <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>">&raquo;</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <span class="text-muted">Page <?= $current_page ?> of <?= $total_pages ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Quick Test -->
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-terminal mr-2"></i>Manual Execution</h3>
            </div>
            <div class="card-body">
                <p>To test cron jobs manually, run these commands from the server:</p>
                <pre class="bg-dark text-white p-3 rounded">
# Test cron system
php index.php cli/cron/test

# Run daily jobs
php index.php cli/cron/daily

# Run weekly jobs
php index.php cli/cron/weekly

# Run monthly jobs
php index.php cli/cron/monthly

# Check cron status
php index.php cli/cron/status</pre>
            </div>
        </div>
    </div>
</section>
