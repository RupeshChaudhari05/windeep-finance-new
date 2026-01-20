<div class="card">
    <div class="card-header"><h3 class="card-title">Notifications</h3></div>
    <div class="card-body">
        <style>
            .notification-message { 
                display: -webkit-box; 
                -webkit-line-clamp: 2; 
                -webkit-box-orient: vertical; 
                overflow: hidden; 
                word-break: break-word;
            }
        </style>
        <?php if (!empty($notifications)): ?>
            <ul class="list-group">
                <?php foreach ($notifications as $n): ?>
                    <li class="list-group-item <?= $n->is_read ? '' : 'font-weight-bold' ?>">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1 pr-2">
                                <div class="font-weight-bold text-truncate"><?= $n->title ?></div>
                                <div class="small text-muted notification-message"><?= $n->message ?></div>
                                <div class="small text-muted mt-1"><?= $n->created_at ?></div>
                            </div>
                            <div class="text-right ml-2">
                                <?php if (isset($n->notification_type) && $n->notification_type === 'guarantor_request' && !empty($n->data['guarantor_id'])): ?>
                                    <div class="btn-group btn-group-sm mb-1" role="group">
                                        <button class="btn btn-success btn-accept-guarantor" data-gid="<?= $n->data['guarantor_id'] ?>" data-nid="<?= $n->id ?>">Accept</button>
                                        <button class="btn btn-danger btn-reject-guarantor" data-gid="<?= $n->data['guarantor_id'] ?>" data-nid="<?= $n->id ?>">Reject</button>
                                    </div>
                                <?php endif; ?>
                                <?php if (!$n->is_read): ?>
                                    <div><a href="<?= site_url('member/notifications/mark_read/' . $n->id) ?>" class="btn btn-sm btn-outline-primary">Mark read</a></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>

                <script>
                $(function(){
                    $('.btn-accept-guarantor').click(function(){
                        var gid = $(this).data('gid');
                        var nid = $(this).data('nid');
                        if (!gid) return;
                        window.openGuarantorModal && window.openGuarantorModal('accept', gid, nid);
                    });

                    $('.btn-reject-guarantor').click(function(){
                        var gid = $(this).data('gid');
                        var nid = $(this).data('nid');
                        if (!gid) return;
                        window.openGuarantorModal && window.openGuarantorModal('reject', gid, nid);
                    });
                });
                </script>
            </ul>
        <?php else: ?>
            <div class="alert alert-info">No notifications</div>
        <?php endif; ?>
    </div>
</div>