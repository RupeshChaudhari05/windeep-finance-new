<!-- Unified Bank Statement - All transactions for a financial year -->

<!-- Filters -->
<div class="card card-outline card-primary">
    <div class="card-header py-2">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filters</h3>
        <div class="card-tools">
            <a href="<?= site_url('admin/bank/import') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-upload mr-1"></i> Import Statement
            </a>
        </div>
    </div>
    <div class="card-body py-2">
        <form method="get" action="<?= site_url('admin/bank/statement') ?>" id="filterForm">
            <div class="row">
                <div class="col-md-2">
                    <label class="small font-weight-bold">Financial Year</label>
                    <select class="form-control form-control-sm" name="fy_id" id="fy_id">
                        <?php foreach ($financial_years as $fy): ?>
                        <option value="<?= $fy->id ?>" <?= ($selected_fy && $selected_fy->id == $fy->id) ? 'selected' : '' ?>>
                            <?= $fy->year_code ?>
                            <?php if (isset($fy->is_active) && $fy->is_active): ?> (Active)<?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">Bank Account</label>
                    <select class="form-control form-control-sm" name="bank_id">
                        <option value="">All Accounts</option>
                        <?php foreach ($bank_accounts as $acc): ?>
                        <option value="<?= $acc->id ?>" <?= ($filters['bank_id'] == $acc->id) ? 'selected' : '' ?>>
                            <?= $acc->account_name ?? $acc->bank_name ?> - <?= $acc->account_number ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">Mapping Status</label>
                    <select class="form-control form-control-sm" name="mapping_status">
                        <option value="">All</option>
                        <option value="unmapped" <?= ($filters['mapping_status'] == 'unmapped') ? 'selected' : '' ?>>Unmapped</option>
                        <option value="mapped" <?= ($filters['mapping_status'] == 'mapped') ? 'selected' : '' ?>>Mapped</option>
                        <option value="partial" <?= ($filters['mapping_status'] == 'partial') ? 'selected' : '' ?>>Partial</option>
                        <option value="ignored" <?= ($filters['mapping_status'] == 'ignored') ? 'selected' : '' ?>>Ignored</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">Transaction Type</label>
                    <select class="form-control form-control-sm" name="transaction_type">
                        <option value="">All</option>
                        <option value="credit" <?= ($filters['transaction_type'] == 'credit') ? 'selected' : '' ?>>Credits Only</option>
                        <option value="debit" <?= ($filters['transaction_type'] == 'debit') ? 'selected' : '' ?>>Debits Only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">Member ID</label>
                    <input type="number" class="form-control form-control-sm" name="member_id" 
                           value="<?= $filters['member_id'] ?>" placeholder="Filter by member">
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search mr-1"></i> Apply
                        </button>
                        <a href="<?= site_url('admin/bank/statement') ?>" class="btn btn-default btn-sm">
                            <i class="fas fa-times mr-1"></i> Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row">
    <?php
    $total_credits = 0; $total_debits = 0; $total_mapped = 0; $total_unmapped = 0; $total_partial = 0;
    foreach ($transactions as $t) {
        if ($t->transaction_type == 'credit') $total_credits += $t->amount;
        else $total_debits += $t->amount;
        if ($t->mapping_status == 'mapped') $total_mapped++;
        elseif ($t->mapping_status == 'partial') $total_partial++;
        else $total_unmapped++;
    }
    ?>
    <div class="col-md-2">
        <div class="small-box bg-info">
            <div class="inner">
                <h4><?= count($transactions) ?></h4>
                <p>Total Transactions</p>
            </div>
            <div class="icon"><i class="fas fa-list"></i></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-success">
            <div class="inner">
                <h4>₹<?= number_format($total_credits, 0) ?></h4>
                <p>Total Credits</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-down"></i></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-danger">
            <div class="inner">
                <h4>₹<?= number_format($total_debits, 0) ?></h4>
                <p>Total Debits</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-up"></i></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-primary">
            <div class="inner">
                <h4>₹<?= number_format($total_credits - $total_debits, 0) ?></h4>
                <p>Net Amount</p>
            </div>
            <div class="icon"><i class="fas fa-balance-scale"></i></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-warning">
            <div class="inner">
                <h4><?= $total_mapped ?> / <?= count($transactions) ?></h4>
                <p>Mapped</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h4><?= $total_unmapped ?></h4>
                <p>Unmapped</p>
            </div>
            <div class="icon"><i class="fas fa-question-circle"></i></div>
        </div>
    </div>
