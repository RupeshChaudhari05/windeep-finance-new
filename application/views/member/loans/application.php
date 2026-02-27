<div class="card">
    <div class="card-header">
        <h3 class="card-title">Application #<?= $application->application_number ?></h3>
        <div class="card-tools">
            <span class="badge badge-<?= $application->status == 'pending' ? 'warning' : ($application->status == 'needs_revision' ? 'info' : ($application->status == 'member_approved' ? 'success' : 'secondary')) ?> badge-lg">
                <?= strtoupper(str_replace('_', ' ', $application->status)) ?>
            </span>
        </div>
    </div>
    <div class="card-body">
        <p><strong>Product:</strong> <?= !empty($application->product_name) ? $application->product_name : '<span class="text-muted">Not assigned yet (admin will assign during approval)</span>' ?></p>
        <p><strong>Amount:</strong> <?= format_amount($application->requested_amount) ?></p>
        <p><strong>Tenure:</strong> <?= $application->requested_tenure_months ?> months</p>
        <p><strong>Purpose:</strong> <?= nl2br(htmlspecialchars($application->purpose)) ?></p>

        <?php if (!empty($application->revision_remarks)): ?>
            <hr>
            <div class="alert alert-info">
                <strong>Requested Changes:</strong>
                <div><?= nl2br(htmlspecialchars($application->revision_remarks)) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($guarantors)): ?>
            <h5>Guarantors</h5>
            <ul>
                <?php foreach ($guarantors as $g): ?>
                    <li><?= $g->member_code ?> - <?= htmlspecialchars($g->first_name . ' ' . $g->last_name) ?> (<?= format_amount($g->guarantee_amount) ?>) - <?= ucfirst($g->consent_status ?? 'pending') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($application->status === 'member_review'): ?>
            <hr>
            <div class="alert alert-success">
                <strong>Admin Approved Terms:</strong><br>
                Amount: <?= format_amount($application->approved_amount) ?><br>
                Tenure: <?= $application->approved_tenure_months ?> months<br>
                Interest Rate: <?= $application->approved_interest_rate ?>%
                <?php if (!empty($application->revision_remarks)): ?>
                    <br><strong>Remarks:</strong> <?= nl2br(htmlspecialchars($application->revision_remarks)) ?>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <button type="button" class="btn btn-success btn-block" data-toggle="modal" data-target="#approveModal">
                        <i class="fas fa-check"></i> Accept Terms
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#rejectModal">
                        <i class="fas fa-times"></i> Reject Terms
                    </button>
                </div>
            </div>

            <!-- Approve Modal -->
            <div class="modal fade" id="approveModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Approval</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to accept the admin-approved terms?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <a href="<?= site_url('member/loans/approve_application/' . $application->id) ?>" class="btn btn-success">Yes, Accept</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reject Modal -->
            <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reject Application</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <form action="<?= site_url('member/loans/reject_application/' . $application->id) ?>" method="post">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="reason">Reason for rejection:</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
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
        <?php elseif (in_array($application->status, ['pending','needs_revision','rejected'])): ?>
            <a href="<?= site_url('member/loans/edit_application/' . $application->id) ?>" class="btn btn-primary">Edit & Resubmit</a>
        <?php endif; ?>

        <a href="<?= site_url('member/loans/applications') ?>" class="btn btn-default">Back</a>
    </div>
</div>