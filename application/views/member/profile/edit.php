<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>Edit Profile</h3>
    </div>

    <?php if (validation_errors()): ?>
        <div class="alert alert-danger alert-dismissible m-3 flash-message">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-triangle mr-1"></i> Please fix the following errors:
            <?= validation_errors() ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('member/profile/edit') ?>" enctype="multipart/form-data" id="profileEditForm">
        <div class="card-body">

            <!-- Personal Information -->
            <h5 class="mb-3"><i class="fas fa-user mr-1 text-primary"></i> Personal Information</h5>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>First Name <span class="text-danger">*</span>
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Your first name as per official ID proof (Aadhaar/PAN)"></i>
                    </label>
                    <input type="text" name="first_name" class="form-control" value="<?= set_value('first_name', $member->first_name) ?>" required placeholder="Enter first name">
                    <span class="text-danger"><?= form_error('first_name') ?></span>
                </div>
                <div class="form-group col-md-6">
                    <label>Last Name <span class="text-danger">*</span>
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Your last name / surname as per official ID proof"></i>
                    </label>
                    <input type="text" name="last_name" class="form-control" value="<?= set_value('last_name', $member->last_name) ?>" required placeholder="Enter last name">
                    <span class="text-danger"><?= form_error('last_name') ?></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Phone <span class="text-danger">*</span>
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Your primary 10-digit mobile number. This is used for OTP verification and notifications."></i>
                    </label>
                    <input type="text" name="phone" class="form-control" value="<?= set_value('phone', $member->phone) ?>" required placeholder="e.g. 9876543210" maxlength="10" pattern="[0-9]{10}">
                    <span class="text-danger"><?= form_error('phone') ?></span>
                </div>
                <div class="form-group col-md-6">
                    <label>Alternate Phone
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Optional secondary phone number for emergency contact"></i>
                    </label>
                    <input type="text" name="alternate_phone" class="form-control" value="<?= set_value('alternate_phone', $member->alternate_phone) ?>" placeholder="Optional alternate number" maxlength="10">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Email
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Your email address for receiving loan statements, notifications, and password recovery"></i>
                    </label>
                    <input type="email" name="email" class="form-control" value="<?= set_value('email', $member->email) ?>" placeholder="e.g. name@example.com">
                    <span class="text-danger"><?= form_error('email') ?></span>
                </div>
                <div class="form-group col-md-6">
                    <label>Profile Photo
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Upload a passport-size photo. Max 2MB, formats: JPG, PNG"></i>
                    </label>
                    <input type="file" name="profile_photo" class="form-control-file" accept="image/*">
                    <small class="form-text text-muted">Max 2MB. Formats: JPG, PNG</small>
                    <?php if (!empty($member->photo)): ?>
                        <small class="form-text text-muted">
                            Current: <a href="<?= base_url('members/uploads/' . $member->id . '/' . $member->photo) ?>" target="_blank"><i class="fas fa-eye"></i> View Photo</a>
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Father's Name
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Father's or guardian's full name as per official records"></i>
                    </label>
                    <input type="text" name="father_name" class="form-control" value="<?= set_value('father_name', $member->father_name) ?>" placeholder="Father's / Guardian's name">
                </div>
                <div class="form-group col-md-3">
                    <label>Date of Birth <span class="text-danger">*</span>
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="You must be at least 18 years old to be a member"></i>
                    </label>
                    <input type="date" name="date_of_birth" class="form-control" required value="<?= set_value('date_of_birth', $member->date_of_birth) ?>" max="<?= date('Y-m-d', safe_timestamp('-18 years')) ?>">
                    <span class="text-danger"><?= form_error('date_of_birth') ?></span>
                </div>
                <div class="form-group col-md-3">
                    <label>Gender
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Select your gender as per official ID"></i>
                    </label>
                    <select name="gender" class="form-control">
                        <option value="">-- Select --</option>
                        <option value="male" <?= set_value('gender', $member->gender) == 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= set_value('gender', $member->gender) == 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other" <?= set_value('gender', $member->gender) == 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>

            <!-- Address -->
            <hr>
            <h5 class="mb-3"><i class="fas fa-map-marker-alt mr-1 text-primary"></i> Address</h5>
            <div class="form-group">
                <label>Address Line 1
                    <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="House/Flat No., Street name, Locality"></i>
                </label>
                <textarea name="address_line1" class="form-control" rows="2" placeholder="House/Flat No., Street, Locality"><?= set_value('address_line1', $member->address_line1) ?></textarea>
            </div>
            <div class="form-group">
                <label>Address Line 2
                    <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Area, Landmark (optional)"></i>
                </label>
                <input type="text" name="address_line2" class="form-control" value="<?= set_value('address_line2', $member->address_line2) ?>" placeholder="Area, Landmark (optional)">
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>City</label>
                    <input type="text" name="city" class="form-control" value="<?= set_value('city', $member->city) ?>" placeholder="City / Town">
                </div>
                <div class="form-group col-md-4">
                    <label>State</label>
                    <input type="text" name="state" class="form-control" value="<?= set_value('state', $member->state) ?>" placeholder="State">
                </div>
                <div class="form-group col-md-4">
                    <label>Pincode
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="6-digit postal PIN code"></i>
                    </label>
                    <input type="text" name="pincode" class="form-control" value="<?= set_value('pincode', $member->pincode) ?>" placeholder="e.g. 400001" maxlength="6" pattern="[0-9]{6}">
                    <span class="text-danger"><?= form_error('pincode') ?></span>
                </div>
            </div>

            <!-- Identification & Bank -->
            <hr>
            <h5 class="mb-3"><i class="fas fa-id-card mr-1 text-primary"></i> Identification & Bank Details</h5>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Aadhaar Number
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="12-digit Aadhaar number issued by UIDAI. Format: XXXX XXXX XXXX"></i>
                    </label>
                    <input type="text" name="aadhaar_number" data-mask="aadhaar" class="form-control" value="<?= set_value('aadhaar_number', $member->aadhaar_number) ?>" placeholder="XXXX XXXX XXXX" maxlength="14">
                    <?php if (!empty($member->aadhaar_doc)): ?>
                        <small class="form-text text-muted">Document: <a href="<?= base_url('members/uploads/' . $member->id . '/' . $member->aadhaar_doc) ?>" target="_blank"><i class="fas fa-eye"></i> View</a></small>
                    <?php endif; ?>
                </div>
                <div class="form-group col-md-4">
                    <label>PAN Number
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="10-character PAN (Permanent Account Number). Format: ABCDE1234F"></i>
                    </label>
                    <input type="text" name="pan_number" data-mask="pan" class="form-control text-uppercase" value="<?= set_value('pan_number', $member->pan_number) ?>" placeholder="ABCDE1234F" maxlength="10">
                    <?php if (!empty($member->pan_doc)): ?>
                        <small class="form-text text-muted">Document: <a href="<?= base_url('members/uploads/' . $member->id . '/' . $member->pan_doc) ?>" target="_blank"><i class="fas fa-eye"></i> View</a></small>
                    <?php endif; ?>
                </div>
                <div class="form-group col-md-4">
                    <label>Voter ID
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Voter ID / Election Card number (optional)"></i>
                    </label>
                    <input type="text" name="voter_id" class="form-control" value="<?= set_value('voter_id', $member->voter_id) ?>" placeholder="Voter ID number">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Aadhaar Document
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Upload scanned Aadhaar card. Max 2MB, formats: JPG, PNG, PDF"></i>
                    </label>
                    <input type="file" name="aadhaar_doc" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf">
                    <small class="form-text text-muted">JPG, PNG or PDF (max 2MB)</small>
                </div>
                <div class="form-group col-md-4">
                    <label>PAN Document
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Upload scanned PAN card. Max 2MB, formats: JPG, PNG, PDF"></i>
                    </label>
                    <input type="file" name="pan_doc" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf">
                    <small class="form-text text-muted">JPG, PNG or PDF (max 2MB)</small>
                </div>
                <div class="form-group col-md-4">
                    <label>Address Proof
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Upload address proof (utility bill, bank statement, etc.). Max 2MB, formats: JPG, PNG, PDF"></i>
                    </label>
                    <input type="file" name="address_proof" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf">
                    <small class="form-text text-muted">JPG, PNG or PDF (max 2MB)</small>
                    <?php if (!empty($member->address_proof_doc)): ?>
                        <small class="form-text text-muted">Document: <a href="<?= base_url('members/uploads/' . $member->id . '/' . $member->address_proof_doc) ?>" target="_blank"><i class="fas fa-eye"></i> View</a></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Bank Name
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Name of your bank (e.g. State Bank of India, HDFC Bank)"></i>
                    </label>
                    <input type="text" name="bank_name" class="form-control" value="<?= set_value('bank_name', $member->bank_name) ?>" placeholder="e.g. State Bank of India">
                </div>
                <div class="form-group col-md-4">
                    <label>Bank Branch
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Branch name where your account is held"></i>
                    </label>
                    <input type="text" name="bank_branch" class="form-control" value="<?= set_value('bank_branch', $member->bank_branch) ?>" placeholder="Branch name">
                </div>
                <div class="form-group col-md-4">
                    <label>Account Number
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Your bank account number for loan disbursement and refunds"></i>
                    </label>
                    <input type="text" name="account_number" class="form-control" value="<?= set_value('account_number', $member->account_number) ?>" placeholder="Bank account number">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>IFSC Code
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="11-character IFSC code of your bank branch. Format: XXXX0XXXXXX (find it on your cheque book or bank website)"></i>
                    </label>
                    <input type="text" name="ifsc_code" class="form-control text-uppercase" value="<?= set_value('ifsc_code', $member->ifsc_code) ?>" placeholder="e.g. SBIN0001234" maxlength="11">
                    <span class="text-danger"><?= form_error('ifsc_code') ?></span>
                </div>
                <div class="form-group col-md-6">
                    <label>Account Holder Name
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Name as printed on your bank passbook / cheque. Must match your KYC name."></i>
                    </label>
                    <input type="text" name="account_holder_name" class="form-control" value="<?= set_value('account_holder_name', $member->account_holder_name) ?>" placeholder="Name as per bank records">
                </div>
            </div>

            <!-- Nominee -->
            <hr>
            <h5 class="mb-3"><i class="fas fa-user-shield mr-1 text-primary"></i> Nominee Details
                <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Nominee is the person who will receive benefits in case of unforeseen circumstances. It is recommended to add a nominee."></i>
            </h5>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Nominee Name
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Full name of the person you wish to nominate"></i>
                    </label>
                    <input type="text" name="nominee_name" class="form-control" value="<?= set_value('nominee_name', $member->nominee_name) ?>" placeholder="Nominee's full name">
                </div>
                <div class="form-group col-md-3">
                    <label>Relation
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Your relationship with the nominee (e.g. Spouse, Son, Daughter, Parent)"></i>
                    </label>
                    <input type="text" name="nominee_relation" class="form-control" value="<?= set_value('nominee_relation', $member->nominee_relation) ?>" placeholder="e.g. Spouse, Son">
                </div>
                <div class="form-group col-md-3">
                    <label>Nominee Phone
                        <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Nominee's 10-digit mobile number"></i>
                    </label>
                    <input type="text" name="nominee_phone" class="form-control" value="<?= set_value('nominee_phone', $member->nominee_phone) ?>" placeholder="10-digit number" maxlength="10">
                </div>
            </div>
            <div class="form-group">
                <label>Nominee Aadhaar
                    <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Nominee's 12-digit Aadhaar number for identity verification"></i>
                </label>
                <input type="text" name="nominee_aadhaar" data-mask="aadhaar" class="form-control" value="<?= set_value('nominee_aadhaar', $member->nominee_aadhaar) ?>" placeholder="XXXX XXXX XXXX" maxlength="14">
            </div>

            <div class="form-group">
                <label>Notes
                    <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Any additional notes or remarks (visible only to you and admin)"></i>
                </label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes or remarks"><?= set_value('notes', $member->notes) ?></textarea>
            </div>

        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-success" data-toggle="tooltip" title="Save all changes to your profile">
                <i class="fas fa-save mr-1"></i> Save Changes
            </button>
            <a href="<?= site_url('member/profile') ?>" class="btn btn-default ml-2" data-toggle="tooltip" title="Discard changes and go back to profile">
                <i class="fas fa-times mr-1"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
