<!-- System Dashboard -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-cogs text-primary"></i> System Management
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">System</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Quick Actions</h5>
                        <div class="btn-group" role="group">
                            <a href="<?= base_url('admin/system/logs') ?>" class="btn btn-outline-primary">
                                <i class="fas fa-file-alt"></i> View Logs
                            </a>
                            <a href="<?= base_url('admin/system/backups') ?>" class="btn btn-outline-success">
                                <i class="fas fa-database"></i> Backups
                            </a>
                            <a href="<?= base_url('admin/system/audit_logs') ?>" class="btn btn-outline-info">
                                <i class="fas fa-history"></i> Audit Trail
                            </a>
                            <a href="<?= base_url('admin/system/cron_logs') ?>" class="btn btn-outline-warning">
                                <i class="fas fa-clock"></i> Cron Jobs
                            </a>
                            <a href="<?= base_url('admin/system/clear_cache') ?>" class="btn btn-outline-danger" onclick="return confirm('Clear all cache files?')">
                                <i class="fas fa-broom"></i> Clear Cache
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Info Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $php_version ?></h3>
                        <p>PHP Version</p>
                    </div>
                    <div class="icon">
                        <i class="fab fa-php"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $db_version ?></h3>
                        <p>Database Version</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= round(($disk_total - $disk_free) / $disk_total * 100, 1) ?>%</h3>
                        <p>Disk Used</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="small-box bg-<?= $cron_status['status'] === 'ok' ? 'success' : 'danger' ?>">
                    <div class="inner">
                        <h3><?= ucfirst($cron_status['status']) ?></h3>
                        <p>Cron Status</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Server Info -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-server mr-2"></i>Server Information</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <tr>
                                <th width="40%">Server Software</th>
                                <td><?= $server_software ?></td>
                            </tr>
                            <tr>
                                <th>PHP Version</th>
                                <td><?= $php_version ?></td>
                            </tr>
                            <tr>
                                <th>CodeIgniter Version</th>
                                <td><?= $ci_version ?></td>
                            </tr>
                            <tr>
                                <th>Memory Limit</th>
                                <td><?= $memory_limit ?></td>
                            </tr>
                            <tr>
                                <th>Max Execution Time</th>
                                <td><?= $max_execution_time ?> seconds</td>
                            </tr>
                            <tr>
                                <th>Upload Max Filesize</th>
                                <td><?= $upload_max_filesize ?></td>
                            </tr>
                            <tr>
                                <th>Post Max Size</th>
                                <td><?= $post_max_size ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Database Stats -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-database mr-2"></i>Database Statistics</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <tr>
                                <th width="40%">Database Version</th>
                                <td><?= $db_version ?></td>
                            </tr>
                            <?php foreach ($db_stats as $table => $count): ?>
                            <tr>
                                <th><?= ucfirst(str_replace('_', ' ', $table)) ?></th>
                                <td><span class="badge badge-info"><?= number_format($count) ?></span> records</td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Errors -->
        <?php if (!empty($recent_errors)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card card-danger card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Recent Errors (Today)</h3>
                        <div class="card-tools">
                            <a href="<?= base_url('admin/system/logs') ?>" class="btn btn-tool">
                                View All <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="15%">Time</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_errors as $error): ?>
                                    <tr>
                                        <td><small><?= date('H:i:s', strtotime($error['datetime'])) ?></small></td>
                                        <td><code class="text-danger"><?= htmlspecialchars(substr($error['message'], 0, 200)) ?></code></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Disk Space -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-hdd mr-2"></i>Disk Space</h3>
                    </div>
                    <div class="card-body">
                        <?php 
                        $used = $disk_total - $disk_free;
                        $percent = round($used / $disk_total * 100, 1);
                        $color = $percent > 90 ? 'danger' : ($percent > 70 ? 'warning' : 'success');
                        ?>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-<?= $color ?>" role="progressbar" style="width: <?= $percent ?>%">
                                <?= $percent ?>% Used
                            </div>
                        </div>
                        <div class="row mt-3 text-center">
                            <div class="col-4">
                                <strong>Total:</strong> <?= number_format($disk_total / 1073741824, 2) ?> GB
                            </div>
                            <div class="col-4">
                                <strong>Used:</strong> <?= number_format($used / 1073741824, 2) ?> GB
                            </div>
                            <div class="col-4">
                                <strong>Free:</strong> <?= number_format($disk_free / 1073741824, 2) ?> GB
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
