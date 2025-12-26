<!-- Monthly Summary Report -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Monthly Summary Report</h3>
        <div class="card-tools">
            <form class="form-inline" method="get">
                <input type="month" name="month" class="form-control form-control-sm mr-2" value="<?= date('Y-m', safe_timestamp($month ?? 'now')) ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i></button>
            </form>
            <a href="<?= site_url('admin/reports/monthly_summary?export=pdf&month=' . $month) ?>" class="btn btn-danger btn-sm ml-2">
                <i class="fas fa-file-pdf"></i>
            </a>
        </div>
    </div>
</div>

<!-- Key Metrics -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="small-box bg-success">
            <div class="inner">
                <h4><?= number_format($summary['total_collection'] ?? 0, 0) ?></h4>
                <p>Collection</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill"></i></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-primary">
            <div class="inner">
                <h4><?= number_format($summary['disbursement'] ?? 0, 0) ?></h4>
                <p>Disbursement</p>
            </div>
            <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-info">
            <div class="inner">
                <h4><?= $summary['new_members'] ?? 0 ?></h4>
                <p>New Members</p>
            </div>
            <div class="icon"><i class="fas fa-user-plus"></i></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-warning">
            <div class="inner">
                <h4><?= $summary['new_loans'] ?? 0 ?></h4>
                <p>New Loans</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-danger">
            <div class="inner">
                <h4><?= number_format($summary['overdue'] ?? 0, 0) ?></h4>
                <p>Overdue</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation"></i></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h4><?= number_format(($summary['collection_efficiency'] ?? 0), 1) ?>%</h4>
                <p>Efficiency</p>
            </div>
            <div class="icon"><i class="fas fa-percentage"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Collection Summary -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0"><i class="fas fa-money-bill mr-1"></i> Collection Summary</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Loan EMI Collection</td>
                        <td class="text-right"><?= number_format($summary['loan_collection'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Savings Deposits</td>
                        <td class="text-right"><?= number_format($summary['savings_collection'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Fine Collection</td>
                        <td class="text-right"><?= number_format($summary['fine_collection'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Processing Fees</td>
                        <td class="text-right"><?= number_format($summary['fees'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Other Income</td>
                        <td class="text-right"><?= number_format($summary['other_income'] ?? 0, 2) ?></td>
                    </tr>
                    <tr class="table-success">
                        <th>Total Collection</th>
                        <th class="text-right"><?= number_format($summary['total_collection'] ?? 0, 2) ?></th>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Disbursement Summary -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-hand-holding-usd mr-1"></i> Disbursement Summary</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Loans Disbursed</td>
                        <td class="text-right"><?= $summary['loans_disbursed'] ?? 0 ?></td>
                    </tr>
                    <tr>
                        <td>Total Principal</td>
                        <td class="text-right"><?= number_format($summary['disbursement'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Savings Withdrawals</td>
                        <td class="text-right"><?= number_format($summary['withdrawals'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Interest Paid</td>
                        <td class="text-right"><?= number_format($summary['interest_paid'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Expenses</td>
                        <td class="text-right"><?= number_format($summary['expenses'] ?? 0, 2) ?></td>
                    </tr>
                    <tr class="table-primary">
                        <th>Total Outflow</th>
                        <th class="text-right"><?= number_format(($summary['disbursement'] ?? 0) + ($summary['withdrawals'] ?? 0) + ($summary['expenses'] ?? 0), 2) ?></th>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Portfolio Status -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-chart-pie mr-1"></i> Portfolio Status as on <?= format_date(date('Y-m-d', safe_timestamp('last day of ' . $month))) ?></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Loan Portfolio</h6>
                <table class="table table-sm table-bordered">
                    <tr>
                        <td>Active Loans</td>
                        <td class="text-right"><?= $portfolio['active_loans'] ?? 0 ?></td>
                    </tr>
                    <tr>
                        <td>Outstanding Principal</td>
                        <td class="text-right"><?= number_format($portfolio['outstanding_principal'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Overdue Amount</td>
                        <td class="text-right text-danger"><?= number_format($portfolio['overdue_amount'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>NPA Percentage</td>
                        <td class="text-right"><?= number_format($portfolio['npa_percentage'] ?? 0, 2) ?>%</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Savings Portfolio</h6>
                <table class="table table-sm table-bordered">
                    <tr>
                        <td>Active Accounts</td>
                        <td class="text-right"><?= $portfolio['savings_accounts'] ?? 0 ?></td>
                    </tr>
                    <tr>
                        <td>Total Deposits</td>
                        <td class="text-right"><?= number_format($portfolio['total_deposits'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Interest Accrued</td>
                        <td class="text-right"><?= number_format($portfolio['interest_accrued'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Net Savings</td>
                        <td class="text-right"><?= number_format($portfolio['net_savings'] ?? 0, 2) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Daily Trend -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-chart-line mr-1"></i> Daily Collection Trend</h5>
    </div>
    <div class="card-body">
        <canvas id="dailyTrendChart" height="100"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    new Chart(document.getElementById('dailyTrendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($daily_data['dates'] ?? []) ?>,
            datasets: [{
                label: 'Collection',
                data: <?= json_encode($daily_data['collection'] ?? []) ?>,
                borderColor: '#28a745',
                fill: false
            }, {
                label: 'Disbursement',
                data: <?= json_encode($daily_data['disbursement'] ?? []) ?>,
                borderColor: '#007bff',
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
</script>
