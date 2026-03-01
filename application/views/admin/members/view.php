<div class="row">
    <!-- Member Info Card -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <?= member_avatar_html($member, 100) ?>
                </div>
                
                <h3 class="profile-username text-center"><?= $member->first_name ?> <?= $member->last_name ?></h3>
                <p class="text-muted text-center"><?= $member->member_code ?></p>
                
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b><i class="fas fa-phone mr-1"></i> Phone</b>
                        <a class="float-right" href="tel:<?= $member->phone ?>"><?= $member->phone ?></a>
                    </li>
                    <?php if ($member->email): ?>
                    <li class="list-group-item">
                        <b><i class="fas fa-envelope mr-1"></i> Email</b>
                        <a class="float-right" href="mailto:<?= $member->email ?>"><?= $member->email ?></a>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item">
                        <b><i class="fas fa-map-marker-alt mr-1"></i> City</b>
                        <span class="float-right"><?= $member->city ?: '-' ?></span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fas fa-calendar mr-1"></i> Join Date</b>
                        <span class="float-right"><?= format_date($member->created_at) ?></span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fas fa-user-check mr-1"></i> Status</b>
                        <span class="float-right">
                            <?php
                            $status_class = ['active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger'];
                            ?>
                            <span class="badge badge-<?= $status_class[$member->status] ?? 'secondary' ?>">
                                <?= ucfirst($member->status) ?>
                            </span>
                        </span>
                    </li>
                    <?php if (!empty($member->member_level)): ?>
                    <li class="list-group-item">
                        <b><i class="fas fa-layer-group mr-1"></i> Member Level</b>
                        <span class="float-right">
                            <?php
                            $level_labels = ['founding_member' => 'Founding Member', 'level2' => 'Level 2 Member', 'level3' => 'Level 3 Member'];
                            $level_badges = ['founding_member' => 'danger', 'level2' => 'warning', 'level3' => 'info'];
                            ?>
                            <span class="badge badge-<?= $level_badges[$member->member_level] ?? 'secondary' ?>">
                                <?= $level_labels[$member->member_level] ?? $member->member_level ?>
                            </span>
                        </span>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item">
                        <b><i class="fas fa-id-card mr-1"></i> KYC</b>
                        <span class="float-right">
                            <?php if ($member->kyc_verified): ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> Verified</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </span>
                    </li>
                </ul>
                
                <div class="btn-group w-100">
                    <a href="<?= site_url('admin/members/edit/' . $member->id) ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="<?= site_url('admin/members/print_card/' . $member->id) ?>" class="btn btn-info" target="_blank">
                        <i class="fas fa-print"></i> Print Card
                    </a>
                </div>
                
                <?php if (!$member->kyc_verified): ?>
                <a href="<?= site_url('admin/members/verify_kyc/' . $member->id) ?>" class="btn btn-success btn-block mt-2" onclick="return confirm('Verify KYC for this member?')">
                    <i class="fas fa-check-circle"></i> Verify KYC
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Financial Summary -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-wallet mr-1"></i> Financial Summary</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Security Deposit Balance</td>
                        <td class="text-right font-weight-bold text-success">
                            <?= format_amount($member->savings_summary->current_balance ?? 0, 0) ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Total Deposited</td>
                        <td class="text-right"><?= format_amount($member->savings_summary->total_deposited ?? 0, 0) ?></td>
                    </tr>
                    <tr>
                        <td>Loan Outstanding</td>
                        <td class="text-right font-weight-bold text-danger">
                            <?= format_amount($member->loan_summary->outstanding_principal ?? 0, 0) ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Active Loans</td>
                        <td class="text-right"><?= $member->loan_summary->active_loans ?? 0 ?></td>
                    </tr>
                    <tr>
                        <td>Total Loan Taken</td>
                        <td class="text-right"><?= format_amount($member->loan_summary->total_principal ?? 0, 0) ?></td>
                    </tr>
                    <tr>
                        <td>Total Repaid</td>
                        <td class="text-right"><?= format_amount($member->loan_summary->total_paid ?? 0, 0) ?></td>
                    </tr>
                    <tr>
                        <td>Pending Fines</td>
                        <td class="text-right text-danger"><?= format_amount($member->fine_summary->pending ?? 0, 0) ?></td>
                    </tr>
                    <tr class="table-primary">
                        <td><strong>Net Balance</strong></td>
                        <td class="text-right font-weight-bold">
                            <?php 
                            $net = ($member->savings_summary->current_balance ?? 0) - ($member->loan_summary->outstanding_principal ?? 0) - ($member->fine_summary->pending ?? 0);
                            ?>
                            <span class="text-<?= $net >= 0 ? 'success' : 'danger' ?>">
                                <?= format_amount(abs($net), 0) ?> <?= $net >= 0 ? 'Cr' : 'Dr' ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Guarantor Exposure -->
        <?php if (isset($member->guarantor_exposure)): ?>
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-handshake mr-1"></i> Guarantor Exposure</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <span>Active Guarantees:</span>
                    <strong><?= $member->guarantor_exposure->active_count ?? 0 ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Total Exposure:</span>
                    <strong><?= format_amount($member->guarantor_exposure->total_exposure ?? 0, 0) ?></strong>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Details Tabs -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link active" href="#personal" data-toggle="tab">
                            <i class="fas fa-user mr-1"></i> Personal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#savings" data-toggle="tab">
                            <i class="fas fa-piggy-bank mr-1"></i> Security Deposit
                            <span class="badge badge-success"><?= count($savings_accounts) ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#loans" data-toggle="tab">
                            <i class="fas fa-hand-holding-usd mr-1"></i> Loans
                            <span class="badge badge-warning"><?= count($loans) ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#fines" data-toggle="tab">
                            <i class="fas fa-gavel mr-1"></i> Fines
                            <span class="badge badge-danger"><?= count($fines) ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#other_transactions" data-toggle="tab">
                            <i class="fas fa-receipt mr-1"></i> Other Transactions
                            <?php if (!empty($other_transactions)): ?>
                            <span class="badge badge-info"><?= count($other_transactions) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#ledger" data-toggle="tab">
                            <i class="fas fa-book mr-1"></i> Ledger
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Personal Info Tab -->
                    <div class="active tab-pane" id="personal">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Personal Details</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Date of Birth</th>
                                        <td><?= $member->date_of_birth ? format_date($member->date_of_birth) : '-' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Gender</th>
                                        <td><?= ucfirst($member->gender ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Marital Status</th>
                                        <td><?= ucfirst($member->marital_status ?? '-') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Occupation</th>
                                        <td><?= $member->occupation ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Monthly Income</th>
                                        <td><?= member_formatted_income($member) ?></td>
                                    </tr>
                                </table>
                                
                                <h5 class="mt-4">ID Proof</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">ID Type</th>
                                        <td><?= ucfirst(str_replace('_', ' ', $member->id_proof_type ?? '-')) ?></td>
                                    </tr>
                                    <tr>
                                        <th>ID Number</th>
                                        <td><?= $member->id_proof_number ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <th>PAN Number</th>
                                        <td><?= $member->pan_number ?? '-' ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Address</h5>
                                <address>
                                    <?= member_formatted_address($member) ?>
                                
                                <h5 class="mt-4">Bank Details</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Bank Name</th>
                                        <td><?= $member->bank_name ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Account Number</th>
                                        <td><?= $member->bank_account_number ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <th>IFSC Code</th>
                                        <td><?= $member->bank_ifsc ?? '-' ?></td>
                                    </tr>
                                </table>
                                
                                <h5 class="mt-4">Nominee</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">Name</th>
                                        <td><?= $member->nominee_name ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Relationship</th>
                                        <td><?= $member->nominee_relationship ?? '-' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Phone</th>
                                        <td><?= $member->nominee_phone ?? '-' ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Deposit Tab -->
                    <div class="tab-pane" id="savings">
                        <div class="d-flex justify-content-between mb-3">
                            <h5>Security Deposit Accounts</h5>
                            <a href="<?= site_url('admin/savings/create?member_id=' . $member->id) ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Open New Account
                            </a>
                        </div>
                        
                        <?php if (empty($savings_accounts)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-piggy-bank fa-3x mb-3"></i>
                                <p>No security deposit accounts found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Account No</th>
                                            <th>Scheme</th>
                                            <th>Monthly</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($savings_accounts as $acc): ?>
                                        <tr>
                                            <td><a href="<?= site_url('admin/savings/view/' . $acc->id) ?>"><?= $acc->account_number ?></a></td>
                                            <td><?= $acc->scheme_name ?></td>
                                            <td><?= format_amount($acc->monthly_amount, 0) ?></td>
                                            <td class="font-weight-bold text-success"><?= format_amount($acc->current_balance, 0) ?></td>
                                            <td><span class="badge badge-<?= $acc->status == 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($acc->status) ?></span></td>
                                            <td>
                                                <a href="<?= site_url('admin/savings/collect/' . $acc->id) ?>" class="btn btn-xs btn-success" title="Collect">
                                                    <i class="fas fa-rupee-sign"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Loans Tab -->
                    <div class="tab-pane" id="loans">
                        <div class="d-flex justify-content-between mb-3">
                            <h5>Loan Accounts</h5>
                            <a href="<?= site_url('admin/loans/apply?member_id=' . $member->id) ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> New Loan Application
                            </a>
                        </div>
                        
                        <?php if (empty($loans)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-hand-holding-usd fa-3x mb-3"></i>
                                <p>No loans found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Loan No</th>
                                            <th>Product</th>
                                            <th>Principal</th>
                                            <th>Outstanding</th>
                                            <th>EMI</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($loans as $loan): ?>
                                        <tr>
                                            <td><a href="<?= site_url('admin/loans/view/' . $loan->id) ?>"><?= $loan->loan_number ?></a></td>
                                            <td><?= $loan->product_name ?></td>
                                            <td><?= format_amount($loan->principal_amount, 0) ?></td>
                                            <td class="font-weight-bold text-danger"><?= format_amount($loan->outstanding_principal, 0) ?></td>
                                            <td><?= format_amount($loan->emi_amount, 0) ?></td>
                                            <td>
                                                <?php
                                                $loan_status = ['active' => 'success', 'closed' => 'secondary', 'overdue' => 'danger', 'npa' => 'dark'];
                                                ?>
                                                <span class="badge badge-<?= $loan_status[$loan->status] ?? 'secondary' ?>">
                                                    <?= ucfirst($loan->status) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($loan->status == 'active'): ?>
                                                <a href="<?= site_url('admin/loans/collect/' . $loan->id) ?>" class="btn btn-xs btn-success" title="Collect EMI">
                                                    <i class="fas fa-rupee-sign"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Fines Tab -->
                    <div class="tab-pane" id="fines">
                        <h5>Fines & Penalties</h5>
                        <?php if (empty($fines)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                <p>No fines found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fine Code</th>
                                            <th>Type</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Paid</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fines as $fine): ?>
                                        <tr>
                                            <td><a href="<?= site_url('admin/fines/view/' . $fine->id) ?>"><?= $fine->fine_code ?></a></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $fine->fine_type)) ?></td>
                                            <td><?= format_date($fine->fine_date) ?></td>
                                            <td><?= format_amount($fine->fine_amount, 0) ?></td>
                                            <td><?= format_amount($fine->paid_amount, 0) ?></td>
                                            <td class="text-danger"><?= format_amount($fine->balance_amount, 0) ?></td>
                                            <td>
                                                <?php
                                                $fine_status = ['pending' => 'warning', 'partial' => 'info', 'paid' => 'success', 'waived' => 'secondary', 'cancelled' => 'dark'];
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
                    
                    <!-- Other Transactions Tab -->
                    <div class="tab-pane" id="other_transactions">
                        <div class="d-flex justify-content-between mb-3">
                            <h5>Other Transactions</h5>
                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addOtherTransactionModal">
                                <i class="fas fa-plus mr-1"></i> Add Transaction
                            </button>
                        </div>
                        
                        <?php if (empty($other_transactions)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-receipt fa-3x mb-3"></i>
                                <p>No other transactions recorded</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th class="text-right">Amount</th>
                                            <th>Mode</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($other_transactions as $txn): ?>
                                        <tr>
                                            <td><?= format_date($txn->transaction_date) ?></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?= ucwords(str_replace('_', ' ', $txn->transaction_type)) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($txn->description ?? '-') ?></td>
                                            <td class="text-right font-weight-bold"><?= format_amount($txn->amount) ?></td>
                                            <td><?= ucfirst($txn->payment_mode ?? '-') ?></td>
                                            <td>
                                                <span class="badge badge-<?= $txn->status == 'completed' ? 'success' : ($txn->status == 'reversed' ? 'danger' : 'warning') ?>">
                                                    <?= ucfirst($txn->status) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Ledger Tab -->
                    <div class="tab-pane" id="ledger">
                        <div class="d-flex justify-content-between mb-3">
                            <h5>Member Ledger</h5>
                            <a href="<?= site_url('admin/reports/member_statement?member_id=' . $member->id) ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-print"></i> Print Statement
                            </a>
                        </div>
                        
                        <?php if (empty($ledger)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-book fa-3x mb-3"></i>
                                <p>No transactions found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Debit</th>
                                            <th>Credit</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($ledger, -20) as $entry): ?>
                                        <tr>
                                            <td><?= format_date($entry->transaction_date) ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $entry->entry_type)) ?></td>
                                            <td class="text-danger"><?= $entry->debit_amount > 0 ? format_amount($entry->debit_amount, 0) : '-' ?></td>
                                            <td class="text-success"><?= $entry->credit_amount > 0 ? format_amount($entry->credit_amount, 0) : '-' ?></td>
                                            <td class="font-weight-bold">
                                                <span class="text-<?= $entry->running_balance >= 0 ? 'success' : 'danger' ?>">
                                                    <?= format_amount(abs($entry->running_balance), 0) ?>
                                                    <?= $entry->running_balance >= 0 ? 'Cr' : 'Dr' ?>
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

<!-- Add Other Transaction Modal -->
<div class="modal fade" id="addOtherTransactionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= site_url('admin/members/add_other_transaction/' . $member->id) ?>" method="post">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-receipt mr-1"></i> Add Other Transaction</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Transaction Type <span class="text-danger">*</span></label>
                        <select name="transaction_type" class="form-control" required>
                            <option value="">Select Type...</option>
                            <option value="membership_fee">Membership Fee</option>
                            <option value="processing_fee">Processing Fee</option>
                            <option value="bonus">Bonus / Reward</option>
                            <option value="penalty">Penalty</option>
                            <option value="late_fee">Late Fee</option>
                            <option value="admission_fee">Admission Fee</option>
                            <option value="share_capital">Share Capital</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Transaction Date</label>
                        <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Payment Mode</label>
                        <select name="payment_mode" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="upi">UPI</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description / Remarks</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Receipt Number</label>
                        <input type="text" name="receipt_number" class="form-control" placeholder="Optional">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>
