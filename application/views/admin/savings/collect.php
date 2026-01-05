<div class="row">
    <!-- Account Info -->
    <div class="col-md-4">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-piggy-bank mr-1"></i> Account Details</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <?php if ($account): ?>
                    <tr>
                        <th>Account No:</th>
                        <td class="font-weight-bold"><?= $account->account_number ?></td>
                    </tr>
                    <tr>
                        <th>Member:</th>
                        <td>
                            <a href="<?= site_url('admin/members/view/' . ($member->id ?? '#')) ?>">
                                <?= ($member->first_name ?? '-') ?> <?= ($member->last_name ?? '') ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td>
                            <?php if (!empty($member->phone)): ?>
                                <a href="tel:<?= $member->phone ?>"><?= $member->phone ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Scheme:</th>
                        <td><span class="badge badge-info"><?= $scheme->scheme_name ?? '-' ?></span></td>
                    </tr>
                    <tr>
                        <th>Monthly Amt:</th>
                        <td class="font-weight-bold text-primary">₹<?= number_format($account->monthly_amount) ?></td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td colspan="2" class="text-center text-muted">Please select an account to collect payment.</td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <hr>

                <?php if ($account): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Current Balance:</span>
                    <strong class="text-success">₹<?= number_format($account->current_balance) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Deposited:</span>
                    <strong>₹<?= number_format($account->total_deposited) ?></strong>
                </div>
                <?php endif; ?>

                <?php if ($pending_dues): ?>
                <div class="d-flex justify-content-between text-danger">
                    <span>Pending Dues:</span>
                    <strong><?= count($pending_dues) ?> installment(s)</strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Pending Dues -->
        <?php if ($pending_dues): ?>
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Pending Dues</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-right">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($pending_dues, 0, 6) as $due): ?>
                        <tr class="<?= safe_timestamp($due->due_date) < time() ? 'table-danger' : '' ?>">
                            <td><?= format_date($due->due_date, 'M Y') ?></td>
                            <td class="text-right">₹<?= number_format($due->due_amount) ?></td>
                            <td>
                                <?php if (safe_timestamp($due->due_date) < time()): ?>
                                    <span class="badge badge-danger">Overdue</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($pending_dues) > 6): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                <small>+<?= count($pending_dues) - 6 ?> more pending</small>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th>Total:</th>
                            <th class="text-right text-danger">
                                ₹<?= number_format(array_sum(array_column($pending_dues, 'due_amount'))) ?>
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Collection Form -->
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-rupee-sign mr-1"></i> Collect Savings Payment</h3>
            </div>
            <?php if ($account): ?>
            <form action="<?= site_url('admin/savings/record_payment/' . $account->id) ?>" method="post" id="collectionForm">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount">Collection Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-lg" id="amount" name="amount" 
                                       value="<?= $account->monthly_amount ?>" required min="1"
                                       placeholder="Enter amount" autofocus>
                                <small class="form-text text-muted">
                                    Monthly: ₹<?= number_format($account->monthly_amount) ?>
                                    <?php if ($pending_dues): ?>
                                        | Pending Total: ₹<?= number_format(array_sum(array_column($pending_dues, 'due_amount'))) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="transaction_date">Transaction Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-lg" id="transaction_date" name="transaction_date" 
                                       value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Amount Buttons -->
                    <div class="mb-3">
                        <label class="d-block">Quick Select:</label>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary quick-amount" data-amount="<?= $account->monthly_amount ?>">
                                1 Month (₹<?= number_format($account->monthly_amount) ?>)
                            </button>
                            <button type="button" class="btn btn-outline-primary quick-amount" data-amount="<?= $account->monthly_amount * 3 ?>">
                                3 Months (₹<?= number_format($account->monthly_amount * 3) ?>)
                            </button>
                            <button type="button" class="btn btn-outline-primary quick-amount" data-amount="<?= $account->monthly_amount * 6 ?>">
                                6 Months (₹<?= number_format($account->monthly_amount * 6) ?>)
                            </button>
                            <?php if ($pending_dues): ?>
                            <button type="button" class="btn btn-outline-danger quick-amount" data-amount="<?= array_sum(array_column($pending_dues, 'amount')) ?>">
                                All Pending (₹<?= number_format(array_sum(array_column($pending_dues, 'amount'))) ?>)
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_mode">Payment Mode <span class="text-danger">*</span></label>
                                <select class="form-control" id="payment_mode" name="payment_mode" required>
                                    <option value="cash">💵 Cash</option>
                                    <option value="upi">📱 UPI</option>
                                    <option value="bank_transfer">🏦 Bank Transfer / NEFT / IMPS</option>
                                    <option value="cheque">📝 Cheque</option>
                                    <option value="online">💻 Online Payment</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_reference">Reference Number</label>
                                <input type="text" class="form-control" id="payment_reference" name="payment_reference" 
                                       placeholder="UPI ID / Transaction ID / Cheque No">
                                <small class="form-text text-muted">Required for non-cash payments</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2" 
                                  placeholder="Any notes about this payment (optional)"></textarea>
                    </div>
                    
                    <!-- Summary -->
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Collection Summary:</strong>
                                <div class="mt-2">
                                    <span>Amount: </span>
                                    <span class="font-weight-bold" id="summaryAmount">₹<?= number_format($account->monthly_amount) ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <strong>After Collection:</strong>
                                <div class="mt-2">
                                    <span>New Balance: </span>
                                    <span class="font-weight-bold text-success" id="summaryNewBalance">
                                        ₹<?= number_format($account->current_balance + $account->monthly_amount) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-check mr-1"></i> Collect Payment
                    </button>
                    <a href="<?= site_url('admin/savings/view/' . $account->id) ?>" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                    <button type="button" class="btn btn-info btn-lg float-right" id="printReceipt" disabled>
                        <i class="fas fa-print mr-1"></i> Print Receipt
                    </button>
                </div>
            </form>            <?php else: ?>
                <div class="card-body">
                    <div class="alert alert-warning mb-0">No account selected. Please choose an account to record a collection.</div>
                </div>
            <?php endif; ?>        </div>
        
        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-1"></i> Recent Transactions</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Receipt</th>
                            <th>Type</th>
                            <th class="text-right">Amount</th>
                            <th>Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recent_transactions ?? [], 0, 5) as $txn): ?>
                        <tr>
                            <td><?= format_date($txn->transaction_date) ?></td>
                            <td><small><?= $txn->receipt_number ?></small></td>
                            <td><span class="badge badge-success"><?= ucfirst($txn->transaction_type) ?></span></td>
                            <td class="text-right">₹<?= number_format($txn->credit_amount) ?></td>
                            <td><small><?= ucfirst($txn->payment_mode) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_transactions)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">No recent transactions</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($account): ?>
