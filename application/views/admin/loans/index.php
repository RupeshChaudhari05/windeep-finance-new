<!-- Active Loans List -->
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-8">
                <form action="" method="get" class="form-inline">
                    <div class="input-group input-group-sm mr-2">
                        <input type="text" name="search" class="form-control" placeholder="Search loan/member..." value="<?= $filters['search'] ?? '' ?>">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <select name="product" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">All Products</option>
                        <?php foreach ($products ?? [] as $product): ?>
                            <option value="<?= $product->id ?>" <?= ($filters['product'] ?? '') == $product->id ? 'selected' : '' ?>><?= $product->product_name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" <?= ($filters['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="overdue" <?= ($filters['status'] ?? '') == 'overdue' ? 'selected' : '' ?>>Overdue</option>
                        <option value="npa" <?= ($filters['status'] ?? '') == 'npa' ? 'selected' : '' ?>>NPA</option>
                        <option value="closed" <?= ($filters['status'] ?? '') == 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </form>
            </div>
            <div class="col-md-4 text-right">
                <a href="<?= site_url('admin/loans/applications') ?>" class="btn btn-info btn-sm">
                    <i class="fas fa-file-alt"></i> Applications
                </a>
                <a href="<?= site_url('admin/loans/apply') ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> New Application
                </a>
                <a href="<?= site_url('admin/loans/overdue') ?>" class="btn btn-danger btn-sm">
                    <i class="fas fa-exclamation-triangle"></i> Overdue
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Summary Stats -->
        <div class="row p-3 bg-light border-bottom">
            <div class="col-md-3">
                <div class="small-box bg-primary mb-0">
                    <div class="inner py-2 px-3">
                        <h4>₹<?= number_format($summary['total_disbursed'] ?? 0) ?></h4>
                        <p class="mb-0">Total Disbursed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-danger mb-0">
                    <div class="inner py-2 px-3">
                        <h4>₹<?= number_format($summary['total_outstanding'] ?? 0) ?></h4>
                        <p class="mb-0">Outstanding</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-success mb-0">
                    <div class="inner py-2 px-3">
                        <h4>₹<?= number_format($summary['total_collected'] ?? 0) ?></h4>
                        <p class="mb-0">Collected</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-warning mb-0">
                    <div class="inner py-2 px-3">
                        <h4><?= number_format($summary['overdue_count'] ?? 0) ?></h4>
                        <p class="mb-0">Overdue Loans</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Loan No</th>
                        <th>Member</th>
                        <th>Product</th>
                        <th class="text-right">Principal</th>
                        <th class="text-right">Outstanding</th>
                        <th class="text-right">EMI</th>
                        <th>Status</th>
                        <th>Overdue</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($loans)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-hand-holding-usd fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No loans found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($loans as $key => $loan): ?>
                        <tr class="<?= $loan->overdue_count > 0 ? 'table-warning' : '' ?> <?= $loan->status == 'npa' ? 'table-danger' : '' ?>">
                            <td><?= (($pagination['current_page'] ?? 1) - 1) * ($pagination['per_page'] ?? 20) + $key + 1 ?></td>
                            <td>
                                <a href="<?= site_url('admin/loans/view/' . $loan->id) ?>" class="font-weight-bold">
                                    <?= $loan->loan_number ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= site_url('admin/members/view/' . $loan->member_id) ?>">
                                    <?= $loan->member_name ?>
                                </a>
                                <br><small class="text-muted"><?= $loan->member_code ?></small>
                            </td>
                            <td><span class="badge badge-info"><?= $loan->product_name ?></span></td>
                            <td class="text-right">₹<?= number_format($loan->principal_amount) ?></td>
                            <td class="text-right font-weight-bold text-danger">₹<?= number_format($loan->outstanding_principal) ?></td>
                            <td class="text-right">₹<?= number_format($loan->emi_amount) ?></td>
                            <td>
                                <?php
                                $status_class = [
                                    'active' => 'success',
                                    'overdue' => 'warning',
                                    'npa' => 'danger',
                                    'closed' => 'secondary',
                                    'written_off' => 'dark'
                                ];
                                ?>
                                <span class="badge badge-<?= $status_class[$loan->status] ?? 'secondary' ?>">
                                    <?= strtoupper($loan->status) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($loan->overdue_count > 0): ?>
                                    <span class="badge badge-danger" title="<?= $loan->overdue_count ?> unpaid EMIs">
                                        <?= $loan->overdue_count ?> EMI
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= site_url('admin/loans/view/' . $loan->id) ?>" class="btn btn-xs btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($loan->status == 'active' || $loan->status == 'overdue'): ?>
                                    <a href="<?= site_url('admin/loans/collect/' . $loan->id) ?>" class="btn btn-xs btn-success" title="Collect EMI">
                                        <i class="fas fa-rupee-sign"></i>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-warning dropdown-toggle dropdown-toggle-split" data-toggle="dropdown"></button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="<?= site_url('admin/loans/schedule/' . $loan->id) ?>">
                                            <i class="fas fa-calendar"></i> EMI Schedule
                                        </a>
                                        <a class="dropdown-item" href="<?= site_url('admin/loans/statement/' . $loan->id) ?>">
                                            <i class="fas fa-file-alt"></i> Loan Statement
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="<?= site_url('admin/loans/reschedule/' . $loan->id) ?>">
                                            <i class="fas fa-calendar-alt"></i> Reschedule
                                        </a>
                                    </div>
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
            <?php 
            $total_pages = ceil($pagination['total'] / $pagination['per_page']);
            $query_string = http_build_query(array_filter($filters ?? []));
            ?>
            <?php if ($pagination['current_page'] > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=1&<?= $query_string ?>">&laquo;</a></li>
            <li class="page-item"><a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>&<?= $query_string ?>">&lsaquo;</a></li>
            <?php endif; ?>
            <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($total_pages, $pagination['current_page'] + 2); $i++): ?>
            <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&<?= $query_string ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <?php if ($pagination['current_page'] < $total_pages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>&<?= $query_string ?>">&rsaquo;</a></li>
            <li class="page-item"><a class="page-link" href="?page=<?= $total_pages ?>&<?= $query_string ?>">&raquo;</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
