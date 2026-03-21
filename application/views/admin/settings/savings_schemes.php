<!-- Savings Schemes Settings -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-piggy-bank mr-1"></i> Savings Schemes</h3>
        <div class="card-tools">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addSchemeModal">
                <i class="fas fa-plus"></i> Add Scheme
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="schemesTable">
            <thead class="thead-light">
                <tr>
                    <th width="50">#</th>
                    <th>Scheme Name</th>
                    <th>Interest Rate</th>
                    <th>Min Deposit</th>
                    <th>Frequency</th>
                    <th>Lock-in Period</th>
                    <th>Members</th>
                    <th>Default</th>
                    <th>Status</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($schemes as $scheme): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <strong><?= html_escape($scheme->scheme_name) ?></strong>
                        <?php if (!empty($scheme->is_default)): ?>
                        <span class="badge badge-warning ml-1"><i class="fas fa-star"></i> Default</span>
                        <?php endif; ?>
                        <?php if (isset($scheme->description)): ?>
                        <br><small class="text-muted"><?= html_escape(substr($scheme->description, 0, 50)) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-success"><?= number_format($scheme->interest_rate, 2) ?>%</span>
                        <small class="text-muted">p.a.</small>
                    </td>
                    <td><?= number_format($scheme->min_deposit ?? 0, 2) ?></td>
                    <td>
                        <span class="badge badge-info"><?= ucfirst($scheme->deposit_frequency ?? 'Monthly') ?></span>
                    </td>
                    <td><?= $scheme->lock_in_period ?? 0 ?> months</td>
                    <td>
                        <span class="badge badge-primary"><?= $scheme->member_count ?? 0 ?></span>
                    </td>
                    <td>
                        <?php if (!empty($scheme->is_default)): ?>
                        <span class="badge badge-warning"><i class="fas fa-star"></i> Yes</span>
                        <?php else: ?>
                        <button class="btn btn-xs btn-outline-warning btn-set-default" data-id="<?= $scheme->id ?>" title="Set as default for new member enrollment">
                            <i class="far fa-star"></i> Set Default
                        </button>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($scheme->is_active): ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i> Active</span>
                        <?php else: ?>
                        <span class="badge badge-secondary"><i class="fas fa-times"></i> Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-warning btn-edit" data-scheme='<?= json_encode($scheme) ?>' title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-<?= $scheme->is_active ? 'secondary' : 'success' ?> btn-toggle" 
                                    data-id="<?= $scheme->id ?>" data-status="<?= $scheme->is_active ?>" title="<?= $scheme->is_active ? 'Deactivate' : 'Activate' ?>">
                                <i class="fas fa-<?= $scheme->is_active ? 'ban' : 'check' ?>"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Scheme Statistics -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Active Schemes</span>
                <span class="info-box-number"><?= count(array_filter($schemes, function($s) { return $s->is_active; })) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Members</span>
                <span class="info-box-number"><?= array_sum(array_column($schemes, 'member_count')) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Avg Interest</span>
                <span class="info-box-number"><?= $schemes ? number_format(array_sum(array_column($schemes, 'interest_rate')) / max(1,count($schemes)), 2) : 0 ?>%</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Deposits</span>
                <span class="info-box-number"><?= number_format($total_deposits ?? 0, 2) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Scheme Modal -->
<div class="modal fade" id="addSchemeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="schemeForm" method="post" action="<?= site_url('admin/settings/save_savings_scheme') ?>">
                <input type="hidden" name="id" id="scheme_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-piggy-bank mr-1"></i> <span id="modalTitle">Add</span> Savings Scheme</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Scheme Name <span class="text-danger">*</span></label>
                                <input type="text" name="scheme_name" id="scheme_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Interest Rate (% p.a.) <span class="text-danger">*</span></label>
                                <input type="number" name="interest_rate" id="interest_rate" class="form-control" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Min Deposit</label>
                                <input type="number" name="min_deposit" id="min_deposit" class="form-control" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Deposit Frequency <span class="text-danger">*</span></label>
                                <select name="deposit_frequency" id="deposit_frequency" class="form-control" required>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="yearly">Yearly</option>
                                    <option value="onetime">One-Time</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Lock-in Period (months)</label>
                                <input type="number" name="lock_in_period" id="lock_in_period" class="form-control" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Penalty Rate (%)</label>
                                <input type="number" name="penalty_rate" id="penalty_rate" class="form-control" step="0.01" value="0">
                                <small class="text-muted">For early withdrawal</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Maturity Bonus (%)</label>
                                <input type="number" name="maturity_bonus" id="maturity_bonus" class="form-control" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Due Day (1-28/31)</label>
                                <input type="number" name="due_day" id="due_day" class="form-control" min="1" max="31" value="1">
                                <small class="text-muted">Preferred day of month for dues (defaults to 1)</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="is_default" id="is_default" value="1">
                            <label class="custom-control-label" for="is_default">
                                <strong>Set as Default Scheme</strong>
                                <small class="text-muted d-block">New members enrolled via bulk import or registration will be automatically added to this scheme.</small>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Scheme</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#schemesTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25
    });
    
    // Edit scheme
    $('.btn-edit').click(function() {
        var scheme = $(this).data('scheme');
        $('#modalTitle').text('Edit');
        $('#scheme_id').val(scheme.id);
        $('#scheme_name').val(scheme.scheme_name);
        $('#interest_rate').val(scheme.interest_rate);
        $('#min_deposit').val(scheme.min_deposit);
        $('#deposit_frequency').val(scheme.deposit_frequency);
        $('#lock_in_period').val(scheme.lock_in_period);
        $('#penalty_rate').val(scheme.penalty_rate);
        $('#maturity_bonus').val(scheme.maturity_bonus);
        $('#description').val(scheme.description);
        $('#is_default').prop('checked', scheme.is_default == 1);
        $('#addSchemeModal').modal('show');
    });
    
    // Reset modal
    $('#addSchemeModal').on('hidden.bs.modal', function() {
        $('#schemeForm')[0].reset();
        $('#scheme_id').val('');
        $('#modalTitle').text('Add');
        $('#is_default').prop('checked', false);
    });

    // Set as default (quick button without opening modal)
    $(document).on('click', '.btn-set-default', function() {
        var id = $(this).data('id');
        if (!confirm('Set this scheme as the default for new member auto-enrollment?')) return;
        $.post('<?= site_url('admin/settings/set_default_scheme') ?>', {id: id}, function(response) {
            if (response.success) {
                toastr.success('Default scheme updated');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to update');
            }
        }, 'json');
    });
    
    // Toggle status
    $('.btn-toggle').click(function() {
        var id = $(this).data('id');
        var status = $(this).data('status') == 1 ? 0 : 1;
        $.post('<?= site_url('admin/settings/toggle_savings_scheme') ?>', {id: id, is_active: status}, function(response) {
            if (response.success) {
                toastr.success('Scheme status updated');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to update');
            }
        }, 'json');
    });
});
</script>
