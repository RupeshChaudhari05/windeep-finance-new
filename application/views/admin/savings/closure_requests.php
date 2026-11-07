<!-- Account Closure Requests -->
<div class="card card-outline card-primary">
    <div class="card-header d-flex flex-wrap align-items-center">
        <h3 class="card-title mb-0 mr-auto"><i class="fas fa-door-closed mr-1"></i> Account Closure Requests</h3>
        <div class="btn-group btn-group-sm mt-2 mt-md-0">
            <?php foreach (['pending' => 'Pending', 'approved' => 'Closed', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'all' => 'All'] as $k => $l): ?>
            <a href="<?= site_url('admin/savings/closure_requests?status=' . $k) ?>" class="btn btn-<?= $status === $k ? 'primary' : 'outline-primary' ?>"><?= $l ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (!$installed): ?>
        <div class="alert alert-warning m-3 mb-0">Account closure is not set up yet — run migration <code>039_savings_closure.sql</code>.</div>
        <?php else: ?>
        <p class="small text-muted px-3 pt-3 mb-2"><i class="fas fa-info-circle mr-1"></i>
            Closing fee in settings: <strong><?= format_amount($setting_fee) ?></strong>. On approval the fee is deducted, the rest of the balance is paid out and the account is closed.</p>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light"><tr>
                    <th>Requested</th><th>Member</th><th>Account</th><th class="text-right">Balance now</th><th>Reason</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                <?php if (!$requests): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> No <?= $status === 'all' ? '' : html_escape($status) ?> requests.</td></tr>
                <?php endif; ?>
                <?php foreach ($requests as $r):
                    $badge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'][$r->status] ?? 'light'; ?>
                    <tr>
                        <td><?= format_date($r->requested_at) ?></td>
                        <td><?= html_escape(trim($r->first_name . ' ' . $r->last_name)) ?><br><small class="text-muted"><?= html_escape($r->member_code) ?> · <?= html_escape($r->phone) ?></small></td>
                        <td><a href="<?= site_url('admin/savings/view/' . $r->savings_account_id) ?>"><?= html_escape($r->account_number) ?></a></td>
                        <td class="text-right"><?= format_amount($r->current_balance) ?></td>
                        <td><small><?= html_escape(mb_strimwidth((string) $r->reason, 0, 80, '…')) ?></small></td>
                        <td><span class="badge badge-<?= $badge ?>"><?= $r->status === 'approved' ? 'closed' : html_escape($r->status) ?></span>
                            <?php if ($r->status === 'approved'): ?><br><small class="text-muted">fee <?= format_amount($r->fee_amount) ?> · paid <?= format_amount($r->refund_amount) ?></small><?php endif; ?></td>
                        <td class="text-right"><a href="<?= site_url('admin/savings/closure/' . $r->id) ?>" class="btn btn-xs btn-<?= $r->status === 'pending' ? 'primary' : 'outline-secondary' ?>">
                            <?= $r->status === 'pending' ? 'Review' : 'View' ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
