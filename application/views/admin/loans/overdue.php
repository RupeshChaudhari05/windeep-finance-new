<!-- Overdue Loans -->
<div class="card">
    <div class="card-header bg-danger text-white">
        <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Overdue Loans</h3>
        <div class="card-tools">
            <a href="<?= site_url('admin/loans') ?>" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Loans
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="overdueTable">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Loan No</th>
                        <th>Member</th>
                        <th>Contact</th>
                        <th class="text-right">EMI Amount</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th class="text-right">Overdue Amount</th>
                        <th class="text-right">Fine Amount</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($overdue)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">No overdue loans. Great job!</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $i = 1; 
                        $total_overdue = 0;
                        $total_fine = 0;
                        foreach ($overdue as $loan): 
                            $days_overdue = floor((time() - strtotime($loan->due_date)) / 86400);
                            $overdue_amount = $loan->emi_amount - ($loan->total_paid ?? 0);
                            $fine_amount = $loan->fine_amount ?? 0;
                            $total_overdue += $overdue_amount;
                            $total_fine += $fine_amount;
                        ?>
                        <tr class="<?= $days_overdue > 90 ? 'table-danger' : ($days_overdue > 30 ? 'table-warning' : '') ?>">
                            <td><?= $i++ ?></td>
                            <td>
                                <a href="<?= site_url('admin/loans/view/' . $loan->id) ?>" class="font-weight-bold">
                                    <?= $loan->loan_number ?>
                                </a>
                                <br><small class="text-muted">EMI #<?= $loan->installment_number ?></small>
                            </td>
                            <td>
                                <a href="<?= site_url('admin/members/view/' . $loan->member_id) ?>">
                                    <?= $loan->member_code ?><br>
                                    <small><?= $loan->first_name ?> <?= $loan->last_name ?></small>
                                </a>
                            </td>
                            <td>
                                <a href="tel:<?= $loan->phone ?>"><?= $loan->phone ?></a>
                            </td>
                            <td class="text-right">₹<?= number_format($loan->emi_amount, 2) ?></td>
                            <td>
                                <span class="text-danger"><?= date('d M Y', strtotime($loan->due_date)) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $days_overdue > 90 ? 'danger' : ($days_overdue > 30 ? 'warning' : 'info') ?>">
                                    <?= $days_overdue ?> days
                                </span>
                            </td>
                            <td class="text-right font-weight-bold text-danger">₹<?= number_format($overdue_amount, 2) ?></td>
                            <td class="text-right text-warning">₹<?= number_format($fine_amount, 2) ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= site_url('admin/loans/collect/' . $loan->id) ?>" class="btn btn-success btn-sm" title="Collect Payment">
                                        <i class="fas fa-rupee-sign"></i>
                                    </a>
                                    <a href="<?= site_url('admin/loans/view/' . $loan->id) ?>" class="btn btn-info btn-sm" title="View Loan">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-warning btn-sm btn-send-reminder" data-id="<?= $loan->id ?>" data-phone="<?= $loan->phone ?>" title="Send Reminder">
                                        <i class="fas fa-bell"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($overdue)): ?>
                <tfoot class="bg-light">
                    <tr>
                        <th colspan="7" class="text-right">Total:</th>
                        <th class="text-right text-danger">₹<?= number_format($total_overdue, 2) ?></th>
                        <th class="text-right text-warning">₹<?= number_format($total_fine, 2) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Summary Stats -->
<div class="row mt-3">
    <div class="col-md-3">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= count($overdue ?? []) ?></h3>
                <p>Total Overdue Loans</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>₹<?= number_format($total_overdue ?? 0) ?></h3>
                <p>Total Overdue Amount</p>
            </div>
            <div class="icon"><i class="fas fa-rupee-sign"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>₹<?= number_format($total_fine ?? 0) ?></h3>
                <p>Total Fine Amount</p>
            </div>
            <div class="icon"><i class="fas fa-gavel"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3><?= $stats['npa_count'] ?? 0 ?></h3>
                <p>NPA Accounts (90+ days)</p>
            </div>
            <div class="icon"><i class="fas fa-ban"></i></div>
        </div>
    </div>
</div>

<!-- Overdue Aging Analysis -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Aging Analysis</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="info-box bg-gradient-info">
                    <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">1-30 Days</span>
                        <span class="info-box-number"><?= $aging['1_30'] ?? 0 ?> loans</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-gradient-warning">
                    <span class="info-box-icon"><i class="fas fa-exclamation"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">31-60 Days</span>
                        <span class="info-box-number"><?= $aging['31_60'] ?? 0 ?> loans</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-gradient-orange">
                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">61-90 Days</span>
                        <span class="info-box-number"><?= $aging['61_90'] ?? 0 ?> loans</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-gradient-danger">
                    <span class="info-box-icon"><i class="fas fa-ban"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">90+ Days (NPA)</span>
                        <span class="info-box-number"><?= $aging['90_plus'] ?? 0 ?> loans</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#overdueTable').DataTable({
        "order": [[6, "desc"]],
        "pageLength": 50
    });
    
    // Send reminder
    $('.btn-send-reminder').click(function() {
        var loanId = $(this).data('id');
        var phone = $(this).data('phone');
        
        if (confirm('Send payment reminder to ' + phone + '?')) {
            $.post('<?= site_url('admin/loans/send_reminder') ?>', {loan_id: loanId}, function(response) {
                if (response.success) {
                    toastr.success('Reminder sent successfully');
                } else {
                    toastr.error(response.message || 'Failed to send reminder');
                }
            }, 'json');
        }
    });
});
</script>
