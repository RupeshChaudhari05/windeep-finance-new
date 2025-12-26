<!-- Admin Users Management -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users-cog mr-1"></i> Admin Users</h3>
        <div class="card-tools">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addUserModal">
                <i class="fas fa-plus"></i> Add User
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0" id="usersTable">
            <thead class="thead-light">
                <tr>
                    <th width="50">#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($users as $user): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <strong><?= $user->full_name ?></strong>
                        <?php if ($user->id == $this->session->userdata('admin_id')): ?>
                        <span class="badge badge-info ml-1">You</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $user->email ?></td>
                    <td><?= $user->phone ?: '-' ?></td>
                    <td>
                        <span class="badge badge-<?= $user->role == 'super_admin' ? 'danger' : ($user->role == 'admin' ? 'primary' : 'secondary') ?>">
                            <?= ucwords(str_replace('_', ' ', $user->role)) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user->is_active): ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i> Active</span>
                        <?php else: ?>
                        <span class="badge badge-danger"><i class="fas fa-times"></i> Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $user->last_login ? format_date_time($user->last_login, 'd M Y H:i') : 'Never' ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-info btn-edit" data-user='<?= json_encode($user) ?>' title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($user->id != $this->session->userdata('admin_id')): ?>
                            <button class="btn btn-<?= $user->is_active ? 'warning' : 'success' ?> btn-toggle" 
                                    data-id="<?= $user->id ?>" 
                                    data-status="<?= $user->is_active ?>"
                                    title="<?= $user->is_active ? 'Deactivate' : 'Activate' ?>">
                                <i class="fas fa-<?= $user->is_active ? 'ban' : 'check' ?>"></i>
                            </button>
                            <button class="btn btn-secondary btn-reset-password" data-id="<?= $user->id ?>" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="userForm" method="post" action="<?= site_url('admin/settings/create_admin') ?>">
                <input type="hidden" name="id" id="user_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus mr-1"></i> <span id="modalTitle">Add</span> Admin User</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" id="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Role <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="form-group" id="passwordGroup">
                        <label>Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control" minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="form-group" id="confirmPasswordGroup">
                        <label>Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form id="resetPasswordForm" method="post" action="<?= site_url('admin/settings/reset_admin_password') ?>">
                <input type="hidden" name="id" id="reset_user_id">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-key mr-1"></i> Reset Password</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>New Password <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25
    });
    
    // Edit user
    $('.btn-edit').click(function() {
        var user = $(this).data('user');
        $('#modalTitle').text('Edit');
        $('#user_id').val(user.id);
        $('#full_name').val(user.full_name);
        $('#email').val(user.email);
        $('#phone').val(user.phone);
        $('#role').val(user.role);
        $('#passwordGroup, #confirmPasswordGroup').hide();
        $('#password, #password_confirm').prop('required', false);
        $('#addUserModal').modal('show');
    });
    
    // Reset modal
    $('#addUserModal').on('hidden.bs.modal', function() {
        $('#userForm')[0].reset();
        $('#user_id').val('');
        $('#modalTitle').text('Add');
        $('#passwordGroup, #confirmPasswordGroup').show();
        $('#password, #password_confirm').prop('required', true);
    });
    
    // Toggle status
    $('.btn-toggle').click(function() {
        var userId = $(this).data('id');
        var status = $(this).data('status') == 1 ? 0 : 1;
        var action = status == 1 ? 'activate' : 'deactivate';
        
        if (confirm('Are you sure you want to ' + action + ' this user?')) {
            $.post('<?= site_url('admin/settings/toggle_admin_status') ?>', {id: userId, is_active: status}, function(response) {
                if (response.success) {
                    toastr.success('User ' + action + 'd successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Operation failed');
                }
            }, 'json');
        }
    });
    
    // Reset password
    $('.btn-reset-password').click(function() {
        $('#reset_user_id').val($(this).data('id'));
        $('#resetPasswordModal').modal('show');
    });
    
    // Validate password match
    $('#userForm, #resetPasswordForm').submit(function(e) {
        var form = $(this);
        var pw = form.find('input[name="password"], input[name="new_password"]').val();
        var confirm = form.find('input[name="password_confirm"], input[name="confirm_password"]').val();
        
        if (pw && pw !== confirm) {
            e.preventDefault();
            toastr.error('Passwords do not match');
            return false;
        }
    });
});
</script>
