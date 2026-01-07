<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-piggy-bank mr-1"></i> Open New Savings Account</h3>
            </div>
            <form action="<?= site_url('admin/savings/store') ?>" method="post" id="savingsForm">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                
                <div class="card-body">
                    <!-- Member Selection -->
                    <div class="form-group">
                        <label for="member_id">Select Member <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="member_id" name="member_id" required data-placeholder="Search by name or member code...">
                            <option value="">Select Member</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= $m->id ?>" <?= ($selected_member ?? '') == $m->id ? 'selected' : '' ?> 
                                        data-phone="<?= $m->phone ?>" data-kyc="<?= $m->kyc_verified ?>">
                                    <?= $m->member_code ?> - <?= $m->first_name ?> <?= $m->last_name ?> (<?= $m->phone ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="text-danger"><?= form_error('member_id') ?></span>
                    </div>
                    
                    <!-- Member Info Display -->
                    <div id="memberInfo" class="alert alert-info d-none mb-3">
                        <div class="row">
                            <div class="col-6"><strong>Name:</strong> <span id="infoName"></span></div>
                            <div class="col-6"><strong>Phone:</strong> <span id="infoPhone"></span></div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-6"><strong>KYC Status:</strong> <span id="infoKyc"></span></div>
                            <div class="col-6"><strong>Existing Savings:</strong> <span id="infoSavings"></span></div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Scheme Selection -->
                    <div class="form-group">
                        <label for="scheme_id">Savings Scheme <span class="text-danger">*</span></label>
                        <select class="form-control" id="scheme_id" name="scheme_id" required>
                            <option value="">Select Scheme</option>
                            <?php foreach ($schemes as $scheme): ?>
                                <option value="<?= $scheme->id ?>" 
                                        data-min="<?= $scheme->monthly_amount ?>" 
                                        data-max="<?= $scheme->maximum_amount ?? '' ?>"
                                        data-interest="<?= $scheme->interest_rate ?>"
                                        data-duration="<?= $scheme->duration_months ?>">
                                    <?= $scheme->scheme_name ?> (<?= $scheme->interest_rate ?>% p.a.)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small id="schemeInfo" class="form-text text-muted"></small>
                        <span class="text-danger"><?= form_error('scheme_id') ?></span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="monthly_amount">Monthly Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="monthly_amount" name="monthly_amount" 
                                       value="<?= set_value('monthly_amount') ?>" required min="100"
                                       placeholder="Enter monthly deposit amount">
                                <small id="amountRange" class="form-text text-muted"></small>
                                <span class="text-danger"><?= form_error('monthly_amount') ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="due_date">Due Date <span class="text-danger">*</span></label>
                                <select class="form-control" id="due_date" name="due_date" required title="Day of month when payment is due">
                                    <?php for ($i = 1; $i <= 28; $i++): ?>
                                        <option value="<?= $i ?>" <?= $i == 5 ? 'selected' : '' ?>><?= $i ?>th of every month</option>
                                    <?php endfor; ?>
                                </select>
                                <span class="text-danger"><?= form_error('due_date') ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?= date('Y-m-d') ?>" required>
                                <span class="text-danger"><?= form_error('start_date') ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="duration_months">Duration (Months)</label>
                                <input type="number" class="form-control" id="duration_months" name="duration_months" 
                                       value="<?= set_value('duration_months', 12) ?>" min="1" max="240"
                                       placeholder="Leave blank for indefinite">
                                <small class="form-text text-muted">Leave blank for open-ended savings</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Initial Deposit -->
                    <hr>
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" class="custom-control-input" id="collectNow" name="collect_now" value="1">
                        <label class="custom-control-label" for="collectNow">
                            <strong>Collect First Deposit Now</strong>
                        </label>
                    </div>
                    
                    <div id="depositSection" class="d-none">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="deposit_amount">Deposit Amount (₹)</label>
                                    <input type="number" class="form-control" id="deposit_amount" name="deposit_amount" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_mode">Payment Mode</label>
                                    <select class="form-control" id="payment_mode" name="payment_mode">
                                        <option value="cash">Cash</option>
                                        <option value="upi">UPI</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="cheque">Cheque</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="payment_reference">Payment Reference</label>
                            <input type="text" class="form-control" id="payment_reference" name="payment_reference" 
                                   placeholder="Transaction ID / Cheque No (Optional)">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2" 
                                  placeholder="Any special notes (optional)"><?= set_value('remarks') ?></textarea>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Create Account
                    </button>
                    <a href="<?= site_url('admin/savings') ?>" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Calculation Preview -->
    <div class="col-md-4">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calculator mr-1"></i> Savings Projection</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td>Monthly Deposit:</td>
                        <td class="text-right font-weight-bold" id="calcMonthly">₹0</td>
                    </tr>
                    <tr>
                        <td>Duration:</td>
                        <td class="text-right" id="calcDuration">-</td>
                    </tr>
                    <tr>
                        <td>Interest Rate:</td>
                        <td class="text-right" id="calcInterest">-</td>
                    </tr>
                    <tr class="border-top">
                        <td>Total Deposits:</td>
                        <td class="text-right" id="calcTotal">₹0</td>
                    </tr>
                    <tr>
                        <td>Expected Interest:</td>
                        <td class="text-right text-success" id="calcInterestAmt">₹0</td>
                    </tr>
                    <tr class="border-top">
                        <td><strong>Maturity Amount:</strong></td>
                        <td class="text-right font-weight-bold text-primary" id="calcMaturity">₹0</td>
                    </tr>
                    <tr>
                        <td>Maturity Date:</td>
                        <td class="text-right" id="calcMaturityDate">-</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Scheme Info Card -->
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Scheme Information</h3>
            </div>
            <div class="card-body" id="schemeDetails">
                <p class="text-muted text-center">Select a scheme to see details</p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
    
    // Member selection change
    $('#member_id').on('change', function() {
        var selected = $(this).find(':selected');
        if (selected.val()) {
            $('#memberInfo').removeClass('d-none');
            $('#infoName').text(selected.text().split(' - ')[1]);
            $('#infoPhone').text(selected.data('phone'));
            $('#infoKyc').html(selected.data('kyc') == '1' ? '<span class="badge badge-success">Verified</span>' : '<span class="badge badge-warning">Pending</span>');
            // Could load existing savings via AJAX
            $('#infoSavings').text('Loading...');
        } else {
            $('#memberInfo').addClass('d-none');
        }
    });
    
    // Scheme selection change
    $('#scheme_id').on('change', function() {
        var selected = $(this).find(':selected');
        if (selected.val()) {
            var min = selected.data('min');
            var max = selected.data('max');
            var interest = selected.data('interest');
            var duration = selected.data('duration');
            
            $('#amountRange').text('Min: ₹' + min.toLocaleString() + ' | Max: ₹' + (max || 'No limit').toLocaleString());
            $('#monthly_amount').attr('min', min).attr('max', max || 99999999);
            
            if (duration) {
                $('#duration_months').val(duration);
            }
            
            $('#schemeDetails').html(`
                <table class="table table-sm table-borderless">
                    <tr><td>Interest Rate:</td><td class="text-right"><strong>${interest}% p.a.</strong></td></tr>
                    <tr><td>Min Amount:</td><td class="text-right">₹${min.toLocaleString()}</td></tr>
                    <tr><td>Max Amount:</td><td class="text-right">₹${(max || 'No limit').toLocaleString()}</td></tr>
                    <tr><td>Default Duration:</td><td class="text-right">${duration ? duration + ' months' : 'Open-ended'}</td></tr>
                </table>
            `);
            
            updateCalculation();
        }
    });
    
    // Update calculation on input change
    $('#monthly_amount, #duration_months, #start_date').on('change input', updateCalculation);
    
    function updateCalculation() {
        var monthly = parseFloat($('#monthly_amount').val()) || 0;
        var duration = parseInt($('#duration_months').val()) || 12;
        var scheme = $('#scheme_id').find(':selected');
        var interest = parseFloat(scheme.data('interest')) || 0;
        var startDate = new Date($('#start_date').val());
        
        var totalDeposits = monthly * duration;
        // Simple interest calculation (approximate)
        var avgPrincipal = totalDeposits / 2;
        var interestAmt = Math.round((avgPrincipal * interest * (duration / 12)) / 100);
        var maturity = totalDeposits + interestAmt;
        
        var maturityDate = new Date(startDate);
        maturityDate.setMonth(maturityDate.getMonth() + duration);
        
        $('#calcMonthly').text('₹' + monthly.toLocaleString());
        $('#calcDuration').text(duration + ' months');
        $('#calcInterest').text(interest + '% p.a.');
        $('#calcTotal').text('₹' + totalDeposits.toLocaleString());
        $('#calcInterestAmt').text('₹' + interestAmt.toLocaleString() + ' (approx)');
        $('#calcMaturity').text('₹' + maturity.toLocaleString());
        $('#calcMaturityDate').text(maturityDate.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }));
    }
    
    // Toggle initial deposit section
    $('#collectNow').on('change', function() {
        if ($(this).is(':checked')) {
            $('#depositSection').removeClass('d-none');
            $('#deposit_amount').val($('#monthly_amount').val());
        } else {
            $('#depositSection').addClass('d-none');
        }
    });
    
    // Form validation
    $('#savingsForm').on('submit', function(e) {
        var memberId = $('#member_id').val();
        var schemeId = $('#scheme_id').val();
        var amount = $('#monthly_amount').val();
        
        if (!memberId || !schemeId || !amount) {
            e.preventDefault();
            Swal.fire('Error', 'Please fill all required fields', 'error');
            return false;
        }
        
        Swal.fire({
            title: 'Creating Account...',
            text: 'Please wait',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => { Swal.showLoading(); }
        });
    });
    
    // Trigger if member pre-selected
    if ($('#member_id').val()) {
        $('#member_id').trigger('change');
    }
});
</script>
