            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
    
    <footer class="main-footer">
        <strong>Copyright &copy; <?= date('Y') ?> <a href="#">Windeep Finance</a>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
    </footer>

</div>
<!-- ./wrapper -->

<!-- jQuery UI 1.11.4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge('uibutton', $.ui.button);
</script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
<!-- Sparkline -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-sparklines/2.1.2/jquery.sparkline.min.js"></script>
<!-- daterangepicker -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.min.js"></script>
<!-- Bootstrap Datepicker -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Select2 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js"></script>
<!-- overlayScrollbars -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.13.1/js/jquery.overlayScrollbars.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.4.8/sweetalert2.all.min.js"></script>
<!-- Toastr -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<!-- InputMask -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.7/jquery.inputmask.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<!-- Custom Script -->
<script>
    // Set base URL for JS
    window.BASE_URL = '<?= base_url() ?>';
    window.CSRF_TOKEN = '<?= $this->security->get_csrf_hash() ?>';
    window.CSRF_NAME = '<?= $this->security->get_csrf_token_name() ?>';
</script>
<script src="<?= base_url('assets/js/custom.js') ?>"></script>

<?php if (isset($extra_js)): ?>
    <?= $extra_js ?>
<?php endif; ?>

<script>
$(document).ready(function() {
    // Initialize overlayScrollbars
    $('.sidebar').overlayScrollbars({
        className: 'os-theme-light',
        sizeAutoCapable: true,
        scrollbars: {
            autoHide: 'leave',
            clickScrolling: true
        }
    });
    
    // Configure toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": false,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "0",
        "extendedTimeOut": "0",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut",
        "tapToDismiss": false
    };
    
    // Show flash messages as toastr notifications
    <?php if ($this->session->flashdata('success')): ?>
        toastr.success('<?= addslashes($this->session->flashdata('success')) ?>');
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        toastr.error('<?= addslashes($this->session->flashdata('error')) ?>');
    <?php endif; ?>
    <?php if ($this->session->flashdata('warning')): ?>
        toastr.warning('<?= addslashes($this->session->flashdata('warning')) ?>');
    <?php endif; ?>
    <?php if ($this->session->flashdata('info')): ?>
        toastr.info('<?= addslashes($this->session->flashdata('info')) ?>');
    <?php endif; ?>

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').not('.alert-permanent').fadeOut('slow');
    }, 5000);

    // ===== Admin Notification Polling =====
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
            'loan_approved':      { icon: 'fas fa-check-circle',      bg: 'bg-success' },
            'loan_disbursed':     { icon: 'fas fa-money-bill-wave',   bg: 'bg-success' },
            'loan_rejected':      { icon: 'fas fa-times-circle',      bg: 'bg-danger' },
            'payment_received':   { icon: 'fas fa-hand-holding-usd',  bg: 'bg-info' },
            'payment_overdue':    { icon: 'fas fa-exclamation-triangle', bg: 'bg-warning' },
            'emi_due':            { icon: 'fas fa-calendar-exclamation', bg: 'bg-warning' },
            'guarantor_request':  { icon: 'fas fa-user-shield',       bg: 'bg-primary' },
            'member':             { icon: 'fas fa-user',              bg: 'bg-info' },
            'savings':            { icon: 'fas fa-piggy-bank',        bg: 'bg-success' },
            'fine':               { icon: 'fas fa-gavel',             bg: 'bg-danger' },
            'system':             { icon: 'fas fa-cog',               bg: 'bg-secondary' }
        };
        return map[type] || { icon: 'fas fa-bell', bg: 'bg-primary' };
    }

    function loadAdminNotifications() {
        $.getJSON('<?= site_url('admin/dashboard/notifications') ?>', function(data) {
            var list = $('.notification-list');
            var count = 0;
            list.empty();
            if (data && data.length) {
                data.forEach(function(n) {
                    var ic = notifIcon(n.notification_type || n.type);
                    var unreadCls = n.is_read ? '' : ' unread';
                    var html = '<a href="#" class="notif-item' + unreadCls + '" data-id="' + (n.id || '') + '">' +
                        '<div class="notif-icon ' + ic.bg + '"><i class="' + ic.icon + '"></i></div>' +
                        '<div class="notif-content">' +
                            '<div class="notif-title">' + $('<span>').text(n.title || 'Notification').html() + '</div>' +
                            '<div class="notif-msg">' + $('<span>').text((n.message || '').substring(0, 120)).html() + '</div>' +
                            '<div class="notif-time"><i class="far fa-clock"></i>' + notifTimeAgo(n.created_at) + '</div>' +
                        '</div>' +
                    '</a>';
                    list.append(html);
                    if (!n.is_read) count++;
                });
            } else {
                list.html('<div class="notif-empty text-center py-4">' +
                    '<i class="far fa-bell-slash fa-2x text-muted mb-2"></i>' +
                    '<p class="text-muted mb-0">No new notifications</p></div>');
            }
            var badge = $('.notification-count');
            if (count > 0) {
                badge.text(count > 99 ? '99+' : count).attr('data-active', 'true').show();
            } else {
                badge.text('0').attr('data-active', 'false').hide();
            }
        });
    }
    loadAdminNotifications();
    setInterval(loadAdminNotifications, 60000);
    
    // CSRF token for all AJAX requests
    $.ajaxSetup({
        data: {
            [window.CSRF_NAME]: window.CSRF_TOKEN
        }
    });
    
    // Update CSRF token after each AJAX request
    $(document).ajaxComplete(function(event, xhr, settings) {
        var csrf = xhr.getResponseHeader('X-CSRF-TOKEN');
        if (csrf) {
            window.CSRF_TOKEN = csrf;
            $('input[name="' + window.CSRF_NAME + '"]').val(csrf);
        }
    });
    
    <?php if (isset($load_reject_script) && $load_reject_script): ?>
    // Loan Application Scripts
    $('#confirmModify').click(function() {
        var remarks = $('#mod_remarks').val().trim();
        if (!remarks) {
            toastr.error('Please enter remarks');
            return;
        }
        var data = {
            remarks: remarks,
            approved_amount: $('#mod_amount').val(),
            approved_tenure_months: $('#mod_tenure').val(),
            approved_interest_rate: $('#mod_interest').val()
        };
        $.post('<?= site_url('admin/loans/request_modification/' . $application->id) ?>', data, function(resp) {
            if (resp.success) {
                toastr.success(resp.message);
                $('#modifyModal').modal('hide');
                location.reload();
            } else {
                toastr.error(resp.message || 'Failed to send modification request');
            }
        }, 'json');
    });

    $('#confirmReject').click(function(e) {
        e.preventDefault();
        console.log('Reject button clicked');

        var reason = $('#reject_reason').val().trim();
        console.log('Reason value:', reason);

        if (!reason) {
            toastr.error('Please enter rejection reason');
            return;
        }

        // Show loading state
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Rejecting...');

        var url = '<?= site_url('admin/loans/reject/' . $application->id) ?>';
        console.log('Reject URL:', url);
        console.log('Reason:', reason);

        $.post(url, {reason: reason}, function(response) {
            console.log('Response:', response);
            if (response.success) {
                toastr.success(response.message || 'Application rejected');
                $('#rejectModal').modal('hide');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to reject');
                $btn.prop('disabled', false).html('<i class="fas fa-times mr-1"></i> Reject Application');
            }
        }, 'json').fail(function(xhr, status, error) {
            console.log('AJAX Error:', xhr.responseText, status, error);
            toastr.error('Network error occurred. Please try again.');
            $btn.prop('disabled', false).html('<i class="fas fa-times mr-1"></i> Reject Application');
        });
    });
    <?php endif; ?>
});
</script>

</body>
</html>
