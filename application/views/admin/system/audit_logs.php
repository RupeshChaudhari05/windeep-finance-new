<!-- Audit Logs -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-history text-info"></i> Audit Trail
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/system') ?>">System</a></li>
                    <li class="breadcrumb-item active">Audit Logs</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Filters -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filters</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= current_url() ?>">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>User Type</label>
                                <select name="user_type" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="admin" <?= $filters['user_type'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="member" <?= $filters['user_type'] === 'member' ? 'selected' : '' ?>>Member</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Action</label>
                                <select name="action" class="form-control">
                                    <option value="">All Actions</option>
                                    <?php foreach ($actions as $action): ?>
                                        <option value="<?= $action->action ?>" <?= $filters['action'] === $action->action ? 'selected' : '' ?>><?= $action->action ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>From Date</label>
                                <input type="date" name="date_from" class="form-control" value="<?= $filters['date_from'] ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>To Date</label>
                                <input type="date" name="date_to" class="form-control" value="<?= $filters['date_to'] ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                    <a href="<?= base_url('admin/system/audit_logs') ?>" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Audit Entries</h3>
                <div class="card-tools">
                    <span class="badge badge-info"><?= number_format($total) ?> records</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th width="12%">Date/Time</th>
                                <th width="8%">User</th>
                                <th width="15%">Action</th>
                                <th>Description</th>
                                <th width="10%">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No audit logs found</td></tr>
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
                                            <span class="badge badge-<?= $log->user_type === 'admin' ? 'primary' : 'success' ?>">
                                                <?= ucfirst($log->user_type) ?>
                                            </span>
                                            <br><small>#<?= $log->user_id ?></small>
                                        </td>
                                        <td>
                                            <code><?= $log->action ?></code>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($log->description ?? '-') ?>
                                            <?php if (!empty($log->entity_type)): ?>
                                                <br><small class="text-muted"><?= $log->entity_type ?> #<?= $log->entity_id ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= $log->ip_address ?></small>
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
                            <a class="page-link" href="?page=<?= $current_page - 1 ?>&<?= http_build_query($filters) ?>">&laquo;</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                        <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($filters) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>&<?= http_build_query($filters) ?>">&raquo;</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <span class="text-muted">Page <?= $current_page ?> of <?= $total_pages ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
