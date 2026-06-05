<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2">📋 Migration History</h1>
                <a href="<?php echo site_url('admin/migrations'); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label class="small font-weight-bold">Filter by Status</label>
                    <select id="statusFilter" class="form-control form-control-sm">
                        <option value="">All</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="running">Running</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold">Search Migration</label>
                    <input type="text" id="searchMigration" class="form-control form-control-sm" placeholder="Search...">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                        <i class="fas fa-refresh"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Migration History Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">All Migrations</h5>
        </div>
        <div class="card-body">
            <?php if (empty($migrations)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle"></i> No migration history available
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="migrationsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Migration Name</th>
                                <th>Status</th>
                                <th>Executed By</th>
                                <th>Duration</th>
                                <th>Executed At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($migrations as $migration): ?>
                                <tr class="migration-row" data-status="<?php echo $migration->status; ?>">
                                    <td>
                                        <code class="text-primary"><?php echo $migration->migration_name; ?></code>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_icon = [
                                            'completed' => '✓',
                                            'failed' => '✗',
                                            'running' => '⟳',
                                            'pending' => '⏱'
                                        ];
                                        $status_badge = [
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'running' => 'info',
                                            'pending' => 'warning'
                                        ];
                                        $badge_class = $status_badge[$migration->status] ?? 'secondary';
                                        $icon = $status_icon[$migration->status] ?? '?';
                                        ?>
                                        <span class="badge badge-<?php echo $badge_class; ?>">
                                            <?php echo $icon; ?> <?php echo ucfirst($migration->status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($migration->executed_by) {
                                            echo '<small>Admin #' . $migration->executed_by . '</small>';
                                        } else {
                                            echo '<span class="text-muted">System</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo $migration->duration_seconds ? $migration->duration_seconds . 'ms' : '—'; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php 
                                            if ($migration->execution_timestamp) {
                                                echo date('M d, Y H:i:s', strtotime($migration->execution_timestamp));
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary view-details" 
                                                data-migration-id="<?php echo $migration->id; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if ($migration->status === 'failed'): ?>
                                            <button class="btn btn-sm btn-outline-danger" title="Failed migrations must be reviewed manually">
                                                <i class="fas fa-exclamation"></i>
                                            </button>
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
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Migration Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailsContent">
                Loading...
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    // Filter by status
    $('#statusFilter').change(function() {
        var status = $(this).val();
        $('tbody tr').show();
        if (status) {
            $('tbody tr[data-status!="' + status + '"]').hide();
        }
    });

    // Search
    $('#searchMigration').keyup(function() {
        var search = $(this).val().toLowerCase();
        $('tbody tr').show();
        if (search) {
            $('tbody tr').each(function() {
                if ($(this).find('code').text().toLowerCase().indexOf(search) === -1) {
                    $(this).hide();
                }
            });
        }
    });

    // View details
    $('.view-details').click(function() {
        var migrationId = $(this).data('migration-id');
        var $modal = $('#detailsModal');
        var $content = $('#detailsContent');

        $.ajax({
            url: '<?php echo site_url('admin/migrations/get_details'); ?>/' + migrationId,
            dataType: 'json',
            success: function(response) {
                var m = response.data;
                var html = '<dl class="row">';
                html += '<dt class="col-sm-4">Migration Name:</dt>';
                html += '<dd class="col-sm-8"><code>' + m.migration_name + '</code></dd>';
                html += '<dt class="col-sm-4">Status:</dt>';
                html += '<dd class="col-sm-8"><span class="badge badge-' + (m.status === 'completed' ? 'success' : m.status === 'failed' ? 'danger' : 'info') + '">' + m.status.toUpperCase() + '</span></dd>';
                html += '<dt class="col-sm-4">Executed At:</dt>';
                html += '<dd class="col-sm-8">' + (m.execution_timestamp || '—') + '</dd>';
                html += '<dt class="col-sm-4">Duration:</dt>';
                html += '<dd class="col-sm-8">' + (m.duration_seconds ? m.duration_seconds + 'ms' : '—') + '</dd>';
                if (m.output_log) {
                    html += '<dt class="col-sm-4">Output:</dt>';
                    html += '<dd class="col-sm-8"><pre class="bg-light p-2" style="font-size:11px;max-height:300px;overflow-y:auto;">' + m.output_log + '</pre></dd>';
                }
                if (m.error_message) {
                    html += '<dt class="col-sm-4">Error:</dt>';
                    html += '<dd class="col-sm-8"><pre class="bg-danger text-white p-2" style="font-size:11px;max-height:300px;overflow-y:auto;">' + m.error_message + '</pre></dd>';
                }
                html += '</dl>';
                $content.html(html);
                $modal.modal('show');
            }
        });
    });
});
</script>
