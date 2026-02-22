<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-user-plus mr-1"></i> Add New Member
        </h3>
    </div>
    <form action="<?= site_url('admin/members/store') ?>" method="post" enctype="multipart/form-data" id="memberForm">
        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
        
        <div class="card-body">
            <!-- Member Code -->
          
                <div class="row">
                    <div class="col-md-6">
                        <label class="font-weight-bold">Member Code (Auto-Generated):</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            </div>
                            <input type="text" class="form-control form-control-lg font-weight-bold" 
                                   value="<?= isset($member_code) ? $member_code : '' ?>" readonly 
                                   style="background-color: #e9ecef; font-size: 1.1rem; letter-spacing: 1px;">
                        </div>
                        <small class="text-muted">This code will be automatically assigned to the member</small>
                    </div>
                </div>
            
            <br>
            <!-- Personal Information -->
            <h5 class="text-primary border-bottom pb-2 mb-3">
                <i class="fas fa-user mr-1"></i> Personal Information
            </h5>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?= set_value('first_name') ?>" required
                               placeholder="Enter first name" title="Member's first name as per ID proof">
                        <span class="text-danger"><?= form_error('first_name') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" class="form-control" id="middle_name" name="middle_name" 
                               value="<?= set_value('middle_name') ?>"
                               placeholder="Enter middle name" title="Member's middle name (optional)">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?= set_value('last_name') ?>" required
                               placeholder="Enter last name" title="Member's surname">
                        <span class="text-danger"><?= form_error('last_name') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?= set_value('phone') ?>" required maxlength="10"
                               placeholder="10-digit mobile number" title="Primary contact number">
                        <span class="text-danger"><?= form_error('phone') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= set_value('email') ?>"
                               placeholder="email@example.com" title="Optional email address">
                        <span class="text-danger"><?= form_error('email') ?></span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                               value="<?= set_value('date_of_birth') ?>" max="<?= date('Y-m-d', safe_timestamp('-18 years')) ?>"
                               title="Must be at least 18 years old">
                        <span class="text-danger"><?= form_error('date_of_birth') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select class="form-control" id="gender" name="gender" title="Select gender">
                            <option value="">Select Gender</option>
                            <option value="male" <?= set_select('gender', 'male') ?>>Male</option>
                            <option value="female" <?= set_select('gender', 'female') ?>>Female</option>
                            <option value="other" <?= set_select('gender', 'other') ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="marital_status">Marital Status</label>
                        <select class="form-control" id="marital_status" name="marital_status">
                            <option value="">Select Status</option>
                            <option value="single" <?= set_select('marital_status', 'single') ?>>Single</option>
                            <option value="married" <?= set_select('marital_status', 'married') ?>>Married</option>
                            <option value="widowed" <?= set_select('marital_status', 'widowed') ?>>Widowed</option>
                            <option value="divorced" <?= set_select('marital_status', 'divorced') ?>>Divorced</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="profile_image">Profile Photo</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="profile_image" name="profile_image" accept="image/*">
                            <label class="custom-file-label" for="profile_image">Choose file</label>
                        </div>
                        <small class="text-muted">Max 2MB, JPG/PNG only</small>
                    </div>
                </div>
            </div>
            
            <!-- ID Proof -->
            <h5 class="text-primary border-bottom pb-2 mb-3 mt-4">
                <i class="fas fa-id-card mr-1"></i> Identity Proof
            </h5>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="id_proof_type">ID Type</label>
                        <select class="form-control" id="id_proof_type" name="id_proof_type" title="Select primary ID type">
                            <option value="">Select ID Type</option>
                            <option value="aadhar" <?= set_select('id_proof_type', 'aadhar') ?>>Aadhar Card</option>
                            <option value="voter_id" <?= set_select('id_proof_type', 'voter_id') ?>>Voter ID</option>
                            <option value="driving_license" <?= set_select('id_proof_type', 'driving_license') ?>>Driving License</option>
                            <option value="passport" <?= set_select('id_proof_type', 'passport') ?>>Passport</option>
                            <option value="ration_card" <?= set_select('id_proof_type', 'ration_card') ?>>Ration Card</option>
                        </select>
                        <span class="text-danger"><?= form_error('id_proof_type') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="id_proof_number">ID Number</label>
                        <input type="text" class="form-control" id="id_proof_number" name="id_proof_number" 
                               value="<?= set_value('id_proof_number') ?>"
                               placeholder="Enter ID number" title="ID number as shown on document">
                        <span class="text-danger"><?= form_error('id_proof_number') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="pan_number">PAN Number</label>
                        <input type="text" class="form-control text-uppercase" id="pan_number" name="pan_number" 
                               value="<?= set_value('pan_number') ?>" maxlength="10"
                               placeholder="ABCDE1234F" title="10-character PAN number">
                        <span class="text-danger"><?= form_error('pan_number') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="id_proof_document">Upload ID Proof</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="id_proof_document" name="id_proof_document" accept="image/*,.pdf">
                            <label class="custom-file-label" for="id_proof_document">Choose file</label>
                        </div>
                        <small class="text-muted">Max 5MB, JPG/PNG/PDF</small>
                    </div>
                </div>
            </div>
            
            <!-- Address -->
            <h5 class="text-primary border-bottom pb-2 mb-3 mt-4">
                <i class="fas fa-map-marker-alt mr-1"></i> Address Details
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="address_line1">Address Line 1</label>
                        <input type="text" class="form-control" id="address_line1" name="address_line1" 
                               value="<?= set_value('address_line1') ?>"
                               placeholder="House/Building No, Street" title="Primary address">
                        <span class="text-danger"><?= form_error('address_line1') ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="address_line2">Address Line 2</label>
                        <input type="text" class="form-control" id="address_line2" name="address_line2" 
                               value="<?= set_value('address_line2') ?>"
                               placeholder="Area, Landmark" title="Additional address info">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" class="form-control" id="city" name="city" 
                               value="<?= set_value('city') ?>"
                               placeholder="City" title="City/Town name">
                        <span class="text-danger"><?= form_error('city') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="state">State</label>
                        <select class="form-control" id="state" name="state">
                            <option value="">Select State</option>
                            <option value="Andhra Pradesh" <?= set_select('state', 'Andhra Pradesh') ?>>Andhra Pradesh</option>
                            <option value="Bihar" <?= set_select('state', 'Bihar') ?>>Bihar</option>
                            <option value="Delhi" <?= set_select('state', 'Delhi') ?>>Delhi</option>
                            <option value="Gujarat" <?= set_select('state', 'Gujarat') ?>>Gujarat</option>
                            <option value="Haryana" <?= set_select('state', 'Haryana') ?>>Haryana</option>
                            <option value="Karnataka" <?= set_select('state', 'Karnataka') ?>>Karnataka</option>
                            <option value="Maharashtra" <?= set_select('state', 'Maharashtra') ?>>Maharashtra</option>
                            <option value="Punjab" <?= set_select('state', 'Punjab') ?>>Punjab</option>
                            <option value="Rajasthan" <?= set_select('state', 'Rajasthan') ?>>Rajasthan</option>
                            <option value="Tamil Nadu" <?= set_select('state', 'Tamil Nadu') ?>>Tamil Nadu</option>
                            <option value="Telangana" <?= set_select('state', 'Telangana') ?>>Telangana</option>
                            <option value="Uttar Pradesh" <?= set_select('state', 'Uttar Pradesh') ?>>Uttar Pradesh</option>
                            <option value="West Bengal" <?= set_select('state', 'West Bengal') ?>>West Bengal</option>
                            <!-- Add more states as needed -->
                        </select>
                        <span class="text-danger"><?= form_error('state') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="pincode">PIN Code</label>
                        <input type="text" class="form-control" id="pincode" name="pincode" 
                               value="<?= set_value('pincode') ?>" maxlength="6"
                               placeholder="6-digit PIN" title="6-digit postal code">
                        <span class="text-danger"><?= form_error('pincode') ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Employment -->
            <h5 class="text-primary border-bottom pb-2 mb-3 mt-4">
                <i class="fas fa-briefcase mr-1"></i> Employment Details
            </h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="occupation">Occupation</label>
                        <input type="text" class="form-control" id="occupation" name="occupation" 
                               value="<?= set_value('occupation') ?>"
                               placeholder="e.g., Business, Service, Farming" title="Member's occupation/profession">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="monthly_income">Monthly Income (₹)</label>
                        <input type="number" class="form-control" id="monthly_income" name="monthly_income" 
                               value="<?= set_value('monthly_income') ?>" min="0"
                               placeholder="Monthly income" title="Approximate monthly income">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="employer_name">Employer / Business Name</label>
                        <input type="text" class="form-control" id="employer_name" name="employer_name" 
                               value="<?= set_value('employer_name') ?>"
                               placeholder="Company/Business name" title="Employer or business name">
                    </div>
                </div>
            </div>
            
            <!-- Bank Details -->
            <h5 class="text-primary border-bottom pb-2 mb-3 mt-4">
                <i class="fas fa-university mr-1"></i> Bank Details
            </h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="bank_name">Bank Name</label>
                        <input type="text" class="form-control" id="bank_name" name="bank_name" 
                               value="<?= set_value('bank_name') ?>"
                               placeholder="Enter bank name" title="Bank name for transactions">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="bank_account_number">Account Number</label>
                        <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" 
                               value="<?= set_value('bank_account_number') ?>"
                               placeholder="Bank account number" title="Savings/Current account number">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="bank_ifsc">IFSC Code</label>
                        <input type="text" class="form-control text-uppercase" id="bank_ifsc" name="bank_ifsc" 
                               value="<?= set_value('bank_ifsc') ?>" maxlength="11"
                               placeholder="11-character IFSC" title="Bank branch IFSC code">
                    </div>
                </div>
            </div>
            
            <!-- Nominee -->
            <h5 class="text-primary border-bottom pb-2 mb-3 mt-4">
                <i class="fas fa-user-shield mr-1"></i> Nominee Details
            </h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="nominee_name">Nominee Name</label>
                        <input type="text" class="form-control" id="nominee_name" name="nominee_name" 
                               value="<?= set_value('nominee_name') ?>"
                               placeholder="Full name of nominee" title="Legal nominee for member's account">
                        <span class="text-danger"><?= form_error('nominee_name') ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="nominee_relationship">Relationship</label>
                        <select class="form-control" id="nominee_relationship" name="nominee_relationship" title="Relationship with nominee">
                            <option value="">Select Relationship</option>
                            <option value="spouse" <?= set_select('nominee_relationship', 'spouse') ?>>Spouse</option>
                            <option value="father" <?= set_select('nominee_relationship', 'father') ?>>Father</option>
                            <option value="mother" <?= set_select('nominee_relationship', 'mother') ?>>Mother</option>
                            <option value="son" <?= set_select('nominee_relationship', 'son') ?>>Son</option>
                            <option value="daughter" <?= set_select('nominee_relationship', 'daughter') ?>>Daughter</option>
                            <option value="brother" <?= set_select('nominee_relationship', 'brother') ?>>Brother</option>
                            <option value="sister" <?= set_select('nominee_relationship', 'sister') ?>>Sister</option>
                            <option value="other" <?= set_select('nominee_relationship', 'other') ?>>Other</option>
                        </select>
                        <span class="text-danger"><?= form_error('nominee_relationship') ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="nominee_phone">Nominee Phone</label>
                        <input type="tel" class="form-control" id="nominee_phone" name="nominee_phone" 
                               value="<?= set_value('nominee_phone') ?>" maxlength="10"
                               placeholder="10-digit mobile number" title="Nominee's contact number">
                    </div>
                </div>
            </div>
            
            <!-- Admin Section -->
            <h5 class="text-primary border-bottom pb-2 mb-3 mt-4">
                <i class="fas fa-shield-alt mr-1"></i> Admin Classification
            </h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="member_level">Member Level <i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" title="Admin-only classification for this member"></i></label>
                        <select class="form-control" id="member_level" name="member_level" title="Select member level">
                            <option value="">-- Select Level --</option>
                            <option value="founding_member" <?= set_select('member_level', 'founding_member') ?>>Founding Member</option>
                            <option value="level2" <?= set_select('member_level', 'level2') ?>>Level 2 Member</option>
                            <option value="level3" <?= set_select('member_level', 'level3') ?>>Level 3 Member</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Referral -->
            <h5 class="text-primary border-bottom pb-2 mb-3 mt-4">
                <i class="fas fa-share-alt mr-1"></i> Referral Details (Optional)
            </h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="referred_by">Referred By (Member)</label>
                        <select class="form-control select2" id="referred_by" name="referred_by" 
                                data-placeholder="Search member..." title="Select if referred by existing member">
                            <option value="">Select Member</option>
                            <?php foreach ($existing_members ?? [] as $m): ?>
                                <option value="<?= $m->id ?>" <?= set_select('referred_by', $m->id) ?>><?= $m->member_code ?> - <?= $m->first_name ?> <?= $m->last_name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2"
                                  placeholder="Any additional notes about the member" title="Internal notes (not shown to member)"><?= set_value('remarks') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Save Member
            </button>
            <a href="<?= site_url('admin/members') ?>" class="btn btn-secondary">
                <i class="fas fa-times mr-1"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
    
    // Custom file input label update
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass("selected").html(fileName || 'Choose file');
    });
    
    // Phone number validation
    $('#phone, #nominee_phone').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });
    
    // PIN code validation
    $('#pincode').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
    });
    
    // PAN validation
    $('#pan_number').on('input', function() {
        this.value = this.value.toUpperCase().slice(0, 10);
    });
    
    // IFSC validation
    $('#bank_ifsc').on('input', function() {
        this.value = this.value.toUpperCase().slice(0, 11);
    });
    
    // Form validation
    $('#memberForm').on('submit', function(e) {
        var phone = $('#phone').val();
        if (phone.length !== 10) {
            e.preventDefault();
            Swal.fire('Error', 'Please enter a valid 10-digit phone number', 'error');
            return false;
        }
        
        var pincode = $('#pincode').val();
        if (pincode.length > 0 && pincode.length !== 6) {
            e.preventDefault();
            Swal.fire('Error', 'Please enter a valid 6-digit PIN code', 'error');
            return false;
        }
        
        // Show loading
        Swal.fire({
            title: 'Saving...',
            text: 'Please wait while we register the member',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
    });
});
</script>
