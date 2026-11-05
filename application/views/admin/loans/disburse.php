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
            $is_adv = !empty($product->is_advance_loan);
            ?>

            <?php if ($is_adv): ?>
            <!-- Advance loan: release plan -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Advance loan — release plan</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info py-2">
                        EMI of <strong><?= format_amount($emi_calc['emi'], 0) ?></strong> is charged from the first EMI date on the full
                        <strong><?= format_amount($application->approved_amount, 0) ?></strong>. <strong>No money is paid now</strong> —
                        each part is released from the loan page on or after its planned date, for exactly the planned amount.
                        <?php if ($processing_fee > 0): ?>The processing fee (<?= format_amount($processing_fee) ?>) is withheld from part 1.<?php endif; ?>
                    </div>
                    <div class="form-inline mb-2 small">
                        Split into <input type="number" id="splitCount" class="form-control form-control-sm mx-1" style="width:70px" min="1" max="24" value="1"> equal part(s),
                        first on <input type="date" id="splitStart" class="form-control form-control-sm mx-1" value="<?= date('Y-m-d', strtotime('+12 months')) ?>">
                        then every <input type="number" id="splitEvery" class="form-control form-control-sm mx-1" style="width:70px" min="1" max="60" value="12"> months
                        <button type="button" id="splitApply" class="btn btn-sm btn-outline-info ml-2">Fill plan</button>
                    </div>
                    <table class="table table-sm table-bordered mb-1" id="planTable">
                        <thead class="thead-light"><tr><th style="width:50px">Part</th><th>Planned date</th><th class="text-right">Amount</th><th style="width:40px"></th></tr></thead>
                        <tbody>
                            <tr>
                                <td class="plan-no">1</td>
                                <td><input type="date" name="plan_date[]" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+12 months')) ?>" required></td>
                                <td><input type="number" step="0.01" min="1" name="plan_amount[]" class="form-control form-control-sm text-right plan-amt" value="<?= (float) $application->approved_amount ?>" required></td>
                                <td><button type="button" class="btn btn-sm btn-link text-danger plan-del" title="Remove"><i class="fas fa-times"></i></button></td>
                            </tr>
                        </tbody>
                        <tfoot><tr><th colspan="2">Total <small class="text-muted">(must be <?= format_amount($application->approved_amount) ?>)</small></th>
                            <th class="text-right" id="planTotal">—</th><th></th></tr></tfoot>
                    </table>
                    <button type="button" id="planAdd" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus mr-1"></i> Add part</button>
                </div>
            </div>
            <?php endif; ?>
            
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
                        <label><?= $is_adv ? 'Loan start (sanction) date' : 'Disbursement Date' ?> <span class="text-danger">*</span></label>
                        <input type="date" name="disbursement_date" id="disbursement_date" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>First EMI Date <span class="text-danger">*</span></label>
                        <input type="date" name="first_emi_date" id="first_emi_date" class="form-control" 
                               value="<?= date('Y-m-d', safe_timestamp('+1 month')) ?>" required>
                        <small class="text-muted">Usually 1 month from disbursement</small>
                    </div>
                    
                    <?php if (!empty($application->disburse_in_parts) && !$is_adv): ?>
                    <div class="alert alert-info py-2">
                        <i class="fas fa-layer-group mr-1"></i> <strong>Disburse in parts.</strong> EMI is on the full <?= format_amount($application->approved_amount, 0) ?>.
                        Enter only the amount given now — the full processing fee<?= $processing_fee > 0 ? ' (' . format_amount($processing_fee) . ')' : '' ?> is deducted from this part.
                    </div>
                    <div class="form-group">
                        <label>Amount disbursed now (part 1) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="<?= round($processing_fee + 1, 2) ?>" max="<?= $application->approved_amount ?>" name="first_amount" id="first_amount" class="form-control" required>
                        <small class="text-muted">Member receives <b id="first_net">—</b> · left to disburse later <b id="first_left">—</b></small>
                    </div>
                    <?php endif; ?>

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
                        <i class="fas fa-paper-plane mr-1"></i> <?= $is_adv ? 'Create advance loan' : 'Disburse Loan' ?>
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
    var fixedDueDay = <?= (int)($fixed_due_day ?? 0) ?>; // globally configured due day (0 = disabled)

    /**
     * Compute the next occurrence of fixedDueDay on or after disbDate + 1 month.
     * If fixedDueDay is 0, simply return disbDate + 1 month (original behaviour).
     */
    function calcFirstEmiDate(disbDateStr) {
        var d = new Date(disbDateStr);
        if (isNaN(d.getTime())) return '';

        // Move forward one month from disbursement date
        d.setMonth(d.getMonth() + 1);

        if (fixedDueDay >= 1 && fixedDueDay <= 28) {
            // Snap to the configured due day in that month (or the last day of the month)
            var year  = d.getFullYear();
            var month = d.getMonth(); // 0-indexed
            var lastDay = new Date(year, month + 1, 0).getDate();
            var day = Math.min(fixedDueDay, lastDay);
            var candidate = new Date(year, month, day);

            // If candidate is on or before the disbursement date, push to next month
            var disbOrig = new Date(disbDateStr);
            if (candidate <= disbOrig) {
                month += 1;
                if (month > 11) { month = 0; year += 1; }
                lastDay = new Date(year, month + 1, 0).getDate();
                day = Math.min(fixedDueDay, lastDay);
                candidate = new Date(year, month, day);
            }
            return candidate.toISOString().split('T')[0];
        }

        return d.toISOString().split('T')[0];
    }

    // Auto-calculate first EMI date on disbursement date change
    $('#disbursement_date').change(function() {
        var result = calcFirstEmiDate($(this).val());
        if (result) $('#first_emi_date').val(result);
    });

    // Set initial value on page load
    (function() {
        var result = calcFirstEmiDate($('#disbursement_date').val());
        if (result) $('#first_emi_date').val(result);
    })();

    // Confirmation before disbursement
    $('#disburseForm').submit(function(e) {
        if (!confirm('Are you sure you want to disburse this loan? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
    // Advance loan: release plan rows
    var sanctioned = <?= (float) $application->approved_amount ?>;
    function planRenumber() {
        var t = 0;
        $('#planTable tbody tr').each(function (i) { $(this).find('.plan-no').text(i + 1); t += parseFloat($(this).find('.plan-amt').val()) || 0; });
        var ok = Math.abs(t - sanctioned) <= 0.01;
        $('#planTotal').text('₹' + t.toLocaleString('en-IN', {maximumFractionDigits: 2})).toggleClass('text-danger', !ok).toggleClass('text-success', ok);
        return ok;
    }
    function planRow(date, amt) {
        var r = $('#planTable tbody tr:first').clone();
        r.find('input[type=date]').val(date || '');
        r.find('.plan-amt').val(amt || '');
        $('#planTable tbody').append(r);
    }
    $('#planAdd').on('click', function () { planRow('', ''); planRenumber(); });
    $('#planTable').on('click', '.plan-del', function () {
        if ($('#planTable tbody tr').length > 1) $(this).closest('tr').remove();
        planRenumber();
    });
    $('#planTable').on('input change', '.plan-amt', planRenumber);
    $('#splitApply').on('click', function () {
        var n = Math.max(1, Math.min(24, parseInt($('#splitCount').val(), 10) || 1)),
            every = Math.max(1, parseInt($('#splitEvery').val(), 10) || 12),
            start = $('#splitStart').val();
        if (!start) return;
        var each = Math.floor(sanctioned / n * 100) / 100, first = Math.round((sanctioned - each * (n - 1)) * 100) / 100;
        $('#planTable tbody tr:gt(0)').remove();
        var p = start.split('-');
        for (var i = 0; i < n; i++) {
            var d = new Date(Date.UTC(+p[0], +p[1] - 1 + i * every, 1));
            var last = new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth() + 1, 0)).getUTCDate();
            d.setUTCDate(Math.min(+p[2], last));
            var ds = d.toISOString().split('T')[0];
            if (i === 0) { $('#planTable tbody tr:first input[type=date]').val(ds); $('#planTable tbody tr:first .plan-amt').val(first); }
            else planRow(ds, each);
        }
        planRenumber();
    });
    if ($('#planTable').length) {
        planRenumber();
        $('#disburseForm').on('submit', function (e) {
            if (!planRenumber()) { e.preventDefault(); e.stopImmediatePropagation(); alert('The release plan total must equal the sanctioned amount.'); }
        });
    }

    // Disburse in parts: preview of the first part
    $('#first_amount').on('input', function () {
        var a = parseFloat(this.value) || 0, fee = <?= (float) $processing_fee ?>, total = <?= (float) $application->approved_amount ?>;
        $('#first_net').text(a > 0 ? '₹' + (a - fee).toLocaleString('en-IN', {maximumFractionDigits: 2}) : '—');
        $('#first_left').text(a > 0 ? '₹' + Math.max(0, total - a).toLocaleString('en-IN', {maximumFractionDigits: 2}) : '—');
    });
});
</script>
