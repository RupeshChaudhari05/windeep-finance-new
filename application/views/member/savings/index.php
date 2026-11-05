<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-piggy-bank mr-1"></i> My Security Deposits</h3></div>
    <div class="card-body">
        <?php if (empty($accounts)): ?>
            <div class="text-center py-4">
                <i class="fas fa-piggy-bank fa-3x text-muted mb-3"></i>
                <p class="text-muted">No security deposit accounts.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($accounts as $a): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card card-outline card-success h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= $a->account_number ?></h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Current Balance:</span>
                                <strong class="text-success"><?= format_amount($a->current_balance ?? 0) ?></strong>
                            </div>
                            <?php if (!empty($a->interest_rate)): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Interest Rate:</span>
                                <span><?= $a->interest_rate ?>% p.a.</span>
                            </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Status:</span>
                                <span class="badge badge-<?= $a->status == 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($a->status) ?></span>
                            </div>
                            <?php $cr = $closure[$a->id] ?? null; ?>
                            <?php if ($cr && $cr->status === 'pending'): ?>
                            <div class="alert alert-warning py-2 px-2 mb-2 small">
                                <i class="fas fa-hourglass-half mr-1"></i> Closure requested on <?= format_date($cr->requested_at) ?> — waiting for approval.
                                <form method="post" action="<?= site_url('member/savings/cancel_closure/' . $cr->id) ?>" class="d-inline">
                                    <button class="btn btn-link btn-sm p-0 ml-1" onclick="return confirm('Cancel your closure request?')">Cancel request</button>
                                </form>
                            </div>
                            <?php elseif ($cr && $cr->status === 'approved' && $a->status === 'closed'): ?>
                            <div class="small text-muted mb-2"><i class="fas fa-lock mr-1"></i> Closed on <?= format_date($cr->payout_date) ?>: fee <?= format_amount($cr->fee_amount) ?>, paid to you <?= format_amount($cr->refund_amount) ?>.</div>
                            <?php elseif (in_array($a->status, ['active', 'matured'], true)): ?>
                            <?php if ($cr && $cr->status === 'rejected'): ?>
                            <div class="small text-danger mb-1"><i class="fas fa-times-circle mr-1"></i> Last closure request was not approved: <?= html_escape((string) $cr->admin_remarks) ?></div>
                            <?php endif; ?>
                            <a href="<?= site_url('member/savings/request_closure/' . $a->id) ?>" class="btn btn-outline-danger btn-sm btn-block mb-2">
                                <i class="fas fa-door-closed mr-1"></i> Request account closure
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($a->recent_transactions)): ?>
                            <hr>
                            <small class="text-muted font-weight-bold">Recent Transactions:</small>
                            <ul class="list-unstyled mt-1 mb-0">
                                <?php foreach (array_slice($a->recent_transactions, 0, 3) as $t): ?>
                                <li class="d-flex justify-content-between py-1 border-bottom">
                                    <span>
                                        <i class="fas fa-<?= $t->transaction_type == 'deposit' ? 'arrow-down text-success' : 'arrow-up text-danger' ?> mr-1"></i>
                                        <small><?= format_date($t->transaction_date) ?></small>
                                    </span>
                                    <small class="<?= $t->transaction_type == 'deposit' ? 'text-success' : 'text-danger' ?>">
                                        <?= $t->transaction_type == 'deposit' ? '+' : '-' ?><?= format_amount($t->amount) ?>
                                    </small>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="<?= site_url('member/savings/view/' . $a->id) ?>" class="btn btn-sm btn-outline-success btn-block">
                                <i class="fas fa-eye mr-1"></i> View All Transactions
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>