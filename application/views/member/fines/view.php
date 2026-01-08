<div class="card">
    <div class="card-header">
        <h3 class="card-title">Fine Details - #<?= $fine->id ?></h3>
        <div class="card-tools">
            <a href="<?= site_url('member/fines') ?>" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Fines
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th>Fine ID:</th>
                        <td><?= $fine->id ?></td>
                    </tr>
                    <tr>
                        <th>Type:</th>
                        <td>
                            <?php
                            $fine_types = [
                                'late_payment' => 'Late Payment',
                                'missed_installment' => 'Missed Installment',
                                'loan_default' => 'Loan Default',
                                'other' => 'Other'
                            ];
                            echo $fine_types[$fine->fine_type] ?? $fine->fine_type;
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Amount:</th>
                        <td class="text-success font-weight-bold">₹<?= number_format($fine->amount, 2) ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <?php
                            $status_classes = [
                                'pending' => 'badge-warning',
                                'paid' => 'badge-success',
                                'waived' => 'badge-info',
                                'cancelled' => 'badge-secondary'
                            ];
                            $status_class = $status_classes[$fine->status] ?? 'badge-secondary';
                            ?>
                            <span class="badge <?= $status_class ?> badge-lg"><?= ucfirst($fine->status) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th>Due Date:</th>
                        <td>
                            <?php
                            $due_date = new DateTime($fine->due_date);
                            $today = new DateTime();
                            $is_overdue = $due_date < $today && $fine->status === 'pending';
                            ?>
                            <span class="<?= $is_overdue ? 'text-danger font-weight-bold' : '' ?>">
                                <?= $due_date->format('d/m/Y') ?>
                                <?php if ($is_overdue): ?>
                                    <small class="text-danger">(Overdue)</small>
                                <?php endif; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Created:</th>
                        <td><?= date('d/m/Y H:i', strtotime($fine->created_at)) ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle"></i> Additional Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($fine->description)): ?>
                            <p><strong>Description:</strong> <?= $fine->description ?></p>
                        <?php endif; ?>

                        <?php if ($fine->loan_id): ?>
                            <p><strong>Related Loan:</strong>
                                <a href="<?= site_url('member/loans/view/' . $fine->loan_id) ?>" class="text-primary">
                                    Loan #<?= $fine->loan_number ?? $fine->loan_id ?>
                                </a>
                            </p>
                        <?php endif; ?>

                        <?php if ($fine->installment_id): ?>
                            <p><strong>Related Installment:</strong> #<?= $fine->installment_id ?></p>
                        <?php endif; ?>

                        <?php if ($fine->status === 'paid' && $fine->paid_at): ?>
                            <p><strong>Paid On:</strong> <?= date('d/m/Y H:i', strtotime($fine->paid_at)) ?></p>
                        <?php endif; ?>

                        <?php if ($fine->status === 'waived' && $fine->waived_at): ?>
                            <p><strong>Waived On:</strong> <?= date('d/m/Y H:i', strtotime($fine->waived_at)) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Waiver Request Section -->
        <?php if ($fine->status === 'pending'): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-hand-paper"></i> Request Waiver
                            </h6>
                        </div>
                        <div class="card-body">
                            <p>If you believe this fine was applied in error or you have valid reasons for requesting a waiver, you can submit a waiver request below.</p>

                            <form id="waiverForm">
                                <input type="hidden" name="fine_id" value="<?= $fine->id ?>">
                                <div class="form-group">
                                    <label for="waiver_reason">Reason for Waiver Request <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="waiver_reason" name="reason"
                                              rows="4" placeholder="Please explain in detail why you are requesting a waiver for this fine..."
                                              required></textarea>
                                    <small class="form-text text-muted">
                                        Provide specific details about why this fine should be waived. Include any relevant circumstances or documentation references.
                                    </small>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox"
                                               id="waiver_acknowledgement" name="acknowledgement" required>
                                        <label class="custom-control-label" for="waiver_acknowledgement">
                                            <strong>I acknowledge that:</strong> Waiver requests are subject to review and approval by management. Not all requests will be granted. False or misleading information may result in additional penalties.
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-paper-plane"></i> Submit Waiver Request
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Waiver Status Section -->
        <?php if (!empty($waiver_request)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-clock"></i> Waiver Request Status
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Request Date:</strong> <?= date('d/m/Y H:i', strtotime($waiver_request->requested_at)) ?></p>
                                    <p><strong>Status:</strong>
                                        <?php
                                        $waiver_status_classes = [
                                            'pending' => 'badge-warning',
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger'
                                        ];
                                        $waiver_status_class = $waiver_status_classes[$waiver_request->status] ?? 'badge-secondary';
                                        ?>
                                        <span class="badge <?= $waiver_status_class ?> badge-lg">
                                            <?= ucfirst($waiver_request->status) ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($waiver_request->reviewed_at): ?>
                                        <p><strong>Reviewed On:</strong> <?= date('d/m/Y H:i', strtotime($waiver_request->reviewed_at)) ?></p>
                                    <?php endif; ?>
                                    <?php if ($waiver_request->reviewed_by): ?>
                                        <p><strong>Reviewed By:</strong> <?= $waiver_request->reviewer_name ?? 'Administrator' ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-3">
                                <strong>Your Reason:</strong>
                                <div class="alert alert-light">
                                    <?= nl2br(htmlspecialchars($waiver_request->reason)) ?>
                                </div>
                            </div>

                            <?php if ($waiver_request->admin_comments): ?>
                                <div class="mt-3">
                                    <strong>Administrator Comments:</strong>
                                    <div class="alert alert-info">
                                        <?= nl2br(htmlspecialchars($waiver_request->admin_comments)) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($waiver_request->status === 'pending'): ?>
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-clock"></i> Your waiver request is being reviewed. You will be notified once a decision is made.
                                </div>
                            <?php elseif ($waiver_request->status === 'approved'): ?>
                                <div class="alert alert-success mt-3">
                                    <i class="fas fa-check-circle"></i> Your waiver request has been approved! The fine has been waived.
                                </div>
                            <?php elseif ($waiver_request->status === 'rejected'): ?>
                                <div class="alert alert-danger mt-3">
                                    <i class="fas fa-times-circle"></i> Your waiver request has been rejected. Please contact administration for more details.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$('#waiverForm').on('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    $.ajax({
        url: '<?= site_url('member/fines/request-waiver/') ?>' + formData.get('fine_id'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Waiver request submitted successfully!');
                setTimeout(() => location.reload(), 2000);
            } else {
                toastr.error(response.message || 'Failed to submit waiver request');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            toastr.error(response.message || 'An error occurred while submitting the request');
        }
    });
});
</script>