<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-gavel mr-2"></i>Foreclosure Requests</h3>
                </div>
                <div class="card-body p-0">
                    <!-- Status Counts -->
                    <div class="row p-3 bg-light border-bottom">
                        <div class="col-md-4">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Pending</span>
                                    <span class="info-box-number"><?= $status_counts['pending'] ?? 0 ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Approved</span>
                                    <span class="info-box-number"><?= $status_counts['approved'] ?? 0 ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-times"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Rejected</span>
                                    <span class="info-box-number"><?= $status_counts['rejected'] ?? 0 ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">All Foreclosure Requests</h3>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($requests)): ?>
                        <div class="alert alert-info m-3">
                            <i class="fas fa-info-circle"></i> No foreclosure requests found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Loan Number</th>
                                        <th>Member</th>
                                        <th>Settlement Amount</th>
                                        <th>Requested Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td><strong><?= $req->id ?></strong></td>
                                        <td>
                                            <a href="<?= site_url('admin/loans/view/' . $req->loan_id) ?>" class="text-primary font-weight-bold">
                                                <?= $req->loan_number ?>
                                            </a>
                                        </td>
                                        <td>
                                            <small>
                                                <strong><?= $req->member_code ?></strong><br>
                                                <?= $req->first_name ?> <?= $req->last_name ?>
                                            </small>
                                        </td>
                                        <td class="text-right">
                                            <strong>₹<?= number_format((float)$req->foreclosure_amount, 2) ?></strong>
                                        </td>
                                        <td>
                                            <small><?= date('d-M-Y H:i', strtotime($req->requested_at)) ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = 'badge-secondary';
                                            if ($req->status === 'pending') $badge_class = 'badge-warning';
                                            elseif ($req->status === 'approved') $badge_class = 'badge-success';
                                            elseif ($req->status === 'rejected') $badge_class = 'badge-danger';
                                            ?>
                                            <span class="badge <?= $badge_class ?> text-uppercase">
                                                <?= $req->status ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= site_url('admin/loans/view_foreclosure_request/' . $req->id) ?>" 
                                               class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($req->status === 'pending'): ?>
                                                <button class="btn btn-sm btn-success process-forecast-btn" 
                                                        data-id="<?= $req->id ?>" data-action="approve" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger process-forecast-btn" 
                                                        data-id="<?= $req->id ?>" data-action="reject" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Process Modal -->
<div class="modal fade" id="processModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Process Foreclosure Request</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="processForm">
                    <input type="hidden" id="requestId" name="request_id">
                    <input type="hidden" id="actionField" name="action">

                    <div class="form-group">
                        <label for="remarksField"><strong>Remarks / Comments</strong></label>
                        <textarea class="form-control" id="remarksField" name="remarks" rows="4" 
                                  placeholder="Enter your remarks (required)"></textarea>
                        <small class="form-text text-muted">Explain your decision</small>
                    </div>

                    <div class="alert alert-info" id="actionInfo">
                        <strong>Action:</strong> <span id="actionText"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Process Forecast Button
    $('.process-forecast-btn').click(function() {
        var requestId = $(this).data('id');
        var action = $(this).data('action');

        $('#requestId').val(requestId);
        $('#actionField').val(action);
        $('#actionText').text(action.charAt(0).toUpperCase() + action.slice(1));
        $('#modalTitle').text('Process Foreclosure Request - ' + (action === 'approve' ? 'Approve' : 'Reject'));
        
        if (action === 'approve') {
            $('#confirmBtn').removeClass('btn-danger').addClass('btn-success').text('Approve');
        } else {
            $('#confirmBtn').removeClass('btn-success').addClass('btn-danger').text('Reject');
        }

        $('#processModal').modal('show');
    });

    // Confirm Process
    $('#confirmBtn').click(function() {
        var remarks = $('#remarksField').val().trim();
        
        if (!remarks) {
            alert('Please enter remarks');
            return;
        }

        var formData = $('#processForm').serialize();

        $.ajax({
            url: '<?= site_url("admin/loans/process_foreclosure_request") ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#confirmBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(function() {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            location.reload();
                        }
                    }, 1500);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Server error. Please try again.');
            },
            complete: function() {
                $('#confirmBtn').prop('disabled', false).text('Confirm');
                $('#processModal').modal('hide');
            }
        });
    });

    function showAlert(type, message) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                        message +
                        '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                        '</div>';
        $('body').prepend(alertHtml);
        setTimeout(function() {
            $('.alert').fadeOut(function() { $(this).remove(); });
        }, 3000);
    }
});
</script>

<style>
    .info-box {
        padding: 15px;
        border-radius: 5px;
        color: white;
        text-align: center;
    }
    .info-box-icon {
        font-size: 30px;
        margin-right: 10px;
    }
    .info-box-text {
        font-size: 14px;
    }
    .info-box-number {
        font-size: 24px;
        font-weight: bold;
    }
</style>
