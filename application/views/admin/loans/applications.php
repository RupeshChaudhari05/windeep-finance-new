<!-- Loan Applications List -->
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-6">
                <form action="" method="get" class="form-inline">
                    <div class="input-group input-group-sm mr-2">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= $filters['search'] ?? '' ?>">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" <?= ($filters['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= ($filters['status'] ?? '') == 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= ($filters['status'] ?? '') == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="disbursed" <?= ($filters['status'] ?? '') == 'disbursed' ? 'selected' : '' ?>>Disbursed</option>
                    </select>
                </form>
            </div>
            <div class="col-md-6 text-right">
                <a href="<?= site_url('admin/loans/apply') ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> New Application
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
                        <th>Application No</th>
                        <th>Member</th>
                        <th>Product</th>
                        <th class="text-right">Applied Amount</th>
                        <th>Tenure</th>
                        <th>Applied On</th>
                        <th>Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No loan applications found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $key => $app): ?>
                        <tr>
                            <td><?= $key + 1 ?></td>
                            <td>
                                <a href="<?= site_url('admin/loans/view_application/' . $app->id) ?>" class="font-weight-bold">
                                    <?= $app->application_number ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= site_url('admin/members/view/' . $app->member_id) ?>">
                                    <?= $app->member_name ?>
                                </a>
                                <br><small class="text-muted"><?= $app->member_code ?></small>
                            </td>
                            <td><span class="badge badge-info"><?= $app->product_name ?></span></td>
                            <td class="text-right font-weight-bold">₹<?= number_format($app->requested_amount) ?></td>
                            <td><?= $app->requested_tenure_months ?> months</td>
                            <td><?= format_date($app->created_at, 'd M Y') ?></td>
                            <td>
                                <?php
                                $status_class = [
                                    'pending' => 'warning',
                                    'under_review' => 'info',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'disbursed' => 'primary',
                                    'cancelled' => 'secondary'
                                ];
                                ?>
                                <span class="badge badge-<?= $status_class[$app->status] ?? 'secondary' ?>">
                                    <?= ucfirst(str_replace('_', ' ', $app->status)) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= site_url('admin/loans/view_application/' . $app->id) ?>" class="btn btn-xs btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($app->status == 'pending' || $app->status == 'under_review'): ?>
                                    <a href="<?= site_url('admin/loans/approve/' . $app->id) ?>" class="btn btn-xs btn-success" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="<?= site_url('admin/loans/reject/' . $app->id) ?>" class="btn btn-xs btn-danger" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    <?php elseif ($app->status == 'approved'): ?>
                                    <a href="<?= site_url('admin/loans/disburse/' . $app->id) ?>" class="btn btn-xs btn-primary" title="Disburse">
                                        <i class="fas fa-money-bill"></i>
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
</div>
