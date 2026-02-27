<div class="row">
    <div class="col-md-8">
        <h6>Foreclosure Calculation Breakdown</h6>

        <table class="table table-sm">
            <tr>
                <td><strong>Outstanding Principal:</strong></td>
                <td class="text-right"><?= format_amount($calculation['outstanding_principal']) ?></td>
            </tr>
            <?php if ($calculation['prepayment_charge'] > 0): ?>
            <tr>
                <td><strong>Prepayment Charge (<?= $calculation['prepayment_percentage'] ?>%):</strong></td>
                <td class="text-right text-warning"><?= format_amount($calculation['prepayment_charge']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($calculation['pending_fines'] > 0): ?>
            <tr>
                <td><strong>Pending Fines:</strong></td>
                <td class="text-right text-danger"><?= format_amount($calculation['pending_fines']) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="table-active">
                <td><strong>Total Foreclosure Amount:</strong></td>
                <td class="text-right font-weight-bold text-primary" id="calculatedAmount">
                    <?= format_amount($calculation['total_amount']) ?>
                </td>
            </tr>
        </table>

        <div class="alert alert-info">
            <small>
                <strong>Note:</strong> This calculation is an estimate. The final amount may vary based on the exact settlement date and any additional charges that may apply. Please contact administration for the exact amount.
            </small>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h6>Total Amount Due</h6>
                <h3 class="text-danger font-weight-bold">
                    <?= format_amount($calculation['total_amount']) ?>
                </h3>
                <small class="text-muted">Including all charges</small>
            </div>
        </div>

        <?php if (!empty($calculation['pending_fines_list'])): ?>
        <div class="mt-3">
            <h6>Pending Fines Details:</h6>
            <div class="list-group list-group-flush">
                <?php foreach ($calculation['pending_fines_list'] as $fine): ?>
                <div class="list-group-item px-0">
                    <small>
                        <strong>Fine #<?= $fine->id ?>:</strong> <?= format_amount((float) (isset($fine->amount) ? $fine->amount : 0)) ?>
                        <br>
                        <span class="text-muted">
                            <?php
                            $fine_types = [
                                'late_payment' => 'Late Payment',
                                'missed_installment' => 'Missed Installment',
                                'loan_default' => 'Loan Default',
                                'other' => 'Other'
                            ];
                            echo $fine_types[$fine->fine_type] ?? $fine->fine_type;
                            ?>
                        </span>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="alert alert-warning">
            <h6><i class="fas fa-exclamation-triangle"></i> Important Information</h6>
            <ul class="mb-0">
                <li>The prepayment charge is calculated as <?= $calculation['prepayment_percentage'] ?>% of the outstanding principal.</li>
                <li>All pending fines must be cleared as part of the foreclosure process.</li>
                <li>The loan will be marked as closed only after full payment of the foreclosure amount.</li>
                <li>This request will be reviewed by management before approval.</li>
            </ul>
        </div>
    </div>
</div>