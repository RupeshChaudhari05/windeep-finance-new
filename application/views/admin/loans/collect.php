<div class="row">
    <?php if (!$loan): ?>
    <!-- Loan Not Found -->
    <div class="col-12">
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Loan Not Found</h3>
            </div>
            <div class="card-body">
                <p><i class="fas fa-ban mr-1"></i> The requested loan could not be found or you don't have permission to access it.</p>
                <a href="<?= site_url('admin/loans') ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Loans
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Loan Info -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-contract mr-1"></i> Loan Details</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="mb-0"><?= $loan->loan_number ?></h4>
                    <?php $status_class = ['active' => 'success', 'overdue' => 'warning', 'npa' => 'danger', 'closed' => 'secondary']; ?>
                    <span class="badge badge-<?= $status_class[$loan->status] ?? 'secondary' ?>">
                        <?= strtoupper($loan->status ?? 'unknown') ?>
                    </span>
                </div>
                
                <table class="table table-sm table-borderless">
                    <tr>
                        <th>Member:</th>
                        <td>
                            <a href="<?= site_url('admin/members/view/' . $member->id) ?>">
                                <?= $member->first_name ?> <?= $member->last_name ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>Code:</th>
                        <td><?= $member->member_code ?></td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td><a href="tel:<?= $member->phone ?>"><?= $member->phone ?></a></td>
                    </tr>
                    <tr>
                        <th>Product:</th>
                        <td><span class="badge badge-info"><?= $product->product_name ?></span></td>
                    </tr>
                    <tr>
                        <th>Principal:</th>
                        <td class="font-weight-bold"><?= format_amount($loan->principal_amount, 0) ?></td>
                    </tr>
                    <tr>
                        <th>EMI:</th>
                        <td class="font-weight-bold text-primary"><?= format_amount($loan->emi_amount, 0) ?></td>
                    </tr>
                </table>
                
                <hr>
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Outstanding Principal:</span>
                    <strong class="text-danger"><?= format_amount($loan->outstanding_principal, 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Outstanding Interest:</span>
                    <strong><?= format_amount($loan->outstanding_interest, 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pending Fines:</span>
                    <strong class="text-warning"><?= format_amount($pending_fines ?? 0, 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between border-top pt-2">
                    <span><strong>Total Due:</strong></span>
                    <strong class="text-danger"><?= format_amount($loan->outstanding_principal + $loan->outstanding_interest + ($pending_fines ?? 0), 0) ?></strong>
                </div>
                
                <?php if ($overdue_emis): ?>
                <hr>
                <div class="card card-danger mb-0">
                    <div class="card-body py-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong><?= count($overdue_emis) ?> EMI(s) Overdue</strong>
                        <br>
                        Amount: <?= get_currency_symbol() ?><?= number_format(array_sum(array_column($overdue_emis, 'emi_amount'))) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Pending EMIs -->
        <?php if (!empty($pending_emis)): ?>
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Pending EMIs</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Due Date</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($pending_emis, 0, 6) as $emi): ?>
                        <tr class="<?= safe_timestamp($emi->due_date) < time() ? 'table-danger' : '' ?>">
                            <td><?= $emi->installment_number ?></td>
                            <td><?= format_date($emi->due_date) ?></td>
                            <td class="text-right"><?= format_amount($emi->emi_amount, 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Collection Form -->
    <div class="col-md-8">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-rupee-sign mr-1"></i> Collect EMI Payment</h3>
            </div>
            <form action="<?= site_url('admin/loans/record_payment/' . ($loan->id ?? '')) ?>" method="post" id="collectionForm">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <input type="hidden" name="loan_id" value="<?= $loan->id ?>">
                <input type="hidden" name="payment_type" id="payment_type" value="emi">
                <input type="hidden" name="total_amount" id="total_amount" value="<?= $loan->emi_amount ?>">
                <?php 
                    // Get next pending installment for interest-only calculation
                    $next_pending = null;
                    if (!empty($pending_emis)) {
                        $next_pending = $pending_emis[0] ?? null;
                    } elseif (!empty($overdue_emis)) {
                        $next_pending = $overdue_emis[0] ?? null;
                    }
                    $interest_for_next = $next_pending ? ($next_pending->interest_amount - $next_pending->interest_paid) : 0;
                    $principal_for_next = $next_pending ? ($next_pending->principal_amount - $next_pending->principal_paid) : 0;
                ?>
                <input type="hidden" name="installment_id" id="installment_id" value="<?= $next_pending->id ?? '' ?>">
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount">Payment Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-lg" id="amount" name="amount" 
                                       value="<?= $loan->emi_amount ?>" required min="1"
                                       placeholder="Enter amount" autofocus>
                                <small class="form-text text-muted">
                                    EMI: <?= format_amount($loan->emi_amount, 0) ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-lg" id="payment_date" name="payment_date" 
                                       value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Amount Buttons -->
                    <div class="mb-3">
                        <label class="d-block">Quick Select: <small class="text-muted">(Click to auto-fill amount)</small></label>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-outline-primary btn-sm quick-amount" data-amount="<?= $loan->emi_amount ?>" title="Pay 1 EMI amount">
                                1 EMI (<?= format_amount($loan->emi_amount, 0) ?>)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm quick-amount" data-amount="<?= $loan->emi_amount * 2 ?>" title="Pay 2 EMIs amount">
                                2 EMIs (<?= format_amount($loan->emi_amount * 2, 0) ?>)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm quick-amount" data-amount="<?= $loan->emi_amount * 3 ?>" title="Pay 3 EMIs amount">
                                3 EMIs (<?= format_amount($loan->emi_amount * 3, 0) ?>)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm quick-amount" data-amount="<?= $loan->emi_amount * 6 ?>" title="Pay 6 EMIs amount">
                                6 EMIs (<?= format_amount($loan->emi_amount * 6, 0) ?>)
                            </button>
                            <?php if ($overdue_emis): ?>
                            <button type="button" class="btn btn-outline-danger btn-sm quick-amount"
                                    data-amount="<?= array_sum(array_column($overdue_emis, 'emi_amount')) ?>" title="Pay all overdue EMIs">
                                All Overdue (<?= get_currency_symbol() ?><?= number_format(array_sum(array_column($overdue_emis, 'emi_amount'))) ?>)
                            </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline-success btn-sm quick-amount"
                                    data-amount="<?= $loan->outstanding_principal + $loan->outstanding_interest ?>" title="Full loan settlement">
                                Full Settlement (<?= format_amount($loan->outstanding_principal + $loan->outstanding_interest, 0) ?>)
                            </button>
                            <?php if ($next_pending && $interest_for_next > 0): ?>
                            <button type="button" class="btn btn-outline-warning btn-sm quick-amount" id="btnInterestOnly"
                                    data-amount="<?= round($interest_for_next, 2) ?>" 
                                    data-interest-only="1"
                                    title="Pay only interest for this month (₹<?= number_format($interest_for_next, 2) ?>). Principal will be deferred and tenure extended.">
                                <i class="fas fa-clock mr-1"></i> Interest Only (<?= format_amount($interest_for_next, 0) ?>)
                            </button>
                            <?php endif; ?>
                        </div>
                        <small class="form-text text-muted">Selected option will be highlighted in blue. You can also type amount manually.</small>
                    </div>
                    
                    <!-- Interest-Only Payment Alert (shown when amount < EMI) -->
                    <div class="alert alert-warning d-none" id="interestOnlyAlert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle fa-2x mr-3 mt-1"></i>
                            <div class="flex-grow-1">
                                <h6 class="alert-heading mb-1">
                                    <i class="fas fa-hand-holding-usd mr-1"></i> Partial Payment Detected
                                </h6>
                                <p class="mb-2">
                                    Payment amount is less than EMI (<?= format_amount($loan->emi_amount, 0) ?>).
                                </p>
                                <div class="custom-control custom-switch mb-2">
                                    <input type="checkbox" class="custom-control-input" id="interestOnlySwitch">
                                    <label class="custom-control-label font-weight-bold" for="interestOnlySwitch">
                                        Pay Interest Only &amp; Defer Principal
                                    </label>
                                </div>
                                <div id="interestOnlyDetails" class="d-none">
                                    <div class="card bg-white mb-0">
                                        <div class="card-body py-2 px-3">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <small class="text-muted">Interest Due:</small>
                                                    <div class="font-weight-bold text-warning">
                                                        <?= get_currency_symbol() ?><span id="interestDueAmount"><?= number_format($interest_for_next, 2) ?></span>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <small class="text-muted">Principal Deferred:</small>
                                                    <div class="font-weight-bold text-info">
                                                        <?= get_currency_symbol() ?><span id="principalDeferredAmount"><?= number_format($principal_for_next, 2) ?></span>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <small class="text-muted">New Tenure:</small>
                                                    <div class="font-weight-bold text-danger">
                                                        <span id="newTenure"><?= ($loan->tenure_months ?? 0) + 1 ?></span> months
                                                        <small class="text-muted">(was <?= $loan->original_tenure_months ?? $loan->tenure_months ?>)</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr class="my-2">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Principal will be added as a new installment at the end of the loan schedule.
                                                Extensions used: <strong><span id="extensionsUsed"><?= $loan->tenure_extensions ?? 0 ?></span> / <span id="maxExtensions"><?= $loan->max_tenure_extensions ?? 6 ?></span></strong>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_mode">Payment Mode <span class="text-danger">*</span></label>
                                <select class="form-control" id="payment_mode" name="payment_mode" required>
                                    <option value="cash">💵 Cash</option>
                                    <option value="upi">📱 UPI</option>
                                    <option value="bank_transfer">🏦 Bank Transfer</option>
                                    <option value="cheque">📝 Cheque</option>
                                    <option value="online">💻 Online Payment</option>
                                    <option value="adjustment">🔄 Adjustment</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_reference">Reference Number</label>
                                <input type="text" class="form-control" id="payment_reference" name="payment_reference" 
                                       placeholder="UPI ID / Transaction ID / Cheque No">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Breakdown -->
                    <div class="card bg-light">
                        <div class="card-header py-2">
                            <strong>Payment Breakdown (Auto-calculated)</strong>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">Towards Fine:</small>
                                    <div class="font-weight-bold" id="breakdownFine"><?= get_currency_symbol() ?>0</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Towards Interest:</small>
                                    <div class="font-weight-bold text-warning" id="breakdownInterest"><?= get_currency_symbol() ?>0</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Towards Principal:</small>
                                    <div class="font-weight-bold text-primary" id="breakdownPrincipal"><?= get_currency_symbol() ?>0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-3">
                        <label for="remarks">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Payment notes (optional)"></textarea>
                    </div>
                    
                    <!-- Summary Card -->
                    <div class="card mb-0" id="summaryCard">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>After Payment:</strong>
                                    <div>New Outstanding: <span class="font-weight-bold" id="newOutstanding"><?= format_amount($loan->outstanding_principal, 0) ?></span></div>
                                </div>
                                <div class="col-md-4">
                                    <strong>EMIs Covered:</strong>
                                    <div><span id="emisCovered">1</span> EMI(s) will be marked paid</div>
                                </div>
                                <div class="col-md-4">
                                    <strong>Payment Type:</strong>
                                    <div><span id="paymentTypeLabel" class="badge badge-success">Regular EMI</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                        <i class="fas fa-check mr-1"></i> <span id="submitBtnText">Record Payment</span>
                    </button>
                    <a href="<?= site_url('admin/loans/view/' . $loan->id) ?>" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Recent Payments -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-1"></i> Recent Payments</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Receipt</th>
                            <th class="text-right">Amount</th>
                            <th>Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recent_payments ?? [], 0, 5) as $pmt): ?>
                        <tr>
                            <td><?= format_date($pmt->payment_date) ?></td>
                            <td><small><?= $pmt->receipt_number ?></small></td>
                            <td class="text-right"><?= format_amount($pmt->amount, 0) ?></td>
                            <td><small><?= ucfirst($pmt->payment_mode) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_payments)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No recent payments</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.quick-amount.active {
    background-color: #007bff !important;
    border-color: #007bff !important;
    color: white !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
.quick-amount[data-interest-only="1"].active {
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
    color: #212529 !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}
#interestOnlyAlert {
    transition: all 0.3s ease;
}
</style>

<script>
$(document).ready(function() {
    <?php if ($loan): ?>
    var outstandingPrincipal = <?= $loan->outstanding_principal ?>;
    var outstandingInterest = <?= $loan->outstanding_interest ?>;
    var pendingFines = <?= $pending_fines ?? 0 ?>;
    var emiAmount = <?= $loan->emi_amount ?>;
    var interestForNext = <?= $interest_for_next ?>;
    var principalForNext = <?= $principal_for_next ?>;
    var currentTenure = <?= $loan->tenure_months ?>;
    var isInterestOnlyMode = false;
    var CS = '<?= get_currency_symbol() ?>';
    
    // Quick amount buttons
    $('.quick-amount').on('click', function() {
        $('.quick-amount').removeClass('active');
        $(this).addClass('active');
        var amt = parseFloat($(this).data('amount'));
        $('#amount').val(amt);
        
        // If Interest Only button clicked, auto-enable the switch
        if ($(this).data('interest-only') == 1) {
            if (!$('#interestOnlySwitch').is(':checked')) {
                $('#interestOnlySwitch').prop('checked', true).trigger('change');
            }
        } else {
            // Regular button clicked - disable interest-only if it was on
            if ($('#interestOnlySwitch').is(':checked')) {
                $('#interestOnlySwitch').prop('checked', false).trigger('change');
            }
        }
        
        updateBreakdown(amt);
    });
    
    // Interest-Only toggle switch
    $('#interestOnlySwitch').on('change', function() {
        isInterestOnlyMode = $(this).is(':checked');
        
        if (isInterestOnlyMode) {
            // Switch to interest-only mode
            $('#interestOnlyDetails').removeClass('d-none');
            $('#payment_type').val('interest_only');
            $('#collectionForm').attr('action', '<?= site_url('admin/loans/interest_only_payment') ?>');
            $('#submitBtn').removeClass('btn-success').addClass('btn-warning');
            $('#submitBtnText').text('Record Interest-Only Payment');
            $('#summaryCard').removeClass('card-success').addClass('card-warning');
            $('#paymentTypeLabel').removeClass('badge-success').addClass('badge-warning').text('Interest Only');
            
            // Set amount to interest only if currently less than EMI
            var currentAmount = parseFloat($('#amount').val()) || 0;
            if (currentAmount < emiAmount) {
                $('#amount').val(interestForNext.toFixed(2));
            }
            
            updateBreakdown(parseFloat($('#amount').val()));
        } else {
            // Switch back to regular mode
            $('#interestOnlyDetails').addClass('d-none');
            $('#payment_type').val('emi');
            $('#collectionForm').attr('action', '<?= site_url('admin/loans/record_payment/' . $loan->id) ?>');
            $('#submitBtn').removeClass('btn-warning').addClass('btn-success');
            $('#submitBtnText').text('Record Payment');
            $('#summaryCard').removeClass('card-warning').addClass('card-success');
            $('#paymentTypeLabel').removeClass('badge-warning').addClass('badge-success').text('Regular EMI');
            
            updateBreakdown(parseFloat($('#amount').val()));
        }
    });
    
    // Calculate breakdown on amount change
    $('#amount').on('input', function() {
        $('.quick-amount').removeClass('active');
        var amount = parseFloat($(this).val()) || 0;
        
        // Show/hide interest-only alert when amount < EMI
        if (amount > 0 && amount < emiAmount && interestForNext > 0) {
            $('#interestOnlyAlert').removeClass('d-none');
        } else {
            $('#interestOnlyAlert').addClass('d-none');
            // Reset to regular mode if amount >= EMI
            if (amount >= emiAmount && isInterestOnlyMode) {
                $('#interestOnlySwitch').prop('checked', false).trigger('change');
            }
        }
        
        updateBreakdown(amount);
    });
    
    function updateBreakdown(amount) {
        // Update hidden total_amount
        $('#total_amount').val(amount);
        
        var remaining = amount;
        var toFine = 0, toInterest = 0, toPrincipal = 0;
        
        if (isInterestOnlyMode) {
            // Interest-only mode: all goes to interest, no principal
            toInterest = Math.min(remaining, interestForNext);
            remaining -= toInterest;
            
            // Excess to fine
            toFine = Math.min(remaining, pendingFines);
            remaining -= toFine;
            
            toPrincipal = 0; // No principal in interest-only mode
            
            $('#breakdownFine').text(CS + toFine.toLocaleString('en-IN'));
            $('#breakdownInterest').text(CS + toInterest.toLocaleString('en-IN'));
            $('#breakdownPrincipal').html('<span class="text-muted"><i class="fas fa-clock"></i> Deferred</span>');
            
            var newOutstanding = outstandingPrincipal; // Principal unchanged
            $('#newOutstanding').text(CS + newOutstanding.toLocaleString('en-IN'));
            $('#emisCovered').html('<span class="badge badge-warning">Interest Only</span>');
        } else {
            // Regular mode: Fine → Interest → Principal (RBI order)
            toFine = Math.min(remaining, pendingFines);
            remaining -= toFine;
            
            toInterest = Math.min(remaining, outstandingInterest);
            remaining -= toInterest;
            
            toPrincipal = Math.min(remaining, outstandingPrincipal);
            
            $('#breakdownFine').text(CS + toFine.toLocaleString('en-IN'));
            $('#breakdownInterest').text(CS + toInterest.toLocaleString('en-IN'));
            $('#breakdownPrincipal').text(CS + toPrincipal.toLocaleString('en-IN'));
            
            var newOutstanding = outstandingPrincipal - toPrincipal;
            $('#newOutstanding').text(CS + newOutstanding.toLocaleString('en-IN'));
            
            var emisCovered = Math.floor(amount / emiAmount);
            $('#emisCovered').text(emisCovered || 'Partial');
        }
    }
    
    // Trigger initial calculation
    $('#amount').trigger('input');
    
    // Form submit validation
    $('#collectionForm').on('submit', function(e) {
        var amount = parseFloat($('#amount').val());
        if (amount <= 0) {
            e.preventDefault();
            Swal.fire('Error', 'Please enter a valid amount', 'error');
            return false;
        }
        
        if (isInterestOnlyMode) {
            if (amount < interestForNext) {
                e.preventDefault();
                Swal.fire('Error', 'Amount must be at least ' + CS + interestForNext.toFixed(2) + ' to cover interest.', 'error');
                return false;
            }
            
            e.preventDefault();
            Swal.fire({
                title: 'Interest-Only Payment',
                html: '<div class="text-left">' +
                      '<p>This will:</p>' +
                      '<ul>' +
                      '<li>Pay <strong>interest only</strong> (' + CS + interestForNext.toFixed(2) + ')</li>' +
                      '<li>Defer principal of ' + CS + principalForNext.toFixed(2) + '</li>' +
                      '<li>Add 1 extra installment at end of schedule</li>' +
                      '<li>Extend loan tenure to <strong>' + (currentTenure + 1) + ' months</strong></li>' +
                      '</ul>' +
                      '<p class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i> This cannot be easily reversed.</p>' +
                      '</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Yes, Record Interest-Only',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Recording interest-only payment...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: function() { Swal.showLoading(); }
                    });
                    document.getElementById('collectionForm').submit();
                }
            });
            return false;
        }
        
        Swal.fire({
            title: 'Processing...',
            text: 'Recording payment of ' + CS + amount.toLocaleString('en-IN'),
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: function() { Swal.showLoading(); }
        });
    });
    <?php endif; ?>
});
</script>
