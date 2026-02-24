<!-- Bank Transactions with Mapping Interface -->
<style>
.transaction-row.selected { background-color: #e8f4f8 !important; }
.member-search-box { max-height: 250px; overflow-y: auto; }
.member-item { cursor: pointer; }
.member-item:hover { background-color: #f8f9fa; }
.member-item.selected { background-color: #d4edda; }
.mapping-done { background-color: #d4edda !important; }
.mapping-mismatch { background-color: #fff3cd !important; }
</style>

<div class="row mb-3">
    <div class="col-md-12">
        <!-- Filter Section -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter Transactions</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="filterForm" class="row align-items-end">
                    <div class="col-md-2">
                        <label>Select Bank</label>
                        <select name="bank_id" id="bank_filter" class="form-control">
                            <option value="">All Banks</option>
                            <?php foreach ($bank_accounts ?? [] as $bank): ?>
                            <option value="<?= $bank->id ?>" <?= ($filters['bank_id'] ?? '') == $bank->id ? 'selected' : '' ?>>
                                <?= $bank->bank_name ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>From Date</label>
                        <input type="date" name="from_date" id="from_date" class="form-control" 
                               value="<?= $filters['from_date'] ?? date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-2">
                        <label>To Date</label>
                        <input type="date" name="to_date" id="to_date" class="form-control" 
                               value="<?= $filters['to_date'] ?? date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Mapping Status</label>
                        <select name="mapping_status" id="mapping_status" class="form-control">
                            <option value="">All</option>
                            <option value="unmapped" <?= ($filters['mapping_status'] ?? '') == 'unmapped' ? 'selected' : '' ?>>Unmapped</option>
                            <option value="mapped" <?= ($filters['mapping_status'] ?? '') == 'mapped' ? 'selected' : '' ?>>Mapped</option>
                            <option value="mismatch" <?= ($filters['mapping_status'] ?? '') == 'mismatch' ? 'selected' : '' ?>>Mismatch</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search mr-1"></i> Show Transactions
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="<?= site_url('admin/bank/import') ?>" class="btn btn-success btn-block">
                            <i class="fas fa-file-import mr-1"></i> Import Excel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Panel - Transaction Table -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h3 class="card-title"><i class="fas fa-list mr-1"></i> Bank Transactions</h3>
                <div class="card-tools">
                    <span class="badge badge-light"><?= count($transactions ?? []) ?> Transactions</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0" id="transactionsTable">
                        <thead class="thead-dark">
                            <tr>
                                <th width="100">Trans ID</th>
                                <th>Bank Name</th>
                                <th>Trans Date</th>
                                <th>Description1</th>
                                <th>Description2</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                                <th class="text-right">Balance</th>
                                <th>Paid By</th>
                                <th>Mapping</th>
                                <th>Updated By</th>
                                <th>Paid For</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $txn): ?>
                            <tr class="transaction-row <?= $txn->mapping_status == 'mapped' ? 'mapping-done' : ($txn->mapping_status == 'mismatch' ? 'mapping-mismatch' : '') ?>" 
                                data-id="<?= $txn->id ?>" 
                                data-amount="<?= $txn->credit_amount ?: $txn->debit_amount ?>"
                                data-type="<?= $txn->credit_amount > 0 ? 'credit' : 'debit' ?>">
                                <td>
                                    <small class="text-muted"><?= $txn->transaction_code ?? 'AUTO-' . $txn->id ?></small>
                                </td>
                                <td><?= $txn->bank_name ?? '-' ?></td>
                                <td><?= format_date($txn->transaction_date, 'd/m/Y') ?></td>
                                <td><?= character_limiter($txn->description, 20) ?></td>
                                <td><?= character_limiter($txn->description2 ?? '', 20) ?></td>
                                <td class="text-right text-danger">
                                    <?= $txn->debit_amount > 0 ? number_format($txn->debit_amount, 2) : '' ?>
                                </td>
                                <td class="text-right text-success">
                                    <?= $txn->credit_amount > 0 ? number_format($txn->credit_amount, 2) : '' ?>
                                </td>
                                <td class="text-right"><?= number_format($txn->running_balance ?? 0, 2) ?></td>
                                <td>
                                    <?php if ($txn->paid_by_member_id): ?>
                                    <small><?= $txn->paid_by_name ?? 'Member ID' ?></small>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $status_class = [
                                        'mapped' => 'success',
                                        'mismatch' => 'warning', 
                                        'unmapped' => 'secondary'
                                    ];
                                    $status_text = [
                                        'mapped' => 'Done',
                                        'mismatch' => 'Mismatch',
                                        'unmapped' => 'Pending'
                                    ];
                                    ?>
                                    <span class="badge badge-<?= $status_class[$txn->mapping_status ?? 'unmapped'] ?>">
                                        <?= $status_text[$txn->mapping_status ?? 'unmapped'] ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= $txn->updated_by_name ?? '-' ?></small>
                                </td>
                                <td>
                                    <?php if ($txn->paid_for_member_id): ?>
                                    <small><?= $txn->paid_for_name ?? 'Member ID' ?></small>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No transactions found. Click "Show Transactions" to load data.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <?php if (!empty($transactions)): ?>
                <div class="card-footer d-flex justify-content-center">
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item"><a class="page-link" href="#"><i class="fas fa-angle-double-left"></i></a></li>
                            <li class="page-item"><a class="page-link" href="#"><i class="fas fa-angle-left"></i></a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#"><i class="fas fa-angle-right"></i></a></li>
                            <li class="page-item"><a class="page-link" href="#"><i class="fas fa-angle-double-right"></i></a></li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Selected Transaction Details -->
        <div class="card" id="selectedTransactionCard" style="display: none;">
            <div class="card-header bg-info text-white">
                <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Selected Transaction</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><strong>Trans ID:</strong> <span id="sel_trans_id">-</span></div>
                    <div class="col-md-3"><strong>Bank:</strong> <span id="sel_bank">-</span></div>
                    <div class="col-md-3"><strong>Date:</strong> <span id="sel_date">-</span></div>
                    <div class="col-md-3"><strong>Amount:</strong> <span id="sel_amount">-</span></div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12"><strong>Description:</strong> <span id="sel_desc">-</span></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Panel - Member Search & Mapping -->
    <div class="col-md-4">
        <!-- Paying Member Section -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-1"></i> Paying Member</h3>
            </div>
            <div class="card-body">
                <div class="input-group mb-2">
                    <input type="text" id="paying_member_search" class="form-control" placeholder="Search by name/code/phone...">
                    <div class="input-group-append">
                        <button class="btn btn-success" id="searchPayingMember">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
                <div class="member-search-box border rounded p-2" id="payingMemberResults">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="payingMemberList">
                            <tr><td colspan="3" class="text-center text-muted">Search for members</td></tr>
                        </tbody>
                    </table>
                </div>
                <input type="hidden" id="selected_paying_member_id">
                <div id="selectedPayingMember" class="mt-2 p-2 bg-success text-white rounded" style="display: none;">
                    <strong>Selected:</strong> <span id="paying_member_name"></span>
                </div>
            </div>
        </div>
        
        <!-- Paid For Member Section -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-friends mr-1"></i> Paid for Member</h3>
            </div>
            <div class="card-body">
                <div class="input-group mb-2">
                    <input type="text" id="paid_for_member_search" class="form-control" placeholder="Search by name/code/phone...">
                    <div class="input-group-append">
                        <button class="btn btn-primary" id="searchPaidForMember">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
                <div class="member-search-box border rounded p-2" id="paidForMemberResults">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="paidForMemberList">
                            <tr><td colspan="3" class="text-center text-muted">Search for members</td></tr>
                        </tbody>
                    </table>
                </div>
                <input type="hidden" id="selected_paid_for_member_id">
                <div id="selectedPaidForMember" class="mt-2 p-2 bg-primary text-white rounded" style="display: none;">
                    <strong>Selected:</strong> <span id="paid_for_member_name"></span>
                </div>
            </div>
        </div>
        
        <!-- Transaction Type Selection -->
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tags mr-1"></i> Transaction Type</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <select id="transaction_type" class="form-control">
                        <option value="">Select Type...</option>
                        <optgroup label="Member Transactions">
                            <option value="emi">EMI Payment</option>
                            <option value="savings">Savings Contribution</option>
                            <option value="fine">Fine Payment</option>
                            <option value="late_fee">Late Fee</option>
                            <option value="membership_fee">Membership Fee</option>
                            <option value="loan_disbursement">Loan Disbursement</option>
                            <option value="withdrawal">Savings Withdrawal</option>
                        </optgroup>
                        <optgroup label="Expenses">
                            <option value="expense_stationery">Stationery</option>
                            <option value="expense_travelling">Travelling</option>
                            <option value="expense_electricity">Electricity</option>
                            <option value="expense_rent">Rent</option>
                            <option value="expense_salary">Salary</option>
                            <option value="expense_printing">Printing & Postage</option>
                            <option value="expense_telephone">Telephone / Internet</option>
                            <option value="expense_maintenance">Maintenance</option>
                            <option value="expense_legal">Legal & Professional</option>
                            <option value="expense_misc">Miscellaneous Expense</option>
                        </optgroup>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group" id="relatedAccountGroup" style="display: none;">
                    <label>Related Account</label>
                    <select id="related_account" class="form-control">
                        <option value="">Select...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Remarks</label>
                    <textarea id="mapping_remarks" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="btn-group btn-group-lg w-100">
                    <button class="btn btn-success" id="saveMapping" disabled>
                        <i class="fas fa-check mr-1"></i> Save Mapping
                    </button>
                    <button class="btn btn-secondary" id="clearSelection">
                        <i class="fas fa-times mr-1"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var selectedTransaction = null;
    var selectedPayingMember = null;
    var selectedPaidForMember = null;
    
    // Click on transaction row
    $(document).on('click', '.transaction-row', function() {
        $('.transaction-row').removeClass('selected');
        $(this).addClass('selected');
        
        selectedTransaction = {
            id: $(this).data('id'),
            amount: $(this).data('amount'),
            type: $(this).data('type')
        };
        
        // Show selected transaction details
        $('#selectedTransactionCard').show();
        $('#sel_trans_id').text($(this).find('td:eq(0)').text());
        $('#sel_bank').text($(this).find('td:eq(1)').text());
        $('#sel_date').text($(this).find('td:eq(2)').text());
        $('#sel_amount').text($(this).find('td:eq(6)').text() || $(this).find('td:eq(5)').text());
        $('#sel_desc').text($(this).find('td:eq(3)').text() + ' ' + $(this).find('td:eq(4)').text());
        
        updateSaveButton();
    });
    
    // Search Paying Member
    $('#searchPayingMember').click(function() {
        searchMembers('paying');
    });
    
    $('#paying_member_search').keypress(function(e) {
        if (e.which == 13) {
            e.preventDefault();
            searchMembers('paying');
        }
    });
    
    // Search Paid For Member
    $('#searchPaidForMember').click(function() {
        searchMembers('paidfor');
    });
    
    $('#paid_for_member_search').keypress(function(e) {
        if (e.which == 13) {
            e.preventDefault();
            searchMembers('paidfor');
        }
    });
    
    function searchMembers(type) {
        var search = type == 'paying' ? $('#paying_member_search').val() : $('#paid_for_member_search').val();
        var listId = type == 'paying' ? '#payingMemberList' : '#paidForMemberList';
        
        if (search.length < 2) {
            toastr.warning('Please enter at least 2 characters');
            return;
        }
        
        $.ajax({
            url: '<?= site_url('admin/bank/search_members') ?>',
            type: 'POST',
            data: { search: search, <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>' },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.members.length > 0) {
                    var html = '';
                    response.members.forEach(function(member) {
                        html += '<tr class="member-item" data-type="' + type + '" data-id="' + member.id + '" data-name="' + member.first_name + ' ' + member.last_name + ' (' + member.member_code + ')">';
                        html += '<td>' + member.member_code + '</td>';
                        html += '<td>' + member.first_name + ' ' + member.last_name + '</td>';
                        html += '<td><span class="badge badge-' + (member.status == 'active' ? 'success' : 'secondary') + '">' + (member.status || 'Active') + '</span></td>';
                        html += '</tr>';
                    });
                    $(listId).html(html);
                } else {
                    $(listId).html('<tr><td colspan="3" class="text-center text-muted">No members found</td></tr>');
                }
            }
        });
    }
    
    // Select member from list
    $(document).on('click', '.member-item', function() {
        var type = $(this).data('type');
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        if (type == 'paying') {
            selectedPayingMember = { id: id, name: name };
            $('#selected_paying_member_id').val(id);
            $('#paying_member_name').text(name);
            $('#selectedPayingMember').show();
            $(this).addClass('selected').siblings().removeClass('selected');
        } else {
            selectedPaidForMember = { id: id, name: name };
            $('#selected_paid_for_member_id').val(id);
            $('#paid_for_member_name').text(name);
            $('#selectedPaidForMember').show();
            $(this).addClass('selected').siblings().removeClass('selected');
        }
        
        updateSaveButton();
        loadMemberAccounts(id, type);
    });
    
    function loadMemberAccounts(memberId, type) {
        if (type == 'paidfor') {
            $.ajax({
                url: '<?= site_url('admin/bank/get_member_accounts') ?>',
                type: 'POST',
                data: { member_id: memberId, <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var html = '<option value="">Select...</option>';
                        if (response.savings_accounts) {
                            response.savings_accounts.forEach(function(acc) {
                                html += '<option value="savings_' + acc.id + '">Savings: ' + acc.account_number + ' (₹' + acc.balance + ')</option>';
                            });
                        }
                        if (response.loans) {
                            response.loans.forEach(function(loan) {
                                html += '<option value="loan_' + loan.id + '">Loan: ' + loan.loan_number + ' (₹' + loan.outstanding + ' due)</option>';
                            });
                        }
                        $('#related_account').html(html);
                    }
                }
            });
        }
    }
    
    // Transaction type change
    $('#transaction_type').change(function() {
        var type = $(this).val();
        if (type == 'emi' || type == 'savings' || type == 'withdrawal') {
            $('#relatedAccountGroup').show();
        } else {
            $('#relatedAccountGroup').hide();
        }
        updateSaveButton();
    });
    
    function updateSaveButton() {
        var valid = selectedTransaction && selectedPayingMember && $('#transaction_type').val();
        $('#saveMapping').prop('disabled', !valid);
    }
    
    // Save Mapping
    $('#saveMapping').click(function() {
        if (!selectedTransaction || !selectedPayingMember) {
            toastr.error('Please select a transaction and paying member');
            return;
        }
        
        var data = {
            transaction_id: selectedTransaction.id,
            paying_member_id: selectedPayingMember.id,
            paid_for_member_id: selectedPaidForMember ? selectedPaidForMember.id : selectedPayingMember.id,
            transaction_type: $('#transaction_type').val(),
            related_account: $('#related_account').val(),
            remarks: $('#mapping_remarks').val(),
            <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
        };
        
        $.ajax({
            url: '<?= site_url('admin/bank/save_transaction_mapping') ?>',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success('Transaction mapped successfully');
                    // Update row styling
                    $('.transaction-row[data-id="' + selectedTransaction.id + '"]')
                        .removeClass('selected mapping-mismatch')
                        .addClass('mapping-done')
                        .find('td:eq(9)').html('<span class="badge badge-success">Done</span>');
                    
                    clearSelection();
                } else {
                    toastr.error(response.message || 'Failed to save mapping');
                }
            },
            error: function() {
                toastr.error('An error occurred');
            }
        });
    });
    
    // Clear Selection
    $('#clearSelection').click(function() {
        clearSelection();
    });
    
    function clearSelection() {
        selectedTransaction = null;
        selectedPayingMember = null;
        selectedPaidForMember = null;
        
        $('.transaction-row').removeClass('selected');
        $('#selectedTransactionCard').hide();
        $('#selectedPayingMember').hide();
        $('#selectedPaidForMember').hide();
        $('#selected_paying_member_id').val('');
        $('#selected_paid_for_member_id').val('');
        $('#paying_member_search').val('');
        $('#paid_for_member_search').val('');
        $('#transaction_type').val('');
        $('#mapping_remarks').val('');
        $('#relatedAccountGroup').hide();
        $('.member-item').removeClass('selected');
        
        updateSaveButton();
    }
});
</script>
