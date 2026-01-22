<!-- Restore Backup Confirmation -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-undo text-warning"></i> Restore Backup
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/system') ?>">System</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/system/backups') ?>">Backups</a></li>
                    <li class="breadcrumb-item active">Restore</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Warning Card -->
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Warning: Database Restore
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <h5><i class="icon fas fa-ban"></i> Critical Action!</h5>
                            <p class="mb-0">
                                Restoring a backup will <strong>overwrite your current database</strong>. 
                                All data entered after this backup was created will be <strong>permanently lost</strong>.
                            </p>
                        </div>

                        <div class="callout callout-info">
                            <h5>Backup Information</h5>
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <th width="30%">Filename:</th>
                                    <td><?= $backup->filename ?></td>
                                </tr>
                                <tr>
                                    <th>Created:</th>
                                    <td><?= date('F d, Y \a\t H:i:s', strtotime($backup->created_at)) ?></td>
                                </tr>
                                <tr>
                                    <th>Size:</th>
                                    <td><?= number_format($backup->size / 1048576, 2) ?> MB</td>
                                </tr>
                                <tr>
                                    <th>Type:</th>
                                    <td>
                                        <span class="badge badge-<?= $backup->type === 'scheduled' ? 'info' : 'primary' ?>">
                                            <?= ucfirst($backup->type) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php if ($backup->notes): ?>
                                <tr>
                                    <th>Notes:</th>
                                    <td><?= $backup->notes ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <div class="callout callout-warning">
                            <h5><i class="fas fa-shield-alt mr-2"></i>Safety Measure</h5>
                            <p class="mb-0">
                                A <strong>pre-restore backup</strong> of your current database will be created automatically 
                                before the restoration begins. This allows you to undo the restore if needed.
                            </p>
                        </div>

                        <form action="<?= base_url('admin/system/process_restore/' . $backup->id) ?>" method="POST" id="restoreForm">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="confirmRestore" required>
                                    <label class="custom-control-label" for="confirmRestore">
                                        I understand that this action will overwrite the current database and may result in data loss.
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Type <strong>RESTORE</strong> to confirm:</label>
                                <input type="text" class="form-control" id="confirmText" placeholder="Type RESTORE" autocomplete="off">
                            </div>

                            <div class="row mt-4">
                                <div class="col-6">
                                    <a href="<?= base_url('admin/system/backups') ?>" class="btn btn-secondary btn-block">
                                        <i class="fas fa-arrow-left mr-2"></i>Cancel
                                    </a>
                                </div>
                                <div class="col-6">
                                    <button type="submit" class="btn btn-danger btn-block" id="restoreBtn" disabled>
                                        <i class="fas fa-undo mr-2"></i>Restore Backup
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheckbox = document.getElementById('confirmRestore');
    const confirmText = document.getElementById('confirmText');
    const restoreBtn = document.getElementById('restoreBtn');
    const restoreForm = document.getElementById('restoreForm');

    function checkForm() {
        const isChecked = confirmCheckbox.checked;
        const isTextValid = confirmText.value.toUpperCase() === 'RESTORE';
        restoreBtn.disabled = !(isChecked && isTextValid);
    }

    confirmCheckbox.addEventListener('change', checkForm);
    confirmText.addEventListener('input', checkForm);

    restoreForm.addEventListener('submit', function(e) {
        if (!confirm('Final confirmation: Are you absolutely sure you want to restore this backup?')) {
            e.preventDefault();
        }
    });
});
</script>
