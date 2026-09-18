<!-- Check Details Display - Add to view_application.php -->

<!-- Check Details Section -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i> Check Details</h3>
        <div class="card-tools">
            <?php if (in_array($application->status, ['draft', 'pending', 'under_review'])): ?>
            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addCheckModal">
                <i class="fas fa-plus mr-1"></i> Add Check
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($checks)): ?>
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Member</th>
                        <th>Check Number</th>
                        <th>Bank Details</th>
                        <th class="text-right">Amount</th>
                        <th>Check Date</th>
                        <th>Status</th>
                        <th width="100">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checks as $check): ?>
                    <tr>
                        <td>
                            <?php if ($check->check_type === 'applicant'): ?>
                                <span class="badge badge-info">Applicant</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Guarantor</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong>
                                <?php if ($check->check_type === 'applicant'): ?>
                                    <?= $check->member_code ?> - <?= $check->first_name ?> <?= $check->last_name ?>
                                <?php else: ?>
                                    <?= $check->guar_member_code ?> - <?= $check->guar_first_name ?> <?= $check->guar_last_name ?>
                                <?php endif; ?>
                            </strong>
                        </td>
                        <td>
                            <code><?= $check->check_number ?></code>
                        </td>
                        <td>
                            <div class="small">
                                <strong><?= $check->bank_name ?></strong><br>
                                <code>IFSC: <?= $check->ifsc_code ?></code>
                                <?php if (!empty($check->account_number)): ?>
                                    <br><code>A/C: ...<?= substr($check->account_number, -4) ?></code>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-right text-primary font-weight-bold">
                            <?= format_amount($check->amount, 0) ?>
                        </td>
                        <td>
                            <?= date('d M Y', strtotime($check->check_date)) ?>
                        </td>
                        <td>
                            <?php 
                                $status_colors = [
                                    'pending' => 'warning',
                                    'verified' => 'success',
                                    'encashed' => 'info',
                                    'bounced' => 'danger',
                                    'cancelled' => 'secondary'
                                ];
                                $color = $status_colors[$check->status] ?? 'secondary';
                            ?>
                            <span class="badge badge-<?= $color ?>">
                                <?= ucfirst($check->status) ?>
                                <?php if (!empty($check->verified_at)): ?>
                                    <br><small>(<?= date('d M Y', strtotime($check->verified_at)) ?>)</small>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-info" data-toggle="tooltip" title="View Details" 
                                        onclick="viewCheckDetails(<?= $check->id ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (in_array($application->status, ['draft', 'pending', 'under_review'])): ?>
                                <button class="btn btn-warning" data-toggle="tooltip" title="Edit" 
                                        onclick="editCheck(<?= $check->id ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger" data-toggle="tooltip" title="Delete" 
                                        onclick="deleteCheck(<?= $check->id ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php if (!empty($check->notes)): ?>
                    <tr>
                        <td colspan="8" class="bg-light">
                            <small class="text-muted"><strong>Notes:</strong> <?= $check->notes ?></small>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="p-4 text-center text-muted">
                <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                <p>No checks collected yet</p>
                <?php if (in_array($application->status, ['draft', 'pending', 'under_review'])): ?>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addCheckModal">
                        <i class="fas fa-plus mr-1"></i> Add Check Details
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Check Details Summary Card -->
<?php if (!empty($checks)): ?>
<div class="row mt-3">
    <div class="col-md-3">
        <div class="info-box bg-blue">
            <span class="info-box-icon"><i class="fas fa-file-invoice-dollar"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Checks</span>
                <span class="info-box-number"><?= count($checks) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-green">
            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Verified</span>
                <span class="info-box-number"><?= count(array_filter($checks, function($c) { return $c->status === 'verified'; })) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-orange">
            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending</span>
                <span class="info-box-number"><?= count(array_filter($checks, function($c) { return $c->status === 'pending'; })) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-red">
            <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Issues</span>
                <span class="info-box-number"><?= count(array_filter($checks, function($c) { return in_array($c->status, ['bounced', 'cancelled']); })) ?></span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal for Adding/Editing Checks -->
