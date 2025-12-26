<!-- Member Statement -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="fas fa-file-invoice-dollar mr-1"></i> Member Statement</h3>
        <div>
            <a href="<?= current_url() ?>?export=pdf&<?= http_build_query($filters ?? []) ?>" class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Member Selection -->
        <form action="" method="get" class="mb-4 no-print">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Select Member <span class="text-danger">*</span></label>
                        <select class="form-control select2" name="member_id" required>
                            <option value="">-- Select Member --</option>
                            <?php foreach ($members_list ?? [] as $m): ?>
                            <option value="<?= $m->id ?>" <?= ($filters['member_id'] ?? '') == $m->id ? 'selected' : '' ?>>
                                <?= $m->member_code ?> - <?= $m->first_name ?> <?= $m->last_name ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date Range</label>
                        <input type="text" class="form-control" id="daterange" name="daterange" 
                               value="<?= $filters['start_date'] ?? date('Y-04-01') ?> - <?= $filters['end_date'] ?? date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Statement Type</label>
                        <select class="form-control" name="statement_type">
                            <option value="all" <?= ($filters['statement_type'] ?? '') == 'all' ? 'selected' : '' ?>>All Transactions</option>
                            <option value="savings" <?= ($filters['statement_type'] ?? '') == 'savings' ? 'selected' : '' ?>>Savings Only</option>
                            <option value="loan" <?= ($filters['statement_type'] ?? '') == 'loan' ? 'selected' : '' ?>>Loan Only</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Generate</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <?php if (!empty($member)): ?>
        <!-- Member Info Header -->
        <div class="row mb-4" id="printArea">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 text-center">
                                <?php if ($member->profile_image): ?>
                                    <img src="<?= base_url('uploads/profile_images/' . $member->profile_image) ?>" class="img-thumbnail" width="100">
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded p-3 mb-2" style="font-size: 40px;">
                                        <?= strtoupper(substr($member->full_name, 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <h4 class="mb-1"><?= $member->full_name ?></h4>
                                <p class="mb-1"><strong>Member Code:</strong> <?= $member->member_code ?></p>
                                <p class="mb-1"><strong>Phone:</strong> <?= $member->phone ?></p>
                                <p class="mb-0"><strong>Address:</strong> <?= $member->address ?></p>
                            </div>
                            <div class="col-md-5">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td><strong>Member Since:</strong></td>
                                        <td><?= date('d M Y', strtotime($member->joining_date)) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Savings Balance:</strong></td>
                                        <td class="text-success font-weight-bold">₹<?= number_format($member_summary['savings_balance'] ?? 0) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Loan Outstanding:</strong></td>
                                        <td class="text-danger font-weight-bold">₹<?= number_format($member_summary['loan_outstanding'] ?? 0) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Pending Fines:</strong></td>
                                        <td class="text-warning font-weight-bold">₹<?= number_format($member_summary['pending_fines'] ?? 0) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statement Period -->
        <div class="alert alert-info mb-4">
            <strong><i class="fas fa-calendar-alt mr-1"></i> Statement Period:</strong> 
            <?= date('d M Y', strtotime($filters['start_date'] ?? date('Y-04-01'))) ?> to 
            <?= date('d M Y', strtotime($filters['end_date'] ?? date('Y-m-d'))) ?>
        </div>
        
        <!-- Savings Section -->
        <?php if (($filters['statement_type'] ?? 'all') != 'loan' && !empty($savings_accounts)): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-piggy-bank mr-1"></i> Savings Accounts</h5>
            </div>
            <?php foreach ($savings_accounts as $acc): ?>
            <div class="card-body border-bottom">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <strong>A/C: <?= $acc->account_number ?></strong>
                        <span class="badge badge-info ml-2"><?= $acc->scheme_name ?></span>
                    </div>
                    <div class="text-right">
                        <strong>Current Balance: <span class="text-success">₹<?= number_format($acc->balance) ?></span></strong>
                    </div>
                </div>
                <table class="table table-sm table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Receipt No</th>
                            <th class="text-right">Deposit</th>
                            <th class="text-right">Withdrawal</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-secondary">
                            <td colspan="5"><strong>Opening Balance</strong></td>
                            <td class="text-right"><strong>₹<?= number_format($acc->opening_balance ?? 0) ?></strong></td>
                        </tr>
                        <?php 
                        $running_balance = $acc->opening_balance ?? 0;
                        foreach ($acc->transactions as $txn): 
                            if ($txn->transaction_type == 'deposit') {
                                $running_balance += $txn->amount;
                            } else {
                                $running_balance -= $txn->amount;
                            }
                        ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($txn->transaction_date)) ?></td>
                            <td><?= $txn->description ?? ucfirst($txn->transaction_type) ?></td>
                            <td><small><?= $txn->receipt_number ?></small></td>
                            <td class="text-right text-success">
                                <?= $txn->transaction_type == 'deposit' ? '₹' . number_format($txn->amount) : '-' ?>
                            </td>
                            <td class="text-right text-danger">
                                <?= $txn->transaction_type == 'withdrawal' ? '₹' . number_format($txn->amount) : '-' ?>
                            </td>
                            <td class="text-right">₹<?= number_format($running_balance) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-primary">
                        <tr>
                            <th colspan="5" class="text-right">Closing Balance:</th>
                            <th class="text-right">₹<?= number_format($running_balance) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Loans Section -->
        <?php if (($filters['statement_type'] ?? 'all') != 'savings' && !empty($loans)): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-hand-holding-usd mr-1"></i> Loan Accounts</h5>
            </div>
            <?php foreach ($loans as $loan): ?>
            <div class="card-body border-bottom">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <strong>Loan No: <?= $loan->loan_number ?></strong>
                        <span class="badge badge-<?= $loan->status == 'closed' ? 'secondary' : 'success' ?> ml-2"><?= ucfirst($loan->status) ?></span>
                    </div>
                    <div class="text-right">
                        <strong>Principal: ₹<?= number_format($loan->principal_amount) ?></strong>
                        <span class="ml-3">Outstanding: <span class="text-danger">₹<?= number_format($loan->outstanding_amount) ?></span></span>
                    </div>
                </div>
                <table class="table table-sm table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>EMI No</th>
                            <th>Due Date</th>
                            <th>Paid Date</th>
                            <th class="text-right">EMI Amount</th>
                            <th class="text-right">Principal</th>
                            <th class="text-right">Interest</th>
                            <th class="text-right">Fine</th>
                            <th class="text-right">Paid</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loan->installments as $inst): ?>
                        <tr class="<?= $inst->status == 'overdue' ? 'table-warning' : '' ?>">
                            <td><?= $inst->installment_number ?></td>
                            <td><?= date('d M Y', strtotime($inst->due_date)) ?></td>
                            <td><?= $inst->paid_date ? date('d M Y', strtotime($inst->paid_date)) : '-' ?></td>
                            <td class="text-right">₹<?= number_format($inst->emi_amount) ?></td>
                            <td class="text-right">₹<?= number_format($inst->principal_amount) ?></td>
                            <td class="text-right">₹<?= number_format($inst->interest_amount) ?></td>
                            <td class="text-right"><?= $inst->fine_amount > 0 ? '₹' . number_format($inst->fine_amount) : '-' ?></td>
                            <td class="text-right text-success"><?= $inst->amount_paid > 0 ? '₹' . number_format($inst->amount_paid) : '-' ?></td>
                            <td>
                                <?php
                                $status_badges = ['pending' => 'secondary', 'paid' => 'success', 'partial' => 'warning', 'overdue' => 'danger'];
                                ?>
                                <span class="badge badge-<?= $status_badges[$inst->status] ?? 'secondary' ?>"><?= ucfirst($inst->status) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-info">
                        <tr>
                            <th colspan="3" class="text-right">Total:</th>
                            <th class="text-right">₹<?= number_format($loan->total_emi ?? 0) ?></th>
                            <th class="text-right">₹<?= number_format($loan->principal_amount) ?></th>
                            <th class="text-right">₹<?= number_format($loan->total_interest ?? 0) ?></th>
                            <th class="text-right">₹<?= number_format($loan->total_fine ?? 0) ?></th>
                            <th class="text-right">₹<?= number_format($loan->total_paid ?? 0) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Summary -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-calculator mr-1"></i> Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td>Total Savings Deposited:</td>
                                <td class="text-right text-success">₹<?= number_format($member_summary['total_deposits'] ?? 0) ?></td>
                            </tr>
                            <tr>
                                <td>Total Savings Withdrawn:</td>
                                <td class="text-right text-danger">₹<?= number_format($member_summary['total_withdrawals'] ?? 0) ?></td>
                            </tr>
                            <tr class="table-success">
                                <th>Net Savings Balance:</th>
                                <th class="text-right">₹<?= number_format($member_summary['savings_balance'] ?? 0) ?></th>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td>Total Loans Availed:</td>
                                <td class="text-right">₹<?= number_format($member_summary['total_loans'] ?? 0) ?></td>
                            </tr>
                            <tr>
                                <td>Total Repaid (Principal + Interest):</td>
                                <td class="text-right text-success">₹<?= number_format($member_summary['total_repaid'] ?? 0) ?></td>
                            </tr>
                            <tr class="table-danger">
                                <th>Outstanding Loan Amount:</th>
                                <th class="text-right">₹<?= number_format($member_summary['loan_outstanding'] ?? 0) ?></th>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Select a member to generate statement</h5>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Print Styles -->
<style media="print">
    .no-print { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .card-header { background-color: #f8f9fa !important; color: #333 !important; }
</style>

<script>
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
    
    $('#daterange').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        opens: 'right'
    });
});
</script>
