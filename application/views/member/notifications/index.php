<?php
$csrfName = $this->security->get_csrf_token_name();
$csrfHash = $this->security->get_csrf_hash();
$unread = 0;
if (!empty($notifications)) {
    foreach ($notifications as $_n) { if (empty($_n->is_read)) $unread++; }
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bell mr-1"></i> Notifications</h3>
        <?php if ($unread > 0): ?>
            <span class="badge badge-danger ml-2"><?= $unread ?> unread</span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($notifications)): ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($notifications as $n): ?>
                    <?php
                        $type = $n->notification_type ?? '';
                        $data = is_array($n->data) ? $n->data : [];
                        $icon = 'fas fa-info-circle text-info';
                        if (strpos($type, 'guarantor_request')   !== false) $icon = 'fas fa-handshake text-warning';
                        elseif (strpos($type, 'guarantor_accepted')  !== false) $icon = 'fas fa-check-circle text-success';
                        elseif (strpos($type, 'guarantor_rejected')  !== false) $icon = 'fas fa-times-circle text-danger';
                        elseif ($type === 'loan_admin_approved')              $icon = 'fas fa-thumbs-up text-success';
                        elseif (strpos($type, 'loan_approved')    !== false)  $icon = 'fas fa-thumbs-up text-success';
                        elseif (strpos($type, 'loan_rejected')    !== false)  $icon = 'fas fa-thumbs-down text-danger';
                        elseif (strpos($type, 'revision') !== false || strpos($type, 'modification') !== false) $icon = 'fas fa-edit text-warning';
                        elseif (strpos($type, 'disbursed') !== false) $icon = 'fas fa-money-bill-wave text-success';

                        $gid   = !empty($data['guarantor_id'])   ? (int)$data['guarantor_id']   : 0;
                        $appId = !empty($data['application_id']) ? (int)$data['application_id'] : 0;
                        // Only show Accept/Reject if the guarantor hasn't responded yet
                        $consentStatus      = $n->guarantor_consent_status ?? 'pending';
                        $isGuarantorRequest = ($type === 'guarantor_request' && $gid > 0 && $consentStatus === 'pending');
                        $isAdminApproved    = ($type === 'loan_admin_approved' && $appId > 0);
                    ?>
                    <li class="list-group-item <?= $n->is_read ? '' : 'bg-light' ?>"
                        style="<?= $n->is_read ? '' : 'border-left: 3px solid #007bff;' ?>">
                        <div class="d-flex align-items-start">
                            <div class="mr-3 mt-1">
                                <i class="<?= $icon ?> fa-lg"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <strong><?= htmlspecialchars($n->title) ?></strong>
                                    <small class="text-muted ml-2 text-nowrap"><?= date('d M Y h:i A', strtotime($n->created_at)) ?></small>
                                </div>
                                <div class="small mt-1" style="word-break: break-word;"><?= nl2br(htmlspecialchars($n->message)) ?></div>

                                <div class="mt-2">
                                    <?php if ($isGuarantorRequest): ?>
                                        <!-- Accept / Reject actions (only shown while still pending) -->
                                        <span id="guarantor-actions-<?= $n->id ?>">
                                            <button class="btn btn-sm btn-success btn-guarantor-action"
                                                    data-action="accept"
                                                    data-gid="<?= $gid ?>"
                                                    data-nid="<?= $n->id ?>">
                                                <i class="fas fa-check"></i> Accept
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-guarantor-action ml-1"
                                                    data-action="reject"
                                                    data-gid="<?= $gid ?>"
                                                    data-nid="<?= $n->id ?>">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                            <?php if ($appId): ?>
                                                <button class="btn btn-sm btn-outline-info ml-1 btn-view-loan"
                                                        data-app-id="<?= $appId ?>">
                                                    <i class="fas fa-eye"></i> View Details
                                                </button>
                                            <?php endif; ?>
                                        </span>
                                    <?php elseif ($type === 'guarantor_request' && $gid > 0 && $consentStatus !== 'pending'): ?>
                                        <!-- Already responded -->
                                        <?php if ($consentStatus === 'accepted'): ?>
                                            <span class="badge badge-success badge-pill px-3 py-2"><i class="fas fa-check mr-1"></i> You Accepted</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger badge-pill px-3 py-2"><i class="fas fa-times mr-1"></i> You Rejected</span>
                                        <?php endif; ?>
                                    <?php elseif ($isAdminApproved): ?>
                                        <!-- Admin approved: member must accept loan terms -->
                                        <a href="<?= site_url('member/loans/application/' . $appId) ?>"
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-check-circle"></i> Review &amp; Accept Loan Terms
                                        </a>
                                        <button class="btn btn-sm btn-outline-info ml-1 btn-view-loan"
                                                data-app-id="<?= $appId ?>">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                    <?php elseif ($appId): ?>
                                        <button class="btn btn-sm btn-outline-info btn-view-loan"
                                                data-app-id="<?= $appId ?>">
                                            <i class="fas fa-eye"></i> View Loan Details
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="ml-2 text-right" style="min-width: 70px;">
                                <?php if (!$n->is_read): ?>
                                    <a href="<?= site_url('member/notifications/mark_read/' . $n->id) ?>"
                                       class="btn btn-sm btn-outline-secondary" title="Mark as read">
                                        <i class="fas fa-envelope-open"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="badge badge-light"><i class="fas fa-check text-muted"></i> Read</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="text-center p-5">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <p class="text-muted">No notifications yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========== Reject Remarks Modal ========== -->
