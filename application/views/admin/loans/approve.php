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
                                <?php if ($product): ?>
                                <tr>
                                    <td class="text-muted">Current Scheme:</td>
                                    <td><?= $product->product_name ?> (<?= $product->interest_rate ?>%)</td>
                                </tr>
                                <?php else: ?>
                                <tr>
                                    <td class="text-muted">Scheme:</td>
                                    <td><span class="badge badge-secondary">Not selected yet</span></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (!empty($application->purpose)): ?>
                    <div class="card card-info mt-3 mb-0">
                        <div class="card-body py-2">
                            <i class="fas fa-comment mr-1"></i> <strong>Applicant's Purpose:</strong> <?= $application->purpose ?>
                        </div>
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
                    <!-- Loan Scheme Selection -->
                    <div class="form-group">
                        <label>Loan Scheme / Product <span class="text-danger">*</span></label>
                        <select name="loan_product_id" id="loan_product_id" class="form-control" required>
                            <option value="">-- Select Loan Scheme --</option>
                            <?php foreach ($loan_products as $lp): ?>
                            <option value="<?= $lp->id ?>"
                                data-rate="<?= $lp->interest_rate ?>"
                                data-type="<?= $lp->interest_type ?? 'reducing' ?>"
                                data-min-amount="<?= $lp->min_amount ?>"
                                data-max-amount="<?= $lp->max_amount ?>"
                                data-min-tenure="<?= $lp->min_tenure_months ?>"
                                data-max-tenure="<?= $lp->max_tenure_months ?>"
                                <?= ($product && $product->id == $lp->id) ? 'selected' : '' ?>>
                                <?= $lp->product_name ?> (<?= $lp->interest_rate ?>% <?= ucfirst($lp->interest_type ?? 'reducing') ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$product): ?>
                        <small class="text-danger"><i class="fas fa-exclamation-circle"></i> Member applied without scheme. Please select one.</small>
                        <?php endif; ?>
                    </div>

                    <!-- Product Info (updates dynamically) -->
                    <div id="productInfo" class="card mb-3" style="display:<?= $product ? 'block' : 'none' ?>;">
                        <div class="card-body py-2">
                            <small>
                                <strong>Rate:</strong> <span id="piRate"><?= $product->interest_rate ?? '-' ?></span>% |
                                <strong>Type:</strong> <span id="piType"><?= ucfirst($product->interest_type ?? '-') ?></span> |
                                <strong>Amount:</strong> ₹<span id="piMinAmt"><?= $product ? number_format($product->min_amount) : '-' ?></span> – ₹<span id="piMaxAmt"><?= $product ? number_format($product->max_amount) : '-' ?></span> |
                                <strong>Tenure:</strong> <span id="piMinTen"><?= $product->min_tenure_months ?? '-' ?></span> – <span id="piMaxTen"><?= $product->max_tenure_months ?? '-' ?></span> months
                            </small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Approved Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                            <input type="number" name="approved_amount" id="approved_amount" class="form-control" 
                                   value="<?= $application->requested_amount ?>" required
                                   min="<?= $product->min_amount ?? 0 ?>" max="<?= $product->max_amount ?? 99999999 ?>">
                        </div>
                        <small class="text-muted" id="amountHelp"><?= $product ? 'Range: ₹'.number_format($product->min_amount).' - ₹'.number_format($product->max_amount) : 'Select scheme to see range' ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label>Tenure (Months) <span class="text-danger">*</span></label>
                        <input type="number" name="approved_tenure_months" id="approved_tenure_months" class="form-control" 
                               value="<?= $application->requested_tenure_months ?>" required
                               min="<?= $product->min_tenure_months ?? 1 ?>" max="<?= $product->max_tenure_months ?? 360 ?>">
                        <small class="text-muted" id="tenureHelp"><?= $product ? 'Range: '.$product->min_tenure_months.' - '.$product->max_tenure_months.' months' : 'Select scheme to see range' ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label>Interest Rate (% p.a.) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="approved_interest_rate" id="approved_interest_rate" class="form-control" 
                                   value="<?= $product->interest_rate ?? '' ?>" step="0.01" min="0" max="100" required>
                            <div class="input-group-append"><span class="input-group-text">%</span></div>
                        </div>
                        <small class="text-muted" id="rateHelp"><?= $product ? 'Default product rate: '.$product->interest_rate.'%' : 'Will auto-fill when scheme is selected' ?></small>
                    </div>

                    <?php
                    // ---- Savings Constraint Panel ----------------------------------------
                    $savings_ok   = true;
                    $savings_msgs = [];

                    if (!empty($min_savings_required) && $savings_balance < $min_savings_required) {
                        $savings_ok     = false;
                        $savings_msgs[] = 'Minimum savings required: <strong>₹' . number_format($min_savings_required) . '</strong> &nbsp;|&nbsp; Member has: <strong>₹' . number_format($savings_balance) . '</strong>';
                    }
                    if ($max_loan_by_savings !== null && $application->requested_amount > $max_loan_by_savings) {
                        $savings_ok     = false;
                        $savings_msgs[] = 'Max loan allowed by savings ratio (' . $savings_ratio . 'x): <strong>₹' . number_format($max_loan_by_savings) . '</strong> &nbsp;|&nbsp; Requested: <strong>₹' . number_format($application->requested_amount) . '</strong>';
                    }
                    ?>

                    <?php if (!$savings_ok): ?>
                    <div class="card card-warning mb-3" id="savingsWarningCard">
                        <div class="card-header bg-warning">
                            <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Savings Constraint Warning</h3>
                        </div>
                        <div class="card-body pb-1">
                            <?php foreach ($savings_msgs as $msg): ?>
                            <p class="mb-1"><i class="fas fa-times-circle text-danger mr-1"></i> <?= $msg ?></p>
                            <?php endforeach; ?>
                            <hr class="my-2">
                            <p class="mb-1 font-weight-bold">What would you like to do?</p>

                            <?php if ($max_loan_by_savings !== null && $max_loan_by_savings > 0): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary mb-2" id="btnUseMaxAllowed">
                                <i class="fas fa-arrow-down mr-1"></i> Set Approved Amount to Max Allowed (₹<?= number_format($max_loan_by_savings) ?>)
                            </button><br>
                            <?php endif; ?>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="force_savings" id="force_savings" value="1">
                                <label class="form-check-label text-danger" for="force_savings">
                                    <strong>Force Approve — Override savings check</strong><br>
                                    <small class="text-muted">Approve the amount as entered, ignoring savings balance / ratio limits. This will be logged.</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($max_loan_by_savings !== null): ?>
                    <div class="card card-success mb-3">
                        <div class="card-body py-2">
                            <i class="fas fa-check-circle mr-1"></i>
                            Savings ratio OK — Max allowed: <strong>₹<?= number_format($max_loan_by_savings) ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- ----------------------------------------------------------------- -->

                    <?php if (!empty($guarantor_counts) && $guarantor_counts['total'] > 0): ?>
                    <div class="card card-info mb-3">
                        <div class="card-body py-2">
                            <strong>Guarantors:</strong>
                            <?= $guarantor_counts['accepted'] ?> accepted, <?= $guarantor_counts['pending'] ?> pending, <?= $guarantor_counts['rejected'] ?> rejected.
                            <br>
                            <small class="text-muted">Minimum required: <?= $min_guarantors_required ?></small>
                        </div>
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
    var currentInterestType = '<?= $product->interest_type ?? 'reducing' ?>';

    // When scheme changes, update rate, limits and info panel
    $('#loan_product_id').change(function() {
        var opt = $(this).find(':selected');
        if (!opt.val()) {
            $('#productInfo').hide();
            return;
        }
        var rate = opt.data('rate');
        var type = opt.data('type') || 'reducing';
        var minAmt = parseInt(opt.data('min-amount')) || 0;
        var maxAmt = parseInt(opt.data('max-amount')) || 99999999;
        var minTen = parseInt(opt.data('min-tenure')) || 1;
        var maxTen = parseInt(opt.data('max-tenure')) || 360;

        currentInterestType = type;
        $('#approved_interest_rate').val(rate);
        $('#approved_amount').attr({min: minAmt, max: maxAmt});
        $('#approved_tenure_months').attr({min: minTen, max: maxTen});

        $('#piRate').text(rate);
        $('#piType').text(type.charAt(0).toUpperCase() + type.slice(1));
        $('#piMinAmt').text(minAmt.toLocaleString('en-IN'));
        $('#piMaxAmt').text(maxAmt.toLocaleString('en-IN'));
        $('#piMinTen').text(minTen);
        $('#piMaxTen').text(maxTen);
        $('#productInfo').show();

        $('#amountHelp').text('Range: ₹' + minAmt.toLocaleString('en-IN') + ' - ₹' + maxAmt.toLocaleString('en-IN'));
        $('#tenureHelp').text('Range: ' + minTen + ' - ' + maxTen + ' months');
        $('#rateHelp').text('Default product rate: ' + rate + '%');

        calculateEMI();
    });

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
                type: currentInterestType
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

    // Set approved amount to max allowed by savings ratio
    $('#btnUseMaxAllowed').click(function() {
        var maxAllowed = <?= $max_loan_by_savings !== null ? (float)$max_loan_by_savings : 0 ?>;
        $('#approved_amount').val(Math.floor(maxAllowed)).trigger('input');
        $('#savingsWarningCard').removeClass('card-warning').addClass('card-success');
        $('#savingsWarningCard .card-header').removeClass('bg-warning').addClass('bg-success');
        toastr.info('Approved amount set to ₹' + Math.floor(maxAllowed).toLocaleString('en-IN'));
    });

    // Confirm Force Approve (guarantors + savings)
    $('#approveForm').submit(function(e) {
        // Ensure scheme is selected
        if (!$('#loan_product_id').val()) {
            e.preventDefault();
            toastr.error('Please select a Loan Scheme first.');
            $('#loan_product_id').focus();
            return false;
        }

        var msgs = [];
        if ($('#force_approve').is(':checked')) {
            msgs.push('Force Approve will mark all pending guarantors as accepted by admin.');
        }
        if ($('#force_savings').is(':checked')) {
            msgs.push('Force Savings Override will bypass the savings balance / ratio check. This action will be logged.');
        }
        if (msgs.length > 0) {
            if (!confirm(msgs.join('\n\n') + '\n\nProceed?')) {
                e.preventDefault();
            }
        }
    });
});
</script>
