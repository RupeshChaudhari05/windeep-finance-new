<?php $reset_result = $this->session->flashdata('reset_result'); ?>
<?php if (!empty($reset_result)): ?>
<div class="card border-warning mb-3" id="resetResultCard">
    <div class="card-header bg-warning">
        <h3 class="card-title"><i class="fas fa-key mr-1"></i> Temporary password<?= empty($reset_result['single']) ? 's' : '' ?> &mdash; shown once</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-sm btn-dark" id="copyResetResult">
                <i class="fas fa-copy"></i> Copy
            </button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="$('#resetResultCard').remove();">
                <i class="fas fa-times"></i> Dismiss
            </button>
        </div>
    </div>
    <div class="card-body">
        <p class="text-danger mb-3">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Copy this now. It is not stored anywhere and will not be shown again once you leave this page.
            Each account must set its own password at next login.
        </p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0" id="resetResultTable">
                <thead class="thead-light">
                    <tr><th>Username</th><th>Name</th><th>Temporary password</th></tr>
                </thead>
                <tbody>
                <?php if (!empty($reset_result['single'])): ?>
                    <tr>
                        <td><?= html_escape($reset_result['username']) ?></td>
                        <td><?= html_escape($reset_result['name']) ?></td>
                        <td><code class="text-dark"><?= html_escape($reset_result['password']) ?></code></td>
                    </tr>
                <?php else: foreach ($reset_result['list'] as $r): ?>
                    <tr>
                        <td><?= html_escape($r['username']) ?></td>
                        <td><?= html_escape($r['name']) ?></td>
                        <td><code class="text-dark"><?= html_escape($r['password']) ?></code></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Admin Users Management -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users-cog mr-1"></i> Admin Users</h3>
        <div class="card-tools">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addUserModal">
                <i class="fas fa-plus"></i> Add User
            </button>
            <?php if (($this->session->userdata('admin_role') ?? '') === 'super_admin'): ?>
            <button class="btn btn-outline-danger btn-sm ml-1" data-toggle="modal" data-target="#resetAllModal">
                <i class="fas fa-key"></i> Reset All Passwords
            </button>
            <?php endif; ?>
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
                            <button class="btn btn-secondary btn-reset-password" data-id="<?= $user->id ?>" data-name="<?= html_escape($user->username . ' (' . $user->full_name . ')') ?>" title="Reset Password">
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
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" minlength="6" autocomplete="new-password">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="#password" title="Show / hide"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="form-group" id="confirmPasswordGroup">
                        <label>Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password_confirm" id="password_confirm" class="form-control" autocomplete="new-password">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="#password_confirm" title="Show / hide"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="resetPasswordForm" method="post" action="<?= site_url('admin/settings/reset_admin_password') ?>">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <input type="hidden" name="id" id="reset_user_id">
                <input type="hidden" name="generate" id="reset_generate" value="0">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-key mr-1"></i> Reset Password &mdash; <span id="reset_user_name"></span></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="custom-control custom-radio mb-2">
                        <input type="radio" id="mode_generate" name="reset_mode" class="custom-control-input" value="generate" checked>
                        <label class="custom-control-label" for="mode_generate">
                            <strong>Generate a temporary password</strong>
                            <small class="d-block text-muted">Recommended. Shown once here, then the user must set their own at next login.</small>
                        </label>
                    </div>
                    <div class="custom-control custom-radio mb-3">
                        <input type="radio" id="mode_manual" name="reset_mode" class="custom-control-input" value="manual">
                        <label class="custom-control-label" for="mode_manual">
                            <strong>Type a password myself</strong>
                        </label>
                    </div>

                    <div id="manualFields" style="display:none;">
                        <div class="form-group">
                            <label>New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="new_password" class="form-control" minlength="8" autocomplete="new-password">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="#new_password" title="Show / hide">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Minimum 8 characters.</small>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" autocomplete="new-password">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="#confirm_password" title="Show / hide">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border mb-0" style="font-size:12px;">
                        <i class="fas fa-shield-alt text-muted mr-1"></i>
                        Existing passwords are stored as one-way hashes and cannot be displayed &mdash; they can only be replaced.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset ALL Passwords Modal -->
<div class="modal fade" id="resetAllModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= site_url('admin/settings/reset_all_admin_passwords') ?>">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-1"></i> Reset ALL Admin Passwords</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Every active admin account gets a new temporary password and must set its own at next login.</p>
                    <p class="text-danger mb-3"><strong>Anyone currently signed in will need the new password on their next login.</strong></p>

                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" class="custom-control-input" id="include_self" name="include_self" value="1">
                        <label class="custom-control-label" for="include_self">Include my own account as well</label>
                    </div>

                    <div class="form-group mb-0">
                        <label>Type <code>RESET ALL</code> to confirm</label>
                        <input type="text" name="confirm_text" class="form-control" placeholder="RESET ALL" autocomplete="off" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-key"></i> Reset All Passwords</button>
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

    // Generate vs type-your-own
    $('input[name="reset_mode"]').on('change', function() {
        var manual = $('#mode_manual').is(':checked');
        $('#manualFields').toggle(manual);
        $('#reset_generate').val(manual ? '0' : '1');
        $('#new_password, #confirm_password').prop('required', manual);
    }).trigger('change');

    // Eye icon — reveals what is being TYPED. An existing password is a one-way
    // hash and can never be shown, only replaced.
    $(document).on('click', '.toggle-pw', function() {
        var $inp = $($(this).data('target'));
        var show = $inp.attr('type') === 'password';
        $inp.attr('type', show ? 'text' : 'password');
        $(this).find('i').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show);
    });

    // Copy the one-time password list
    $(document).on('click', '#copyResetResult', function() {
        var lines = [];
        $('#resetResultTable tbody tr').each(function() {
            var c = $(this).find('td');
            lines.push($(c[0]).text().trim() + '	' + $(c[1]).text().trim() + '	' + $(c[2]).text().trim());
        });
        var text = 'Username	Name	Temporary password
' + lines.join('
');
        var $t = $('<textarea>').val(text).css({position:'fixed', opacity:0}).appendTo('body');
        $t[0].select();
        try { document.execCommand('copy'); toastr.success('Copied ' + lines.length + ' password(s)'); }
        catch (e) { alert(text); }
        $t.remove();
    });

    $('.btn-reset-password').click(function() {
        $('#reset_user_id').val($(this).data('id'));
        $('#reset_user_name').text($(this).data('name') || '');
        $('#mode_generate').prop('checked', true).trigger('change');
        $('#new_password, #confirm_password').val('');
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
