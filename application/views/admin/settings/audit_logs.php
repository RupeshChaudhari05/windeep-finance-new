<!-- Audit Logs -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Audit Logs</h3>
        <div class="card-tools">
            <form class="form-inline" method="get" action="">
                <select name="table" class="form-control form-control-sm mr-2">
                    <option value="">All Tables</option>
                    <?php foreach ($tables ?? [] as $table): ?>
                    <option value="<?= $table ?>" <?= (isset($_GET['table']) && $_GET['table'] == $table) ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $table)) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="action" class="form-control form-control-sm mr-2">
                    <option value="">All Actions</option>
                    <option value="insert" <?= (isset($_GET['action']) && $_GET['action'] == 'insert') ? 'selected' : '' ?>>Insert</option>
                    <option value="update" <?= (isset($_GET['action']) && $_GET['action'] == 'update') ? 'selected' : '' ?>>Update</option>
                    <option value="delete" <?= (isset($_GET['action']) && $_GET['action'] == 'delete') ? 'selected' : '' ?>>Delete</option>
                </select>
                <input type="date" name="from_date" class="form-control form-control-sm mr-2" value="<?= $_GET['from_date'] ?? '' ?>" placeholder="From">
                <input type="date" name="to_date" class="form-control form-control-sm mr-2" value="<?= $_GET['to_date'] ?? '' ?>" placeholder="To">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i></button>
                <a href="<?= site_url('admin/settings/audit_logs') ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-times"></i></a>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" id="auditTable">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>Old Values</th>
                        <th>New Values</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <small><?= format_date($log->created_at) ?></small><br>
                            <small class="text-muted"><?= format_date_time($log->created_at, 'h:i:s A') ?></small>
                        </td>
                        <td>
                            <strong><?= $log->user_name ?? 'System' ?></strong>
                            <?php if (isset($log->user_role)): ?>
                            <br><small class="text-muted"><?= ucfirst($log->user_role) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $action_class = [
                                'insert' => 'success',
                                'update' => 'warning',
                                'delete' => 'danger'
                            ];
                            ?>
                            <span class="badge badge-<?= $action_class[$log->action] ?? 'secondary' ?>">
                                <i class="fas fa-<?= $log->action == 'insert' ? 'plus' : ($log->action == 'update' ? 'edit' : 'trash') ?>"></i>
                                <?= ucfirst($log->action) ?>
                            </span>
                        </td>
                        <td><code><?= $log->table_name ?></code></td>
                        <td><?= $log->record_id ?></td>
                        <td>
                            <?php if ($log->old_values): ?>
                            <button class="btn btn-xs btn-outline-secondary btn-view-json" 
                                    data-title="Old Values" 
                                    data-json='<?= htmlspecialchars($log->old_values) ?>'>
                                <i class="fas fa-eye"></i> View
                            </button>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log->new_values): ?>
                            <button class="btn btn-xs btn-outline-primary btn-view-json" 
                                    data-title="New Values" 
                                    data-json='<?= htmlspecialchars($log->new_values) ?>'>
                                <i class="fas fa-eye"></i> View
                            </button>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?= $log->ip_address ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (isset($pagination)): ?>
    <div class="card-footer">
        <?= $pagination ?>
    </div>
    <?php endif; ?>
</div>

<!-- JSON View Modal -->
<div class="modal fade" id="jsonModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="jsonModalTitle">View Data</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <pre id="jsonContent" style="max-height: 400px; overflow-y: auto; background: #f4f6f9; padding: 15px; border-radius: 5px;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#auditTable').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 50,
        "searching": true
    });
    
    // View JSON data
    $('.btn-view-json').click(function() {
        var title = $(this).data('title');
        var json = $(this).data('json');
        
        try {
            var parsed = JSON.parse(json);
            var formatted = JSON.stringify(parsed, null, 2);
            $('#jsonContent').text(formatted);
        } catch (e) {
            $('#jsonContent').text(json);
        }
        
        $('#jsonModalTitle').text(title);
        $('#jsonModal').modal('show');
    });
});
</script>
