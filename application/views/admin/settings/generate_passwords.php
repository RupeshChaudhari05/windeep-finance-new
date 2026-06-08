<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2 class="mb-0">
                <i class="fas fa-key mr-2"></i>Member Password Generation
            </h2>
            <small class="text-muted">Generated: <?= date('d M Y H:i:s') ?></small>
        </div>
    </div>

    <!-- Status Alert -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="alert alert-success" role="alert">
                <h5 class="alert-heading">
                    <i class="fas fa-check-circle mr-2"></i>Success!
                </h5>
                <p class="mb-0">
                    <strong><?= $updated_count ?></strong> member passwords have been generated and updated.
                </p>
            </div>
        </div>
    </div>

    <!-- Credentials Table -->
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-users mr-1"></i>Member Credentials (<?= count($credentials) ?> members)
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Member Code</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th width="80">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($credentials)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No credentials generated</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($credentials as $key => $cred): ?>
                            <tr>
                                <td><?= $key + 1 ?></td>
                                <td>
                                    <span class="badge badge-primary"><?= $cred['member_code'] ?></span>
                                </td>
                                <td><?= $cred['first_name'] . ' ' . $cred['last_name'] ?></td>
                                <td>
                                    <a href="tel:<?= $cred['phone'] ?>" class="text-primary">
                                        <?= $cred['phone'] ?>
                                    </a>
                                </td>
                                <td>
                                    <code class="bg-light p-2 rounded"><?= $cred['username'] ?></code>
                                </td>
                                <td>
                                    <span class="password-mask" data-pwd="<?= $cred['password'] ?>">
                                        ••••••••••••
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-outline-info toggle-pwd" data-pwd="<?= $cred['password'] ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Export Section -->
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Export Credentials</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Download credentials as CSV to distribute to members securely
                    </p>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" onclick="exportToCSV()">
                            <i class="fas fa-download mr-1"></i> Export as CSV
                        </button>
                        <button type="button" class="btn btn-info" onclick="window.print()">
                            <i class="fas fa-print mr-1"></i> Print List
                        </button>
                        <a href="<?= site_url('admin/settings') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="alert alert-warning">
                <h5 class="alert-heading">
                    <i class="fas fa-lightbulb mr-1"></i>Important Notes:
                </h5>
                <ul class="mb-0">
                    <li>✓ Each member has a unique username and password</li>
                    <li>✓ Passwords are encrypted and cannot be recovered if lost</li>
                    <li>✓ Send credentials via secure channels (SMS, Secure Email, In-Person)</li>
                    <li>✓ Members should change their password on first login</li>
                    <li>✓ Keep this list confidential - delete after distribution</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
$(document).on('click', '.toggle-pwd', function() {
    const $btn = $(this);
    const $pwd = $btn.closest('tr').find('.password-mask');
    const password = $pwd.data('pwd');
    
    if ($pwd.text() === '••••••••••••') {
        $pwd.text(password);
        $btn.find('i').removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        $pwd.text('••••••••••••');
        $btn.find('i').removeClass('fa-eye-slash').addClass('fa-eye');
    }
});

// Export to CSV
function exportToCSV() {
    const csvContent = [
        ['Member Code', 'First Name', 'Last Name', 'Phone', 'Email', 'Username', 'Password']
    ];
    
    <?php foreach ($credentials as $cred): ?>
    csvContent.push([
        '<?= addslashes($cred['member_code']) ?>',
        '<?= addslashes($cred['first_name']) ?>',
        '<?= addslashes($cred['last_name']) ?>',
        '<?= addslashes($cred['phone']) ?>',
        '<?= addslashes($cred['email'] ?? '') ?>',
        '<?= addslashes($cred['username']) ?>',
        '<?= addslashes($cred['password']) ?>'
    ]);
    <?php endforeach; ?>
    
    let csv = csvContent.map(row => 
        row.map(cell => `"${cell}"`).join(',')
    ).join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', 'member_credentials_<?= date('Y-m-d_H-i-s') ?>.csv');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    alert('CSV file downloaded successfully!');
}
</script>

<style>
    .password-mask {
        font-family: monospace;
        background: #f0f0f0;
        padding: 5px 10px;
        border-radius: 3px;
        letter-spacing: 2px;
    }
    
    @media print {
        .btn-group, .alert, .card-header {
            display: none;
        }
        .table {
            width: 100%;
        }
    }
</style>