<div class="modal fade" id="rejectRemarksModal" tabindex="-1" role="dialog" aria-labelledby="rejectRemarksModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectRemarksModalLabel"><i class="fas fa-times-circle mr-1"></i> Reject Guarantor Request</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-0">
                    <label>Reason for rejection <span class="text-muted">(optional)</span>:</label>
                    <textarea class="form-control" id="rejectRemarks" rows="3" placeholder="Enter your reason..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn">
                    <i class="fas fa-times"></i> Confirm Reject
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========== Loan Detail Modal ========== -->
<div class="modal fade" id="loanDetailModal" tabindex="-1" role="dialog" aria-labelledby="loanDetailModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="loanDetailModalLabel">
                    <i class="fas fa-file-alt mr-1"></i> Loan Application Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="loanDetailBody">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    var pendingGid = null;
    var pendingNid = null;
    var csrfName  = '<?= $csrfName ?>';

    /* ---- helpers ---- */
    function freshCsrf() {
        // Re-fetch CSRF from a meta or return a stored value
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function getCsrfHash() {
        return $('input[name="' + csrfName + '"]').first().val() || '<?= $csrfHash ?>';
    }

    /* ---- Accept / Reject buttons ---- */
    $(document).on('click', '.btn-guarantor-action', function () {
        var btn    = $(this);
        var action = btn.data('action');
        var gid    = btn.data('gid');
        var nid    = btn.data('nid');

        if (action === 'reject') {
            pendingGid = gid;
            pendingNid = nid;
            $('#rejectRemarks').val('');
            $('#rejectRemarksModal').modal('show');
            return;
        }

        // Accept — ask confirmation
        Swal.fire({
            title: 'Accept Guarantor Request?',
            text: 'You are agreeing to act as a guarantor for this loan application.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, Accept'
        }).then(function (result) {
            if (result.isConfirmed) {
                processGuarantorAction(gid, nid, 'accept', '');
            }
        });
    });

    /* ---- Confirm Reject ---- */
    $('#confirmRejectBtn').on('click', function () {
        if (!pendingGid) return;
        var remarks = $('#rejectRemarks').val().trim();
        $('#rejectRemarksModal').modal('hide');
        processGuarantorAction(pendingGid, pendingNid, 'reject', remarks);
        pendingGid = null;
        pendingNid = null;
    });

    /* ---- Core AJAX guarantor action ---- */
    function processGuarantorAction(gid, nid, action, remarks) {
        var $area = $('#guarantor-actions-' + nid);
        $area.html('<span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Processing...</span>');

        var postData = { action: action, remarks: remarks };
        postData[csrfName] = getCsrfHash();

        $.ajax({
            url : '<?= site_url('member/loans/guarantor_consent/') ?>' + gid,
            type: 'POST',
            data: postData,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (resp) {
                if (resp && resp.success) {
                    var badge = action === 'accept'
                        ? '<span class="badge badge-success badge-pill px-3 py-2"><i class="fas fa-check mr-1"></i> You Accepted</span>'
                        : '<span class="badge badge-danger  badge-pill px-3 py-2"><i class="fas fa-times mr-1"></i> You Rejected</span>';
                    $area.html(badge);
                    // silently mark notification read (GET to avoid CSRF issues)
                    $.get('<?= site_url('member/notifications/mark_read_ajax/') ?>' + nid);
                } else {
                    $area.html('<span class="text-danger"><i class="fas fa-exclamation-circle"></i> ' +
                               (resp && resp.message ? resp.message : 'Action failed. Please try again.') + '</span>');
                }
            },
            error: function (xhr) {
                $area.html('<span class="text-danger"><i class="fas fa-exclamation-circle"></i> ' +
                           'Server error (' + xhr.status + '). Please try again.</span>');
            }
        });
    }

    /* ---- View Loan Details Modal ---- */
    $(document).on('click', '.btn-view-loan', function () {
        var appId = $(this).data('app-id');
        $('#loanDetailBody').html(
            '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="text-muted mt-2">Loading...</p></div>'
        );
        $('#loanDetailModal').modal('show');

        $.ajax({
            url : '<?= site_url('member/notifications/get_loan_detail/') ?>' + appId,
            type: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (d) {
                if (!d || !d.success) {
                    $('#loanDetailBody').html('<div class="alert alert-danger">' + (d ? d.message : 'Failed to load details.') + '</div>');
                    return;
                }
                var statusClass = {
                    'Pending': 'warning', 'Under Review': 'info', 'Admin Approved': 'primary',
                    'Disbursed': 'success', 'Rejected': 'danger', 'Cancelled': 'secondary',
                    'Expired': 'secondary'
                }[d.status] || 'secondary';

                $('#loanDetailBody').html(
                    '<div class="row">' +
                    '  <div class="col-md-6">' +
                    '    <table class="table table-sm table-borderless">' +
                    '      <tr><th class="text-muted" width="45%">Application No.</th><td><strong>' + d.application_number + '</strong></td></tr>' +
                    '      <tr><th class="text-muted">Applicant</th><td>' + d.applicant + '</td></tr>' +
                    '      <tr><th class="text-muted">Member Code</th><td>' + d.member_code + '</td></tr>' +
                    '      <tr><th class="text-muted">Phone</th><td>' + d.phone + '</td></tr>' +
                    '      <tr><th class="text-muted">Applied On</th><td>' + d.application_date + '</td></tr>' +
                    '      <tr><th class="text-muted">Expires On</th><td>' + d.expiry_date + '</td></tr>' +
                    '    </table>' +
                    '  </div>' +
                    '  <div class="col-md-6">' +
                    '    <table class="table table-sm table-borderless">' +
                    '      <tr><th class="text-muted" width="45%">Loan Product</th><td>' + d.product_name + '</td></tr>' +
                    '      <tr><th class="text-muted">Amount</th><td><strong class="text-primary">₹' + d.requested_amount + '</strong></td></tr>' +
                    '      <tr><th class="text-muted">Tenure</th><td>' + d.tenure + '</td></tr>' +
                    '      <tr><th class="text-muted">Interest Type</th><td>' + d.interest_type + '</td></tr>' +
                    '      <tr><th class="text-muted">Purpose</th><td>' + d.purpose + '</td></tr>' +
                    '      <tr><th class="text-muted">Status</th>' +
                    '          <td><span class="badge badge-' + statusClass + ' badge-pill">' + d.status + '</span></td></tr>' +
                    '    </table>' +
                    '  </div>' +
                    '</div>' +
                    (d.purpose_details ? '<hr><p class="small text-muted mb-0"><strong>Details:</strong> ' + d.purpose_details + '</p>' : '')
                );
            },
            error: function () {
                $('#loanDetailBody').html('<div class="alert alert-danger">Failed to load loan details. Please try again.</div>');
            }
        });
    });
});
</script>