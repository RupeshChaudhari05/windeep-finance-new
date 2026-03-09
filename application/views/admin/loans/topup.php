<!-- Top-up Eligibility & Application -->
<div class="row">
    <!-- Left: Current Loan Summary -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-contract mr-1"></i> Current Loan</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="mb-1"><?= $loan->loan_number ?></h4>
                    <span class="badge badge-<?= $loan->status === 'active' ? 'success' : 'secondary' ?> badge-lg">
                        <?= strtoupper($loan->status) ?>
                    </span>
                </div>

                <table class="table table-sm table-borderless mb-0">
                    <tr><th>Member:</th>
                        <td>
                            <a href="<?= site_url('admin/members/view/' . $member->id) ?>">
                                <?= $member->first_name ?> <?= $member->last_name ?>
                            </a>
                            <br><small class="text-muted"><?= $member->member_code ?></small>
                        </td>
                    </tr>
                    <tr><th>Product:</th><td><span class="badge badge-info"><?= $product->product_name ?></span></td></tr>
                    <tr><th>Principal:</th><td class="font-weight-bold"><?= format_amount($loan->principal_amount) ?></td></tr>
                    <tr><th>Rate:</th><td><?= $loan->interest_rate ?>% p.a. (<?= ucfirst($loan->interest_type) ?>)</td></tr>
                    <tr><th>Tenure:</th><td><?= $loan->tenure_months ?> months</td></tr>
                    <tr><th>EMI:</th><td><?= format_amount($loan->emi_amount) ?></td></tr>
                    <tr><th>Disbursed:</th><td><?= format_date($loan->disbursement_date) ?></td></tr>
                </table>

                <hr>

                <!-- Outstanding Summary -->
                <div class="info-box bg-danger mb-2">
                    <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Outstanding Principal</span>
                        <span class="info-box-number"><?= format_amount($eligibility->stats->outstanding_principal) ?></span>
                    </div>
                </div>
                <div class="info-box bg-warning mb-2">
                    <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Outstanding Interest</span>
                        <span class="info-box-number"><?= format_amount($eligibility->stats->outstanding_interest) ?></span>
                    </div>
                </div>
                <div class="info-box bg-success mb-2">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Repaid</span>
                        <span class="info-box-number">
                            <?= $eligibility->stats->emis_paid ?> / <?= $eligibility->stats->total_installments ?> EMIs
                            (<?= $eligibility->stats->repayment_percentage ?>%)
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Eligibility & Form -->
    <div class="col-md-8">
        <!-- Eligibility Status -->
        <div class="card card-<?= $eligibility->eligible ? 'success' : 'danger' ?> card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-<?= $eligibility->eligible ? 'check-circle' : 'times-circle' ?> mr-1"></i>
                    Top-up Eligibility
                </h3>
            </div>
            <div class="card-body">
                <?php if ($eligibility->eligible): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong>Eligible!</strong> This loan qualifies for a top-up.
                    </div>

                    <div class="row text-center mb-3">
                        <div class="col-md-3">
                            <div class="small text-muted">EMIs Paid</div>
                            <h4 class="text-success"><?= $eligibility->stats->emis_paid ?> / <?= $eligibility->stats->min_emis_required ?> min</h4>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Overdue EMIs</div>
                            <h4 class="<?= $eligibility->stats->overdue_count > 0 ? 'text-danger' : 'text-success' ?>"><?= $eligibility->stats->overdue_count ?></h4>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Max Top-up</div>
                            <h4 class="text-primary"><?= format_amount($eligibility->stats->max_topup_amount, 0) ?></h4>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Repayment</div>
                            <h4 class="text-info"><?= $eligibility->stats->repayment_percentage ?>%</h4>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle mr-2"></i>
                        <strong>Not Eligible.</strong> This loan does not qualify for top-up.
                    </div>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($eligibility->reasons as $reason): ?>
                            <li class="mb-1"><i class="fas fa-times text-danger mr-1"></i> <?= $reason ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($eligibility->eligible): ?>
        <!-- Top-up Application Form -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i> Top-up Application</h3>
            </div>
            <form action="<?= site_url('admin/loans/submit_topup') ?>" method="post" id="topupForm">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <input type="hidden" name="parent_loan_id" value="<?= $loan->id ?>">

                <div class="card-body">
                    <div class="callout callout-info">
                        <h5><i class="fas fa-info-circle mr-1"></i> How Top-up Works</h5>
                        <ol class="mb-0 pl-3">
                            <li>Current loan outstanding (<strong><?= format_amount($eligibility->stats->outstanding_principal) ?></strong>) will be internally settled</li>
                            <li>A <strong>new loan</strong> will be created: Outstanding + Top-up Amount</li>
                            <li>Only the <strong>additional top-up amount</strong> (minus fee) is disbursed to the member</li>
                            <li>Fresh EMI schedule is generated on the new combined principal</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="topup_amount">Additional Top-up Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-lg" id="topup_amount" name="topup_amount"
                                       min="1000" max="<?= $eligibility->stats->max_topup_amount ?>" step="100" required
                                       placeholder="e.g. 50000">
                                <small class="form-text text-muted">
                                    Min: <?= format_amount(1000) ?> | Max: <?= format_amount($eligibility->stats->max_topup_amount) ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tenure">New Tenure (months) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-lg" id="tenure" name="tenure"
                                       min="<?= $product->min_tenure_months ?>" max="<?= $product->max_tenure_months ?>"
                                       value="<?= $loan->tenure_months ?>" required>
                                <small class="form-text text-muted">
                                    Range: <?= $product->min_tenure_months ?> – <?= $product->max_tenure_months ?> months
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="interest_rate">Interest Rate (% p.a.) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="interest_rate" name="interest_rate"
                                       value="<?= $loan->interest_rate ?>" min="1" max="50" step="0.01" required>
                                <small class="form-text text-muted">Interest type: <?= ucfirst($product->interest_type) ?></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="purpose">Purpose <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="purpose" name="purpose"
                                       placeholder="e.g. Business expansion, Home repair" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="purpose_details">Additional Details</label>
                        <textarea class="form-control" id="purpose_details" name="purpose_details" rows="2"
                                  placeholder="Optional details about the top-up purpose..."></textarea>
                    </div>

                    <!-- Preview Section (AJAX populated) -->
                    <div id="topupPreview" class="d-none">
                        <hr>
                        <h5 class="text-primary"><i class="fas fa-calculator mr-1"></i> Top-up Preview</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr><th colspan="2">Foreclosure Settlement (Current Loan)</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>Outstanding Principal</td><td class="text-right font-weight-bold" id="pvOutPrincipal">-</td></tr>
                                        <tr><td>Outstanding Interest</td><td class="text-right" id="pvOutInterest">-</td></tr>
                                        <tr><td>Outstanding Fine</td><td class="text-right" id="pvOutFine">-</td></tr>
                                        <tr class="table-secondary"><td><strong>Settlement Amount</strong></td><td class="text-right font-weight-bold" id="pvSettlement">-</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr><th colspan="2">New Top-up Loan</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>New Principal</td><td class="text-right font-weight-bold" id="pvNewPrincipal">-</td></tr>
                                        <tr><td>Total Interest</td><td class="text-right" id="pvNewInterest">-</td></tr>
                                        <tr><td>New EMI</td><td class="text-right font-weight-bold text-primary" id="pvNewEMI">-</td></tr>
                                        <tr><td>Total Payable</td><td class="text-right" id="pvNewTotal">-</td></tr>
                                        <tr><td>Processing Fee</td><td class="text-right text-danger" id="pvFee">-</td></tr>
                                        <tr class="table-success"><td><strong>Net Disbursement</strong></td><td class="text-right font-weight-bold text-success" id="pvNetDisb">-</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="button" class="btn btn-info" id="btnCalculate">
                        <i class="fas fa-calculator mr-1"></i> Calculate Preview
                    </button>
                    <button type="submit" class="btn btn-success float-right" id="btnSubmit" disabled>
                        <i class="fas fa-paper-plane mr-1"></i> Submit Top-up Application
                    </button>
                    <a href="<?= site_url('admin/loans/view/' . $loan->id) ?>" class="btn btn-default float-right mr-2">
                        <i class="fas fa-arrow-left mr-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(function() {
    var loanId = <?= (int) $loan->id ?>;
    var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
    var csrfHash = '<?= $this->security->get_csrf_hash() ?>';

    function formatAmt(n) {
        return '₹' + parseFloat(n).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    $('#btnCalculate').on('click', function() {
        var btn = $(this);
        var amt = parseFloat($('#topup_amount').val()) || 0;
        var tenure = parseInt($('#tenure').val()) || 0;
        var rate = parseFloat($('#interest_rate').val()) || 0;

        if (amt <= 0 || tenure <= 0 || rate <= 0) {
            toastr.error('Please fill in all required fields.');
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Calculating...');

        $.post('<?= site_url("admin/loans/topup_calculate") ?>', {
            loan_id: loanId,
            topup_amount: amt,
            tenure: tenure,
            interest_rate: rate,
            [csrfName]: csrfHash
        }, function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-calculator mr-1"></i> Calculate Preview');

            if (res.error) {
                toastr.error(res.message || res.reasons.join(' '));
                return;
            }

            $('#pvOutPrincipal').text(formatAmt(res.outstanding_principal));
            $('#pvOutInterest').text(formatAmt(res.outstanding_interest));
            $('#pvOutFine').text(formatAmt(res.outstanding_fine));
            $('#pvSettlement').text(formatAmt(res.settlement_amount));
            $('#pvNewPrincipal').text(formatAmt(res.new_principal));
            $('#pvNewInterest').text(formatAmt(res.new_total_interest));
            $('#pvNewEMI').text(formatAmt(res.new_emi));
            $('#pvNewTotal').text(formatAmt(res.new_total_payable));
            $('#pvFee').text(formatAmt(res.topup_fee));
            $('#pvNetDisb').text(formatAmt(res.net_disbursement));

            $('#topupPreview').removeClass('d-none');
            $('#btnSubmit').prop('disabled', false);

            // Update CSRF hash
            if (res.csrf_hash) csrfHash = res.csrf_hash;
        }, 'json').fail(function() {
            btn.prop('disabled', false).html('<i class="fas fa-calculator mr-1"></i> Calculate Preview');
            toastr.error('Server error. Please try again.');
        });
    });

    // Re-disable submit if inputs change after preview
    $('#topup_amount, #tenure, #interest_rate').on('change', function() {
        $('#btnSubmit').prop('disabled', true);
        $('#topupPreview').addClass('d-none');
    });
});
</script>
