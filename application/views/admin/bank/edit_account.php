<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Edit Bank Account</h4>
            </div>
            <div class="card-body">
                <?php echo form_open('admin/bank/accounts/edit/' . $account->id); ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="account_name">Account Name *</label>
                            <input type="text" class="form-control" id="account_name" name="account_name"
                                   value="<?php echo set_value('account_name', $account->account_name); ?>" required>
                            <?php echo form_error('account_name', '<div class="text-danger">', '</div>'); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bank_name">Bank Name *</label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name"
                                   value="<?php echo set_value('bank_name', $account->bank_name); ?>" required>
                            <?php echo form_error('bank_name', '<div class="text-danger">', '</div>'); ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="branch_name">Branch Name</label>
                            <input type="text" class="form-control" id="branch_name" name="branch_name"
                                   value="<?php echo set_value('branch_name', $account->branch_name); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="account_number">Account Number *</label>
                            <input type="text" class="form-control" id="account_number" name="account_number"
                                   value="<?php echo set_value('account_number', $account->account_number); ?>" required>
                            <?php echo form_error('account_number', '<div class="text-danger">', '</div>'); ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ifsc_code">IFSC Code</label>
                            <input type="text" class="form-control" id="ifsc_code" name="ifsc_code"
                                   value="<?php echo set_value('ifsc_code', $account->ifsc_code); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="account_type">Account Type *</label>
                            <select class="form-control" id="account_type" name="account_type" required>
                                <option value="">Select Account Type</option>
                                <option value="current" <?php echo set_select('account_type', 'current', $account->account_type == 'current'); ?>>Current Account</option>
                                <option value="savings" <?php echo set_select('account_type', 'savings', $account->account_type == 'savings'); ?>>Savings Account</option>
                                <option value="cash" <?php echo set_select('account_type', 'cash', $account->account_type == 'cash'); ?>>Cash Account</option>
                            </select>
                            <?php echo form_error('account_type', '<div class="text-danger">', '</div>'); ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="opening_balance">Opening Balance</label>
                            <input type="number" class="form-control" id="opening_balance" name="opening_balance"
                                   value="<?php echo set_value('opening_balance', $account->opening_balance); ?>" step="0.01">
                            <?php echo form_error('opening_balance', '<div class="text-danger">', '</div>'); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Options</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                       <?php echo $account->is_active ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="is_primary" name="is_primary" value="1"
                                       <?php echo $account->is_primary ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_primary">
                                    Primary Account
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Update Account
                    </button>
                    <a href="<?php echo site_url('admin/bank/accounts'); ?>" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>