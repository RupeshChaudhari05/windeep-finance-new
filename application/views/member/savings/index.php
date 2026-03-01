<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-piggy-bank mr-1"></i> My Savings</h3></div>
    <div class="card-body">
        <?php if (empty($accounts)): ?>
            <div class="text-center py-4">
                <i class="fas fa-piggy-bank fa-3x text-muted mb-3"></i>
                <p class="text-muted">No savings accounts.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($accounts as $a): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card card-outline card-success h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= $a->account_number ?></h5>
                            <p class="text-muted mb-2"><i class="fas fa-tag mr-1"></i> <?= $a->scheme_name ?></p>
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