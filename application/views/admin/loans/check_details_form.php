<!-- Check Details Section - Add to Loan Application Form -->

<hr class="my-4">

<h5 class="text-primary border-bottom pb-2 mb-3">
    <i class="fas fa-file-invoice-dollar mr-1"></i> Check / Security Details
    <small class="text-muted d-block mt-1">Collect blank check from applicant and all guarantors for loan security</small>
</h5>

<!-- Applicant Check -->
<div class="card card-info mb-3">
    <div class="card-header">
        <h6 class="card-title mb-0">
            <i class="fas fa-user-check mr-2"></i> Applicant Check
            <span class="badge badge-info ml-2">REQUIRED</span>
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="check_number_applicant">Check Number <span class="text-danger">*</span>
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Check number from the blank check provided by applicant"></i>
                    </label>
                    <input type="text" class="form-control" id="check_number_applicant" 
                           name="applicant_check[check_number]" placeholder="e.g., 123456"
                           value="<?= set_value('applicant_check[check_number]') ?>" required>
                    <small class="text-muted">10-15 digit check number</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="bank_name_applicant">Bank Name <span class="text-danger">*</span>
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Bank name where the check is drawn from"></i>
                    </label>
                    <input type="text" class="form-control" id="bank_name_applicant" 
                           name="applicant_check[bank_name]" placeholder="e.g., HDFC Bank, State Bank of India"
                           value="<?= set_value('applicant_check[bank_name]') ?>" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="account_number_applicant">Account Number
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Account number (optional - can be obtained from check)"></i>
                    </label>
                    <input type="text" class="form-control" id="account_number_applicant" 
                           name="applicant_check[account_number]" placeholder="e.g., XXXX XXXX XXXX 1234"
                           value="<?= set_value('applicant_check[account_number]') ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="ifsc_code_applicant">IFSC Code <span class="text-danger">*</span>
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="11-character IFSC code (e.g., HDFC0000456)"></i>
                    </label>
                    <input type="text" class="form-control" id="ifsc_code_applicant" 
                           name="applicant_check[ifsc_code]" placeholder="e.g., HDFC0000456"
                           value="<?= set_value('applicant_check[ifsc_code]') ?>" 
                           pattern="^[A-Z]{4}0[A-Z0-9]{6}$" maxlength="11" required>
                    <small class="text-muted">Format: XXXXXX0XXXXX (11 characters)</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="check_amount_applicant">Check Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span>
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Amount of check (usually equals or exceeds loan amount)"></i>
                    </label>
                    <input type="number" class="form-control" id="check_amount_applicant" 
                           name="applicant_check[amount]" placeholder="Amount"
                           value="<?= set_value('applicant_check[amount]') ?>" 
                           min="0" step="0.01" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="check_date_applicant">Check Date <span class="text-danger">*</span>
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Date written on the check"></i>
                    </label>
                    <input type="date" class="form-control" id="check_date_applicant" 
                           name="applicant_check[check_date]"
                           value="<?= set_value('applicant_check[check_date]', date('Y-m-d')) ?>" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="check_notes_applicant">Notes / Remarks
                <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Any special notes about this check (optional)"></i>
            </label>
            <textarea class="form-control" id="check_notes_applicant" name="applicant_check[notes]" 
                      rows="2" placeholder="e.g., Check in applicant's name, etc."><?= set_value('applicant_check[notes]') ?></textarea>
        </div>
    </div>
</div>

<!-- Guarantor Checks -->
<div id="guarantorChecksContainer">
    <!-- Guarantor checks will be added here dynamically -->
</div>

<div class="form-group">
    <small class="text-muted d-block mb-2">
        <i class="fas fa-exclamation-triangle mr-1"></i> 
        <strong>Important:</strong> Ensure all details match the blank checks provided. Save this information for future reference and verification.
    </small>
</div>

<script>
// Add guarantor check section when guarantor is added
function addGuarantorCheckSection(guarantor_id, guarantor_name, guarantor_code) {
    const container = document.getElementById('guarantorChecksContainer');
    const sectionId = 'guarantor_check_' + guarantor_id;
    
    // Check if section already exists
    if (document.getElementById(sectionId)) {
        return;
    }
    
    const html = `
        <div class="card card-warning mb-3" id="${sectionId}">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-user-shield mr-2"></i> Guarantor Check - ${guarantor_name} (${guarantor_code})
                    <span class="badge badge-warning ml-2">REQUIRED</span>
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="check_number_guar_${guarantor_id}">Check Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="check_number_guar_${guarantor_id}" 
                                   name="guarantor_checks[${guarantor_id}][check_number]" placeholder="e.g., 123456">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bank_name_guar_${guarantor_id}">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="bank_name_guar_${guarantor_id}" 
                                   name="guarantor_checks[${guarantor_id}][bank_name]" placeholder="e.g., HDFC Bank">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="account_number_guar_${guarantor_id}">Account Number</label>
                            <input type="text" class="form-control" id="account_number_guar_${guarantor_id}" 
                                   name="guarantor_checks[${guarantor_id}][account_number]">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ifsc_code_guar_${guarantor_id}">IFSC Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ifsc_code_guar_${guarantor_id}" 
                                   name="guarantor_checks[${guarantor_id}][ifsc_code]" placeholder="e.g., HDFC0000456"
                                   pattern="^[A-Z]{4}0[A-Z0-9]{6}$" maxlength="11">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="check_amount_guar_${guarantor_id}">Check Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="check_amount_guar_${guarantor_id}" 
                                   name="guarantor_checks[${guarantor_id}][amount]" placeholder="Amount" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="check_date_guar_${guarantor_id}">Check Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="check_date_guar_${guarantor_id}" 
                                   name="guarantor_checks[${guarantor_id}][check_date]" value="${new Date().toISOString().split('T')[0]}">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="check_notes_guar_${guarantor_id}">Notes / Remarks</label>
                    <textarea class="form-control" id="check_notes_guar_${guarantor_id}" 
                              name="guarantor_checks[${guarantor_id}][notes]" rows="2"></textarea>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
}

// Remove guarantor check section
function removeGuarantorCheckSection(guarantor_id) {
    const sectionId = 'guarantor_check_' + guarantor_id;
    const element = document.getElementById(sectionId);
    if (element) {
        element.remove();
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<style>
.card-info {
    border-top: 3px solid #17a2b8;
}
.card-warning {
    border-top: 3px solid #ffc107;
}
</style>
