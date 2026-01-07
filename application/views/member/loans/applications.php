<div class="card">
    <div class="card-header">
        <h3 class="card-title">My Loan Applications</h3>
        <div class="card-tools">
            <a href="<?= site_url('member/loans/apply') ?>" class="btn btn-sm btn-success">New Application</a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= $this->session->flashdata('success') ?>
        </div>
        <?php endif; ?>
        
        <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= $this->session->flashdata('error') ?>
        </div>
        <?php endif; ?>
        
        <?php if (empty($applications)): ?>
            <p class="text-muted">You have no loan applications.</p>
        <?php else: ?>
            <table class="table table-sm">
                <thead><tr><th>#</th><th>Product</th><th>Amount</th><th>Tenure</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><?= $app->application_number ?></td>
                        <td><?= htmlspecialchars($app->product_name ?? '-') ?></td>
                        <td>₹<?= number_format($app->requested_amount,2) ?></td>
                        <td><?= $app->requested_tenure_months ?> months</td>
                        <td><?= ucfirst(str_replace('_',' ',$app->status)) ?></td>
                        <td>
                            <a href="<?= site_url('member/loans/application/' . $app->id) ?>" class="btn btn-sm btn-outline-primary">View</a>
                            <?php if (in_array($app->status, ['pending','needs_revision','rejected'])): ?>
                                <a href="<?= site_url('member/loans/edit_application/' . $app->id) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <?php elseif ($app->status === 'member_review'): ?>
                                <span class="badge badge-warning">Action Required</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>