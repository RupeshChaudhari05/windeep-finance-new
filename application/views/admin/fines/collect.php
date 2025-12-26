<!-- Collect Fine Payment -->
<div class="row">
    <div class="col-md-8">
        <!-- Fine Details -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-gavel mr-1"></i> Fine Details</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="text-muted">Fine Code:</td>
                                <td><strong><?= $fine->fine_code ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Fine Type:</td>
                                <td>
                                    <span class="badge badge-danger">
                                        <?= ucwords(str_replace('_', ' ', $fine->fine_type)) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Fine Date:</td>
                                <td><?= date('d M Y', strtotime($fine->fine_date)) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="text-muted">Total Fine:</td>
                                <td class="text-danger font-weight-bold">₹<?= number_format($fine->fine_amount, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Paid Amount:</td>
                                <td class="text-success">₹<?= number_format($fine->paid_amount ?? 0, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Balance Due:</td>
                                <?php $balance = $fine->fine_amount - ($fine->paid_amount ?? 0) - ($fine->waived_amount ?? 0); ?>
                                <td class="text-warning font-weight-bold">₹<?= number_format($balance, 2) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
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
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="text-muted">Phone:</td>
                                <td><a href="tel:<?= $member->phone ?>"><?= $member->phone ?></a></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Savings Balance:</td>
                                <td class="text-success">₹<?= number_format($member->savings_balance ?? 0, 2) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Payment Form -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-rupee-sign mr-1"></i> Collect Payment</h3>
            </div>
            <form method="post" action="<?= site_url('admin/fines/collect/' . $fine->id) ?>">
                <div class="card-body">
                    <div class="form-group">
                        <label>Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                            <input type="number" name="amount" id="amount" class="form-control" 
                                   value="<?= $balance ?>" max="<?= $balance ?>" step="0.01" required>
                        </div>
                        <small class="text-muted">Balance: ₹<?= number_format($balance, 2) ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Mode <span class="text-danger">*</span></label>
                        <select name="payment_mode" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="upi">UPI</option>
                            <option value="cheque">Cheque</option>
                            <option value="savings_deduction">Savings Deduction</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Reference Number</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="Transaction/Receipt No.">
                    </div>
                    
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block btn-lg">
                        <i class="fas fa-check mr-1"></i> Record Payment
                    </button>
                    <a href="<?= site_url('admin/fines/view/' . $fine->id) ?>" class="btn btn-default btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Quick Pay Buttons -->
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="text-muted">Quick Amount</h6>
                <div class="btn-group btn-group-sm d-flex flex-wrap">
                    <button type="button" class="btn btn-outline-primary quick-amount" data-amount="<?= $balance ?>">Full (₹<?= number_format($balance) ?>)</button>
                    <?php if ($balance >= 100): ?>
                    <button type="button" class="btn btn-outline-secondary quick-amount" data-amount="100">₹100</button>
                    <?php endif; ?>
                    <?php if ($balance >= 500): ?>
                    <button type="button" class="btn btn-outline-secondary quick-amount" data-amount="500">₹500</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.quick-amount').click(function() {
        $('#amount').val($(this).data('amount'));
    });
});
</script>
