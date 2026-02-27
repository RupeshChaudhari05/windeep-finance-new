<div class="row">
    <div class="col-md-4">
        <!-- Member Search -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-1"></i> Select Member</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Search Member</label>
                    <select class="form-control select2" id="memberSearch" style="width: 100%;">
                        <option value="">Type member code, name or phone...</option>
                        <?php foreach ($recent_members as $rm): ?>
                        <option value="<?= $rm->id ?>" <?= ($member && $member->id == $rm->id) ? 'selected' : '' ?>>
                            <?= $rm->member_code ?> - <?= $rm->first_name ?> <?= $rm->last_name ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($member): ?>
                <div class="alert alert-info">
                    <h6 class="mb-2"><strong><?= $member->member_code ?></strong></h6>
                    <p class="mb-1"><?= $member->first_name ?> <?= $member->last_name ?></p>
                    <p class="mb-0"><i class="fas fa-phone"></i> <?= $member->phone ?></p>
                </div>
                
                <!-- Member Dues Summary -->
                <?php if (!empty($member_dues)): ?>
                <div class="card bg-light mb-0">
                    <div class="card-header bg-warning">
                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> Pending Dues</h6>
                    </div>
                    <div class="card-body p-2">
                        <table class="table table-sm table-borderless mb-0">
                            <?php 
                            $total_dues = 0;
                            foreach ($member_dues as $due): 
                                $total_dues += $due['amount'];
                            ?>
                            <tr>
                                <td>
                                    <?php
                                    $icon = ['loan' => 'file-invoice-dollar', 'savings' => 'piggy-bank', 'fine' => 'gavel'];
                                    $color = ['loan' => 'primary', 'savings' => 'success', 'fine' => 'danger'];
                                    ?>
                                    <i class="fas fa-<?= $icon[$due['type']] ?> text-<?= $color[$due['type']] ?>"></i>
                                    <small><?= $due['description'] ?></small>
                                </td>
                                <td class="text-right"><strong><?= format_amount($due['amount']) ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="border-top">
                                <th>Total Due:</th>
                                <th class="text-right text-danger"><?= format_amount($total_dues) ?></th>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle mr-1"></i> No pending dues!
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Payment Form -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-rupee-sign mr-1"></i> Receive Payment</h3>
            </div>
            <form action="<?= site_url('admin/payments/receive') ?>" method="post" id="paymentForm">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                
                <div class="card-body">
                    <input type="hidden" name="member_id" id="hiddenMemberId" value="<?= $member->id ?? '' ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Type <span class="text-danger">*</span></label>
                                <select class="form-control" name="payment_type" id="paymentType" required <?= !$member ? 'disabled' : '' ?>>
                                    <option value="">Select Type</option>
                                    <option value="loan">Loan EMI</option>
                                    <option value="savings">Savings Deposit</option>
                                    <option value="fine">Fine Payment</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Related To <span class="text-danger">*</span></label>
                                <select class="form-control" name="related_id" id="relatedId" required <?= !$member ? 'disabled' : '' ?>>
                                    <option value="">Select payment type first</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><?= get_currency_symbol() ?></span></div>
                                    <input type="number" class="form-control" name="amount" id="amount" 
                                           min="1" step="0.01" required <?= !$member ? 'disabled' : '' ?>>
                                </div>
                                <small class="text-muted" id="amountHint"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="payment_date" 
                                       value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Mode <span class="text-danger">*</span></label>
                                <select class="form-control" name="payment_mode" id="paymentMode" required>
                                    <option value="cash">Cash</option>
                                    <option value="upi">UPI</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="card">Card</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reference Number</label>
                                <input type="text" class="form-control" name="reference_number" 
                                       placeholder="Transaction ID / Cheque No.">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea class="form-control" name="remarks" rows="2" 
                                  placeholder="Optional notes"></textarea>
                    </div>
                    
                    <?php if (!$member): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle mr-1"></i> Please select a member first to proceed with payment.
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-lg" <?= !$member ? 'disabled' : '' ?>>
                        <i class="fas fa-check mr-1"></i> Record Payment
                    </button>
                    <a href="<?= site_url('admin/payments/history') ?>" class="btn btn-default btn-lg">
                        <i class="fas fa-history mr-1"></i> View History
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('#memberSearch').select2({
        ajax: {
            url: '<?= site_url('admin/payments/search_members') ?>',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term };
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: 'Type to search...'
    });
    
    // On member selection
    $('#memberSearch').on('change', function() {
        const memberId = $(this).val();
        if (memberId) {
            window.location.href = '<?= site_url('admin/payments/receive') ?>?member_id=' + memberId;
        }
    });
    
    // Payment type change
    $('#paymentType').on('change', function() {
        const type = $(this).val();
        const memberId = $('#hiddenMemberId').val();
        
        if (!type || !memberId) return;
        
        // Load related items
        $.ajax({
            url: '<?= site_url('admin/payments/get_member_dues') ?>',
            data: { member_id: memberId },
            success: function(data) {
                const relatedSelect = $('#relatedId');
                relatedSelect.empty().append('<option value="">Select...</option>');
                
                data.forEach(function(due) {
                    if (due.type === type) {
                        relatedSelect.append(
                            $('<option></option>')
                                .val(due.id)
                                .text(due.description + ' - <?= get_currency_symbol() ?>' + parseFloat(due.amount).toFixed(2))
                                .data('amount', due.amount)
                        );
                    }
                });
            }
        });
    });
    
    // Auto-fill amount when related item selected
    $('#relatedId').on('change', function() {
        const amount = $(this).find(':selected').data('amount');
        if (amount) {
            $('#amount').val(parseFloat(amount).toFixed(2));
            $('#amountHint').text('Suggested: <?= get_currency_symbol() ?>' + parseFloat(amount).toFixed(2));
        }
    });
    
    // Show reference field based on payment mode
    $('#paymentMode').on('change', function() {
        const mode = $(this).val();
        if (mode === 'cash') {
            $('input[name="reference_number"]').prop('required', false).closest('.form-group').hide();
        } else {
            $('input[name="reference_number"]').prop('required', true).closest('.form-group').show();
        }
    });
});
</script>