</div>

<!-- Financial Year Info -->
<?php if ($selected_fy): ?>
<div class="alert alert-info py-2">
    <i class="fas fa-calendar-alt mr-1"></i>
    <strong>Financial Year: <?= $selected_fy->year_code ?></strong>
    &mdash; <?= date('d M Y', strtotime($selected_fy->start_date)) ?> to <?= date('d M Y', strtotime($selected_fy->end_date)) ?>
    &mdash; <strong><?= count($transactions) ?></strong> transactions (all imports combined into one statement)
</div>
<?php endif; ?>

<!-- Unified Statement Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-file-alt mr-1"></i> 
            Bank Statement
            <?php if ($selected_fy): ?> &mdash; <?= $selected_fy->year_code ?><?php endif; ?>
        </h3>
        <div class="card-tools">
            <div class="btn-group btn-group-sm" id="quickFilters">
                <button class="btn btn-default quick-filter active" data-filter="all">All</button>
                <button class="btn btn-default quick-filter" data-filter="unmapped">Unmapped</button>
                <button class="btn btn-default quick-filter" data-filter="mapped">Mapped</button>
                <button class="btn btn-default quick-filter" data-filter="credit">Credits</button>
                <button class="btn btn-default quick-filter" data-filter="debit">Debits</button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-sm mb-0" id="statementTable">
                <thead class="thead-light">
                    <tr>
                        <th width="30">#</th>
                        <th width="100">Date</th>
                        <th>Description</th>
                        <th>Reference</th>
                        <th class="text-right" width="110">Credit</th>
                        <th class="text-right" width="110">Debit</th>
                        <th width="80">Status</th>
                        <th>Member</th>
                        <th width="80">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $row_num = 0; foreach ($transactions as $txn): $row_num++; ?>
                    <tr data-mapping-status="<?= $txn->mapping_status ?>" data-txn-type="<?= $txn->transaction_type ?>">
                        <td class="text-muted"><?= $row_num ?></td>
                        <td><?= date('d M Y', strtotime($txn->transaction_date)) ?></td>
                        <td>
                            <?= character_limiter($txn->description, 50) ?>
                        </td>
                        <td><small class="text-muted"><?= $txn->reference_number ?: ($txn->utr_number ?: '-') ?></small></td>
                        <td class="text-right">
                            <?php if ($txn->transaction_type == 'credit'): ?>
                            <span class="text-success font-weight-bold">₹<?= number_format($txn->amount, 2) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <?php if ($txn->transaction_type == 'debit'): ?>
                            <span class="text-danger font-weight-bold">₹<?= number_format($txn->amount, 2) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($txn->mapping_status == 'mapped'): ?>
                            <span class="badge badge-success"><i class="fas fa-check"></i> Mapped</span>
                            <?php elseif ($txn->mapping_status == 'partial'): ?>
                            <span class="badge badge-warning"><i class="fas fa-clock"></i> Partial</span>
                            <?php elseif ($txn->mapping_status == 'ignored'): ?>
                            <span class="badge badge-dark"><i class="fas fa-ban"></i> Ignored</span>
                            <?php else: ?>
                            <span class="badge badge-secondary"><i class="fas fa-question"></i> Unmapped</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($txn->paid_for_name): ?>
                                <small><?= $txn->paid_for_name ?></small>
                            <?php elseif ($txn->paid_by_name): ?>
                                <small><?= $txn->paid_by_name ?></small>
                            <?php elseif ($txn->detected_member_id): ?>
                                <small class="text-info">Auto-detected #<?= $txn->detected_member_id ?></small>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($txn->mapping_status != 'mapped' && $txn->mapping_status != 'ignored'): ?>
                            <button class="btn btn-info btn-xs btn-match" data-id="<?= $txn->id ?>" data-amount="<?= $txn->amount ?>" data-type="<?= $txn->transaction_type ?>">
                                <i class="fas fa-link"></i> Match
                            </button>
                            <?php else: ?>
                            <button class="btn btn-default btn-xs" disabled>
                                <i class="fas fa-check"></i> Done
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Enhanced Match Transaction Modal (same as view_import) -->
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
                
                <!-- Transaction Info - Sticky Header -->
                <div class="row mb-3" style="position: sticky; top: 0; z-index: 10; background: white; padding-top: 10px; padding-bottom: 10px; margin-left: -15px; margin-right: -15px; padding-left: 15px; padding-right: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div class="col-md-6">
                        <div class="mb-0">
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
                        <div class="mb-0" id="allocation_status">
                            <div class="d-flex justify-content-between">
                                <span><strong>Allocated:</strong> <span id="total_allocated" class="text-primary">₹0.00</span></span>
                                <span><strong>Remaining:</strong> <span id="remaining_amount" class="text-danger font-weight-bold">₹0.00</span></span>
                            </div>
                            <div class="progress mt-2" style="height: 10px;">
                                <div class="progress-bar bg-success" id="allocation_progress" style="width: 0%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block" id="allocation_helper">
                                <i class="fas fa-info-circle"></i> You can partially allocate the transaction
                            </small>
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
                <div class="col-md-4">
                    <label class="small font-weight-bold"><i class="fas fa-hand-holding-usd"></i> Loans/EMI</label>
                    <div class="loans-list" style="max-height: 150px; overflow-y: auto;"></div>
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold"><i class="fas fa-piggy-bank"></i> Savings</label>
                    <div class="savings-list" style="max-height: 150px; overflow-y: auto;"></div>
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold"><i class="fas fa-exclamation-circle"></i> Fines</label>
                    <div class="fines-list" style="max-height: 150px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
