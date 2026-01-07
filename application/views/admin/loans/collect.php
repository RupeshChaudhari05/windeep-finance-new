<div class="row">
    <?php if (!$loan): ?>
    <!-- Loan Not Found -->
    <div class="col-12">
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Loan Not Found</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    The requested loan could not be found or you don't have permission to access it.
                </div>
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
                        <td class="font-weight-bold">₹<?= number_format($loan->principal_amount) ?></td>
                    </tr>
                    <tr>
                        <th>EMI:</th>
                        <td class="font-weight-bold text-primary">₹<?= number_format($loan->emi_amount) ?></td>
                    </tr>
                </table>
                
                <hr>
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Outstanding Principal:</span>
                    <strong class="text-danger">₹<?= number_format($loan->outstanding_principal) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Outstanding Interest:</span>
                    <strong>₹<?= number_format($loan->outstanding_interest) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pending Fines:</span>
                    <strong class="text-warning">₹<?= number_format($pending_fines ?? 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between border-top pt-2">
                    <span><strong>Total Due:</strong></span>
                    <strong class="text-danger">₹<?= number_format($loan->outstanding_principal + $loan->outstanding_interest + ($pending_fines ?? 0)) ?></strong>
                </div>
                
                <?php if ($overdue_emis): ?>
                <hr>
                <div class="alert alert-danger py-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong><?= count($overdue_emis) ?> EMI(s) Overdue</strong>
                    <br>
                    Amount: ₹<?= number_format(array_sum(array_column($overdue_emis, 'emi_amount'))) ?>
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
                            <td><?= format_date($emi->due_date, 'd M Y') ?></td>
                            <td class="text-right">₹<?= number_format($emi->emi_amount) ?></td>
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
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount">Payment Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-lg" id="amount" name="amount" 
                                       value="<?= $loan->emi_amount ?>" required min="1"
                                       placeholder="Enter amount" autofocus>
                                <small class="form-text text-muted">
                                    EMI: ₹<?= number_format($loan->emi_amount) ?>
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
                                1 EMI (₹<?= number_format($loan->emi_amount) ?>)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm quick-amount" data-amount="<?= $loan->emi_amount * 2 ?>" title="Pay 2 EMIs amount">
                                2 EMIs (₹<?= number_format($loan->emi_amount * 2) ?>)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm quick-amount" data-amount="<?= $loan->emi_amount * 3 ?>" title="Pay 3 EMIs amount">
                                3 EMIs (₹<?= number_format($loan->emi_amount * 3) ?>)
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm quick-amount" data-amount="<?= $loan->emi_amount * 6 ?>" title="Pay 6 EMIs amount">
                                6 EMIs (₹<?= number_format($loan->emi_amount * 6) ?>)
                            </button>
                            <?php if ($overdue_emis): ?>
                            <button type="button" class="btn btn-outline-danger btn-sm quick-amount"
                                    data-amount="<?= array_sum(array_column($overdue_emis, 'emi_amount')) ?>" title="Pay all overdue EMIs">
                                All Overdue (₹<?= number_format(array_sum(array_column($overdue_emis, 'emi_amount'))) ?>)
                            </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline-success btn-sm quick-amount"
                                    data-amount="<?= $loan->outstanding_principal + $loan->outstanding_interest ?>" title="Full loan settlement">
                                Full Settlement (₹<?= number_format($loan->outstanding_principal + $loan->outstanding_interest) ?>)
                            </button>
                        </div>
                        <small class="form-text text-muted">Selected option will be highlighted in blue. You can also type amount manually.</small>
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
                                    <div class="font-weight-bold" id="breakdownFine">₹0</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Towards Interest:</small>
                                    <div class="font-weight-bold text-warning" id="breakdownInterest">₹0</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Towards Principal:</small>
                                    <div class="font-weight-bold text-primary" id="breakdownPrincipal">₹0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-3">
                        <label for="remarks">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Payment notes (optional)"></textarea>
                    </div>
                    
                    <!-- Summary Alert -->
                    <div class="alert alert-success">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>After Payment:</strong>
                                <div>New Outstanding: <span class="font-weight-bold" id="newOutstanding">₹<?= number_format($loan->outstanding_principal) ?></span></div>
                            </div>
                            <div class="col-md-6">
                                <strong>EMIs Covered:</strong>
                                <div><span id="emisCovered">1</span> EMI(s) will be marked paid</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-check mr-1"></i> Record Payment
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
                            <td class="text-right">₹<?= number_format($pmt->amount) ?></td>
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
</style>

<script>
$(document).ready(function() {
    <?php if ($loan): ?>
    var outstandingPrincipal = <?= $loan->outstanding_principal ?>;
    var outstandingInterest = <?= $loan->outstanding_interest ?>;
    var pendingFines = <?= $pending_fines ?? 0 ?>;
    var emiAmount = <?= $loan->emi_amount ?>;
    
    // Quick amount buttons
    $('.quick-amount').on('click', function() {
        // Remove active class from all buttons
        $('.quick-amount').removeClass('active');
        // Add active class to clicked button
        $(this).addClass('active');
        // Set the amount
        $('#amount').val($(this).data('amount')).trigger('input');
    });
    
    // Calculate breakdown on amount change
    $('#amount').on('input', function() {
        // Remove active class when user types manually
        $('.quick-amount').removeClass('active');
        
        var amount = parseFloat($(this).val()) || 0;
        var remaining = amount;
        
        // First apply to fines
        var toFine = Math.min(remaining, pendingFines);
        remaining -= toFine;
        
        // Then to interest
        var toInterest = Math.min(remaining, outstandingInterest);
        remaining -= toInterest;
        
        // Rest to principal
        var toPrincipal = Math.min(remaining, outstandingPrincipal);
        
        $('#breakdownFine').text('₹' + toFine.toLocaleString());
        $('#breakdownInterest').text('₹' + toInterest.toLocaleString());
        $('#breakdownPrincipal').text('₹' + toPrincipal.toLocaleString());
        
        var newOutstanding = outstandingPrincipal - toPrincipal;
        $('#newOutstanding').text('₹' + newOutstanding.toLocaleString());
        
        var emisCovered = Math.floor(amount / emiAmount);
        $('#emisCovered').text(emisCovered || 'Partial');
    });
    
    // Trigger initial calculation
    $('#amount').trigger('input');
    
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
    <?php endif; ?>
});
</style>
