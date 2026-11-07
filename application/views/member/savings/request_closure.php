<?php
$balance = (float) $account->current_balance;
$fee = min($closing_fee, max(0, $balance));
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-door-closed mr-1"></i> Close account <?= html_escape($account->account_number) ?></h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered mb-3">
                    <tr><td>Current balance</td><td class="text-right"><?= format_amount($balance) ?></td></tr>
                    <tr><td>Account closing fee</td><td class="text-right text-danger">- <?= format_amount($fee) ?></td></tr>
                    <tr class="table-success"><th>You will receive (estimate)</th><th class="text-right"><?= format_amount(max(0, $balance - $fee)) ?></th></tr>
                </table>
                <p class="small text-muted">The final amount is worked out on the day the office approves your request. After closing, no more monthly deposits are due on this account.</p>

                <?php if ($pending): ?>
                    <div class="alert alert-info mb-0"><i class="fas fa-hourglass-half mr-1"></i> You already asked to close this account on <?= format_date($pending->requested_at) ?>. It is waiting for approval.</div>
                <?php elseif ($blockers): ?>
                    <div class="alert alert-warning mb-0">
                        <strong><i class="fas fa-exclamation-triangle mr-1"></i> This account cannot be closed yet:</strong>
                        <ul class="mb-0 mt-1"><?php foreach ($blockers as $b): ?><li><?= html_escape($b['text']) ?></li><?php endforeach; ?></ul>
                        <small>Please clear these first, then request again.</small>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= site_url('member/savings/request_closure/' . $account->id) ?>">
                        <div class="form-group">
                            <label>Why do you want to close this account? <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="3" minlength="5" maxlength="2000" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Send the request to close this account?')">
                            <i class="fas fa-paper-plane mr-1"></i> Send closure request
                        </button>
                        <a href="<?= site_url('member/savings') ?>" class="btn btn-default">Back</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
