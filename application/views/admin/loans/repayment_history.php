<div class="row">
    <!-- Summary Cards -->
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>₹<?= number_format($total_amount, 2) ?></h3>
                <p>Total Collected</p>
            </div>
            <div class="icon">
                <i class="fas fa-rupee-sign"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>₹<?= number_format($total_principal, 2) ?></h3>
                <p>Principal Recovered</p>
            </div>
            <div class="icon">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>₹<?= number_format($total_interest, 2) ?></h3>
                <p>Interest Earned</p>
            </div>
            <div class="icon">
                <i class="fas fa-percentage"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= count($payments) ?></h3>
                <p>Total Transactions</p>
            </div>
            <div class="icon">
                <i class="fas fa-receipt"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-1"></i> Repayment History
                    <?php if (isset($loan)): ?>
                        - <?= $loan->loan_number ?>
                    <?php endif; ?>
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-toggle="collapse" data-target="#filterSection">
                        <i class="fas fa-filter"></i> Filters
                    </button>
                    <button type="button" class="btn btn-sm btn-success" onclick="exportToExcel()">
                        <i class="fas fa-file-excel mr-1"></i> Export
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">
                        <i class="fas fa-print mr-1"></i> Print
                    </button>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="collapse <?= !empty($filters) ? 'show' : '' ?>" id="filterSection">
                <div class="card-body border-bottom">
                    <form method="get" action="<?= site_url('admin/loans/repayment_history') ?>" class="form-inline">
                        <div class="form-group mr-2 mb-2">
                            <label class="mr-2">Loan:</label>
                            <select name="loan_id" class="form-control form-control-sm" style="width: 200px;">
                                <option value="">All Loans</option>
                                <?php foreach ($all_loans as $loan_opt): ?>
                                    <option value="<?= $loan_opt->id ?>" <?= ($filters['loan_id'] ?? '') == $loan_opt->id ? 'selected' : '' ?>>
                                        <?= $loan_opt->loan_number ?> - <?= $loan_opt->first_name ?> <?= $loan_opt->last_name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mr-2 mb-2">
                            <label class="mr-2">From:</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $filters['date_from'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group mr-2 mb-2">
                            <label class="mr-2">To:</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $filters['date_to'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group mr-2 mb-2">
                            <label class="mr-2">Mode:</label>
                            <select name="payment_mode" class="form-control form-control-sm">
                                <option value="">All Modes</option>
                                <option value="cash" <?= ($filters['payment_mode'] ?? '') == 'cash' ? 'selected' : '' ?>>Cash</option>
                                <option value="bank_transfer" <?= ($filters['payment_mode'] ?? '') == 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                <option value="cheque" <?= ($filters['payment_mode'] ?? '') == 'cheque' ? 'selected' : '' ?>>Cheque</option>
                                <option value="upi" <?= ($filters['payment_mode'] ?? '') == 'upi' ? 'selected' : '' ?>>UPI</option>
                            </select>
                        </div>
                        
                        <div class="form-group mr-2 mb-2">
                            <label class="mr-2">Type:</label>
                            <select name="payment_type" class="form-control form-control-sm">
                                <option value="">All Types</option>
                                <option value="emi" <?= ($filters['payment_type'] ?? '') == 'emi' ? 'selected' : '' ?>>EMI</option>
                                <option value="part_payment" <?= ($filters['payment_type'] ?? '') == 'part_payment' ? 'selected' : '' ?>>Part Payment</option>
                                <option value="foreclosure" <?= ($filters['payment_type'] ?? '') == 'foreclosure' ? 'selected' : '' ?>>Foreclosure</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-sm btn-primary mb-2 mr-2">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="<?= site_url('admin/loans/repayment_history') ?>" class="btn btn-sm btn-secondary mb-2">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </form>
                </div>
            </div>
            
            <div class="card-body p-0">
                <?php if (empty($payments)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                        <p class="text-muted">No payment records found</p>
                        <?php if (!empty($filters)): ?>
                            <a href="<?= site_url('admin/loans/repayment_history') ?>" class="btn btn-primary">
                                <i class="fas fa-redo mr-1"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-striped mb-0" id="paymentsTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Payment Code</th>
                                    <th>Loan Number</th>
                                    <th>Member</th>
                                    <th>Type</th>
                                    <th class="text-right">Amount</th>
                                    <th class="text-right">Principal</th>
                                    <th class="text-right">Interest</th>
                                    <th class="text-right">Fine</th>
                                    <th>Mode</th>
                                    <th>Reference</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= format_date($payment->payment_date, 'd M Y') ?></td>
                                    <td>
                                        <a href="<?= site_url('admin/loans/payment_receipt/' . $payment->id) ?>" target="_blank" class="text-primary" title="View Receipt">
                                            <i class="fas fa-receipt mr-1"></i><?= $payment->payment_code ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('admin/loans/view/' . $payment->loan_id) ?>">
                                            <?= $payment->loan_number ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('admin/members/view/' . $payment->member_id) ?>">
                                            <?= $payment->first_name ?> <?= $payment->last_name ?>
                                        </a>
                                        <br><small class="text-muted"><?= $payment->member_code ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $type_badges = [
                                            'emi' => 'primary',
                                            'part_payment' => 'info',
                                            'advance_payment' => 'success',
                                            'foreclosure' => 'warning',
                                            'fine_payment' => 'danger'
                                        ];
                                        $badge_class = $type_badges[$payment->payment_type] ?? 'secondary';
                                        ?>
                                        <span class="badge badge-<?= $badge_class ?>">
                                            <?= ucfirst(str_replace('_', ' ', $payment->payment_type)) ?>
                                        </span>
                                    </td>
                                    <td class="text-right font-weight-bold">₹<?= number_format($payment->total_amount, 2) ?></td>
                                    <td class="text-right">₹<?= number_format($payment->principal_component, 2) ?></td>
                                    <td class="text-right">₹<?= number_format($payment->interest_component, 2) ?></td>
                                    <td class="text-right">
                                        <?php if ($payment->fine_component > 0): ?>
                                            <span class="text-danger">₹<?= number_format($payment->fine_component, 2) ?></span>
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
                                        $icon = $mode_icons[$payment->payment_mode] ?? 'rupee-sign';
                                        ?>
                                        <i class="fas fa-<?= $icon ?> mr-1"></i><?= ucfirst(str_replace('_', ' ', $payment->payment_mode)) ?>
                                    </td>
                                    <td><small><?= $payment->reference_number ?: '-' ?></small></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= site_url('admin/loans/payment_receipt/' . $payment->id) ?>" class="btn btn-info btn-sm" target="_blank" title="View Receipt">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= site_url('admin/loans/view/' . $payment->loan_id) ?>" class="btn btn-primary btn-sm" title="View Loan">
                                                <i class="fas fa-file-contract"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <th colspan="5" class="text-right">Total:</th>
                                    <th class="text-right">₹<?= number_format($total_amount, 2) ?></th>
                                    <th class="text-right">₹<?= number_format($total_principal, 2) ?></th>
                                    <th class="text-right">₹<?= number_format($total_interest, 2) ?></th>
                                    <th class="text-right">₹<?= number_format($total_fine, 2) ?></th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function exportToExcel() {
    var table = document.getElementById('paymentsTable');
    var html = table.outerHTML;
    var url = 'data:application/vnd.ms-excel,' + escape(html);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'loan_repayment_history_<?= date('Y-m-d') ?>.xls';
    a.click();
}
</script>

<style media="print">
    .btn, .card-tools, #filterSection { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
</style>
