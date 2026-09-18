<!-- Admin Manual Check Collection Form - For Manual Entry After Collecting Proof -->
<!-- This section allows admins to manually fill in check details after collecting proof from members -->

<div class="card card-primary mt-4" id="checkDetailsCard">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-file-invoice-dollar mr-2"></i> Check Collection Records
            <small class="text-muted d-block mt-1">Manually enter check details after collecting proof from applicant & guarantors</small>
        </h5>
    </div>
    
    <div class="card-body">
        <!-- Existing Checks Table -->
        <?php if (!empty($checks)): ?>
        <div class="mb-4">
            <h6 class="text-bold mb-3">
                <i class="fas fa-list mr-2"></i> Collected Checks (<?= count($checks) ?>)
            </h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>Type</th>
                            <th>Member/Guarantor</th>
                            <th>Check #</th>
                            <th>Bank & IFSC</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checks as $check): ?>
                        <tr>
                            <td>
                                <span class="badge <?= $check->check_type === 'applicant' ? 'badge-info' : 'badge-warning' ?>">
                                    <?= ucfirst($check->check_type) ?>
                                </span>
                            </td>
                            <td><?= $check->member_name ?: 'N/A' ?></td>
                            <td><code><?= $check->check_number ?></code></td>
                            <td>
                                <small><?= $check->bank_name ?><br>
                                <?= $check->ifsc_code ?></small>
                            </td>
                            <td class="text-right"><?= get_currency_symbol() ?> <?= number_format($check->amount, 2) ?></td>
                            <td><?= date('d-M-Y', strtotime($check->check_date)) ?></td>
                            <td>
                                <span class="badge badge-<?= 
                                    $check->status === 'pending' ? 'warning' : 
                                    ($check->status === 'verified' ? 'success' : 
                                    ($check->status === 'encashed' ? 'info' : 'danger')) 
                                ?>">
                                    <?= ucfirst($check->status) ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-xs btn-info" onclick="editCheck(<?= $check->id ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-xs btn-danger" onclick="deleteCheck(<?= $check->id ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <hr>
        </div>
        <?php else: ?>
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle mr-2"></i> No checks collected yet
        </div>
        <?php endif; ?>

        <!-- Admin Manual Entry Form -->
        <h6 class="text-bold mb-3">
            <i class="fas fa-plus-circle mr-2"></i> Add New Check
        </h6>
        
        <form id="addCheckForm" class="row">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <input type="hidden" name="application_id" value="<?= $application->id ?>">
            <input type="hidden" name="check_id" id="check_id" value="">
            <input type="hidden" name="loan_id" value="<?= isset($application->loan_id) ? $application->loan_id : '' ?>">
            <input type="hidden" name="guarantor_id" id="guarantor_id" value="">

            <!-- Check Type Selection -->
            <div class="col-md-6 form-group">
                <label for="check_type">Check For <span class="text-danger">*</span></label>
                <select class="form-control" id="check_type" name="check_type" required onchange="updateMemberSelect()">
                    <option value="">-- Select --</option>
                    <option value="applicant">Applicant</option>
                    <option value="guarantor">Guarantor</option>
                </select>
                <small class="text-muted">Who does this check belong to?</small>
            </div>

            <!-- Member/Guarantor Selection -->
            <div class="col-md-6 form-group">
                <label for="member_id">Member/Guarantor <span class="text-danger">*</span></label>
                <select class="form-control" id="member_id" name="member_id" required onchange="updateGuarantorId()">
                    <option value="">-- Select --</option>
                </select>
                <small class="text-muted">Person who provided the check</small>
            </div>

            <!-- Check Number -->
            <div class="col-md-4 form-group">
                <label for="check_number">Check Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="check_number" name="check_number" 
                       placeholder="e.g., 123456" required>
                <small class="text-muted">From blank check</small>
            </div>

            <!-- Bank Name -->
            <div class="col-md-4 form-group">
                <label for="bank_name">Bank Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="bank_name" name="bank_name" 
                       placeholder="e.g., HDFC Bank" required>
                <small class="text-muted">From check</small>
            </div>

            <!-- IFSC Code -->
            <div class="col-md-4 form-group">
                <label for="ifsc_code">IFSC Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="ifsc_code" name="ifsc_code" 
                       placeholder="e.g., HDFC0000456" 
                       maxlength="11" required
                       autocomplete="off">
                <small class="text-muted">11 characters (Format: 4 letters + 0 + 6 alphanumeric, e.g. HDFC0000456)</small>
            </div>

            <!-- Account Number -->
            <div class="col-md-4 form-group">
                <label for="account_number">Account Number</label>
                <input type="text" class="form-control" id="account_number" name="account_number" 
                       placeholder="Optional">
                <small class="text-muted">Account number from check</small>
            </div>

            <!-- Amount -->
            <div class="col-md-4 form-group">
                <label for="amount">Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="amount" name="amount" 
                       placeholder="0.00" min="0" step="0.01" required>
                <small class="text-muted">Check amount</small>
            </div>

            <!-- Check Date -->
            <div class="col-md-4 form-group">
                <label for="check_date">Check Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="check_date" name="check_date" required>
                <small class="text-muted">Date on check</small>
            </div>

            <!-- Notes -->
            <div class="col-md-12 form-group">
                <label for="notes">Notes / Remarks</label>
                <textarea class="form-control" id="notes" name="notes" rows="2" 
                          placeholder="e.g., Check in applicant's name, post-dated check, etc."></textarea>
            </div>

            <!-- Form Buttons -->
            <div class="col-md-12 form-group">
                <button type="submit" class="btn btn-primary" id="saveCheckButton">
                    <i class="fas fa-save mr-1"></i> Save Check
                </button>
                <button type="reset" class="btn btn-secondary" id="resetCheckButton">
                    <i class="fas fa-undo mr-1"></i> Clear
                </button>
                <button type="button" class="btn btn-default d-none" id="cancelEditButton" onclick="resetCheckForm()">
                    <i class="fas fa-times mr-1"></i> Cancel Edit
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const csrfTokenName = '<?= $this->security->get_csrf_token_name() ?>';
// List of members (applicant) and guarantors
const applicantData = {
    id: <?= $application->member_id ?>,
    first_name: '<?= addslashes($member->first_name ?? 'N/A') ?>',
    last_name: '<?= addslashes($member->last_name ?? '') ?>',
    code: '<?= addslashes($application->member_code ?? '') ?>'
};

const guarantorsData = <?= json_encode($guarantors ?? []) ?>;
const existingChecks = <?= json_encode($checks ?? []) ?>;

function updateMemberSelect() {
    const checkType = document.getElementById('check_type').value;
    const memberSelect = document.getElementById('member_id');
    memberSelect.innerHTML = '<option value="">-- Select --</option>';
    document.getElementById('guarantor_id').value = '';

    if (checkType === 'applicant') {
        const option = document.createElement('option');
        option.value = applicantData.id;
        option.text = `${applicantData.first_name} ${applicantData.last_name} (${applicantData.code}) - Applicant`;
        memberSelect.appendChild(option);
    } else if (checkType === 'guarantor') {
        if (guarantorsData.length === 0) {
            const option = document.createElement('option');
            option.text = '-- No guarantors added --';
            option.disabled = true;
            memberSelect.appendChild(option);
            return;
        }
        
        guarantorsData.forEach(function(guarantor) {
            const option = document.createElement('option');
            option.value = guarantor.guarantor_member_id;
            option.dataset.guarantorId = guarantor.id;  // Store the guarantor_id
            option.text = `${guarantor.first_name} ${guarantor.last_name} (${guarantor.member_code})`;
            memberSelect.appendChild(option);
        });
    }
}

function updateGuarantorId() {
    const checkType = document.getElementById('check_type').value;
    const memberSelect = document.getElementById('member_id');
    const guarantorIdField = document.getElementById('guarantor_id');
    
    if (checkType === 'guarantor' && memberSelect.selectedOptions.length > 0) {
        const selectedOption = memberSelect.selectedOptions[0];
        guarantorIdField.value = selectedOption.dataset.guarantorId || '';
    } else {
        guarantorIdField.value = '';
    }
}

// Handle form submission
document.getElementById('addCheckForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?= base_url("admin/loans/save_check_details") ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message || 'Check saved successfully!');
            resetCheckForm();
            setTimeout(() => location.reload(), 1500);
        } else {
            let errorMessage = 'An error occurred while saving the check';
            
            // Detailed error reporting
            if (data.errors && data.errors.length > 0) {
                errorMessage = '<strong>Validation Errors:</strong><ul class="mt-2">';
                data.errors.forEach(error => {
                    errorMessage += '<li>' + error + '</li>';
                });
                errorMessage += '</ul>';
            } else if (data.message) {
                errorMessage = '<strong>Error:</strong><br>' + data.message;
            }
            
            showAlert('error', errorMessage);
            console.log('Save error details:', data);
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        showAlert('error', '<strong>Network Error:</strong><br>Could not reach the server. Check your connection.');
    });
});

