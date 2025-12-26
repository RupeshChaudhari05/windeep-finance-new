<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-clock mr-1"></i> Waiver Requests</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="waiverRequestsTable">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Fine</th>
                        <th>Member</th>
                        <th>Requested By</th>
                        <th>Requested At</th>
                        <th>Requested Amount</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No waiver requests found.</td>
                    </tr>
                    <?php else: $i = 1; foreach ($requests as $req): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><strong><?= $req->fine_code ?? ('#' . $req->id) ?></strong><br><small>₹<?= number_format($req->balance_amount ?? $req->fine_amount, 2) ?> balance</small></td>
                        <td><?= trim(($req->first_name ?? '') . ' ' . ($req->last_name ?? '')) ?> (<?= $req->member_code ?? '' ?>)</td>
                        <td><?= $req->waiver_requested_by ? $req->waiver_requested_by : '-' ?></td>
                        <td><?= format_date_time($req->waiver_requested_at, 'd M Y H:i', '-') ?></td>
                        <td>
                            <input type="number" class="form-control form-control-sm request-amount" value="<?= number_format(min($req->balance_amount ?? 0, $req->fine_amount ?? 0), 2, '.', '') ?>" data-id="<?= $req->id ?>" step="0.01" min="0">
                        </td>
                        <td><?= character_limiter($req->waiver_reason ?? '', 80) ?></td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-success btn-approve" data-id="<?= $req->id ?>">Approve</button>
                                <button class="btn btn-sm btn-danger btn-deny" data-id="<?= $req->id ?>">Deny</button>
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
        if (!amount || amount <= 0) { toastr.error('Enter valid amount'); return; }
        if (!confirm('Approve waiver for ₹' + amount + '?')) return;
        $.post('<?= site_url('admin/fines/approve_waiver/') ?>' + id, {amount: amount}, function(res) {
            if (res.success) { toastr.success(res.message); location.reload(); } else { toastr.error(res.message); }
        }, 'json');
    });

    $('.btn-deny').click(function() {
        var id = $(this).data('id');
        var reason = prompt('Enter denial reason');
        if (!reason) { toastr.error('Denial reason required'); return; }
        $.post('<?= site_url('admin/fines/deny_waiver/') ?>' + id, {reason: reason}, function(res) {
            if (res.success) { toastr.success(res.message); location.reload(); } else { toastr.error(res.message); }
        }, 'json');
    });
});
</script>