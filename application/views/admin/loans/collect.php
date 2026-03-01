<div class="row">
    <?php if (!$loan): ?>
    <!-- Loan Not Found -->
    <div class="col-12">
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Loan Not Found</h3>
            </div>
            <div class="card-body">
                <p><i class="fas fa-ban mr-1"></i> The requested loan could not be found or you don't have permission to access it.</p>
                <a href="<?= site_url('admin/loans') ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Loans
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Loan Info -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-contract mr-1"></i> Loan Details</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="mb-0"><?= $loan->loan_number ?></h4>
                    <?php $status_class = ['active' => 'success', 'overdue' => 'warning', 'npa' => 'danger', 'closed' => 'secondary']; ?>
                    <span class="badge badge-<?= $status_class[$loan->status] ?? 'secondary' ?>">
                        <?= strtoupper($loan->status ?? 'unknown') ?>
                    </span>
                </div>
                
                <table class="table table-sm table-borderless">
                    <tr>
                        <th>Member:</th>
                        <td>
                            <a href="<?= site_url('admin/members/view/' . $member->id) ?>">
                                <?= $member->first_name ?> <?= $member->last_name ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>Code:</th>
                        <td><?= $member->member_code ?></td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td><a href="tel:<?= $member->phone ?>"><?= $member->phone ?></a></td>
                    </tr>
                    <tr>
                        <th>Product:</th>
                        <td><span class="badge badge-info"><?= $product->product_name ?></span></td>
                    </tr>
                    <tr>
                        <th>Principal:</th>
                        <td class="font-weight-bold"><?= format_amount($loan->principal_amount, 0) ?></td>
                    </tr>
                    <tr>
                        <th>EMI:</th>
                        <td class="font-weight-bold text-primary"><?= format_amount($loan->emi_amount, 0) ?></td>
                    </tr>
                </table>
                
                <hr>
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Outstanding Principal:</span>
                    <strong class="text-danger"><?= format_amount($loan->outstanding_principal, 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Outstanding Interest:</span>
                    <strong><?= format_amount($loan->outstanding_interest, 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pending Fines:</span>
                    <strong class="text-warning"><?= format_amount($pending_fines ?? 0, 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between border-top pt-2">
                    <span><strong>Total Due:</strong></span>
                    <strong class="text-danger"><?= format_amount($loan->outstanding_principal + $loan->outstanding_interest + ($pending_fines ?? 0), 0) ?></strong>
                </div>
                
                <?php if ($overdue_emis): ?>
                <hr>
                <div class="card card-danger mb-0">
                    <div class="card-body py-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong><?= count($overdue_emis) ?> EMI(s) Overdue</strong>
                        <br>
                        Amount: <?= get_currency_symbol() ?><?= number_format(array_sum(array_column($overdue_emis, 'emi_amount'))) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Pending EMIs -->
        <?php if (!empty($pending_emis)): ?>
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Pending EMIs</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Due Date</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($pending_emis, 0, 6) as $emi): ?>
                        <tr class="<?= safe_timestamp($emi->due_date) < time() ? 'table-danger' : '' ?>">
                            <td><?= $emi->installment_number ?></td>
                            <td><?= format_date($emi->due_date) ?></td>
                            <td class="text-right"><?= format_amount($emi->emi_amount, 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Collection Form -->
    <div class="col-md-8">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-rupee-sign mr-1"></i> Collect EMI Payment</h3>
            </div>
            <form action="<?= site_url('admin/loans/record_payment/' . ($loan->id ?? '')) ?>" method="post" id="collectionForm">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <input type="hidden" name="loan_id" value="<?= $loan->id ?>">
                <input type="hidden" name="payment_type" id="payment_type" value="emi">
                <input type="hidden" name="total_amount" id="total_amount" value="<?= $loan->emi_amount ?>">
                <?php 
                    // Get next pending installment for interest-only calculation
                    $next_pending = null;
                    if (!empty($pending_emis)) {
                        $next_pending = $pending_emis[0] ?? null;
                    } elseif (!empty($overdue_emis)) {
                        $next_pending = $overdue_emis[0] ?? null;
                    }
                    $interest_for_next = $next_pending ? ($next_pending->interest_amount - $next_pending->interest_paid) : 0;
                    $principal_for_next = $next_pending ? ($next_pending->principal_amount - $next_pending->principal_paid) : 0;
                ?>
                <input type="hidden" name="installment_id" id="installment_id" value="<?= $next_pending->id ?? '' ?>">
                
                <div class="card-body">
                    
                    <!-- ═══ STEP 1: Select Payment Type ═══ -->
                    <label class="d-block mb-2"><i class="fas fa-layer-group mr-1"></i> Step 1: Select Payment Type</label>
                    <div class="row mb-3">
                        <!-- Regular EMI -->
                        <div class="col-md-3 col-6 mb-2">
                            <div class="pay-type-card active" data-type="emi" data-amount="<?= $loan->emi_amount ?>">
                                <div class="pay-type-icon bg-success"><i class="fas fa-calendar-check"></i></div>
                                <div class="pay-type-label">Regular EMI</div>
                                <div class="pay-type-amount"><?= format_amount($loan->emi_amount, 0) ?></div>
                                <div class="pay-type-desc">Full EMI (Principal + Interest)</div>
                                <div class="pay-type-check"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                        <!-- Interest Only -->
                        <?php if ($next_pending && $interest_for_next > 0): ?>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="pay-type-card" data-type="interest_only" data-amount="<?= round($interest_for_next, 2) ?>">
                                <div class="pay-type-icon bg-warning"><i class="fas fa-percentage"></i></div>
                                <div class="pay-type-label">Interest Only</div>
                                <div class="pay-type-amount"><?= format_amount($interest_for_next, 0) ?></div>
                                <div class="pay-type-desc">Skip principal, extend tenure</div>
                                <div class="pay-type-check"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <!-- Multi EMI -->
                        <div class="col-md-3 col-6 mb-2">
                            <div class="pay-type-card" data-type="multi_emi" data-amount="<?= $loan->emi_amount * 2 ?>">
                                <div class="pay-type-icon bg-primary"><i class="fas fa-layer-group"></i></div>
                                <div class="pay-type-label">Multi EMI</div>
                                <div class="pay-type-amount"><?= format_amount($loan->emi_amount * 2, 0) ?>+</div>
                                <div class="pay-type-desc">Pay 2 or more EMIs at once</div>
                                <div class="pay-type-check"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                        <!-- Full Settlement -->
                        <div class="col-md-3 col-6 mb-2">
                            <div class="pay-type-card" data-type="settlement" data-amount="<?= $loan->outstanding_principal + $loan->outstanding_interest ?>">
                                <div class="pay-type-icon bg-danger"><i class="fas fa-flag-checkered"></i></div>
                                <div class="pay-type-label">Settlement</div>
                                <div class="pay-type-amount"><?= format_amount($loan->outstanding_principal + $loan->outstanding_interest, 0) ?></div>
                                <div class="pay-type-desc">Close loan in full</div>
                                <div class="pay-type-check"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($overdue_emis): ?>
                    <!-- Overdue Quick Action -->
                    <div class="callout callout-danger py-2 mb-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <i class="fas fa-exclamation-triangle text-danger mr-1"></i>
                                <strong><?= count($overdue_emis) ?> Overdue EMI(s)</strong> — 
                                Total: <strong class="text-danger"><?= get_currency_symbol() ?><?= number_format(array_sum(array_column($overdue_emis, 'emi_amount'))) ?></strong>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger pay-type-card-btn" 
                                    data-type="emi" data-amount="<?= array_sum(array_column($overdue_emis, 'emi_amount')) ?>">
                                <i class="fas fa-bolt mr-1"></i> Pay All Overdue
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ═══ Interest-Only Detail Card (shown when Interest Only selected) ═══ -->
                    <div class="card card-outline card-warning d-none mb-3" id="interestOnlyCard">
                        <div class="card-header py-2 bg-gradient-warning">
                            <h3 class="card-title text-white">
                                <i class="fas fa-info-circle mr-1"></i> Interest-Only Payment — What Happens?
                            </h3>
                        </div>
                        <div class="card-body py-3">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <div class="d-flex align-items-start">
                                        <span class="badge badge-warning badge-pill mr-2 mt-1">1</span>
                                        <div>
                                            <strong>You Pay Interest</strong>
                                            <div class="text-warning h5 mb-0"><?= get_currency_symbol() ?><span id="interestDueAmount"><?= number_format($interest_for_next, 2) ?></span></div>
                                            <small class="text-muted">Covers this month's interest charge</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="d-flex align-items-start">
                                        <span class="badge badge-info badge-pill mr-2 mt-1">2</span>
                                        <div>
                                            <strong>Principal Deferred</strong>
                                            <div class="text-info h5 mb-0"><?= get_currency_symbol() ?><span id="principalDeferredAmount"><?= number_format($principal_for_next, 2) ?></span></div>
                                            <small class="text-muted">Moved to new installment at end</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="d-flex align-items-start">
                                        <span class="badge badge-danger badge-pill mr-2 mt-1">3</span>
                                        <div>
                                            <strong>Tenure Extends</strong>
                                            <div class="text-danger h5 mb-0"><span id="newTenure"><?= ($loan->tenure_months ?? 0) + 1 ?></span> months</div>
                                            <small class="text-muted">Was <?= $loan->original_tenure_months ?? $loan->tenure_months ?> months originally</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <small class="text-muted">
                                    <i class="fas fa-history mr-1"></i>
                                    Extensions used: <strong><span id="extensionsUsed"><?= $loan->tenure_extensions ?? 0 ?></span></strong> of <strong><span id="maxExtensions"><?= $loan->max_tenure_extensions ?? 6 ?></span></strong> allowed
                                </small>
                                <?php 
                                    $ext_used = $loan->tenure_extensions ?? 0;
                                    $ext_max = $loan->max_tenure_extensions ?? 6;
                                    $ext_pct = $ext_max > 0 ? round(($ext_used / $ext_max) * 100) : 0;
                                    $ext_color = $ext_pct >= 80 ? 'danger' : ($ext_pct >= 50 ? 'warning' : 'success');
                                ?>
                                <div style="width:120px">
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-<?= $ext_color ?>" style="width:<?= $ext_pct ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══ Multi EMI Selector (shown when Multi EMI selected) ═══ -->
                    <div class="card card-outline card-primary d-none mb-3" id="multiEmiCard">
                        <div class="card-header py-2">
                            <h3 class="card-title"><i class="fas fa-list-ol mr-1"></i> How many EMIs?</h3>
                        </div>
                        <div class="card-body py-2">
                            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                <label class="btn btn-outline-primary active emi-count-btn" data-count="2">
                                    <input type="radio" name="emi_count" value="2" checked> 2 EMIs<br>
                                    <small><?= format_amount($loan->emi_amount * 2, 0) ?></small>
                                </label>
                                <label class="btn btn-outline-primary emi-count-btn" data-count="3">
                                    <input type="radio" name="emi_count" value="3"> 3 EMIs<br>
                                    <small><?= format_amount($loan->emi_amount * 3, 0) ?></small>
                                </label>
                                <label class="btn btn-outline-primary emi-count-btn" data-count="4">
                                    <input type="radio" name="emi_count" value="4"> 4 EMIs<br>
                                    <small><?= format_amount($loan->emi_amount * 4, 0) ?></small>
                                </label>
                                <label class="btn btn-outline-primary emi-count-btn" data-count="6">
                                    <input type="radio" name="emi_count" value="6"> 6 EMIs<br>
                                    <small><?= format_amount($loan->emi_amount * 6, 0) ?></small>
                                </label>
                                <label class="btn btn-outline-primary emi-count-btn" data-count="12">
                                    <input type="radio" name="emi_count" value="12"> 12 EMIs<br>
                                    <small><?= format_amount($loan->emi_amount * 12, 0) ?></small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">
                    
                    <!-- ═══ STEP 2: Amount & Date ═══ -->
                    <label class="d-block mb-2"><i class="fas fa-rupee-sign mr-1"></i> Step 2: Payment Details</label>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="amount">Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-lg text-center font-weight-bold" id="amount" name="amount" 
                                       value="<?= $loan->emi_amount ?>" required min="1" step="0.01"
                                       placeholder="Enter amount" autofocus>
                                <small class="form-text text-muted text-center" id="amountHint">
                                    EMI: <?= format_amount($loan->emi_amount, 0) ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-lg" id="payment_date" name="payment_date" 
                                       value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="payment_mode">Payment Mode <span class="text-danger">*</span></label>
                                <select class="form-control form-control-lg" id="payment_mode" name="payment_mode" required>
                                    <option value="cash">Cash</option>
                                    <option value="upi">UPI</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="online">Online</option>
                                    <option value="adjustment">Adjustment</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_reference">Reference / Transaction ID</label>
                                <input type="text" class="form-control" id="payment_reference" name="payment_reference" 
                                       placeholder="UPI ID / UTR / Cheque No">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="remarks">Remarks <small class="text-muted">(optional)</small></label>
                                <input type="text" class="form-control" id="remarks" name="remarks" placeholder="Payment notes">
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <!-- ═══ STEP 3: Payment Breakdown ═══ -->
                    <label class="d-block mb-2"><i class="fas fa-chart-pie mr-1"></i> Step 3: Payment Breakdown <small class="text-muted">(auto-calculated as per RBI norms: Fine → Interest → Principal)</small></label>
                    <div class="row mb-3">
                        <div class="col-md-3 col-6">
                            <div class="card bg-light mb-2">
                                <div class="card-body py-2 text-center">
                                    <small class="text-muted d-block">Towards Fine</small>
                                    <div class="h5 mb-0" id="breakdownFine"><?= get_currency_symbol() ?>0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="card bg-light mb-2">
                                <div class="card-body py-2 text-center">
                                    <small class="text-muted d-block">Towards Interest</small>
                                    <div class="h5 mb-0 text-warning" id="breakdownInterest"><?= get_currency_symbol() ?>0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="card bg-light mb-2">
                                <div class="card-body py-2 text-center">
                                    <small class="text-muted d-block">Towards Principal</small>
                                    <div class="h5 mb-0 text-primary" id="breakdownPrincipal"><?= get_currency_symbol() ?>0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="card bg-light mb-2">
                                <div class="card-body py-2 text-center">
                                    <small class="text-muted d-block">EMIs Covered</small>
                                    <div class="h5 mb-0 text-success" id="emisCovered">1</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Summary Bar -->
                    <div class="card card-outline mb-0" id="summaryCard">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <span id="paymentTypeLabel" class="badge badge-success badge-lg mr-2">Regular EMI</span>
                                    <span class="text-muted">New Outstanding:</span>
                                    <strong class="ml-1" id="newOutstanding"><?= format_amount($loan->outstanding_principal, 0) ?></strong>
                                </div>
                                <div class="text-right">
                                    <span class="text-muted">Paying:</span>
                                    <strong class="h4 mb-0 ml-1 text-success" id="payingAmount"><?= format_amount($loan->emi_amount, 0) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                        <i class="fas fa-check mr-1"></i> <span id="submitBtnText">Record Payment</span>
                    </button>
                    <a href="<?= site_url('admin/loans/view/' . $loan->id) ?>" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Recent Payments -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-1"></i> Recent Payments</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Receipt</th>
                            <th class="text-right">Amount</th>
                            <th>Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recent_payments ?? [], 0, 5) as $pmt): ?>
                        <tr>
                            <td><?= format_date($pmt->payment_date) ?></td>
                            <td><small><?= $pmt->receipt_number ?></small></td>
                            <td class="text-right"><?= format_amount($pmt->amount, 0) ?></td>
                            <td><small><?= ucfirst($pmt->payment_mode) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_payments)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No recent payments</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* ─── Payment Type Cards ─── */
