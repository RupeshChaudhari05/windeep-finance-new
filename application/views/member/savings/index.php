<div class="card">
    <div class="card-header"><h3 class="card-title">My Savings</h3></div>
    <div class="card-body">
        <?php if (empty($accounts)): ?>
            <p class="text-muted">No savings accounts.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($accounts as $a): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= $a->account_number ?></strong>
                        <br><small class="text-muted"><?= $a->scheme_name ?></small>
                    </div>
                    <span class="badge badge-success"><?= format_amount($a->current_balance ?? 0) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>