<!-- Ageing Analysis Report -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="fas fa-clock mr-1"></i> Loan Ageing Analysis</h3>
        <div>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="ageingTable">
                <thead class="thead-dark">
                    <tr>
                        <th>Loan No</th>
                        <th>Member</th>
                        <th>Product</th>
                        <th>Principal</th>
                        <th>Outstanding</th>
                        <th>Days Overdue</th>
                        <th>Overdue Amount</th>
                        <th>Ageing Bucket</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report ?? [] as $loan): ?>
                    <tr>
                        <td><?= $loan->loan_number ?></td>
                        <td><?= $loan->member_code ?> - <?= $loan->first_name . ' ' . $loan->last_name ?></td>
                        <td><?= $loan->product_name ?></td>
                        <td class="text-right"><?= format_amount($loan->principal_amount, 0) ?></td>
                        <td class="text-right"><?= format_amount($loan->outstanding_principal, 0) ?></td>
                        <td class="text-center">
                            <span class="badge badge-danger"><?= $loan->days_overdue ?? 0 ?></span>
                        </td>
                        <td class="text-right text-danger font-weight-bold"><?= format_amount($loan->overdue_amount ?? 0, 0) ?></td>
                        <td>
                            <span class="badge badge-<?= 
                                ($loan->ageing_bucket ?? '') == '0-30 days' ? 'warning' : 
                                (($loan->ageing_bucket ?? '') == '31-60 days' ? 'danger' : 'dark') 
                            ?>">
                                <?= $loan->ageing_bucket ?? 'N/A' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#ageingTable').DataTable({
        "pageLength": 25,
        "order": [[5, "desc"]]
    });
});
</script>