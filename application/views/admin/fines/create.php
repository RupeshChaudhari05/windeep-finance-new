<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-gavel mr-1"></i> Create Manual Fine</h3>
            </div>
            <form action="<?= site_url('admin/fines/store') ?>" method="post" id="fineForm">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                
                <div class="card-body">
                    <!-- Member Selection -->
                    <div class="form-group">
                        <label for="member_id">Select Member <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="member_id" name="member_id" required data-placeholder="Search by name or code...">
                            <option value="">Select Member</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= $m->id ?>"><?= $m->member_code ?> - <?= $m->first_name ?> <?= $m->last_name ?> (<?= $m->phone ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <span class="text-danger"><?= form_error('member_id') ?></span>
                    </div>
                    
                    <!-- Fine Type -->
                    <div class="form-group">
                        <label for="fine_type">Fine Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="fine_type" name="fine_type" required>
                            <option value="">Select Type</option>
                            <option value="manual">Manual Fine</option>
                            <option value="meeting_absence">Meeting Absence</option>
                            <option value="document_late">Document Submission Late</option>
                            <option value="bounced_cheque">Bounced Cheque</option>
                            <option value="other">Other</option>
                        </select>
                        <span class="text-danger"><?= form_error('fine_type') ?></span>
                    </div>
                    
                    <!-- Link to Loan/Savings (Optional) -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="loan_id">Link to Loan (Optional)</label>
                                <select class="form-control" id="loan_id" name="loan_id">
                                    <option value="">No Link</option>
                                    <!-- Will be populated via AJAX based on member -->
                                </select>
                                <small class="form-text text-muted">Select if fine is related to a loan</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="savings_account_id">Link to Savings (Optional)</label>
                                <select class="form-control" id="savings_account_id" name="savings_account_id">
                                    <option value="">No Link</option>
                                    <!-- Will be populated via AJAX based on member -->
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fine_amount">Fine Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="fine_amount" name="fine_amount" 
                                       value="<?= set_value('fine_amount') ?>" required min="1"
                                       placeholder="Enter fine amount">
                                <span class="text-danger"><?= form_error('fine_amount') ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fine_date">Fine Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fine_date" name="fine_date" 
                                       value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
                                <span class="text-danger"><?= form_error('fine_date') ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required
                                  placeholder="Provide detailed reason for the fine"><?= set_value('reason') ?></textarea>
                        <span class="text-danger"><?= form_error('reason') ?></span>
                    </div>
                    
                    <!-- Collect Now -->
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" class="custom-control-input" id="collectNow" name="collect_now" value="1">
                        <label class="custom-control-label" for="collectNow">
                            <strong>Collect Fine Amount Now</strong>
                        </label>
                    </div>
                    
                    <div id="paymentSection" class="d-none">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_amount">Payment Amount</label>
                                    <input type="number" class="form-control" id="payment_amount" name="payment_amount" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_mode">Payment Mode</label>
                                    <select class="form-control" id="payment_mode" name="payment_mode">
                                        <option value="cash">Cash</option>
                                        <option value="upi">UPI</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Create Fine
                    </button>
                    <a href="<?= site_url('admin/fines') ?>" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Quick Reference -->
    <div class="col-md-4">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Fine Rules Reference</h3>
            </div>
            <div class="card-body">
                <?php if (empty($fine_rules)): ?>
                    <p class="text-muted">No fine rules configured</p>
                <?php else: ?>
                    <table class="table table-sm table-borderless">
                        <?php foreach ($fine_rules ?? [] as $rule): ?>
                        <tr>
                            <td><?= ucfirst(str_replace('_', ' ', $rule->fine_type)) ?></td>
                            <td class="text-right">
                                <?php if ($rule->calculation_type == 'fixed'): ?>
                                    ₹<?= number_format($rule->amount) ?>
                                <?php else: ?>
                                    <?= $rule->amount ?>%
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
                
                <hr>
                
                <h6>Common Fine Amounts</h6>
                <div class="btn-group-vertical w-100">
                    <button type="button" class="btn btn-outline-secondary btn-sm preset-amount" data-amount="50">₹50</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm preset-amount" data-amount="100">₹100</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm preset-amount" data-amount="200">₹200</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm preset-amount" data-amount="500">₹500</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
    
    // Member change - load loans and savings
    $('#member_id').on('change', function() {
        var memberId = $(this).val();
        if (memberId) {
            // AJAX to load member's loans
            $.get('<?= site_url('admin/loans/get_member_loans') ?>/' + memberId, function(data) {
                var html = '<option value="">No Link</option>';
                $.each(data, function(i, loan) {
                    html += '<option value="' + loan.id + '">' + loan.loan_number + '</option>';
                });
                $('#loan_id').html(html);
            });
            
            // AJAX to load member's savings
            $.get('<?= site_url('admin/savings/get_member_accounts') ?>/' + memberId, function(data) {
                var html = '<option value="">No Link</option>';
                $.each(data, function(i, acc) {
                    html += '<option value="' + acc.id + '">' + acc.account_number + '</option>';
                });
                $('#savings_account_id').html(html);
            });
        }
    });
    
    // Preset amounts
    $('.preset-amount').on('click', function() {
        $('#fine_amount').val($(this).data('amount'));
    });
    
    // Toggle payment section
    $('#collectNow').on('change', function() {
        if ($(this).is(':checked')) {
            $('#paymentSection').removeClass('d-none');
            $('#payment_amount').val($('#fine_amount').val());
        } else {
            $('#paymentSection').addClass('d-none');
        }
    });
    
    // Sync payment amount with fine amount
    $('#fine_amount').on('input', function() {
        if ($('#collectNow').is(':checked')) {
            $('#payment_amount').val($(this).val());
        }
    });
    
    // Form validation
    $('#fineForm').on('submit', function(e) {
        var memberId = $('#member_id').val();
        var amount = $('#fine_amount').val();
        var reason = $('#reason').val();
        
        if (!memberId || !amount || !reason) {
            e.preventDefault();
            Swal.fire('Error', 'Please fill all required fields', 'error');
            return false;
        }
        
        Swal.fire({
            title: 'Creating Fine...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => { Swal.showLoading(); }
        });
    });
});
</script>
