<!-- Accounting Settings -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calculator mr-1"></i> Accounting Configuration</h3>
            </div>
            <form method="post" action="<?= site_url('admin/settings/update') ?>">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <div class="card-body">
                    <h5 class="text-primary mb-3"><i class="fas fa-book mr-1"></i> Default Accounts</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Cash Account</label>
                                <select name="settings[default_cash_account]" class="form-control">
                                    <option value="">Select Account</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc->id ?>" <?= ($settings['default_cash_account'] ?? '') == $acc->id ? 'selected' : '' ?>>
                                        <?= $acc->account_code ?> - <?= $acc->account_name ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Default account for cash transactions</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bank Account</label>
                                <select name="settings[default_bank_account]" class="form-control">
                                    <option value="">Select Account</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc->id ?>" <?= ($settings['default_bank_account'] ?? '') == $acc->id ? 'selected' : '' ?>>
                                        <?= $acc->account_code ?> - <?= $acc->account_name ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Default account for bank transactions</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Loan Portfolio Account</label>
                                <select name="settings[loan_portfolio_account]" class="form-control">
                                    <option value="">Select Account</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc->id ?>" <?= ($settings['loan_portfolio_account'] ?? '') == $acc->id ? 'selected' : '' ?>>
                                        <?= $acc->account_code ?> - <?= $acc->account_name ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Asset account for loan disbursements</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Security Deposit Liability Account</label>
                                <select name="settings[savings_liability_account]" class="form-control">
                                    <option value="">Select Account</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc->id ?>" <?= ($settings['savings_liability_account'] ?? '') == $acc->id ? 'selected' : '' ?>>
                                        <?= $acc->account_code ?> - <?= $acc->account_name ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Liability account for member security deposits</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Interest Income Account</label>
                                <select name="settings[interest_income_account]" class="form-control">
                                    <option value="">Select Account</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc->id ?>" <?= ($settings['interest_income_account'] ?? '') == $acc->id ? 'selected' : '' ?>>
                                        <?= $acc->account_code ?> - <?= $acc->account_name ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Income account for loan interest</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fine Income Account</label>
                                <select name="settings[fine_income_account]" class="form-control">
                                    <option value="">Select Account</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc->id ?>" <?= ($settings['fine_income_account'] ?? '') == $acc->id ? 'selected' : '' ?>>
                                        <?= $acc->account_code ?> - <?= $acc->account_name ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Income account for fines and penalties</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Processing Fee Income Account</label>
                                <select name="settings[processing_fee_account]" class="form-control">
                                    <option value="">Select Account</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc->id ?>" <?= ($settings['processing_fee_account'] ?? '') == $acc->id ? 'selected' : '' ?>>
                                        <?= $acc->account_code ?> - <?= $acc->account_name ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Interest Expense Account</label>
                                <select name="settings[interest_expense_account]" class="form-control">
                                    <option value="">Select Account</option>
                                    <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc->id ?>" <?= ($settings['interest_expense_account'] ?? '') == $acc->id ? 'selected' : '' ?>>
                                        <?= $acc->account_code ?> - <?= $acc->account_name ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Expense account for security deposit interest paid</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h5 class="text-primary mb-3"><i class="fas fa-cogs mr-1"></i> Accounting Options</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Accounting Method</label>
                                <select name="settings[accounting_method]" class="form-control">
                                    <option value="cash" <?= ($settings['accounting_method'] ?? 'accrual') == 'cash' ? 'selected' : '' ?>>Cash Basis</option>
                                    <option value="accrual" <?= ($settings['accounting_method'] ?? 'accrual') == 'accrual' ? 'selected' : '' ?>>Accrual Basis</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Interest Recognition</label>
                                <select name="settings[interest_recognition]" class="form-control">
                                    <option value="on_receipt" <?= ($settings['interest_recognition'] ?? '') == 'on_receipt' ? 'selected' : '' ?>>On Receipt</option>
                                    <option value="daily" <?= ($settings['interest_recognition'] ?? '') == 'daily' ? 'selected' : '' ?>>Daily Accrual</option>
                                    <option value="monthly" <?= ($settings['interest_recognition'] ?? '') == 'monthly' ? 'selected' : '' ?>>Monthly Accrual</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="auto_posting" 
                                           name="settings[auto_posting]" value="1"
                                           <?= ($settings['auto_posting'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="auto_posting">
                                        Auto-post transactions to GL
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="require_voucher" 
                                           name="settings[require_voucher]" value="1"
                                           <?= ($settings['require_voucher'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="require_voucher">
                                        Require voucher approval
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h5 class="text-primary mb-3"><i class="fas fa-file-invoice mr-1"></i> Voucher Settings</h5>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Receipt Prefix</label>
                                <input type="text" name="settings[receipt_prefix]" class="form-control" 
                                       value="<?= $settings['receipt_prefix'] ?? 'RCT' ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Payment Prefix</label>
                                <input type="text" name="settings[payment_prefix]" class="form-control" 
                                       value="<?= $settings['payment_prefix'] ?? 'PAY' ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Journal Prefix</label>
                                <input type="text" name="settings[journal_prefix]" class="form-control" 
                                       value="<?= $settings['journal_prefix'] ?? 'JRN' ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Financial Year Start Month</label>
                                <select name="settings[fy_start_month]" class="form-control">
                                    <?php 
                                    $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                                               'July', 'August', 'September', 'October', 'November', 'December'];
                                    $selected_month = $settings['fy_start_month'] ?? 4; // April default for India
                                    foreach ($months as $i => $month): ?>
                                    <option value="<?= $i + 1 ?>" <?= $selected_month == ($i + 1) ? 'selected' : '' ?>>
                                        <?= $month ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">April for Indian financial year</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Voucher Number Reset</label>
                                <select name="settings[voucher_reset]" class="form-control">
                                    <option value="yearly" <?= ($settings['voucher_reset'] ?? 'yearly') == 'yearly' ? 'selected' : '' ?>>Yearly</option>
                                    <option value="monthly" <?= ($settings['voucher_reset'] ?? '') == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                    <option value="never" <?= ($settings['voucher_reset'] ?? '') == 'never' ? 'selected' : '' ?>>Never</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-book-open mr-1"></i> Chart of Accounts</h3>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm mb-0">
                    <thead class="thead-light sticky-top">
                        <tr>
                            <th>Code</th>
                            <th>Account Name</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $acc): ?>
                        <tr>
                            <td><code><?= $acc->account_code ?></code></td>
                            <td><?= $acc->account_name ?></td>
                            <td>
                                <?php
                                $type_class = [
                                    'asset' => 'info',
                                    'liability' => 'warning',
                                    'equity' => 'primary',
                                    'income' => 'success',
                                    'expense' => 'danger'
                                ];
                                ?>
                                <span class="badge badge-<?= $type_class[$acc->account_type] ?? 'secondary' ?>">
                                    <?= ucfirst($acc->account_type ?? 'Other') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="<?= site_url('admin/settings/chart_of_accounts') ?>" class="btn btn-sm btn-outline-success btn-block">
                    <i class="fas fa-edit mr-1"></i> Manage Accounts
                </a>
            </div>
        </div>
        
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-lightbulb mr-1"></i> Indian Accounting</h3>
            </div>
            <div class="card-body">
                <p class="small">
                    <strong>Financial Year:</strong> April 1 to March 31
                </p>
                <p class="small">
                    <strong>TDS Compliance:</strong> Ensure proper TDS deduction on interest payments above threshold.
                </p>
                <p class="small">
                    <strong>GST:</strong> Processing fees may be subject to GST.
                </p>
                <p class="small mb-0">
                    <strong>RBI Guidelines:</strong> Follow RBI norms for NBFC/MFI accounting standards.
                </p>
            </div>
        </div>
    </div>
</div>
