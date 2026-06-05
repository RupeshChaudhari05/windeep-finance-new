<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2">🔄 Database Migrations</h1>
                <a href="<?php echo site_url('admin/migrations/history'); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-history"></i> View History
                </a>
            </div>
            <p class="text-muted">Manage and execute database migrations</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="text-primary font-weight-bold text-uppercase mb-1">Total Migrations</div>
                    <div class="h3 mb-0"><?php echo count($available_migrations); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="text-success font-weight-bold text-uppercase mb-1">Executed</div>
                    <div class="h3 mb-0"><?php echo count(array_filter($available_migrations, fn($m) => $m['executed'])); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="text-warning font-weight-bold text-uppercase mb-1">Pending</div>
                    <div class="h3 mb-0"><?php echo count($pending_migrations); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="text-info font-weight-bold text-uppercase mb-1">Last Updated</div>
                    <div class="small mb-0">
                        <?php 
                        $latest = end($migration_history);
                        echo $latest ? date('M d, Y', strtotime($latest->created_at)) : 'Never';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Migrations -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Available Migrations</h5>
        </div>
        <div class="card-body">
            <?php if (empty($available_migrations)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle"></i> No migration files found in database/migrations folder
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Migration File</th>
                                <th>Size</th>
                                <th>Last Modified</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_migrations as $migration): ?>
                                <tr>
                                    <td>
                                        <code class="text-primary"><?php echo $migration['name']; ?></code>
                                    </td>
                                    <td><?php echo $migration['size_formatted']; ?></td>
                                    <td>
                                        <small class="text-muted"><?php echo $migration['modified_date']; ?></small>
                                    </td>
                                    <td>
                                        <?php if ($migration['executed']): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i> Executed
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$migration['executed']): ?>
                                            <button class="btn btn-sm btn-success migration-execute-btn" 
                                                    data-migration="<?php echo $migration['name']; ?>">
                                                <i class="fas fa-play"></i> Execute
                                            </button>
                                        <?php else: ?>
                                            <span class="text-success">✓</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Migration History -->
    <?php if (!empty($migration_history)): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Recent Migration History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Migration</th>
                                <th>Status</th>
                                <th>Executed By</th>
                                <th>Duration</th>
                                <th>Executed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($migration_history, 0, 10) as $history): ?>
                                <tr>
                                    <td><code><?php echo $history->migration_name; ?></code></td>
                                    <td>
                                        <?php 
                                        $status_badge = [
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'running' => 'info',
                                            'pending' => 'warning'
                                        ];
                                        $badge_class = $status_badge[$history->status] ?? 'secondary';
                                        ?>
                                        <span class="badge badge-<?php echo $badge_class; ?>">
                                            <?php echo ucfirst($history->status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $history->executed_by ? 'Admin #' . $history->executed_by : '-'; ?></td>
                                    <td>
                                        <?php echo $history->duration_seconds ? $history->duration_seconds . 'ms' : '-'; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y H:i', strtotime($history->execution_timestamp)); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Execution Modal -->
<div class="modal fade" id="executionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Execute Migration</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="executionLog" class="bg-light p-3" style="max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; border-radius: 4px;">
                    Loading...
                </div>
                <div id="executionError" class="alert alert-danger mt-3" style="display:none;">
                    <strong>Error:</strong> <span id="errorText"></span>
                </div>
                <div id="executionSuccess" class="alert alert-success mt-3" style="display:none;">
                    <strong>Success!</strong> Migration executed successfully.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('.migration-execute-btn').click(function() {
        var migration = $(this).data('migration');
        var $log = $('#executionLog');
        var $modal = $('#executionModal');
        
        $log.html('<i class="fas fa-spinner fa-spin"></i> Executing migration: ' + migration);
        $('#executionError').hide();
        $('#executionSuccess').hide();
        $modal.modal('show');

        $.ajax({
            url: '<?php echo site_url('admin/migrations/execute'); ?>/' + migration,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $log.html('<span class="text-success">✓ Migration executed successfully</span><br>' +
                              'Duration: ' + response.duration + '<br>' +
                              'Output: ' + (response.output.join('<br>') || 'No output'));
                    $('#executionSuccess').show();
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $log.html('<span class="text-danger">✗ Execution failed</span>');
                    $('#executionError').show();
                    $('#errorText').text(response.message);
                }
            },
            error: function() {
                $log.html('<span class="text-danger">✗ Network error</span>');
                $('#executionError').show();
            }
        });
    });
});
</script>

<style>
.border-left-primary {
    border-left: 4px solid #007bff !important;
}
.border-left-success {
    border-left: 4px solid #28a745 !important;
}
.border-left-warning {
    border-left: 4px solid #ffc107 !important;
}
.border-left-info {
    border-left: 4px solid #17a2b8 !important;
}
</style>
