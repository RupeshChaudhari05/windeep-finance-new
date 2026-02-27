<div class="row">
    <!-- Summary Cards -->
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= count($installments) ?></h3>
                <p>Total Installments</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?= format_amount($total_emi) ?></h3>
                <p>Total EMI Amount</p>
            </div>
            <div class="icon">
                <i class="fas fa-rupee-sign"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= format_amount($total_paid) ?></h3>
                <p>Amount Collected</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= format_amount($total_pending) ?></h3>
                <p>Amount Pending</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> EMI Schedule</h3>
                <div class="card-tools">
                    <a href="<?= site_url('admin/installments/collection_sheet') ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-file-excel mr-1"></i> Collection Sheet
                    </a>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card-body border-bottom">
                <form method="get" class="form-inline">
                    <div class="form-group mr-2 mb-2">
                        <label class="mr-2">Status:</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= $status == 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="partial" <?= $status == 'partial' ? 'selected' : '' ?>>Partial</option>
                            <option value="overdue" <?= $status == 'overdue' ? 'selected' : '' ?>>Overdue</option>
                        </select>
                    </div>
                    
                    <div class="form-group mr-2 mb-2">
                        <label class="mr-2">Month:</label>
                        <input type="month" name="month" class="form-control form-control-sm" value="<?= $month ?>">
                    </div>
                    
                    <div class="form-group mr-2 mb-2">
                        <label class="mr-2">Loan:</label>
                        <select name="loan_id" class="form-control form-control-sm" style="width: 200px;">
                            <option value="">All Loans</option>
                            <?php foreach ($active_loans as $loan_opt): ?>
                                <option value="<?= $loan_opt->id ?>" <?= $loan_id == $loan_opt->id ? 'selected' : '' ?>>
                                    <?= $loan_opt->loan_number ?> - <?= $loan_opt->first_name ?> <?= $loan_opt->last_name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-sm btn-primary mb-2 mr-2">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="<?= site_url('admin/installments') ?>" class="btn btn-sm btn-secondary mb-2">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
            </div>
            
            <div class="card-body p-0">
                <?php if (empty($installments)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <p class="text-muted">No installments found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-striped mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>EMI #</th>
                                    <th>Due Date</th>
                                    <th>Loan Number</th>
                                    <th>Member</th>
                                    <th>Product</th>
                                    <th class="text-right">EMI Amount</th>
                                    <th class="text-right">Paid</th>
                                    <th class="text-right">Balance</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($installments as $inst): ?>
                                <?php
                                $balance = $inst->emi_amount - $inst->total_paid;
                                $is_overdue = $inst->status == 'pending' && strtotime($inst->due_date) < time();
                                ?>
                                <tr class="<?= $is_overdue ? 'table-danger' : '' ?> <?= $inst->status == 'paid' ? 'table-success' : '' ?>">
                                    <td><strong>#<?= $inst->installment_number ?></strong></td>
                                    <td>
                                        <?= format_date($inst->due_date) ?>
                                        <?php if ($is_overdue): ?>
                                            <br><small class="text-danger">
                                                <i class="fas fa-exclamation-circle"></i> 
                                                <?= floor((time() - strtotime($inst->due_date)) / 86400) ?> days late
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('admin/loans/view/' . $inst->loan_id) ?>">
                                            <?= $inst->loan_number ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('admin/members/view/' . $inst->member_id) ?>">
                                            <?= $inst->first_name ?> <?= $inst->last_name ?>
                                        </a>
                                        <br><small class="text-muted"><?= $inst->member_code ?></small>
                                    </td>
                                    <td><span class="badge badge-info"><?= $inst->product_name ?></span></td>
                                    <td class="text-right font-weight-bold"><?= format_amount($inst->emi_amount) ?></td>
                                    <td class="text-right text-success"><?= format_amount($inst->total_paid) ?></td>
                                    <td class="text-right">
                                        <?php if ($balance > 0): ?>
                                            <span class="text-danger"><?= format_amount($balance) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'pending' => 'warning',
                                            'paid' => 'success',
                                            'partial' => 'info',
                                            'upcoming' => 'secondary',
                                            'overdue' => 'danger'
                                        ];
                                        $display_status = $is_overdue ? 'overdue' : $inst->status;
                                        $badge_class = $status_badges[$display_status] ?? 'secondary';
                                        ?>
                                        <span class="badge badge-<?= $badge_class ?>">
                                            <?= ucfirst($display_status) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= site_url('admin/installments/view/' . $inst->id) ?>" class="btn btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($inst->status != 'paid'): ?>
                                                <a href="<?= site_url('admin/loans/collect/' . $inst->loan_id . '?installment_id=' . $inst->id) ?>" class="btn btn-success" title="Collect Payment">
                                                    <i class="fas fa-rupee-sign"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <th colspan="5" class="text-right">Total:</th>
                                    <th class="text-right"><?= format_amount($total_emi) ?></th>
                                    <th class="text-right text-success"><?= format_amount($total_paid) ?></th>
                                    <th class="text-right text-danger"><?= format_amount($total_pending) ?></th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
