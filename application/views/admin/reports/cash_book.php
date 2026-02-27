<!-- Cash Book Report -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="fas fa-book mr-1"></i> Cash Book Report</h3>
        <div>
            <form class="form-inline" method="get" action="">
                <input type="date" name="from_date" class="form-control form-control-sm mr-2" value="<?= $from_date ?>">
                <input type="date" name="to_date" class="form-control form-control-sm mr-2" value="<?= $to_date ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
            </form>
            <button onclick="window.print()" class="btn btn-secondary btn-sm ml-2">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th class="text-right">Receipt (Debit)</th>
                        <th class="text-right">Payment (Credit)</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $balance = 0;
                    $all_entries = array_merge($report['receipts'] ?? [], $report['payments'] ?? []);
                    usort($all_entries, function($a, $b) {
                        return strtotime($a->date) - strtotime($b->date);
                    });
                    ?>
                    <?php foreach ($all_entries as $entry): ?>
                    <tr>
                        <td><?= format_date($entry->date) ?></td>
                        <td><?= $entry->description ?? 'N/A' ?></td>
                        <td class="text-right text-success">
                            <?= $entry->debit > 0 ? format_amount($entry->debit, 0) : '-' ?>
                        </td>
                        <td class="text-right text-danger">
                            <?= $entry->credit > 0 ? format_amount($entry->credit, 0) : '-' ?>
                        </td>
                        <td class="text-right font-weight-bold">
                            <?php 
                            $balance += ($entry->debit ?? 0) - ($entry->credit ?? 0);
                            echo format_amount($balance, 0);
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-primary">
                    <tr>
                        <th colspan="4" class="text-right">Closing Balance:</th>
                        <th class="text-right"><?= format_amount($balance, 0) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>