<script>
$(document).ready(function() {
    var currentBalance = <?= $account->current_balance ?>;
    
    // Quick amount buttons
    $('.quick-amount').on('click', function() {
        var amount = $(this).data('amount');
        $('#amount').val(amount);
        updateSummary();
    });
    
    // Amount change
    $('#amount').on('input', updateSummary);
    
    function updateSummary() {
        var amount = parseFloat($('#amount').val()) || 0;
        $('#summaryAmount').text('₹' + amount.toLocaleString());
        $('#summaryNewBalance').text('₹' + (currentBalance + amount).toLocaleString());
    }
    
    // Payment mode change - require reference for non-cash
    $('#payment_mode').on('change', function() {
        if ($(this).val() !== 'cash') {
            $('#payment_reference').attr('required', true);
            $('#payment_reference').closest('.form-group').find('small').html('<span class="text-danger">Required for this payment mode</span>');
        } else {
            $('#payment_reference').removeAttr('required');
            $('#payment_reference').closest('.form-group').find('small').text('Required for non-cash payments');
        }
    });
    
    // Form submit
    $('#collectionForm').on('submit', function(e) {
        var amount = parseFloat($('#amount').val());
        if (amount <= 0) {
            e.preventDefault();
            Swal.fire('Error', 'Please enter a valid amount', 'error');
            return false;
        }
        
        Swal.fire({
            title: 'Processing...',
            text: 'Recording payment of ₹' + amount.toLocaleString(),
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => { Swal.showLoading(); }
        });
    });
});
</script>
<?php endif; ?>
