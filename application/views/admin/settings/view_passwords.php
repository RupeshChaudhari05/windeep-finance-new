<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2 class="mb-0">
                <i class="fas fa-key mr-2"></i>Member Password Status
            </h2>
            <small class="text-muted">Last refreshed: <?= date('d M Y H:i:s') ?></small>
            <div class="mt-2">
                <span class="badge badge-info">Note:</span>
                This page shows whether a password is set or not. Existing passwords are stored as bcrypt hashes and cannot be decrypted back to plain text.
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Members</span>
                    <span class="info-box-number"><?= $total_members ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Passwords Set</span>
                    <span class="info-box-number"><?= $password_set ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Not Set</span>
                    <span class="info-box-number"><?= $password_not_set ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-percent"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Coverage</span>
                    <span class="info-box-number"><?= $total_members > 0 ? round(($password_set / $total_members) * 100) : 0 ?>%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card card-outline card-primary mb-3">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filter</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label><strong>Show:</strong></label>
                        <select id="passwordFilter" class="form-control">
                            <option value="all">All Members (<?= $total_members ?>)</option>
                            <option value="set">With Passwords (<?= $password_set ?>)</option>
                            <option value="not_set">Without Passwords (<?= $password_not_set ?>)</option>
                            <option value="active">Active Members</option>
                            <option value="inactive">Inactive Members</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label><strong>Search:</strong></label>
                        <input type="text" id="searchMembers" class="form-control" placeholder="Search by code, name, or phone...">
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <label>&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-success" onclick="exportToCSV()">
                            <i class="fas fa-download mr-1"></i> Export CSV
                        </button>
                        <a href="<?= site_url('admin/settings#member_passwords') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($generated_credentials)): ?>
    <div class="card card-outline card-success mb-3">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-lock-open mr-1"></i>Recently Generated Passwords - ALL <?= $generated_password_count ?> Members</h3>
            <div class="card-tools">
                <span class="badge badge-success"><?= $generated_password_count ?> generated</span>
                <?php if ($generated_password_time): ?><small class="text-muted ml-2">at <?= $generated_password_time ?></small><?php endif; ?>
                <button type="button" class="btn btn-xs btn-secondary ml-2" onclick="clearGeneratedPasswords()">
                    <i class="fas fa-times mr-1"></i>Clear
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="60">#</th>
                            <th>Member Code</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Username</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($generated_credentials as $index => $credential): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><span class="badge badge-primary"><?= $credential['member_code'] ?></span></td>
                            <td><?= $credential['first_name'] . ' ' . $credential['last_name'] ?></td>
                            <td><?= $credential['email'] ?></td>
                            <td><a href="tel:<?= $credential['phone'] ?>"><?= $credential['phone'] ?></a></td>
                            <td><code class="bg-light p-2 rounded"><?= $credential['username'] ?></code></td>
                            <td>
                                <code class="bg-dark text-white p-2 rounded" style="cursor: pointer;" title="Click to copy" onclick="copyPassword('<?= $credential['password'] ?>')">
                                    <?= $credential['password'] ?>
                                </code>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <p class="text-muted mb-2"><strong>ℹ️ Important:</strong></p>
            <ul class="text-muted mb-0">
                <li>These passwords are displayed here for distribution only</li>
                <li>Click on any password to copy it to clipboard</li>
                <li>Export to CSV for records</li>
                <li>Click "Clear" button to remove this section after distribution</li>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Members Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-key mr-1"></i>Member Password Status (<span id="visibleCount"><?= count($members) ?></span> members)
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" onclick="bulkResetConfirm()">
                    <i class="fas fa-redo mr-1"></i>Reset All Passwords
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="membersTable">
                    <thead class="thead-light">
                        <tr>
                            <th width="60">#</th>
                            <th>Member Code</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Member Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($members as $key => $member): ?>
                        <tr class="member-row" data-has-password="<?= $member['has_password'] ? '1' : '0' ?>" 
                            data-status="<?= $member['status'] ?>"
                            data-search="<?= strtolower($member['member_code'] . ' ' . $member['first_name'] . ' ' . $member['last_name'] . ' ' . $member['phone']) ?>">
                            <td><?= $key + 1 ?></td>
                            <td>
                                <span class="badge badge-primary"><?= $member['member_code'] ?></span>
                            </td>
                            <td><?= $member['first_name'] . ' ' . $member['last_name'] ?></td>
                            <td>
                                <a href="tel:<?= $member['phone'] ?>" class="text-primary">
                                    <?= $member['phone'] ?>
                                </a>
                            </td>
                            <td>
                                <code class="bg-light p-2 rounded" style="font-size: 0.85rem;"><?= $member['username'] ?></code>
                            </td>
                            <td>
                                <?php if ($member['plain_password']): ?>
                                    <!-- Recently generated password - show plaintext -->
                                    <code class="bg-dark text-white p-2 rounded" style="font-size: 0.85rem; cursor: pointer;" title="Click to copy" onclick="copyPassword('<?= $member['plain_password'] ?>')">
                                        <?= $member['plain_password'] ?>
                                    </code>
                                <?php elseif ($member['has_password']): ?>
                                    <!-- Password exists but hashed - show masked version -->
                                    <code class="bg-secondary text-white p-2 rounded" style="font-size: 0.85rem;">
                                        ••••••••••••
                                    </code>
                                <?php else: ?>
                                    <!-- No password - show alert -->
                                    <code class="bg-danger text-white p-2 rounded" style="font-size: 0.85rem;">
                                        NOT SET
                                    </code>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $member['status'] == 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($member['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-warning btn-xs" onclick="resetPasswordConfirm(<?= $member['id'] ?>, '<?= $member['member_code'] ?>')">
                                    <i class="fas fa-key mr-1"></i>Reset
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="resetPasswordModalLabel">
                        <i class="fas fa-lock-open mr-2"></i>Password Reset Successfully
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Share this password with the member.</strong> They should change it after first login.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Member Code:</strong></label>
                                <input type="text" class="form-control" id="resetMemberCode" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Member Name:</strong></label>
                                <input type="text" class="form-control" id="resetMemberName" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Username:</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="resetUsername" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('resetUsername')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>New Password:</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-dark text-white font-weight-bold" id="resetNewPassword" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('resetNewPassword')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-warning mt-3">
                        <strong><i class="fas fa-exclamation-triangle mr-2"></i>Important:</strong>
                        <ul class="mb-0 mt-2">
                            <li>This password will only be displayed once. Save it securely.</li>
                            <li>Refresh the page to clear these values.</li>
                            <li>Member should change this password after first login.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printPassword()">
                        <i class="fas fa-print mr-1"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Reset Confirmation Modal -->
    <div class="modal fade" id="bulkResetModal" tabindex="-1" role="dialog" aria-labelledby="bulkResetModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bulkResetModalLabel">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Reset All Member Passwords
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>⚠️ Warning!</strong><br>
                        This will reset passwords for <strong>all <?= $total_members ?> members</strong>. 
                        A new page will display all new passwords for you to distribute.
                    </div>
                    <p>Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmBulkReset()">
                        <i class="fas fa-redo mr-1"></i>Yes, Reset All
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Copy password function
function copyPassword(password) {
    const temp = $('<input>');
    $('body').append(temp);
    temp.val(password).select();
    document.execCommand('copy');
    temp.remove();
    
    Swal.fire({
        title: 'Copied!',
        text: 'Password copied to clipboard',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
    });
}

// Clear generated passwords from session
function clearGeneratedPasswords() {
    Swal.fire({
        title: 'Clear Generated Passwords?',
        text: 'This will hide the recently generated passwords section. You can still export to CSV if needed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Clear'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?= site_url('admin/settings/clear_generated_passwords') ?>',
                type: 'POST',
                success: function() {
                    location.reload();
                }
            });
        }
    });
}

