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
    <!-- Toastr Notifications -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        /* Tooltip styling - larger, readable */
        .tooltip-inner { max-width: 300px; text-align: left; font-size: 13px; padding: 8px 12px; }
        /* Flash message styling */
        .flash-message { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    
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
        /* Suppress all transitions on initial load to prevent sidebar flicker */
        body.page-loading *,
        body.page-loading *::before,
        body.page-loading *::after {
            transition: none !important;
            animation: none !important;
        }
    </style>
    
    <?php if (isset($extra_css)): ?>
        <?= $extra_css ?>
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed page-loading">
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
            <li class="nav-item dropdown notif-dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" title="Notifications">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-danger navbar-badge member-notification-count" style="display:none;">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right notif-dropdown-menu">
                    <div class="notif-dropdown-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-bell mr-1"></i> Notifications</span>
                        <a href="#" class="btn-mark-all-read text-sm" title="Mark all as read"><i class="fas fa-check-double"></i></a>
                    </div>
                    <div class="member-notification-list notif-scroll-area">
                        <div class="notif-empty text-center py-4">
                            <i class="far fa-bell-slash fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No new notifications</p>
                        </div>
                    </div>
                    <div class="notif-dropdown-footer">
                        <a href="<?= site_url('member/notifications') ?>">View All Notifications <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
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
    /* ===== Notification Dropdown Styles (Member) ===== */
    .notif-dropdown-menu {
        width: 380px;
        max-width: 95vw;
        padding: 0;
        border: none;
        border-radius: 8px;
        box-shadow: 0 4px 25px rgba(0,0,0,.15);
        overflow: hidden;
    }
    .notif-dropdown-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: #fff;
        padding: 12px 16px;
        font-weight: 600;
        font-size: 14px;
    }
    .notif-dropdown-header .btn-mark-all-read {
        color: rgba(255,255,255,.8);
        text-decoration: none;
        font-weight: 400;
        transition: color .2s;
    }
    .notif-dropdown-header .btn-mark-all-read:hover { color: #fff; }
    .notif-scroll-area {
        max-height: 360px;
        overflow-y: auto;
        overscroll-behavior: contain;
    }
    .notif-scroll-area::-webkit-scrollbar { width: 5px; }
    .notif-scroll-area::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }

    .notif-item {
        display: flex;
        align-items: flex-start;
        padding: 12px 16px 12px 20px;
        border-bottom: 1px solid #f4f4f4;
        text-decoration: none;
        color: #333;
        transition: background .15s;
        cursor: pointer;
        position: relative;
    }
    .notif-item:hover { background: #f0f7ff; text-decoration: none; color: #333; }
    .notif-item.unread { background: #f8f9ff; }
    .notif-item.unread::before {
        content: '';
        position: absolute;
        left: 6px;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        background: #007bff;
        border-radius: 50%;
    }

    .notif-icon {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-right: 12px;
        font-size: 15px;
        color: #fff;
    }
    .notif-icon.bg-info    { background: #17a2b8 !important; }
    .notif-icon.bg-success { background: #28a745 !important; }
    .notif-icon.bg-warning { background: #ffc107 !important; color: #856404 !important; }
    .notif-icon.bg-danger  { background: #dc3545 !important; }
    .notif-icon.bg-primary { background: #007bff !important; }
    .notif-icon.bg-secondary { background: #6c757d !important; }

    .notif-content { flex: 1; min-width: 0; }
    .notif-title {
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .notif-msg {
        font-size: 12px;
        color: #6c757d;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.4;
        margin-bottom: 3px;
    }
    .notif-time {
        font-size: 11px;
        color: #adb5bd;
    }
    .notif-time i { margin-right: 3px; }

    .notif-dropdown-footer {
        text-align: center;
        padding: 10px;
        background: #f8f9fa;
        border-top: 1px solid #eee;
    }
    .notif-dropdown-footer a {
        color: #007bff;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
    }
    .notif-dropdown-footer a:hover { text-decoration: underline; }

    .notif-empty { padding: 30px 16px; }
    .notif-empty i { display: block; color: #ccc; }
    .notif-empty p { font-size: 13px; }

    .notif-actions { margin-left: 8px; flex-shrink: 0; }
    .notif-actions .btn { font-size: 11px; padding: 2px 8px; }

    .member-notification-count[data-active="true"] {
        animation: notif-pulse 2s ease-in-out infinite;
    }
    @keyframes notif-pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
</style>
<script>
$(function(){
    function notifTimeAgo(dateStr) {
        if (!dateStr) return '';
        var now = new Date();
        var past = new Date(dateStr);
        var diff = Math.floor((now - past) / 1000);
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
        return past.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' });
    }

    function notifIcon(type) {
        var map = {
            'loan_approved':      { icon: 'fas fa-check-circle',         bg: 'bg-success' },
            'loan_disbursed':     { icon: 'fas fa-money-bill-wave',      bg: 'bg-success' },
            'loan_rejected':      { icon: 'fas fa-times-circle',         bg: 'bg-danger' },
            'payment_received':   { icon: 'fas fa-hand-holding-usd',     bg: 'bg-info' },
            'payment_overdue':    { icon: 'fas fa-exclamation-triangle',  bg: 'bg-warning' },
            'guarantor_request':  { icon: 'fas fa-user-shield',          bg: 'bg-primary' },
            'savings':            { icon: 'fas fa-piggy-bank',           bg: 'bg-success' },
            'fine':               { icon: 'fas fa-gavel',                bg: 'bg-danger' },
            'system':             { icon: 'fas fa-cog',                  bg: 'bg-secondary' }
        };
        return map[type] || { icon: 'fas fa-bell', bg: 'bg-primary' };
    }

    // expose globally
    window.loadMemberNotifications = function(){
        $.getJSON('<?= site_url('member/dashboard/notifications') ?>', function(data){
            var list = $('.member-notification-list');
            var count = 0;
            list.empty();
            if (data && data.length) {
                data.forEach(function(n){
                    var ic = notifIcon(n.notification_type || n.type);
                    var unreadCls = n.is_read ? '' : ' unread';
                    var item = $('<div class="notif-item' + unreadCls + '" data-id="' + (n.id || '') + '"></div>');

                    // Icon
                    item.append('<div class="notif-icon ' + ic.bg + '"><i class="' + ic.icon + '"></i></div>');

                    // Content
                    var content = $('<div class="notif-content"></div>');
                    content.append($('<div class="notif-title"></div>').text(n.title || 'Notification'));
                    content.append($('<div class="notif-msg"></div>').text((n.message || '').substring(0, 120)));
                    content.append('<div class="notif-time"><i class="far fa-clock"></i>' + notifTimeAgo(n.created_at) + '</div>');
                    item.append(content);

                    // Action buttons for guarantor requests
                    if (n.notification_type === 'guarantor_request' && n.data && n.data.guarantor_id) {
                        var actions = $('<div class="notif-actions"></div>');
                        var accept = $('<button class="btn btn-success btn-sm btn-accept-guarantor mr-1" title="Accept"><i class="fas fa-check"></i></button>');
                        var reject = $('<button class="btn btn-danger btn-sm btn-reject-guarantor" title="Reject"><i class="fas fa-times"></i></button>');
                        accept.data('notif', n);
                        reject.data('notif', n);
                        actions.append(accept).append(reject);
                        item.append(actions);
                    }

                    list.append(item);
                    if (!n.is_read) count++;
                });
            } else {
                list.html('<div class="notif-empty text-center py-4">' +
                    '<i class="far fa-bell-slash fa-2x text-muted mb-2"></i>' +
                    '<p class="text-muted mb-0">No new notifications</p></div>');
            }
            var badge = $('.member-notification-count');
            if (count > 0) {
                badge.text(count > 99 ? '99+' : count).attr('data-active', 'true').show();
            } else {
                badge.text('0').attr('data-active', 'false').hide();
            }
        });
    };

    // Delegated handlers for accept/reject (use modal)
    $('.member-notification-list').on('click', '.btn-accept-guarantor', function(e){
        e.preventDefault();
        e.stopPropagation();
        var n = $(this).data('notif');
        var gid = n.data ? n.data.guarantor_id : null;
        var nid = n.id;
        if (!gid) return;
        window.openGuarantorModal && window.openGuarantorModal('accept', gid, nid);
    });

    $('.member-notification-list').on('click', '.btn-reject-guarantor', function(e){
        e.preventDefault();
        e.stopPropagation();
        var n = $(this).data('notif');
        var gid = n.data ? n.data.guarantor_id : null;
        var nid = n.id;
        if (!gid) return;
        window.openGuarantorModal && window.openGuarantorModal('reject', gid, nid);
    });

    window.loadMemberNotifications();
    setInterval(window.loadMemberNotifications, 60000);
});
</script>
    </nav>
    <!-- /.navbar -->

    <!-- /.navbar -->

