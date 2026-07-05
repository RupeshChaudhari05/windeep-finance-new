<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Left Column: Request Details -->
        <div class="col-lg-8">
            <!-- Request Summary -->
            <div class="card card-primary mb-3">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">
                        <i class="fas fa-file-contract mr-2"></i>Foreclosure Request #<?= $request->id ?>
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
                                if ($request->status === 'pending') $badge = 'badge-warning';
                                elseif ($request->status === 'approved') $badge = 'badge-success';
                                elseif ($request->status === 'rejected') $badge = 'badge-danger';
                                ?>
                                <span class="badge <?= $badge ?> text-uppercase" style="font-size: 14px;">
                                    <?= $request->status ?>
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
                                <strong>Interest Charge (<?= $breakdown['interest_charge_pct'] ?? 80 ?>%):</strong>
                                <small class="text-muted d-block">
                                    <?= $breakdown['interest_charge_pct'] ?? 80 ?>% of total pending interest
                                    (admin configured)
                                </small>
                            </td>
                            <td class="text-right">₹<?= number_format($breakdown['interest_charge'] ?? 0, 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Pending Fines:</strong></td>
                            <td class="text-right">₹<?= number_format($breakdown['pending_fines'] ?? 0, 2) ?></td>
                        </tr>
                        <tr class="border-top bg-light">
                            <td><strong style="font-size:16px;">Total Settlement Amount:</strong></td>
                            <td class="text-right"><strong style="font-size:16px;">₹<?= number_format($breakdown['total_amount'] ?? 0, 2) ?></strong></td>
                        </tr>
                    </table>

                    <div class="mt-2 p-2 rounded" style="background:#f8f9fa;border:1px solid #dee2e6;">
                        <strong>Formula:</strong>
                        <small class="d-block text-muted">
                            Settlement = Principal + (Total Interest × Admin %) + Fines
                        </small>
                        <strong class="mt-2 d-block">Amount to Collect from Member:</strong>
                        <span class="float-right font-weight-bold text-primary" style="font-size:1.2em;">
                            ₹<?= number_format((float)$request->foreclosure_amount, 2) ?>
                        </span>
                        <small class="text-muted d-block">(As requested and stored — recalculated at approval time)</small>
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
            <?php if ($request->status === 'pending'): ?>
            <div class="card card-outline card-warning">
                <div class="card-header bg-warning">
                    <h3 class="card-title">Take Action</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Choose to approve or reject this foreclosure request:</p>

                    <button class="btn btn-success btn-block mb-2 process-btn" data-action="approve" data-id="<?= $request->id ?>">
                        <i class="fas fa-check mr-2"></i>Approve Foreclosure
                    </button>
                    <button class="btn btn-danger btn-block process-btn" data-action="reject" data-id="<?= $request->id ?>">
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
                    <p class="text-muted">This request has already been <strong><?= $request->status ?></strong></p>
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
                    <input type="hidden" id="requestId" name="request_id" value="<?= $request->id ?>">
                    <input type="hidden" id="actionField" name="action">

                    <div class="form-group">
                        <label for="remarksField"><strong>Remarks / Comments</strong></label>
                        <textarea class="form-control" id="remarksField" name="remarks" rows="3" 
                                  placeholder="Enter remarks for this decision (required)"></textarea>
                        <small class="form-text text-muted">This will be recorded in the audit log</small>
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
            $('#paymentDetails').show();
            $('#paymentMode').prop('required', true);
            $('#transactionId').prop('required', true);
            $('#paymentDate').prop('required', true);
            $('#confirmBtn').removeClass('btn-danger').addClass('btn-success').text('Approve');
        } else {
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

        // For approval, validate payment fields
        if (action === 'approve') {
            var paymentMode = $('#paymentMode').val().trim();
            var transactionId = $('#transactionId').val().trim();
            var paymentDate = $('#paymentDate').val();

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
