<div class="card">
    <div class="card-header">
        <h3 class="card-title">My Fines</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-sm btn-info" onclick="refreshFines()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($fines)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No fines found. You're all clear!
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Fine ID</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fines as $fine): ?>
                        <tr>
                            <td><?= $fine->id ?></td>
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
                            <td class="text-right">₹<?= number_format($fine->amount, 2) ?></td>
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
                                <span class="badge <?= $status_class ?>"><?= ucfirst($fine->status) ?></span>
                            </td>
                            <td>
                                <?php
                                $due_date = new DateTime($fine->due_date);
                                $today = new DateTime();
                                $is_overdue = $due_date < $today && $fine->status === 'pending';
                                ?>
                                <span class="<?= $is_overdue ? 'text-danger' : '' ?>">
                                    <?= $due_date->format('d/m/Y') ?>
                                    <?php if ($is_overdue): ?>
                                        <small class="text-danger">(Overdue)</small>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-xs">
                                    <a href="<?= site_url('member/fines/view/' . $fine->id) ?>"
                                       class="btn btn-xs btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if ($fine->status === 'pending'): ?>
                                        <button type="button"
                                                class="btn btn-xs btn-warning"
                                                onclick="requestWaiver(<?= $fine->id ?>)">
                                            <i class="fas fa-hand-paper"></i> Request Waiver
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Waiver Request Modal -->
<div class="modal fade" id="waiverModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Fine Waiver</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="waiverForm">
                <div class="modal-body">
                    <input type="hidden" id="waiver_fine_id" name="fine_id">
                    <div class="form-group">
                        <label for="waiver_reason">Reason for Waiver Request</label>
                        <textarea class="form-control" id="waiver_reason" name="reason"
                                  rows="4" placeholder="Please explain why you are requesting a waiver for this fine..."
                                  required></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" type="checkbox"
                                   id="waiver_acknowledgement" name="acknowledgement" required>
                            <label class="custom-control-label" for="waiver_acknowledgement">
                                I understand that waiver requests are subject to approval and may not be granted.
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function refreshFines() {
    location.reload();
}

function requestWaiver(fineId) {
    $('#waiver_fine_id').val(fineId);
    $('#waiverModal').modal('show');
}

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
                $('#waiverModal').modal('hide');
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