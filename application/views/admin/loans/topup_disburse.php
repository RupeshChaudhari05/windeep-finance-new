<!-- Top-up Disbursement -->
<form method="post" action="<?= site_url('admin/loans/topup_disburse/' . $application->id) ?>" id="topupDisburseForm">
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
    <div class="row">
        <div class="col-md-8">
            <!-- Parent Loan Foreclosure -->
            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exchange-alt mr-1"></i> Parent Loan - Internal Foreclosure</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Parent Loan Details</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td>Loan Number:</td>
                                    <td><strong><?= $parent_loan->loan_number ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Original Principal:</td>
                                    <td><?= format_amount($parent_loan->principal_amount) ?></td>
                                </tr>
                                <tr>
                                    <td>Interest Rate:</td>
                                    <td><?= $parent_loan->interest_rate ?>% p.a.</td>
                                </tr>
                                <tr>
                                    <td>Disbursed On:</td>
                                    <td><?= format_date($parent_loan->disbursement_date) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Foreclosure Settlement</h6>
                            <table class="table table-bordered table-sm mb-0">
                                <tr>
                                    <td>Outstanding Principal</td>
                                    <td class="text-right font-weight-bold"><?= format_amount($application->parent_outstanding_principal) ?></td>
                                </tr>
                                <tr>
                                    <td>Outstanding Interest</td>
                                    <td class="text-right"><?= format_amount($application->parent_outstanding_interest) ?></td>
                                </tr>
                                <tr class="table-danger">
                                    <th>Settlement (Rolled into New Loan)</th>
                                    <th class="text-right"><?= format_amount($application->parent_outstanding_principal) ?></th>
                                </tr>
                            </table>
                            <small class="text-muted mt-1 d-block">
                                <i class="fas fa-info-circle mr-1"></i>
                                Only the outstanding principal is carried forward. Pending interest is absorbed.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Top-up Loan Details -->
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i> New Top-up Loan</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Application Details</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td>Application #:</td>
                                    <td><strong><?= $application->application_number ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Loan Product:</td>
                                    <td><?= $product->product_name ?></td>
                                </tr>
                                <tr>
                                    <td>Interest Rate:</td>
                                    <td><?= $application->approved_interest_rate ?>% p.a.</td>
                                </tr>
                                <tr>
                                    <td>Tenure:</td>
                                    <td><?= $application->approved_tenure_months ?> months</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">EMI Calculation</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td>New Principal:</td>
                                    <td class="text-primary"><strong><?= format_amount($application->approved_amount) ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Total Interest:</td>
                                    <td><?= format_amount($emi_calc['total_interest'], 0) ?></td>
                                </tr>
                                <tr>
                                    <td>Monthly EMI:</td>
                                    <td class="text-primary"><strong><?= format_amount($emi_calc['emi'], 0) ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Total Payable:</td>
                                    <td class="text-danger"><strong><?= format_amount($emi_calc['total_payable'], 0) ?></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- Principal Breakdown -->
                    <h6 class="text-muted">Principal Breakdown</h6>
                    <table class="table table-bordered">
                        <tr>
                            <td>Outstanding from Parent Loan</td>
                            <td class="text-right"><?= format_amount($application->parent_outstanding_principal) ?></td>
                        </tr>
                        <tr>
                            <td>Additional Top-up Amount</td>
                            <td class="text-right font-weight-bold text-success"><?= format_amount($application->topup_amount) ?></td>
                        </tr>
                        <tr class="table-primary">
                            <th>New Loan Principal</th>
                            <th class="text-right"><?= format_amount($application->approved_amount) ?></th>
                        </tr>
                    </table>

                    <!-- Disbursement Breakdown -->
                    <h6 class="text-muted">Disbursement Breakdown</h6>
                    <table class="table table-bordered">
                        <tr>
                            <td>Additional Top-up Amount</td>
                            <td class="text-right"><?= format_amount($application->topup_amount) ?></td>
                        </tr>
                        <?php if ($topup_fee > 0): ?>
                        <tr>
                            <td>Processing Fee
                                <small class="text-muted">(on additional amount only)</small>
                            </td>
                            <td class="text-right text-danger">- <?= format_amount($topup_fee) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-success">
                            <th>Net Disbursement to Member</th>
                            <th class="text-right text-success"><?= format_amount($net_disbursement) ?></th>
                        </tr>
                    </table>

                    <div class="callout callout-warning py-2">
                        <small>
                            <i class="fas fa-info-circle mr-1"></i>
                            Only <strong><?= format_amount($net_disbursement) ?></strong> will be disbursed to the member.
                            The outstanding principal of <?= format_amount($application->parent_outstanding_principal) ?> from the parent loan is internally settled.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Member Details -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user mr-1"></i> Member Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <table class="table table-borderless table-sm mb-0">
                                <tr><td>Member Code:</td><td><strong><?= $member->member_code ?></strong></td></tr>
                                <tr><td>Name:</td><td><?= $member->first_name ?> <?= $member->last_name ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-borderless table-sm mb-0">
                                <tr><td>Phone:</td><td><?= $member->phone ?></td></tr>
                                <tr><td>Email:</td><td><?= $member->email ?? '-' ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-borderless table-sm mb-0">
                                <tr><td>Parent Loan:</td>
                                    <td>
                                        <a href="<?= site_url('admin/loans/view/' . $parent_loan->id) ?>"><?= $parent_loan->loan_number ?></a>
                                    </td>
                                </tr>
                                <tr><td>Status:</td><td><span class="badge badge-success">Approved</span></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Disbursement Form -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-wallet mr-1"></i> Disbursement Details</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Disbursement Date <span class="text-danger">*</span></label>
                        <input type="date" name="disbursement_date" id="disbursement_date" class="form-control"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>First EMI Date <span class="text-danger">*</span></label>
                        <?php
                        $first_emi = date('Y-m-d', strtotime('+1 month'));
                        if ($fixed_due_day > 0) {
                            $next_month = date('Y-m', strtotime('+1 month'));
                            $last_day   = date('t', strtotime($next_month . '-01'));
                            $day        = min($fixed_due_day, $last_day);
                            $first_emi  = $next_month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                        }
                        ?>
                        <input type="date" name="first_emi_date" id="first_emi_date" class="form-control"
                               value="<?= $first_emi ?>" required>
                        <?php if ($fixed_due_day > 0): ?>
                            <small class="text-muted">Fixed due day: <?= $fixed_due_day ?>th of every month</small>
                        <?php else: ?>
                            <small class="text-muted">Usually 1 month from disbursement</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Disbursement Mode <span class="text-danger">*</span></label>
                        <select name="disbursement_mode" id="disbursement_mode" class="form-control" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer" selected>Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="upi">UPI</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Reference Number</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="Transaction/Cheque No.">
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block btn-lg" onclick="return confirm('Confirm top-up disbursement?\n\nParent loan will be closed.\nNew loan will be created.\nNet disbursement: <?= format_amount($net_disbursement) ?>')">
                        <i class="fas fa-paper-plane mr-1"></i> Disburse Top-up
                    </button>
                    <a href="<?= site_url('admin/loans') ?>" class="btn btn-default btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Cancel
                    </a>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="card bg-gradient-info">
                <div class="card-body text-white">
                    <h6><i class="fas fa-info-circle mr-1"></i> Top-up Summary</h6>
                    <ul class="list-unstyled mb-0">
                        <li><small>Parent Loan: <strong><?= $parent_loan->loan_number ?></strong> (will be closed)</small></li>
                        <li><small>Outstanding Settled: <strong><?= format_amount($application->parent_outstanding_principal) ?></strong></small></li>
                        <li><small>Additional Amount: <strong><?= format_amount($application->topup_amount) ?></strong></small></li>
                        <li class="mt-1"><small>New Principal: <strong><?= format_amount($application->approved_amount) ?></strong></small></li>
                        <li><small>Monthly EMI: <strong><?= format_amount($emi_calc['emi'], 0) ?></strong></small></li>
                        <li><small>Tenure: <strong><?= $application->approved_tenure_months ?> months</strong></small></li>
                        <?php if ($topup_fee > 0): ?>
                        <li><small>Processing Fee: <strong><?= format_amount($topup_fee) ?></strong></small></li>
                        <?php endif; ?>
                        <li class="mt-1 border-top pt-1"><small>Net to Member: <strong class="h5"><?= format_amount($net_disbursement) ?></strong></small></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</form>
