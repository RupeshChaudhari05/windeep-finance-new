<div class="card">
    <div class="card-header">
        <h3 class="card-title">My Loans</h3>
        <div class="card-tools">
            <a href="<?= site_url('member/loans/apply') ?>" class="btn btn-sm btn-primary">Apply for Loan</a>
            <a href="<?= site_url('member/loans/applications') ?>" class="btn btn-sm btn-secondary ml-2">My Applications</a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($loans)): ?>
            <p class="text-muted">No loans found.</p>
        <?php else: ?>
            <table class="table table-sm">
                <thead>
                    <tr><th>Loan Number</th><th>Product</th><th>Status</th><th class="text-right">Outstanding</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $l): ?>
                    <?php
                        $loan_status = strtolower($l->status ?? 'active');
                        if (in_array($loan_status, ['foreclosed', 'closed'])) {
                            $status_label = ($l->closure_type === 'force_close') ? 'Force Close' : 'Closed';
                            $status_class = 'danger';
                        } elseif ($loan_status === 'overdue') {
                            $status_label = 'Active';
                            $status_class = 'warning';
                        } elseif ($loan_status === 'npa') {
                            $status_label = 'NPA';
                            $status_class = 'danger';
                        } else {
                            $status_label = 'Active';
                            $status_class = 'success';
                        }
                    ?>
                    <tr>
                        <td><?= $l->loan_number ?></td>
                        <td><?= $l->product_name ?></td>
                        <td><span class="badge badge-<?= $status_class ?>"><?= htmlspecialchars($status_label) ?></span></td>
                        <td class="text-right"><?= format_amount($l->outstanding_principal ?? 0) ?></td>
                        <td><a href="<?= site_url('member/loans/view/' . $l->id) ?>" class="btn btn-xs btn-info">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>