<div class="card">
    <div class="card-header">
        <h3 class="card-title">Application #<?= $application->application_number ?></h3>
        <div class="card-tools">
            <span class="badge badge-<?= $application->status == 'pending' ? 'warning' : ($application->status == 'needs_revision' ? 'info' : ($application->status == 'member_approved' ? 'success' : 'secondary')) ?> badge-lg">
                <?= strtoupper(str_replace('_', ' ', $application->status)) ?>
            </span>
        </div>
    </div>
    <div class="card-body">
        <p><strong>Product:</strong> <?= $application->product_name ?></p>
        <p><strong>Amount:</strong> ₹<?= number_format($application->requested_amount, 2) ?></p>
        <p><strong>Tenure:</strong> <?= $application->requested_tenure_months ?> months</p>
        <p><strong>Purpose:</strong> <?= nl2br(htmlspecialchars($application->purpose)) ?></p>

        <?php if (!empty($application->revision_remarks)): ?>
            <hr>
            <div class="alert alert-info">
                <strong>Requested Changes:</strong>
                <div><?= nl2br(htmlspecialchars($application->revision_remarks)) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($guarantors)): ?>
            <h5>Guarantors</h5>
            <ul>
                <?php foreach ($guarantors as $g): ?>
                    <li><?= $g->member_code ?> - <?= htmlspecialchars($g->first_name . ' ' . $g->last_name) ?> (₹<?= number_format($g->guarantee_amount,2) ?>) - <?= ucfirst($g->consent_status ?? 'pending') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (in_array($application->status, ['pending','needs_revision','rejected'])): ?>
            <a href="<?= site_url('member/loans/edit_application/' . $application->id) ?>" class="btn btn-primary">Edit & Resubmit</a>
        <?php endif; ?>

        <a href="<?= site_url('member/loans') ?>" class="btn btn-default">Back</a>
    </div>
</div>