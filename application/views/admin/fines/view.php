<!-- View Fine Details -->
<div class="row">
    <div class="col-md-8">
        <!-- Fine Details -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-gavel mr-1"></i> Fine Details</h3>
                <div class="card-tools">
                    <span class="badge badge-<?= 
                        $fine->status == 'paid' ? 'success' : 
                        ($fine->status == 'waived' ? 'info' : 
                        ($fine->status == 'cancelled' ? 'secondary' : 
                        ($fine->status == 'partial' ? 'warning' : 'danger'))) 
                    ?> badge-lg">
                        <?= strtoupper($fine->status) ?>
                    </span>
                </div>
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
                                    <span class="badge badge-<?= 
                                        $fine->fine_type == 'late_payment' ? 'danger' : 
                                        ($fine->fine_type == 'meeting_absence' ? 'warning' : 'info') 
                                    ?>">
                                        <?= ucwords(str_replace('_', ' ', $fine->fine_type)) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Fine Date:</td>
                                <td><?= date('d M Y', strtotime($fine->fine_date)) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Related Rule:</td>
                                <td><?= $fine->rule_name ?: 'Manual Fine' ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="text-muted">Fine Amount:</td>
                                <td class="text-danger font-weight-bold">₹<?= number_format($fine->fine_amount, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Paid Amount:</td>
                                <td class="text-success">₹<?= number_format($fine->paid_amount ?? 0, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Waived Amount:</td>
                                <td>₹<?= number_format($fine->waived_amount ?? 0, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Balance Due:</td>
                                <td class="text-warning font-weight-bold">
                                    ₹<?= number_format($fine->fine_amount - ($fine->paid_amount ?? 0) - ($fine->waived_amount ?? 0), 2) ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($fine->remarks)): ?>
                <hr>
                <h6 class="text-muted">Remarks</h6>
                <p class="mb-0"><?= nl2br($fine->remarks) ?></p>
                <?php endif; ?>
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
                                <td><strong><?= $fine->member_code ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Name:</td>
                                <td><?= $fine->first_name ?> <?= $fine->last_name ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="text-muted">Phone:</td>
                                <td><a href="tel:<?= $fine->phone ?>"><?= $fine->phone ?></a></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-1"></i> Payment History</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th class="text-right">Amount</th>
                            <th>Mode</th>
                            <th>Reference</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $payments = $this->db->where('fine_id', $fine->id)
                                             ->order_by('created_at', 'DESC')
                                             ->get('fine_payments')
                                             ->result();
                        if (empty($payments)): 
                        ?>
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">No payments recorded</td>
                        </tr>
                        <?php else: foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= date('d M Y h:i A', strtotime($payment->created_at)) ?></td>
                            <td>
                                <span class="badge badge-<?= $payment->payment_type == 'waiver' ? 'info' : 'success' ?>">
                                    <?= ucfirst($payment->payment_type ?? 'payment') ?>
                                </span>
                            </td>
                            <td class="text-right">₹<?= number_format($payment->amount, 2) ?></td>
                            <td><?= ucfirst($payment->payment_mode ?? '-') ?></td>
                            <td><?= $payment->reference_number ?: '-' ?></td>
                            <td><?= $payment->created_by_name ?? '-' ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cogs mr-1"></i> Actions</h3>
            </div>
            <div class="card-body">
                <?php 
                $balance = $fine->fine_amount - ($fine->paid_amount ?? 0) - ($fine->waived_amount ?? 0);
                if ($balance > 0 && $fine->status != 'cancelled'): 
                ?>
                <a href="<?= site_url('admin/fines/collect/' . $fine->id) ?>" class="btn btn-success btn-block mb-2">
                    <i class="fas fa-rupee-sign mr-1"></i> Collect Payment
                </a>
                <button class="btn btn-warning btn-block mb-2" data-toggle="modal" data-target="#waiveModal">
                    <i class="fas fa-hand-holding-heart mr-1"></i> Waive Fine
                </button>
                <button class="btn btn-danger btn-block mb-2" data-toggle="modal" data-target="#cancelModal">
                    <i class="fas fa-times mr-1"></i> Cancel Fine
                </button>
                <?php endif; ?>
                
                <a href="<?= site_url('admin/members/view/' . $fine->member_id) ?>" class="btn btn-outline-secondary btn-block mb-2">
                    <i class="fas fa-user mr-1"></i> View Member
                </a>
                
                <a href="<?= site_url('admin/fines') ?>" class="btn btn-default btn-block">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Fines
                </a>
            </div>
        </div>
        
        <!-- Summary Card -->
        <div class="card bg-gradient-<?= $fine->status == 'paid' ? 'success' : ($balance > 0 ? 'danger' : 'info') ?>">
            <div class="card-body text-white">
                <h5 class="mb-3">Balance Summary</h5>
                <div class="d-flex justify-content-between">
                    <span>Fine Amount:</span>
                    <span>₹<?= number_format($fine->fine_amount, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Paid:</span>
                    <span>₹<?= number_format($fine->paid_amount ?? 0, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Waived:</span>
                    <span>₹<?= number_format($fine->waived_amount ?? 0, 2) ?></span>
                </div>
                <hr class="bg-white">
                <div class="d-flex justify-content-between">
                    <strong>Balance Due:</strong>
                    <strong>₹<?= number_format($balance, 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Waive Modal -->
<div class="modal fade" id="waiveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-hand-holding-heart mr-1"></i> Waive Fine</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Waive Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                        <input type="number" id="waive_amount" class="form-control" value="<?= $balance ?>" max="<?= $balance ?>" step="0.01">
                    </div>
                    <small class="text-muted">Max: ₹<?= number_format($balance, 2) ?></small>
                </div>
                <div class="form-group">
                    <label>Reason <span class="text-danger">*</span></label>
                    <textarea id="waive_reason" class="form-control" rows="3" placeholder="Enter reason for waiver" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmWaive">
                    <i class="fas fa-check mr-1"></i> Confirm Waiver
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-times mr-1"></i> Cancel Fine</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    This will cancel the entire fine. Any payments made will need to be refunded manually.
                </div>
                <div class="form-group">
                    <label>Cancellation Reason <span class="text-danger">*</span></label>
                    <textarea id="cancel_reason" class="form-control" rows="3" placeholder="Enter reason for cancellation" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Back</button>
                <button type="button" class="btn btn-danger" id="confirmCancel">
                    <i class="fas fa-times mr-1"></i> Cancel Fine
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Waive Fine
    $('#confirmWaive').click(function() {
        var amount = $('#waive_amount').val();
        var reason = $('#waive_reason').val().trim();
        
        if (!reason) {
            toastr.error('Please enter waiver reason');
            return;
        }
        
        $.post('<?= site_url('admin/fines/waive/' . $fine->id) ?>', {amount: amount, reason: reason}, function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message);
            }
        }, 'json');
    });
    
    // Cancel Fine
    $('#confirmCancel').click(function() {
        var reason = $('#cancel_reason').val().trim();
        
        if (!reason) {
            toastr.error('Please enter cancellation reason');
            return;
        }
        
        $.post('<?= site_url('admin/fines/cancel/' . $fine->id) ?>', {reason: reason}, function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message);
            }
        }, 'json');
    });
});
</script>
