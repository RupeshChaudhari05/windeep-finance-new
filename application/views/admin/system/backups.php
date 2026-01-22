<!-- Database Backups -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-database text-success"></i> Database Backups
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/system') ?>">System</a></li>
                    <li class="breadcrumb-item active">Backups</li>
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

        <!-- Create Backup Card -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Create New Backup</h3>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <p class="mb-0">
                            <i class="fas fa-info-circle text-info mr-2"></i>
                            Create a complete backup of your database. This includes all tables, data, and structure.
                            Backups are stored in <code><?= $backup_path ?></code>
                        </p>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="<?= base_url('admin/system/create_backup') ?>" class="btn btn-success btn-lg">
                            <i class="fas fa-download mr-2"></i> Create Backup Now
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning Card -->
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle mr-2"></i>Important Notes</h5>
            <ul class="mb-0">
                <li>Backups are created as ZIP files containing SQL dumps</li>
                <li>Automated daily backups are created via cron job (if configured)</li>
                <li>Always download important backups to a secure location</li>
                <li>Restoring a backup will <strong>overwrite current data</strong> - a pre-restore backup is created automatically</li>
            </ul>
        </div>

        <!-- Backups List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-archive mr-2"></i>Available Backups</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($backups)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-database fa-4x text-muted mb-3"></i>
                        <p class="text-muted">No backups found</p>
                        <a href="<?= base_url('admin/system/create_backup') ?>" class="btn btn-success">
                            <i class="fas fa-plus"></i> Create First Backup
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Created</th>
                                    <th>Status</th>
                                    <th width="20%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-file-archive text-warning mr-2"></i>
                                            <?= $backup->filename ?>
                                        </td>
                                        <td>
                                            <?php
                                            $type_class = 'secondary';
                                            switch ($backup->type) {
                                                case 'scheduled': $type_class = 'info'; break;
                                                case 'manual': $type_class = 'primary'; break;
                                                case 'pre-restore': $type_class = 'warning'; break;
                                            }
                                            ?>
                                            <span class="badge badge-<?= $type_class ?>"><?= ucfirst($backup->type) ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $size = $backup->size;
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
                                            <?= date('M d, Y H:i', strtotime($backup->created_at)) ?>
                                        </td>
                                        <td>
                                            <?php if ($backup->status === 'completed'): ?>
                                                <span class="badge badge-success"><i class="fas fa-check"></i> Completed</span>
                                            <?php elseif ($backup->status === 'in_progress'): ?>
                                                <span class="badge badge-warning"><i class="fas fa-spinner fa-spin"></i> In Progress</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger"><i class="fas fa-times"></i> Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= base_url('admin/system/download_backup/' . $backup->id) ?>" class="btn btn-info" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="<?= base_url('admin/system/restore_backup/' . $backup->id) ?>" class="btn btn-warning" title="Restore">
                                                    <i class="fas fa-undo"></i>
                                                </a>
                                                <a href="<?= base_url('admin/system/delete_backup/' . $backup->id) ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Delete this backup?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php if (!empty($backup->notes)): ?>
                                    <tr class="bg-light">
                                        <td colspan="6" class="py-1">
                                            <small class="text-muted"><i class="fas fa-sticky-note mr-1"></i> <?= $backup->notes ?></small>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Storage Info -->
        <div class="row">
            <div class="col-md-6">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-folder"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Backup Location</span>
                        <span class="info-box-number"><small><?= $backup_path ?></small></span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-archive"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Backups</span>
                        <span class="info-box-number"><?= count($backups) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
