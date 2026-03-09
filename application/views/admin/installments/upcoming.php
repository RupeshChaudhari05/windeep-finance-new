<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title">
                    <i class="fas fa-clock mr-2"></i>
                    <strong>Upcoming Installments</strong> (Next <?= $days ?> Days)
                </h3>
                <div class="card-tools">
                    <form method="get" class="form-inline">
                        <select name="days" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="7" <?= $days == 7 ? 'selected' : '' ?>>Next 7 Days</option>
                            <option value="15" <?= $days == 15 ? 'selected' : '' ?>>Next 15 Days</option>
                            <option value="30" <?= $days == 30 ? 'selected' : '' ?>>Next 30 Days</option>
                            <option value="60" <?= $days == 60 ? 'selected' : '' ?>>Next 60 Days</option>
                        </select>
                    </form>
                    <button type="button" class="btn btn-sm btn-warning ml-1" onclick="syncStatuses()" title="Sync installment statuses from DB (fixes upcoming/pending/overdue transitions)">
                        <i class="fas fa-sync-alt"></i> Sync Statuses
                    </button>
                    <button type="button" class="btn btn-sm btn-danger ml-1" onclick="rescheduleToFixedDay()" title="Update all installment due dates to use the Fixed Due Day from Settings">
                        <i class="fas fa-calendar-alt"></i> Reschedule Dates
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong><?= $total_count ?></strong> installments due between 
                    <strong><?= format_date('now') ?></strong> and 
                    <strong><?= format_date($end_date) ?></strong>
                    | Total Expected: <strong><?= format_amount($total_amount) ?></strong>
                </div>
                
                <?php if (empty($installments)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <p class="text-muted">No upcoming installments in the selected period</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Due Date</th>
                                    <th>Days Until Due</th>
                                    <th>Member</th>
                                    <th>Loan Number</th>
                                    <th>Product</th>
                                    <th>EMI #</th>
                                    <th class="text-right">EMI Amount</th>
                                    <th>Phone</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($installments as $inst): ?>
                                <?php
                                $days_until = floor((strtotime($inst->due_date) - time()) / 86400);
                                $badge_class = $days_until <= 3 ? 'warning' : 'info';
                                ?>
                                <tr>
                                    <td><?= format_date($inst->due_date, 'd M Y (D)') ?></td>
                                    <td>
                                        <span class="badge badge-<?= $badge_class ?>">
                                            <?= $days_until ?> days
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('admin/members/view/' . $inst->member_id) ?>">
                                            <?= $inst->first_name ?> <?= $inst->last_name ?>
                                        </a>
                                        <br><small class="text-muted"><?= $inst->member_code ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= site_url('admin/loans/view/' . $inst->loan_id) ?>">
                                            <?= $inst->loan_number ?>
                                        </a>
                                    </td>
                                    <td><span class="badge badge-info"><?= $inst->product_name ?></span></td>
                                    <td class="text-center"><strong>#<?= $inst->installment_number ?></strong></td>
                                    <td class="text-right font-weight-bold"><?= format_amount($inst->emi_amount) ?></td>
                                    <td><a href="tel:<?= $inst->phone ?>"><?= $inst->phone ?></a></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= site_url('admin/installments/view/' . $inst->id) ?>" class="btn btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-primary" onclick="sendReminder(<?= $inst->id ?>, '<?= $inst->phone ?>')" title="Send Reminder">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <th colspan="6" class="text-right">Total Expected:</th>
                                    <th class="text-right"><?= format_amount($total_amount) ?></th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function sendReminder(installmentId, phone) {
    if (!confirm('Send payment reminder to ' + phone + '?')) return;
    
    $.ajax({
        url: '<?= site_url('admin/installments/send_reminder') ?>',
        method: 'POST',
        data: {
            installment_id: installmentId,
            <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        }
    });
}

function syncStatuses() {
    if (!confirm('This will update all installment statuses (upcoming → pending/overdue) based on due dates. Continue?')) return;
    $.ajax({
        url: '<?= site_url('admin/installments/sync_statuses') ?>',
        method: 'POST',
        data: { <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>' },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                toastr.error(response.message || 'Sync failed');
            }
        },
        error: function() { toastr.error('Sync request failed'); }
    });
}

function rescheduleToFixedDay() {
    if (!confirm('This will update ALL unpaid installment due dates to the Fixed Due Day configured in Settings.\n\nInstallments that are already on the correct day will be skipped.\n\nContinue?')) return;
    $.ajax({
        url: '<?= site_url('admin/installments/reschedule_to_fixed_day') ?>',
        method: 'POST',
        data: { <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>' },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(function() { location.reload(); }, 2000);
            } else {
                toastr.error(response.message || 'Reschedule failed');
            }
        },
        error: function() { toastr.error('Reschedule request failed'); }
    });
}
</script>
