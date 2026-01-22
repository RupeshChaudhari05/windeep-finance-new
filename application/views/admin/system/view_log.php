<!-- View Log File -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-file-code text-primary"></i> <?= $filename ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/system') ?>">System</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/system/logs') ?>">Logs</a></li>
                    <li class="breadcrumb-item active"><?= $filename ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Stats Row -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Errors</span>
                        <span class="info-box-number"><?= count(array_filter($entries, fn($e) => $e['level'] === 'ERROR')) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Warnings</span>
                        <span class="info-box-number"><?= count(array_filter($entries, fn($e) => $e['level'] === 'WARNING')) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-info-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Info/Debug</span>
                        <span class="info-box-number"><?= count(array_filter($entries, fn($e) => in_array($e['level'], ['INFO', 'DEBUG']))) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="fas fa-list"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Entries</span>
                        <span class="info-box-number"><?= count($entries) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Filter Entries</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Log Level</label>
                            <select id="levelFilter" class="form-control">
                                <option value="">All Levels</option>
                                <option value="ERROR">Errors Only</option>
                                <option value="WARNING">Warnings Only</option>
                                <option value="INFO">Info Only</option>
                                <option value="DEBUG">Debug Only</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Search Text</label>
                            <input type="text" id="textFilter" class="form-control" placeholder="Search in messages...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-secondary btn-block" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log Entries -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Log Entries</h3>
                <div class="card-tools">
                    <a href="<?= base_url('admin/system/delete_log/' . $filename) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this log file?')">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0" id="logTable">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th width="5%">Level</th>
                                <th width="15%">Time</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No log entries found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($entries as $entry): ?>
                                    <?php
                                    $badge_class = 'secondary';
                                    switch ($entry['level']) {
                                        case 'ERROR': $badge_class = 'danger'; break;
                                        case 'WARNING': $badge_class = 'warning'; break;
                                        case 'INFO': $badge_class = 'info'; break;
                                        case 'DEBUG': $badge_class = 'secondary'; break;
                                    }
                                    ?>
                                    <tr class="log-entry" data-level="<?= $entry['level'] ?>">
                                        <td>
                                            <span class="badge badge-<?= $badge_class ?>"><?= $entry['level'] ?></span>
                                        </td>
                                        <td>
                                            <small><?= date('H:i:s', strtotime($entry['datetime'])) ?></small>
                                        </td>
                                        <td>
                                            <code class="log-message" style="white-space: pre-wrap; word-break: break-word;">
                                                <?= htmlspecialchars($entry['message']) ?>
                                            </code>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Raw Log View -->
        <div class="card collapsed-card">
            <div class="card-header">
                <h3 class="card-title">Raw Log Content</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <pre style="max-height: 400px; overflow: auto; background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px;"><?= htmlspecialchars($raw_content) ?></pre>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('levelFilter').addEventListener('change', filterLogs);
document.getElementById('textFilter').addEventListener('input', filterLogs);

function filterLogs() {
    const level = document.getElementById('levelFilter').value.toLowerCase();
    const text = document.getElementById('textFilter').value.toLowerCase();
    const rows = document.querySelectorAll('.log-entry');
    
    rows.forEach(row => {
        const rowLevel = row.dataset.level.toLowerCase();
        const message = row.querySelector('.log-message').textContent.toLowerCase();
        
        const levelMatch = !level || rowLevel === level;
        const textMatch = !text || message.includes(text);
        
        row.style.display = (levelMatch && textMatch) ? '' : 'none';
    });
}

function clearFilters() {
    document.getElementById('levelFilter').value = '';
    document.getElementById('textFilter').value = '';
    filterLogs();
}
</script>
