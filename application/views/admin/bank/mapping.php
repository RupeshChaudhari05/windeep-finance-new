<section class="content">
    <div class="container-fluid">
    <style>
    .table-responsive {
        margin-bottom: 20px;
    }
    .card-body .table th, .card-body .table td {
        padding: 8px 4px;
        font-size: 0.875rem;
    }
    .modal-body {
        padding: 20px;
        max-height: 70vh;
        overflow-y: auto;
    }
    .modal-body .form-group {
        margin-bottom: 15px;
    }
    .mapping-form-section {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #dee2e6;
    }
    .txn-row:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
    .badge {
        font-size: 0.75rem;
    }
    .btn-xs {
        padding: 0.125rem 0.25rem;
        font-size: 0.75rem;
        line-height: 1.5;
        border-radius: 0.2rem;
    }
    .mapping-row {
        border: 1px solid #e9ecef;
        border-radius: 0.25rem;
        margin-bottom: 10px;
    }
    .mapping-row .card-body {
        padding: 10px;
    }
    .alert-info {
        border-left: 4px solid #17a2b8;
    }
    @media (max-width: 768px) {
        .card-body .table th, .card-body .table td {
            padding: 4px 2px;
            font-size: 0.75rem;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100vw - 1rem);
        }
        .col-md-6 {
            margin-bottom: 10px;
        }
        .table-responsive {
            font-size: 0.8rem;
        }
        th, td {
            white-space: nowrap;
        }
    }
    </style>


    <section class="content">
        <div class="container-fluid">
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
                        <table class="table table-striped table-hover table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Bank Account</th>
                                    <th>Description</th>
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Credit</th>
                                    <th>Paid By</th>
                                    <th>Paid For</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Action</th>
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
                                            data-credit="<?= $txn->credit_amount ?>"
                                            data-category="<?= htmlspecialchars($txn->transaction_category ?? $txn->transaction_type ?? '') ?>">
                                            <td class="text-center"><small><?= $txn->id ?></small></td>
                                            <td><small><?= format_date($txn->transaction_date) ?></small></td>
                                            <td>
                                                <small class="text-muted d-block">
                                                    <?= $txn->bank_name ?>
                                                </small>
                                                <small class="text-muted">
                                                    <?= $txn->bank_account_number ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($txn->description) ?>">
                                                    <?= character_limiter($txn->description, 40) ?>
                                                </div>
                                                <?php if (!empty($txn->bank_reference)): ?>
                                                    <small class="text-muted">Ref: <?= $txn->bank_reference ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right">
                                                <?php if ($txn->debit_amount > 0): ?>
                                                    <span class="text-danger font-weight-bold">₹<?= number_format($txn->debit_amount, 2) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right">
                                                <?php if ($txn->credit_amount > 0): ?>
                                                    <span class="text-success font-weight-bold">₹<?= number_format($txn->credit_amount, 2) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="paid-by-display">
                                                    <?= $txn->paid_by_name ?: '<span class="text-muted">-</span>' ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="paid-for-display">
                                                    <?= $txn->paid_for_name ?: '<span class="text-muted">-</span>' ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="category-display">
                                                    <?php if (isset($txn->transaction_category) && $txn->transaction_category): ?>
                                                        <span class="badge badge-info badge-sm"><?= ucfirst($txn->transaction_category) ?></span>
                                                    <?php elseif (!empty($txn->transaction_type)): ?>
                                                        <span class="badge badge-secondary badge-sm"><?= ucfirst($txn->transaction_type) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </small>
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
                                                <span class="badge badge-<?= $badge_class ?> badge-sm"><?= ucfirst($txn->mapping_status) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($txn->mapping_status == 'unmapped'): ?>
                                                    <button class="btn btn-sm btn-primary btn-xs map-btn" data-txn-id="<?= $txn->id ?>" title="Map Transaction">
                                                        <i class="fas fa-link"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-info btn-xs view-btn" data-txn-id="<?= $txn->id ?>" title="View Mapping">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning btn-xs edit-map-btn" data-txn-id="<?= $txn->id ?>" title="Edit Mapping">
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
<div class="modal fade" id="mappingModal" tabindex="-1" role="dialog" aria-labelledby="mappingModalLabel">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="mappingModalLabel">
                    <i class="fas fa-link"></i> Map Bank Transaction
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="mappingForm" novalidate>
                <div class="modal-body">
                    <!-- Transaction Details -->
                    <div class="card card-outline card-info mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle"></i> Transaction Details
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Date:</strong> <span id="txn_date" class="text-primary"></span></p>
                                    <p class="mb-1"><strong>Bank:</strong> <span id="txn_bank"></span></p>
                                    <p class="mb-1"><strong>Amount:</strong> <span id="txn_amount" class="h5 font-weight-bold"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Description:</strong> <span id="txn_description"></span></p>
                                    <p class="mb-1"><strong>Reference:</strong> <span id="txn_reference"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="transaction_id" name="transaction_id">

                    <!-- Mapping Form -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="transaction_type">Transaction Category <span class="text-danger">*</span></label>
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
                            <div class="alert alert-info">
                                <strong>Transaction Amount:</strong> <span id="txn_amount_display" class="h5 font-weight-bold"></span>
                                <br>
                                <small>Remaining to map: <span id="txn_remaining_display" class="font-weight-bold">0.00</span></small>
                            </div>
                        </div>
                    </div>

                    <!-- Mapping Rows -->
                    <div class="mapping-form-section">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="paying_member_search">Paid By (Who made the payment) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="paying_member_search" placeholder="Search payer by code, name, phone...">
                                        <div class="input-group-append">
                                            <button class="btn btn-info" type="button" id="search_paying_member">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <input type="hidden" id="paying_member_id" name="paying_member_id" required>
                                    <div id="paying_member_result" class="mt-2"></div>
                                </div>
                            </div>
                            <div class="col-md-6 text-right">
                                <label>&nbsp;</label><br>
                                <button type="button" id="add_mapping_row" class="btn btn-success">
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
                                    <label for="remarks">Remarks</label>
                                    <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Optional remarks..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Mapping
                    </button>
                </div>
        </div>
    </div>
</div>

<?php // Mapping scripts are loaded from assets to ensure they run after jQuery. ?>
