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
                    $status_class = ['active' => 'success', 'overdue' => 'warning', 'npa' => 'danger', 'closed' => 'secondary'];
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
                        <td><?= format_date($loan->disbursement_date, 'd M Y') ?></td>
                    </tr>
                    <tr>
                        <th>First EMI:</th>
                        <td><?= format_date($loan->first_emi_date, 'd M Y') ?></td>
                    </tr>
                    <tr>
                        <th>Last EMI:</th>
                        <td><?= format_date($loan->last_emi_date, 'd M Y') ?></td>
                    </tr>
                </table>
                
                <hr>
                
                <!-- Amount Summary -->
                <div class="info-box bg-primary mb-2">
                    <span class="info-box-icon"><i class="fas fa-money-bill"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Principal Amount</span>
                        <span class="info-box-number">₹<?= number_format($loan->principal_amount) ?></span>
                    </div>
                </div>
                
                <div class="info-box bg-warning mb-2">
                    <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Interest</span>
                        <span class="info-box-number">₹<?= number_format($loan->total_interest) ?></span>
                    </div>
                </div>
                
                <div class="info-box bg-info mb-2">
                    <span class="info-box-icon"><i class="fas fa-calculator"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">EMI Amount</span>
                        <span class="info-box-number">₹<?= number_format($loan->emi_amount) ?></span>
                    </div>
                </div>
                
                <div class="info-box bg-danger mb-2">
                    <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Outstanding Principal</span>
                        <span class="info-box-number">₹<?= number_format($loan->outstanding_principal) ?></span>
                    </div>
                </div>
                
                <div class="info-box bg-success mb-2">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Paid</span>
                        <span class="info-box-number">₹<?= number_format($loan->total_paid) ?></span>
                    </div>
                </div>
                
                <?php if ($overdue_count > 0): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong><?= $overdue_count ?> EMI(s) Overdue!</strong>
                    <br>
                    Amount: ₹<?= number_format($overdue_amount) ?>
                </div>
                <?php endif; ?>
                
                <?php if ($loan->status == 'active' || $loan->status == 'overdue'): ?>
                <hr>
                <a href="<?= site_url('admin/loans/collect/' . $loan->id) ?>" class="btn btn-success btn-block">
                    <i class="fas fa-rupee-sign mr-1"></i> Collect EMI
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
                                    <?= $g->guarantor_name ?>
                                </a>
                                <br><small class="text-muted"><?= $g->guarantor_code ?></small>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-<?= $g->status == 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($g->status) ?>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($installments as $emi): ?>
                                    <tr class="<?= $emi->status == 'pending' && (!empty($emi->due_date) && safe_timestamp($emi->due_date) < time()) ? 'table-danger' : '' ?>
                                               <?= $emi->status == 'paid' ? 'table-success' : '' ?>">
                                        <td><?= $emi->installment_number ?></td>
                                        <td><?= format_date($emi->due_date, 'd M Y') ?></td>
                                        <td class="text-right">₹<?= number_format($emi->emi_amount) ?></td>
                                        <td class="text-right">₹<?= number_format($emi->principal_component) ?></td>
                                        <td class="text-right">₹<?= number_format($emi->interest_component) ?></td>
                                        <td class="text-right font-weight-bold">₹<?= number_format($emi->outstanding_after) ?></td>
                                        <td>
                                            <?php
                                            $emi_status = ['pending' => 'warning', 'paid' => 'success', 'partial' => 'info', 'skipped' => 'secondary'];
                                            ?>
                                            <span class="badge badge-<?= $emi_status[$emi->status] ?? 'secondary' ?>">
                                                <?= ucfirst($emi->status) ?>
                                            </span>
                                            <?php if ($emi->skip_reason): ?>
                                                <i class="fas fa-info-circle text-info" title="<?= $emi->skip_reason ?>"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $emi->paid_date ? format_date($emi->paid_date, 'd M Y') : '-' ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Receipt No</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Principal</th>
                                            <th class="text-right">Interest</th>
                                            <th>Mode</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $pmt): ?>
                                        <tr>
                                            <td><?= format_date($pmt->payment_date) ?></td>
                                            <td><small><?= $pmt->receipt_number ?></small></td>
                                            <td class="text-right font-weight-bold">₹<?= number_format($pmt->amount) ?></td>
                                            <td class="text-right">₹<?= number_format($pmt->principal_component) ?></td>
                                            <td class="text-right">₹<?= number_format($pmt->interest_component) ?></td>
                                            <td><small><?= ucfirst($pmt->payment_mode) ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-primary">
                                        <tr>
                                            <th colspan="2">Total:</th>
                                            <th class="text-right">₹<?= number_format(array_sum(array_column($payments, 'amount'))) ?></th>
                                            <th class="text-right">₹<?= number_format(array_sum(array_column($payments, 'principal_component'))) ?></th>
                                            <th class="text-right">₹<?= number_format(array_sum(array_column($payments, 'interest_component'))) ?></th>
                                            <th></th>
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
                                            <td><small><?= $fine->reason ?></small></td>
                                            <td class="text-right">₹<?= number_format($fine->fine_amount) ?></td>
                                            <td class="text-right">₹<?= number_format($fine->paid_amount) ?></td>
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
                </div>
            </div>
        </div>
    </div>
</div>
