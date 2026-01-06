<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Bank Accounts</h4>
                <a href="<?php echo site_url('admin/bank/accounts/create'); ?>" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> Add Bank Account
                </a>
            </div>
            <div class="card-body">
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $this->session->flashdata('success'); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $this->session->flashdata('error'); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Account Name</th>
                                <th>Bank Name</th>
                                <th>Account Number</th>
                                <th>Type</th>
                                <th>Current Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bank_accounts)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No bank accounts found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bank_accounts as $account): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($account->account_name); ?>
                                            <?php if ($account->is_primary): ?>
                                                <span class="badge badge-primary">Primary</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($account->bank_name); ?></td>
                                        <td><?php echo htmlspecialchars($account->account_number); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo ucfirst($account->account_type); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($account->current_balance, 2); ?></td>
                                        <td>
                                            <?php if ($account->is_active): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo site_url('admin/bank/accounts/edit/' . $account->id); ?>"
                                               class="btn btn-sm btn-warning">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
                                            <button type="button"
                                                    class="btn btn-sm <?php echo $account->is_active ? 'btn-secondary' : 'btn-success'; ?> toggle-status"
                                                    data-id="<?php echo $account->id; ?>"
                                                    data-status="<?php echo $account->is_active; ?>">
                                                <i class="fa fa-<?php echo $account->is_active ? 'ban' : 'check'; ?>"></i>
                                                <?php echo $account->is_active ? 'Deactivate' : 'Activate'; ?>
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
    </div>
</div>

<script>
$(document).ready(function() {
    $('.toggle-status').on('click', function() {
        var btn = $(this);
        var accountId = btn.data('id');
        var currentStatus = btn.data('status');

        if (confirm('Are you sure you want to ' + (currentStatus ? 'deactivate' : 'activate') + ' this account?')) {
            $.ajax({
                url: '<?php echo site_url('admin/bank/accounts/toggle/'); ?>' + accountId,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while updating account status.');
                }
            });
        }
    });
});
</script>