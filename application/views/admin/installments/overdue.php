<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-danger">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Overdue Installments</strong>
                </h3>
                <div class="card-tools">
                    <span class="badge badge-light badge-lg">Total Overdue: <?= format_amount($total_overdue) ?></span>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Summary Tabs -->
                <ul class="nav nav-pills mb-3">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="pill" href="#all">
                            All (<?= $total_count ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#critical">
                            Critical 90+ Days (<?= count($critical) ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#serious">
                            Serious 30-90 Days (<?= count($serious) ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#recent">
                            Recent &lt;30 Days (<?= count($recent) ?>)
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <!-- All Overdue -->
                    <div class="tab-pane active" id="all">
                        <?php echo render_installment_table($installments); ?>
                    </div>
                    
                    <!-- Critical -->
                    <div class="tab-pane" id="critical">
                        <?php echo render_installment_table($critical); ?>
                    </div>
                    
                    <!-- Serious -->
                    <div class="tab-pane" id="serious">
                        <?php echo render_installment_table($serious); ?>
                    </div>
                    
                    <!-- Recent -->
                    <div class="tab-pane" id="recent">
                        <?php echo render_installment_table($recent); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function render_installment_table($installments) {
    if (empty($installments)) {
        return '<div class="text-center py-4 text-muted">No records found</div>';
    }
    
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Due Date</th>
                    <th>Days Overdue</th>
                    <th>Member</th>
                    <th>Loan Number</th>
                    <th>EMI #</th>
                    <th class="text-right">Amount Due</th>
                    <th class="text-right">Paid</th>
                    <th class="text-right">Balance</th>
                    <th>Phone</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($installments as $inst): ?>
                <?php 
                $balance = $inst->emi_amount - $inst->total_paid;
                $severity_class = '';
                if ($inst->days_overdue > 90) $severity_class = 'table-danger';
                elseif ($inst->days_overdue > 30) $severity_class = 'table-warning';
                ?>
                <tr class="<?= $severity_class ?>">
                    <td><?= format_date($inst->due_date) ?></td>
                    <td>
                        <span class="badge badge-danger">
                            <i class="fas fa-exclamation-circle"></i> <?= $inst->days_overdue ?> days
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
                    <td class="text-center"><strong>#<?= $inst->installment_number ?></strong></td>
                    <td class="text-right font-weight-bold"><?= format_amount($inst->emi_amount) ?></td>
                    <td class="text-right text-success"><?= format_amount($inst->total_paid) ?></td>
                    <td class="text-right"><strong class="text-danger"><?= format_amount($balance) ?></strong></td>
                    <td><a href="tel:<?= $inst->phone ?>"><?= $inst->phone ?></a></td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="<?= site_url('admin/loans/collect/' . $inst->loan_id) ?>" class="btn btn-success" title="Collect Payment">
                                <i class="fas fa-rupee-sign"></i>
                            </a>
                            <button type="button" class="btn btn-warning" onclick="sendReminder(<?= $inst->id ?>, '<?= $inst->phone ?>')" title="Send Reminder">
                                <i class="fas fa-sms"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}
?>

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
</script>
