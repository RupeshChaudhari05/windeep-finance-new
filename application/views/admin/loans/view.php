<div class="row">
    <!-- Loan Info Card -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-contract mr-1"></i> Loan Details</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="mb-0"><?= $loan->loan_number ?></h4>
                    <?php
                    $status_class = ['active' => 'success', 'overdue' => 'warning', 'npa' => 'danger', 'closed' => 'secondary', 'foreclosed' => 'dark'];
                    ?>
                    <span class="badge badge-<?= $status_class[$loan->status] ?? 'secondary' ?> badge-lg mt-1">
                        <?= strtoupper($loan->status) ?>
                    </span>
                </div>
                
                <table class="table table-sm table-borderless">
                    <tr>
                        <th>Member:</th>
                        <td>
                            <a href="<?= site_url('admin/members/view/' . $loan->member_id) ?>">
                                <?= $member->first_name ?> <?= $member->last_name ?>
                            </a>
                            <br><small class="text-muted"><?= $member->member_code ?></small>
                        </td>
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
                        <th>Interest Rate:</th>
                        <td><?= $loan->interest_rate ?>% p.a. (<?= ucfirst($loan->interest_type) ?>)</td>
                    </tr>
                    <tr>
                        <th>Tenure:</th>
                        <td><?= $loan->tenure_months ?> months</td>
                    </tr>
                    <tr>
                        <th>EMI Date:</th>
                        <td><?= $loan->emi_date_formatted ?> of every month</td>
                    </tr>
                    <tr>
                        <th>Disbursed On:</th>
                        <td><?= format_date($loan->disbursement_date) ?></td>
                    </tr>
                    <tr>
                        <th>First EMI:</th>
                        <td><?= format_date($loan->first_emi_date) ?></td>
                    </tr>
                    <tr>
                        <th>Last EMI:</th>
                        <td><?= format_date($loan->last_emi_date) ?></td>
                    </tr>
                </table>
                
                <hr>
                
                <!-- Amount Summary -->
                <div class="info-box bg-primary mb-2">
                    <span class="info-box-icon"><i class="fas fa-money-bill"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Principal Amount<?= !empty($loan->is_tranche) ? ' (sanctioned)' : '' ?></span>
                        <span class="info-box-number"><?= format_amount($loan->principal_amount, 0) ?></span>
                        <?php if (!empty($loan->is_tranche)): ?><small>Disbursed <?= format_amount($disb_done, 0) ?> · left <?= format_amount($disb_pending, 0) ?></small><?php endif; ?>
                    </div>
                </div>
                
                <div class="info-box bg-warning mb-2">
                    <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Interest</span>
                        <span class="info-box-number"><?= format_amount($loan->total_interest, 0) ?></span>
                    </div>
                </div>
                
                <div class="info-box bg-info mb-2">
                    <span class="info-box-icon"><i class="fas fa-calculator"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">EMI Amount</span>
                        <span class="info-box-number"><?= format_amount($loan->emi_amount, 0) ?></span>
                    </div>
                </div>
                
                <div class="info-box bg-danger mb-2">
                    <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Outstanding Principal</span>
                        <span class="info-box-number"><?= format_amount($loan->outstanding_principal, 0) ?></span>
                    </div>
                </div>
                
                <div class="info-box bg-success mb-2">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Paid</span>
                        <span class="info-box-number"><?= format_amount($loan->total_paid, 0) ?></span>
                    </div>
                </div>
                
                <?php if (!empty($loan->is_tranche) || count($disb_parts ?? []) > 1): ?>
                <!-- Disbursement in parts -->
                <div class="card card-outline card-primary mb-2" id="disbursementCard">
                    <div class="card-header py-2">
                        <h3 class="card-title"><i class="fas fa-layer-group mr-1"></i> Disbursement in parts</h3>
                    </div>
                    <div class="card-body p-2">
                        <?php $pct = $loan->principal_amount > 0 ? min(100, round($disb_done / $loan->principal_amount * 100)) : 100; ?>
                        <table class="table table-sm mb-2">
                            <tr><td>Sanctioned (EMI on this)</td><td class="text-right"><strong><?= format_amount($loan->principal_amount, 0) ?></strong></td></tr>
                            <tr><td>Disbursed</td><td class="text-right text-success"><strong><?= format_amount($disb_done, 0) ?></strong></td></tr>
                            <tr><td>Left to disburse</td><td class="text-right <?= $disb_pending > 0 ? 'text-danger' : '' ?>"><strong><?= format_amount($disb_pending, 0) ?></strong></td></tr>
                        </table>
                        <div class="progress progress-sm mb-2"><div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div></div>
                        <table class="table table-sm table-striped mb-2" style="font-size:12px">
                            <thead><tr><th>#</th><th>Date</th><th class="text-right">Amount</th><th>Bank</th></tr></thead>
                            <tbody>
                            <?php foreach ($disb_parts as $p): ?>
                                <tr class="<?= $p->status !== 'completed' ? 'text-muted' : '' ?>">
                                    <td><?= (int) $p->tranche_no ?></td>
                                    <td><?= format_date($p->disbursement_date) ?></td>
                                    <td class="text-right">
                                        <?= format_amount($p->disbursement_amount, 0) ?>
                                        <?php if ((float) $p->processing_fee > 0): ?><br><small class="text-muted">fee <?= format_amount($p->processing_fee, 0) ?> · paid <?= format_amount($p->net_amount, 0) ?></small><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p->status !== 'completed'): ?><span class="badge badge-secondary"><?= htmlspecialchars($p->status) ?></span>
                                        <?php elseif ($p->bank_transaction_id): ?><span class="badge badge-success" title="<?= htmlspecialchars((string) $p->bank_description) ?>"><i class="fas fa-link"></i> mapped</span>
                                        <?php else: ?><span class="badge badge-warning" title="Map the bank debit in Bank Mapping → Loan disbursement">not mapped</span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (!empty($loan->is_tranche) && $disb_pending > 0 && in_array($loan->status, ['active', 'npa'], true)): ?>
                        <button type="button" class="btn btn-primary btn-sm btn-block" data-toggle="modal" data-target="#addDisbursementModal">
                            <i class="fas fa-plus mr-1"></i> Add disbursement
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($loan->is_tranche) && $disb_pending > 0): ?>
                <div class="modal fade" id="addDisbursementModal" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                        <form method="post" action="<?= site_url('admin/loans/add_disbursement/' . $loan->id) ?>">
                            <div class="modal-header"><h5 class="modal-title">Add disbursement — <?= htmlspecialchars($loan->loan_number) ?></h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button></div>
                            <div class="modal-body">
                                <p class="small text-muted mb-2">Left to disburse: <strong><?= format_amount($disb_pending) ?></strong>. EMI does not change.</p>
                                <div class="form-group">
                                    <label>Bank debit line (optional — maps it in Bank Mapping)</label>
                                    <select name="bank_transaction_id" id="disbBankLine" class="form-control" data-no-search="1">
                                        <option value="">— not from bank / map later —</option>
                                        <?php foreach ($disb_bank_lines as $b): $left = (float) $b->amount - (float) $b->mapped_amount; ?>
                                        <option value="<?= $b->id ?>" data-amount="<?= $left ?>" data-date="<?= $b->transaction_date ?>">
                                            <?= format_date($b->transaction_date) ?> · <?= format_amount($left) ?> · <?= htmlspecialchars(mb_substr((string) $b->description, 0, 60)) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-6"><label>Amount *</label>
                                        <input type="number" step="0.01" min="1" max="<?= $disb_pending ?>" name="amount" id="disbAmount" class="form-control" required></div>
                                    <div class="form-group col-6"><label>Date *</label>
                                        <input type="date" name="date" id="disbDate" class="form-control" max="<?= date('Y-m-d') ?>" min="<?= $loan->disbursement_date ?>" value="<?= date('Y-m-d') ?>" required></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-6"><label>Mode</label>
                                        <select name="mode" class="form-control" data-no-search="1">
                                            <option value="bank_transfer">Bank transfer</option><option value="cheque">Cheque</option><option value="upi">UPI</option><option value="cash">Cash</option>
                                        </select></div>
                                    <div class="form-group col-6"><label>Reference</label><input type="text" name="reference" class="form-control" maxlength="100"></div>
                                </div>
                                <div class="form-group mb-0"><label>Remarks</label><input type="text" name="remarks" class="form-control" maxlength="255"></div>
                            </div>
                            <div class="modal-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i> Save disbursement</button></div>
                        </form>
                    </div></div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var sel = document.getElementById('disbBankLine');
                    if (!sel) return;
                    sel.addEventListener('change', function () {
                        var o = sel.options[sel.selectedIndex];
                        if (o && o.dataset.amount) { document.getElementById('disbAmount').value = o.dataset.amount; document.getElementById('disbDate').value = o.dataset.date; }
                    });
                });
                </script>
                <?php endif; ?>
                <?php endif; ?>

                <?php if ($overdue_count > 0): ?>
                <div class="card card-danger">
                    <div class="card-body py-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong><?= $overdue_count ?> EMI(s) Overdue!</strong>
                        <br>
                        Amount: <?= format_amount($overdue_amount, 0) ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($loan->status == 'active' || $loan->status == 'overdue'): ?>
                <hr>
                <a href="<?= site_url('admin/loans/collect/' . $loan->id) ?>" class="btn btn-success btn-block">
                    <i class="fas fa-rupee-sign mr-1"></i> Collect EMI
                </a>
                <a href="<?= site_url('admin/loans/part_payment/' . $loan->id) ?>" class="btn btn-warning btn-block mt-2">
                    <i class="fas fa-hand-holding-usd mr-1"></i> Part Payment (Prepayment)
                </a>
                <a href="<?= site_url('admin/loans/topup/' . $loan->id) ?>" class="btn btn-primary btn-block mt-2">
                    <i class="fas fa-level-up-alt mr-1"></i> Loan Top-up
                </a>
                <a href="<?= site_url('admin/loans/repayment_history?loan_id=' . $loan->id) ?>" class="btn btn-info btn-block mt-2">
                    <i class="fas fa-history mr-1"></i> View Repayment History
                </a>
                <?php else: ?>
                <hr>
                <a href="<?= site_url('admin/loans/repayment_history?loan_id=' . $loan->id) ?>" class="btn btn-info btn-block">
                    <i class="fas fa-history mr-1"></i> View Repayment History
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Guarantors -->
        <?php if (!empty($guarantors)): ?>
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-friends mr-1"></i> Guarantors</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($guarantors as $g): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="<?= site_url('admin/members/view/' . $g->guarantor_member_id) ?>">
                                    <?= trim($g->first_name . ' ' . $g->last_name) ?>
                                </a>
                                <br><small class="text-muted"><?= $g->member_code ?></small>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-<?= $g->consent_status == 'accepted' ? 'success' : ($g->consent_status == 'rejected' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($g->consent_status) ?>
                                </span>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Tabs Section -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link active" href="#schedule" data-toggle="tab">
                            <i class="fas fa-calendar mr-1"></i> EMI Schedule
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#payments" data-toggle="tab">
                            <i class="fas fa-history mr-1"></i> Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#fines" data-toggle="tab">
                            <i class="fas fa-gavel mr-1"></i> Fines
                            <?php if (!empty($fines)): ?>
                                <span class="badge badge-danger"><?= count($fines) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#part_payments" data-toggle="tab">
                            <i class="fas fa-hand-holding-usd mr-1"></i> Part Payments
                            <?php if (!empty($part_payment_history)): ?>
                                <span class="badge badge-warning"><?= count($part_payment_history) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- EMI Schedule Tab -->
                    <div class="active tab-pane" id="schedule">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="thead-light" style="position: sticky; top: 0;">
                                    <tr>
                                        <th>No</th>
                                        <th>Due Date</th>
                                        <th class="text-right">EMI</th>
                                        <th class="text-right">Principal</th>
                                        <th class="text-right">Interest</th>
                                        <th class="text-right">Balance</th>
                                        <th>Status</th>
                                        <th>Paid Date</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Installments cancelled when the loan was closed early are not
                                    // payable, so they are left out of the schedule. The count is
                                    // noted under the table so the numbering gap is explained.
                                    $cancelled_emis = 0;
                                    foreach ($installments as $emi) {
                                        if ($emi->status === 'cancelled') { $cancelled_emis++; }
                                    }
                                    ?>
                                    <?php foreach ($installments as $emi): ?>
                                    <?php if ($emi->status === 'cancelled') { continue; } ?>
                                    <tr class="<?= $emi->status == 'pending' && (!empty($emi->due_date) && safe_timestamp($emi->due_date) < time()) ? 'table-danger' : '' ?>
                                               <?= $emi->status == 'paid' ? 'table-success' : '' ?>">
                                        <td><?= $emi->installment_number ?></td>
                                        <td><?= format_date($emi->due_date) ?></td>
                                        <td class="text-right"><?= format_amount($emi->emi_amount, 0) ?></td>
                                        <td class="text-right"><?= format_amount($emi->principal_component, 0) ?></td>
                                        <td class="text-right"><?= format_amount($emi->interest_component, 0) ?></td>
                                        <td class="text-right font-weight-bold"><?= format_amount($emi->outstanding_after, 0) ?></td>
                                        <td>
                                            <?php
                                            $emi_status = ['pending' => 'warning', 'paid' => 'success', 'partial' => 'info', 'skipped' => 'secondary', 'cancelled' => 'dark', 'overdue' => 'danger', 'waived' => 'secondary', 'interest_only' => 'info'];
                                            ?>
                                            <span class="badge badge-<?= $emi_status[$emi->status] ?? 'secondary' ?>">
                                                <?= ucfirst($emi->status) ?>
                                            </span>
                                            <?php if ($emi->skip_reason): ?>
                                                <i class="fas fa-info-circle text-info" title="<?= $emi->skip_reason ?>"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= ($emi->actual_payment_date ?? $emi->paid_date) ? format_date($emi->actual_payment_date ?? $emi->paid_date) : '-' ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($emi->payment_reference)): ?>
                                                <small class="text-info" title="Bank/Payment Reference"><?= htmlspecialchars($emi->payment_reference) ?></small>
                                            <?php elseif (!empty($emi->bank_transaction_id)): ?>
                                                <small class="text-info"><i class="fas fa-university"></i> Bank #<?= $emi->bank_transaction_id ?></small>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if ($cancelled_emis > 0): ?>
                            <p class="text-muted small mb-0 px-2 py-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                <?= $cancelled_emis ?> remaining installment<?= $cancelled_emis > 1 ? 's' : '' ?>
                                cancelled when this loan was closed &mdash; not payable, so not listed.
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Payments Tab -->
                    <div class="tab-pane" id="payments">
                        <?php if (empty($payments)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-receipt fa-3x mb-3"></i>
                                <p>No payments recorded yet</p>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <a href="<?= site_url('admin/loans/repayment_history?loan_id=' . $loan->id) ?>" class="btn btn-sm btn-info float-right">
                                    <i class="fas fa-history mr-1"></i> Detailed History
                                </a>
                                <h6><i class="fas fa-rupee-sign mr-2"></i>Payment History</h6>
                            </div>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover">
                                    <thead class="thead-light" style="position: sticky; top: 0;">
                                        <tr>
                                            <th>Date</th>
                                            <th>Payment Code</th>
                                            <th>Type</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Principal</th>
                                            <th class="text-right">Interest</th>
                                            <th class="text-right">Fine</th>
                                            <th>Mode</th>
                                            <th>Reference</th>
                                            <th class="text-center">Receipt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $pmt): ?>
                                        <tr>
                                            <td><?= format_date($pmt->payment_date) ?></td>
                                            <td><small class="text-primary"><?= $pmt->payment_code ?? ($pmt->receipt_number ?? 'N/A') ?></small></td>
                                            <td>
                                                <?php
                                                $type_badges = [
                                                    'emi' => 'primary',
                                                    'part_payment' => 'info',
                                                    'advance_payment' => 'success',
                                                    'foreclosure' => 'warning',
                                                    'fine_payment' => 'danger'
                                                ];
                                                $badge_class = $type_badges[$pmt->payment_type ?? 'emi'] ?? 'secondary';
                                                ?>
                                                <span class="badge badge-<?= $badge_class ?> badge-sm">
                                                    <?= ucfirst(str_replace('_', ' ', $pmt->payment_type ?? 'emi')) ?>
                                                </span>
                                            </td>
                                            <td class="text-right font-weight-bold"><?= format_amount($pmt->total_amount ?? $pmt->amount ?? 0) ?></td>
                                            <td class="text-right"><?= format_amount($pmt->principal_component ?? 0) ?></td>
                                            <td class="text-right"><?= format_amount($pmt->interest_component ?? 0) ?></td>
                                            <td class="text-right">
                                                <?php if (($pmt->fine_component ?? 0) > 0): ?>
                                                    <span class="text-danger"><?= format_amount($pmt->fine_component) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $mode_icons = [
                                                    'cash' => 'money-bill-wave',
                                                    'bank_transfer' => 'university',
                                                    'cheque' => 'file-invoice-dollar',
                                                    'upi' => 'mobile-alt',
                                                    'auto_debit' => 'credit-card'
                                                ];
                                                $mode = $pmt->payment_mode ?? 'cash';
                                                $icon = $mode_icons[$mode] ?? 'rupee-sign';
                                                ?>
                                                <small><i class="fas fa-<?= $icon ?> mr-1"></i><?= ucfirst(str_replace('_', ' ', $mode)) ?></small>
                                            </td>
                                            <td><small><?= $pmt->reference_number ?: '-' ?></small></td>
                                            <td class="text-center">
                                                <a href="<?= site_url('admin/loans/payment_receipt/' . $pmt->id) ?>" target="_blank" class="btn btn-xs btn-outline-primary" title="View Receipt">
                                                    <i class="fas fa-receipt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-primary">
                                        <tr>
                                            <th colspan="3" class="text-right">Total:</th>
                                            <th class="text-right"><?= get_currency_symbol() ?><?= number_format(array_sum(array_map(function($p) { return $p->total_amount ?? $p->amount ?? 0; }, $payments)), 2) ?></th>
                                            <th class="text-right"><?= get_currency_symbol() ?><?= number_format(array_sum(array_map(function($p) { return $p->principal_component ?? 0; }, $payments)), 2) ?></th>
                                            <th class="text-right"><?= get_currency_symbol() ?><?= number_format(array_sum(array_map(function($p) { return $p->interest_component ?? 0; }, $payments)), 2) ?></th>
                                            <th class="text-right"><?= get_currency_symbol() ?><?= number_format(array_sum(array_map(function($p) { return $p->fine_component ?? 0; }, $payments)), 2) ?></th>
                                            <th colspan="3"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Fines Tab -->
                    <div class="tab-pane" id="fines">
                        <?php if (empty($fines)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                <p>No fines or penalties</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Reason</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Paid</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fines as $fine): ?>
                                        <tr>
                                            <td><?= format_date($fine->fine_date) ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $fine->fine_type)) ?></td>
                                            <td><small><?= htmlspecialchars($fine->remarks ?? '') ?></small></td>
                                            <td class="text-right"><?= format_amount($fine->fine_amount, 0) ?></td>
                                            <td class="text-right"><?= format_amount($fine->paid_amount, 0) ?></td>
                                            <td>
                                                <?php
                                                $fine_status = ['pending' => 'warning', 'partial' => 'info', 'paid' => 'success', 'waived' => 'secondary'];
                                                ?>
                                                <span class="badge badge-<?= $fine_status[$fine->status] ?? 'secondary' ?>">
                                                    <?= ucfirst($fine->status) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Part Payments Tab -->
                    <div class="tab-pane" id="part_payments">
                        <?php if (empty($part_payment_history)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-hand-holding-usd fa-3x mb-3"></i>
                                <p>No part payments recorded</p>
                                <?php if ($loan->status == 'active'): ?>
                                <a href="<?= site_url('admin/loans/part_payment/' . $loan->id) ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-plus mr-1"></i> Make Part Payment
                                </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <?php if ($loan->status == 'active'): ?>
                                <a href="<?= site_url('admin/loans/part_payment/' . $loan->id) ?>" class="btn btn-sm btn-warning float-right">
                                    <i class="fas fa-plus mr-1"></i> New Part Payment
                                </a>
                                <?php endif; ?>
                                <h6><i class="fas fa-hand-holding-usd mr-2"></i>Part Payment History</h6>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Date</th>
                                            <th class="text-right">Amount</th>
                                            <th>Type</th>
                                            <th class="text-right">Old EMI</th>
                                            <th class="text-right">New EMI</th>
                                            <th>Old Tenure</th>
                                            <th>New Tenure</th>
                                            <th class="text-right">Interest Saved</th>
                                            <th>Mode</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($part_payment_history as $pp): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($pp->payment_date)) ?></td>
                                            <td class="text-right font-weight-bold"><?= format_amount($pp->part_payment_amount) ?></td>
                                            <td>
                                                <?php
                                                $pp_badges = ['reduce_emi' => 'primary', 'reduce_tenure' => 'success', 'manual' => 'warning'];
                                                ?>
                                                <span class="badge badge-<?= $pp_badges[$pp->adjustment_type] ?? 'secondary' ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $pp->adjustment_type)) ?>
                                                </span>
                                            </td>
                                            <td class="text-right"><?= format_amount($pp->previous_emi) ?></td>
                                            <td class="text-right text-primary"><?= format_amount($pp->new_emi) ?></td>
                                            <td><?= $pp->previous_tenure ?> mo</td>
                                            <td class="text-success"><?= $pp->new_tenure ?> mo</td>
                                            <td class="text-right text-success"><?= format_amount($pp->interest_savings) ?></td>
                                            <td><small><?= ucfirst(str_replace('_', ' ', $pp->payment_mode)) ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-warning">
                                        <tr>
                                            <th>Total</th>
                                            <th class="text-right"><?= format_amount(array_sum(array_column($part_payment_history, 'part_payment_amount'))) ?></th>
                                            <th colspan="5"></th>
                                            <th class="text-right"><?= format_amount(array_sum(array_column($part_payment_history, 'interest_savings'))) ?></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
