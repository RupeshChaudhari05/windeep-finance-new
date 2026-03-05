<!-- Part Payment (Partial Prepayment) - Admin View -->
<div class="row">
    <!-- Left: Loan Summary -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-contract mr-1"></i> Loan Summary</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="mb-0"><?= $loan->loan_number ?></h4>
                    <span class="badge badge-success badge-lg mt-1"><?= strtoupper($loan->status) ?></span>
                </div>

                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th width="50%">Member:</th>
                        <td>
                            <a href="<?= site_url('admin/members/view/' . $loan->member_id) ?>">
                                <?= $member->first_name ?> <?= $member->last_name ?>
                            </a>
                            <br><small class="text-muted"><?= $member->member_code ?></small>
                        </td>
                    </tr>
                    <tr>
                        <th>Product:</th>
                        <td><span class="badge badge-info"><?= $product->product_name ?></span></td>
                    </tr>
                    <tr>
                        <th>Interest Rate:</th>
                        <td><strong><?= $loan->interest_rate ?>%</strong> p.a. (<?= ucfirst($loan->interest_type) ?>)</td>
                    </tr>
                </table>

                <hr class="my-2">

                <div class="info-box bg-danger mb-2">
                    <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Outstanding Principal</span>
                        <span class="info-box-number" id="display-outstanding"><?= format_amount($loan->outstanding_principal, 0) ?></span>
                    </div>
                </div>

                <div class="info-box bg-info mb-2">
                    <span class="info-box-icon"><i class="fas fa-calculator"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Current EMI</span>
                        <span class="info-box-number" id="display-emi"><?= format_amount($loan->emi_amount, 0) ?></span>
                    </div>
                </div>

                <div class="info-box bg-warning mb-2">
                    <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Remaining Tenure</span>
                        <span class="info-box-number" id="display-tenure"><?= $remaining_tenure ?> months</span>
                    </div>
                </div>

                <div class="info-box bg-primary mb-2">
                    <span class="info-box-icon"><i class="fas fa-money-bill"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Original Principal</span>
                        <span class="info-box-number"><?= format_amount($loan->principal_amount, 0) ?></span>
                    </div>
                </div>

                <?php if ($prepayment_penalty_percent > 0): ?>
                <div class="callout callout-warning py-2">
                    <small><i class="fas fa-info-circle mr-1"></i> Prepayment penalty: <strong><?= $prepayment_penalty_percent ?>%</strong> on part payment amount</small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Part Payment History -->
        <?php if (!empty($part_payment_history)): ?>
        <div class="card card-secondary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-1"></i> Part Payment History</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th class="text-right">Amount</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($part_payment_history as $pp): ?>
                            <tr>
                                <td><small><?= date('d M Y', strtotime($pp->payment_date)) ?></small></td>
                                <td class="text-right"><small><?= format_amount($pp->part_payment_amount, 0) ?></small></td>
                                <td>
                                    <?php
                                    $type_badges = ['reduce_emi' => 'primary', 'reduce_tenure' => 'success', 'manual' => 'warning'];
                                    ?>
                                    <span class="badge badge-<?= $type_badges[$pp->adjustment_type] ?? 'secondary' ?> badge-sm">
                                        <?= ucfirst(str_replace('_', ' ', $pp->adjustment_type)) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Part Payment Form -->
    <div class="col-md-8">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i> Part Payment (Partial Prepayment)</h3>
            </div>
            <div class="card-body">
                <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-exclamation-triangle mr-1"></i> <?= $this->session->flashdata('error') ?>
                </div>
                <?php endif; ?>

                <form id="partPaymentForm" method="post" action="<?= site_url('admin/loans/process_part_payment') ?>">
                    <input type="hidden" name="loan_id" value="<?= $loan->id ?>">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                    <!-- Step 1: Payment Amount -->
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header py-2">
                            <h5 class="card-title mb-0"><span class="badge badge-primary mr-2">1</span> Part Payment Amount</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="part_payment_amount">Part Payment Amount (<?= get_currency_symbol() ?>)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><?= get_currency_symbol() ?></span>
                                            </div>
                                            <input type="number" class="form-control form-control-lg" id="part_payment_amount" 
                                                   name="part_payment_amount" min="1" max="<?= $loan->outstanding_principal - 1 ?>" 
                                                   step="0.01" required placeholder="Enter amount"
                                                   data-outstanding="<?= $loan->outstanding_principal ?>">
                                        </div>
                                        <small class="text-muted">Max: <?= format_amount($loan->outstanding_principal - 1) ?></small>
                                        <div id="amount-error" class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="payment_date">Payment Date</label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                               value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="payment_mode">Payment Mode</label>
                                        <select class="form-control" id="payment_mode" name="payment_mode" required>
                                            <option value="cash">Cash</option>
                                            <option value="bank_transfer" selected>Bank Transfer</option>
                                            <option value="cheque">Cheque</option>
                                            <option value="upi">UPI</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="payment_reference">Reference Number</label>
                                        <input type="text" class="form-control" id="payment_reference" name="payment_reference" 
                                               placeholder="Transaction/Cheque reference">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label for="remarks">Remarks</label>
                                        <input type="text" class="form-control" id="remarks" name="remarks" placeholder="Optional remarks">
                                    </div>
                                </div>
                            </div>

                            <?php if ($prepayment_penalty_percent > 0): ?>
                            <div class="mt-2">
                                <div class="alert alert-warning mb-0 py-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Prepayment penalty of <strong><?= $prepayment_penalty_percent ?>%</strong> will be charged.
                                    <span id="penalty-display"></span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="mt-3 text-center">
                                <button type="button" id="btnCalculate" class="btn btn-primary btn-lg" disabled>
                                    <i class="fas fa-calculator mr-1"></i> Calculate Options
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Adjustment Options (shown after calculation) -->
                    <div id="optionsSection" class="card card-outline card-info mb-3" style="display: none;">
                        <div class="card-header py-2">
                            <h5 class="card-title mb-0"><span class="badge badge-info mr-2">2</span> Choose Adjustment Type</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Option A: Reduce EMI -->
                                <div class="col-md-4">
                                    <div class="card card-outline option-card" id="optionA-card">
                                        <div class="card-body text-center">
                                            <div class="custom-control custom-radio mb-2">
                                                <input type="radio" class="custom-control-input" id="option_reduce_emi" 
                                                       name="adjustment_type" value="reduce_emi" checked>
                                                <label class="custom-control-label font-weight-bold" for="option_reduce_emi">
                                                    <i class="fas fa-arrow-down text-primary mr-1"></i> Reduce EMI
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mb-2">Keep tenure same, lower EMI</small>
                                            <hr class="my-2">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td class="text-muted">New EMI:</td>
                                                    <td class="text-right font-weight-bold text-primary" id="optA-emi">-</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Tenure:</td>
                                                    <td class="text-right" id="optA-tenure">-</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Interest Saved:</td>
                                                    <td class="text-right text-success" id="optA-savings">-</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Option B: Reduce Tenure -->
                                <div class="col-md-4">
                                    <div class="card card-outline option-card" id="optionB-card">
                                        <div class="card-body text-center">
                                            <div class="custom-control custom-radio mb-2">
                                                <input type="radio" class="custom-control-input" id="option_reduce_tenure" 
                                                       name="adjustment_type" value="reduce_tenure">
                                                <label class="custom-control-label font-weight-bold" for="option_reduce_tenure">
                                                    <i class="fas fa-compress-arrows-alt text-success mr-1"></i> Reduce Tenure
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mb-2">Keep EMI same, shorter tenure</small>
                                            <hr class="my-2">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td class="text-muted">EMI:</td>
                                                    <td class="text-right" id="optB-emi">-</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">New Tenure:</td>
                                                    <td class="text-right font-weight-bold text-success" id="optB-tenure">-</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Interest Saved:</td>
                                                    <td class="text-right text-success" id="optB-savings">-</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Option C: Manual -->
                                <div class="col-md-4">
                                    <div class="card card-outline option-card" id="optionC-card">
                                        <div class="card-body text-center">
                                            <div class="custom-control custom-radio mb-2">
                                                <input type="radio" class="custom-control-input" id="option_manual" 
                                                       name="adjustment_type" value="manual">
                                                <label class="custom-control-label font-weight-bold" for="option_manual">
                                                    <i class="fas fa-sliders-h text-warning mr-1"></i> Manual Override
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mb-2">Custom EMI or tenure</small>
                                            <hr class="my-2">
                                            <div id="manualFields" style="display: none;">
                                                <div class="form-group mb-2">
                                                    <label class="small"><i class="fas fa-rupee-sign mr-1"></i>Custom EMI</label>
                                                    <input type="number" class="form-control form-control-sm" id="manual_emi" 
                                                           name="manual_emi" step="0.01" min="100" placeholder="Enter EMI">
                                                </div>
                                                <div class="text-muted small my-1">— OR —</div>
                                                <div class="form-group mb-2">
                                                    <label class="small"><i class="fas fa-calendar mr-1"></i>Custom Tenure (months)</label>
                                                    <input type="number" class="form-control form-control-sm" id="manual_tenure" 
                                                           name="manual_tenure" min="1" max="360" placeholder="Enter months">
                                                </div>
                                                <button type="button" id="btnManualCalc" class="btn btn-sm btn-outline-warning btn-block">
                                                    <i class="fas fa-sync-alt mr-1"></i> Recalculate
                                                </button>
                                                <div id="manual-result" class="mt-2" style="display: none;">
                                                    <table class="table table-sm table-borderless mb-0">
                                                        <tr>
                                                            <td class="text-muted">Calc. EMI:</td>
                                                            <td class="text-right font-weight-bold" id="manual-calc-emi">-</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted">Calc. Tenure:</td>
                                                            <td class="text-right font-weight-bold" id="manual-calc-tenure">-</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-muted">Interest Saved:</td>
                                                            <td class="text-right text-success" id="manual-calc-savings">-</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div id="manual-error" class="text-danger small mt-1" style="display: none;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Preview (shown after option selection) -->
                    <div id="previewSection" class="card card-outline card-success mb-3" style="display: none;">
                        <div class="card-header py-2">
                            <h5 class="card-title mb-0"><span class="badge badge-success mr-2">3</span> Preview Changes</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted"><i class="fas fa-arrow-left mr-1"></i> Before Part Payment</h6>
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <td>Outstanding Principal</td>
                                            <td class="text-right font-weight-bold"><?= format_amount($loan->outstanding_principal) ?></td>
                                        </tr>
                                        <tr>
                                            <td>EMI</td>
                                            <td class="text-right font-weight-bold"><?= format_amount($loan->emi_amount) ?></td>
                                        </tr>
                                        <tr>
                                            <td>Remaining Tenure</td>
                                            <td class="text-right font-weight-bold"><?= $remaining_tenure ?> months</td>
                                        </tr>
                                        <tr>
                                            <td>Total Interest Remaining</td>
                                            <td class="text-right" id="preview-old-interest">-</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-success"><i class="fas fa-arrow-right mr-1"></i> After Part Payment</h6>
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <td>Outstanding Principal</td>
                                            <td class="text-right font-weight-bold text-success" id="preview-new-principal">-</td>
                                        </tr>
                                        <tr>
                                            <td>EMI</td>
                                            <td class="text-right font-weight-bold" id="preview-new-emi">-</td>
                                        </tr>
                                        <tr>
                                            <td>Remaining Tenure</td>
                                            <td class="text-right font-weight-bold" id="preview-new-tenure">-</td>
                                        </tr>
                                        <tr>
                                            <td>Total Interest Remaining</td>
                                            <td class="text-right" id="preview-new-interest">-</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="alert alert-success text-center mb-0">
                                        <i class="fas fa-piggy-bank fa-lg mr-2"></i>
                                        <strong>Interest Savings: <span id="preview-savings" class="text-success">₹0</span></strong>
                                        <?php if ($prepayment_penalty_percent > 0): ?>
                                        <br><small class="text-warning">Prepayment Penalty: <span id="preview-penalty">₹0</span></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <button type="button" id="btnSubmit" class="btn btn-success btn-lg" disabled>
                                    <i class="fas fa-check-double mr-1"></i> Confirm & Process Part Payment
                                </button>
                                <a href="<?= site_url('admin/loans/view/' . $loan->id) ?>" class="btn btn-secondary btn-lg ml-2">
                                    <i class="fas fa-times mr-1"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-shield-alt mr-1"></i> Confirm Part Payment</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-2"></i>
                    <h5>Are you sure you want to process this part payment?</h5>
                    <p class="text-muted">This action will permanently alter the loan schedule and cannot be easily undone.</p>
                </div>
                <table class="table table-sm table-bordered" id="confirmTable">
                    <tr>
                        <td>Loan:</td>
                        <td class="font-weight-bold"><?= $loan->loan_number ?></td>
                    </tr>
                    <tr>
                        <td>Part Payment:</td>
                        <td class="font-weight-bold text-primary" id="confirm-amount">-</td>
                    </tr>
                    <tr>
                        <td>Adjustment:</td>
                        <td id="confirm-type">-</td>
                    </tr>
                    <tr>
                        <td>New EMI:</td>
                        <td id="confirm-emi">-</td>
                    </tr>
                    <tr>
                        <td>New Tenure:</td>
                        <td id="confirm-tenure">-</td>
                    </tr>
                    <tr>
                        <td>Interest Savings:</td>
                        <td class="text-success font-weight-bold" id="confirm-savings">-</td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button type="button" id="btnFinalConfirm" class="btn btn-success">
                    <i class="fas fa-check mr-1"></i> Yes, Process Payment
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const LOAN_ID = <?= $loan->id ?>;
    const OUTSTANDING = <?= $loan->outstanding_principal ?>;
    const CURRENT_EMI = <?= $loan->emi_amount ?>;
    const REMAINING_TENURE = <?= $remaining_tenure ?>;
    const INTEREST_RATE = <?= $loan->interest_rate ?>;
    const PENALTY_PERCENT = <?= $prepayment_penalty_percent ?>;
    const CSRF_NAME = '<?= $this->security->get_csrf_token_name() ?>';
    const BASE_URL = '<?= site_url() ?>';

    let calculatedOptions = null;
    let selectedOption = null;

    // --- Enable calculate button when amount entered ---
    $('#part_payment_amount').on('input', function() {
        const val = parseFloat($(this).val());
        const valid = val > 0 && val < OUTSTANDING;
        $('#btnCalculate').prop('disabled', !valid);
        if (!valid) {
            $('#optionsSection, #previewSection').hide();
        }
        // Show penalty estimate
        if (PENALTY_PERCENT > 0 && val > 0) {
            const penalty = (val * PENALTY_PERCENT / 100).toFixed(2);
            $('#penalty-display').html('Estimated penalty: <strong>' + formatCurrency(penalty) + '</strong>');
        }
    });

    // --- Calculate Options ---
    $('#btnCalculate').on('click', function() {
        const amount = parseFloat($('#part_payment_amount').val());
        if (!amount || amount <= 0 || amount >= OUTSTANDING) {
            toastr.error('Please enter a valid part payment amount.');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Calculating...');

        $.ajax({
            url: BASE_URL + 'admin/loans/calculate_part_payment',
            method: 'POST',
            data: {
                loan_id: LOAN_ID,
                part_payment_amount: amount,
                [CSRF_NAME]: $('input[name="' + CSRF_NAME + '"]').val()
            },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-calculator mr-1"></i> Calculate Options');

                if (!res.success) {
                    toastr.error(res.errors ? res.errors.join('<br>') : 'Calculation failed.');
                    return;
                }

                calculatedOptions = res.options;
                displayOptions(res.options);
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-calculator mr-1"></i> Calculate Options');
                toastr.error('Server error. Please try again.');
            }
        });
    });

    function displayOptions(opts) {
        // Option A
        $('#optA-emi').text(formatCurrency(opts.option_a.new_emi));
        $('#optA-tenure').text(opts.option_a.new_tenure + ' months');
        $('#optA-savings').text(formatCurrency(opts.option_a.interest_savings));

        // Option B
        if (opts.option_b.tenure_valid) {
            $('#optB-emi').text(formatCurrency(opts.option_b.new_emi));
            $('#optB-tenure').text(opts.option_b.new_tenure + ' months');
            $('#optB-savings').text(formatCurrency(opts.option_b.interest_savings));
            $('#option_reduce_tenure').prop('disabled', false);
        } else {
            $('#optB-emi').text('-');
            $('#optB-tenure').html('<span class="text-danger">N/A</span>');
            $('#optB-savings').text('-');
            $('#option_reduce_tenure').prop('disabled', true);
            if (opts.option_b.error) {
                $('#optionB-card .card-body').append('<div class="text-danger small mt-1">' + opts.option_b.error + '</div>');
            }
        }

        // Show options section
        $('#optionsSection').slideDown();
        
        // Pre-select Option A and update preview
        $('#option_reduce_emi').prop('checked', true).trigger('change');
    }

    // --- Option Selection ---
    $('input[name="adjustment_type"]').on('change', function() {
        const type = $(this).val();
        selectedOption = type;

        // Highlight selected card
        $('.option-card').removeClass('card-primary card-success card-warning').addClass('card-outline');
        if (type === 'reduce_emi') {
            $('#optionA-card').removeClass('card-outline').addClass('card-primary');
        } else if (type === 'reduce_tenure') {
            $('#optionB-card').removeClass('card-outline').addClass('card-success');
        } else if (type === 'manual') {
            $('#optionC-card').removeClass('card-outline').addClass('card-warning');
        }

        // Show/hide manual fields
        if (type === 'manual') {
            $('#manualFields').slideDown();
            $('#previewSection').hide();
            $('#btnSubmit').prop('disabled', true);
        } else {
            $('#manualFields').slideUp();
            updatePreview(type);
        }
    });

    function updatePreview(type) {
        if (!calculatedOptions) return;

        let newEmi, newTenure, interestSavings, newTotalInterest;
        const pp_amount = parseFloat($('#part_payment_amount').val());
        const newPrincipal = calculatedOptions.new_principal;

        if (type === 'reduce_emi') {
            newEmi = calculatedOptions.option_a.new_emi;
            newTenure = calculatedOptions.option_a.new_tenure;
            interestSavings = calculatedOptions.option_a.interest_savings;
            newTotalInterest = calculatedOptions.option_a.new_total_interest;
        } else if (type === 'reduce_tenure') {
            newEmi = calculatedOptions.option_b.new_emi;
            newTenure = calculatedOptions.option_b.new_tenure;
            interestSavings = calculatedOptions.option_b.interest_savings;
            newTotalInterest = calculatedOptions.option_b.new_total_interest;
        }

        // Populate preview
        $('#preview-new-principal').text(formatCurrency(newPrincipal));
        $('#preview-new-emi').text(formatCurrency(newEmi));
        $('#preview-new-tenure').text(newTenure + ' months');
        $('#preview-old-interest').text(formatCurrency(calculatedOptions.current.total_interest));
        $('#preview-new-interest').text(formatCurrency(newTotalInterest));
        $('#preview-savings').text(formatCurrency(interestSavings));

        if (PENALTY_PERCENT > 0) {
            const penalty = (pp_amount * PENALTY_PERCENT / 100).toFixed(2);
            $('#preview-penalty').text(formatCurrency(penalty));
        }

        // Show preview & enable submit
        $('#previewSection').slideDown();
        $('#btnSubmit').prop('disabled', false);

        // Store for confirmation
        selectedOption = {
            type: type,
            emi: newEmi,
            tenure: newTenure,
            savings: interestSavings,
            amount: pp_amount
        };
    }

    // --- Manual EMI/Tenure ---
    // Clear the other field when one is typed
    $('#manual_emi').on('input', function() {
        if ($(this).val()) $('#manual_tenure').val('');
    });
    $('#manual_tenure').on('input', function() {
        if ($(this).val()) $('#manual_emi').val('');
    });

    $('#btnManualCalc').on('click', function() {
        const manualEmi = $('#manual_emi').val();
        const manualTenure = $('#manual_tenure').val();

        if (!manualEmi && !manualTenure) {
            toastr.error('Please enter either a custom EMI or custom Tenure.');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Calculating...');

        $.ajax({
            url: BASE_URL + 'admin/loans/calculate_manual_override',
            method: 'POST',
            data: {
                new_principal: calculatedOptions.new_principal,
                interest_rate: INTEREST_RATE,
                manual_emi: manualEmi || '',
                manual_tenure: manualTenure || '',
                old_principal: OUTSTANDING,
                old_emi: CURRENT_EMI,
                old_tenure: REMAINING_TENURE,
                [CSRF_NAME]: $('input[name="' + CSRF_NAME + '"]').val()
            },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Recalculate');

                if (!res.success) {
                    $('#manual-error').text(res.errors ? res.errors.join(' ') : 'Invalid values.').show();
                    $('#manual-result').hide();
                    $('#previewSection').hide();
                    $('#btnSubmit').prop('disabled', true);
                    return;
                }

                $('#manual-error').hide();
                $('#manual-calc-emi').text(formatCurrency(res.calculated_emi));
                $('#manual-calc-tenure').text(res.calculated_tenure + ' months');
                $('#manual-calc-savings').text(formatCurrency(res.interest_savings));
                $('#manual-result').show();

                // Update preview   
                $('#preview-new-principal').text(formatCurrency(calculatedOptions.new_principal));
                $('#preview-new-emi').text(formatCurrency(res.calculated_emi));
                $('#preview-new-tenure').text(res.calculated_tenure + ' months');
                $('#preview-old-interest').text(formatCurrency(calculatedOptions.current.total_interest));
                $('#preview-new-interest').text(formatCurrency(res.total_interest));
                $('#preview-savings').text(formatCurrency(res.interest_savings));

                const pp_amount = parseFloat($('#part_payment_amount').val());
                if (PENALTY_PERCENT > 0) {
                    const penalty = (pp_amount * PENALTY_PERCENT / 100).toFixed(2);
                    $('#preview-penalty').text(formatCurrency(penalty));
                }

                selectedOption = {
                    type: 'manual',
                    emi: res.calculated_emi,
                    tenure: res.calculated_tenure,
                    savings: res.interest_savings,
                    amount: pp_amount
                };

                $('#previewSection').slideDown();
                $('#btnSubmit').prop('disabled', false);
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Recalculate');
                toastr.error('Server error.');
            }
        });
    });

    // --- Submit (show confirmation modal) ---
    $('#btnSubmit').on('click', function(e) {
        e.preventDefault();
        if (!selectedOption) return;

        const opt = selectedOption;
        const typeLabels = {
            'reduce_emi': 'Reduce EMI',
            'reduce_tenure': 'Reduce Tenure',
            'manual': 'Manual Override'
        };

        $('#confirm-amount').text(formatCurrency(opt.amount || parseFloat($('#part_payment_amount').val())));
        $('#confirm-type').text(typeLabels[opt.type] || opt.type);
        $('#confirm-emi').text(formatCurrency(opt.emi));
        $('#confirm-tenure').text(opt.tenure + ' months');
        $('#confirm-savings').text(formatCurrency(opt.savings));

        $('#confirmModal').modal('show');
    });

    // --- Final Confirm ---
    $('#btnFinalConfirm').on('click', function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');
        $('#confirmModal').modal('hide');
        $('#partPaymentForm').submit();
    });

    // --- Utility ---
    function formatCurrency(val) {
        if (val === null || val === undefined || isNaN(val)) return '-';
        return '₹' + parseFloat(val).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
});
</script>
