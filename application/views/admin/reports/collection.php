<!-- Collection Report -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i> Collection Report</h3>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form action="" method="get" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date Range</label>
                        <input type="text" class="form-control" id="daterange" name="daterange" 
                               value="<?= $filters['start_date'] ?? date('Y-m-01') ?> - <?= $filters['end_date'] ?? date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Collection Type</label>
                        <select class="form-control" name="type">
                            <option value="">All Types</option>
                            <option value="savings" <?= ($filters['type'] ?? '') == 'savings' ? 'selected' : '' ?>>Savings</option>
                            <option value="loan" <?= ($filters['type'] ?? '') == 'loan' ? 'selected' : '' ?>>Loan EMI</option>
                            <option value="fine" <?= ($filters['type'] ?? '') == 'fine' ? 'selected' : '' ?>>Fine</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Payment Mode</label>
                        <select class="form-control" name="mode">
                            <option value="">All Modes</option>
                            <option value="cash" <?= ($filters['mode'] ?? '') == 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="upi" <?= ($filters['mode'] ?? '') == 'upi' ? 'selected' : '' ?>>UPI</option>
                            <option value="bank_transfer" <?= ($filters['mode'] ?? '') == 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                            <a href="<?= current_url() ?>?export=excel&<?= http_build_query($filters ?? []) ?>" class="btn btn-success">
                                <i class="fas fa-download"></i> Export
                            </a>
                            <a href="<?= current_url() ?>?export=pdf&<?= http_build_query($filters ?? []) ?>" class="btn btn-danger">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-rupee-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Collection</span>
                        <span class="info-box-number">₹<?= number_format($summary['total'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-piggy-bank"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Savings</span>
                        <span class="info-box-number">₹<?= number_format($summary['savings'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Loan EMI</span>
                        <span class="info-box-number">₹<?= number_format($summary['loan'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-gavel"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Fine</span>
                        <span class="info-box-number">₹<?= number_format($summary['fine'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chart -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daily Collection Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="collectionChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">By Payment Mode</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="modeChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Data Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="collectionTable">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Receipt No</th>
                        <th>Member</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th class="text-right">Amount</th>
                        <th>Mode</th>
                        <th>Collected By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($collections)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">No collections found for the selected period</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($collections as $key => $col): ?>
                        <tr>
                            <td><?= $key + 1 ?></td>
                            <td><?= date('d M Y', strtotime($col->transaction_date)) ?></td>
                            <td><small><?= $col->receipt_number ?></small></td>
                            <td>
                                <a href="<?= site_url('admin/members/view/' . $col->member_id) ?>">
                                    <?= $col->member_name ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                $type_badges = ['savings' => 'success', 'loan' => 'primary', 'fine' => 'warning'];
                                ?>
                                <span class="badge badge-<?= $type_badges[$col->collection_type] ?? 'secondary' ?>">
                                    <?= ucfirst($col->collection_type) ?>
                                </span>
                            </td>
                            <td><small><?= $col->reference_number ?? '-' ?></small></td>
                            <td class="text-right font-weight-bold">₹<?= number_format($col->amount) ?></td>
                            <td><?= ucfirst($col->payment_mode) ?></td>
                            <td><small><?= $col->collected_by ?? '-' ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-primary">
                    <tr>
                        <th colspan="6" class="text-right">Total:</th>
                        <th class="text-right">₹<?= number_format($summary['total'] ?? 0) ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Date range picker
    $('#daterange').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        opens: 'right'
    });
    
    // Collection Chart
    var ctx = document.getElementById('collectionChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels ?? []) ?>,
            datasets: [{
                label: 'Collection',
                data: <?= json_encode($chart_data ?? []) ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function(v) { return '₹' + v.toLocaleString(); } } }
            }
        }
    });
    
    // Mode Chart
    var modeCtx = document.getElementById('modeChart').getContext('2d');
    new Chart(modeCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($mode_labels ?? ['Cash', 'UPI', 'Bank', 'Other']) ?>,
            datasets: [{
                data: <?= json_encode($mode_data ?? [0, 0, 0, 0]) ?>,
                backgroundColor: ['#28a745', '#17a2b8', '#007bff', '#6c757d']
            }]
        }
    });
    
    // DataTable
    $('#collectionTable').DataTable({
        pageLength: 25,
        order: [[1, 'desc']],
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'print']
    });
});
</script>