// Filter by password status
$(document).on('change', '#passwordFilter', function() {
    const filter = $(this).val();
    let visibleCount = 0;
    
    $('#tableBody tr').each(function() {
        const hasPassword = $(this).data('has-password') == '1';
        const status = $(this).data('status');
        let show = false;
        
        if (filter === 'all') {
            show = true;
        } else if (filter === 'set') {
            show = hasPassword;
        } else if (filter === 'not_set') {
            show = !hasPassword;
        } else if (filter === 'active') {
            show = status === 'active';
        } else if (filter === 'inactive') {
            show = status !== 'active';
        }
        
        if (show) {
            $(this).show();
            visibleCount++;
        } else {
            $(this).hide();
        }
    });
    
    $('#visibleCount').text(visibleCount);
});

// Search members
$(document).on('keyup', '#searchMembers', function() {
    const query = $(this).val().toLowerCase();
    let visibleCount = 0;
    
    $('#tableBody tr').each(function() {
        const searchText = $(this).data('search');
        if (searchText.includes(query)) {
            $(this).show();
            visibleCount++;
        } else {
            $(this).hide();
        }
    });
    
    $('#visibleCount').text(visibleCount);
});

// Export to CSV
function exportToCSV() {
    <?php if (!empty($generated_credentials)): ?>
    const csvContent = [
        ['Member Code', 'First Name', 'Last Name', 'Username', 'Email', 'Phone', 'New Password']
    ];
    <?php foreach ($generated_credentials as $credential): ?>
    csvContent.push([
        '<?= addslashes($credential['member_code']) ?>',
        '<?= addslashes($credential['first_name']) ?>',
        '<?= addslashes($credential['last_name']) ?>',
        '<?= addslashes($credential['username']) ?>',
        '<?= addslashes($credential['email'] ?? '') ?>',
        '<?= addslashes($credential['phone']) ?>',
        '<?= addslashes($credential['password']) ?>'
    ]);
    <?php endforeach; ?>
    <?php $exportFileName = 'member_passwords_' . date('Y-m-d_H-i-s') . '.csv'; ?>
    <?php else: ?>
    const csvContent = [
        ['Member Code', 'First Name', 'Last Name', 'Phone', 'Email', 'Username', 'Password', 'Member Status']
    ];
    <?php foreach ($members as $member): ?>
    csvContent.push([
        '<?= addslashes($member['member_code']) ?>',
        '<?= addslashes($member['first_name']) ?>',
        '<?= addslashes($member['last_name']) ?>',
        '<?= addslashes($member['phone']) ?>',
        '<?= addslashes($member['email'] ?? '') ?>',
        '<?= addslashes($member['username']) ?>',
        '<?= $member['plain_password'] ? addslashes($member['plain_password']) : ($member['has_password'] ? '••••••••••••' : 'NOT SET') ?>',
        '<?= $member['status'] ?>'
    ]);
    <?php endforeach; ?>
    <?php $exportFileName = 'member_passwords_' . date('Y-m-d_H-i-s') . '.csv'; ?>
    <?php endif; ?>

    let csv = csvContent.map(row => 
        row.map(cell => `"${cell}"`).join(',')
    ).join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', '<?= $exportFileName ?>');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Reset password for individual member
function resetPasswordConfirm(memberId, memberCode) {
    Swal.fire({
        title: 'Reset Password?',
        text: `Reset password for member ${memberCode}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Reset Password'
    }).then((result) => {
        if (result.isConfirmed) {
            resetMemberPassword(memberId);
        }
    });
}

// AJAX function to reset member password
function resetMemberPassword(memberId) {
    $.ajax({
        url: '<?= site_url('admin/settings/reset_member_password') ?>',
        type: 'POST',
        dataType: 'json',
        data: {
            member_id: memberId
        },
        beforeSend: function() {
            $('body').css('cursor', 'wait');
        },
        success: function(response) {
            if (response.success) {
                // Update the table to show password plaintext
                const row = $('button[onclick*="resetPasswordConfirm(' + memberId + '"]').closest('tr');
                const passwordCell = row.find('td:nth-child(6)');
                
                const plainPassword = response.data.plain_password;
                passwordCell.html(`
                    <code class="bg-dark text-white p-2 rounded" style="font-size: 0.85rem; cursor: pointer;" title="Click to copy" onclick="copyPassword('${plainPassword}')">
                        <i class="fas fa-eye-slash mr-1"></i>${plainPassword}
                    </code>
                `);
                
                // Show modal with new password
                $('#resetMemberCode').val(response.data.member_code);
                $('#resetMemberName').val(response.data.member_name);
                $('#resetUsername').val(response.data.username);
                $('#resetNewPassword').val(response.data.plain_password);
                
                $('#resetPasswordModal').modal('show');
                
                Swal.fire('Success!', 'Password has been reset successfully.', 'success');
            } else {
                Swal.fire('Error', response.message || 'Error resetting password', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'An error occurred while resetting the password', 'error');
        },
        complete: function() {
            $('body').css('cursor', 'default');
        }
    });
}

// Bulk reset confirmation
function bulkResetConfirm() {
    $('#bulkResetModal').modal('show');
}

// Confirm bulk reset
function confirmBulkReset() {
    window.location.href = '<?= site_url('admin/settings/reset_all_passwords') ?>';
}

// Copy to clipboard
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    document.execCommand('copy');
    
    Swal.fire({
        title: 'Copied!',
        text: 'Copied to clipboard',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
    });
}

// Print password
function printPassword() {
    const memberCode = document.getElementById('resetMemberCode').value;
    const memberName = document.getElementById('resetMemberName').value;
    const username = document.getElementById('resetUsername').value;
    const password = document.getElementById('resetNewPassword').value;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Member Login Credentials - ${memberCode}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .container { max-width: 600px; margin: 0 auto; border: 2px solid #333; padding: 30px; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { margin: 0; color: #333; }
                .header p { margin: 5px 0; color: #666; }
                .content { line-height: 2; }
                .label { font-weight: bold; color: #333; }
                .value { background-color: #f0f0f0; padding: 10px; margin: 10px 0; font-family: monospace; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #ccc; padding-top: 10px; }
                .warning { background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Member Login Credentials</h1>
                    <p>Generated on ${new Date().toLocaleString()}</p>
                </div>
                <div class="content">
                    <div><span class="label">Member Code:</span> ${memberCode}</div>
                    <div><span class="label">Member Name:</span> ${memberName}</div>
                    <div><span class="label">Username:</span></div>
                    <div class="value">${username}</div>
                    <div><span class="label">Password:</span></div>
                    <div class="value">${password}</div>
                </div>
                <div class="warning">
                    <strong>⚠️ Important:</strong>
                    <ul style="margin: 10px 0;">
                        <li>Keep this document safe and secure.</li>
                        <li>Member should change this password after first login.</li>
                        <li>Never share password via email or unsecured channels.</li>
                    </ul>
                </div>
                <div class="footer">
                    <p>This is a confidential document. Destroy after member receives credentials.</p>
                </div>
            </div>
        </body>
        </html>
    `);
    printWindow.print();
}
</script>

<style>
    @media print {
        .btn-group, .card-header, #passwordFilter, #searchMembers {
            display: none;
        }
    }
    
    .badge-lg {
        font-size: 0.9rem !important;
        padding: 0.5rem 0.75rem !important;
    }
    
    code.bg-dark {
        user-select: all;
    }
</style>
