<!-- Fines & Penalties List -->
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-8">
                <form action="" method="get" class="form-inline">
                    <div class="input-group input-group-sm mr-2">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= $filters['search'] ?? '' ?>">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <select name="type" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="loan_late_payment" <?= ($filters['type'] ?? '') == 'loan_late_payment' ? 'selected' : '' ?>>Loan Late Payment</option>
                        <option value="savings_late_payment" <?= ($filters['type'] ?? '') == 'savings_late_payment' ? 'selected' : '' ?>>Savings Late Payment</option>
                        <option value="manual" <?= ($filters['type'] ?? '') == 'manual' ? 'selected' : '' ?>>Manual Fine</option>
                    </select>
                    <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" <?= ($filters['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="partial" <?= ($filters['status'] ?? '') == 'partial' ? 'selected' : '' ?>>Partial</option>
                        <option value="paid" <?= ($filters['status'] ?? '') == 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="waived" <?= ($filters['status'] ?? '') == 'waived' ? 'selected' : '' ?>>Waived</option>
                    </select>
                </form>
            </div>
            <div class="col-md-4 text-right">
                <a href="<?= site_url('admin/fines/create') ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Manual Fine
                </a>
                <a href="<?= site_url('admin/fines/rules') ?>" class="btn btn-info btn-sm">
                    <i class="fas fa-cog"></i> Fine Rules
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Summary Stats -->
        <div class="row p-3 bg-light border-bottom">
            <div class="col-md-3">
                <div class="small-box bg-warning mb-0">
                    <div class="inner py-2 px-3">
                        <h4>₹<?= number_format($summary['pending_amount'] ?? 0) ?></h4>
                        <p class="mb-0">Pending Fines</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-success mb-0">
                    <div class="inner py-2 px-3">
                        <h4>₹<?= number_format($summary['collected_amount'] ?? 0) ?></h4>
                        <p class="mb-0">Collected</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-secondary mb-0">
                    <div class="inner py-2 px-3">
                        <h4>₹<?= number_format($summary['waived_amount'] ?? 0) ?></h4>
                        <p class="mb-0">Waived</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-danger mb-0">
                    <div class="inner py-2 px-3">
                        <h4><?= number_format($summary['pending_count'] ?? 0) ?></h4>
                        <p class="mb-0">Pending Count</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Fine Code</th>
                        <th>Member</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Date</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Balance</th>
                        <th>Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fines)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-4">
                            <i class="fas fa-gavel fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No fines found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($fines as $key => $fine): ?>
                        <tr>
                            <td><?= $key + 1 ?></td>
                            <td>
                                <a href="<?= site_url('admin/fines/view/' . $fine->id) ?>" class="font-weight-bold">
                                    <?= $fine->fine_code ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= site_url('admin/members/view/' . $fine->member_id) ?>">
                                    <?= $fine->member_name ?>
                                </a>
                                <br><small class="text-muted"><?= $fine->member_code ?></small>
                            </td>
                            <td>
                                <?php
                                $type_badges = [
                                    'loan_late_payment' => 'danger',
                                    'savings_late_payment' => 'warning',
                                    'manual' => 'info',
                                    'meeting_absence' => 'secondary'
                                ];
                                ?>
                                <span class="badge badge-<?= $type_badges[$fine->fine_type] ?? 'secondary' ?>">
                                    <?= ucfirst(str_replace('_', ' ', $fine->fine_type)) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($fine->loan_id): ?>
                                    <a href="<?= site_url('admin/loans/view/' . $fine->loan_id) ?>"><?= $fine->loan_number ?></a>
                                <?php elseif ($fine->savings_account_id): ?>
                                    <a href="<?= site_url('admin/savings/view/' . $fine->savings_account_id) ?>"><?= $fine->account_number ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($fine->fine_date)) ?></td>
                            <td class="text-right">₹<?= number_format($fine->fine_amount) ?></td>
                            <td class="text-right text-success">₹<?= number_format($fine->paid_amount) ?></td>
                            <td class="text-right font-weight-bold text-danger">₹<?= number_format($fine->balance_amount) ?></td>
                            <td>
                                <?php
                                $status_class = [
                                    'pending' => 'warning',
                                    'partial' => 'info',
                                    'paid' => 'success',
                                    'waived' => 'secondary',
                                    'cancelled' => 'dark'
                                ];
                                ?>
                                <span class="badge badge-<?= $status_class[$fine->status] ?? 'secondary' ?>">
                                    <?= ucfirst($fine->status) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= site_url('admin/fines/view/' . $fine->id) ?>" class="btn btn-xs btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($fine->status == 'pending' || $fine->status == 'partial'): ?>
                                    <a href="<?= site_url('admin/fines/collect/' . $fine->id) ?>" class="btn btn-xs btn-success" title="Collect">
                                        <i class="fas fa-rupee-sign"></i>
                                    </a>
                                    <a href="<?= site_url('admin/fines/waive/' . $fine->id) ?>" class="btn btn-xs btn-secondary" title="Waive" onclick="return confirm('Waive this fine?')">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (isset($pagination) && $pagination['total'] > $pagination['per_page']): ?>
    <div class="card-footer clearfix">
        <div class="float-left">
            Showing <?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?> to 
            <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) ?> of 
            <?= $pagination['total'] ?> entries
        </div>
        <ul class="pagination pagination-sm m-0 float-right">
            <?php $total_pages = ceil($pagination['total'] / $pagination['per_page']); ?>
            <?php if ($pagination['current_page'] > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>">&lsaquo;</a></li>
            <?php endif; ?>
            <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($total_pages, $pagination['current_page'] + 2); $i++): ?>
            <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <?php if ($pagination['current_page'] < $total_pages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>">&rsaquo;</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
