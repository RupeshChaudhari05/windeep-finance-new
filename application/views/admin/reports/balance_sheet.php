<!-- Balance Sheet -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i> Balance Sheet</h3>
        <div class="card-tools">
            <form class="form-inline" method="get">
                <label class="mr-2">As on:</label>
                <input type="date" name="as_on" class="form-control form-control-sm mr-2" value="<?= $as_on ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync"></i></button>
            </form>
            <a href="<?= site_url('admin/reports/balance_sheet?export=pdf&as_on=' . $as_on) ?>" class="btn btn-danger btn-sm ml-2">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white text-center">
        <h4 class="mb-0">Balance Sheet as on <?= date('d M Y', strtotime($as_on)) ?></h4>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Liabilities & Equity -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">Liabilities & Equity</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <!-- Equity -->
                            <thead class="thead-light">
                                <tr><th colspan="2">Capital & Reserves</th></tr>
                            </thead>
                            <tbody>
                                <?php $total_equity = 0; ?>
                                <?php foreach ($report['equity'] ?? [] as $item): ?>
                                <?php $total_equity += $item->balance; ?>
                                <tr>
                                    <td class="pl-4"><?= $item->account_name ?></td>
                                    <td class="text-right"><?= number_format($item->balance, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th>Total Equity</th>
                                    <th class="text-right"><?= number_format($total_equity, 2) ?></th>
                                </tr>
                            </tfoot>
                            
                            <!-- Liabilities -->
                            <thead class="thead-light">
                                <tr><th colspan="2">Liabilities</th></tr>
                            </thead>
                            <tbody>
                                <?php $total_liabilities = 0; ?>
                                <?php foreach ($report['liabilities'] ?? [] as $item): ?>
                                <?php $total_liabilities += $item->balance; ?>
                                <tr>
                                    <td class="pl-4"><?= $item->account_name ?></td>
                                    <td class="text-right"><?= number_format($item->balance, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th>Total Liabilities</th>
                                    <th class="text-right"><?= number_format($total_liabilities, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="card-footer bg-dark text-white">
                        <div class="d-flex justify-content-between">
                            <strong>Total Liabilities & Equity</strong>
                            <strong><?= number_format($total_equity + $total_liabilities, 2) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Assets -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Assets</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <!-- Fixed Assets -->
                            <thead class="thead-light">
                                <tr><th colspan="2">Fixed Assets</th></tr>
                            </thead>
                            <tbody>
                                <?php $total_fixed = 0; ?>
                                <?php foreach ($report['fixed_assets'] ?? [] as $item): ?>
                                <?php $total_fixed += $item->balance; ?>
                                <tr>
                                    <td class="pl-4"><?= $item->account_name ?></td>
                                    <td class="text-right"><?= number_format($item->balance, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th>Total Fixed Assets</th>
                                    <th class="text-right"><?= number_format($total_fixed, 2) ?></th>
                                </tr>
                            </tfoot>
                            
                            <!-- Current Assets -->
                            <thead class="thead-light">
                                <tr><th colspan="2">Current Assets</th></tr>
                            </thead>
                            <tbody>
                                <?php $total_current = 0; ?>
                                <?php foreach ($report['current_assets'] ?? [] as $item): ?>
                                <?php $total_current += $item->balance; ?>
                                <tr>
                                    <td class="pl-4"><?= $item->account_name ?></td>
                                    <td class="text-right"><?= number_format($item->balance, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th>Total Current Assets</th>
                                    <th class="text-right"><?= number_format($total_current, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="card-footer bg-dark text-white">
                        <div class="d-flex justify-content-between">
                            <strong>Total Assets</strong>
                            <strong><?= number_format($total_fixed + $total_current, 2) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Balance Check -->
        <?php 
        $total_assets = $total_fixed + $total_current;
        $total_le = $total_equity + $total_liabilities;
        $diff = abs($total_assets - $total_le);
        ?>
        <div class="alert alert-<?= $diff < 0.01 ? 'success' : 'danger' ?> text-center mt-4">
            <?php if ($diff < 0.01): ?>
            <i class="fas fa-check-circle fa-2x"></i>
            <h5 class="mt-2 mb-0">Balance Sheet is Balanced</h5>
            <?php else: ?>
            <i class="fas fa-exclamation-triangle fa-2x"></i>
            <h5 class="mt-2 mb-0">Difference: ₹ <?= number_format($diff, 2) ?></h5>
            <?php endif; ?>
        </div>
    </div>
</div>
