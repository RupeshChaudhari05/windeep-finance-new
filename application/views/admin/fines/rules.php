<!-- Fine Rules Management -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-balance-scale mr-1"></i> Fine Rules Configuration</h3>
        <div class="card-tools">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addRuleModal">
                <i class="fas fa-plus"></i> Add Rule
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="rulesTable">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Rule Name</th>
                        <th>Fine Type</th>
                        <th>Amount Type</th>
                        <th class="text-right">Amount/Rate</th>
                        <th>Grace Period</th>
                        <th>Max Fine</th>
                        <th>Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rules)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-balance-scale fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No fine rules configured. Click "Add Rule" to create one.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($rules as $rule): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td>
                                <strong><?= $rule->rule_name ?></strong>
                                <?php if ($rule->description): ?>
                                <br><small class="text-muted"><?= character_limiter($rule->description, 50) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= 
                                    $rule->fine_type == 'late_emi' ? 'danger' : 
                                    ($rule->fine_type == 'late_savings' ? 'warning' : 
                                    ($rule->fine_type == 'meeting_absence' ? 'info' : 'secondary'))
                                ?>">
                                    <?= ucwords(str_replace('_', ' ', $rule->fine_type)) ?>
                                </span>
                            </td>
                            <td>
                                <?= $rule->amount_type == 'percentage' ? 'Percentage' : 'Fixed Amount' ?>
                            </td>
                            <td class="text-right">
                                <?php if ($rule->amount_type == 'percentage'): ?>
                                    <?= $rule->amount_value ?>%
                                    <br><small class="text-muted">of overdue amount</small>
                                <?php else: ?>
                                    ₹<?= number_format($rule->amount_value, 2) ?>
                                    <br><small class="text-muted"><?= $rule->frequency ?? 'one-time' ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $rule->grace_days ?: 0 ?> days
                            </td>
                            <td>
                                <?php if ($rule->max_fine_amount): ?>
                                    ₹<?= number_format($rule->max_fine_amount) ?>
                                <?php else: ?>
                                    <span class="text-muted">No limit</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($rule->is_active): ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> Active</span>
                                <?php else: ?>
                                <span class="badge badge-danger"><i class="fas fa-times"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-info btn-sm btn-edit" data-rule='<?= json_encode($rule) ?>' title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-<?= $rule->is_active ? 'warning' : 'success' ?> btn-sm btn-toggle" 
                                            data-id="<?= $rule->id ?>" 
                                            data-status="<?= $rule->is_active ?>"
                                            title="<?= $rule->is_active ? 'Deactivate' : 'Activate' ?>">
                                        <i class="fas fa-<?= $rule->is_active ? 'ban' : 'check' ?>"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $rule->id ?>" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

<!-- Quick Actions -->
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h3 class="card-title"><i class="fas fa-robot mr-1"></i> Auto Fine Job</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Run automatic fine calculation for all overdue EMIs and savings based on configured rules.</p>
                <a href="<?= site_url('admin/fines/run_auto_fines') ?>" class="btn btn-danger btn-block" 
                   onclick="return confirm('This will apply fines to all overdue accounts. Continue?')">
                    <i class="fas fa-play mr-1"></i> Run Auto Fine Job
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-coins"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">This Month Fines</span>
                <span class="info-box-number">
                    ₹<?= number_format($this->db->select_sum('fine_amount')
                                               ->where('MONTH(fine_date)', date('m'))
                                               ->where('YEAR(fine_date)', date('Y'))
                                               ->get('fines')->row()->fine_amount ?? 0) ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Collection</span>
                <span class="info-box-number">
                    ₹<?= number_format($this->db->select('SUM(fine_amount - IFNULL(paid_amount, 0) - IFNULL(waived_amount, 0)) as pending', FALSE)
                                               ->where_in('status', ['pending', 'partial'])
                                               ->get('fines')->row()->pending ?? 0) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Rule Modal -->
