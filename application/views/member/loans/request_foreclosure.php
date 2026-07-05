<?php
$cs              = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : get_currency_symbol();
$principal       = (float)($settlement['outstanding_principal']   ?? 0);
$total_interest  = (float)($settlement['total_interest']          ?? 0);
$interest_pct    = (float)($settlement['interest_charge_pct']     ?? 80);
$interest_charge = (float)($settlement['interest_charge']         ?? 0);
$fines           = (float)($settlement['pending_fines']           ?? 0);
$total           = (float)($settlement['total_settlement']        ?? 0);
$annual_rate     = (float)($loan->interest_rate ?? 0);
?>

<div class="content-header" style="background:linear-gradient(135deg,#1a3c6e 0%,#2c5f9e 100%);margin-bottom:0;">
    <div class="container-fluid py-2">
        <div class="row align-items-center">
            <div class="col">
                <h4 class="m-0 text-white"><i class="fas fa-hand-holding-usd mr-2"></i><?= htmlspecialchars($page_title) ?></h4>
                <ol class="breadcrumb bg-transparent p-0 m-0" style="font-size:12px;">
                    <li class="breadcrumb-item"><a href="<?= site_url('member/dashboard') ?>" class="text-white-50">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= site_url('member/loans') ?>" class="text-white-50">My Loans</a></li>
                    <li class="breadcrumb-item text-white active"><?= htmlspecialchars($loan->loan_number) ?></li>
                </ol>
            </div>
            <div class="col-auto">
                <a href="<?= site_url('member/loans/view/' . $loan->id) ?>" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Loan
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content" style="background:#f4f6f9;">
<div class="container-fluid py-4">

<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger alert-dismissible shadow-sm"><button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-exclamation-triangle mr-2"></i><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success alert-dismissible shadow-sm"><button type="button" class="close" data-dismiss="alert">&times;</button>
    <i class="fas fa-check-circle mr-2"></i><?= $this->session->flashdata('success') ?></div>
<?php endif; ?>

<!-- Loan Info Bar -->
<div class="card shadow-sm mb-3" style="border-left:4px solid #1a3c6e;">
    <div class="card-body py-3">
        <div class="row align-items-center text-center text-md-left">
            <div class="col-6 col-md-3 border-right">
                <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Loan Number</div>
                <div class="font-weight-bold text-primary"><?= htmlspecialchars($loan->loan_number) ?></div>
            </div>
            <div class="col-6 col-md-3 border-right">
                <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Product</div>
                <div class="font-weight-bold"><?= htmlspecialchars($loan->product_name ?? 'N/A') ?></div>
            </div>
            <div class="col-6 col-md-3 border-right mt-2 mt-md-0">
                <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Outstanding Principal</div>
                <div class="font-weight-bold text-danger" style="font-size:1.1em;"><?= $cs.number_format($principal,2) ?></div>
            </div>
            <div class="col-6 col-md-3 mt-2 mt-md-0">
                <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">Interest Rate</div>
                <div class="font-weight-bold"><?= $annual_rate ?>% p.a.</div>
            </div>
        </div>
    </div>
</div>

<!-- Settlement Breakdown -->
<div class="card shadow-sm mb-3" style="border-radius:10px;">
    <div class="card-header bg-white border-bottom d-flex align-items-center" style="border-radius:10px 10px 0 0;">
        <h5 class="mb-0"><i class="fas fa-calculator text-primary mr-2"></i>Foreclosure Settlement Breakdown</h5>
        <span class="badge badge-primary ml-auto">Standard Foreclosure</span>
    </div>
    <div class="card-body">
        <table class="table table-sm mb-2" style="font-size:13px;">
            <tr>
                <td class="text-muted border-0 py-1">Outstanding Principal</td>
                <td class="text-right border-0 py-1 font-weight-bold"><?= $cs.number_format($principal,2) ?></td>
            </tr>
            <tr>
                <td class="text-muted border-0 py-1">Total Pending Interest (Current + Future Months)</td>
                <td class="text-right border-0 py-1"><?= $cs.number_format($total_interest,2) ?></td>
            </tr>
            <tr>
                <td class="text-muted border-0 py-1">
                    Interest Charge (<?= $interest_pct ?>% of pending interest)
                    <small class="d-block text-muted">Based on admin configuration</small>
                </td>
                <td class="text-right border-0 py-1 text-info"><?= $cs.number_format($interest_charge,2) ?></td>
            </tr>
            <?php if ($fines > 0): ?>
            <tr>
                <td class="text-muted border-0 py-1">Pending Fines</td>
                <td class="text-right border-0 py-1 text-danger"><?= $cs.number_format($fines,2) ?></td>
            </tr>
            <?php endif; ?>
            <tr style="border-top:2px solid #dee2e6;">
                <td class="font-weight-bold py-2" style="font-size:14px;">Total to Pay</td>
                <td class="text-right font-weight-bold text-primary py-2" style="font-size:1.4em;"><?= $cs.number_format($total,2) ?></td>
            </tr>
        </table>
    </div>