$(document).ready(function() {
    // DataTable with all sorting options
    var table = $('#statementTable').DataTable({
        "order": [[1, "asc"]], // Sort by date ascending (chronological statement)
        "pageLength": 100,
        "lengthMenu": [[50, 100, 250, 500, -1], [50, 100, 250, 500, "All"]],
        "dom": '<"row"<"col-md-6"l><"col-md-6"f>>rtip',
        "language": {
            "info": "Showing _START_ to _END_ of _TOTAL_ transactions",
            "lengthMenu": "Show _MENU_ transactions per page"
        },
        "columnDefs": [
            { "orderable": false, "targets": [8] }, // Actions column not sortable
            { "type": "date", "targets": [1] }
        ]
    });
    
    // Quick filter buttons (client-side filtering via DataTable)
    $('.quick-filter').click(function() {
        $('.quick-filter').removeClass('active');
        $(this).addClass('active');
        var filter = $(this).data('filter');
        
        // Clear all column filters first
        table.columns().search('');
        
        if (filter === 'unmapped') {
            table.column(6).search('Unmapped').draw();
        } else if (filter === 'mapped') {
            table.column(6).search('Mapped').draw();
        } else if (filter === 'credit') {
            // Filter rows where credit column has a value
            table.column(4).search('.+', true, false).draw();
        } else if (filter === 'debit') {
            table.column(5).search('.+', true, false).draw();
        } else {
            table.draw();
        }
    });

    // Global state
    var allocations = [];
    var transactionAmount = 0;

    // Initialize Select2 for paying member
    function initMemberSelect2(selector) {
        $(selector).select2({
            ajax: {
                url: '<?= site_url('admin/bank/search_members') ?>',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term, limit: 15 }; },
                processResults: function(resp) {
                    var items = resp && resp.data ? resp.data : [];
                    return { results: items };
                }
            },
            placeholder: 'Search member by name, code, or phone...',
            minimumInputLength: 1,
            allowClear: true,
            width: '100%',
            dropdownParent: $('#matchModal'),
            templateResult: function(item) {
                if (!item.id) return item.text;
                var label = item.full_name ? item.full_name : item.text;
                var sub = item.member_code ? (' <small class="text-muted">' + item.member_code + '</small>') : '';
                return $('<span>' + label + sub + '</span>');
            },
            templateSelection: function(item) {
                if (!item.id) return item.text || '';
                return item.full_name ? (item.member_code ? item.member_code + ' - ' + item.full_name : item.full_name) : item.text;
            }
        });
    }

    // Match button click - event delegation for DataTable pagination compatibility
    $('#statementTable').on('click', '.btn-match', function() {
        var id = $(this).data('id');
        var amount = parseFloat($(this).data('amount'));
        var type = $(this).data('type');
        var $row = $(this).closest('tr');
        var date = $row.find('td:eq(1)').text();
        var desc = $row.find('td:eq(2)').text();

        // Reset modal
        allocations = [];
        transactionAmount = amount;
        $('#match_transaction_id').val(id);
        $('#match_transaction_amount').val(amount);
        $('#match_amount').text('₹' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2}));
        $('#match_date').text(date);
        $('#match_description').text(desc);
        $('#paid_for_container').html('<div class="text-center text-muted py-3" id="no_members_msg"><i class="fas fa-info-circle"></i> Click "Add Member" to allocate this transaction</div>');
        $('#allocations_body').html('<tr id="no_allocations_row"><td colspan="5" class="text-center text-muted py-3">No allocations yet</td></tr>');
        $('#mapping_notes').val('');
        $('#manual_match_type').val('');
        $('#manual_amount').val('');
        $('#paying_member').val(null).trigger('change');
        
        updateAllocationStatus();
        initMemberSelect2('#paying_member');
        $('#matchModal').modal('show');
    });

    // Add Paid For Member
    $('#add_paid_for_member').click(function() {
        $('#no_members_msg').hide();
        var cardId = 'member_card_' + Date.now();
        var template = $('#member_allocation_template').html();
        var $card = $(template);
        $card.attr('id', cardId);
        
        var $searchDiv = $('<div class="mb-2"><select class="form-control member-search-select" style="width: 100%;"><option value="">Search member...</option></select></div>');
        $card.find('.card-body').prepend($searchDiv);
        $('#paid_for_container').append($card);

        var $select = $card.find('.member-search-select');
        $select.select2({
            ajax: {
                url: '<?= site_url('admin/bank/search_members') ?>',
                dataType: 'json',
                delay: 300,
                data: function(params) { return { q: params.term, limit: 15 }; },
                processResults: function(resp) { return { results: resp && resp.data ? resp.data : [] }; }
            },
            placeholder: 'Search member...',
            minimumInputLength: 1,
            allowClear: true,
            width: '100%',
            dropdownParent: $('#matchModal'),
            templateResult: function(item) {
                if (!item.id) return item.text;
                var label = item.full_name ? item.full_name : item.text;
                var sub = item.member_code ? (' <small class="text-muted">' + item.member_code + '</small>') : '';
                return $('<span>' + label + sub + '</span>');
            },
            templateSelection: function(item) {
                if (!item.id) return item.text || '';
                return item.full_name ? (item.member_code ? item.member_code + ' - ' + item.full_name : item.full_name) : item.text;
            }
        });

        $select.on('select2:select', function(e) {
            var member = e.params.data;
            $card.attr('data-member-id', member.id);
            $card.find('.member-name').text(member.full_name);
            $card.find('.member-code').text(member.member_code);
            loadMemberAccounts($card, member.id);
        });

        $card.find('.remove-member-btn').click(function() {
            var memberId = $card.attr('data-member-id');
            allocations = allocations.filter(a => a.member_id != memberId);
            $card.remove();
            updateAllocationsTable();
            updateAllocationStatus();
            if ($('#paid_for_container .member-allocation-card').length === 0) {
                $('#no_members_msg').show();
            }
        });
    });

    function loadMemberAccounts($card, memberId) {
        $.get('<?= site_url('admin/bank/get_member_details') ?>', { member_id: memberId }, function(response) {
            if (response.success) {
                var data = response.data;
                renderLoans($card.find('.loans-list'), data.loans, memberId);
                renderSavings($card.find('.savings-list'), data.savings, memberId);
                renderFines($card.find('.fines-list'), data.fines, memberId);
            }
        }, 'json');
    }

    function renderLoans($container, loans, memberId) {
        if (!loans || loans.length === 0) {
            $container.html('<small class="text-muted">No active loans</small>');
            return;
        }
        var html = '';
        loans.forEach(function(loan) {
            html += '<div class="border rounded p-2 mb-1 loan-item" data-loan-id="' + loan.id + '">';
            html += '<div class="d-flex justify-content-between"><strong>' + loan.loan_number + '</strong><span class="badge badge-info">₹' + parseFloat(loan.pending_amount).toLocaleString('en-IN') + '</span></div>';
            if (loan.installments && loan.installments.length > 0) {
                html += '<div class="mt-1">';
                loan.installments.forEach(function(inst) {
                    html += '<div class="d-flex justify-content-between align-items-center py-1 installment-row" data-inst-id="' + inst.id + '">';
                    html += '<small>EMI #' + inst.installment_number + ' - Due: ' + inst.due_date + '</small>';
                    html += '<div class="input-group input-group-sm" style="width: 120px;">';
                    html += '<input type="number" class="form-control allocation-input" data-type="emi" data-member="' + memberId + '" data-related="loan_' + inst.id + '" data-label="EMI #' + inst.installment_number + ' (' + loan.loan_number + ')" placeholder="₹" step="0.01" min="0" max="' + inst.pending_amount + '">';
                    html += '</div></div>';
                });
                html += '</div>';
            }
            html += '</div>';
        });
        $container.html(html);
    }

    function renderSavings($container, savings, memberId) {
        if (!savings || savings.length === 0) {
            $container.html('<small class="text-muted">No savings accounts</small>');
            return;
        }
        var html = '';
        savings.forEach(function(acc) {
            html += '<div class="border rounded p-2 mb-1">';
            html += '<div class="d-flex justify-content-between"><strong>' + acc.account_number + '</strong><span class="badge badge-success">₹' + parseFloat(acc.current_balance).toLocaleString('en-IN') + '</span></div>';
            html += '<div class="input-group input-group-sm mt-1">';
            html += '<input type="number" class="form-control allocation-input" data-type="savings" data-member="' + memberId + '" data-related="savings_' + acc.id + '" data-label="Savings ' + acc.account_number + '" placeholder="Deposit amount" step="0.01" min="0">';
            html += '</div></div>';
        });
        $container.html(html);
    }

    function renderFines($container, fines, memberId) {
        if (!fines || fines.length === 0) {
            $container.html('<small class="text-muted">No pending fines</small>');
            return;
        }
        var html = '';
        fines.forEach(function(fine) {
            html += '<div class="border rounded p-2 mb-1">';
            html += '<div class="d-flex justify-content-between"><small>' + fine.fine_type + '</small><span class="badge badge-danger">₹' + parseFloat(fine.pending_amount).toLocaleString('en-IN') + '</span></div>';
            html += '<div class="input-group input-group-sm mt-1">';
            html += '<input type="number" class="form-control allocation-input" data-type="fine" data-member="' + memberId + '" data-related="fine_' + fine.id + '" data-label="Fine: ' + fine.fine_type + '" placeholder="Pay amount" step="0.01" min="0" max="' + fine.pending_amount + '">';
            html += '</div></div>';
        });
        $container.html(html);
    }

    // Handle allocation input changes
    $(document).on('input', '.allocation-input', function() {
        var $input = $(this);
        var amount = parseFloat($input.val()) || 0;
        var type = $input.data('type');
        var memberId = $input.data('member');
        var related = $input.data('related');
        var label = $input.data('label');

        allocations = allocations.filter(a => !(a.member_id == memberId && a.related == related));

        if (amount > 0) {
            allocations.push({
                member_id: memberId,
                type: type,
                related: related,
                label: label,
                amount: amount
            });
        }

        updateAllocationsTable();
        updateAllocationStatus();
    });

    // Add manual/internal entry
    $('#add_manual_entry').click(function() {
        var type = $('#manual_match_type').val();
        var amount = parseFloat($('#manual_amount').val()) || transactionAmount - getTotalAllocated();
        
        if (!type) {
            toastr.warning('Please select an internal entry type');
            return;
        }
        if (amount <= 0) {
            toastr.warning('Amount must be greater than 0');
            return;
        }

        allocations.push({
            member_id: null,
            type: type,
            related: null,
            label: type.replace('_', ' ').toUpperCase(),
            amount: amount
        });

        $('#manual_match_type').val('');
        $('#manual_amount').val('');
        updateAllocationsTable();
        updateAllocationStatus();
    });

    function updateAllocationsTable() {
        var $tbody = $('#allocations_body');
        $tbody.empty();

        if (allocations.length === 0) {
            $tbody.html('<tr id="no_allocations_row"><td colspan="5" class="text-center text-muted py-3">No allocations yet</td></tr>');
            return;
        }

        allocations.forEach(function(alloc, index) {
            var memberName = alloc.member_id ? ($('.member-allocation-card[data-member-id="' + alloc.member_id + '"] .member-name').text() || 'Member #' + alloc.member_id) : '-';
            var row = '<tr data-index="' + index + '">';
            row += '<td>' + memberName + '</td>';
            row += '<td><span class="badge badge-' + getTypeBadgeClass(alloc.type) + '">' + alloc.type.toUpperCase() + '</span></td>';
            row += '<td>' + alloc.label + '</td>';
            row += '<td class="text-right">₹' + alloc.amount.toLocaleString('en-IN', {minimumFractionDigits: 2}) + '</td>';
            row += '<td><button type="button" class="btn btn-xs btn-danger remove-allocation-btn" data-index="' + index + '"><i class="fas fa-times"></i></button></td>';
            row += '</tr>';
            $tbody.append(row);
        });
    }

    function getTypeBadgeClass(type) {
        switch(type) {
            case 'emi': return 'primary';
            case 'savings': return 'success';
            case 'fine': return 'danger';
            case 'expense': return 'warning';
            default: return 'secondary';
        }
    }

    $(document).on('click', '.remove-allocation-btn', function() {
        var index = $(this).data('index');
        var alloc = allocations[index];
        allocations.splice(index, 1);
        
        if (alloc.member_id && alloc.related) {
            $('.allocation-input[data-member="' + alloc.member_id + '"][data-related="' + alloc.related + '"]').val('');
        }
        
        updateAllocationsTable();
        updateAllocationStatus();
    });

    function getTotalAllocated() {
        return allocations.reduce((sum, a) => sum + a.amount, 0);
    }

    function updateAllocationStatus() {
        var total = getTotalAllocated();
        var remaining = transactionAmount - total;
        var percent = transactionAmount > 0 ? (total / transactionAmount) * 100 : 0;

        $('#total_allocated').text('₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2}));
        $('#remaining_amount').text('₹' + remaining.toLocaleString('en-IN', {minimumFractionDigits: 2}));
        $('#footer_total').text('₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2}));
        $('#allocation_progress').css('width', Math.min(percent, 100) + '%');

        var $status = $('#allocation_status');
        var $error = $('#validation_error');
        var $btn = $('#confirmMatch');
        var $helper = $('#allocation_helper');

        if (total > transactionAmount) {
            $error.show();
            $('#validation_msg').text('Total allocated exceeds transaction amount');
            $helper.html('<i class="fas fa-exclamation-triangle"></i> Over-allocated! Please reduce amounts.');
            $btn.prop('disabled', true);
        } else if (allocations.length === 0) {
            $error.hide();
            $helper.html('<i class="fas fa-info-circle"></i> Add allocations to map this transaction');
            $btn.prop('disabled', true);
        } else if (remaining > 0) {
            $error.hide();
            $helper.html('<i class="fas fa-check-circle"></i> Partial mapping: ₹' + remaining.toLocaleString('en-IN', {minimumFractionDigits: 2}) + ' will remain unmapped');
            $btn.prop('disabled', false);
        } else {
            $error.hide();
            $helper.html('<i class="fas fa-check-circle"></i> Fully allocated! Ready to save.');
            $btn.prop('disabled', false);
        }
    }

    // Confirm and save mapping
    $('#confirmMatch').click(function() {
        var transactionId = $('#match_transaction_id').val();
        var payingMemberId = $('#paying_member').val();
        var notes = $('#mapping_notes').val();

        if (allocations.length === 0) {
            toastr.error('Please add at least one allocation');
            return;
        }

        var total = getTotalAllocated();
        if (total > transactionAmount) {
            toastr.error('Total allocated exceeds transaction amount');
            return;
        }
        
        if (total === 0) {
            toastr.error('Total allocated amount cannot be zero');
            return;
        }

        var mappings = allocations.map(function(a) {
            return {
                paying_member_id: payingMemberId,
                paid_for_member_id: a.member_id,
                transaction_type: a.type,
                related_account: a.related,
                amount: a.amount,
                remarks: notes
            };
        });

        var requestData = {
            transaction_id: transactionId,
            mappings: mappings,
            remarks: notes
        };

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '<?= site_url('admin/bank/save_transaction_mapping') ?>',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(requestData),
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    toastr.success(response.message || 'Transaction mapped successfully');
                    $('#matchModal').modal('hide');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    var errorMsg = (response && response.message) ? response.message : 'Failed to save mapping';
                    toastr.error(errorMsg);
                    $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Save Mapping');
                }
            },
            error: function(xhr, status, error) {
                var errorMsg = 'An error occurred while saving.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        errorMsg = resp.message || errorMsg;
                    } catch (e) {}
                }
                toastr.error(errorMsg);
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Save Mapping');
            }
        });
    });
});
</script>
