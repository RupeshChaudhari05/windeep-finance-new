<div class="row">
    <div class="col-12">
        <!-- Filter Card -->
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Member Ledger Filter</h3>
            </div>
            <div class="card-body">
                <form method="get" action="<?= site_url('admin/ledger/member') ?>" id="filterForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Member <span class="text-danger">*</span></label>
                                <select name="member_id" id="member_id" class="form-control select2" required>
                                    <option value="">-- Select Member --</option>
                                    <?php foreach ($members as $m): ?>
                                        <option value="<?= $m->id ?>" <?= $member_id == $m->id ? 'selected' : '' ?>>
                                            <?= $m->member_code ?> - <?= $m->first_name ?> <?= $m->last_name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>From Date</label>
                                <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>To Date</label>
                                <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> View Ledger
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($member && !empty($ledger)): ?>
        <!-- Member Info -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user mr-2"></i><?= $member->first_name ?> <?= $member->last_name ?>
                    <small class="ml-2">(<?= $member->member_code ?>)</small>
                </h3>
                <div class="card-tools">
                    <a href="<?= site_url('admin/ledger/export?' . http_build_query(['member_id' => $member_id, 'from_date' => $from_date, 'to_date' => $to_date])) ?>" 
                       class="btn btn-tool btn-success">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a href="<?= site_url('admin/ledger/print_ledger?' . http_build_query(['member_id' => $member_id, 'from_date' => $from_date, 'to_date' => $to_date])) ?>" 
                       class="btn btn-tool btn-info" target="_blank">
                        <i class="fas fa-print"></i> Print
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Summary -->
                <div class="row p-3 bg-light">
                    <div class="col-md-3">
                        <div class="info-box mb-0">
                            <span class="info-box-icon bg-info"><i class="fas fa-balance-scale"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Opening Balance</span>
                                <span class="info-box-number"><?= format_amount($summary['opening_balance']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box mb-0">
                            <span class="info-box-icon bg-danger"><i class="fas fa-arrow-down"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Debit</span>
                                <span class="info-box-number text-danger"><?= format_amount($summary['total_debit']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box mb-0">
                            <span class="info-box-icon bg-success"><i class="fas fa-arrow-up"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Credit</span>
                                <span class="info-box-number text-success"><?= format_amount($summary['total_credit']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box mb-0">
                            <span class="info-box-icon bg-primary"><i class="fas fa-wallet"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Closing Balance</span>
                                <span class="info-box-number"><?= format_amount($summary['closing_balance']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ledger Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" id="ledgerTable">
                        <thead class="thead-dark">
                            <tr>
                                <th style="width: 100px;">Date</th>
                                <th style="width: 150px;">Transaction Type</th>
                                <th style="width: 120px;">Reference</th>
                                <th>Narration</th>
                                <th class="text-right" style="width: 120px;">Debit (Dr)</th>
                                <th class="text-right" style="width: 120px;">Credit (Cr)</th>
                                <th class="text-right" style="width: 120px;">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ledger as $entry): ?>
                            <tr>
                                <td><?= format_date($entry->transaction_date) ?></td>
                                <td>
                                    <?php
                                    $type_badges = [
                                        'savings_deposit' => 'success',
                                        'savings_withdrawal' => 'warning',
                                        'loan_disbursement' => 'info',
                                        'loan_payment' => 'primary',
                                        'fine_applied' => 'danger',
                                        'fine_payment' => 'success',
                                        'share_purchase' => 'secondary'
                                    ];
                                    $badge_class = $type_badges[$entry->transaction_type] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-<?= $badge_class ?>">
                                        <?= ucfirst(str_replace('_', ' ', $entry->transaction_type)) ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php if ($entry->reference_type && $entry->reference_id): ?>
                                            <?= ucfirst($entry->reference_type) ?> #<?= $entry->reference_id ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <small><?= $entry->narration ?: '-' ?></small>
                                </td>
                                <td class="text-right text-danger">
                                    <?php if ($entry->debit_amount > 0): ?>
                                        <?= format_amount($entry->debit_amount) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-right text-success">
                                    <?php if ($entry->credit_amount > 0): ?>
                                        <?= format_amount($entry->credit_amount) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-weight-bold">
                                    <span class="text-<?= $entry->balance_after >= 0 ? 'success' : 'danger' ?>">
                                        <?= format_amount(abs($entry->balance_after)) ?>
                                        <?= $entry->balance_after >= 0 ? 'Cr' : 'Dr' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-primary">
                            <tr>
                                <th colspan="4" class="text-right">Total:</th>
                                <th class="text-right text-danger"><?= format_amount($summary['total_debit']) ?></th>
                                <th class="text-right text-success"><?= format_amount($summary['total_credit']) ?></th>
                                <th class="text-right"><?= format_amount($summary['closing_balance']) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <?php elseif ($member): ?>
        <!-- No transactions -->
        <div class="card">
            <div class="card-body">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-book fa-4x mb-3"></i>
                    <h5>No Transactions Found</h5>
                    <p>No ledger entries found for the selected member and date range.</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- No member selected -->
        <div class="card">
            <div class="card-body">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-users fa-4x mb-3"></i>
                    <h5>Select a Member</h5>
                    <p>Please select a member from the dropdown above to view their ledger.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: '-- Select Member --',
        allowClear: true
    });
    
    // Initialize DataTable if ledger exists
    <?php if ($member && !empty($ledger)): ?>
    $('#ledgerTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 50,
        "lengthMenu": [[25, 50, 100, -1], [25, 50, 100, "All"]],
        "dom": 'Bfrtip',
        "buttons": ['copy', 'csv', 'excel', 'pdf', 'print']
    });
    <?php endif; ?>
});
</script>
