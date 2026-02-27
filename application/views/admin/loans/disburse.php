<!-- Disburse Loan -->
<form method="post" action="<?= site_url('admin/loans/disburse/' . $application->id) ?>" id="disburseForm">
    <div class="row">
        <div class="col-md-8">
            <!-- Application Details -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title"><i class="fas fa-check-circle mr-1"></i> Approved Loan - Ready for Disbursement</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Application Details</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td>Application #:</td>
                                    <td><strong><?= $application->application_number ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Loan Product:</td>
                                    <td><?= $application->product_name ?? $product->name ?></td>
                                </tr>
                                <tr>
                                    <td>Requested Amount:</td>
                                    <td><?= format_amount($application->requested_amount) ?></td>
                                </tr>
                                <tr>
                                    <td>Approved Amount:</td>
                                    <td class="text-success"><strong><?= format_amount($application->approved_amount, 0) ?></strong></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Loan Terms</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td>Interest Rate:</td>
                                    <td><?= $application->approved_interest_rate ?>% p.a.</td>
                                </tr>
                                <tr>
                                    <td>Tenure:</td>
                                    <td><?= $application->approved_tenure_months ?> months</td>
                                </tr>
                                <tr>
                                    <td>Interest Type:</td>
                                    <td><?= ucfirst($product->interest_type ?? 'Reducing') ?> Balance</td>
                                </tr>
                                <tr>
                                    <td>Monthly EMI:</td>
                                    <td class="text-primary"><strong><?= format_amount($emi_calc['emi'], 0) ?></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Member Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Member Details</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td>Member Code:</td>
                                    <td><strong><?= $member->member_code ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Name:</td>
                                    <td><?= $member->first_name ?> <?= $member->last_name ?></td>
                                </tr>
                                <tr>
                                    <td>Phone:</td>
                                    <td><?= $member->phone ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">EMI Schedule Summary</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td>Total Principal:</td>
                                    <td><?= format_amount($application->approved_amount) ?></td>
                                </tr>
                                <tr>
                                    <td>Total Interest:</td>
                                    <td><?= format_amount($emi_calc['total_interest'], 0) ?></td>
                                </tr>
                                <tr>
                                    <td>Total Payable:</td>
                                    <td class="text-danger"><strong><?= format_amount($emi_calc['total_payable'], 0) ?></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Processing Fee -->
            <?php 
            $processing_fee = 0;
            if (isset($product->processing_fee_value) && $product->processing_fee_value > 0) {
                if ($product->processing_fee_type == 'percentage') {
                    $processing_fee = ($application->approved_amount * $product->processing_fee_value) / 100;
                } else {
                    $processing_fee = $product->processing_fee_value;
                }
            }
            $net_disbursement = $application->approved_amount - $processing_fee;
            ?>
            
            <?php if ($processing_fee > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Processing Fee & Net Disbursement</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <td>Approved Amount</td>
                            <td class="text-right"><?= format_amount($application->approved_amount, 0) ?></td>
                        </tr>
                        <tr>
                            <td>Processing Fee (<?= $product->processing_fee_value ?><?= $product->processing_fee_type == 'percentage' ? '%' : '' ?>)</td>
                            <td class="text-right text-danger">- <?= format_amount($processing_fee) ?></td>
                        </tr>
                        <tr class="table-success">
                            <th>Net Disbursement</th>
                            <th class="text-right"><?= format_amount($net_disbursement) ?></th>
                        </tr>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <!-- Disbursement Form -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-wallet mr-1"></i> Disbursement Details</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Disbursement Date <span class="text-danger">*</span></label>
                        <input type="date" name="disbursement_date" id="disbursement_date" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>First EMI Date <span class="text-danger">*</span></label>
                        <input type="date" name="first_emi_date" id="first_emi_date" class="form-control" 
                               value="<?= date('Y-m-d', safe_timestamp('+1 month')) ?>" required>
                        <small class="text-muted">Usually 1 month from disbursement</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Disbursement Mode <span class="text-danger">*</span></label>
                        <select name="disbursement_mode" id="disbursement_mode" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer" selected>Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="upi">UPI</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Reference Number</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="Transaction/Cheque No.">
                    </div>
                    
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block btn-lg">
                        <i class="fas fa-paper-plane mr-1"></i> Disburse Loan
                    </button>
                    <a href="<?= site_url('admin/loans/disbursement') ?>" class="btn btn-default btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Cancel
                    </a>
                </div>
            </div>
            
            <!-- Quick Info -->
            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle text-info"></i> Quick Info</h6>
                    <ul class="list-unstyled mb-0">
                        <li><small>• Net Disbursement: <strong><?= format_amount($net_disbursement, 0) ?></strong></small></li>
                        <li><small>• Monthly EMI: <strong><?= format_amount($emi_calc['emi'], 0) ?></strong></small></li>
                        <li><small>• Total EMIs: <strong><?= $application->approved_tenure_months ?></strong></small></li>
                        <li><small>• Total Interest: <strong><?= format_amount($emi_calc['total_interest'], 0) ?></strong></small></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    // Auto-calculate first EMI date when disbursement date changes
    $('#disbursement_date').change(function() {
        var disbDate = new Date($(this).val());
        disbDate.setMonth(disbDate.getMonth() + 1);
        $('#first_emi_date').val(disbDate.toISOString().split('T')[0]);
    });
    
    // Confirmation before disbursement
    $('#disburseForm').submit(function(e) {
        if (!confirm('Are you sure you want to disburse this loan? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>
