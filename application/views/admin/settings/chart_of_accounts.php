<!-- Chart of Accounts -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-sitemap mr-1"></i> Chart of Accounts</h3>
        <div class="card-tools">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addAccountModal">
                <i class="fas fa-plus"></i> Add Account
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="coaTable">
            <thead class="thead-light">
                <tr>
                    <th width="100">Code</th>
                    <th>Account Name</th>
                    <th>Account Type</th>
                    <th>Parent Account</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th width="100">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): ?>
                <tr class="<?= $account->parent_id ? '' : 'table-light font-weight-bold' ?>">
                    <td><code><?= $account->account_code ?></code></td>
                    <td>
                        <?= $account->parent_id ? '<span class="ml-3">↳</span> ' : '' ?>
                        <?= $account->account_name ?>
                    </td>
                    <td>
                        <?php 
                        $type_badges = [
                            'asset' => 'success',
                            'liability' => 'danger',
                            'equity' => 'primary',
                            'income' => 'info',
                            'expense' => 'warning'
                        ];
                        ?>
                        <span class="badge badge-<?= $type_badges[$account->account_type] ?? 'secondary' ?>">
                            <?= ucfirst($account->account_type) ?>
                        </span>
                    </td>
                    <td><?= $account->parent_name ?? '-' ?></td>
                    <td><small><?= $account->description ?? '-' ?></small></td>
                    <td>
                        <?php if ($account->is_active): ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i></span>
                        <?php else: ?>
                        <span class="badge badge-secondary"><i class="fas fa-times"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-warning btn-edit" data-account='<?= json_encode($account) ?>' title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if (!$account->is_system): ?>
                            <button class="btn btn-danger btn-delete" data-id="<?= $account->id ?>" title="Delete">
                                <i class="fas fa-trash"></i>
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

<!-- Account Type Summary -->
<div class="row mt-4">
    <?php foreach (['asset' => 'success', 'liability' => 'danger', 'equity' => 'primary', 'income' => 'info', 'expense' => 'warning'] as $type => $color): ?>
    <div class="col">
        <div class="info-box bg-<?= $color ?>">
            <span class="info-box-icon"><i class="fas fa-<?= $type == 'asset' ? 'wallet' : ($type == 'liability' ? 'credit-card' : ($type == 'equity' ? 'balance-scale' : ($type == 'income' ? 'arrow-down' : 'arrow-up'))) ?>"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><?= ucfirst($type) ?></span>
                <span class="info-box-number"><?= $type_counts[$type] ?? 0 ?> Accounts</span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add/Edit Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="accountForm" method="post" action="<?= site_url('admin/settings/save_coa') ?>">
                <input type="hidden" name="id" id="account_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-sitemap mr-1"></i> <span id="modalTitle">Add</span> Account</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Account Code <span class="text-danger">*</span></label>
                                <input type="text" name="account_code" id="account_code" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Account Name <span class="text-danger">*</span></label>
                                <input type="text" name="account_name" id="account_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Account Type <span class="text-danger">*</span></label>
                                <select name="account_type" id="account_type" class="form-control" required>
                                    <option value="asset">Asset</option>
                                    <option value="liability">Liability</option>
                                    <option value="equity">Equity</option>
                                    <option value="income">Income</option>
                                    <option value="expense">Expense</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Parent Account</label>
                                <select name="parent_id" id="parent_id" class="form-control">
                                    <option value="">-- None (Root Account) --</option>
                                    <?php foreach ($parent_accounts ?? $accounts as $parent): ?>
                                    <?php if (!$parent->parent_id): ?>
                                    <option value="<?= $parent->id ?>"><?= $parent->account_code ?> - <?= $parent->account_name ?></option>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#coaTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 50,
        "paging": false
    });
    
    // Edit account
    $('.btn-edit').click(function() {
        var account = $(this).data('account');
        $('#modalTitle').text('Edit');
        $('#account_id').val(account.id);
        $('#account_code').val(account.account_code);
        $('#account_name').val(account.account_name);
        $('#account_type').val(account.account_type);
        $('#parent_id').val(account.parent_id);
        $('#description').val(account.description);
        $('#is_active').prop('checked', account.is_active == 1);
        $('#addAccountModal').modal('show');
    });
    
    // Reset modal
    $('#addAccountModal').on('hidden.bs.modal', function() {
        $('#accountForm')[0].reset();
        $('#account_id').val('');
        $('#modalTitle').text('Add');
        $('#is_active').prop('checked', true);
    });
    
    // Delete account
    $('.btn-delete').click(function() {
        var accountId = $(this).data('id');
        if (confirm('Are you sure you want to delete this account? This cannot be undone.')) {
            $.post('<?= site_url('admin/settings/delete_coa') ?>', {id: accountId}, function(response) {
                if (response.success) {
                    toastr.success('Account deleted successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Cannot delete account');
                }
            }, 'json');
        }
    });
});
</script>
