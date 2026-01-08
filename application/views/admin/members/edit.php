<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-user-edit mr-1"></i> Edit Member - <?= $member->member_code ?>
        </h3>
    </div>
    <form action="<?= site_url('admin/members/update/' . $member->id) ?>" method="post" enctype="multipart/form-data" id="memberForm">
        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
        
        <div class="card-body">
            <!-- Personal Information -->
            <h5 class="text-primary border-bottom pb-2 mb-3">
                <i class="fas fa-user mr-1"></i> Personal Information
            </h5>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?= set_value('first_name', isset($member->first_name) ? $member->first_name : '') ?>" required>
                        <span class="text-danger"><?= form_error('first_name') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                           <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?= set_value('last_name', isset($member->last_name) ? $member->last_name : '') ?>" required>
                        <span class="text-danger"><?= form_error('last_name') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="phone">Phone Number <span class="text-danger">*</span></label>
                           <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?= set_value('phone', isset($member->phone) ? $member->phone : '') ?>" required maxlength="10">
                        <span class="text-danger"><?= form_error('phone') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="email">Email</label>
                           <input type="email" class="form-control" id="email" name="email" 
                               value="<?= set_value('email', isset($member->email) ? $member->email : '') ?>">
                        <span class="text-danger"><?= form_error('email') ?></span>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                           <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                               value="<?= set_value('date_of_birth', isset($member->date_of_birth) ? $member->date_of_birth : '') ?>">
                        <span class="text-danger"><?= form_error('date_of_birth') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select class="form-control" id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" <?= set_select('gender', 'male', (isset($member->gender) && $member->gender == 'male')) ?>>Male</option>
                            <option value="female" <?= set_select('gender', 'female', (isset($member->gender) && $member->gender == 'female')) ?>>Female</option>
                            <option value="other" <?= set_select('gender', 'other', (isset($member->gender) && $member->gender == 'other')) ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="marital_status">Marital Status</label>
                        <select class="form-control" id="marital_status" name="marital_status">
                            <option value="">Select Status</option>
                            <option value="single" <?= set_select('marital_status', 'single', (isset($member->marital_status) && $member->marital_status == 'single')) ?>>Single</option>
                            <option value="married" <?= set_select('marital_status', 'married', (isset($member->marital_status) && $member->marital_status == 'married')) ?>>Married</option>
                            <option value="widowed" <?= set_select('marital_status', 'widowed', (isset($member->marital_status) && $member->marital_status == 'widowed')) ?>>Widowed</option>
                            <option value="divorced" <?= set_select('marital_status', 'divorced', (isset($member->marital_status) && $member->marital_status == 'divorced')) ?>>Divorced</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="status">Member Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="active" <?= set_select('status', 'active', (isset($member->status) && $member->status == 'active')) ?>>Active</option>
                            <option value="inactive" <?= set_select('status', 'inactive', (isset($member->status) && $member->status == 'inactive')) ?>>Inactive</option>
                            <option value="suspended" <?= set_select('status', 'suspended', (isset($member->status) && $member->status == 'suspended')) ?>>Suspended</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Current Photo</label>
                        <div>
                            <?php if (isset($member->profile_image) && !empty($member->profile_image)): ?>
                                    <img src="<?= base_url('uploads/profile_images/' . $member->profile_image) ?>" class="img-thumbnail" style="width: 80px; height: 80px;">
                                <?php else: ?>
                                    <span class="text-muted">No photo</span>
                                <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="profile_image">Change Photo</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="profile_image" name="profile_image" accept="image/*">
                            <label class="custom-file-label" for="profile_image">Choose file</label>
                        </div>
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
                        <label for="id_proof_type">ID Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="id_proof_type" name="id_proof_type" required>
                            <option value="">Select ID Type</option>
                            <option value="aadhar" <?= set_select('id_proof_type', 'aadhar', (isset($member->id_proof_type) && $member->id_proof_type == 'aadhar')) ?>>Aadhar Card</option>
                            <option value="voter_id" <?= set_select('id_proof_type', 'voter_id', (isset($member->id_proof_type) && $member->id_proof_type == 'voter_id')) ?>>Voter ID</option>
                            <option value="driving_license" <?= set_select('id_proof_type', 'driving_license', (isset($member->id_proof_type) && $member->id_proof_type == 'driving_license')) ?>>Driving License</option>
                            <option value="passport" <?= set_select('id_proof_type', 'passport', (isset($member->id_proof_type) && $member->id_proof_type == 'passport')) ?>>Passport</option>
                            <option value="ration_card" <?= set_select('id_proof_type', 'ration_card', (isset($member->id_proof_type) && $member->id_proof_type == 'ration_card')) ?>>Ration Card</option>
                        </select>
                        <span class="text-danger"><?= form_error('id_proof_type') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="id_proof_number">ID Number <span class="text-danger">*</span></label>
                           <input type="text" class="form-control" id="id_proof_number" name="id_proof_number" 
                               value="<?= set_value('id_proof_number', isset($member->id_proof_number) ? $member->id_proof_number : '') ?>" required>
                        <span class="text-danger"><?= form_error('id_proof_number') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="pan_number">PAN Number</label>
                           <input type="text" class="form-control text-uppercase" id="pan_number" name="pan_number" 
                               value="<?= set_value('pan_number', isset($member->pan_number) ? $member->pan_number : '') ?>" maxlength="10">
                        <span class="text-danger"><?= form_error('pan_number') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="id_proof_document">Update ID Proof</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="id_proof_document" name="id_proof_document" accept="image/*,.pdf">
                            <label class="custom-file-label" for="id_proof_document">Choose file</label>
                        </div>
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
                        <label for="address_line1">Address Line 1 <span class="text-danger">*</span></label>
                           <input type="text" class="form-control" id="address_line1" name="address_line1" 
                               value="<?= set_value('address_line1', isset($member->address_line1) ? $member->address_line1 : '') ?>" required>
                        <span class="text-danger"><?= form_error('address_line1') ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="address_line2">Address Line 2</label>
                           <input type="text" class="form-control" id="address_line2" name="address_line2" 
                               value="<?= set_value('address_line2', isset($member->address_line2) ? $member->address_line2 : '') ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="city">City <span class="text-danger">*</span></label>
                           <input type="text" class="form-control" id="city" name="city" 
                               value="<?= set_value('city', isset($member->city) ? $member->city : '') ?>" required>
                        <span class="text-danger"><?= form_error('city') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="state">State <span class="text-danger">*</span></label>
                        <select class="form-control" id="state" name="state" required>
                            <option value="">Select State</option>
                            <?php
                            $states = ['Andhra Pradesh', 'Bihar', 'Delhi', 'Gujarat', 'Haryana', 'Karnataka', 'Maharashtra', 'Punjab', 'Rajasthan', 'Tamil Nadu', 'Telangana', 'Uttar Pradesh', 'West Bengal'];
                            foreach ($states as $state):
                            ?>
                            <option value="<?= $state ?>" <?= set_select('state', $state, (isset($member->state) && $member->state == $state)) ?>><?= $state ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="text-danger"><?= form_error('state') ?></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="pincode">PIN Code <span class="text-danger">*</span></label>
                           <input type="text" class="form-control" id="pincode" name="pincode" 
                               value="<?= set_value('pincode', isset($member->pincode) ? $member->pincode : '') ?>" required maxlength="6">
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
                               value="<?= set_value('occupation', isset($member->occupation) ? $member->occupation : '') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="monthly_income">Monthly Income (₹)</label>
                           <input type="number" class="form-control" id="monthly_income" name="monthly_income" 
                               value="<?= set_value('monthly_income', isset($member->monthly_income) ? $member->monthly_income : '') ?>" min="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="employer_name">Employer / Business Name</label>
                           <input type="text" class="form-control" id="employer_name" name="employer_name" 
                               value="<?= set_value('employer_name', isset($member->employer_name) ? $member->employer_name : '') ?>">
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
                               value="<?= set_value('bank_name', isset($member->bank_name) ? $member->bank_name : '') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="bank_account_number">Account Number</label>
                           <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" 
                               value="<?= set_value('bank_account_number', isset($member->bank_account_number) ? $member->bank_account_number : '') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="bank_ifsc">IFSC Code</label>
                           <input type="text" class="form-control text-uppercase" id="bank_ifsc" name="bank_ifsc" 
                               value="<?= set_value('bank_ifsc', isset($member->bank_ifsc) ? $member->bank_ifsc : '') ?>" maxlength="11">
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
                        <label for="nominee_name">Nominee Name <span class="text-danger">*</span></label>
                           <input type="text" class="form-control" id="nominee_name" name="nominee_name" 
                               value="<?= set_value('nominee_name', isset($member->nominee_name) ? $member->nominee_name : '') ?>" required>
                        <span class="text-danger"><?= form_error('nominee_name') ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="nominee_relationship">Relationship <span class="text-danger">*</span></label>
                        <select class="form-control" id="nominee_relationship" name="nominee_relationship" required>
                            <option value="">Select Relationship</option>
                            <?php
                            $relationships = ['spouse', 'father', 'mother', 'son', 'daughter', 'brother', 'sister', 'other'];
                            foreach ($relationships as $rel):
                            ?>
                            <option value="<?= $rel ?>" <?= set_select('nominee_relationship', $rel, (isset($member->nominee_relationship) && $member->nominee_relationship == $rel)) ?>><?= ucfirst($rel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="text-danger"><?= form_error('nominee_relationship') ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="nominee_phone">Nominee Phone</label>
                           <input type="tel" class="form-control" id="nominee_phone" name="nominee_phone" 
                               value="<?= set_value('nominee_phone', isset($member->nominee_phone) ? $member->nominee_phone : '') ?>" maxlength="10">
                    </div>
                </div>
            </div>
            
            <!-- Remarks -->
            <h5 class="text-primary border-bottom pb-2 mb-3 mt-4">
                <i class="fas fa-sticky-note mr-1"></i> Additional Notes
            </h5>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2"><?= set_value('remarks', isset($member->remarks) ? $member->remarks : '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-save mr-1"></i> Update Member
            </button>
            <a href="<?= site_url('admin/members/view/' . $member->id) ?>" class="btn btn-secondary">
                <i class="fas fa-times mr-1"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Custom file input label update
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass("selected").html(fileName || 'Choose file');
    });
    
    // Validations
    $('#phone, #nominee_phone').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });
    
    $('#pincode').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
    });
    
    $('#pan_number').on('input', function() {
        this.value = this.value.toUpperCase().slice(0, 10);
    });
    
    $('#bank_ifsc').on('input', function() {
        this.value = this.value.toUpperCase().slice(0, 11);
    });
    
    // Form submit
    $('#memberForm').on('submit', function(e) {
        var phone = $('#phone').val();
        if (phone.length !== 10) {
            e.preventDefault();
            Swal.fire('Error', 'Please enter a valid 10-digit phone number', 'error');
            return false;
        }
        
        Swal.fire({
            title: 'Updating...',
            text: 'Please wait while we update the member details',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => { Swal.showLoading(); }
        });
    });
});
</script>
