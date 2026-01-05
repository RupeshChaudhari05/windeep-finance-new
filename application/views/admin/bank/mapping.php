<div class="content-wrapper">
    <style>
    .table-responsive {
        margin-bottom: 20px;
    }
    .card-body .table th, .card-body .table td {
        padding: 12px 8px;
    }
    .modal-body {
        padding: 20px;
    }
    .modal-body .form-group {
        margin-bottom: 15px;
    }
    .mapping-form-section {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #dee2e6;
    }
    </style>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Bank Transaction Mapping</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= site_url('admin/dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= site_url('admin/bank/import') ?>">Bank</a></li>
                        <li class="breadcrumb-item active">Mapping</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Flash Messages -->
            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('success') ?>
                </div>
            <?php endif; ?>
            
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('error') ?>
                </div>
            <?php endif; ?>

            <!-- Filters Card -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Filters</h3>
                </div>
                <form method="get" action="<?= site_url('admin/bank/mapping') ?>">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Bank Account</label>
                                    <select name="bank_id" class="form-control">
                                        <option value="">All Accounts</option>
                                        <?php foreach ($bank_accounts as $account): ?>
                                            <option value="<?= $account->id ?>" <?= $filters['bank_id'] == $account->id ? 'selected' : '' ?>>
                                                <?= $account->bank_name ?> - <?= $account->account_number ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>From Date</label>
                                    <input type="date" name="from_date" class="form-control" value="<?= $filters['from_date'] ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>To Date</label>
                                    <input type="date" name="to_date" class="form-control" value="<?= $filters['to_date'] ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="mapping_status" class="form-control">
                                        <option value="">All Transactions</option>
                                        <option value="unmapped" <?= $filters['mapping_status'] == 'unmapped' ? 'selected' : '' ?>>Unmapped</option>
                                        <option value="mapped" <?= $filters['mapping_status'] == 'mapped' ? 'selected' : '' ?>>Mapped</option>
                                        <option value="partial" <?= $filters['mapping_status'] == 'partial' ? 'selected' : '' ?>>Partial</option>
                                        <option value="ignored" <?= $filters['mapping_status'] == 'ignored' ? 'selected' : '' ?>>Ignored</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Transactions Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exchange-alt"></i> Bank Transactions</h3>
                    <div class="card-tools">
                        <span class="badge badge-info"><?= count($transactions) ?> transactions</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Date</th>
                                    <th>Bank Account</th>
                                    <th>Description</th>
                                    <th width="100" class="text-right">Debit</th>
                                    <th width="100" class="text-right">Credit</th>
                                    <th>Paid By</th>
                                    <th>Paid For</th>
                                    <th>Category</th>
                                    <th width="80">Status</th>
                                    <th width="100">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No transactions found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $txn): ?>
                                        <tr data-txn-id="<?= $txn->id ?>" class="txn-row"
                                            data-date="<?= format_date($txn->transaction_date) ?>"
                                            data-bank="<?= htmlspecialchars($txn->bank_name) ?>"
                                            data-account="<?= htmlspecialchars($txn->bank_account_number) ?>"
                                            data-description="<?= htmlspecialchars($txn->description) ?>"
                                            data-reference="<?= htmlspecialchars($txn->bank_reference ?? '') ?>"
                                            data-debit="<?= $txn->debit_amount ?>"
                                            data-credit="<?= $txn->credit_amount ?>">
                                            <td><?= $txn->id ?></td>
                                            <td><?= format_date($txn->transaction_date) ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= $txn->bank_name ?><br>
                                                    <?= $txn->bank_account_number ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?= character_limiter($txn->description, 50) ?>
                                                <?php if (!empty($txn->bank_reference)): ?>
                                                    <br><small class="text-muted">Ref: <?= $txn->bank_reference ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right">
                                                <?php if ($txn->debit_amount > 0): ?>
                                                    <span class="text-danger">₹<?= number_format($txn->debit_amount, 2) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right">
                                                <?php if ($txn->credit_amount > 0): ?>
                                                    <span class="text-success">₹<?= number_format($txn->credit_amount, 2) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="paid-by-display">
                                                    <?= $txn->paid_by_name ?: '<span class="text-muted">-</span>' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="paid-for-display">
                                                    <?= $txn->paid_for_name ?: '<span class="text-muted">-</span>' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="category-display">
                                                    <?php if ($txn->transaction_category): ?>
                                                        <span class="badge badge-info"><?= ucfirst($txn->transaction_category) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'mapped' => 'success',
                                                    'unmapped' => 'warning',
                                                    'partial' => 'info',
                                                    'ignored' => 'secondary'
                                                ];
                                                $badge_class = $status_class[$txn->mapping_status] ?? 'secondary';
                                                ?>
                                                <span class="badge badge-<?= $badge_class ?>"><?= ucfirst($txn->mapping_status) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($txn->mapping_status == 'unmapped'): ?>
                                                    <button class="btn btn-sm btn-primary map-btn" data-txn-id="<?= $txn->id ?>">
                                                        <i class="fas fa-link"></i> Map
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-info view-btn" data-txn-id="<?= $txn->id ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning edit-map-btn" data-txn-id="<?= $txn->id ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php endif; ?>
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
    </section>
</div>

<!-- Mapping Modal -->
<div class="modal fade" id="mappingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Map Bank Transaction</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="mappingForm">
                <div class="modal-body">
                    <input type="hidden" id="transaction_id" name="transaction_id">
                    
                    <!-- Transaction Details -->
                    <div class="card card-outline card-info mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Transaction Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Date:</strong> <span id="txn_date"></span></p>
                                    <p><strong>Bank:</strong> <span id="txn_bank"></span></p>
                                    <p><strong>Amount:</strong> <span id="txn_amount" class="h5"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Description:</strong> <span id="txn_description"></span></p>
                                    <p><strong>Reference:</strong> <span id="txn_reference"></span></p>
                                </div>
                            </div>

                            <hr>

                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <strong>Transaction total:</strong>
                                    <div id="txn_amount_display_small" class="h5"></div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <strong>Remaining:</strong>
                                    <div id="txn_remaining_display_small" class="h5"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mapping Form -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Transaction Category <span class="text-danger">*</span></label>
                                <select class="form-control" id="transaction_type" name="transaction_type" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($transaction_categories as $key => $label): ?>
                                        <option value="<?= $key ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Transaction Amount</label>
                                <p id="txn_amount_display" class="h4"></p>
                                <p><small>Remaining to map: <span id="txn_remaining_display">0.00</span></small></p>
                            </div>
                        </div>
                    </div>

                    <!-- Mapping Rows -->
                    <div class="row mb-3 mapping-form-section">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Paid By (Who made the payment) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="paying_member_search" placeholder="Search payer by code, name, phone...">
                                    <div class="input-group-append">
                                        <button class="btn btn-info" type="button" id="search_paying_member"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                                <input type="hidden" id="paying_member_id" name="paying_member_id" required>
                                <div id="paying_member_result" class="mt-2"></div>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="button" id="add_mapping_row" class="btn btn-sm btn-success mt-4">
                                <i class="fas fa-plus"></i> Add Mapping Row
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div id="mapping_rows">
                                <!-- Template row inserted via JS -->
                            </div>

                            <div class="mt-3">
                                <label>Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Mapping
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php // Mapping scripts are loaded from assets to ensure they run after jQuery. ?>
