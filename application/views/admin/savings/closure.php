<!-- Account Closure Request -->
<?php
$balance = (float) $account->current_balance;
$default_fee = min($setting_fee, max(0, $balance));
$pending = $req->status === 'pending';
?>
<div class="row">
    <div class="col-lg-5">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-door-closed mr-1"></i> Request #<?= (int) $req->id ?></h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><th style="width:40%">Member</th><td><a href="<?= site_url('admin/members/view/' . $req->member_id) ?>"><?= html_escape(trim($req->first_name . ' ' . $req->last_name)) ?></a>
                        <small class="text-muted"><?= html_escape($req->member_code) ?> · <?= html_escape($req->phone) ?></small></td></tr>
                    <tr><th>Account</th><td><a href="<?= site_url('admin/savings/view/' . $req->savings_account_id) ?>"><?= html_escape($req->account_number) ?></a>
                        <span class="badge badge-<?= $account->status === 'active' ? 'success' : 'secondary' ?>"><?= html_escape($account->status) ?></span></td></tr>
                    <tr><th>Requested on</th><td><?= format_date_time($req->requested_at) ?></td></tr>
                    <tr><th>Reason</th><td><?= nl2br(html_escape($req->reason)) ?></td></tr>
                    <tr><th>Balance at request</th><td><?= format_amount($req->balance_at_request) ?></td></tr>
                    <tr><th>Balance now</th><td><strong><?= format_amount($balance) ?></strong></td></tr>
                    <tr><th>Status</th><td>
                        <?php $badge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'][$req->status] ?? 'light'; ?>
                        <span class="badge badge-<?= $badge ?>"><?= $req->status === 'approved' ? 'closed' : html_escape($req->status) ?></span>
                        <?php if ($req->processed_at): ?><small class="text-muted"> · <?= format_date_time($req->processed_at) ?><?= $req->processed_by_name ? ' by ' . html_escape($req->processed_by_name) : '' ?></small><?php endif; ?>
                    </td></tr>
                    <?php if ($req->status === 'approved'): ?>
                    <tr><th>Balance at closure</th><td><?= format_amount($req->balance_at_closure) ?></td></tr>
                    <tr><th>Closing fee</th><td><?= format_amount($req->fee_amount) ?>
                        <?php if (abs((float) $req->fee_amount - (float) $req->setting_fee) > 0.001): ?><small class="text-muted">(settings <?= format_amount($req->setting_fee) ?> — <?= html_escape($req->fee_change_reason) ?>)</small><?php endif; ?></td></tr>
                    <tr><th>Paid to member</th><td><strong><?= format_amount($req->refund_amount) ?></strong>
                        <?php if ($req->payout_mode): ?><small class="text-muted"> · <?= html_escape(str_replace('_', ' ', $req->payout_mode)) ?> · <?= format_date($req->payout_date) ?><?= $req->payout_reference ? ' · ' . html_escape($req->payout_reference) : '' ?></small><?php endif; ?></td></tr>
                    <?php endif; ?>
                    <?php if ($req->admin_remarks): ?><tr><th>Remarks</th><td><?= nl2br(html_escape($req->admin_remarks)) ?></td></tr><?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <?php if ($pending): ?>
    <div class="col-lg-7">
        <?php if ($blockers): ?>
        <div class="card card-danger">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-ban mr-1"></i> Cannot be closed yet</h3></div>
            <div class="card-body">
                <ul class="mb-2"><?php foreach ($blockers as $b): ?><li><?= html_escape($b['text']) ?></li><?php endforeach; ?></ul>
                <p class="small text-muted mb-0">Running loans (as borrower or guarantor), open fines and overdue deposits must be cleared first. You can reject the request below.</p>
            </div>
        </div>
        <?php else: ?>
        <form method="post" action="<?= site_url('admin/savings/closure/' . $req->id) ?>" id="closureForm">
            <div class="card card-success">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-check mr-1"></i> Approve &amp; close account</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Closing fee <small class="text-muted">(settings: <?= format_amount($setting_fee) ?>)</small></label>
                            <input type="number" step="0.01" min="0" max="<?= $balance ?>" name="fee" id="clFee" class="form-control" value="<?= $default_fee ?>" required>
                        </div>
                        <div class="form-group col-md-6" id="clFeeReasonWrap" style="display:none">
                            <label>Reason for changing / waiving the fee *</label>
                            <input type="text" name="fee_reason" id="clFeeReason" class="form-control" maxlength="255">
                        </div>
                    </div>
                    <table class="table table-sm table-bordered mb-3">
                        <tr><td>Balance</td><td class="text-right"><?= format_amount($balance) ?></td></tr>
                        <tr><td>Closing fee</td><td class="text-right text-danger" id="clFeeShow">- <?= format_amount($default_fee) ?></td></tr>
                        <tr class="table-success"><th>Paid out to member</th><th class="text-right" id="clRefund"><?= format_amount($balance - $default_fee) ?></th></tr>
                    </table>
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>Payout mode</label>
                            <select name="payout_mode" class="form-control" data-no-search="1">
                                <option value="bank_transfer">Bank transfer</option><option value="cash">Cash</option><option value="cheque">Cheque</option><option value="upi">UPI</option>
                            </select></div>
                        <div class="form-group col-md-4"><label>Payout date</label>
                            <input type="date" name="payout_date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= substr($req->requested_at, 0, 10) ?>" max="<?= date('Y-m-d') ?>" required></div>
                        <div class="form-group col-md-4"><label>Reference / UTR</label>
                            <input type="text" name="payout_reference" class="form-control" maxlength="100"></div>
                    </div>
                    <div class="form-group mb-0"><label>Remarks</label><input type="text" name="remarks" class="form-control" maxlength="255"></div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success"><i class="fas fa-door-closed mr-1"></i> Approve, pay out &amp; close account</button>
                    <small class="text-muted ml-2">Future monthly dues are waived; the account is closed with a zero balance.</small>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <form method="post" action="<?= site_url('admin/savings/reject_closure/' . $req->id) ?>" class="card card-outline card-danger">
            <div class="card-body">
                <label class="mb-1">Reject request</label>
                <div class="input-group">
                    <input type="text" name="remarks" class="form-control" placeholder="Reason (sent to the member)" maxlength="500" required>
                    <div class="input-group-append"><button class="btn btn-danger" onclick="return confirm('Reject this closure request?')"><i class="fas fa-times mr-1"></i> Reject</button></div>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php if ($pending && !$blockers): ?>
<script>
$(function () {
    var balance = <?= json_encode(round($balance, 2)) ?>, settingFee = <?= json_encode(round($setting_fee, 2)) ?>;
    function inr(v) { return '₹' + Number(v).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function upd() {
        var f = Math.max(0, parseFloat($('#clFee').val()) || 0), changed = Math.abs(f - settingFee) > 0.001;
        $('#clFeeShow').text('- ' + inr(f));
        $('#clRefund').text(inr(Math.max(0, balance - f)));
        $('#clFeeReasonWrap').toggle(changed);
        $('#clFeeReason').prop('required', changed);
    }
    $('#clFee').on('input', upd); upd();
    $('#closureForm').on('submit', function () {
        return confirm('Close this account? The fee is deducted and ' + $('#clRefund').text() + ' is paid out. This cannot be undone here.');
    });
});
</script>
<?php endif; ?>