// Edit check (placeholder - implement AJAX edit)
function editCheck(checkId) {
    const check = existingChecks.find(function(item) {
        return Number(item.id) === Number(checkId);
    });

    if (!check) {
        showAlert('error', 'Check record not found');
        return;
    }

    document.getElementById('check_id').value = check.id;
    document.getElementById('check_type').value = check.check_type;
    updateMemberSelect();
    document.getElementById('member_id').value = check.member_id;
    if (check.check_type === 'guarantor') {
        document.getElementById('guarantor_id').value = check.guarantor_id || '';
    }
    document.getElementById('check_number').value = check.check_number || '';
    document.getElementById('bank_name').value = check.bank_name || '';
    document.getElementById('ifsc_code').value = check.ifsc_code || '';
    document.getElementById('account_number').value = check.account_number || '';
    document.getElementById('amount').value = check.amount || '';
    document.getElementById('check_date').value = check.check_date || '';
    document.getElementById('notes').value = check.notes || '';
    document.getElementById('saveCheckButton').innerHTML = '<i class="fas fa-save mr-1"></i> Update Check';
    document.getElementById('cancelEditButton').classList.remove('d-none');
    document.getElementById('checkDetailsCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetCheckForm() {
    document.getElementById('addCheckForm').reset();
    document.getElementById('check_id').value = '';
    document.getElementById('guarantor_id').value = '';
    document.getElementById('member_id').innerHTML = '<option value="">-- Select --</option>';
    document.getElementById('saveCheckButton').innerHTML = '<i class="fas fa-save mr-1"></i> Save Check';
    document.getElementById('cancelEditButton').classList.add('d-none');
    document.getElementById('check_date').valueAsDate = new Date();
}

// Delete check
function deleteCheck(checkId) {
    if (confirm('Are you sure you want to delete this check record?')) {
        const formData = new FormData();
        formData.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>');

        fetch('<?= base_url("admin/loans/delete_check/") ?>' + checkId, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Check deleted successfully');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('error', data.message || 'Error deleting check');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred');
        });
    }
}

