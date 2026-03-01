<?php
$csrfName = $this->security->get_csrf_token_name();
$csrfHash = $this->security->get_csrf_hash();
?>

<!-- Notification Stats Summary -->
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="info-box bg-gradient-info mb-0">
            <span class="info-box-icon"><i class="fas fa-bell"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total</span>
                <span class="info-box-number"><?= $total_count ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box bg-gradient-danger mb-0">
            <span class="info-box-icon"><i class="fas fa-envelope"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Unread</span>
                <span class="info-box-number"><?= $unread_count ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box bg-gradient-success mb-0">
            <span class="info-box-icon"><i class="fas fa-envelope-open"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Read</span>
                <span class="info-box-number"><?= $read_count ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box bg-gradient-warning mb-0">
            <span class="info-box-icon"><i class="fas fa-filter"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Current View</span>
                <span class="info-box-number"><?= ucfirst($filter) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Filter & Actions Bar -->
<div class="card card-outline card-primary mb-3">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="btn-group btn-group-sm" role="group">
                <a href="<?= site_url('admin/notifications') ?>" class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-primary' ?>">
                    <i class="fas fa-list mr-1"></i> All <span class="badge badge-light ml-1"><?= $total_count ?></span>
                </a>
                <a href="<?= site_url('admin/notifications?filter=unread') ?>" class="btn btn-<?= $filter === 'unread' ? 'danger' : 'outline-danger' ?>">
                    <i class="fas fa-envelope mr-1"></i> Unread <span class="badge badge-light ml-1"><?= $unread_count ?></span>
                </a>
                <a href="<?= site_url('admin/notifications?filter=read') ?>" class="btn btn-<?= $filter === 'read' ? 'success' : 'outline-success' ?>">
                    <i class="fas fa-envelope-open mr-1"></i> Read <span class="badge badge-light ml-1"><?= $read_count ?></span>
                </a>
            </div>
            
            <?php if ($unread_count > 0): ?>
            <button class="btn btn-sm btn-outline-info" id="markAllReadBtn">
                <i class="fas fa-check-double mr-1"></i> Mark All as Read
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Notifications List -->
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-bell mr-1"></i> Notifications</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($notifications)): ?>
            <div class="list-group list-group-flush" id="notificationsList">
                <?php foreach ($notifications as $n): ?>
                    <?php
                        $type = $n->notification_type ?? '';
                        $data_arr = is_array($n->data) ? $n->data : [];
                        
                        // Icon mapping based on notification type
                        $icon = 'fas fa-info-circle text-info';
                        $bgClass = '';
                        if (strpos($type, 'loan_application') !== false || strpos($type, 'loan_applied') !== false) {
                            $icon = 'fas fa-file-invoice-dollar text-primary';
                        } elseif (strpos($type, 'guarantor_accepted') !== false || strpos($type, 'consent_accepted') !== false) {
                            $icon = 'fas fa-handshake text-success';
                        } elseif (strpos($type, 'guarantor_rejected') !== false || strpos($type, 'consent_rejected') !== false) {
                            $icon = 'fas fa-handshake-slash text-danger';
                        } elseif (strpos($type, 'guarantor') !== false) {
                            $icon = 'fas fa-handshake text-warning';
                        } elseif (strpos($type, 'payment') !== false || strpos($type, 'collect') !== false) {
                            $icon = 'fas fa-rupee-sign text-success';
                        } elseif (strpos($type, 'overdue') !== false || strpos($type, 'reminder') !== false) {
                            $icon = 'fas fa-exclamation-triangle text-danger';
                        } elseif (strpos($type, 'disburs') !== false) {
                            $icon = 'fas fa-money-bill-wave text-success';
                        } elseif (strpos($type, 'fine') !== false) {
                            $icon = 'fas fa-gavel text-warning';
                        } elseif (strpos($type, 'savings') !== false) {
                            $icon = 'fas fa-piggy-bank text-success';
                        } elseif (strpos($type, 'member') !== false) {
                            $icon = 'fas fa-user text-primary';
                        } elseif (strpos($type, 'approved') !== false) {
                            $icon = 'fas fa-thumbs-up text-success';
                        } elseif (strpos($type, 'rejected') !== false) {
                            $icon = 'fas fa-thumbs-down text-danger';
                        } elseif (strpos($type, 'revision') !== false || strpos($type, 'modification') !== false) {
                            $icon = 'fas fa-edit text-warning';
                        }
                        
                        // Time ago
                        $created = strtotime($n->created_at);
                        $diff = time() - $created;
                        if ($diff < 60) $time_ago = 'Just now';
                        elseif ($diff < 3600) $time_ago = floor($diff/60) . 'm ago';
                        elseif ($diff < 86400) $time_ago = floor($diff/3600) . 'h ago';
                        elseif ($diff < 604800) $time_ago = floor($diff/86400) . 'd ago';
                        else $time_ago = date('d M Y', $created);
                    ?>
                    <div class="list-group-item list-group-item-action notification-item <?= $n->is_read ? '' : 'bg-light' ?>" 
                         data-id="<?= $n->id ?>"
                         style="<?= $n->is_read ? '' : 'border-left: 4px solid #007bff;' ?>">
                        <div class="d-flex align-items-start">
                            <!-- Icon -->
                            <div class="mr-3 mt-1">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 42px; height: 42px; background: <?= $n->is_read ? '#f0f0f0' : '#e3f2fd' ?>;">
                                    <i class="<?= $icon ?> fa-lg"></i>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 <?= $n->is_read ? 'text-muted' : 'font-weight-bold' ?>">
                                            <?= htmlspecialchars($n->title) ?>
                                        </h6>
                                        <p class="mb-1 text-sm <?= $n->is_read ? 'text-muted' : '' ?>" style="word-break: break-word;">
                                            <?= nl2br(htmlspecialchars($n->message)) ?>
                                        </p>
                                    </div>
                                    <div class="ml-3 text-right text-nowrap">
                                        <small class="text-muted d-block"><?= $time_ago ?></small>
                                        <small class="text-muted d-block"><?= date('h:i A', $created) ?></small>
                                    </div>
                                </div>
                                
                                <!-- Meta info (notification type badge + extra data) -->
                                <div class="mt-1 d-flex align-items-center flex-wrap">
                                    <span class="badge badge-pill badge-secondary mr-2" style="font-size: 0.7em;">
                                        <?= ucwords(str_replace('_', ' ', $type ?: 'general')) ?>
                                    </span>
                                    
                                    <?php if (!empty($data_arr['member_code'])): ?>
                                        <span class="badge badge-pill badge-outline-primary mr-2">
                                            <i class="fas fa-user mr-1"></i><?= $data_arr['member_code'] ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($data_arr['loan_number'])): ?>
                                        <span class="badge badge-pill badge-outline-warning mr-2">
                                            <i class="fas fa-file-invoice mr-1"></i><?= $data_arr['loan_number'] ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($data_arr['application_number']) || !empty($data_arr['application_id'])): ?>
                                        <a href="<?= site_url('admin/loans/view_application/' . ($data_arr['application_id'] ?? '')) ?>" class="badge badge-pill badge-outline-info mr-2">
                                            <i class="fas fa-eye mr-1"></i>View Application
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($data_arr['loan_id'])): ?>
                                        <a href="<?= site_url('admin/loans/view/' . $data_arr['loan_id']) ?>" class="badge badge-pill badge-outline-success mr-2">
                                            <i class="fas fa-eye mr-1"></i>View Loan
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($data_arr['member_id'])): ?>
                                        <a href="<?= site_url('admin/members/view/' . $data_arr['member_id']) ?>" class="badge badge-pill badge-outline-primary mr-2">
                                            <i class="fas fa-user mr-1"></i>View Member
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="ml-2 d-flex flex-column" style="min-width: 40px;">
                                <?php if (!$n->is_read): ?>
                                    <button class="btn btn-sm btn-outline-primary mb-1 btn-mark-read" data-id="<?= $n->id ?>" title="Mark as Read">
                                        <i class="fas fa-envelope-open"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="btn btn-sm btn-light mb-1 disabled" title="Read">
                                        <i class="fas fa-check text-success"></i>
                                    </span>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-danger btn-delete-notification" data-id="<?= $n->id ?>" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-bell-slash fa-4x text-muted mb-3 d-block"></i>
                <h5 class="text-muted">No notifications</h5>
                <p class="text-muted">
                    <?php if ($filter === 'unread'): ?>
                        You're all caught up! No unread notifications.
                    <?php elseif ($filter === 'read'): ?>
                        No read notifications found.
                    <?php else: ?>
                        No notifications yet. They'll appear here when there's activity.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(function() {
    var csrfName = '<?= $csrfName ?>';
    var csrfHash = '<?= $csrfHash ?>';
    
    // Mark single notification as read
    $(document).on('click', '.btn-mark-read', function(e) {
        e.stopPropagation();
        var btn = $(this);
        var id = btn.data('id');
        var item = btn.closest('.notification-item');
        
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.ajax({
            url: '<?= site_url("admin/notifications/mark_read/") ?>' + id,
            type: 'POST',
            data: { [csrfName]: csrfHash },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    item.removeClass('bg-light').css('border-left', '');
                    item.find('h6').removeClass('font-weight-bold').addClass('text-muted');
                    item.find('.rounded-circle').css('background', '#f0f0f0');
                    btn.replaceWith('<span class="btn btn-sm btn-light mb-1 disabled" title="Read"><i class="fas fa-check text-success"></i></span>');
                    toastr.success('Marked as read');
                    
                    // Update CSRF token if returned
                    if (res.csrf_hash) csrfHash = res.csrf_hash;
                } else {
                    btn.html('<i class="fas fa-envelope-open"></i>').prop('disabled', false);
                    toastr.error('Failed to mark as read');
                }
            },
            error: function() {
                btn.html('<i class="fas fa-envelope-open"></i>').prop('disabled', false);
                toastr.error('Server error');
            }
        });
    });
    
    // Mark all as read
    $('#markAllReadBtn').on('click', function() {
        var btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...').prop('disabled', true);
        
        $.ajax({
            url: '<?= site_url("admin/notifications/mark_all_read") ?>',
            type: 'POST',
            data: { [csrfName]: csrfHash },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    toastr.success(res.affected + ' notification(s) marked as read');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    btn.html('<i class="fas fa-check-double mr-1"></i> Mark All as Read').prop('disabled', false);
                    toastr.error('Failed');
                }
            },
            error: function() {
                btn.html('<i class="fas fa-check-double mr-1"></i> Mark All as Read').prop('disabled', false);
                toastr.error('Server error');
            }
        });
    });
    
    // Delete notification
    $(document).on('click', '.btn-delete-notification', function(e) {
        e.stopPropagation();
        var btn = $(this);
        var id = btn.data('id');
        var item = btn.closest('.notification-item');
        
        Swal.fire({
            title: 'Delete Notification?',
            text: 'This notification will be permanently removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Delete'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= site_url("admin/notifications/delete/") ?>' + id,
                    type: 'POST',
                    data: { [csrfName]: csrfHash },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            item.slideUp(300, function() { $(this).remove(); });
                            toastr.success('Notification deleted');
                        } else {
                            toastr.error('Failed to delete');
                        }
                    },
                    error: function() {
                        toastr.error('Server error');
                    }
                });
            }
        });
    });
});
</script>

<style>
.notification-item {
    transition: all 0.2s ease;
}
.notification-item:hover {
    background-color: #f8f9fa !important;
}
.badge-outline-primary {
    color: #007bff;
    border: 1px solid #007bff;
    background: transparent;
}
.badge-outline-warning {
    color: #856404;
    border: 1px solid #ffc107;
    background: transparent;
}
.badge-outline-info {
    color: #17a2b8;
    border: 1px solid #17a2b8;
    background: transparent;
}
.badge-outline-success {
    color: #28a745;
    border: 1px solid #28a745;
    background: transparent;
}
</style>
