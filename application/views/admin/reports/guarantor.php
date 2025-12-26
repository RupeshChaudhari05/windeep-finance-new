<!-- Guarantor Exposure Report -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-shield mr-1"></i> Guarantor Exposure Report</h3>
        <div class="card-tools">
            <a href="<?= site_url('admin/reports/guarantor?export=excel') ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Export
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="guarantorTable">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Guarantor Name</th>
                        <th>Member ID</th>
                        <th>Phone</th>
                        <th>Loans Guaranteed</th>
                        <th>Total Exposure</th>
                        <th>Outstanding</th>
                        <th>Overdue Loans</th>
                        <th>Risk Level</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($report as $row): ?>
                    <?php 
                    $risk = 'Low';
                    $risk_class = 'success';
                    if ($row->overdue_count > 0) { $risk = 'High'; $risk_class = 'danger'; }
                    elseif ($row->total_exposure > 500000) { $risk = 'Medium'; $risk_class = 'warning'; }
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <strong><?= $row->guarantor_name ?></strong>
                            <?php if ($row->is_member): ?>
                            <span class="badge badge-info ml-1">Member</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $row->member_number ?? '-' ?></td>
                        <td><?= $row->phone ?></td>
                        <td>
                            <span class="badge badge-primary"><?= $row->loan_count ?></span>
                        </td>
                        <td><?= number_format($row->total_exposure, 2) ?></td>
                        <td>
                            <strong class="text-<?= $row->outstanding > 0 ? 'danger' : 'success' ?>">
                                <?= number_format($row->outstanding, 2) ?>
                            </strong>
                        </td>
                        <td>
                            <?php if ($row->overdue_count > 0): ?>
                            <span class="badge badge-danger"><?= $row->overdue_count ?> overdue</span>
                            <?php else: ?>
                            <span class="badge badge-success">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $risk_class ?>"><?= $risk ?></span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info btn-view-loans" data-id="<?= $row->guarantor_id ?>" 
                                    data-name="<?= $row->guarantor_name ?>">
                                <i class="fas fa-eye"></i> Loans
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Summary -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Guarantors</span>
                <span class="info-box-number"><?= count($report) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Exposure</span>
                <span class="info-box-number"><?= number_format(array_sum(array_column($report, 'total_exposure')), 2) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-danger">
            <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">High Risk Guarantors</span>
                <span class="info-box-number"><?= count(array_filter($report, function($r) { return $r->overdue_count > 0; })) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Loans Modal -->
<div class="modal fade" id="loansModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-list mr-1"></i> Loans Guaranteed by <span id="guarantorName"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="loansContent">
                <div class="text-center p-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#guarantorTable').DataTable({
        "order": [[5, "desc"]],
        "pageLength": 50
    });
    
    // View loans
    $('.btn-view-loans').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        $('#guarantorName').text(name);
        $('#loansContent').html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
        $('#loansModal').modal('show');
        
        $.get('<?= site_url('admin/reports/guarantor_loans') ?>/' + id, function(html) {
            $('#loansContent').html(html);
        });
    });
});
</script>