<div class="modal fade" id="addCheckModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Check Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addCheckForm" action="<?= site_url('admin/loans/save_check_details') ?>" method="post">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <?= form_hidden('application_id', $application->id) ?>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Check Type <span class="text-danger">*</span></label>
                        <select class="form-control" name="check_type" required id="checkTypeSelect" onchange="updateCheckTypeInfo()">
                            <option value="">Select Type</option>
                            <option value="applicant">Applicant Check</option>
                            <option value="guarantor">Guarantor Check</option>
                        </select>
                    </div>

                    <div id="guarantorSelectDiv" style="display:none;">
                        <div class="form-group">
                            <label>Guarantor <span class="text-danger">*</span></label>
                            <select class="form-control" name="guarantor_id" id="guarantorSelect">
                                <option value="">Select Guarantor</option>
                                <?php foreach ($guarantors as $g): ?>
                                    <option value="<?= $g->id ?>">
                                        <?= $g->guar_member_code ?> - <?= $g->guar_first_name ?> <?= $g->guar_last_name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Check Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="check_number" placeholder="e.g., 123456" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bank Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="bank_name" placeholder="e.g., HDFC Bank" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Account Number</label>
                                <input type="text" class="form-control" name="account_number" placeholder="Optional">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>IFSC Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ifsc_code" placeholder="e.g., HDFC0000456" 
                                       pattern="^[A-Z]{4}0[A-Z0-9]{6}$" maxlength="11" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="amount" placeholder="0.00" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Check Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="check_date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notes / Remarks</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Any special notes..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Save Check Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateCheckTypeInfo() {
    const checkType = document.getElementById('checkTypeSelect').value;
    const guarantorDiv = document.getElementById('guarantorSelectDiv');
    const guarantorSelect = document.getElementById('guarantorSelect');
    
    if (checkType === 'guarantor') {
        guarantorDiv.style.display = 'block';
        guarantorSelect.setAttribute('required', 'required');
    } else {
        guarantorDiv.style.display = 'none';
        guarantorSelect.removeAttribute('required');
    }
}

function viewCheckDetails(checkId) {
    // Load and display check details in a modal
    $.ajax({
        url: '<?= site_url("admin/loans/get_check_details/") ?>' + checkId,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const check = response.data;
                let modal = `
                    <div class="modal fade" id="viewCheckModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Check Details</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <table class="table table-sm">
                                        <tr><td><strong>Check Number:</strong></td><td><code>${check.check_number}</code></td></tr>
                                        <tr><td><strong>Bank:</strong></td><td>${check.bank_name}</td></tr>
                                        <tr><td><strong>IFSC:</strong></td><td><code>${check.ifsc_code}</code></td></tr>
                                        <tr><td><strong>Amount:</strong></td><td>${check.amount}</td></tr>
                                        <tr><td><strong>Check Date:</strong></td><td>${check.check_date}</td></tr>
                                        <tr><td><strong>Status:</strong></td><td><span class="badge badge-info">${check.status}</span></td></tr>
                                        ${check.verified_by ? '<tr><td><strong>Verified By:</strong></td><td>' + check.verified_by_name + ' on ' + check.verified_at + '</td></tr>' : ''}
                                    </table>
                                    ${check.notes ? '<p><strong>Notes:</strong> ' + check.notes + '</p>' : ''}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Remove old modal if exists
                $('#viewCheckModal').remove();
                
                // Add and show new modal
                $('body').append(modal);
                $('#viewCheckModal').modal('show');
            }
        }
    });
}

function editCheck(checkId) {
    alert('Edit functionality - load check details into add modal');
}

function deleteCheck(checkId) {
    if (confirm('Are you sure you want to delete this check?')) {
        $.ajax({
            url: '<?= site_url("admin/loans/delete_check/") ?>' + checkId,
            method: 'POST',
            data: { '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>' },
            success: function(response) {
                if (response.success) {
                    alert('Check deleted successfully');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }
        });
    }
}
</script>

<style>
.info-box {
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.bg-blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.bg-green { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
.bg-orange { background: linear-gradient(135deg, #ffa751 0%, #ffe259 100%); color: white; }
.bg-red { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; }
</style>