$(function() {
    // Client-side validation with user-friendly messages
    $('#profileEditForm').on('submit', function(e) {
        var phone = $('input[name="phone"]').val();
        if (phone && !/^[0-9]{10}$/.test(phone)) {
            e.preventDefault();
            toastr.error('Phone number must be exactly 10 digits');
            $('input[name="phone"]').focus();
            return false;
        }

        var pincode = $('input[name="pincode"]').val();
        if (pincode && !/^[0-9]{6}$/.test(pincode)) {
            e.preventDefault();
            toastr.error('Pincode must be exactly 6 digits');
            $('input[name="pincode"]').focus();
            return false;
        }

        var pan = $('input[name="pan_number"]').val();
        if (pan && !/^[A-Z]{5}[0-9]{4}[A-Z]$/.test(pan.toUpperCase())) {
            e.preventDefault();
            toastr.error('PAN format must be ABCDE1234F (5 letters, 4 digits, 1 letter)');
            $('input[name="pan_number"]').focus();
            return false;
        }

        var ifsc = $('input[name="ifsc_code"]').val();
        if (ifsc && !/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifsc.toUpperCase())) {
            e.preventDefault();
            toastr.error('IFSC format must be XXXX0XXXXXX (4 letters, 0, 6 alphanumeric)');
            $('input[name="ifsc_code"]').focus();
            return false;
        }

        // Show loading indicator
        $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');
    });
});
</script>