</div>

<!-- Amount Banner -->
<div class="card shadow-sm mb-4" style="border-radius:10px;border:none;background:linear-gradient(135deg,#1a3c6e,#2c5f9e);color:#fff;">
    <div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col">
                <div style="font-size:12px;opacity:.8;text-transform:uppercase;letter-spacing:.5px;">You will pay (Foreclosure)</div>
                <div class="font-weight-bold" style="font-size:2em;"><?= $cs.number_format($total,2) ?></div>
                <div style="font-size:12px;opacity:.75;">Includes principal, accrued interest &amp; all charges</div>
            </div>
            <div class="col-auto d-none d-md-block">
                <i class="fas fa-rupee-sign" style="font-size:4em;opacity:.15;"></i>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm" style="border-radius:10px;">
    <div class="card-header bg-white border-bottom" style="border-radius:10px 10px 0 0;">
        <h5 class="mb-0"><i class="fas fa-paper-plane text-primary mr-2"></i>Submit Foreclosure Request</h5>
    </div>
    <div class="card-body">
        <form action="<?= site_url('member/loans/request_foreclosure/' . $loan->id) ?>" method="post" id="foreclosureForm">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <input type="hidden" name="closure_type" value="regular">

            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="font-weight-bold" for="reason">Reason for Foreclosure <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" required
                            placeholder="e.g., I have received funds and want to close the loan early…"></textarea>
                        <small class="text-muted">Please explain why you are requesting early closure.</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold" for="preferred_date">Preferred Settlement Date</label>
                        <input type="date" name="preferred_date" id="preferred_date" class="form-control"
                               min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                        <small class="text-muted">The final amount may differ slightly based on the actual settlement date.</small>
                    </div>

                    <div class="p-3 rounded mb-3" style="background:#fff8e1;border:1px solid #ffe082;">
                        <p class="mb-2" style="font-size:13px;font-weight:600;"><i class="fas fa-exclamation-triangle text-warning mr-2"></i>Important — please read before submitting</p>
                        <ul class="mb-2 pl-3" style="font-size:12px;color:#555;">
                            <li>This request will be reviewed by our team within <strong>2–3 business days</strong>.</li>
                            <li>The final amount may differ slightly from the estimate shown above.</li>
                            <li>The loan is closed only after <strong>full payment</strong> of the approved amount.</li>
                        </ul>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="agree_terms" name="agree_terms" required>
                            <label class="custom-control-label" for="agree_terms" style="font-size:13px;">
                                I have read and agree to the above terms for loan foreclosure.
                            </label>
                        </div>
                    </div>

                    <div class="d-flex" style="gap:10px;">
                        <a href="<?= site_url('member/loans/view/' . $loan->id) ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4" id="submitBtn" disabled>
                            <i class="fas fa-paper-plane mr-2"></i>Submit Request
                        </button>
                    </div>
                </div>

                <div class="col-md-4 mt-3 mt-md-0">
                    <div class="card" style="border-radius:8px;border:1px solid #e0e0e0;">
                        <div class="card-header bg-light py-2">
                            <strong style="font-size:13px;"><i class="fas fa-receipt mr-1 text-primary"></i>Request Summary</strong>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between mb-2" style="font-size:13px;">
                                <span class="text-muted">Loan</span>
                                <span class="font-weight-bold"><?= htmlspecialchars($loan->loan_number) ?></span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                                <span class="text-muted">Principal</span>
                                <span><?= $cs.number_format($prin,2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                                <span class="text-muted">Accrued Interest</span>
                                <span><?= $cs.number_format($acc,2) ?></span>
                            </div>
                            <?php if ($pen > 0): ?>
                            <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                                <span class="text-muted">Prepayment Charge</span>
                                <span><?= $cs.number_format($pen,2) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($fine > 0): ?>
                            <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                                <span class="text-muted">Pending Fines</span>
                                <span class="text-danger"><?= $cs.number_format($fine,2) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($pic > 0): ?>
                            <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                                <span class="text-muted">Foreclosure Charge (<?= $pic_pct ?>%)</span>
                                <span class="text-danger"><?= $cs.number_format($pic,2) ?></span>
                            </div>
                            <?php endif; ?>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="font-weight-bold">Total</span>
                                <span class="font-weight-bold text-primary" style="font-size:1.1em;"><?= $cs.number_format($total,2) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-light border mt-3 mb-0" style="font-size:12px;">
                        <i class="fas fa-headset text-primary mr-1"></i>Need help? Contact our support team before submitting.
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

</div>
</section>

<script>
$(function(){
    $('#agree_terms').on('change', function(){
        $('#submitBtn').prop('disabled', !this.checked);
    });
    $('#foreclosureForm').on('submit', function(){
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Submitting…');
    });
});
</script>
