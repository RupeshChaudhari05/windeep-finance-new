<!-- Pending Approval Loans -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Pending Approval</h3>
        <div class="card-tools">
            <a href="<?= site_url('admin/loans') ?>" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Loans
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="pendingTable">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Application No</th>
                        <th>Member</th>
                        <th>Product</th>
                        <th class="text-right">Requested Amount</th>
                        <th>Tenure</th>
                        <th>Application Date</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">No pending applications</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($applications as $app): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td>
                                <a href="<?= site_url('admin/loans/view_application/' . $app->id) ?>" class="font-weight-bold">
                                    <?= $app->application_number ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= site_url('admin/members/view/' . $app->member_id) ?>">
                                    <?= $app->member_code ?> - <?= $app->first_name ?> <?= $app->last_name ?>
                                </a>
                                <br><small class="text-muted"><?= $app->phone ?></small>
                            </td>
                            <td><?= $app->product_name ?></td>
                            <td class="text-right font-weight-bold">₹<?= number_format($app->requested_amount, 2) ?></td>
                            <td><?= $app->requested_tenure_months ?> months</td>
                            <td><?= format_date($app->application_date) ?></td>
                            <td>
                                <?php
                                $status_badges = [
                                    'pending' => 'warning',
                                    'under_review' => 'info',
                                    'guarantor_pending' => 'secondary',
                                    'admin_approved' => 'primary',
                                    'member_approved' => 'success'
                                ];
                                $badge = $status_badges[$app->status] ?? 'secondary';
                                ?>
                                <span class="badge badge-<?= $badge ?>">
                                    <?= ucwords(str_replace('_', ' ', $app->status)) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= site_url('admin/loans/view_application/' . $app->id) ?>" class="btn btn-info btn-sm" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($app->status === 'pending'): ?>
                                    <a href="<?= site_url('admin/loans/approve/' . $app->id) ?>" class="btn btn-success btn-sm" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm btn-reject" data-id="<?= $app->id ?>" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php elseif ($app->status === 'member_approved'): ?>
                                    <a href="<?= site_url('admin/loans/disburse/' . $app->id) ?>" class="btn btn-primary btn-sm" title="Disburse">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mt-3">
    <div class="col-md-3">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Review</span>
                <span class="info-box-number"><?= $stats['pending'] ?? 0 ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-search"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Under Review</span>
                <span class="info-box-number"><?= $stats['under_review'] ?? 0 ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Admin Approved</span>
                <span class="info-box-number"><?= $stats['admin_approved'] ?? 0 ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-thumbs-up"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ready for Disbursement</span>
                <span class="info-box-number"><?= $stats['member_approved'] ?? 0 ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-times-circle mr-1"></i> Reject Application</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="rejectForm">
                <div class="modal-body">
                    <input type="hidden" name="application_id" id="rejectAppId">
                    <div class="form-group">
                        <label>Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#pendingTable').DataTable({
        "order": [[6, "asc"]],
        "pageLength": 25
    });
    
    // Reject button click
    $('.btn-reject').click(function() {
        $('#rejectAppId').val($(this).data('id'));
        $('#rejectModal').modal('show');
    });
    
    // Reject form submit
    $('#rejectForm').submit(function(e) {
        e.preventDefault();
        
        $.post('<?= site_url('admin/loans/reject') ?>/' + $('#rejectAppId').val(), {
            reason: $('[name="reason"]').val()
        }, function(response) {
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
