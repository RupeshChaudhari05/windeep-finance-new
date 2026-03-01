<!-- Fines & Penalties List -->
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-8">
                <form action="" method="get" class="form-inline">
                    <div class="input-group input-group-sm mr-2">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= $filters['search'] ?? '' ?>">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <select name="type" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="loan_late_payment" <?= ($filters['type'] ?? '') == 'loan_late_payment' ? 'selected' : '' ?>>Loan Late Payment</option>
                        <option value="savings_late_payment" <?= ($filters['type'] ?? '') == 'savings_late_payment' ? 'selected' : '' ?>>SD Late Payment</option>
                        <option value="manual" <?= ($filters['type'] ?? '') == 'manual' ? 'selected' : '' ?>>Manual Fine</option>
                    </select>
                    <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" <?= ($filters['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="partial" <?= ($filters['status'] ?? '') == 'partial' ? 'selected' : '' ?>>Partial</option>
                        <option value="paid" <?= ($filters['status'] ?? '') == 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="waived" <?= ($filters['status'] ?? '') == 'waived' ? 'selected' : '' ?>>Waived</option>
                    </select>
                </form>
            </div>
            <div class="col-md-4 text-right">
                <a href="<?= site_url('admin/fines/create') ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Manual Fine
                </a>
                <a href="<?= site_url('admin/fines/waiver-requests') ?>" class="btn btn-outline-warning btn-sm">
                    <i class="fas fa-user-clock"></i> Waiver Requests
                </a>
                <a href="<?= site_url('admin/fines/rules') ?>" class="btn btn-info btn-sm">
                    <i class="fas fa-cog"></i> Fine Rules
                </a>
                <a href="<?= site_url('admin/fines/recalculate_all') ?>" class="btn btn-warning btn-sm" onclick="return confirm('This will recalculate all pending fines using the correct formula. Proceed?')">
                    <i class="fas fa-sync-alt"></i> Recalculate All
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Summary Stats -->
        <div class="row p-3 bg-light border-bottom">
            <div class="col-md-3">
                <div class="small-box bg-warning mb-0">
                    <div class="inner py-2 px-3">
                        <h4><?= format_amount($summary['pending_amount'] ?? 0, 0) ?></h4>
                        <p class="mb-0">Pending Fines</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-success mb-0">
                    <div class="inner py-2 px-3">
                        <h4><?= format_amount($summary['collected_amount'] ?? 0, 0) ?></h4>
                        <p class="mb-0">Collected</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-secondary mb-0">
                    <div class="inner py-2 px-3">
                        <h4><?= format_amount($summary['waived_amount'] ?? 0, 0) ?></h4>
                        <p class="mb-0">Waived</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-danger mb-0">
                    <div class="inner py-2 px-3">
                        <h4><?= number_format($summary['pending_count'] ?? 0) ?></h4>
                        <p class="mb-0">Pending Count</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Fine Code</th>
                        <th>Member</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Date</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Balance</th>
                        <th>Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fines)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-4">
                            <i class="fas fa-gavel fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No fines found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($fines as $key => $fine): ?>
                        <tr>
                            <td><?= $key + 1 ?></td>
                            <td>
                                <a href="<?= site_url('admin/fines/view/' . $fine->id) ?>" class="font-weight-bold">
                                    <?= $fine->fine_code ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= site_url('admin/members/view/' . $fine->member_id) ?>">
                                    <?= $fine->member_name ?>
                                </a>
                                <br><small class="text-muted"><?= $fine->member_code ?></small>
                            </td>
                            <td>
                                <?php
                                $type_badges = [
                                    'loan_late_payment' => 'danger',
                                    'savings_late_payment' => 'warning',
                                    'manual' => 'info',
                                    'meeting_absence' => 'secondary'
                                ];
                                ?>
                                <span class="badge badge-<?= $type_badges[$fine->fine_type] ?? 'secondary' ?>">
                                    <?= ucfirst(str_replace('_', ' ', $fine->fine_type)) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (isset($fine->loan_id) && $fine->loan_id): ?>
                                    <a href="<?= site_url('admin/loans/view/' . $fine->loan_id) ?>"><?= isset($fine->loan_number) ? $fine->loan_number : $fine->loan_id ?></a>
                                <?php elseif (isset($fine->savings_account_id) && $fine->savings_account_id): ?>
                                    <a href="<?= site_url('admin/savings/view/' . $fine->savings_account_id) ?>"><?= isset($fine->account_number) ? $fine->account_number : $fine->savings_account_id ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= format_date($fine->fine_date) ?></td>
                            <td class="text-right"><?= format_amount($fine->fine_amount, 0) ?></td>
                            <td class="text-right text-success"><?= format_amount($fine->paid_amount, 0) ?></td>
                            <td class="text-right font-weight-bold text-danger"><?= format_amount($fine->balance_amount, 0) ?></td>
                            <td>
                                <?php
                                $status_class = [
                                    'pending' => 'warning',
                                    'partial' => 'info',
                                    'paid' => 'success',
                                    'waived' => 'secondary',
                                    'cancelled' => 'dark'
                                ];
                                ?>
                                <span class="badge badge-<?= $status_class[$fine->status] ?? 'secondary' ?>">
                                    <?= ucfirst($fine->status) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-xs btn-outline-primary btn-fine-detail" data-fine-id="<?= $fine->id ?>" title="Calculation Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="<?= site_url('admin/fines/view/' . $fine->id) ?>" class="btn btn-xs btn-info" title="Full View">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <?php if ($fine->status == 'pending' || $fine->status == 'partial'): ?>
                                    <a href="<?= site_url('admin/fines/collect/' . $fine->id) ?>" class="btn btn-xs btn-success" title="Collect">
                                        <i class="fas fa-rupee-sign"></i>
                                    </a>
                                    <a href="<?= site_url('admin/fines/waive/' . $fine->id) ?>" class="btn btn-xs btn-secondary" title="Waive" onclick="return confirm('Waive this fine?')">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (isset($pagination) && $pagination['total'] > $pagination['per_page']): ?>
    <div class="card-footer clearfix">
        <div class="float-left">
            Showing <?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?> to 
            <?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) ?> of 
            <?= $pagination['total'] ?> entries
        </div>
        <ul class="pagination pagination-sm m-0 float-right">
            <?php $total_pages = ceil($pagination['total'] / $pagination['per_page']); ?>
            <?php if ($pagination['current_page'] > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>">&lsaquo;</a></li>
            <?php endif; ?>
            <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($total_pages, $pagination['current_page'] + 2); $i++): ?>
            <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <?php if ($pagination['current_page'] < $total_pages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>">&rsaquo;</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<!-- Fine Calculation Detail Modal -->
<div class="modal fade" id="fineDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-calculator mr-2"></i>Fine Calculation Breakdown</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="fineDetailBody">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Fine Detail Modal
    $(document).on('click', '.btn-fine-detail', function() {
        var fineId = $(this).data('fine-id');
        var $body = $('#fineDetailBody');
        
        $body.html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading calculation details...</p></div>');
        $('#fineDetailModal').modal('show');
        
        $.getJSON('<?= site_url('admin/fines/get_fine_detail') ?>/' + fineId, function(resp) {
            if (!resp.success) {
                $body.html('<div class="text-center text-danger py-3"><i class="fas fa-exclamation-circle fa-2x"></i><p>'+resp.message+'</p></div>');
                return;
            }
            
            var f = resp.fine;
            var steps = resp.steps;
            
            var html = '';
            
            // Fine summary card
            html += '<div class="row mb-3">';
            html += '<div class="col-md-6">';
            html += '<div class="card card-outline card-primary mb-0">';
            html += '<div class="card-body py-2">';
            html += '<table class="table table-sm table-borderless mb-0">';
            html += '<tr><td class="text-muted">Fine Code:</td><td><strong>' + f.fine_code + '</strong></td></tr>';
            html += '<tr><td class="text-muted">Member:</td><td>' + f.member_name + ' <small class="text-muted">(' + f.member_code + ')</small></td></tr>';
            html += '<tr><td class="text-muted">Fine Type:</td><td>' + f.fine_type + '</td></tr>';
            html += '<tr><td class="text-muted">Rule Applied:</td><td><span class="badge badge-info">' + f.rule_name + '</span></td></tr>';
            html += '</table>';
            html += '</div></div></div>';
            
            html += '<div class="col-md-6">';
            html += '<div class="card card-outline card-' + (f.status === 'paid' ? 'success' : (f.status === 'pending' ? 'warning' : 'info')) + ' mb-0">';
            html += '<div class="card-body py-2">';
            html += '<table class="table table-sm table-borderless mb-0">';
            html += '<tr><td class="text-muted">Fine Amount:</td><td class="font-weight-bold">&#8377;' + f.fine_amount.toLocaleString('en-IN', {minimumFractionDigits:2}) + '</td></tr>';
            html += '<tr><td class="text-muted">Paid:</td><td class="text-success">&#8377;' + f.paid_amount.toLocaleString('en-IN', {minimumFractionDigits:2}) + '</td></tr>';
            html += '<tr><td class="text-muted">Balance:</td><td class="text-danger font-weight-bold">&#8377;' + f.balance_amount.toLocaleString('en-IN', {minimumFractionDigits:2}) + '</td></tr>';
            html += '<tr><td class="text-muted">Status:</td><td><span class="badge badge-' + ({pending:"warning",partial:"info",paid:"success",waived:"secondary",cancelled:"dark"}[f.status] || "secondary") + '">' + f.status.charAt(0).toUpperCase() + f.status.slice(1) + '</span></td></tr>';
            html += '</table>';
            html += '</div></div></div>';
            html += '</div>';
            
            // Calculation Steps
            html += '<div class="card card-outline card-secondary">';
            html += '<div class="card-header py-2"><h6 class="card-title mb-0"><i class="fas fa-list-ol mr-1"></i> Step-by-Step Calculation</h6></div>';
            html += '<div class="card-body p-0">';
            html += '<table class="table table-sm table-striped mb-0">';
            html += '<thead><tr><th width="40">#</th><th>Parameter</th><th class="text-right">Value</th></tr></thead><tbody>';
            
            for (var i = 0; i < steps.length; i++) {
                var s = steps[i];
                var cls = s.highlight ? ' class="bg-light font-weight-bold"' : '';
                html += '<tr' + cls + '>';
                html += '<td>' + (i + 1) + '</td>';
                html += '<td>' + s.label + '</td>';
                html += '<td class="text-right">' + s.value + '</td>';
                html += '</tr>';
            }
            html += '</tbody></table></div></div>';
            
            // Correctness badge
            if (!f.is_correct) {
                html += '<div class="card card-danger">';
                html += '<div class="card-body py-2">';
                html += '<i class="fas fa-exclamation-triangle mr-1"></i> ';
                html += '<strong>Mismatch detected!</strong> Current amount &#8377;' + f.fine_amount.toLocaleString('en-IN',{minimumFractionDigits:2}) + ' should be &#8377;' + f.correct_amount.toLocaleString('en-IN',{minimumFractionDigits:2}) + '. ';
                html += 'Use <strong>Recalculate All</strong> button to fix all fines.';
                html += '</div></div>';
            } else {
                html += '<div class="card card-success">';
                html += '<div class="card-body py-2">';
                html += '<i class="fas fa-check-circle mr-1"></i> <strong>Calculation is correct.</strong> Fine amount matches the rule.';
                html += '</div></div>';
            }
            
            if (f.remarks) {
                html += '<div class="card"><div class="card-body py-2"><small class="text-muted"><i class="fas fa-sticky-note mr-1"></i> ' + f.remarks + '</small></div></div>';
            }
            
            $body.html(html);
        }).fail(function() {
            $body.html('<div class="text-center text-danger py-3"><i class="fas fa-exclamation-circle fa-2x"></i><p>Failed to load fine details.</p></div>');
        });
    });
});
</script>