// Helper function to show alerts (supports HTML in message)
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    const alertHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas ${icon} mr-2"></i>
            <div style="display: inline-block; width: calc(100% - 30px);">${message}</div>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    document.getElementById('checkDetailsCard').insertAdjacentHTML('beforebegin', alertHTML);
    // Scroll to alert
    const alert = document.querySelector('.alert');
    if (alert) alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

document.getElementById('resetCheckButton').addEventListener('click', function() {
    resetCheckForm();
});

// IFSC code input formatter and validation hint
document.getElementById('ifsc_code').addEventListener('input', function() {
    let value = this.value.toUpperCase();
    this.value = value;
    
    const hint = document.getElementById('ifsc_hint');
    if (hint) hint.remove();
    
    if (value.length > 0) {
        let status = 'text-muted';
        let message = '';
        
        if (value.length < 11) {
            message = `${11 - value.length} more characters needed`;
            status = 'text-warning';
        } else if (value.length === 11) {
            const pattern = /^[A-Z]{4}0[A-Z0-9]{6}$/;
            if (pattern.test(value)) {
                message = '✓ Valid IFSC format';
                status = 'text-success';
            } else {
                message = '✗ Invalid format. Use: XXXXXX0XXXXX (4 letters, 0, then 6 alphanumeric)';
                status = 'text-danger';
            }
        }
        
        if (message) {
            const hintEl = document.createElement('small');
            hintEl.id = 'ifsc_hint';
            hintEl.className = `${status} d-block mt-1`;
            hintEl.textContent = message;
            this.parentElement.appendChild(hintEl);
        }
    }
});

// Initialize date field with today
resetCheckForm();
</script>
