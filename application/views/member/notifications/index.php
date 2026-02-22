<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bell mr-1"></i> Notifications</h3>
        <?php
            $unread = 0;
            if (!empty($notifications)) {
                foreach ($notifications as $_n) { if (empty($_n->is_read)) $unread++; }
            }
        ?>
        <?php if ($unread > 0): ?>
            <span class="badge badge-danger ml-2"><?= $unread ?> unread</span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($notifications)): ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($notifications as $n): ?>
                    <?php
                        // Determine icon based on notification type
                        $type = isset($n->notification_type) ? $n->notification_type : '';
                        $icon = 'fas fa-info-circle text-info';
                        if (strpos($type, 'guarantor_request') !== false) $icon = 'fas fa-handshake text-warning';
                        elseif (strpos($type, 'guarantor_accepted') !== false) $icon = 'fas fa-check-circle text-success';
                        elseif (strpos($type, 'guarantor_rejected') !== false) $icon = 'fas fa-times-circle text-danger';
                        elseif (strpos($type, 'loan_approved') !== false) $icon = 'fas fa-thumbs-up text-success';
                        elseif (strpos($type, 'loan_rejected') !== false) $icon = 'fas fa-thumbs-down text-danger';
                        elseif (strpos($type, 'revision') !== false || strpos($type, 'modification') !== false) $icon = 'fas fa-edit text-warning';
                        elseif (strpos($type, 'disbursed') !== false) $icon = 'fas fa-money-bill-wave text-success';
                    ?>
                    <li class="list-group-item <?= $n->is_read ? '' : 'bg-light border-left border-primary' ?>" style="<?= $n->is_read ? '' : 'border-left: 3px solid #007bff;' ?>">
                        <div class="d-flex align-items-start">
                            <div class="mr-3 mt-1">
                                <i class="<?= $icon ?> fa-lg"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <strong><?= htmlspecialchars($n->title) ?></strong>
                                    <small class="text-muted"><?= date('d M Y h:i A', strtotime($n->created_at)) ?></small>
                                </div>
                                <div class="small mt-1" style="word-break: break-word;"><?= nl2br(htmlspecialchars($n->message)) ?></div>

                                <?php if ($type === 'guarantor_request' && !empty($n->data['guarantor_id'])): ?>
                                    <div class="mt-2" id="guarantor-actions-<?= $n->id ?>">
                                        <button class="btn btn-sm btn-success btn-guarantor-action" data-action="accept" data-gid="<?= $n->data['guarantor_id'] ?>" data-nid="<?= $n->id ?>">
                                            <i class="fas fa-check"></i> Accept
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-guarantor-action ml-1" data-action="reject" data-gid="<?= $n->data['guarantor_id'] ?>" data-nid="<?= $n->id ?>">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                        <?php if (!empty($n->data['url'])): ?>
                                            <a href="<?= $n->data['url'] ?>" class="btn btn-sm btn-outline-info ml-1"><i class="fas fa-external-link-alt"></i> View Details</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($n->data['application_id']) && $type !== 'guarantor_request'): ?>
                                    <div class="mt-1">
                                        <a href="<?= site_url('member/loans/application/' . $n->data['application_id']) ?>" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i> View Application
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="ml-2 text-right" style="min-width: 80px;">
                                <?php if (!$n->is_read): ?>
                                    <a href="<?= site_url('member/notifications/mark_read/' . $n->id) ?>" class="btn btn-sm btn-outline-secondary" title="Mark as read">
                                        <i class="fas fa-envelope-open"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="badge badge-light"><i class="fas fa-check"></i> Read</span>
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

<!-- Guarantor Reject Remarks Modal -->
<div class="modal fade" id="rejectRemarksModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Guarantor Request</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Reason for rejection (optional):</label>
                    <textarea class="form-control" id="rejectRemarks" rows="3" placeholder="Enter reason..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn"><i class="fas fa-times"></i> Confirm Reject</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function(){
    var pendingGid = null, pendingNid = null;

    // Handle Accept/Reject buttons
    $('.btn-guarantor-action').click(function(){
        var btn = $(this);
        var action = btn.data('action');
        var gid = btn.data('gid');
        var nid = btn.data('nid');

        if (action === 'reject') {
            pendingGid = gid;
            pendingNid = nid;
            $('#rejectRemarks').val('');
            $('#rejectRemarksModal').modal('show');
            return;
        }

        // Accept
        if (!confirm('Are you sure you want to accept this guarantor request?')) return;
        processGuarantorAction(gid, nid, 'accept', '', btn);
    });

    // Confirm reject from modal
    $('#confirmRejectBtn').click(function(){
        if (!pendingGid) return;
        var remarks = $('#rejectRemarks').val();
        $('#rejectRemarksModal').modal('hide');
        processGuarantorAction(pendingGid, pendingNid, 'reject', remarks, null);
    });

    function processGuarantorAction(gid, nid, action, remarks, btn) {
        var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
        var csrfHash = '<?= $this->security->get_csrf_hash() ?>';
        var postData = { action: action, remarks: remarks };
        postData[csrfName] = csrfHash;

        $.ajax({
            url: '<?= site_url('member/loans/guarantor_consent/') ?>' + gid,
            type: 'POST',
            data: postData,
            dataType: 'json',
            beforeSend: function() {
                $('#guarantor-actions-' + nid).html('<span class="text-muted"><i class="fas fa-spinner fa-spin"></i> Processing...</span>');
            },
            success: function(resp) {
                if (resp.success) {
                    var label = action === 'accept' 
                        ? '<span class="badge badge-success"><i class="fas fa-check"></i> Accepted</span>'
                        : '<span class="badge badge-danger"><i class="fas fa-times"></i> Rejected</span>';
                    $('#guarantor-actions-' + nid).html(label);
                    // Also mark notification read
                    $.get('<?= site_url('member/notifications/mark_read_ajax/') ?>' + nid);
                } else {
                    $('#guarantor-actions-' + nid).html('<span class="text-danger">' + (resp.message || 'Error occurred') + '</span>');
                }
            },
            error: function() {
                $('#guarantor-actions-' + nid).html('<span class="text-danger">Request failed. Please try again.</span>');
            }
        });
    }
});
</script>