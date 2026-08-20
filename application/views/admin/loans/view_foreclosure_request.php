<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Live figures used by the approval form (recalculated on this page load)
$fc_principal     = (float)($breakdown['outstanding_principal'] ?? 0);
$fc_total_int     = (float)($breakdown['total_interest'] ?? 0);
$fc_pct           = (float)($breakdown['interest_charge_pct'] ?? 30);
$fc_int_charge    = (float)($breakdown['interest_charge'] ?? 0);
$fc_fines         = (float)($breakdown['pending_fines'] ?? 0);
$fc_total         = (float)($breakdown['total_amount'] ?? 0);
?>

<div class="container-fluid">
    <div class="row">
        <!-- Left Column: Request Details -->
        <div class="col-lg-8">
            <!-- Request Summary -->
            <div class="card card-primary mb-3">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">
                        <i class="fas fa-file-contract mr-2"></i>Foreclosure Request #<?= $request->request_id ?>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Loan Number:</strong> 
                                <a href="<?= site_url('admin/loans/view/' . $request->loan_id) ?>" class="text-primary">
                                    <?= $request->loan_number ?>
                                </a>
                            </p>
                            <p><strong>Request Date:</strong> <?= date('d-M-Y H:i', strtotime($request->requested_at)) ?></p>
                            <p><strong>Settlement Date:</strong> <?= date('d-M-Y', strtotime($request->settlement_date)) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p>
                                <strong>Status:</strong>
                                <?php
                                $badge = 'badge-secondary';
                                if ($request->request_status === 'pending') $badge = 'badge-warning';
                                elseif ($request->request_status === 'approved') $badge = 'badge-success';
                                elseif ($request->request_status === 'rejected') $badge = 'badge-danger';
                                ?>
                                <span class="badge <?= $badge ?> text-uppercase" style="font-size: 14px;">
                                    <?= $request->request_status ?>
                                </span>
                            </p>
                            <?php if ($request->processed_at): ?>
                            <p><strong>Processed Date:</strong> <?= date('d-M-Y H:i', strtotime($request->processed_at)) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Member Details -->
            <div class="card card-info mb-3">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title"><i class="fas fa-user mr-2"></i>Member Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Member Code:</strong> <?= $request->member_code ?></p>
                            <p><strong>Name:</strong> <?= $request->first_name ?> <?= $request->last_name ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Phone:</strong> <a href="tel:<?= $request->phone ?>"><?= $request->phone ?></a></p>
                            <p><strong>Email:</strong> <a href="mailto:<?= $request->email ?>"><?= $request->email ?></a></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settlement Breakdown -->
            <div class="card card-success mb-3">
                <div class="card-header bg-success text-white d-flex align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-calculator mr-2"></i>Settlement Amount Breakdown</h3>
                    <span class="badge badge-light ml-auto text-dark" style="font-size:13px;">Foreclosure</span>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td><strong>Outstanding Principal:</strong></td>
                            <td class="text-right">₹<?= number_format($breakdown['outstanding_principal'] ?? 0, 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total Pending Interest (Current + Future Months):</strong></td>
                            <td class="text-right">₹<?= number_format($breakdown['total_interest'] ?? 0, 2) ?></td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Interest Charge (<?= $fc_pct ?>%):</strong>
                                <small class="text-muted d-block">
                                    <?= $fc_pct ?>% of total remaining interest
                                    (admin configured — editable on approval)
                                </small>
                            </td>
                            <td class="text-right">₹<?= number_format($fc_int_charge, 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Pending Fines:</strong></td>
                            <td class="text-right">₹<?= number_format($fc_fines, 2) ?></td>
                        </tr>
                        <tr class="border-top bg-light">
                            <td><strong style="font-size:16px;">Total Settlement Amount:</strong></td>
                            <td class="text-right"><strong style="font-size:16px;">₹<?= number_format($fc_total, 2) ?></strong></td>
                        </tr>
                    </table>

                    <div class="mt-2 p-2 rounded" style="background:#f8f9fa;border:1px solid #dee2e6;">
                        <strong>Formula:</strong>
                        <small class="d-block text-muted">
                            Settlement = Principal + (Remaining Interest × Charge %) + Fines
                        </small>
                        <div class="mt-2 d-flex justify-content-between">
                            <strong>Amount requested by member:</strong>
                            <span class="text-muted">₹<?= number_format((float)$request->foreclosure_amount, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <strong>Amount to collect (recalculated now):</strong>
                            <span class="font-weight-bold text-primary" style="font-size:1.2em;">
                                ₹<?= number_format($fc_total, 2) ?>
                            </span>
                        </div>
                        <?php if (!empty($request->approved_amount)): ?>
                        <div class="d-flex justify-content-between mt-1 pt-1 border-top">
                            <strong>Approved &amp; collected:</strong>
                            <span class="font-weight-bold text-success" style="font-size:1.2em;">
                                ₹<?= number_format((float)$request->approved_amount, 2) ?>
                                <?php if (!empty($request->approved_interest_pct)): ?>
                                <small class="text-muted">(@ <?= (float)$request->approved_interest_pct ?>%)</small>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php else: ?>
                        <small class="text-muted d-block mt-1">The admin can adjust the % and the final amount while approving.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Reason for Foreclosure -->
            <div class="card card-warning mb-3">
                <div class="card-header bg-warning">
                    <h3 class="card-title"><i class="fas fa-comment mr-2"></i>Reason for Foreclosure</h3>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($request->reason)) ?></p>
                </div>
            </div>

            <!-- Admin Remarks (if processed) -->
            <?php if ($request->admin_comments): ?>
            <div class="card card-secondary mb-3">
                <div class="card-header bg-secondary text-white">
                    <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Admin Remarks</h3>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($request->admin_comments)) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Loan Summary & Action -->
        <div class="col-lg-4">
            <!-- Loan Summary -->
            <div class="card card-outline card-primary mb-3">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">Loan Summary</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-6">Principal:</dt>
                        <dd class="col-sm-6 text-right">₹<?= number_format($request->principal_amount ?? 0, 2) ?></dd>

                        <dt class="col-sm-6">Outstanding Principal:</dt>
                        <dd class="col-sm-6 text-right">₹<?= number_format($request->outstanding_principal ?? 0, 2) ?></dd>

                        <dt class="col-sm-6">Outstanding Interest:</dt>
                        <dd class="col-sm-6 text-right">₹<?= number_format($request->outstanding_interest ?? 0, 2) ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Action Buttons -->
            <?php if ($request->request_status === 'pending'): ?>
            <div class="card card-outline card-warning">
                <div class="card-header bg-warning">
                    <h3 class="card-title">Take Action</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Choose to approve or reject this foreclosure request:</p>

                    <button class="btn btn-success btn-block mb-2 process-btn" data-action="approve" data-id="<?= $request->request_id ?>">
                        <i class="fas fa-check mr-2"></i>Approve Foreclosure
                    </button>
                    <button class="btn btn-danger btn-block process-btn" data-action="reject" data-id="<?= $request->request_id ?>">
                        <i class="fas fa-times mr-2"></i>Reject Request
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="card card-outline card-success">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title">Request Status</h3>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted">This request has already been <strong><?= $request->request_status ?></strong></p>
                    <a href="<?= site_url('admin/loans/foreclosure_requests') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Requests
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Payments -->
            <?php if (!empty($payments)): ?>
            <div class="card card-outline card-info mt-3">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title">Recent Payments (Last 10)</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Date</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $pmt): ?>
                            <tr>
                                <td><small><?= date('d-M-Y', strtotime($pmt->payment_date)) ?></small></td>
                                <td class="text-right"><small>₹<?= number_format($pmt->total_amount ?? 0, 2) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Installments Table -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card card-outline card-secondary">
                <div class="card-header bg-secondary text-white">
                    <h3 class="card-title">Loan Installments (EMI Schedule)</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>#</th>
                                    <th>Due Date</th>
                                    <th class="text-right">EMI</th>
                                    <th class="text-right">Principal</th>
                                    <th class="text-right">Interest</th>
                                    <th class="text-right">Paid</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($installments as $inst): ?>
                                <tr class="<?= $inst->status === 'paid' ? 'table-success' : ($inst->status === 'overdue' ? 'table-danger' : '') ?>">
                                    <td>#<?= $inst->installment_number ?></td>
                                    <td><?= date('d-M-Y', strtotime($inst->due_date)) ?></td>
                                    <td class="text-right">₹<?= number_format($inst->emi_amount ?? 0, 2) ?></td>
                                    <td class="text-right">₹<?= number_format($inst->principal_amount ?? 0, 2) ?></td>
                                    <td class="text-right">₹<?= number_format($inst->interest_amount ?? 0, 2) ?></td>
                                    <td class="text-right">₹<?= number_format($inst->total_paid ?? 0, 2) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $inst->status === 'paid' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($inst->status) ?>
                                        </span>
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
</div>

<!-- Process Modal -->
<div class="modal fade" id="processModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Process Request</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="processForm">
                    <input type="hidden" id="requestId" name="request_id" value="<?= $request->request_id ?>">
                    <input type="hidden" id="actionField" name="action">

                    <div class="form-group">
                        <label for="remarksField"><strong>Remarks / Comments</strong></label>
                        <textarea class="form-control" id="remarksField" name="remarks" rows="3" 
                                  placeholder="Enter remarks for this decision (required)"></textarea>
                        <small class="form-text text-muted">This will be recorded in the audit log</small>
                    </div>

                    <!-- Settlement Amount (Only for Approve) -->
                    <div id="settlementDetails" style="display: none;">
                        <hr>
                        <h6 class="text-primary">
                            <i class="fas fa-sliders-h mr-2"></i>Settlement Amount
                            <small class="text-muted">(editable — adjust before approving)</small>
                        </h6>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="interestPct"><strong>Interest Charge %</strong></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="interestPct"
                                               name="interest_charge_pct"
                                               value="<?= $fc_pct ?>" min="0" max="100" step="0.01">
                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                    </div>
                                    <small class="form-text text-muted">
                                        % of remaining interest (₹<?= number_format($fc_total_int, 2) ?>)
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><strong>Interest Charged</strong></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                        <input type="text" class="form-control bg-light" id="interestCharge" readonly>
                                    </div>
                                    <small class="form-text text-muted">Remaining Interest × %</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="finalAmount"><strong>Final Amount to Collect</strong></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                        <input type="number" class="form-control font-weight-bold" id="finalAmount"
                                               name="final_amount" step="0.01" min="0.01"
                                               value="<?= number_format($fc_total, 2, '.', '') ?>">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="resetAmount"
                                                    title="Reset to calculated amount">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted" id="amountHint">Auto-calculated. Type to override.</small>
                                </div>
                            </div>
                        </div>

                        <table class="table table-sm table-borderless mb-0" style="font-size:13px;background:#f8f9fa;">
                            <tr>
                                <td>Outstanding Principal</td>
                                <td class="text-right">₹<?= number_format($fc_principal, 2) ?></td>
                            </tr>
                            <tr>
                                <td>Interest Charge (<span id="pctEcho"><?= $fc_pct ?></span>% of ₹<?= number_format($fc_total_int, 2) ?>)</td>
                                <td class="text-right">₹<span id="intEcho">0.00</span></td>
                            </tr>
                            <tr>
                                <td>Pending Fines</td>
                                <td class="text-right">₹<?= number_format($fc_fines, 2) ?></td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>Calculated Total</strong></td>
                                <td class="text-right"><strong>₹<span id="calcTotalEcho">0.00</span></strong></td>
                            </tr>
                        </table>
                        <div class="alert alert-warning py-2 mb-0 mt-2" id="overrideWarning" style="display:none;font-size:13px;">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            The final amount differs from the calculated total by
                            <strong>₹<span id="diffEcho">0.00</span></strong>. This adjustment will be recorded in the audit log.
                        </div>
                    </div>

                    <!-- Payment Details (Only for Approve) -->
                    <div id="paymentDetails" style="display: none;">
                        <hr>
                        <h6 class="text-primary"><i class="fas fa-money-check mr-2"></i>Payment Details</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="paymentMode"><strong>Payment Mode</strong></label>
                                    <select class="form-control" id="paymentMode" name="payment_mode" required>
                                        <option value="">-- Select --</option>
                                        <option value="cash">Cash</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="bank_transfer">Bank Transfer / NEFT / RTGS</option>
                                        <option value="upi">UPI / Online Payment</option>
                                        <option value="auto_debit">Auto Debit</option>
                                    </select>
                                    <small class="form-text text-muted">Method of payment received</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transactionId"><strong>Transaction ID / Reference</strong></label>
                                    <input type="text" class="form-control" id="transactionId" name="transaction_id" 
                                           placeholder="e.g., TRN123456 or Cheque #789012" required>
                                    <small class="form-text text-muted">Reference number for payment tracking</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="paymentDate"><strong>Payment Date</strong></label>
                            <input type="date" class="form-control" id="paymentDate" name="payment_date" required>
                            <small class="form-text text-muted">Date payment was received</small>
                        </div>
                    </div>

                    <div class="alert alert-info" id="actionInfo">
                        <strong>Action:</strong> <span id="actionText"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // ── Live settlement figures ────────────────────────────────────────
    var FC_PRINCIPAL = <?= json_encode(round($fc_principal, 2)) ?>;
    var FC_TOTAL_INT = <?= json_encode(round($fc_total_int, 2)) ?>;
    var FC_FINES     = <?= json_encode(round($fc_fines, 2)) ?>;
    var FC_PCT       = <?= json_encode(round($fc_pct, 2)) ?>;

    var amountTouched = false;

    function money(n) {
        return (Math.round(n * 100) / 100).toFixed(2)
               .replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function calcTotal() {
        var pct = parseFloat($('#interestPct').val());
        if (isNaN(pct) || pct < 0) { pct = 0; }
        if (pct > 100) { pct = 100; }
        var intCharge = Math.round(FC_TOTAL_INT * pct) / 100;
        return {
            pct: pct,
            interest: intCharge,
            total: Math.round((FC_PRINCIPAL + intCharge + FC_FINES) * 100) / 100
        };
    }

    function refresh(syncAmount) {
        var c = calcTotal();
        $('#pctEcho').text(c.pct);
        $('#intEcho').text(money(c.interest));
        $('#interestCharge').val(money(c.interest));
        $('#calcTotalEcho').text(money(c.total));

        if (syncAmount) {
            $('#finalAmount').val(c.total.toFixed(2));
        }

        var entered = parseFloat($('#finalAmount').val());
        var diff = (isNaN(entered) ? 0 : entered) - c.total;
        if (Math.abs(diff) >= 0.01) {
            $('#diffEcho').text(money(Math.abs(diff)) + (diff > 0 ? ' more' : ' less'));
            $('#overrideWarning').show();
            $('#amountHint').text('Manually overridden.');
        } else {
            $('#overrideWarning').hide();
            $('#amountHint').text(amountTouched ? 'Matches calculated total.' : 'Auto-calculated. Type to override.');
        }
    }

    // Changing the % recalculates the amount unless the admin typed one manually
    $('#interestPct').on('input change', function() { refresh(!amountTouched); });
    $('#finalAmount').on('input', function() { amountTouched = true; refresh(false); });
    $('#resetAmount').click(function() { amountTouched = false; $('#interestPct').val(FC_PCT); refresh(true); });

    // Set today's date as default payment date
    $('#paymentDate').val(new Date().toISOString().split('T')[0]);

    // Process Button
    $('.process-btn').click(function() {
        var action = $(this).data('action');
        var requestId = $(this).data('id');

        $('#actionField').val(action);
        $('#actionText').text(action.charAt(0).toUpperCase() + action.slice(1));
        $('#modalTitle').text('Process Foreclosure - ' + (action === 'approve' ? 'Approve' : 'Reject'));

        // Show payment fields only for approve
        if (action === 'approve') {
            $('#settlementDetails').show();
            $('#paymentDetails').show();
            $('#paymentMode').prop('required', true);
            $('#transactionId').prop('required', true);
            $('#paymentDate').prop('required', true);
            $('#confirmBtn').removeClass('btn-danger').addClass('btn-success').text('Approve');
            amountTouched = false;
            $('#interestPct').val(FC_PCT);
            refresh(true);
        } else {
            $('#settlementDetails').hide();
            $('#paymentDetails').hide();
            $('#paymentMode').prop('required', false);
            $('#transactionId').prop('required', false);
            $('#paymentDate').prop('required', false);
            $('#confirmBtn').removeClass('btn-success').addClass('btn-danger').text('Reject');
        }

        $('#processModal').modal('show');
    });

    // Confirm
    $('#confirmBtn').click(function() {
        var remarks = $('#remarksField').val().trim();
        var action = $('#actionField').val();
        
        if (!remarks) {
            alert('Please enter remarks');
            return;
        }

        // For approval, validate settlement + payment fields
        if (action === 'approve') {
            var paymentMode = $('#paymentMode').val().trim();
            var transactionId = $('#transactionId').val().trim();
            var paymentDate = $('#paymentDate').val();
            var pct = parseFloat($('#interestPct').val());
            var amount = parseFloat($('#finalAmount').val());

            if (isNaN(pct) || pct < 0 || pct > 100) {
                alert('Interest charge % must be between 0 and 100');
                return;
            }
            if (isNaN(amount) || amount <= 0) {
                alert('Please enter a valid final settlement amount');
                return;
            }
            if (!paymentMode) {
                alert('Please select payment mode');
                return;
            }
            if (!transactionId) {
                alert('Please enter transaction ID or reference number');
                return;
            }
            if (!paymentDate) {
                alert('Please select payment date');
                return;
            }

            var calculated = calcTotal().total;
            if (Math.abs(amount - calculated) >= 0.01) {
                if (!confirm('The final amount (' + money(amount) + ') differs from the calculated total ('
                             + money(calculated) + ').\n\nApprove with the adjusted amount?')) {
                    return;
                }
            }
        }

        var formData = $('#processForm').serialize();

        $.ajax({
            url: '<?= site_url("admin/loans/process_foreclosure_request") ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#confirmBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    window.location.href = response.redirect || '<?= site_url("admin/loans/foreclosure_requests") ?>';
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Server error. Please try again.');
            },
            complete: function() {
                $('#confirmBtn').prop('disabled', false).text('Confirm');
                $('#processModal').modal('hide');
            }
        });
    });
});
</script>
