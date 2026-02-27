<!-- Savings Accounts List -->
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-8">
                <form action="" method="get" class="form-inline">
                    <div class="input-group input-group-sm mr-2">
                        <input type="text" name="search" class="form-control" placeholder="Search account/member..." value="<?= $filters['search'] ?? '' ?>">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <select name="scheme" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">All Schemes</option>
                        <?php foreach ($schemes ?? [] as $scheme): ?>
                            <option value="<?= $scheme->id ?>" <?= ($filters['scheme'] ?? '') == $scheme->id ? 'selected' : '' ?>><?= $scheme->scheme_name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" <?= ($filters['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="matured" <?= ($filters['status'] ?? '') == 'matured' ? 'selected' : '' ?>>Matured</option>
                        <option value="closed" <?= ($filters['status'] ?? '') == 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </form>
            </div>
            <div class="col-md-4 text-right">
                <a href="<?= site_url('admin/savings/create') ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Open New Account
                </a>
                <a href="<?= site_url('admin/savings/pending') ?>" class="btn btn-warning btn-sm">
                    <i class="fas fa-clock"></i> Pending Dues
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Summary Stats -->
        <div class="row p-3 bg-light border-bottom">
            <div class="col-md-3">
                <div class="small-box bg-info mb-0">
                    <div class="inner py-2 px-3">
                        <h4><?= format_amount($summary['total_balance'] ?? 0, 0) ?></h4>
                        <p class="mb-0">Total Balance</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-success mb-0">
                    <div class="inner py-2 px-3">
                        <h4><?= number_format($summary['active_accounts'] ?? 0) ?></h4>
                        <p class="mb-0">Active Accounts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-warning mb-0">
                    <div class="inner py-2 px-3">
                        <h4><?= format_amount($summary['pending_collection'] ?? 0, 0) ?></h4>
                        <p class="mb-0">Pending Collection</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-danger mb-0">
                    <div class="inner py-2 px-3">
                        <h4><?= number_format($summary['overdue_accounts'] ?? 0) ?></h4>
                        <p class="mb-0">Overdue Accounts</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Account No</th>
                        <th>Member</th>
                        <th>Scheme</th>
                        <th class="text-right">Monthly</th>
                        <th class="text-right">Deposited</th>
                        <th class="text-right">Balance</th>
                        <th>Status</th>
                        <th>Pending</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($accounts)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-piggy-bank fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No savings accounts found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $key => $acc): ?>
                        <tr class="<?= $acc->pending_dues > 0 ? 'table-warning' : '' ?>">
                            <td><?= (($pagination['current_page'] - 1) * $pagination['per_page']) + $key + 1 ?></td>
                            <td>
                                <a href="<?= site_url('admin/savings/view/' . $acc->id) ?>" class="font-weight-bold">
                                    <?= $acc->account_number ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= site_url('admin/members/view/' . $acc->member_id) ?>">
                                    <?= $acc->member_name ?>
                                </a>
                                <br><small class="text-muted"><?= $acc->member_code ?></small>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= $acc->scheme_name ?></span>
                            </td>
                            <td class="text-right"><?= format_amount($acc->monthly_amount, 0) ?></td>
                            <td class="text-right"><?= format_amount($acc->total_deposited, 0) ?></td>
                            <td class="text-right font-weight-bold text-success"><?= format_amount($acc->current_balance, 0) ?></td>
                            <td>
                                <?php
                                $status_class = [
                                    'active' => 'success',
                                    'matured' => 'primary',
                                    'closed' => 'secondary',
                                    'dormant' => 'warning'
                                ];
                                ?>
                                <span class="badge badge-<?= $status_class[$acc->status] ?? 'secondary' ?>">
                                    <?= ucfirst($acc->status) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($acc->pending_dues > 0): ?>
                                    <span class="badge badge-danger" title="<?= $acc->pending_dues ?> unpaid installments">
                                        <?= $acc->pending_dues ?> dues
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= site_url('admin/savings/view/' . $acc->id) ?>" class="btn btn-xs btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($acc->status == 'active'): ?>
                                    <a href="<?= site_url('admin/savings/collect/' . $acc->id) ?>" class="btn btn-xs btn-success" title="Collect Payment">
                                        <i class="fas fa-rupee-sign"></i>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-warning dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="<?= site_url('admin/savings/edit/' . $acc->id) ?>">
                                            <i class="fas fa-edit"></i> Edit Account
                                        </a>
                                        <a class="dropdown-item" href="<?= site_url('admin/savings/schedule/' . $acc->id) ?>">
                                            <i class="fas fa-calendar"></i> View Schedule
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="<?= site_url('admin/savings/close/' . $acc->id) ?>" onclick="return confirm('Close this savings account?')">
                                            <i class="fas fa-times-circle"></i> Close Account
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
