<div class="row">
    <!-- Summary Cards -->
    <div class="col-md-4">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">EMIs Due Today</span>
                <span class="info-box-number"><?= $total_count ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-rupee-sign"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Amount Due</span>
                <span class="info-box-number"><?= format_amount($total_amount) ?></span>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="info-box bg-danger">
            <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Collection</span>
                <span class="info-box-number"><?= format_amount($total_pending) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title">
                    <i class="fas fa-calendar-day mr-2"></i>
                    <strong>EMI Due Today</strong> - <?= format_date(date('Y-m-d'), 'd M Y (l)') ?>
                </h3>
                <div class="card-tools">
                    <a href="<?= site_url('admin/installments/collection_sheet?date=' . date('Y-m-d')) ?>" class="btn btn-sm btn-light">
                        <i class="fas fa-print mr-1"></i> Print Collection Sheet
                    </a>
                </div>
            </div>
            
            <div class="card-body p-0">
                <?php if (empty($installments)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h5>No EMI Due Today!</h5>
                        <p class="text-muted">All caught up for today</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Member</th>
                                    <th>Loan Number</th>
                                    <th>Product</th>
                                    <th>EMI No.</th>
                                    <th class="text-right">EMI Amount</th>
                                    <th class="text-right">Paid</th>
                                    <th class="text-right">Balance</th>
                                    <th>Phone</th>
                                    <th width="150" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $sr = 1; foreach ($installments as $inst): ?>
                                <?php $balance = $inst->emi_amount - $inst->total_paid; ?>
                                <tr>
                                    <td><?= $sr++ ?></td>
                                    <td>
                                        <a href="<?= site_url('admin/members/view/' . $inst->member_id) ?>">
                                            <strong><?= $inst->first_name ?> <?= $inst->last_name ?></strong>
                                        </a>
                                        <br><small class="text-muted"><?= $inst->member_code ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('admin/loans/view/' . $inst->loan_id) ?>">
                                            <?= $inst->loan_number ?>
                                        </a>
                                    </td>
                                    <td><span class="badge badge-info"><?= $inst->product_name ?></span></td>
                                    <td class="text-center"><strong>#<?= $inst->installment_number ?></strong></td>
                                    <td class="text-right font-weight-bold"><?= format_amount($inst->emi_amount) ?></td>
                                    <td class="text-right text-success"><?= format_amount($inst->total_paid) ?></td>
                                    <td class="text-right">
                                        <strong class="text-danger"><?= format_amount($balance) ?></strong>
                                    </td>
                                    <td><a href="tel:<?= $inst->phone ?>"><?= $inst->phone ?></a></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= site_url('admin/loans/collect/' . $inst->loan_id . '?installment_id=' . $inst->id) ?>" class="btn btn-success" title="Collect Payment">
                                                <i class="fas fa-rupee-sign"></i> Collect
                                            </a>
                                            <button type="button" class="btn btn-info" onclick="sendReminder(<?= $inst->id ?>, '<?= $inst->phone ?>')" title="Send Reminder">
                                                <i class="fas fa-sms"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <th colspan="5" class="text-right">Total:</th>
                                    <th class="text-right"><?= format_amount($total_amount) ?></th>
                                    <th class="text-right text-success"><?= format_amount($total_collected) ?></th>
                                    <th class="text-right text-danger"><?= format_amount($total_pending) ?></th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function sendReminder(installmentId, phone) {
    if (!confirm('Send payment reminder to ' + phone + '?')) return;
    
    $.ajax({
        url: '<?= site_url('admin/installments/send_reminder') ?>',
        method: 'POST',
        data: {
            installment_id: installmentId,
            <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        }
    });
}
</script>
