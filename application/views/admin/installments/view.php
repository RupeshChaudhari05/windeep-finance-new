<div class="row">
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-check mr-1"></i> Installment Details</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th>Installment Number:</th>
                        <td><strong class="text-primary">#<?= $installment->installment_number ?></strong></td>
                    </tr>
                    <tr>
                        <th>Loan Number:</th>
                        <td>
                            <a href="<?= site_url('admin/loans/view/' . $installment->loan_id) ?>">
                                <?= $installment->loan_number ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>Member:</th>
                        <td>
                            <a href="<?= site_url('admin/members/view/' . $installment->member_id) ?>">
                                <?= $installment->first_name ?> <?= $installment->last_name ?>
                            </a>
                            <br><small class="text-muted"><?= $installment->member_code ?></small>
                        </td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td><a href="tel:<?= $installment->phone ?>"><?= $installment->phone ?></a></td>
                    </tr>
                    <tr>
                        <th>Product:</th>
                        <td><span class="badge badge-info"><?= $installment->product_name ?></span></td>
                    </tr>
                </table>
                
                <hr>
                
                <table class="table table-sm table-borderless">
                    <tr>
                        <th>Due Date:</th>
                        <td><?= format_date($installment->due_date) ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <?php
                            $status_badges = ['pending' => 'warning', 'paid' => 'success', 'partial' => 'info', 'overdue' => 'danger'];
                            $badge = $status_badges[$installment->status] ?? 'secondary';
                            ?>
                            <span class="badge badge-<?= $badge ?>">
                                <?= ucfirst($installment->status) ?>
                            </span>
                        </td>
                    </tr>
                    <?php if ($installment->paid_date): ?>
                    <tr>
                        <th>Paid Date:</th>
                        <td><?= format_date($installment->paid_date) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <?php if ($installment->status != 'paid'): ?>
        <a href="<?= site_url('admin/loans/collect/' . $installment->loan_id . '?installment_id=' . $installment->id) ?>" class="btn btn-success btn-block">
            <i class="fas fa-rupee-sign mr-1"></i> Collect Payment
        </a>
        <?php endif; ?>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-rupee-sign mr-1"></i> Amount Breakdown</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="200">EMI Amount</th>
                        <td class="text-right"><strong class="text-primary"><?= format_amount($installment->emi_amount) ?></strong></td>
                    </tr>
                    <tr>
                        <th>Principal Component</th>
                        <td class="text-right"><?= format_amount($installment->principal_amount) ?></td>
                    </tr>
                    <tr>
                        <th>Interest Component</th>
                        <td class="text-right"><?= format_amount($installment->interest_amount) ?></td>
                    </tr>
                    <tr class="table-success">
                        <th>Total Paid</th>
                        <td class="text-right"><strong><?= format_amount($installment->total_paid) ?></strong></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div class="progress" style="height: 25px;">
                                <?php $paid_percent = $installment->emi_amount > 0 ? ($installment->total_paid / $installment->emi_amount) * 100 : 0; ?>
                                <div class="progress-bar bg-success" style="width: <?= $paid_percent ?>%">
                                    <?= number_format($paid_percent, 1) ?>% Paid
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Principal Paid</th>
                        <td class="text-right text-success"><?= format_amount($installment->principal_paid) ?></td>
                    </tr>
                    <tr>
                        <th>Interest Paid</th>
                        <td class="text-right text-success"><?= format_amount($installment->interest_paid) ?></td>
                    </tr>
                    <tr class="table-danger">
                        <th>Outstanding Balance</th>
                        <td class="text-right"><strong><?= format_amount($installment->emi_amount - $installment->total_paid) ?></strong></td>
                    </tr>
                </table>
                
                <h5 class="mt-4"><i class="fas fa-chart-line mr-1"></i> Outstanding Principal</h5>
                <table class="table table-sm table-bordered">
                    <tr>
                        <th>Before This EMI</th>
                        <td class="text-right"><?= format_amount($installment->outstanding_principal_before) ?></td>
                    </tr>
                    <tr>
                        <th>After This EMI</th>
                        <td class="text-right"><?= format_amount($installment->outstanding_principal_after) ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-receipt mr-1"></i> Payment History</h3>
            </div>
            <div class="card-body">
                <?php if (empty($payments)): ?>
                    <p class="text-muted text-center py-3">No payments recorded yet</p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Payment Code</th>
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
                                <td><?= $pmt->payment_code ?></td>
                                <td class="text-right"><?= format_amount($pmt->total_amount) ?></td>
                                <td class="text-right"><?= format_amount($pmt->principal_component) ?></td>
                                <td class="text-right"><?= format_amount($pmt->interest_component) ?></td>
                                <td><?= ucfirst($pmt->payment_mode) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Fines -->
        <?php if (!empty($fines)): ?>
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title"><i class="fas fa-gavel mr-1"></i> Fines & Penalties</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm">
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
                            <td><?= $fine->reason ?></td>
                            <td class="text-right"><?= format_amount($fine->fine_amount) ?></td>
                            <td class="text-right"><?= format_amount($fine->paid_amount) ?></td>
                            <td>
                                <span class="badge badge-<?= $fine->status == 'paid' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($fine->status) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
