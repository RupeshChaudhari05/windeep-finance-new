<!-- View Non-Member Fund Provider -->

<div class="row">
    <!-- Provider Details -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-tie mr-1"></i> Provider Details</h3>
                <div class="card-tools">
                    <a href="<?= site_url('admin/non_members/edit/' . $non_member->id) ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="35%"><i class="fas fa-user text-muted mr-1"></i> Name</th>
                        <td class="font-weight-bold"><?= htmlspecialchars($non_member->name) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-phone text-muted mr-1"></i> Phone</th>
                        <td><?= $non_member->phone ? '<a href="tel:' . $non_member->phone . '">' . htmlspecialchars($non_member->phone) . '</a>' : '-' ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-envelope text-muted mr-1"></i> Email</th>
                        <td><?= $non_member->email ? htmlspecialchars($non_member->email) : '-' ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-map-marker-alt text-muted mr-1"></i> Address</th>
                        <td><?= $non_member->address ? nl2br(htmlspecialchars($non_member->address)) : '-' ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-info-circle text-muted mr-1"></i> Status</th>
                        <td>
                            <span class="badge badge-<?= $non_member->status === 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($non_member->status) ?>
                            </span>
                        </td>
                    </tr>
                    <?php if ($non_member->notes): ?>
                    <tr>
                        <th><i class="fas fa-sticky-note text-muted mr-1"></i> Notes</th>
                        <td><?= nl2br(htmlspecialchars($non_member->notes)) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><i class="fas fa-calendar text-muted mr-1"></i> Added</th>
                        <td><?= format_date($non_member->created_at) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Fund Summary -->
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Fund Summary</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <th><i class="fas fa-arrow-down text-success mr-1"></i> Total Received</th>
                        <td class="text-right text-success font-weight-bold"><?= format_amount($non_member->total_received) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-arrow-up text-warning mr-1"></i> Total Returned</th>
                        <td class="text-right text-warning font-weight-bold"><?= format_amount($non_member->total_returned) ?></td>
                    </tr>
                    <tr class="bg-light">
                        <th><i class="fas fa-balance-scale text-primary mr-1"></i> Outstanding</th>
                        <td class="text-right font-weight-bold text-primary" style="font-size: 1.2em;">
                            <?= format_amount($non_member->balance) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Fund Transactions -->
    <div class="col-md-8">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exchange-alt mr-1"></i> Fund Transactions</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addFundModal">
                        <i class="fas fa-plus mr-1"></i> Add Transaction
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="fundsTable">
                        <thead class="thead-light">
                            <tr>
                                <th width="40">#</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th class="text-right">Amount</th>
                                <th>Mode</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th width="60">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($funds)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No fund transactions yet</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php $running = 0; ?>
                                <?php foreach (array_reverse($funds) as $idx => $f): ?>
                                <?php
                                    if ($f->transaction_type === 'received') $running += $f->amount;
                                    else $running -= $f->amount;
                                ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><?= format_date($f->transaction_date) ?></td>
                                    <td>
                                        <?php if ($f->transaction_type === 'received'): ?>
                                            <span class="badge badge-success"><i class="fas fa-arrow-down mr-1"></i> Received</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><i class="fas fa-arrow-up mr-1"></i> Returned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right font-weight-bold <?= $f->transaction_type === 'received' ? 'text-success' : 'text-warning' ?>">
                                        <?= format_amount($f->amount) ?>
                                    </td>
                                    <td><?= ucfirst($f->payment_mode ?? 'cash') ?></td>
                                    <td><?= $f->reference_number ? htmlspecialchars($f->reference_number) : '-' ?></td>
                                    <td><?= $f->description ? htmlspecialchars($f->description) : '-' ?></td>
                                    <td>
                                        <button class="btn btn-xs btn-danger" onclick="deleteFund(<?= $f->id ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Fund Transaction Modal -->
<div class="modal fade" id="addFundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle mr-1"></i> Add Fund Transaction</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="addFundForm">
                <input type="hidden" name="non_member_id" value="<?= $non_member->id ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Transaction Type <span class="text-danger">*</span></label>
                        <select name="transaction_type" class="form-control" required>
                            <option value="received">Received (Money In)</option>
                            <option value="returned">Returned (Money Out)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Transaction Date <span class="text-danger">*</span></label>
                        <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Mode</label>
                        <select name="payment_mode" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="upi">UPI</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference Number</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="Cheque/Transfer reference">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveFundBtn">
                        <i class="fas fa-save mr-1"></i> Save Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function() {
    // Add fund transaction
    $('#addFundForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#saveFundBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');

        $.ajax({
            url: '<?= site_url('admin/non_members/add_fund') ?>',
            type: 'POST',
            data: $(this).serialize() + '&<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    toastr.error(res.message || 'Failed to save transaction');
                    btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save Transaction');
                }
            },
            error: function(xhr) {
                var res = xhr.responseJSON || {};
                toastr.error(res.message || 'An error occurred');
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save Transaction');
            }
        });
    });
});

function deleteFund(fundId) {
    if (!confirm('Are you sure you want to delete this fund transaction?')) return;

    $.ajax({
        url: '<?= site_url('admin/non_members/delete_fund') ?>',
        type: 'POST',
        data: {
            fund_id: fundId,
            '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                toastr.success(res.message);
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                toastr.error(res.message || 'Failed to delete');
            }
        },
        error: function(xhr) {
            var res = xhr.responseJSON || {};
            toastr.error(res.message || 'An error occurred');
        }
    });
}
</script>
