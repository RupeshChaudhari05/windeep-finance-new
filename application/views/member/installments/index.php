<div class="card">
    <div class="card-header"><h3 class="card-title">My Installments</h3></div>
    <div class="card-body">
        <form method="get" class="form-inline mb-3">
            <label class="mr-2">Loan</label>
            <select name="loan_id" class="form-control mr-2">
                <option value="">All</option>
                <?php foreach ($member_loans as $l): ?>
                <option value="<?= $l->id ?>" <?= $this->input->get('loan_id') == $l->id ? 'selected' : '' ?>><?= $l->loan_number ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary">Filter</button>
        </form>

        <?php if (empty($installments)): ?>
            <p class="text-muted">No installments found.</p>
        <?php else: ?>
            <table class="table table-sm">
                <thead><tr><th>Due Date</th><th>Loan</th><th class="text-right">EMI</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($installments as $inst): ?>
                    <tr>
                        <td><?= format_date($inst->due_date) ?></td>
                        <td><?= $inst->loan_number ?></td>
                        <td class="text-right">₹<?= number_format($inst->emi_amount, 2) ?></td>
                        <td><?= ucfirst($inst->status) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>