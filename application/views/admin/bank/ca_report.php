<!-- Filters -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Report Filters</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="<?= site_url('admin/bank/ca_report') ?>" id="filterForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Financial Year</label>
                                    <select name="fy_id" class="form-control" id="fySelect">
                                        <?php foreach ($financial_years as $fy): ?>
                                            <option value="<?= $fy->id ?>"
                                                data-start="<?= $fy->start_date ?>"
                                                data-end="<?= $fy->end_date ?>"
                                                <?= ($selected_fy && $selected_fy->id == $fy->id) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($fy->year_label ?? ($fy->start_date . ' to ' . $fy->end_date)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Bank Account</label>
                                    <select name="bank_id" class="form-control">
                                        <option value="">All Accounts</option>
                                        <?php foreach ($bank_accounts as $ba): ?>
                                            <option value="<?= $ba->id ?>" <?= ($filters['bank_id'] == $ba->id) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($ba->bank_name . ' - ' . $ba->account_number) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>From Date</label>
                                    <input type="date" name="from_date" class="form-control" id="fromDate"
                                           value="<?= $filters['from_date'] ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>To Date</label>
                                    <input type="date" name="to_date" class="form-control" id="toDate"
                                           value="<?= $filters['to_date'] ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="mapping_status" class="form-control">
                                        <option value="">All</option>
                                        <option value="mapped" <?= ($filters['mapping_status'] == 'mapped') ? 'selected' : '' ?>>Mapped</option>
                                        <option value="unmapped" <?= ($filters['mapping_status'] == 'unmapped') ? 'selected' : '' ?>>Unmapped</option>
                                        <option value="partial" <?= ($filters['mapping_status'] == 'partial') ? 'selected' : '' ?>>Partial</option>
                                        <option value="ignored" <?= ($filters['mapping_status'] == 'ignored') ? 'selected' : '' ?>>Ignored</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row">
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h4><?= number_format($summary['total_transactions']) ?></h4>
                            <p>Total Transactions</p>
                        </div>
                        <div class="icon"><i class="fas fa-exchange-alt"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h4><?= number_format($summary['mapped_count']) ?></h4>
                            <p>Mapped</p>
                        </div>
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h4><?= number_format($summary['unmapped_count']) ?></h4>
                            <p>Unmapped</p>
                        </div>
                        <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h4><?= number_format($summary['partial_count']) ?></h4>
                            <p>Partial</p>
                        </div>
                        <div class="icon"><i class="fas fa-adjust"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box" style="background: #28a745; color: #fff;">
                        <div class="inner">
                            <h4><?= get_currency_symbol() ?><?= format_amount($summary['total_credit']) ?></h4>
                            <p>Total Credits</p>
                        </div>
                        <div class="icon"><i class="fas fa-arrow-down"></i></div>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box" style="background: #dc3545; color: #fff;">
                        <div class="inner">
                            <h4><?= get_currency_symbol() ?><?= format_amount($summary['total_debit']) ?></h4>
                            <p>Total Debits</p>
                        </div>
                        <div class="icon"><i class="fas fa-arrow-up"></i></div>
                    </div>
                </div>
            </div>

            <!-- Export Button -->
            <div class="row mb-3">
                <div class="col-12 text-right">
                    <a href="<?= site_url('admin/bank/ca_report_export') ?>?<?= http_build_query($filters) ?>"
                       class="btn btn-success btn-lg" target="_blank">
                        <i class="fas fa-file-excel"></i> Export to Excel (For CA)
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary btn-lg">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>

            <!-- Category Summary -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-pie"></i> Category-wise Summary</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-center">Count</th>
                                        <th class="text-right">Debit</th>
                                        <th class="text-right">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($summary['by_category'] as $cat => $data): ?>
                                        <tr>
                                            <td><span class="badge badge-primary"><?= ucfirst(str_replace('_', ' ', $cat)) ?></span></td>
                                            <td class="text-center"><?= $data['count'] ?></td>
                                            <td class="text-right text-danger"><?= format_amount($data['debit']) ?></td>
                                            <td class="text-right text-success"><?= format_amount($data['credit']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tasks"></i> Status-wise Summary</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Status</th>
                                        <th class="text-center">Count</th>
                                        <th class="text-right">Debit</th>
                                        <th class="text-right">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $statusBadges = ['mapped' => 'success', 'unmapped' => 'danger', 'partial' => 'warning', 'ignored' => 'secondary']; ?>
                                    <?php foreach ($summary['by_status'] as $status => $data): ?>
                                        <tr>
                                            <td><span class="badge badge-<?= $statusBadges[$status] ?? 'light' ?>"><?= ucfirst($status) ?></span></td>
                                            <td class="text-center"><?= $data['count'] ?></td>
                                            <td class="text-right text-danger"><?= format_amount($data['debit']) ?></td>
                                            <td class="text-right text-success"><?= format_amount($data['credit']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Passbook Statement Table -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-book"></i> Bank Statement with Mapping Details (Passbook Format)</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-hover" id="caReportTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width:40px">Sr</th>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Reference</th>
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Credit</th>
                                    <th class="text-right">Balance</th>
                                    <th>Status</th>
                                    <th>Mapped To</th>
                                    <th>Narration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $sr = 1; foreach ($transactions as $txn): ?>
                                    <?php
                                        $statusClass = [
                                            'mapped' => 'table-success',
                                            'unmapped' => 'table-danger',
                                            'partial' => 'table-warning',
                                            'ignored' => 'table-secondary'
                                        ];
                                        $rowClass = $statusClass[$txn->mapping_status] ?? '';
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td><?= $sr++ ?></td>
                                        <td class="text-nowrap"><?= date('d-M-Y', strtotime($txn->transaction_date)) ?></td>
                                        <td><?= htmlspecialchars($txn->description) ?></td>
                                        <td class="text-nowrap"><?= htmlspecialchars($txn->reference_number ?? $txn->utr_number ?? $txn->cheque_number ?? '') ?></td>
                                        <td class="text-right text-danger"><?= $txn->debit_amount > 0 ? format_amount($txn->debit_amount) : '' ?></td>
                                        <td class="text-right text-success"><?= $txn->credit_amount > 0 ? format_amount($txn->credit_amount) : '' ?></td>
                                        <td class="text-right"><?= format_amount($txn->running_balance ?? $txn->balance_after ?? 0) ?></td>
                                        <td>
                                            <span class="badge badge-<?= ['mapped'=>'success','unmapped'=>'danger','partial'=>'warning','ignored'=>'secondary'][$txn->mapping_status] ?? 'light' ?>">
                                                <?= ucfirst($txn->mapping_status) ?>
                                            </span>
                                        </td>
                                        <td style="max-width:250px; font-size:12px;"><?= htmlspecialchars($txn->mapped_to_display ?? '') ?></td>
                                        <td style="max-width:200px; font-size:12px;"><?= htmlspecialchars($txn->mapping_narration ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary font-weight-bold">
                                    <td colspan="4" class="text-right">TOTAL</td>
                                    <td class="text-right text-danger"><?= format_amount($summary['total_debit']) ?></td>
                                    <td class="text-right text-success"><?= format_amount($summary['total_credit']) ?></td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
<script>
$(function() {
    // DataTable
    $('#caReportTable').DataTable({
        pageLength: 100,
        lengthMenu: [[50, 100, 250, 500, -1], [50, 100, 250, 500, 'All']],
        order: [[1, 'asc']],
        dom: 'Blfrtip',
        buttons: ['copy', 'csv', 'print'],
        footerCallback: function(row, data, start, end, display) {
            // Keep footer totals visible
        }
    });

    // FY selector auto-fill dates
    $('#fySelect').on('change', function() {
        var opt = $(this).find('option:selected');
        $('#fromDate').val(opt.data('start'));
        $('#toDate').val(opt.data('end'));
    });
});
</script>

<style>
@media print {
    .content-header, .card-primary.card-outline:first-of-type,
    .btn, .dataTables_filter, .dataTables_length, .dataTables_paginate, .dataTables_info,
    .main-sidebar, .main-header, .main-footer { display: none !important; }
    .content-wrapper { margin-left: 0 !important; }
    .table td, .table th { font-size: 10px !important; padding: 2px 4px !important; }
}
</style>
