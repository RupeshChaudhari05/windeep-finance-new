<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $page_title ?? 'Member Portal' ?> - Windeep Finance</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <!-- jQuery (moved to header for immediate availability) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <style>
        /* Content-wrapper margin for proper sidebar spacing */
        .content-wrapper {
            min-height: calc(100vh - 57px);
            margin-left: 250px;
            transition: margin-left .2s ease-in-out;
        }
        body.sidebar-collapse .content-wrapper {
            margin-left: 80px;
        }
        @media (max-width: 767px) {
            .content-wrapper { margin-left: 0 !important; }
        }
    </style>
    
    <?php if (isset($extra_css)): ?>
        <?= $extra_css ?>
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?= site_url('member/dashboard') ?>" class="nav-link">Dashboard</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Notifications Dropdown for Member -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" title="Notifications">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge member-notification-count">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header">Notifications</span>
                    <div class="dropdown-divider"></div>
                    <div class="member-notification-list">
                        <a href="#" class="dropdown-item text-center text-muted"><small>No new notifications</small></a>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="<?= site_url('member/notifications') ?>" class="dropdown-item dropdown-footer">See All Notifications</a>
                </div>
            </li>

            <!-- User Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i>
                    <span class="d-none d-md-inline ml-1"><?= $member->first_name ?> <?= $member->last_name ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <a href="<?= site_url('member/profile') ?>" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?= site_url('member/profile/edit') ?>" class="dropdown-item">
                        <i class="fas fa-edit mr-2"></i> Edit Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?= site_url('member/logout') ?>" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>

<style>
    /* Compact notification styles */
    .notification-item .notification-message { 
        display: -webkit-box; 
        -webkit-line-clamp: 2; 
        -webkit-box-orient: vertical; 
        overflow: hidden; 
        word-break: break-word;
    }
    .notification-item { min-width: 320px; max-width: 520px; }
    .notification-actions { min-width: 110px; }
</style>
<script>
$(function(){
    // expose globally
    window.loadMemberNotifications = function(){
        $.getJSON('<?= site_url('member/dashboard/notifications') ?>', function(data){
            var list = $('.member-notification-list');
            var count = 0;
            list.empty();
            if (data && data.length) {
                data.forEach(function(n){
                    var item = $('<div class="dropdown-item notification-item d-flex align-items-start"></div>');
                    var content = $('<div class="mr-2 flex-grow-1 pr-1"></div>');
                    var title = $('<div class="font-weight-bold text-truncate"></div>').text(n.title);
                    var msg = $('<div class="small text-muted notification-message"></div>').html(n.message);
                    var time = $('<div class="small text-muted mt-1"></div>').text(n.created_at || '');
                    content.append(title).append(msg).append(time);

                    var actions = $('<div class="notification-actions d-flex flex-column align-items-end"></div>');
                    try {
                        if (n.notification_type === 'guarantor_request' && n.data && n.data.guarantor_id) {
                            var btnGroup = $('<div class="btn-group btn-group-sm" role="group"></div>');
                            var accept = $('<button class="btn btn-success btn-accept-guarantor" title="Accept">Accept</button>');
                            var reject = $('<button class="btn btn-danger btn-reject-guarantor" title="Reject">Reject</button>');
                            accept.data('notif', n); reject.data('notif', n);
                            btnGroup.append(accept).append(reject);
                            actions.append(btnGroup);
                        } else {
                            actions.append('<small class="text-muted">&nbsp;</small>');
                        }
                    } catch (e) {
                        actions.append('<small class="text-muted">&nbsp;</small>');
                    }

                    item.append(content).append(actions);
                    list.append(item);

                    if (!n.is_read) count++;
                });
            } else {
                list.append('<a href="#" class="dropdown-item text-center text-muted"><small>No new notifications</small></a>');
            }
            $('.member-notification-count').text(count);
        });
    };

    // Delegated handlers for accept/reject (use modal)
    $('.member-notification-list').on('click', '.btn-accept-guarantor', function(e){
        e.preventDefault();
        var n = $(this).data('notif');
        var gid = n.data ? n.data.guarantor_id : null;
        var nid = n.id;
        if (!gid) return;
        // Open modal in accept mode
        window.openGuarantorModal && window.openGuarantorModal('accept', gid, nid);
    });

    $('.member-notification-list').on('click', '.btn-reject-guarantor', function(e){
        e.preventDefault();
        var n = $(this).data('notif');
        var gid = n.data ? n.data.guarantor_id : null;
        var nid = n.id;
        if (!gid) return;
        // Open modal in reject mode
        window.openGuarantorModal && window.openGuarantorModal('reject', gid, nid);
    });

    window.loadMemberNotifications();
    // Optionally poll every 60s
    setInterval(window.loadMemberNotifications, 60000);
});
</script>
    </nav>
    <!-- /.navbar -->

    <!-- /.navbar -->