<div class="modal fade" id="addRuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="ruleForm" method="post" action="<?= site_url('admin/fines/save_rule') ?>">
                <input type="hidden" name="id" id="rule_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-balance-scale mr-1"></i> <span id="modalTitle">Add</span> Fine Rule</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Rule Name <span class="text-danger">*</span></label>
                                <input type="text" name="rule_name" id="rule_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fine Type <span class="text-danger">*</span></label>
                                <select name="fine_type" id="fine_type" class="form-control" required>
                                    <option value="late_emi">Late EMI Payment</option>
                                    <option value="late_savings">Late Savings Deposit</option>
                                    <option value="meeting_absence">Meeting Absence</option>
                                    <option value="document_missing">Missing Documents</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Amount Type <span class="text-danger">*</span></label>
                                <select name="amount_type" id="amount_type" class="form-control" required>
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Amount/Rate <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend" id="amountPrefix"><span class="input-group-text">₹</span></div>
                                    <input type="number" name="amount_value" id="amount_value" class="form-control" required step="0.01" min="0">
                                    <div class="input-group-append" id="amountSuffix" style="display:none"><span class="input-group-text">%</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Frequency</label>
                                <select name="frequency" id="frequency" class="form-control">
                                    <option value="one_time">One Time</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Grace Period (Days)</label>
                                <input type="number" name="grace_days" id="grace_days" class="form-control" min="0" value="0">
                                <small class="text-muted">No fine during grace period</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Maximum Fine</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" name="max_fine_amount" id="max_fine_amount" class="form-control" min="0">
                                </div>
                                <small class="text-muted">Leave empty for no limit</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Applies To</label>
                                <select name="applies_to" id="applies_to" class="form-control">
                                    <option value="all">All Members</option>
                                    <option value="loan_holders">Loan Holders Only</option>
                                    <option value="savings_accounts">Savings Accounts Only</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="is_active" id="is_active" class="custom-control-input" value="1" checked>
                            <label class="custom-control-label" for="is_active">Active (rule will be applied automatically)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#rulesTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25
    });
    
    // Amount type toggle
    $('#amount_type').change(function() {
        if ($(this).val() == 'percentage') {
            $('#amountPrefix').hide();
            $('#amountSuffix').show();
        } else {
            $('#amountPrefix').show();
            $('#amountSuffix').hide();
        }
    });
    
    // Reset modal on close
    $('#addRuleModal').on('hidden.bs.modal', function() {
        $('#ruleForm')[0].reset();
        $('#rule_id').val('');
        $('#modalTitle').text('Add');
        $('#is_active').prop('checked', true);
        $('#amountPrefix').show();
        $('#amountSuffix').hide();
    });
    
    // Edit rule
    $('.btn-edit').click(function() {
        var rule = $(this).data('rule');
        $('#modalTitle').text('Edit');
        $('#rule_id').val(rule.id);
        $('#rule_name').val(rule.rule_name);
        $('#fine_type').val(rule.fine_type);
        $('#description').val(rule.description);
        $('#amount_type').val(rule.amount_type).trigger('change');
        $('#amount_value').val(rule.amount_value);
        $('#frequency').val(rule.frequency);
        $('#grace_days').val(rule.grace_days);
        $('#max_fine_amount').val(rule.max_fine_amount);
        $('#applies_to').val(rule.applies_to);
        $('#is_active').prop('checked', rule.is_active == 1);
        $('#addRuleModal').modal('show');
    });
    
    // Toggle status
    $('.btn-toggle').click(function() {
        var ruleId = $(this).data('id');
        var status = $(this).data('status') == 1 ? 0 : 1;
        
        $.post('<?= site_url('admin/fines/toggle_rule_status') ?>', {id: ruleId, is_active: status}, function(response) {
            if (response.success) {
                toastr.success('Rule status updated');
                location.reload();
            } else {
                toastr.error(response.message || 'Operation failed');
            }
        }, 'json');
    });
    
    // Delete rule
    $('.btn-delete').click(function() {
        var ruleId = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this rule?')) {
            $.post('<?= site_url('admin/fines/delete_rule') ?>', {id: ruleId}, function(response) {
                if (response.success) {
                    toastr.success('Rule deleted');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to delete');
                }
            }, 'json');
        }
    });
    
    // Form submission
    $('#ruleForm').submit(function(e) {
        e.preventDefault();
        
        $.post($(this).attr('action'), $(this).serialize(), function(response) {
            if (response.success) {
                toastr.success('Rule saved successfully');
                $('#addRuleModal').modal('hide');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to save rule');
            }
        }, 'json');
    });
});
</script>
