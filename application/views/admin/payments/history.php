<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-1"></i> Payment History</h3>
                <div class="card-tools">
                    <a href="<?= site_url('admin/payments/receive') ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-plus mr-1"></i> New Payment
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="get" class="mb-3">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Payment Type</label>
                                <select class="form-control form-control-sm" name="type">
                                    <option value="">All Types</option>
                                    <option value="loan" <?= ($filters['type'] ?? '') == 'loan' ? 'selected' : '' ?>>Loan EMI</option>
                                    <option value="savings" <?= ($filters['type'] ?? '') == 'savings' ? 'selected' : '' ?>>Savings</option>
                                    <option value="fine" <?= ($filters['type'] ?? '') == 'fine' ? 'selected' : '' ?>>Fine</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Member</label>
                                <select class="form-control form-control-sm" name="member_id">
                                    <option value="">All Members</option>
                                    <?php foreach ($all_members as $m): ?>
                                    <option value="<?= $m->id ?>" <?= ($filters['member_id'] ?? '') == $m->id ? 'selected' : '' ?>>
                                        <?= $m->member_code ?> - <?= $m->first_name ?> <?= $m->last_name ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Payment Mode</label>
                                <select class="form-control form-control-sm" name="payment_mode">
                                    <option value="">All Modes</option>
                                    <option value="cash" <?= ($filters['payment_mode'] ?? '') == 'cash' ? 'selected' : '' ?>>Cash</option>
                                    <option value="upi" <?= ($filters['payment_mode'] ?? '') == 'upi' ? 'selected' : '' ?>>UPI</option>
                                    <option value="bank_transfer" <?= ($filters['payment_mode'] ?? '') == 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                    <option value="cheque" <?= ($filters['payment_mode'] ?? '') == 'cheque' ? 'selected' : '' ?>>Cheque</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>From Date</label>
                                <input type="date" class="form-control form-control-sm" name="date_from" value="<?= $filters['date_from'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>To Date</label>
                                <input type="date" class="form-control form-control-sm" name="date_to" value="<?= $filters['date_to'] ?? date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-sm btn-block">
                                    <i class="fas fa-filter"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- Summary Cards -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>₹<?= number_format($total_amount, 2) ?></h3>
                                <p>Total Collection</p>
                            </div>
                            <div class="icon"><i class="fas fa-rupee-sign"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3><?= count($payments) ?></h3>
                                <p>Total Transactions</p>
                            </div>
                            <div class="icon"><i class="fas fa-receipt"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <?php $avg = count($payments) > 0 ? $total_amount / count($payments) : 0; ?>
                                <h3>₹<?= number_format($avg, 2) ?></h3>
                                <p>Average Payment</p>
                            </div>
                            <div class="icon"><i class="fas fa-calculator"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <?php
                                $type_counts = array_count_values(array_column($payments, 'type'));
                                $most_common = !empty($type_counts) ? array_search(max($type_counts), $type_counts) : 'N/A';
                                ?>
                                <h3><?= ucfirst($most_common) ?></h3>
                                <p>Most Common Type</p>
                            </div>
                            <div class="icon"><i class="fas fa-chart-pie"></i></div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Table -->
                <?php if (empty($payments)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-1"></i> No payments found for the selected filters.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Member</th>
                                <th>Type</th>
                                <th>Reference</th>
                                <th class="text-right">Amount</th>
                                <th>Mode</th>
                                <th>Ref. Number</th>
                                <th class="text-center">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $pmt): ?>
                            <tr>
                                <td><?= format_date($pmt->payment_date, 'd M Y') ?></td>
                                <td>
                                    <a href="<?= site_url('admin/members/view/' . ($pmt->member_id ?? '')) ?>">
                                        <?= $pmt->member_code ?>
                                    </a>
                                    <br><small class="text-muted"><?= $pmt->first_name ?> <?= $pmt->last_name ?></small>
                                </td>
                                <td>
                                    <?php
                                    $type_badges = ['loan' => 'primary', 'savings' => 'success', 'fine' => 'danger'];
                                    $type_icons = ['loan' => 'file-invoice-dollar', 'savings' => 'piggy-bank', 'fine' => 'gavel'];
                                    ?>
                                    <span class="badge badge-<?= $type_badges[$pmt->type] ?? 'secondary' ?>">
                                        <i class="fas fa-<?= $type_icons[$pmt->type] ?? 'money-bill' ?>"></i>
                                        <?= ucfirst($pmt->type) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($pmt->type == 'loan'): ?>
                                        <a href="<?= site_url('admin/loans/view/' . ($pmt->loan_id ?? '')) ?>">
                                            <?= $pmt->loan_number ?? '-' ?>
                                        </a>
                                    <?php elseif ($pmt->type == 'savings'): ?>
                                        <?= $pmt->account_number ?? '-' ?>
                                    <?php elseif ($pmt->type == 'fine'): ?>
                                        <?= $pmt->fine_code ?? '-' ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right"><strong>₹<?= number_format($pmt->amount, 2) ?></strong></td>
                                <td>
                                    <?php
                                    $mode_icons = ['cash' => 'money-bill-wave', 'upi' => 'mobile-alt', 'bank_transfer' => 'university', 'cheque' => 'file-invoice', 'card' => 'credit-card'];
                                    ?>
                                    <small><i class="fas fa-<?= $mode_icons[$pmt->payment_mode] ?? 'rupee-sign' ?>"></i> <?= ucfirst(str_replace('_', ' ', $pmt->payment_mode)) ?></small>
                                </td>
                                <td><small><?= $pmt->reference_number ?: '-' ?></small></td>
                                <td class="text-center">
                                    <a href="<?= site_url('admin/payments/receipt/' . $pmt->id . '?type=' . $pmt->type) ?>" target="_blank" class="btn btn-xs btn-outline-primary">
                                        <i class="fas fa-receipt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-primary">
                            <tr>
                                <th colspan="4" class="text-right">Total:</th>
                                <th class="text-right">₹<?= number_format($total_amount, 2) ?></th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('table').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[0, "desc"]]
    });
});
</script>
