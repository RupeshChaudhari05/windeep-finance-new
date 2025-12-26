<!-- Activity Logs -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-clock mr-1"></i> Activity Logs</h3>
        <div class="card-tools">
            <form class="form-inline" method="get" action="">
                <select name="user_id" class="form-control form-control-sm mr-2">
                    <option value="">All Users</option>
                    <?php foreach ($users ?? [] as $user): ?>
                    <option value="<?= $user->id ?>" <?= (isset($_GET['user_id']) && $_GET['user_id'] == $user->id) ? 'selected' : '' ?>><?= $user->full_name ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="activity_type" class="form-control form-control-sm mr-2">
                    <option value="">All Activities</option>
                    <option value="login" <?= (isset($_GET['activity_type']) && $_GET['activity_type'] == 'login') ? 'selected' : '' ?>>Login</option>
                    <option value="logout" <?= (isset($_GET['activity_type']) && $_GET['activity_type'] == 'logout') ? 'selected' : '' ?>>Logout</option>
                    <option value="view" <?= (isset($_GET['activity_type']) && $_GET['activity_type'] == 'view') ? 'selected' : '' ?>>View</option>
                    <option value="create" <?= (isset($_GET['activity_type']) && $_GET['activity_type'] == 'create') ? 'selected' : '' ?>>Create</option>
                    <option value="update" <?= (isset($_GET['activity_type']) && $_GET['activity_type'] == 'update') ? 'selected' : '' ?>>Update</option>
                    <option value="delete" <?= (isset($_GET['activity_type']) && $_GET['activity_type'] == 'delete') ? 'selected' : '' ?>>Delete</option>
                    <option value="export" <?= (isset($_GET['activity_type']) && $_GET['activity_type'] == 'export') ? 'selected' : '' ?>>Export</option>
                </select>
                <input type="date" name="date" class="form-control form-control-sm mr-2" value="<?= $_GET['date'] ?? '' ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i></button>
                <a href="<?= site_url('admin/settings/activity_logs') ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-times"></i></a>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" id="activityTable">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Activity</th>
                        <th>Description</th>
                        <th>Module</th>
                        <th>IP Address</th>
                        <th>Browser</th>
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
                            <strong><?= $log->user_name ?? 'Guest' ?></strong>
                            <?php if (isset($log->user_role)): ?>
                            <br><small class="text-muted"><?= ucfirst($log->user_role) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $type_icons = [
                                'login' => ['sign-in-alt', 'success'],
                                'logout' => ['sign-out-alt', 'secondary'],
                                'view' => ['eye', 'info'],
                                'create' => ['plus', 'success'],
                                'update' => ['edit', 'warning'],
                                'delete' => ['trash', 'danger'],
                                'export' => ['download', 'primary']
                            ];
                            $icon = $type_icons[$log->activity_type] ?? ['circle', 'secondary'];
                            ?>
                            <span class="badge badge-<?= $icon[1] ?>">
                                <i class="fas fa-<?= $icon[0] ?>"></i>
                                <?= ucfirst($log->activity_type) ?>
                            </span>
                        </td>
                        <td><?= $log->description ?></td>
                        <td><code><?= $log->module ?? '-' ?></code></td>
                        <td><small><?= $log->ip_address ?></small></td>
                        <td>
                            <small title="<?= $log->user_agent ?? '' ?>"><?= substr($log->browser ?? 'Unknown', 0, 20) ?></small>
                        </td>
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

<!-- Activity Statistics -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-pie mr-1"></i> Activity by Type</h5>
            </div>
            <div class="card-body">
                <canvas id="activityTypeChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0"><i class="fas fa-users mr-1"></i> Most Active Users</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($active_users ?? [] as $auser): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= $auser->user_name ?>
                        <span class="badge badge-primary badge-pill"><?= $auser->activity_count ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0"><i class="fas fa-clock mr-1"></i> Recent Logins</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($recent_logins ?? [] as $login): ?>
                    <li class="list-group-item">
                        <strong><?= $login->user_name ?></strong>
                        <small class="float-right text-muted"><?= format_date_time($login->created_at, 'd M h:i A') ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    $('#activityTable').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 50
    });
    
    // Activity Type Chart
    var ctx = document.getElementById('activityTypeChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($activity_stats ?? [], 'activity_type')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($activity_stats ?? [], 'count')) ?>,
                backgroundColor: ['#28a745', '#6c757d', '#17a2b8', '#28a745', '#ffc107', '#dc3545', '#007bff']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>
