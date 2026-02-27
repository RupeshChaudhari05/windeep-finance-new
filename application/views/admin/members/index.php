<!-- Members List -->
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-6">
                <form action="" method="get" class="form-inline">
                    <div class="input-group input-group-sm mr-2">
                        <input type="text" name="search" class="form-control" placeholder="Search members..." value="<?= $filters['search'] ?? '' ?>">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" <?= ($filters['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($filters['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="suspended" <?= ($filters['status'] ?? '') == 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </form>
            </div>
            <div class="col-md-6 text-right">
                <a href="<?= site_url('admin/members/send_email') ?>" class="btn btn-info btn-sm mr-2">
                    <i class="fas fa-envelope"></i> Send Email
                </a>
                <a href="<?= site_url('admin/members/create') ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add New Member
                </a>
                <a href="<?= site_url('admin/members/export?status=' . ($filters['status'] ?? '')) ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-download"></i> Export
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="membersTable">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Member Code</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>KYC</th>
                        <th>Status</th>
                        <th>Join Date</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No members found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($members as $key => $member): ?>
                        <tr>
                            <td><?= (($pagination['current_page'] - 1) * $pagination['per_page']) + $key + 1 ?></td>
                            <td>
                                <a href="<?= site_url('admin/members/view/' . $member->id) ?>" class="font-weight-bold">
                                    <?= $member->member_code ?>
                                </a>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?= member_avatar_html($member, 32, 'mr-2') ?>
                                    <div>
                                        <strong><?= $member->first_name ?> <?= $member->last_name ?></strong>
                                        <?php if ($member->email): ?>
                                            <br><small class="text-muted"><?= $member->email ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="tel:<?= $member->phone ?>"><?= $member->phone ?></a>
                            </td>
                            <td><?= $member->city ?: '-' ?></td>
                            <td>
                                <?php if ($member->kyc_verified): ?>
                                    <span class="badge badge-success" title="Verified on <?= format_date($member->kyc_verified_at) ?>">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_class = [
                                    'active' => 'success',
                                    'inactive' => 'secondary',
                                    'suspended' => 'danger',
                                    'deceased' => 'dark'
                                ];
                                ?>
                                <span class="badge badge-<?= $status_class[$member->status] ?? 'secondary' ?>">
                                    <?= ucfirst($member->status) ?>
                                </span>
                            </td>
                            <td>
                                <span title="<?= format_date_time($member->created_at, 'd M Y H:i') ?>">
                                    <?= format_date($member->created_at) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= site_url('admin/members/view/' . $member->id) ?>" class="btn btn-xs btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= site_url('admin/members/edit/' . $member->id) ?>" class="btn btn-xs btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($member->email): ?>
                                        <a href="<?= site_url('admin/members/send_email/' . $member->id) ?>" class="btn btn-xs btn-secondary" title="Send Email">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= site_url('admin/loans/apply?member_id=' . $member->id) ?>" class="btn btn-xs btn-success" title="New Loan">
                                        <i class="fas fa-hand-holding-usd"></i>
                                    </a>
                                    <a href="<?= site_url('admin/savings/create?member_id=' . $member->id) ?>" class="btn btn-xs btn-primary" title="Open Savings">
                                        <i class="fas fa-piggy-bank"></i>
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
    <?php if ($pagination['total'] > $pagination['per_page']): ?>
    <div class="card-footer clearfix">
        <div class="float-left">
            Showing <?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?> to 
            <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) ?> of 
            <?= $pagination['total'] ?> entries
        </div>
        <ul class="pagination pagination-sm m-0 float-right">
            <?php 
            $total_pages = ceil($pagination['total'] / $pagination['per_page']);
            $query_string = http_build_query(array_filter($filters));
            ?>
            
            <?php if ($pagination['current_page'] > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=1&<?= $query_string ?>">&laquo;</a>
            </li>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>&<?= $query_string ?>">&lsaquo;</a>
            </li>
            <?php endif; ?>
            
            <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($total_pages, $pagination['current_page'] + 2); $i++): ?>
            <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&<?= $query_string ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($pagination['current_page'] < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>&<?= $query_string ?>">&rsaquo;</a>
            </li>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $total_pages ?>&<?= $query_string ?>">&raquo;</a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<style>
.table td {
    vertical-align: middle;
}
</style>
