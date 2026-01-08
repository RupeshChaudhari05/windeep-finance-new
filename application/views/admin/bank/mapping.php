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
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-default filter-btn active" data-filter="all">All</button>
                            <button class="btn btn-default filter-btn" data-filter="unmapped">Unmapped</button>
                            <button class="btn btn-default filter-btn" data-filter="mapped">Mapped</button>
                            <button class="btn btn-default filter-btn" data-filter="partial">Partial</button>
                        </div>
                        <span class="badge badge-info ml-2" id="transactionCount"><?= count($transactions) ?> transactions</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm" id="transactionsTable">
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
                                            data-status="<?= $txn->mapping_status ?>"
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

<!-- Enhanced Match Transaction Modal -->
<div class="modal fade" id="matchModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-link mr-2"></i> Map Bank Transaction</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                <input type="hidden" id="match_transaction_id">
                <input type="hidden" id="match_transaction_amount">

                <!-- Transaction Info -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Transaction Amount:</strong>
                                    <span id="match_amount" class="h4 ml-2 mb-0">₹0.00</span>
                                </div>
                                <div>
                                    <strong>Date:</strong> <span id="match_date"></span>
                                </div>
                            </div>
                            <small class="d-block mt-1" id="match_description"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert mb-0" id="allocation_status">
                            <div class="d-flex justify-content-between">
                                <span><strong>Allocated:</strong> <span id="total_allocated">₹0.00</span></span>
                                <span><strong>Remaining:</strong> <span id="remaining_amount">₹0.00</span></span>
                            </div>
                            <div class="progress mt-2" style="height: 8px;">
                                <div class="progress-bar bg-success" id="allocation_progress" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paying Member Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light py-2">
                        <strong><i class="fas fa-user mr-1"></i> Paying Member</strong>
                        <small class="text-muted ml-2">(Who made this payment?)</small>
                    </div>
                    <div class="card-body py-2">
                        <select id="paying_member" class="form-control select2-member" style="width: 100%;">
                            <option value="">Search member by name, code, or phone...</option>
                        </select>
                    </div>
                </div>

                <!-- Paid For Members Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                        <div>
                            <strong><i class="fas fa-users mr-1"></i> Paid For Members</strong>
                            <small class="text-muted ml-2">(Allocate to loans/savings/fines)</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" id="add_paid_for_member">
                            <i class="fas fa-plus"></i> Add Member
                        </button>
                    </div>
                    <div class="card-body" id="paid_for_container">
                        <div class="text-center text-muted py-3" id="no_members_msg">
                            <i class="fas fa-info-circle"></i> Click "Add Member" to allocate this transaction
                        </div>
                    </div>
                </div>

                <!-- Manual/Internal Entry Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light py-2">
                        <strong><i class="fas fa-edit mr-1"></i> Internal/Manual Entry</strong>
                        <small class="text-muted ml-2">(For bank expenses, transfers, etc.)</small>
                    </div>
                    <div class="card-body py-2">
                        <div class="row">
                            <div class="col-md-4">
                                <select id="manual_match_type" class="form-control form-control-sm">
                                    <option value="">-- Select if internal --</option>
                                    <option value="expense">Bank Expense</option>
                                    <option value="other_income">Other Income</option>
                                    <option value="transfer">Internal Transfer</option>
                                    <option value="ignore">Ignore/Skip</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="number" id="manual_amount" class="form-control form-control-sm" placeholder="Amount (optional)" step="0.01" min="0">
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-block" id="add_manual_entry">
                                    <i class="fas fa-plus"></i> Add as Internal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Allocations Summary -->
                <div class="card">
                    <div class="card-header bg-light py-2">
                        <strong><i class="fas fa-list-alt mr-1"></i> Allocation Summary</strong>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0" id="allocations_table">
                            <thead class="thead-light">
                                <tr>
                                    <th>Member</th>
                                    <th>Type</th>
                                    <th>Account/Details</th>
                                    <th class="text-right">Amount</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody id="allocations_body">
                                <tr id="no_allocations_row">
                                    <td colspan="5" class="text-center text-muted py-3">No allocations yet</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="3" class="text-right">Total Allocated:</th>
                                    <th class="text-right" id="footer_total">₹0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="form-group mt-3">
                    <label><i class="fas fa-sticky-note mr-1"></i> Notes/Remarks</label>
                    <textarea id="mapping_notes" class="form-control" rows="2" placeholder="Add any notes about this mapping..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <div class="mr-auto">
                    <span class="text-danger" id="validation_error" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i> <span id="validation_msg"></span>
                    </span>
                </div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmMatch" disabled>
                    <i class="fas fa-check"></i> Save Mapping
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Member Allocation Template (hidden) -->
<template id="member_allocation_template">
    <div class="member-allocation-card card mb-2" data-member-id="">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <div>
                <strong class="member-name"></strong>
                <small class="text-muted member-code ml-2"></small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-member-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="card-body py-2">
            <div class="row">
                <!-- Loans Column -->
                <div class="col-md-4">
                    <label class="small font-weight-bold"><i class="fas fa-hand-holding-usd"></i> Loans/EMI</label>
                    <div class="loans-list" style="max-height: 150px; overflow-y: auto;"></div>
                </div>
                <!-- Savings Column -->
                <div class="col-md-4">
                    <label class="small font-weight-bold"><i class="fas fa-piggy-bank"></i> Savings</label>
                    <div class="savings-list" style="max-height: 150px; overflow-y: auto;"></div>
                </div>
                <!-- Fines Column -->
                <div class="col-md-4">
                    <label class="small font-weight-bold"><i class="fas fa-exclamation-circle"></i> Fines</label>
                    <div class="fines-list" style="max-height: 150px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>
</template>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#transactionsTable').DataTable({
        "order": [[1, "desc"]], // Sort by date descending
        "pageLength": 25,
        "columnDefs": [
            { "orderable": false, "targets": [10] } // Disable sorting on action column
        ]
    });

    // Filter buttons
    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        var filter = $(this).data('filter');

        var table = $('#transactionsTable').DataTable();
        if (filter == 'all') {
            table.search('').columns().search('').draw();
        } else {
            // Search in the status column (index 9) for the filter text
            table.column(9).search(filter, true, false).draw();
        }

        // Update count
        var info = table.page.info();
        $('#transactionCount').text(info.recordsDisplay + ' transactions');
    });

    // Update count on search
    $('#transactionsTable').on('search.dt', function() {
        var table = $('#transactionsTable').DataTable();
        var info = table.page.info();
        $('#transactionCount').text(info.recordsDisplay + ' transactions');
    });
});
</script>

<?php // Mapping scripts are loaded from assets to ensure they run after jQuery. ?>
