<!-- Edit Non-Member Fund Provider -->
<div class="row">
    <div class="col-md-8 offset-md-2">
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-exclamation-triangle mr-1"></i> <?= $this->session->flashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-1"></i> Edit Fund Provider</h3>
            </div>
            <form action="<?= site_url('admin/non_members/edit/' . $non_member->id) ?>" method="post">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <div class="card-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= htmlspecialchars($non_member->name) ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($non_member->phone ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($non_member->email ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($non_member->address ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($non_member->notes ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?= $non_member->status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $non_member->status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?= site_url('admin/non_members/view/' . $non_member->id) ?>" class="btn btn-default">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                    <button type="submit" class="btn btn-warning float-right">
                        <i class="fas fa-save mr-1"></i> Update Fund Provider
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
