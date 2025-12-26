<!-- Bank Accounts Management -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-university mr-1"></i> Bank Accounts</h3>
        <div class="card-tools">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addAccountModal">
                <i class="fas fa-plus"></i> Add Account
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0" id="accountsTable">
            <thead class="thead-light">
                <tr>
                    <th width="50">#</th>
                    <th>Bank Name</th>
                    <th>Account Name</th>
                    <th>Account Number</th>
                    <th>Branch</th>
                    <th>Account Type</th>
                    <th>Current Balance</th>
                    <th>Status</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($accounts as $account): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= $account->bank_name ?></strong></td>
                    <td><?= $account->account_name ?></td>
                    <td><code><?= $account->account_number ?></code></td>
                    <td><?= $account->branch ?: '-' ?></td>
                    <td>
                        <span class="badge badge-<?= $account->account_type == 'current' ? 'primary' : 'info' ?>">
                            <?= ucfirst($account->account_type) ?>
                        </span>
                    </td>
                    <td>
                        <strong class="text-<?= $account->current_balance >= 0 ? 'success' : 'danger' ?>">
                            <?= number_format($account->current_balance, 2) ?>
                        </strong>
                    </td>
                    <td>
                        <?php if ($account->is_active): ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i> Active</span>
                        <?php else: ?>
                        <span class="badge badge-danger"><i class="fas fa-times"></i> Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= site_url('admin/bank/transactions/' . $account->id) ?>" class="btn btn-info" title="Transactions">
                                <i class="fas fa-list"></i>
                            </a>
                            <button class="btn btn-warning btn-edit" data-account='<?= json_encode($account) ?>' title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-<?= $account->is_active ? 'secondary' : 'success' ?> btn-toggle" 
                                    data-id="<?= $account->id ?>" 
                                    data-status="<?= $account->is_active ?>"
                                    title="<?= $account->is_active ? 'Deactivate' : 'Activate' ?>">
                                <i class="fas fa-<?= $account->is_active ? 'ban' : 'check' ?>"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="bg-light">
                    <th colspan="6" class="text-right">Total Balance:</th>
                    <th class="text-success">
                        <?= number_format(array_sum(array_column($accounts, 'current_balance')), 2) ?>
                    </th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Add/Edit Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="accountForm" method="post" action="<?= site_url('admin/settings/save_bank_account') ?>">
                <input type="hidden" name="id" id="account_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-university mr-1"></i> <span id="modalTitle">Add</span> Bank Account</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" id="bank_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Account Name <span class="text-danger">*</span></label>
                        <input type="text" name="account_name" id="account_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Account Number <span class="text-danger">*</span></label>
                        <input type="text" name="account_number" id="account_number" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Branch</label>
                                <input type="text" name="branch" id="branch" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Account Type <span class="text-danger">*</span></label>
                                <select name="account_type" id="account_type" class="form-control" required>
                                    <option value="savings">Savings</option>
                                    <option value="current">Current</option>
                                    <option value="fixed_deposit">Fixed Deposit</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Opening Balance</label>
                        <input type="number" name="opening_balance" id="opening_balance" class="form-control" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label>IFSC Code</label>
                        <input type="text" name="ifsc_code" id="ifsc_code" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
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
    $('#accountsTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25
    });
    
    // Edit account
    $('.btn-edit').click(function() {
        var account = $(this).data('account');
        $('#modalTitle').text('Edit');
        $('#account_id').val(account.id);
        $('#bank_name').val(account.bank_name);
        $('#account_name').val(account.account_name);
        $('#account_number').val(account.account_number);
        $('#branch').val(account.branch);
        $('#account_type').val(account.account_type);
        $('#opening_balance').val(account.opening_balance);
        $('#ifsc_code').val(account.ifsc_code);
        $('#notes').val(account.notes);
        $('#addAccountModal').modal('show');
    });
    
    // Reset modal
    $('#addAccountModal').on('hidden.bs.modal', function() {
        $('#accountForm')[0].reset();
        $('#account_id').val('');
        $('#modalTitle').text('Add');
    });
    
    // Toggle status
    $('.btn-toggle').click(function() {
        var accountId = $(this).data('id');
        var status = $(this).data('status') == 1 ? 0 : 1;
        var action = status == 1 ? 'activate' : 'deactivate';
        
        if (confirm('Are you sure you want to ' + action + ' this bank account?')) {
            $.post('<?= site_url('admin/settings/toggle_bank_account') ?>', {id: accountId, is_active: status}, function(response) {
                if (response.success) {
                    toastr.success('Bank account ' + action + 'd successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Operation failed');
                }
            }, 'json');
        }
    });
});
</script>
