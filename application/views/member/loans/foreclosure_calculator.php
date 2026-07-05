<?php
$cs             = get_currency_symbol();
$principal      = (float)($calculation['outstanding_principal']   ?? 0);
$total_interest = (float)($calculation['total_interest']          ?? 0);
$interest_pct   = (float)($calculation['interest_charge_pct']     ?? 80);
$interest_charge = (float)($calculation['interest_charge']         ?? 0);
$fines          = (float)($calculation['pending_fines']           ?? 0);
$total          = (float)($calculation['total_amount']            ?? 0);
?>

<div class="row">
    <div class="col-md-8">
        <h6 class="border-bottom pb-1 mb-2">Foreclosure Settlement Breakdown</h6>
        <table class="table table-sm table-bordered">
            <tr>
                <td>Outstanding Principal</td>
                <td class="text-right font-weight-bold"><?= $cs.number_format($principal,2) ?></td>
            </tr>
            <tr>
                <td>Total Pending Interest (Current + Future Months)</td>
                <td class="text-right"><?= $cs.number_format($total_interest,2) ?></td>
            </tr>
            <tr>
                <td>
                    Interest Charge (<?= $interest_pct ?>%)
                    <small class="d-block text-muted">
                        <?= $interest_pct ?>% of total pending interest (admin configured)
                    </small>
                </td>
                <td class="text-right text-info font-weight-bold"><?= $cs.number_format($interest_charge,2) ?></td>
            </tr>
            <?php if ($fines > 0): ?>
            <tr>
                <td>Pending Fines / Charges</td>
                <td class="text-right text-danger"><?= $cs.number_format($fines,2) ?></td>
            </tr>
            <?php endif; ?>
            <tr class="table-primary">
                <td><strong>Total Foreclosure Amount</strong></td>
                <td class="text-right font-weight-bold" id="calculatedAmount"><?= $cs.number_format($total,2) ?></td>
            </tr>
        </table>
        <div class="alert alert-info py-2 mb-0">
            <small>
                <strong>Formula:</strong> Settlement = Principal + (Total Interest × Admin %) + Fines
                <br><i class="fas fa-info-circle mr-1"></i>
                Estimate only. Final amount may vary on settlement date. Contact admin for exact figure.
            </small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-body text-center py-3">
                <div class="text-muted small">Total Amount Due</div>
                <h3 class="text-danger font-weight-bold mb-0"><?= $cs.number_format($total,2) ?></h3>
                <small class="text-muted">Including all charges</small>
            </div>
        </div>
        <?php if (!empty($calculation['pending_fines_list'])): ?>
        <div class="mt-2">
            <small class="text-muted font-weight-bold">Pending Fines:</small>
            <?php foreach ($calculation['pending_fines_list'] as $fine): ?>
            <div class="d-flex justify-content-between border-bottom py-1">
                <small class="text-danger">Fine #<?= $fine->id ?></small>
                <small class="font-weight-bold"><?= $cs.number_format((float)($fine->fine_amount ?? $fine->amount ?? 0),2) ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="alert alert-warning py-2">
            <h6 class="mb-1"><i class="fas fa-exclamation-triangle"></i> Important Information</h6>
            <ul class="mb-0 small">
                <?php if ($prepay_pct > 0): ?>
                <li>The prepayment charge is calculated as <?= $prepay_pct ?>% of the outstanding principal.</li>
                <?php endif; ?>
                <?php if ($pic_pct > 0): ?>
                <li>The foreclosure charge of <?= $pic_pct ?>% is applied to the total pending scheduled interest (current month + all remaining months).</li>
                <?php endif; ?>
                <li>All pending fines must be cleared as part of the foreclosure.</li>
                <li>The loan will be marked as closed only after full payment of the foreclosure amount.</li>
                <li>This request will be reviewed by management before approval.</li>
            </ul>
        </div>
    </div>
</div>