.pay-type-card {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 12px 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.25s ease;
    position: relative;
    background: #fff;
    height: 100%;
}
.pay-type-card:hover {
    border-color: #adb5bd;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
    transform: translateY(-1px);
}
.pay-type-card.active {
    border-color: #28a745;
    background: #f0fff4;
    box-shadow: 0 0 0 3px rgba(40,167,69,.15);
}
.pay-type-card[data-type="interest_only"].active {
    border-color: #ffc107;
    background: #fffdf0;
    box-shadow: 0 0 0 3px rgba(255,193,7,.15);
}
.pay-type-card[data-type="settlement"].active {
    border-color: #dc3545;
    background: #fff5f5;
    box-shadow: 0 0 0 3px rgba(220,53,69,.15);
}
.pay-type-card[data-type="multi_emi"].active {
    border-color: #007bff;
    background: #f0f7ff;
    box-shadow: 0 0 0 3px rgba(0,123,255,.15);
}
.pay-type-icon {
    width: 40px; height: 40px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 16px;
    margin-bottom: 6px;
}
.pay-type-label { font-weight: 700; font-size: 13px; margin-bottom: 2px; }
.pay-type-amount { font-size: 18px; font-weight: 700; color: #333; }
.pay-type-desc { font-size: 11px; color: #888; margin-top: 2px; }
.pay-type-check {
    position: absolute; top: 6px; right: 8px;
    color: #28a745; font-size: 16px;
    display: none;
}
.pay-type-card.active .pay-type-check { display: block; }
.pay-type-card[data-type="interest_only"].active .pay-type-check { color: #ffc107; }
.pay-type-card[data-type="settlement"].active .pay-type-check { color: #dc3545; }
.pay-type-card[data-type="multi_emi"].active .pay-type-check { color: #007bff; }

/* Amount input highlight */
#amount { font-size: 1.5rem; }
#interestOnlyCard, #multiEmiCard { transition: all 0.3s ease; }
</style>

<script>
$(document).ready(function() {
    <?php if ($loan): ?>
    var outstandingPrincipal = <?= $loan->outstanding_principal ?>;
    var outstandingInterest = <?= $loan->outstanding_interest ?>;
    var pendingFines = <?= $pending_fines ?? 0 ?>;
    var emiAmount = <?= $loan->emi_amount ?>;
    var interestForNext = <?= $interest_for_next ?>;
    var principalForNext = <?= $principal_for_next ?>;
    var currentTenure = <?= $loan->tenure_months ?>;
    var selectedType = 'emi';
    var CS = '<?= get_currency_symbol() ?>';
    
    // ─── Payment Type Card Selection ───
    $('.pay-type-card').on('click', function() {
        var $card = $(this);
        var type = $card.data('type');
        var amount = parseFloat($card.data('amount'));
        
        // Update active card
        $('.pay-type-card').removeClass('active');
        $card.addClass('active');
        selectedType = type;
        
        // Set amount
        $('#amount').val(amount);
        
        // Show/hide sub-cards
        $('#interestOnlyCard').addClass('d-none');
        $('#multiEmiCard').addClass('d-none');
        
        if (type === 'interest_only') {
            $('#interestOnlyCard').removeClass('d-none');
            $('#payment_type').val('interest_only');
            $('#collectionForm').attr('action', '<?= site_url('admin/loans/interest_only_payment') ?>');
            $('#submitBtn').removeClass('btn-success').addClass('btn-warning');
            $('#submitBtnText').text('Record Interest-Only Payment');
            $('#paymentTypeLabel').removeClass('badge-success badge-primary badge-danger').addClass('badge-warning').text('Interest Only');
            $('#amountHint').html('<i class="fas fa-info-circle mr-1"></i>Interest portion only — principal deferred');
        } else if (type === 'multi_emi') {
            $('#multiEmiCard').removeClass('d-none');
            $('#payment_type').val('emi');
            setRegularMode();
            $('#paymentTypeLabel').removeClass('badge-success badge-warning badge-danger').addClass('badge-primary').text('Multi EMI');
            $('#amountHint').html('<i class="fas fa-info-circle mr-1"></i>Select number of EMIs above');
        } else if (type === 'settlement') {
            $('#payment_type').val('emi');
            setRegularMode();
            $('#paymentTypeLabel').removeClass('badge-success badge-warning badge-primary').addClass('badge-danger').text('Full Settlement');
            $('#amountHint').html('<i class="fas fa-flag-checkered mr-1"></i>Loan will be closed after this payment');
        } else {
            // Regular EMI
            $('#payment_type').val('emi');
            setRegularMode();
            $('#paymentTypeLabel').removeClass('badge-warning badge-primary badge-danger').addClass('badge-success').text('Regular EMI');
            $('#amountHint').html('EMI: <?= format_amount($loan->emi_amount, 0) ?>');
        }
        
        updateBreakdown(amount);
    });
    
    // Overdue quick pay button
    $('.pay-type-card-btn').on('click', function() {
        var amount = parseFloat($(this).data('amount'));
        var type = $(this).data('type');
        $('.pay-type-card').removeClass('active');
        $('.pay-type-card[data-type="emi"]').addClass('active');
        selectedType = type;
        $('#amount').val(amount);
        setRegularMode();
        $('#paymentTypeLabel').removeClass('badge-warning badge-primary badge-danger').addClass('badge-success').text('Overdue Payment');
        updateBreakdown(amount);
    });
    
    // Multi EMI count buttons
    $('.emi-count-btn').on('click', function() {
        var count = parseInt($(this).data('count'));
        var amount = emiAmount * count;
        $('#amount').val(amount);
        updateBreakdown(amount);
    });
    
    function setRegularMode() {
        $('#collectionForm').attr('action', '<?= site_url('admin/loans/record_payment/' . $loan->id) ?>');
        $('#submitBtn').removeClass('btn-warning').addClass('btn-success');
        $('#submitBtnText').text('Record Payment');
    }
    
    // Amount manual input
    $('#amount').on('input', function() {
        var amount = parseFloat($(this).val()) || 0;
        
        // Auto-detect type from amount if user types manually
        if (amount === emiAmount) {
            highlightCard('emi');
        } else if (Math.abs(amount - interestForNext) < 0.01 && interestForNext > 0) {
            highlightCard('interest_only');
        } else if (amount >= (outstandingPrincipal + outstandingInterest) * 0.95) {
            highlightCard('settlement');
        } else if (amount > emiAmount) {
            highlightCard('multi_emi');
        } else {
            // Partial or custom — keep cards but no highlight
            $('.pay-type-card').removeClass('active');
        }
        
        updateBreakdown(amount);
    });
    
    function highlightCard(type) {
        if (selectedType !== type) return; // Don't auto-switch if user manually selected
        // Soft highlight only — user choice is king
    }
    
    function updateBreakdown(amount) {
        // Update hidden total_amount
        $('#total_amount').val(amount);
        $('#payingAmount').text(CS + amount.toLocaleString('en-IN'));
        
        var remaining = amount;
        var toFine = 0, toInterest = 0, toPrincipal = 0;
        
        if (selectedType === 'interest_only') {
            // Interest-only mode: all goes to interest, no principal
            toInterest = Math.min(remaining, interestForNext);
            remaining -= toInterest;
            
            toFine = Math.min(remaining, pendingFines);
            remaining -= toFine;
            
            toPrincipal = 0;
            
            $('#breakdownFine').text(CS + toFine.toLocaleString('en-IN'));
            $('#breakdownInterest').text(CS + toInterest.toLocaleString('en-IN'));
            $('#breakdownPrincipal').html('<span class="text-muted"><i class="fas fa-forward"></i> Deferred</span>');
            
            var newOutstanding = outstandingPrincipal;
            $('#newOutstanding').text(CS + newOutstanding.toLocaleString('en-IN'));
            $('#emisCovered').html('<span class="badge badge-warning">Interest Only</span>');
        } else {
            // Regular mode: Fine → Interest → Principal (RBI order)
            toFine = Math.min(remaining, pendingFines);
            remaining -= toFine;
            
            toInterest = Math.min(remaining, outstandingInterest);
            remaining -= toInterest;
            
            toPrincipal = Math.min(remaining, outstandingPrincipal);
            
            $('#breakdownFine').text(CS + toFine.toLocaleString('en-IN'));
            $('#breakdownInterest').text(CS + toInterest.toLocaleString('en-IN'));
            $('#breakdownPrincipal').text(CS + toPrincipal.toLocaleString('en-IN'));
            
            var newOutstanding = outstandingPrincipal - toPrincipal;
            $('#newOutstanding').text(CS + newOutstanding.toLocaleString('en-IN'));
            
            var emisCovered = Math.floor(amount / emiAmount);
            if (emisCovered > 1) {
                $('#emisCovered').text(emisCovered + ' EMIs');
            } else if (emisCovered === 1) {
                $('#emisCovered').text('1 EMI');
            } else {
                $('#emisCovered').html('<span class="badge badge-info">Partial</span>');
            }
        }
    }
    
    // Trigger initial calculation
    updateBreakdown(parseFloat($('#amount').val()) || emiAmount);
    
    // Form submit validation
    $('#collectionForm').on('submit', function(e) {
        var amount = parseFloat($('#amount').val());
        if (amount <= 0) {
            e.preventDefault();
            Swal.fire('Error', 'Please enter a valid amount', 'error');
            return false;
        }
        
        if (selectedType === 'interest_only') {
            if (amount < interestForNext) {
                e.preventDefault();
                Swal.fire('Error', 'Amount must be at least ' + CS + interestForNext.toFixed(2) + ' to cover interest.', 'error');
                return false;
            }
            
            e.preventDefault();
            Swal.fire({
                title: 'Confirm Interest-Only Payment',
                html: '<div class="text-left">' +
                      '<p>This will:</p>' +
                      '<ul class="mb-2">' +
                      '<li>Pay <strong>interest only</strong>: ' + CS + interestForNext.toFixed(2) + '</li>' +
                      '<li>Defer principal: ' + CS + principalForNext.toFixed(2) + '</li>' +
                      '<li>Add 1 extra installment at end of schedule</li>' +
                      '<li>Extend tenure to <strong>' + (currentTenure + 1) + ' months</strong></li>' +
                      '</ul>' +
                      '<div class="alert alert-warning py-2 mb-0"><small><i class="fas fa-exclamation-triangle mr-1"></i>This action cannot be easily reversed.</small></div>' +
                      '</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                confirmButtonText: '<i class="fas fa-check mr-1"></i> Yes, Record Interest-Only',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Recording interest-only payment...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: function() { Swal.showLoading(); }
                    });
                    document.getElementById('collectionForm').submit();
                }
            });
            return false;
        }
        
        // Regular / multi / settlement confirmation
        var typeLabel = selectedType === 'settlement' ? 'Full Settlement' : 'EMI Payment';
        Swal.fire({
            title: 'Processing ' + typeLabel + '...',
            text: 'Recording payment of ' + CS + amount.toLocaleString('en-IN'),
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: function() { Swal.showLoading(); }
        });
    });
    <?php endif; ?>
});
</script>
