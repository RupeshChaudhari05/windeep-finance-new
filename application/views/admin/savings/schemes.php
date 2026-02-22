<!-- Savings Schemes -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-piggy-bank mr-1"></i> Savings Schemes</h3>
        <div class="card-tools">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addSchemeModal">
                <i class="fas fa-plus"></i> Add Scheme
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($schemes as $scheme): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 <?= $scheme->is_active ? 'card-outline card-success' : 'card-outline card-secondary' ?> <?= !empty($scheme->is_default) ? 'border-warning' : '' ?>">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <?= $scheme->scheme_name ?>
                            <?php if (!empty($scheme->is_default)): ?>
                            <span class="badge badge-warning ml-2"><i class="fas fa-star"></i> Default</span>
                            <?php endif; ?>
                            <?php if (!$scheme->is_active): ?>
                            <span class="badge badge-secondary ml-2">Inactive</span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <h4 class="text-success mb-0"><?= number_format($scheme->interest_rate, 2) ?>%</h4>
                                <small class="text-muted">Interest p.a.</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-primary mb-0"><?= $scheme->member_count ?? 0 ?></h4>
                                <small class="text-muted">Members</small>
                            </div>
                        </div>
                        
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span>Min Deposit:</span>
                                <strong><?= number_format($scheme->min_deposit ?? 0, 2) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span>Frequency:</span>
                                <span class="badge badge-info"><?= ucfirst($scheme->deposit_frequency ?? 'Monthly') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span>Lock-in:</span>
                                <strong><?= $scheme->lock_in_period ?? 0 ?> months</strong>
                            </li>
                            <?php if (isset($scheme->penalty_rate) && $scheme->penalty_rate > 0): ?>
                            <li class="list-group-item d-flex justify-content-between py-2">
                                <span>Penalty Rate:</span>
                                <span class="text-danger"><?= number_format($scheme->penalty_rate, 2) ?>%</span>
                            </li>
                            <?php endif; ?>
                        </ul>
                        
                        <?php if (isset($scheme->description)): ?>
                        <p class="text-muted small"><?= $scheme->description ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group btn-group-sm w-100">
                            <button class="btn btn-info btn-edit" data-scheme='<?= json_encode($scheme) ?>'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-success btn-enroll" data-scheme='<?= json_encode($scheme) ?>'>
                                <i class="fas fa-user-plus"></i> Enroll
                            </button>
                            <button class="btn btn-outline-success btn-enroll-all" data-scheme='<?= json_encode($scheme) ?>'>
                                <i class="fas fa-user-friends"></i> Enroll All
                            </button>
                            <a href="<?= site_url('admin/savings?scheme_id=' . $scheme->id) ?>" class="btn btn-primary">
                                <i class="fas fa-users"></i> Members
                            </a>
                            <?php if (empty($scheme->is_default)): ?>
                            <a href="<?= site_url('admin/savings/set_default_scheme/' . $scheme->id) ?>"
                               class="btn btn-warning"
                               onclick="return confirm('Set &quot;<?= addslashes($scheme->scheme_name) ?>&quot; as the default scheme for new members?')">
                                <i class="fas fa-star"></i> Set Default
                            </a>
                            <?php endif; ?>
                            <?php if (empty($scheme->is_default)): ?>
                            <button class="btn btn-<?= $scheme->is_active ? 'warning' : 'success' ?> btn-toggle" 
                                    data-id="<?= $scheme->id ?>" data-status="<?= $scheme->is_active ?>">
                                <i class="fas fa-<?= $scheme->is_active ? 'ban' : 'check' ?>"></i>
                            </button>
                            <?php else: ?>
                            <button class="btn btn-secondary" disabled title="Default scheme cannot be deactivated">
                                <i class="fas fa-lock"></i>
                            </button>
                            <?php endif; ?>
                        </div> 
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Enroll Members Modal -->
<div class="modal fade" id="enrollMembersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="enrollForm" method="post" action="<?= site_url('admin/savings/enroll_members') ?>">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="scheme_id" id="enroll_scheme_id">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus mr-1"></i> Enroll Members</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Members <span class="text-danger">*</span></label>
                        <select name="member_ids[]" id="enroll_members" class="form-control" multiple size="8" required>
                            <?php foreach($members as $m): ?>
                                <option value="<?= $m->id ?>"><?= htmlspecialchars(trim($m->first_name . ' ' . $m->last_name) . ' (' . $m->member_code . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Use Ctrl/Cmd + Click to select multiple</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Monthly Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="monthly_amount" id="enroll_monthly_amount" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="enroll_start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Enroll Members</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Enroll All Modal -->
<div class="modal fade" id="enrollAllModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form id="enrollAllForm" method="post" action="<?= site_url('admin/savings/enroll_all_members') ?>">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="scheme_id" id="enroll_all_scheme_id">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-user-friends mr-1"></i> Enroll All Members</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Monthly Amount</label>
                        <input type="number" step="0.01" name="monthly_amount" id="enroll_all_monthly_amount" class="form-control" required>
                        <small class="text-muted">Defaults to scheme's monthly amount or min deposit</small>
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" id="enroll_all_start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" name="force" id="enroll_all_force">
                        <label class="form-check-label" for="enroll_all_force">Allow multiple accounts per member (force)</label>
                    </div>
                    <div class="alert alert-warning small">
                        This will attempt to create an account for every active member. Existing active accounts will be skipped unless <strong>Allow multiple</strong> is checked.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Enroll All</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scheme Statistics --> 
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-pie mr-1"></i> Members by Scheme</h5>
            </div>
            <div class="card-body">
                <canvas id="schemeDistributionChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-bar mr-1"></i> Deposits by Scheme</h5>
            </div>
            <div class="card-body">
                <canvas id="depositsChart" height="200"></canvas>
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
                                <label>Interest Rate (%) <span class="text-danger">*</span></label>
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Penalty Rate (%)</label>
                                <input type="number" name="penalty_rate" id="penalty_rate" class="form-control" step="0.01" value="0">
                                <small class="text-muted">Applied for early withdrawal</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Maturity Bonus (%)</label>
                                <input type="number" name="maturity_bonus" id="maturity_bonus" class="form-control" step="0.01" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
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
        $('#due_day').val(scheme.due_day || 1);
        $('#description').val(scheme.description);
        $('#addSchemeModal').modal('show');
    });
    
    // Reset modal
    $('#addSchemeModal').on('hidden.bs.modal', function() {
        $('#schemeForm')[0].reset();
        $('#scheme_id').val('');
        $('#modalTitle').text('Add');
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

    // Enroll members to scheme modal
    $('.btn-enroll').click(function() {
        var scheme = $(this).data('scheme');
        $('#enroll_scheme_id').val(scheme.id);
        $('#enroll_monthly_amount').val(scheme.monthly_amount || scheme.min_deposit || 0);
        $('#enroll_start_date').val(new Date().toISOString().slice(0,10));
        $('#enrollMembersModal').modal('show');
    });

    // Enroll all members modal
    $('.btn-enroll-all').click(function() {
        var scheme = $(this).data('scheme');
        $('#enroll_all_scheme_id').val(scheme.id);
        $('#enroll_all_monthly_amount').val(scheme.monthly_amount || scheme.min_deposit || 0);
        $('#enroll_all_start_date').val(new Date().toISOString().slice(0,10));
        $('#enroll_all_force').prop('checked', false);
        $('#enrollAllModal').modal('show');
    });
    
    // Charts
    var schemeNames = <?= json_encode(array_column($schemes, 'scheme_name')) ?>;
    var memberCounts = <?= json_encode(array_column($schemes, 'member_count')) ?>;
    var deposits = <?= json_encode(array_column($schemes, 'total_deposits')) ?>;
    
    new Chart(document.getElementById('schemeDistributionChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: schemeNames,
            datasets: [{
                data: memberCounts,
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    
    new Chart(document.getElementById('depositsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: schemeNames,
            datasets: [{
                label: 'Total Deposits',
                data: deposits,
                backgroundColor: '#28a745'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });
});
</script>
