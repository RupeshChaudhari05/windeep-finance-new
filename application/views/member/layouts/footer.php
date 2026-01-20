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

<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<?php if (isset($extra_js)): ?>
    <?= $extra_js ?>
<?php endif; ?>

<script>
    // Set base URL for JS
    window.BASE_URL = '<?= base_url() ?>';
    window.CSRF_TOKEN = '<?= $this->security->get_csrf_hash() ?>';
    window.CSRF_NAME = '<?= $this->security->get_csrf_token_name() ?>';

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

    // Guarantor action modal (Accept/Reject with remarks)
    $('body').append('\n<div class="modal fade" id="guarantorActionModal" tabindex="-1" role="dialog" aria-hidden="true">\n  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">\n    <div class="modal-content">\n      <div class="modal-header">\n        <h5 class="modal-title" id="guarantorActionModalLabel">Guarantor Response</h5>\n        <button type="button" class="close" data-dismiss="modal" aria-label="Close">\n          <span aria-hidden="true">&times;</span>\n        </button>\n      </div>\n      <div class="modal-body">\n        <div class="form-group">\n            <label>Remarks (optional)</label>\n            <textarea id="guarantorRemarks" class="form-control" rows="3"></textarea>\n            <input type="hidden" id="guarantorActionGid" value="">\n            <input type="hidden" id="guarantorActionNid" value="">\n            <input type="hidden" id="guarantorActionType" value="">\n        </div>\n      </div>\n      <div class="modal-footer">\n        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>\n        <button type="button" class="btn btn-danger" id="confirmGuarantorReject" style="display:none">Reject</button>\n        <button type="button" class="btn btn-success" id="confirmGuarantorAccept" style="display:none">Accept</button>\n      </div>\n    </div>\n  </div>\n</div>\n');

    // Expose global function to open modal
    window.openGuarantorModal = function(action, gid, nid) {
        $('#guarantorRemarks').val('');
        $('#guarantorActionGid').val(gid);
        $('#guarantorActionNid').val(nid);
        $('#guarantorActionType').val(action);
        if (action === 'accept') {
            $('#confirmGuarantorAccept').show();
            $('#confirmGuarantorReject').hide();
        } else {
            $('#confirmGuarantorAccept').hide();
            $('#confirmGuarantorReject').show();
        }
        $('#guarantorActionModal').modal('show');
    };

    // Confirm handlers
    $(document).on('click', '#confirmGuarantorAccept', function(e){
        var gid = $('#guarantorActionGid').val();
        var nid = $('#guarantorActionNid').val();
        var remarks = $('#guarantorRemarks').val();
        var $btn = $(this);
        $btn.prop('disabled', true).text('Accepting...');
        $.post(window.BASE_URL + 'member/loans/guarantor_consent/' + gid, {action: 'accept', remarks: remarks}, function(resp){
            $btn.prop('disabled', false).text('Accept');
            if (resp && resp.success) {
                $('#guarantorActionModal').modal('hide');
                toastr.success(resp.message || 'Accepted');
                if (nid) {
                    $.post(window.BASE_URL + 'member/notifications/mark_read_ajax/' + nid, function(){
                        // refresh if notification list present
                        if (typeof loadMemberNotifications === 'function') loadMemberNotifications();
                        else location.reload();
                    });
                } else { location.reload(); }
            } else {
                toastr.error((resp && resp.message) || 'Failed to accept');
            }
        }, 'json').fail(function(){ $btn.prop('disabled', false).text('Accept'); toastr.error('Network error'); });
    });

    $(document).on('click', '#confirmGuarantorReject', function(e){
        if (!confirm('Confirm reject?')) return;
        var gid = $('#guarantorActionGid').val();
        var nid = $('#guarantorActionNid').val();
        var remarks = $('#guarantorRemarks').val();
        var $btn = $(this);
        $btn.prop('disabled', true).text('Rejecting...');
        $.post(window.BASE_URL + 'member/loans/guarantor_consent/' + gid, {action: 'reject', remarks: remarks}, function(resp){
            $btn.prop('disabled', false).text('Reject');
            if (resp && resp.success) {
                $('#guarantorActionModal').modal('hide');
                toastr.success(resp.message || 'Rejected');
                if (nid) {
                    $.post(window.BASE_URL + 'member/notifications/mark_read_ajax/' + nid, function(){
                        if (typeof loadMemberNotifications === 'function') loadMemberNotifications();
                        else location.reload();
                    });
                } else { location.reload(); }
            } else {
                toastr.error((resp && resp.message) || 'Failed to reject');
            }
        }, 'json').fail(function(){ $btn.prop('disabled', false).text('Reject'); toastr.error('Network error'); });
    });
</script>

</body>
</html>