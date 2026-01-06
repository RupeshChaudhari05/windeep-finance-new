<div class="card">
    <div class="card-header">
        <h3 class="card-title">Loan Details - <?= $loan->loan_number ?></h3>
    </div>
    <div class="card-body">
        <p><strong>Product:</strong> <?= $loan->product_name ?></p>
        <p><strong>Amount:</strong> ₹<?= number_format($loan->principal_amount, 2) ?></p>
        <p><strong>Outstanding:</strong> ₹<?= number_format($loan->outstanding_principal ?? 0, 2) ?></p>

        <h5>Installments</h5>
        <?php if (empty($installments)): ?>
            <p class="text-muted">No installments found.</p>
        <?php else: ?>
            <table class="table table-sm">
                <thead><tr><th>#</th><th>Due Date</th><th class="text-right">EMI</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($installments as $inst): ?>
                    <tr>
                        <td><?= $inst->installment_number ?></td>
                        <td><?= format_date($inst->due_date) ?></td>
                        <td class="text-right">₹<?= number_format($inst->emi_amount, 2) ?></td>
                        <td><?= ucfirst($inst->status) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>