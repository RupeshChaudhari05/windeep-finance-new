<!-- System Settings -->
<div class="row">
    <div class="col-md-3">
        <!-- Settings Navigation -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cogs mr-1"></i> Settings</h3>
            </div>
            <div class="card-body p-0">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#general" data-toggle="pill">
                            <i class="fas fa-sliders-h mr-2"></i> General Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#financial" data-toggle="pill">
                            <i class="fas fa-calendar-alt mr-2"></i> Financial Year
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#loan_products" data-toggle="pill">
                            <i class="fas fa-hand-holding-usd mr-2"></i> Loan Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#savings_schemes" data-toggle="pill">
                            <i class="fas fa-piggy-bank mr-2"></i> Savings Schemes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#fine_rules" data-toggle="pill">
                            <i class="fas fa-gavel mr-2"></i> Fine Rules
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#admin_users" data-toggle="pill">
                            <i class="fas fa-users-cog mr-2"></i> Admin Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#backup" data-toggle="pill">
                            <i class="fas fa-database mr-2"></i> Backup & Restore
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="tab-content">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-sliders-h mr-1"></i> General Settings</h3>
                    </div>
                    <form action="<?= site_url('admin/settings/update') ?>" method="post" enctype="multipart/form-data">
                        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                        
                        <div class="card-body">
                            <h5 class="text-primary border-bottom pb-2 mb-3">Organization Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Organization Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="org_name" value="<?= $settings['org_name'] ?? '' ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Short Name</label>
                                        <input type="text" class="form-control" name="org_short_name" value="<?= $settings['org_short_name'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" class="form-control" name="org_phone" value="<?= $settings['org_phone'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" class="form-control" name="org_email" value="<?= $settings['org_email'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <textarea class="form-control" name="org_address" rows="2"><?= $settings['org_address'] ?? '' ?></textarea>
                            </div>
                            
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-4">System Preferences</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Currency Symbol</label>
                                        <input type="text" class="form-control" name="currency_symbol" value="<?= $settings['currency_symbol'] ?? '₹' ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Date Format</label>
                                        <select class="form-control" name="date_format">
                                            <option value="d/m/Y" <?= ($settings['date_format'] ?? '') == 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                                            <option value="m/d/Y" <?= ($settings['date_format'] ?? '') == 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                                            <option value="Y-m-d" <?= ($settings['date_format'] ?? '') == 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Member Code Prefix</label>
                                        <input type="text" class="form-control" name="member_code_prefix" value="<?= $settings['member_code_prefix'] ?? 'MEM' ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Loan Number Prefix</label>
                                        <input type="text" class="form-control" name="loan_prefix" value="<?= $settings['loan_prefix'] ?? 'LN' ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Savings Account Prefix</label>
                                        <input type="text" class="form-control" name="savings_prefix" value="<?= $settings['savings_prefix'] ?? 'SV' ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Receipt Prefix</label>
                                        <input type="text" class="form-control" name="receipt_prefix" value="<?= $settings['receipt_prefix'] ?? 'RCP' ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="text-primary border-bottom pb-2 mb-3 mt-4">Business Rules</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Max Active Loans Per Member</label>
                                        <input type="number" class="form-control" name="max_active_loans" value="<?= $settings['max_active_loans'] ?? 3 ?>" min="1">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Max Guarantor Per Member</label>
                                        <input type="number" class="form-control" name="max_guarantor" value="<?= $settings['max_guarantor'] ?? 3 ?>" min="1">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>NPA Days (Loan)</label>
                                        <input type="number" class="form-control" name="npa_days" value="<?= $settings['npa_days'] ?? 90 ?>" min="30">
                                        <small class="form-text text-muted">Loan marked NPA after these many days overdue</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="autoFines" name="auto_apply_fines" value="1" 
                                               <?= ($settings['auto_apply_fines'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="autoFines">Auto-apply late payment fines</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="kycRequired" name="kyc_required" value="1"
                                               <?= ($settings['kyc_required'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="kycRequired">Require KYC for loan approval</label>
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
            
            <!-- Financial Year -->
            <div class="tab-pane fade" id="financial">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Financial Years</h3>
                        <button type="button" class="btn btn-sm btn-light float-right" data-toggle="modal" data-target="#addFYModal">
                            <i class="fas fa-plus"></i> Add New
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Year Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($financial_years ?? [] as $fy): ?>
                                <tr>
                                    <td><?= $fy->year_name ?></td>
                                    <td><?= format_date($fy->start_date, 'd M Y') ?></td>
                                    <td><?= format_date($fy->end_date, 'd M Y') ?></td>
                                    <td>
                                        <?php if ($fy->is_current): ?>
                                            <span class="badge badge-success">Current</span>
                                        <?php elseif ($fy->is_closed): ?>
                                            <span class="badge badge-secondary">Closed</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Open</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$fy->is_current && !$fy->is_closed): ?>
                                        <a href="<?= site_url('admin/settings/set_current_fy/' . $fy->id) ?>" class="btn btn-xs btn-success" onclick="return confirm('Set this as current financial year?')">
                                            Set Current
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Loan Products -->
            <div class="tab-pane fade" id="loan_products">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i> Loan Products</h3>
                        <button type="button" class="btn btn-sm btn-light float-right" data-toggle="modal" data-target="#addProductModal">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Interest Rate</th>
                                    <th>Type</th>
                                    <th>Amount Range</th>
                                    <th>Tenure</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loan_products ?? [] as $prod): ?>
                                <tr>
                                    <td><?= $prod->product_name ?></td>
                                    <td><?= $prod->interest_rate ?>% p.a.</td>
                                    <td><span class="badge badge-info"><?= ucfirst($prod->interest_type) ?></span></td>
                                    <td>₹<?= number_format($prod->min_amount) ?> - ₹<?= number_format($prod->max_amount) ?></td>
                                    <td><?= $prod->min_tenure ?> - <?= $prod->max_tenure ?> months</td>
                                    <td>
                                        <span class="badge badge-<?= $prod->is_active ? 'success' : 'secondary' ?>">
                                            <?= $prod->is_active ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-xs btn-warning edit-product" data-id="<?= $prod->id ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Savings Schemes -->
            <div class="tab-pane fade" id="savings_schemes">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-piggy-bank mr-1"></i> Savings Schemes</h3>
                        <button type="button" class="btn btn-sm btn-light float-right" data-toggle="modal" data-target="#addSchemeModal">
                            <i class="fas fa-plus"></i> Add Scheme
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Scheme Name</th>
                                    <th>Interest Rate</th>
                                    <th>Min Amount</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($savings_schemes ?? [] as $scheme): ?>
                                <tr>
                                    <td><?= $scheme->scheme_name ?></td>
                                    <td><?= $scheme->interest_rate ?>% p.a.</td>
                                    <td>₹<?= number_format($scheme->minimum_amount) ?></td>
                                    <td><?= $scheme->duration_months ? $scheme->duration_months . ' months' : 'Open-ended' ?></td>
                                    <td>
                                        <span class="badge badge-<?= $scheme->is_active ? 'success' : 'secondary' ?>">
                                            <?= $scheme->is_active ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-xs btn-warning edit-scheme" data-id="<?= $scheme->id ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Fine Rules -->
            <div class="tab-pane fade" id="fine_rules">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-gavel mr-1"></i> Fine Rules</h3>
                        <button type="button" class="btn btn-sm btn-light float-right" data-toggle="modal" data-target="#addFineRuleModal">
                            <i class="fas fa-plus"></i> Add Rule
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Fine Type</th>
                                    <th>Calculation</th>
                                    <th>Amount</th>
                                    <th>Grace Days</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fine_rules ?? [] as $rule): ?>
                                <tr>
                                    <td><?= ucfirst(str_replace('_', ' ', $rule->fine_type)) ?></td>
                                    <td><span class="badge badge-info"><?= ucfirst($rule->calculation_type) ?></span></td>
                                    <td>
                                        <?php if ($rule->calculation_type == 'fixed'): ?>
                                            ₹<?= number_format($rule->amount) ?>
                                        <?php else: ?>
                                            <?= $rule->amount ?>%
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $rule->grace_days ?> days</td>
                                    <td>
                                        <span class="badge badge-<?= $rule->is_active ? 'success' : 'secondary' ?>">
                                            <?= $rule->is_active ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-xs btn-warning edit-rule" data-id="<?= $rule->id ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Admin Users -->
            <div class="tab-pane fade" id="admin_users">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-users-cog mr-1"></i> Admin Users</h3>
                        <button type="button" class="btn btn-sm btn-light float-right" data-toggle="modal" data-target="#addAdminModal">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Last Login</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admin_users ?? [] as $user): ?>
                                <tr>
                                    <td><?= $user->name ?></td>
                                    <td><?= $user->email ?></td>
                                    <td><span class="badge badge-primary"><?= ucfirst($user->role) ?></span></td>
                                    <td><?= format_date_time($user->last_login, 'd M Y H:i', 'Never') ?></td>
                                    <td>
                                        <span class="badge badge-<?= $user->is_active ? 'success' : 'danger' ?>">
                                            <?= $user->is_active ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-xs btn-warning edit-admin" data-id="<?= $user->id ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-xs btn-info reset-password" data-id="<?= $user->id ?>">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Backup -->
            <div class="tab-pane fade" id="backup">
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-database mr-1"></i> Backup & Restore</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0"><i class="fas fa-download mr-1"></i> Create Backup</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Create a full database backup. This will download a SQL file with all your data.</p>
                                        <a href="<?= site_url('admin/settings/backup') ?>" class="btn btn-success">
                                            <i class="fas fa-download mr-1"></i> Download Backup
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-warning">
                                        <h5 class="mb-0"><i class="fas fa-upload mr-1"></i> Restore Backup</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-danger"><strong>Warning:</strong> This will overwrite all existing data!</p>
                                        <form action="<?= site_url('admin/settings/restore') ?>" method="post" enctype="multipart/form-data">
                                            <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                                            <div class="custom-file mb-3">
                                                <input type="file" class="custom-file-input" name="backup_file" accept=".sql" required>
                                                <label class="custom-file-label">Choose SQL file</label>
                                            </div>
                                            <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure? This will overwrite ALL existing data!')">
                                                <i class="fas fa-upload mr-1"></i> Restore
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Custom file input
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').html(fileName);
    });
});
</script>
