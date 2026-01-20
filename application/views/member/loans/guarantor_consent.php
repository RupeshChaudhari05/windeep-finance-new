<?php if (!isset($guarantor)) { echo '<p>Invalid request.</p>'; return; } ?>
<div class="card">
    <div class="card-header"><h3 class="card-title">Guarantor Consent</h3></div>
    <div class="card-body">
        <p><strong>Application:</strong> <?= htmlspecialchars($application->application_number) ?></p>
        <p><strong>Applicant:</strong> <?= htmlspecialchars($application->first_name . ' ' . $application->last_name) ?></p>
        <p><strong>Amount:</strong> ₹<?= number_format($application->requested_amount, 2) ?></p>
        <p><strong>Your Requested Guarantee:</strong> ₹<?= number_format($guarantor->guarantee_amount, 2) ?></p>
        <p><strong>Status:</strong> <?= strtoupper($guarantor->consent_status) ?></p>

        <?php if ($guarantor->consent_status === 'pending'): ?>
            <form method="post" action="<?= site_url('member/loans/guarantor_consent/' . $guarantor->id . '/' . $token) ?>">
                <div class="form-group">
                    <label>Remarks (optional)</label>
                    <textarea name="remarks" class="form-control"></textarea>
                </div>
                <button name="action" value="accept" class="btn btn-success">Accept</button>
                <button name="action" value="reject" class="btn btn-danger">Reject</button>
            </form>
        <?php else: ?>
            <div class="alert alert-info">You have already responded: <?= htmlspecialchars($guarantor->consent_status) ?>.</div>
        <?php endif; ?>
    </div>
</div>