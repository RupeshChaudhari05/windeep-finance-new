<!-- Non-Member Fund Providers List -->

<!-- Summary Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h4><?= $summary['active_providers'] ?></h4>
                <p>Active Providers</p>
            </div>
            <div class="icon"><i class="fas fa-user-tie"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h4><?= format_amount($summary['total_received']) ?></h4>
                <p>Total Received</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-down"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h4><?= format_amount($summary['total_returned']) ?></h4>
                <p>Total Returned</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-up"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h4><?= format_amount($summary['outstanding']) ?></h4>
                <p>Outstanding Balance</p>
            </div>
            <div class="icon"><i class="fas fa-balance-scale"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-6">
                <form action="" method="get" class="form-inline">
                    <div class="input-group input-group-sm mr-2">
                        <input type="text" name="search" class="form-control" placeholder="Search providers..." value="<?= $filters['search'] ?? '' ?>">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" <?= ($filters['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($filters['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </form>
            </div>
            <div class="col-md-6 text-right">
                <a href="<?= site_url('admin/non_members/create') ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Fund Provider
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th class="text-right">Received</th>
                        <th class="text-right">Returned</th>
                        <th class="text-right">Balance</th>
                        <th>Status</th>
                        <th width="130">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($non_members)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No fund providers found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($non_members as $key => $nm): ?>
                        <tr>
                            <td><?= (($pagination['current_page'] - 1) * $pagination['per_page']) + $key + 1 ?></td>
                            <td>
                                <a href="<?= site_url('admin/non_members/view/' . $nm->id) ?>" class="font-weight-bold">
                                    <?= htmlspecialchars($nm->name) ?>
                                </a>
                            </td>
                            <td><?= $nm->phone ? '<a href="tel:' . $nm->phone . '">' . htmlspecialchars($nm->phone) . '</a>' : '-' ?></td>
                            <td><?= $nm->email ? htmlspecialchars($nm->email) : '-' ?></td>
                            <td class="text-right text-success"><?= format_amount($nm->total_received) ?></td>
                            <td class="text-right text-warning"><?= format_amount($nm->total_returned) ?></td>
                            <td class="text-right font-weight-bold <?= $nm->balance > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= format_amount($nm->balance) ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $nm->status === 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($nm->status) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= site_url('admin/non_members/view/' . $nm->id) ?>" class="btn btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= site_url('admin/non_members/edit/' . $nm->id) ?>" class="btn btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= site_url('admin/non_members/delete/' . $nm->id) ?>" class="btn btn-danger" title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this fund provider?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-center">
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= site_url('admin/non_members?page=' . $i . '&status=' . ($filters['status'] ?? '') . '&search=' . ($filters['search'] ?? '')) ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
