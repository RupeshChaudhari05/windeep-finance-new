<!-- Bonus Distribution -->

<!-- Yearly Summary -->
<?php if (!empty($yearly_summary)): ?>
<div class="row mb-3">
    <?php foreach ($yearly_summary as $ys): ?>
    <div class="col-md-3">
        <div class="info-box bg-gradient-success">
            <span class="info-box-icon"><i class="fas fa-gift"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Year <?= $ys->bonus_year ?></span>
                <span class="info-box-number"><?= format_amount($ys->total_amount, 0) ?></span>
                <span class="info-box-text"><?= $ys->member_count ?> members</span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row">
    <!-- Bonus Form -->
    <div class="col-lg-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-gift mr-1"></i> Distribute Bonus</h3>
            </div>
            <div class="card-body">
                <form id="bonusForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Bonus Year <span class="text-danger">*</span></label>
                                <select name="bonus_year" class="form-control" required>
                                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Amount Per Member <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="1" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" name="description" class="form-control" placeholder="e.g., Annual bonus 2025">
                            </div>
                        </div>
                    </div>

                    <!-- Member Selection -->
                    <div class="form-group">
                        <label>
                            <i class="fas fa-users mr-1"></i> Select Members
                            <span class="text-danger">*</span>
                        </label>
                        <div class="mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">Deselect All</button>
                            <span class="ml-2 text-muted" id="selectedCount">0 selected</span>
                        </div>

                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover table-sm table-bordered mb-0" id="memberTable">
                                <thead class="thead-light" style="position: sticky; top: 0;">
                                    <tr>
                                        <th width="40"><input type="checkbox" id="checkAll"></th>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Savings A/C</th>
                                        <th class="text-right">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($members)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-3">No active members found</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($members as $m): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="member-check" name="member_ids[]" value="<?= $m->id ?>"
                                                    <?= $m->savings_account_id ? '' : 'disabled title="No active savings account"' ?>>
                                            </td>
                                            <td><code><?= $m->member_code ?></code></td>
                                            <td><?= htmlspecialchars($m->first_name . ' ' . $m->last_name) ?></td>
                                            <td><?= $m->phone ?: '-' ?></td>
                                            <td>
                                                <?php if ($m->savings_account_id): ?>
                                                    <code><?= $m->account_number ?></code>
                                                <?php else: ?>
                                                    <span class="text-danger"><i class="fas fa-exclamation-circle"></i> None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right"><?= $m->savings_account_id ? format_amount($m->current_balance) : '-' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-success btn-lg" id="distributeBtn">
                            <i class="fas fa-gift mr-1"></i> Distribute Bonus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bonus History -->
    <div class="col-lg-4">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-1"></i> Recent Bonus History</h3>
            </div>
            <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-sm table-striped mb-0">
                    <thead class="thead-light" style="position: sticky; top: 0;">
                        <tr>
                            <th>Member</th>
                            <th>Year</th>
                            <th class="text-right">Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bonus_history)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No bonus distributed yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($bonus_history as $bh): ?>
                            <tr>
                                <td>
                                    <small><code><?= $bh->member_code ?></code></small><br>
                                    <small><?= htmlspecialchars($bh->first_name) ?></small>
                                </td>
                                <td><?= $bh->bonus_year ?></td>
                                <td class="text-right text-success font-weight-bold"><?= format_amount($bh->amount) ?></td>
                                <td><small><?= format_date($bh->created_at) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    // Select all / deselect all
    $('#selectAll, #checkAll').on('click', function() {
        $('.member-check:not(:disabled)').prop('checked', true);
        updateCount();
    });
    $('#deselectAll').on('click', function() {
        $('.member-check').prop('checked', false);
        $('#checkAll').prop('checked', false);
        updateCount();
    });
    $('#checkAll').on('change', function() {
        var checked = $(this).prop('checked');
        $('.member-check:not(:disabled)').prop('checked', checked);
        updateCount();
    });
    $(document).on('change', '.member-check', function() {
        updateCount();
    });

    function updateCount() {
        var count = $('.member-check:checked').length;
        $('#selectedCount').text(count + ' selected');
    }

    // Submit bonus form
    $('#bonusForm').on('submit', function(e) {
        e.preventDefault();

        var checked = $('.member-check:checked').length;
        if (checked === 0) {
            toastr.warning('Please select at least one member.');
            return;
        }

        var amount = parseFloat($('[name="amount"]').val());
        var year = $('[name="bonus_year"]').val();
        var totalAmount = (amount * checked).toLocaleString('en-IN', {minimumFractionDigits: 2});

        if (!confirm('Distribute bonus of ₹' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2}) +
                      ' to ' + checked + ' members for year ' + year +
                      '?\nTotal: ₹' + totalAmount)) {
            return;
        }

        var btn = $('#distributeBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');

        $.ajax({
            url: '<?= site_url("admin/savings/process_bonus") ?>',
            type: 'POST',
            data: $(this).serialize() + '&<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    var msg = res.message;
                    if (res.data && res.data.errors && res.data.errors.length > 0) {
                        msg += '<br><br><strong>Errors:</strong><ul>';
                        res.data.errors.forEach(function(err) {
                            msg += '<li>' + err + '</li>';
                        });
                        msg += '</ul>';
                    }
                    toastr.success(msg);
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    toastr.error(res.message || 'Failed to process bonus.');
                    btn.prop('disabled', false).html('<i class="fas fa-gift mr-1"></i> Distribute Bonus');
                }
            },
            error: function(xhr) {
                var res = xhr.responseJSON || {};
                toastr.error(res.message || 'An error occurred');
                btn.prop('disabled', false).html('<i class="fas fa-gift mr-1"></i> Distribute Bonus');
            }
        });
    });
});
</script>
