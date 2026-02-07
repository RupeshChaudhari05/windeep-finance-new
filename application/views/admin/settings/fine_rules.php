<!-- Fine Rules Settings - Indian Banking Style -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-gavel mr-1"></i> Fine Rules Configuration</h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addRuleModal">
                        <i class="fas fa-plus"></i> Add Rule
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0" id="rulesTable">
                    <thead class="thead-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Rule Name</th>
                            <th>Applies To</th>
                            <th>Calculation Type</th>
                            <th>Initial Fine</th>
                            <th>Daily Fine</th>
                            <th>Grace Period</th>
                            <th>Max Fine</th>
                            <th>Status</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($rules as $rule): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td>
                                <strong><?= $rule->rule_name ?></strong>
                                <?php if (isset($rule->description) && $rule->description): ?>
                                <br><small class="text-muted"><?= $rule->description ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $applies_class = [
                                    'loan' => 'primary',
                                    'savings' => 'success',
                                    'all' => 'info'
                                ];
                                $applies_to = $rule->applies_to ?? 'all';
                                ?>
                                <span class="badge badge-<?= $applies_class[$applies_to] ?? 'secondary' ?>">
                                    <?= ucfirst($applies_to) ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $type_labels = [
                                    'fixed' => '<span class="badge badge-secondary">Fixed Amount</span>',
                                    'percentage' => '<span class="badge badge-primary">% of Due</span>',
                                    'per_day' => '<span class="badge badge-warning">Per Day</span>',
                                    'fixed_plus_daily' => '<span class="badge badge-danger">Fixed + Daily</span>'
                                ];
                                echo $type_labels[$rule->calculation_type ?? 'fixed'] ?? '<span class="badge badge-secondary">Fixed</span>';
                                ?>
                            </td>
                            <td class="text-right">
                                <?php if (($rule->calculation_type ?? '') == 'percentage'): ?>
                                    <strong><?= number_format($rule->fine_value ?? 0, 2) ?>%</strong>
                                <?php else: ?>
                                    <strong>₹<?= number_format($rule->fine_value ?? 0, 2) ?></strong>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <?php if (in_array($rule->calculation_type ?? '', ['per_day', 'fixed_plus_daily'])): ?>
                                    <strong>₹<?= number_format($rule->per_day_amount ?? 0, 2) ?></strong>/day
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= $rule->grace_period_days ?? 0 ?> days</td>
                            <td class="text-right">
                                <?= ($rule->max_fine_amount ?? 0) > 0 ? '₹' . number_format($rule->max_fine_amount, 2) : '<span class="text-muted">No limit</span>' ?>
                            </td>
                            <td>
                                <?php if ($rule->is_active ?? 1): ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> Active</span>
                                <?php else: ?>
                                <span class="badge badge-secondary"><i class="fas fa-times"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-warning btn-edit" data-rule='<?= json_encode($rule) ?>' title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-<?= ($rule->is_active ?? 1) ? 'secondary' : 'success' ?> btn-toggle" 
                                            data-id="<?= $rule->id ?>" data-status="<?= $rule->is_active ?? 1 ?>">
                                        <i class="fas fa-<?= ($rule->is_active ?? 1) ? 'ban' : 'check' ?>"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Quick Reference -->
    <div class="col-md-4">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Fine Calculation Guide</h3>
            </div>
            <div class="card-body">
                <h6><i class="fas fa-building text-primary mr-1"></i> Indian Banking Standard</h6>
                <p class="small text-muted">Common fine structures used in Indian NBFCs and MFIs:</p>
                
                <div class="callout callout-warning">
                    <h6>Fixed + Daily Fine</h6>
                    <p class="small mb-0">
                        <strong>Example:</strong> ₹100 initial fine + ₹10 per day after grace period.<br>
                        <em>Due Date: 5th → Grace till 10th → Fine from 11th</em>
                    </p>
                </div>
                
                <div class="callout callout-info">
                    <h6>Percentage Based</h6>
                    <p class="small mb-0">
                        <strong>Example:</strong> 2% of overdue amount per month.<br>
                        <em>Used for larger loans</em>
                    </p>
                </div>
                
                <hr>
                <h6><i class="fas fa-calculator mr-1"></i> Example Calculation</h6>
                <table class="table table-sm table-bordered">
                    <tr class="bg-light">
                        <td>Due Date</td>
                        <td class="text-right">5th Jan</td>
                    </tr>
                    <tr>
                        <td>Grace Period</td>
                        <td class="text-right">5 days</td>
                    </tr>
                    <tr>
                        <td>Fine Starts</td>
                        <td class="text-right">11th Jan</td>
                    </tr>
                    <tr>
                        <td>Initial Fine</td>
                        <td class="text-right">₹100</td>
                    </tr>
                    <tr>
                        <td>Daily After</td>
                        <td class="text-right">₹10/day</td>
                    </tr>
                    <tr class="bg-warning">
                        <td>Fine on 20th Jan</td>
                        <td class="text-right"><strong>₹100 + (10×10) = ₹200</strong></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog mr-1"></i> Quick Actions</h3>
            </div>
            <div class="card-body">
                <button class="btn btn-outline-primary btn-block mb-2" id="presetLoan">
                    <i class="fas fa-file-invoice-dollar mr-1"></i> Create Loan EMI Fine Rule
                </button>
                <button class="btn btn-outline-success btn-block mb-2" id="presetSavings">
                    <i class="fas fa-piggy-bank mr-1"></i> Create Savings Fine Rule
                </button>
                <button class="btn btn-outline-warning btn-block" id="presetMembership">
                    <i class="fas fa-users mr-1"></i> Create Membership Fine Rule
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Rule Modal -->
<div class="modal fade" id="addRuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="ruleForm" method="post" action="<?= site_url('admin/settings/save_fine_rule') ?>">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <input type="hidden" name="id" id="rule_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-gavel mr-1"></i> <span id="modalTitle">Add</span> Fine Rule</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Rule Name <span class="text-danger">*</span></label>
                                <input type="text" name="rule_name" id="rule_name" class="form-control" required
                                       placeholder="e.g., Loan EMI Late Fine">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Applies To <span class="text-danger">*</span></label>
                                <select name="applies_to" id="applies_to" class="form-control" required>
                                    <option value="loan">Loan EMI</option>
                                    <option value="savings">Savings Contribution</option>
                                    <option value="both">Both (Loan & Savings)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Calculation Type <span class="text-danger">*</span></label>
                                <select name="fine_type" id="fine_type" class="form-control" required>
                                    <option value="fixed">Fixed Amount Only</option>
                                    <option value="percentage">Percentage of Due Amount</option>
                                    <option value="per_day">Per Day Late Only</option>
                                    <option value="fixed_plus_daily">Fixed + Daily (Indian Standard)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Grace Period (Days)</label>
                                <input type="number" name="grace_period" id="grace_period" class="form-control" value="5" min="0">
                                <small class="text-muted">Days after due date before fine applies</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4" id="fixedAmountGroup">
                            <div class="form-group">
                                <label>Initial/Fixed Fine (₹) <span class="text-danger">*</span></label>
                                <input type="number" name="fine_amount" id="fine_amount" class="form-control" step="0.01" value="100">
                                <small class="text-muted">One-time initial fine amount</small>
                            </div>
                        </div>
                        <div class="col-md-4" id="percentageGroup" style="display:none;">
                            <div class="form-group">
                                <label>Fine Rate (%) <span class="text-danger">*</span></label>
                                <input type="number" name="fine_rate" id="fine_rate" class="form-control" step="0.01" value="2">
                                <small class="text-muted">Percentage of due amount</small>
                            </div>
                        </div>
                        <div class="col-md-4" id="perDayGroup" style="display:none;">
                            <div class="form-group">
                                <label>Per Day Fine (₹) <span class="text-danger">*</span></label>
                                <input type="number" name="per_day_amount" id="per_day_amount" class="form-control" step="0.01" value="10">
                                <small class="text-muted">Added daily after grace period</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Maximum Fine (₹)</label>
                                <input type="number" name="max_fine" id="max_fine" class="form-control" step="0.01">
                                <small class="text-muted">Leave empty for no limit</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Days Range (Min)</label>
                                <input type="number" name="min_days" id="min_days" class="form-control" value="1" min="0">
                                <small class="text-muted">Minimum days late for this rule</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Days Range (Max)</label>
                                <input type="number" name="max_days" id="max_days" class="form-control" value="9999">
                                <small class="text-muted">Maximum days (9999 for unlimited)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2"
                                  placeholder="Describe when and how this fine rule applies..."></textarea>
                    </div>
                    
                    <!-- Fine Preview -->
                    <div class="card bg-light" id="finePreview">
                        <div class="card-body">
                            <h6><i class="fas fa-calculator mr-1"></i> Fine Preview</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">After 10 days late:</small>
                                    <div class="h5 text-danger mb-0" id="preview10">₹0</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">After 30 days late:</small>
                                    <div class="h5 text-danger mb-0" id="preview30">₹0</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">After 60 days late:</small>
                                    <div class="h5 text-danger mb-0" id="preview60">₹0</div>
                                </div>
                            </div>
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
    
    // Toggle amount/rate/daily fields based on type
    $('#fine_type').change(function() {
        var type = $(this).val();
        
        $('#fixedAmountGroup').hide();
        $('#percentageGroup').hide();
        $('#perDayGroup').hide();
        
        switch(type) {
            case 'fixed':
                $('#fixedAmountGroup').show();
                break;
            case 'percentage':
                $('#percentageGroup').show();
                break;
            case 'per_day':
                $('#perDayGroup').show();
                break;
            case 'fixed_plus_daily':
                $('#fixedAmountGroup').show();
                $('#perDayGroup').show();
                break;
        }
        
        updatePreview();
    });
    
    // Update preview on input change
    $('#fine_amount, #fine_rate, #per_day_amount, #grace_period, #max_fine').on('input', function() {
        updatePreview();
    });
    
    function updatePreview() {
        var type = $('#fine_type').val();
        var fixed = parseFloat($('#fine_amount').val()) || 0;
        var rate = parseFloat($('#fine_rate').val()) || 0;
        var daily = parseFloat($('#per_day_amount').val()) || 0;
        var grace = parseInt($('#grace_period').val()) || 0;
        var maxFine = parseFloat($('#max_fine').val()) || 999999;
        var sampleDue = 5000; // Sample due amount for percentage calc
        
        [10, 30, 60].forEach(function(days) {
            var effectiveDays = Math.max(0, days - grace);
            var fine = 0;
            
            switch(type) {
                case 'fixed':
                    fine = effectiveDays > 0 ? fixed : 0;
                    break;
                case 'percentage':
                    fine = effectiveDays > 0 ? (sampleDue * rate / 100) : 0;
                    break;
                case 'per_day':
                    fine = effectiveDays * daily;
                    break;
                case 'fixed_plus_daily':
                    fine = effectiveDays > 0 ? fixed + (effectiveDays * daily) : 0;
                    break;
            }
            
            fine = Math.min(fine, maxFine);
            $('#preview' + days).text('₹' + fine.toFixed(2));
        });
    }
    
    // Edit rule
    $('.btn-edit').click(function() {
        var rule = $(this).data('rule');
        console.log('Editing rule:', rule); // Debug
        
        $('#modalTitle').text('Edit Rule');
        $('#rule_id').val(rule.id);
        $('#rule_name').val(rule.rule_name || '');
        
        // Set dropdown values - handle both 'all' and 'both' for backwards compatibility
        var appliesTo = rule.applies_to || 'loan';
        if (appliesTo === 'all') appliesTo = 'both';
        $('#applies_to').val(appliesTo);
        
        // Get calculation type - check multiple field names for compatibility
        var calcType = rule.calculation_type || rule.fine_type || 'fixed';
        // Normalize and map historical/variant values to current select options
        if (typeof calcType === 'string') {
            calcType = calcType.toLowerCase().trim();
            calcType = calcType.replace(/\s+/g, '_');
            // Common legacy values -> map to supported types
            if (calcType === 'loan_late' || calcType === 'savings_late') calcType = 'fixed';
            if (calcType === 'perday' || calcType === 'per-day') calcType = 'per_day';
            if (calcType === 'fixedplusdaily' || calcType === 'fixed_plus_daily' || (calcType.indexOf('plus') !== -1 && calcType.indexOf('daily') !== -1)) calcType = 'fixed_plus_daily';
            // Fallback to fixed when unknown
            var allowed = ['fixed', 'percentage', 'per_day', 'fixed_plus_daily', 'slab'];
            if (allowed.indexOf(calcType) === -1) calcType = 'fixed';
        } else {
            calcType = 'fixed';
        }

        // Try to select the exact matching option; if not found, attempt relaxed matching
        var $opt = $('#fine_type option[value="' + calcType + '"]');
        if ($opt.length) {
            $opt.prop('selected', true);
        } else {
            // Attempt to match by partial keywords
            var found = false;
            $('#fine_type option').each(function() {
                var v = $(this).val().toLowerCase();
                if (v.indexOf(calcType) !== -1 || calcType.indexOf(v) !== -1) {
                    $(this).prop('selected', true);
                    found = true;
                    return false; // break
                }
            });
            if (!found) {
                // As last resort, pick 'fixed'
                $('#fine_type option[value="fixed"]').prop('selected', true);
                calcType = 'fixed';
            }
        }

        // Trigger change to show/hide appropriate fields
        $('#fine_type').trigger('change');
        
        // Set values AFTER triggering change to ensure fields are visible
        setTimeout(function() {
            // For percentage type, fine_value contains the percentage rate
            $('#fine_amount').val(rule.fine_value || 0);
            $('#fine_rate').val(rule.fine_value || 0); // Use fine_value for both
            $('#per_day_amount').val(rule.per_day_amount || 0);
            $('#grace_period').val(rule.grace_period_days || 0);
            $('#max_fine').val(rule.max_fine_amount || '');
            $('#min_days').val(rule.min_days || 1);
            $('#max_days').val(rule.max_days || 9999);
            $('#description').val(rule.description || '');
            
            // Force refresh dropdown display
            $('#applies_to').val(appliesTo);
            $('#fine_type').val(calcType);
            
            updatePreview();
        }, 100);
        
        $('#addRuleModal').modal('show');
    });
    
    // Preset buttons
    $('#presetLoan').click(function() {
        resetForm();
        $('#rule_name').val('Loan EMI Late Fine');
        $('#applies_to').val('loan');
        $('#fine_type').val('fixed_plus_daily').trigger('change');
        $('#fine_amount').val(100);
        $('#per_day_amount').val(10);
        $('#grace_period').val(5);
        $('#description').val('₹100 initial fine after 5 days grace period, then ₹10 per day');
        updatePreview();
        $('#addRuleModal').modal('show');
    });
    
    $('#presetSavings').click(function() {
        resetForm();
        $('#rule_name').val('Savings Late Fine');
        $('#applies_to').val('savings');
        $('#fine_type').val('fixed_plus_daily').trigger('change');
        $('#fine_amount').val(50);
        $('#per_day_amount').val(5);
        $('#grace_period').val(3);
        $('#description').val('₹50 initial fine after 3 days grace period, then ₹5 per day');
        updatePreview();
        $('#addRuleModal').modal('show');
    });
    
    $('#presetMembership').click(function() {
        resetForm();
        $('#rule_name').val('Membership Due Fine');
        $('#applies_to').val('all');
        $('#fine_type').val('fixed').trigger('change');
        $('#fine_amount').val(200);
        $('#grace_period').val(15);
        $('#description').val('₹200 flat fine for membership due after 15 days');
        updatePreview();
        $('#addRuleModal').modal('show');
    });
    
    function resetForm() {
        $('#ruleForm')[0].reset();
        $('#rule_id').val('');
        $('#modalTitle').text('Add');
        $('#fine_type').val('fixed').trigger('change');
    }
    
    // Reset modal
    $('#addRuleModal').on('hidden.bs.modal', function() {
        resetForm();
    });
    
    // Toggle status
    $('.btn-toggle').click(function() {
        var id = $(this).data('id');
        var status = $(this).data('status') == 1 ? 0 : 1;
        $.post('<?= site_url('admin/settings/toggle_fine_rule') ?>', {
            id: id, 
            is_active: status,
            <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
        }, function(response) {
            if (response.success) {
                toastr.success('Rule status updated');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to update');
            }
        }, 'json');
    });
    
    // Initial preview
    updatePreview();
});
</script>
