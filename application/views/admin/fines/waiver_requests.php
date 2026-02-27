<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-clock mr-1"></i> Waiver Requests</h3>
        <div class="card-tools">
            <a href="<?= site_url('admin/fines') ?>" class="btn btn-sm btn-default">
                <i class="fas fa-arrow-left"></i> Back to Fines
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="waiverRequestsTable">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Fine</th>
                        <th>Member</th>
                        <th>Requested At</th>
                        <th>Requested Amount</th>
                        <th>Waive Amount</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                            No pending waiver requests.
                        </td>
                    </tr>
                    <?php else: $i = 1; foreach ($requests as $req): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <a href="<?= site_url('admin/fines/view/' . $req->id) ?>" class="font-weight-bold">
                                <?= $req->fine_code ?? ('#' . $req->id) ?>
                            </a>
                            <br>
                            <small class="text-muted">
                                Fine: <?= format_amount($req->fine_amount ?? 0) ?> |
                                Balance: <?= format_amount($req->balance_amount ?? $req->fine_amount) ?>
                            </small>
                        </td>
                        <td>
                            <a href="<?= site_url('admin/members/view/' . $req->member_id) ?>">
                                <?= trim(($req->first_name ?? '') . ' ' . ($req->last_name ?? '')) ?>
                            </a>
                            <br><small class="text-muted"><?= $req->member_code ?? '' ?></small>
                        </td>
                        <td><?= format_date_time($req->waiver_requested_at, 'd M Y H:i', '-') ?></td>
                        <td>
                            <?php if (!empty($req->waiver_requested_amount)): ?>
                                <span class="badge badge-info"><?= format_amount($req->waiver_requested_amount) ?></span>
                            <?php else: ?>
                                <span class="text-muted">Full balance</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm request-amount"
                                   value="<?= number_format($req->waiver_requested_amount ?? ($req->balance_amount ?? $req->fine_amount ?? 0), 2, '.', '') ?>"
                                   data-id="<?= $req->id ?>" step="0.01" min="0"
                                   max="<?= number_format($req->balance_amount ?? $req->fine_amount ?? 0, 2, '.', '') ?>">
                            <small class="text-muted">Max: <?= format_amount($req->balance_amount ?? $req->fine_amount ?? 0) ?></small>
                        </td>
                        <td>
                            <span title="<?= htmlspecialchars($req->waiver_reason ?? '') ?>">
                                <?= character_limiter($req->waiver_reason ?? '', 80) ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-success btn-approve" data-id="<?= $req->id ?>" title="Approve Waiver">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="btn btn-sm btn-danger btn-deny" data-id="<?= $req->id ?>" title="Deny Waiver">
                                    <i class="fas fa-times"></i> Deny
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.btn-approve').click(function() {
        var id = $(this).data('id');
        var amount = $('.request-amount[data-id="' + id + '"]').val();
        if (!amount || parseFloat(amount) <= 0) { toastr.error('Enter a valid waiver amount'); return; }
        if (!confirm('Approve waiver for <?= get_currency_symbol() ?>' + parseFloat(amount).toFixed(2) + '?')) return;

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post('<?= site_url('admin/fines/approve-waiver/') ?>' + id, {amount: amount}, function(res) {
            if (res.success) {
                toastr.success(res.message);
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                toastr.error(res.message);
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Approve');
            }
        }, 'json').fail(function() {
            toastr.error('Request failed. Please try again.');
            $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Approve');
        });
    });

    $('.btn-deny').click(function() {
        var id = $(this).data('id');
        var reason = prompt('Enter denial reason:');
        if (!reason || !reason.trim()) { toastr.error('Denial reason is required'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post('<?= site_url('admin/fines/deny-waiver/') ?>' + id, {reason: reason.trim()}, function(res) {
            if (res.success) {
                toastr.success(res.message);
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                toastr.error(res.message);
                $btn.prop('disabled', false).html('<i class="fas fa-times"></i> Deny');
            }
        }, 'json').fail(function() {
            toastr.error('Request failed. Please try again.');
            $btn.prop('disabled', false).html('<i class="fas fa-times"></i> Deny');
        });
    });
});
</script>