<!-- Request Foreclosure View -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-hand-holding-usd mr-2"></i><?= $page_title ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= site_url('member/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= site_url('member/loans') ?>">My Loans</a></li>
                    <li class="breadcrumb-item active">Foreclosure Request</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-exclamation-triangle mr-1"></i> <?= $this->session->flashdata('error') ?>
            </div>
        <?php endif; ?>
        
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-check-circle mr-1"></i> <?= $this->session->flashdata('success') ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Loan Details -->
            <div class="col-md-6">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> Loan Details</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-sm">
                            <tr>
                                <th style="width: 40%" title="Unique loan identification number">
                                    <i class="fas fa-hashtag text-muted mr-1"></i> Loan Number
                                </th>
                                <td><?= htmlspecialchars($loan->loan_number) ?></td>
                            </tr>
                            <tr>
                                <th title="The loan product type">
                                    <i class="fas fa-box text-muted mr-1"></i> Product
                                </th>
                                <td><?= htmlspecialchars($loan->product_name ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <th title="Original loan amount disbursed">
                                    <i class="fas fa-rupee-sign text-muted mr-1"></i> Principal Amount
                                </th>
                                <td class="font-weight-bold"><?= isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '₹' ?><?= number_format($loan->principal_amount, 2) ?></td>
                            </tr>
                            <tr>
                                <th title="Remaining principal balance on the loan">
                                    <i class="fas fa-balance-scale text-muted mr-1"></i> Outstanding Principal
                                </th>
                                <td class="text-danger font-weight-bold"><?= isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '₹' ?><?= number_format($loan->outstanding_principal ?? 0, 2) ?></td>
                            </tr>
                            <tr>
                                <th title="Current status of your loan">
                                    <i class="fas fa-info-circle text-muted mr-1"></i> Status
                                </th>
                                <td>
                                    <span class="badge badge-<?= $loan->status === 'active' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($loan->status) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Settlement Calculation -->
            <div class="col-md-6">
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calculator mr-1"></i> Settlement Amount</h3>
                    </div>
                    <div class="card-body">
                        <?php $cs = isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '₹'; ?>
                        <table class="table table-bordered table-sm">
                            <tr>
                                <th title="Remaining principal to be paid">Outstanding Principal</th>
                                <td class="text-right"><?= $cs ?><?= number_format($settlement['outstanding_principal'] ?? 0, 2) ?></td>
                            </tr>
                            <tr>
                                <th title="Interest accrued up to today">Accrued Interest</th>
                                <td class="text-right"><?= $cs ?><?= number_format($settlement['accrued_interest'] ?? 0, 2) ?></td>
                            </tr>
                            <?php if (!empty($settlement['penalty_amount'])): ?>
                            <tr>
                                <th title="Any late payment penalties or charges">Penalty / Charges</th>
                                <td class="text-right text-danger"><?= $cs ?><?= number_format($settlement['penalty_amount'], 2) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($settlement['foreclosure_fee'])): ?>
                            <tr>
                                <th title="Fee charged for early loan closure">Foreclosure Fee</th>
                                <td class="text-right"><?= $cs ?><?= number_format($settlement['foreclosure_fee'], 2) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="bg-light">
                                <th class="text-primary font-weight-bold" title="Total amount you need to pay to close this loan completely">
                                    <i class="fas fa-coins mr-1"></i> Total Settlement Amount
                                </th>
                                <td class="text-right text-primary font-weight-bold" style="font-size: 1.2rem;">
                                    <?= $cs ?><?= number_format($settlement['total_settlement'] ?? 0, 2) ?>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>How it works:</strong> Submit a foreclosure request. Our team will review and approve it within 2-3 business days. 
                            Once approved, you can pay the settlement amount to close your loan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Foreclosure Request Form -->
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-paper-plane mr-1"></i> Submit Foreclosure Request</h3>
            </div>
            <div class="card-body">
                <form action="<?= site_url('member/loans/request_foreclosure/' . $loan->id) ?>" method="post">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    
                    <div class="form-group">
                        <label for="reason">
                            <i class="fas fa-comment-alt mr-1"></i> Reason for Foreclosure 
                            <span class="text-danger">*</span>
                            <i class="fas fa-question-circle text-muted ml-1" title="Tell us why you want to close this loan early. This helps our team process your request faster."></i>
                        </label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" required 
                                  placeholder="e.g., I have received funds and want to close the loan early..."
                                  title="Provide the reason for early loan closure"></textarea>
                        <small class="form-text text-muted">Please explain why you want to foreclose this loan.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="preferred_date">
                            <i class="fas fa-calendar-alt mr-1"></i> Preferred Settlement Date
                            <i class="fas fa-question-circle text-muted ml-1" title="Choose the date by which you plan to pay the settlement amount."></i>
                        </label>
                        <input type="date" name="preferred_date" id="preferred_date" class="form-control" 
                               min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+3 days')) ?>"
                               title="Select when you want to complete the settlement payment">
                        <small class="form-text text-muted">The settlement amount may change based on the actual settlement date.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="agree_terms" name="agree_terms" required>
                            <label class="custom-control-label" for="agree_terms">
                                I understand that the final settlement amount may vary and I agree to pay the amount as determined by the organization.
                            </label>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <a href="<?= site_url('member/loans') ?>" class="btn btn-secondary btn-block">
                                <i class="fas fa-arrow-left mr-1"></i> Back to My Loans
                            </a>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-success btn-block" id="submitBtn">
                                <i class="fas fa-paper-plane mr-1"></i> Submit Foreclosure Request
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
$(function() {
    // Enable submit only when terms agreed
    $('#agree_terms').on('change', function() {
        $('#submitBtn').prop('disabled', !this.checked);
    });
    $('#submitBtn').prop('disabled', true);
    
    // Add tooltips
    $('[title]').tooltip({placement: 'top', trigger: 'hover'});
});
</script>
