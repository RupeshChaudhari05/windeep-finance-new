<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-piggy-bank mr-1"></i> Edit Savings Account</h3>
            </div>
            <form action="<?= site_url('admin/savings/update/' . $account->id) ?>" method="post" id="savingsForm">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                
                <div class="card-body">
                    <!-- Member Display (readonly) -->
                    <div class="form-group">
                        <label>Member</label>
                        <div class="alert alert-light"> 
                            <strong><?= ($account->member_code ?? '') ?> - <?= ($account->first_name ?? '') ?> <?= ($account->last_name ?? '') ?></strong>
                        </div>
                    </div>
                    
                    <!-- Scheme Selection -->
                    <div class="form-group">
                        <label for="scheme_id">Savings Scheme <span class="text-danger">*</span></label>
                        <select class="form-control" id="scheme_id" name="scheme_id" required>
                            <option value="">Select Scheme</option>
                            <?php foreach ($schemes as $scheme): ?>
                                <option value="<?= $scheme->id ?>" <?= ($account->scheme_id == $scheme->id) ? 'selected' : '' ?>
                                        data-min="<?= $scheme->monthly_amount ?>" 
                                        data-max="<?= $scheme->maximum_amount ?? '' ?>"
                                        data-interest="<?= $scheme->interest_rate ?>"
                                        data-duration="<?= $scheme->duration_months ?>">
                                    <?= $scheme->scheme_name ?> (<?= $scheme->interest_rate ?>% p.a.)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small id="schemeInfo" class="form-text text-muted"></small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="monthly_amount">Monthly Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="monthly_amount" name="monthly_amount" 
                                       value="<?= set_value('monthly_amount', $account->monthly_amount) ?>" required min="1"
                                       placeholder="Enter monthly deposit amount">
                                <small id="amountRange" class="form-text text-muted"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="due_date">Due Date <span class="text-danger">*</span></label>
                                <select class="form-control" id="due_date" name="due_date" required>
                                    <?php for ($i = 1; $i <= 28; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($i == ($account->due_date ?? (int)date('j', strtotime($account->start_date ?? 'now')))) ? 'selected' : '' ?>><?= ordinal_suffix($i) ?> of every month</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?= set_value('start_date', $account->start_date) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="duration_months">Duration (Months)</label>
                                <input type="number" class="form-control" id="duration_months" name="duration_months" 
                                       value="<?= set_value('duration_months', $account->duration_months ?? '') ?>" min="1" max="240"
                                       placeholder="Leave blank for indefinite">
                                <small class="form-text text-muted">Leave blank for open-ended savings</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active" <?= ($account->status == 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($account->status == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            <option value="closed" <?= ($account->status == 'closed') ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2"><?= set_value('remarks', $account->remarks ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Update Account
                    </button>
                    <a href="<?= site_url('admin/savings/view/' . $account->id) ?>" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calculator mr-1"></i> Savings Preview</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td>Monthly Deposit:</td>
                        <td class="text-right font-weight-bold" id="calcMonthly"><?= format_amount($account->monthly_amount, 0) ?></td>
                    </tr>
                    <tr>
                        <td>Duration:</td>
                        <td class="text-right" id="calcDuration"><?= $account->duration_months ?? '-' ?></td>
                    </tr>
                    <tr>
                        <td>Interest Rate:</td>
                        <td class="text-right" id="calcInterest">-</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Scheme selection change to update hints
    $('#scheme_id').on('change', function() {
        var selected = $(this).find(':selected');
        if (selected.val()) {
            var min = selected.data('min');
            var max = selected.data('max');
            var interest = selected.data('interest');
            var duration = selected.data('duration');

            $('#amountRange').text('Min: <?= get_currency_symbol() ?>' + min.toLocaleString() + ' | Max: <?= get_currency_symbol() ?>' + (max || 'No limit').toLocaleString());
            $('#monthly_amount').attr('min', min).attr('max', max || 99999999);
            $('#duration_months').val(duration || $('#duration_months').val());

            $('#calcInterest').text(interest ? interest + '% p.a.' : '-');
        }
    });

    // Trigger scheme change to set info
    if ($('#scheme_id').val()) {
        $('#scheme_id').trigger('change');
    }
});
</script>