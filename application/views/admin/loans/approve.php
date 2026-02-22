<!-- Approve Loan Application -->
<form method="post" action="<?= site_url('admin/loans/approve/' . $application->id) ?>" id="approveForm">
    <div class="row">
        <div class="col-md-8">
            <!-- Application Details -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> Application Details</h3>
                    <div class="card-tools">
                        <span class="badge badge-warning badge-lg">PENDING APPROVAL</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="text-muted">Application Number:</td>
                                    <td><strong><?= $application->application_number ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Application Date:</td>
                                    <td><?= format_date($application->application_date) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Loan Product:</td>
                                    <td><?= $product->product_name ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Requested Amount:</td>
                                    <td><span class="text-primary font-weight-bold">₹<?= number_format($application->requested_amount) ?></span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Requested Tenure:</td>
                                    <td><?= $application->requested_tenure_months ?> months</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="text-muted">Purpose:</td>
                                    <td><?= $application->purpose ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Product Rate:</td>
                                    <td><?= $product->interest_rate ?>% p.a.</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Rate Type:</td>
                                    <td><?= ucfirst($product->interest_type ?? 'Reducing') ?> Balance</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Min Amount:</td>
                                    <td>₹<?= number_format($product->min_amount) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Max Amount:</td>
                                    <td>₹<?= number_format($product->max_amount) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (!empty($application->purpose)): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-comment mr-1"></i> <strong>Applicant's Purpose:</strong> <?= $application->purpose ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Member Details -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user mr-1"></i> Member Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="text-muted">Member Code:</td>
                                    <td><strong><?= $member->member_code ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Name:</td>
                                    <td><?= $member->first_name ?> <?= $member->last_name ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Phone:</td>
                                    <td><a href="tel:<?= $member->phone ?>"><?= $member->phone ?></a></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Email:</td>
                                    <td><?= $member->email ?: '-' ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="text-muted">Savings Balance:</td>
                                    <td class="text-success">₹<?= number_format($member->savings_summary->current_balance ?? 0) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Active Loans:</td>
                                    <td><?= $member->loan_summary->total_loans ?? 0 ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Member Since:</td>
                                    <td><?= format_date($member->created_at) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Status:</td>
                                    <td>
                                        <span class="badge badge-<?= $member->status == 'active' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($member->status) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php if (!empty($member->member_level)): ?>
                                <tr>
                                    <td class="text-muted">Member Level:</td>
                                    <td>
                                        <?php
                                        $level_labels = ['founding_member' => 'Founding Member', 'level2' => 'Level 2 Member', 'level3' => 'Level 3 Member'];
                                        $level_badges = ['founding_member' => 'danger', 'level2' => 'warning', 'level3' => 'info'];
                                        ?>
                                        <span class="badge badge-<?= $level_badges[$member->member_level] ?? 'secondary' ?>">
                                            <?= $level_labels[$member->member_level] ?? $member->member_level ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- EMI Calculator Preview -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calculator mr-1"></i> EMI Preview</h3>
                </div>
                <div class="card-body" id="emiPreview">
                    <div class="text-center py-4">
                        <i class="fas fa-calculator fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Enter approval details to see EMI calculation</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Approval Form -->
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-check mr-1"></i> Approval Details</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Approved Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                            <input type="number" name="approved_amount" id="approved_amount" class="form-control" 
                                   value="<?= $application->requested_amount ?>" required
                                   min="<?= $product->min_amount ?>" max="<?= $product->max_amount ?>">
                        </div>
                        <small class="text-muted">Range: ₹<?= number_format($product->min_amount) ?> - ₹<?= number_format($product->max_amount) ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label>Tenure (Months) <span class="text-danger">*</span></label>
                        <input type="number" name="approved_tenure_months" id="approved_tenure_months" class="form-control" 
                               value="<?= $application->requested_tenure_months ?>" required
                               min="<?= $product->min_tenure_months ?>" max="<?= $product->max_tenure_months ?>">
                        <small class="text-muted">Range: <?= $product->min_tenure_months ?> - <?= $product->max_tenure_months ?> months</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Interest Rate (% p.a.) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="approved_interest_rate" id="approved_interest_rate" class="form-control" 
                                   value="<?= $product->interest_rate ?>" step="0.01" min="0" max="100" required>
                            <div class="input-group-append"><span class="input-group-text">%</span></div>
                        </div>
                        <small class="text-muted">Default product rate: <?= $product->interest_rate ?>%</small>
                    </div>

                    <?php if (!empty($guarantor_counts) && $guarantor_counts['total'] > 0): ?>
                    <div class="alert alert-info">
                        <strong>Guarantors:</strong>
                        <?= $guarantor_counts['accepted'] ?> accepted, <?= $guarantor_counts['pending'] ?> pending, <?= $guarantor_counts['rejected'] ?> rejected.
                        <br>
                        <small class="text-muted">Minimum required: <?= $min_guarantors_required ?></small>
                    </div>
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="force_approve" id="force_approve" value="1">
                            <label class="form-check-label" for="force_approve">Force Approve (mark pending guarantors as accepted)</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Approval Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block btn-lg">
                        <i class="fas fa-check mr-1"></i> Approve Application
                    </button>
                </div>
            </div>
            
            <!-- Reject Option -->
            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-times mr-1"></i> Reject Application</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label>Rejection Reason</label>
                        <textarea id="reject_reason" class="form-control" rows="2" placeholder="Enter reason for rejection"></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-danger btn-block" id="btnReject">
                        <i class="fas fa-times mr-1"></i> Reject
                    </button>
                </div>
            </div>
            
            <a href="<?= site_url('admin/loans/pending-approval') ?>" class="btn btn-default btn-block">
                <i class="fas fa-arrow-left mr-1"></i> Back to Applications
            </a>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    // Calculate EMI on input change
    function calculateEMI() {
        var principal = $('#approved_amount').val();
        var rate = $('#approved_interest_rate').val();
        var tenure = $('#approved_tenure_months').val();
        
        if (principal && rate && tenure) {
            $.post('<?= site_url('admin/loans/calculate_emi') ?>', {
                principal: principal,
                rate: rate,
                tenure: tenure,
                type: '<?= $product->interest_type ?? 'reducing' ?>'
            }, function(data) {
                var html = '<div class="row">';
                html += '<div class="col-6"><div class="text-center"><h4 class="text-primary">₹' + data.emi.toLocaleString('en-IN', {maximumFractionDigits: 0}) + '</h4><small class="text-muted">Monthly EMI</small></div></div>';
                html += '<div class="col-6"><div class="text-center"><h4 class="text-success">₹' + data.total_interest.toLocaleString('en-IN', {maximumFractionDigits: 0}) + '</h4><small class="text-muted">Total Interest</small></div></div>';
                html += '</div>';
                html += '<hr>';
                html += '<div class="row">';
                html += '<div class="col-6"><small class="text-muted">Principal:</small> ₹' + data.total_principal.toLocaleString('en-IN') + '</div>';
                html += '<div class="col-6"><small class="text-muted">Total Payable:</small> ₹' + data.total_amount.toLocaleString('en-IN') + '</div>';
                html += '</div>';
                $('#emiPreview').html(html);
            }, 'json');
        }
    }
    
    $('#approved_amount, #approved_interest_rate, #approved_tenure_months').on('input', calculateEMI);
    calculateEMI(); // Initial calculation
    
    // Reject Application
    $('#btnReject').click(function() {
        var reason = $('#reject_reason').val().trim();
        
        if (!reason) {
            toastr.error('Please enter rejection reason');
            return;
        }
        
        if (confirm('Are you sure you want to reject this application?')) {
            $.post('<?= site_url('admin/loans/reject/' . $application->id) ?>', {reason: reason}, function(response) {
                if (response.success) {
                    toastr.success('Application rejected');
                    window.location.href = '<?= site_url('admin/loans/pending-approval') ?>';
                } else {
                    toastr.error(response.message || 'Failed to reject');
                }
            }, 'json');
        }
    });

    // Confirm Force Approve
    $('#approveForm').submit(function(e) {
        if ($('#force_approve').is(':checked')) {
            if (!confirm('Force Approve will mark all pending guarantors as accepted by admin. Proceed?')) {
                e.preventDefault();
            }
        }
    });
});
</script>
