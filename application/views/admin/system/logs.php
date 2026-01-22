<!-- Application Logs -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-file-alt text-primary"></i> Application Logs
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/system') ?>">System</a></li>
                    <li class="breadcrumb-item active">Logs</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-check-circle"></i> <?= $this->session->flashdata('success') ?>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-exclamation-circle"></i> <?= $this->session->flashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Log Files</h3>
                <div class="card-tools">
                    <?php if (!empty($log_files)): ?>
                        <a href="<?= base_url('admin/system/clear_logs') ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete ALL log files? This cannot be undone.')">
                            <i class="fas fa-trash"></i> Clear All Logs
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($log_files)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                        <p class="text-muted">No log files found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Date</th>
                                    <th>Size</th>
                                    <th width="20%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($log_files as $file): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-file-code text-info mr-2"></i>
                                            <a href="<?= base_url('admin/system/view_log/' . $file['name']) ?>">
                                                <?= $file['name'] ?>
                                            </a>
                                            <?php if ($file['date'] === date('Y-m-d')): ?>
                                                <span class="badge badge-success ml-2">Today</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($file['date'])) ?></td>
                                        <td>
                                            <?php 
                                            $size = $file['size'];
                                            if ($size > 1048576) {
                                                echo number_format($size / 1048576, 2) . ' MB';
                                            } elseif ($size > 1024) {
                                                echo number_format($size / 1024, 2) . ' KB';
                                            } else {
                                                echo $size . ' bytes';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('admin/system/view_log/' . $file['name']) ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= base_url('admin/system/delete_log/' . $file['name']) ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this log file?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
