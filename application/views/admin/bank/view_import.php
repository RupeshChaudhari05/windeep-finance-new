<!-- Bank Import Details -->
<div class="row">
    <div class="col-md-4">
        <!-- Import Summary -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-import mr-1"></i> Import Details</h3>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr>
                        <td class="text-muted">Import Code:</td>
                        <td><strong><?= $import->import_code ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Bank Account:</td>
                        <td><?= $import->bank_name ?> - <?= $import->account_number ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Import Date:</td>
                        <td><?= format_date_time($import->imported_at, 'd M Y h:i A') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Imported By:</td>
                        <td><?= $import->imported_by_name ?? 'System' ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Transactions:</td>
                        <td><span class="badge badge-info"><?= $import->total_transactions ?? count($transactions) ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Auto Matched:</td>
                        <td><span class="badge badge-success"><?= $import->mapped_count ?? 0 ?></span></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Summary Stats -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Transaction Summary</h3>
            </div>
            <div class="card-body p-0">
                <?php
                $credits = 0; $debits = 0; $matched = 0; $unmatched = 0;
                foreach ($transactions as $t) {
                    if ($t->transaction_type == 'credit') $credits += $t->amount;
                    else $debits += $t->amount;
                    if ($t->mapping_status == 'mapped') $matched++;
                    else $unmatched++;
                }
                ?>
                <table class="table mb-0">
                    <tr class="bg-success text-white">
                        <td>Total Credits</td>
                        <td class="text-right">₹<?= number_format($credits, 2) ?></td>
                    </tr>
                    <tr class="bg-danger text-white">
                        <td>Total Debits</td>
                        <td class="text-right">₹<?= number_format($debits, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Net Amount</td>
                        <td class="text-right font-weight-bold">₹<?= number_format($credits - $debits, 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Match Status -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Matched</span>
                    <span class="badge badge-success"><?= $matched ?></span>
                </div>
                <div class="progress mb-3" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: <?= count($transactions) ? ($matched / count($transactions) * 100) : 0 ?>%"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Unmatched</span>
                    <span class="badge badge-warning"><?= $unmatched ?></span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-warning" style="width: <?= count($transactions) ? ($unmatched / count($transactions) * 100) : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Transactions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-1"></i> Transactions</h3>
                <div class="card-tools">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-default filter-btn active" data-filter="all">All</button>
                        <button class="btn btn-default filter-btn" data-filter="unmapped">Unmatched</button>
                        <button class="btn btn-default filter-btn" data-filter="mapped">Matched</button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="transactionsTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Reference</th>
                                <th class="text-right">Amount</th>
                                <th>Status</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $txn): ?>
                            <tr data-status="<?= $txn->mapping_status ?>">
                                <td><?= format_date($txn->transaction_date, 'd M Y') ?></td>
                                <td>
                                    <?= character_limiter($txn->description, 40) ?>
                                    <?php if ($txn->mapping_status == 'mapped'): ?>
                                    <br><small class="text-success"><i class="fas fa-link"></i> Mapped</small>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= $txn->reference_number ?: '-' ?></small></td>
                                <td class="text-right">
                                    <span class="text-<?= $txn->transaction_type == 'credit' ? 'success' : 'danger' ?>">
                                        <?= $txn->transaction_type == 'credit' ? '+' : '-' ?>₹<?= number_format($txn->amount, 2) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($txn->mapping_status == 'mapped'): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Matched</span>
                                    <?php elseif ($txn->mapping_status == 'partial'): ?>
                                    <span class="badge badge-warning"><i class="fas fa-clock"></i> Partial</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary"><i class="fas fa-question"></i> Unmatched</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($txn->mapping_status != 'mapped'): ?>
                                    <button class="btn btn-info btn-sm btn-match" data-id="<?= $txn->id ?>" data-amount="<?= $txn->amount ?>" data-type="<?= $txn->transaction_type ?>">
                                        <i class="fas fa-link"></i> Match
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-default btn-sm" disabled>
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
    </div>
</div>

<!-- Match Transaction Modal -->
<div class="modal fade" id="matchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-link mr-1"></i> Match Transaction</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="match_transaction_id">
                
                <div class="alert alert-info">
                    <strong>Transaction Amount:</strong> <span id="match_amount"></span>
                </div>
                
                <ul class="nav nav-tabs" id="matchTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#savingsTab">Savings Payments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#loansTab">Loan Payments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#manualTab">Manual Entry</a>
                    </li>
                </ul>
                
                <div class="tab-content mt-3">
                    <div class="tab-pane active" id="savingsTab">
                        <div class="table-responsive" style="max-height: 300px;">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Account</th>
                                        <th>Date</th>
                                        <th class="text-right">Amount</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="savingsMatches">
                                    <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="loansTab">
                        <div class="table-responsive" style="max-height: 300px;">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Loan #</th>
                                        <th>Date</th>
                                        <th class="text-right">Amount</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="loanMatches">
                                    <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="manualTab">
                        <div class="form-group">
                            <label>Match Type</label>
                            <select id="manual_match_type" class="form-control">
                                <option value="other_income">Other Income</option>
                                <option value="expense">Expense</option>
                                <option value="transfer">Internal Transfer</option>
                                <option value="ignore">Ignore</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea id="manual_description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" id="confirmMatch">
                    <i class="fas fa-link"></i> Match
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#transactionsTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 50
    });
    
    // Filter buttons
    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        var filter = $(this).data('filter');
        
        $('#transactionsTable tbody tr').each(function() {
            if (filter == 'all' || $(this).data('status') == filter) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Match button
    $('.btn-match').click(function() {
        var id = $(this).data('id');
        var amount = $(this).data('amount');
        
        $('#match_transaction_id').val(id);
        $('#match_amount').text('₹' + parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2}));
        
        // Load potential matches
        loadPotentialMatches(amount);
        
        $('#matchModal').modal('show');
    });
    
    function loadPotentialMatches(amount) {
        // Would load via AJAX in production
        $('#savingsMatches').html('<tr><td colspan="5" class="text-center text-muted">Search for matching transactions...</td></tr>');
        $('#loanMatches').html('<tr><td colspan="5" class="text-center text-muted">Search for matching transactions...</td></tr>');
    }
    
    // Confirm match
    $('#confirmMatch').click(function() {
        var transactionId = $('#match_transaction_id').val();
        var activeTab = $('#matchTabs .nav-link.active').attr('href');
        var matchType, matchId;
        
        if (activeTab == '#manualTab') {
            matchType = $('#manual_match_type').val();
            matchId = 0;
        }
        
        $.post('<?= site_url('admin/bank/match_transaction') ?>', {
            transaction_id: transactionId,
            match_type: matchType,
            match_id: matchId
        }, function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message);
            }
        }, 'json');
    });
});
</script>
