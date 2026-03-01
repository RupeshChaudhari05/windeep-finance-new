<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> New Loan Application</h3>
            </div>
            <form action="<?= site_url('admin/loans/submit_application') ?>" method="post" id="loanForm">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                
                <div class="card-body">
                    <!-- Member Selection -->
                    <h5 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-user mr-1"></i> Applicant Information
                    </h5>
                    
                    <div class="form-group">
                        <label for="member_id">Select Member <span class="text-danger">*</span>
                            <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Search and select the member applying for the loan. Member must have active status and completed KYC."></i>
                        </label>
                        <select class="form-control select2" id="member_id" name="member_id" required data-placeholder="Search by name or code...">
                            <option value="">Select Member</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= $m->id ?>" <?= ($selected_member ?? '') == $m->id ? 'selected' : '' ?>
                                        data-kyc="<?= $m->kyc_verified ?>" data-savings="<?= $m->savings_balance ?? 0 ?>"
                                        data-loans="<?= $m->active_loans ?? 0 ?>">
                                    <?= $m->member_code ?> - <?= $m->first_name ?> <?= $m->last_name ?> (<?= $m->phone ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="text-danger"><?= form_error('member_id') ?></span>
                    </div>
                    
                    <!-- Member Info Display -->
                    <div id="memberInfo" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-box shadow-sm mb-0">
                                    <span class="info-box-icon bg-success"><i class="fas fa-id-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">KYC Status</span>
                                        <span class="info-box-number" id="infoKyc"><span class="text-muted small">— select member —</span></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box shadow-sm mb-0">
                                    <span class="info-box-icon bg-info"><i class="fas fa-piggy-bank"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Security Deposit Balance</span>
                                        <span class="info-box-number" id="infoSavings"><span class="text-muted small">—</span></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box shadow-sm mb-0">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-file-contract"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Active Loans</span>
                                        <span class="info-box-number" id="infoLoans"><span class="text-muted small">—</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Loan Details -->
                    <h5 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-hand-holding-usd mr-1"></i> Loan Details
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="product_id">Loan Product <span class="text-danger">*</span>
                                    <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Select the loan product. Each product has different interest rates, tenure ranges, and amount limits."></i>
                                </label>
                                <select class="form-control" id="product_id" name="loan_product_id" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p->id ?>" 
                                                data-min="<?= $p->min_amount ?>" data-max="<?= $p->max_amount ?>"
                                                data-rate="<?= $p->interest_rate ?>" data-type="<?= $p->interest_type ?>"
                                                data-tenure-min="<?= $p->min_tenure_months ?>" data-tenure-max="<?= $p->max_tenure_months ?>"
                                                data-guarantors="<?= $p->min_guarantors ?>">
                                            <?= $p->product_name ?> (<?= $p->interest_rate ?>% <?= ucfirst($p->interest_type) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small id="productInfo" class="form-text text-muted"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="requested_amount">Loan Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span>
                                    <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Amount the member wants to borrow. Must be within the product's min-max range shown below."></i>
                                </label>
                                <input type="number" class="form-control" id="requested_amount" name="requested_amount" 
                                       value="<?= set_value('requested_amount') ?>" required min="1000"
                                       placeholder="Enter loan amount">
                                <small id="amountRange" class="form-text text-muted"></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tenure_months">Tenure (Months) <span class="text-danger">*</span>
                                    <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Loan repayment period in months. Longer tenure = lower EMI but more total interest."></i>
                                </label>
                                <input type="number" class="form-control" id="tenure_months" name="requested_tenure_months" 
                                       value="<?= set_value('requested_tenure_months', 12) ?>" required min="1" max="240">
                                <small id="tenureRange" class="form-text text-muted"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="emi_date">EMI Payment Day <span class="text-danger">*</span>
                                    <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Day of each month when EMI is due. Choose a date after the member's salary date. Max 28 to avoid month-end issues."></i>
                                </label>
                                <select class="form-control" id="emi_date" name="emi_date" required title="Day of month for EMI payment">
                                    <?php for ($i = 1; $i <= 28; $i++): ?>
                                        <option value="<?= $i ?>" <?= $i == 5 ? 'selected' : '' ?>><?= $i ?>th</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="purpose">Loan Purpose <span class="text-danger">*</span>
                            <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Primary reason for taking the loan. This is recorded for regulatory compliance."></i>
                        </label>
                        <select class="form-control" id="purpose" name="purpose" required>
                            <option value="">Select Purpose</option>
                            <option value="business">Business / Working Capital</option>
                            <option value="education">Education</option>
                            <option value="medical">Medical Emergency</option>
                            <option value="agriculture">Agriculture</option>
                            <option value="vehicle">Vehicle Purchase</option>
                            <option value="home_improvement">Home Improvement</option>
                            <option value="marriage">Marriage / Family Function</option>
                            <option value="personal">Personal / Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="purpose_details">Purpose Details</label>
                        <textarea class="form-control" id="purpose_details" name="purpose_details" rows="2" 
                                  placeholder="Describe the purpose of loan in detail"><?= set_value('purpose_details') ?></textarea>
                    </div>
                    
                    <hr>
                    
                    <!-- Guarantors -->
                    <h5 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-user-friends mr-1"></i> Guarantor Information
                        <small class="text-muted" id="guarantorRequired">(Required: 0)</small>
                    </h5>
                    
                    <div id="guarantorsContainer">
                        <div class="guarantor-row row mb-3" data-index="0">
                            <div class="col-md-5">
                                <select class="form-control select2-guarantor" name="guarantor_ids[]" data-placeholder="Select Guarantor">
                                    <option value="">Select Guarantor (Optional)</option>
                                    <?php foreach ($members as $m): ?>
                                        <option value="<?= $m->id ?>"><?= $m->member_code ?> - <?= $m->first_name ?> <?= $m->last_name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="guarantor_relation[]" placeholder="Relationship with applicant">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-block remove-guarantor" disabled>
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="addGuarantor">
                        <i class="fas fa-plus"></i> Add Another Guarantor
                    </button>
                    
                    <hr>
                    
                    <div class="form-group">
                        <label for="remarks">Additional Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2" 
                                  placeholder="Any additional notes or comments"><?= set_value('remarks') ?></textarea>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i> Submit Application
                    </button>
                    <a href="<?= site_url('admin/loans/applications') ?>" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- EMI Calculator -->
    <div class="col-md-4">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calculator mr-1"></i> EMI Calculator</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td>Loan Amount:</td>
                        <td class="text-right font-weight-bold" id="calcAmount"><?= get_currency_symbol() ?>0</td>
                    </tr>
                    <tr>
                        <td>Interest Rate:</td>
                        <td class="text-right" id="calcRate">-</td>
                    </tr>
                    <tr>
                        <td>Tenure:</td>
                        <td class="text-right" id="calcTenure">-</td>
                    </tr>
                    <tr class="border-top">
                        <td><strong>Monthly EMI:</strong></td>
                        <td class="text-right font-weight-bold text-primary" id="calcEMI"><?= get_currency_symbol() ?>0</td>
                    </tr>
                    <tr>
                        <td>Total Interest:</td>
                        <td class="text-right text-warning" id="calcTotalInterest"><?= get_currency_symbol() ?>0</td>
                    </tr>
                    <tr>
                        <td>Total Payable:</td>
                        <td class="text-right font-weight-bold" id="calcTotal"><?= get_currency_symbol() ?>0</td>
                    </tr>
                </table>
                
                <hr>
                
                <div class="card">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0">Repayment Schedule Preview</h6>
                    </div>
                    <div class="card-body p-2" id="schedulePreview" style="max-height: 300px; overflow-y: auto;">
                        <p class="text-muted text-center">Select product and amount</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Info -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Product Details</h3>
            </div>
            <div class="card-body" id="productDetails">
                <p class="text-muted text-center">Select a loan product</p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var requiredGuarantors = 0;
    
    // Initialize Select2
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
    initGuarantorSelect2();
    
    function initGuarantorSelect2() {
        $('.select2-guarantor').select2({ theme: 'bootstrap4', width: '100%' });
    }
    
    // Member selection
    $('#member_id').on('change', function() {
        var selected = $(this).find(':selected');
        if (selected.val()) {
            $('#infoKyc').html(selected.data('kyc') == '1' ? '<span class="badge badge-success">Verified</span>' : '<span class="badge badge-warning">Pending</span>');
            $('#infoSavings').text('<?= get_currency_symbol() ?>' + Number(selected.data('savings')).toLocaleString('en-IN'));
            $('#infoLoans').text(selected.data('loans') || '0');
        } else {
            $('#infoKyc').html('<span class="text-muted small">— select member —</span>');
            $('#infoSavings').html('<span class="text-muted small">—</span>');
            $('#infoLoans').html('<span class="text-muted small">—</span>');
        }
    });
    
    // Product selection
    $('#product_id').on('change', function() {
        var selected = $(this).find(':selected');
        if (selected.val()) {
            var min = selected.data('min');
            var max = selected.data('max');
            var rate = selected.data('rate');
            var type = selected.data('type');
            var tenureMin = selected.data('tenure-min');
            var tenureMax = selected.data('tenure-max');
            requiredGuarantors = selected.data('guarantors');
            
            $('#amountRange').text('Min: <?= get_currency_symbol() ?>' + min.toLocaleString() + ' | Max: <?= get_currency_symbol() ?>' + max.toLocaleString());
            $('#tenureRange').text('Min: ' + tenureMin + ' months | Max: ' + tenureMax + ' months');
            $('#guarantorRequired').text('(Required: ' + requiredGuarantors + ')');
            
            $('#requested_amount').attr('min', min).attr('max', max);
            $('#tenure_months').attr('min', tenureMin).attr('max', tenureMax);
            
            $('#productDetails').html(`
                <table class="table table-sm table-borderless">
                    <tr><td>Product:</td><td class="text-right"><strong>${selected.text().split('(')[0]}</strong></td></tr>
                    <tr><td>Interest:</td><td class="text-right">${rate}% p.a. (${type})</td></tr>
                    <tr><td>Amount Range:</td><td class="text-right"><?= get_currency_symbol() ?>${min.toLocaleString()} - <?= get_currency_symbol() ?>${max.toLocaleString()}</td></tr>
                    <tr><td>Tenure Range:</td><td class="text-right">${tenureMin} - ${tenureMax} months</td></tr>
                    <tr><td>Guarantors:</td><td class="text-right">${requiredGuarantors} required</td></tr>
                </table>
            `);
            
            calculateEMI();
        }
    });
    
    // Calculate EMI on input change
    $('#requested_amount, #tenure_months').on('change input', calculateEMI);
    
    function calculateEMI() {
        var principal = parseFloat($('#requested_amount').val()) || 0;
        var tenure = parseInt($('#tenure_months').val()) || 12;
        var selected = $('#product_id').find(':selected');
        var rate = parseFloat(selected.data('rate')) || 0;
        var type = selected.data('type') || 'reducing';
        
        if (principal <= 0 || tenure <= 0) return;
        
        var monthlyRate = rate / 12 / 100;
        var emi, totalInterest;
        
        if (type === 'flat') {
            totalInterest = Math.round(principal * rate * tenure / 12 / 100);
            emi = Math.round((principal + totalInterest) / tenure);
        } else {
            // Reducing balance EMI formula
            if (monthlyRate > 0) {
                emi = Math.round(principal * monthlyRate * Math.pow(1 + monthlyRate, tenure) / (Math.pow(1 + monthlyRate, tenure) - 1));
            } else {
                emi = Math.round(principal / tenure);
            }
            totalInterest = (emi * tenure) - principal;
        }
        
        var totalPayable = principal + totalInterest;
        
        $('#calcAmount').text('<?= get_currency_symbol() ?>' + principal.toLocaleString());
        $('#calcRate').text(rate + '% p.a. (' + type + ')');
        $('#calcTenure').text(tenure + ' months');
        $('#calcEMI').text('<?= get_currency_symbol() ?>' + emi.toLocaleString());
        $('#calcTotalInterest').text('<?= get_currency_symbol() ?>' + totalInterest.toLocaleString());
        $('#calcTotal').text('<?= get_currency_symbol() ?>' + totalPayable.toLocaleString());
        
        // Generate mini schedule
        generateSchedulePreview(principal, emi, monthlyRate, tenure, type);
    }
    
    function generateSchedulePreview(principal, emi, monthlyRate, tenure, type) {
        var html = '<table class="table table-xs table-bordered"><thead><tr><th>No</th><th>EMI</th><th>Balance</th></tr></thead><tbody>';
        var balance = principal;
        
        for (var i = 1; i <= Math.min(tenure, 12); i++) {
            var interest, principalPart;
            if (type === 'flat') {
                interest = Math.round(principal * monthlyRate * 12);
                principalPart = Math.round(principal / tenure);
            } else {
                interest = Math.round(balance * monthlyRate);
                principalPart = emi - interest;
            }
            balance = Math.max(0, balance - principalPart);
            html += '<tr><td>' + i + '</td><td><?= get_currency_symbol() ?>' + emi.toLocaleString() + '</td><td><?= get_currency_symbol() ?>' + Math.round(balance).toLocaleString() + '</td></tr>';
        }
        
        if (tenure > 12) {
            html += '<tr><td colspan="3" class="text-center text-muted">... ' + (tenure - 12) + ' more EMIs ...</td></tr>';
        }
        
        html += '</tbody></table>';
        $('#schedulePreview').html(html);
    }
    
    // Add guarantor row
    var guarantorIndex = 1;
    $('#addGuarantor').on('click', function() {
        var html = `
            <div class="guarantor-row row mb-3" data-index="${guarantorIndex}">
                <div class="col-md-5">
                    <select class="form-control select2-guarantor" name="guarantor_ids[]" data-placeholder="Select Guarantor">
                        <option value="">Select Guarantor</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= $m->id ?>"><?= $m->member_code ?> - <?= $m->first_name ?> <?= $m->last_name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="guarantor_relation[]" placeholder="Relationship (optional)">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-block remove-guarantor">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        $('#guarantorsContainer').append(html);
        initGuarantorSelect2();
        guarantorIndex++;
    });
    
    // Remove guarantor row
    $(document).on('click', '.remove-guarantor', function() {
        $(this).closest('.guarantor-row').remove();
    });
    
    // Form validation
    $('#loanForm').on('submit', function(e) {
        var memberId = $('#member_id').val();
        var productId = $('#product_id').val();
        var amount = $('#requested_amount').val();
        
        if (!memberId || !productId || !amount) {
            e.preventDefault();
            Swal.fire('Error', 'Please fill all required fields', 'error');
            return false;
        }
        
        // Check guarantors
        var filledGuarantors = $('select[name="guarantor_ids[]"]').filter(function() { return $(this).val(); }).length;
        if (filledGuarantors < requiredGuarantors) {
            e.preventDefault();
            Swal.fire('Error', 'Please add at least ' + requiredGuarantors + ' guarantor(s)', 'error');
            return false;
        }
        
        Swal.fire({
            title: 'Submitting...',
            text: 'Please wait',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => { Swal.showLoading(); }
        });
    });
    
    // Trigger if pre-selected
    if ($('#member_id').val()) $('#member_id').trigger('change');
});
</script>
