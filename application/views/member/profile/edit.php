<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Edit Profile</h3>
    </div>
    <form method="post" action="<?= site_url('member/profile/edit') ?>" enctype="multipart/form-data">
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?= set_value('first_name', $member->first_name) ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?= set_value('last_name', $member->last_name) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= set_value('phone', $member->phone) ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Alternate Phone</label>
                    <input type="text" name="alternate_phone" class="form-control" value="<?= set_value('alternate_phone', $member->alternate_phone) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= set_value('email', $member->email) ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Profile Photo</label>
                    <input type="file" name="profile_photo" class="form-control-file" accept="image/*">
                    <?php if (!empty($member->photo)): ?>
                        <small class="form-text text-muted">
                            Current: <a href="<?= base_url('members/uploads/' . $member->id . '/' . $member->photo) ?>" target="_blank">View Photo</a>
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Father's Name</label>
                    <input type="text" name="father_name" class="form-control" value="<?= set_value('father_name', $member->father_name) ?>">
                </div>
                <div class="form-group col-md-3">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?= set_value('date_of_birth', $member->date_of_birth) ?>">
                </div>
                <div class="form-group col-md-3">
                    <label>Gender</label>
                    <select name="gender" class="form-control">
                        <option value="">-- Select --</option>
                        <option value="male" <?= set_value('gender', $member->gender) == 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= set_value('gender', $member->gender) == 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other" <?= set_value('gender', $member->gender) == 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Address Line 1</label>
                <textarea name="address_line1" class="form-control"><?= set_value('address_line1', $member->address_line1) ?></textarea>
            </div>
            <div class="form-group">
                <label>Address Line 2</label>
                <input type="text" name="address_line2" class="form-control" value="<?= set_value('address_line2', $member->address_line2) ?>">
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>City</label>
                    <input type="text" name="city" class="form-control" value="<?= set_value('city', $member->city) ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>State</label>
                    <input type="text" name="state" class="form-control" value="<?= set_value('state', $member->state) ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>Pincode</label>
                    <input type="text" name="pincode" class="form-control" value="<?= set_value('pincode', $member->pincode) ?>">
                </div>
            </div>

            <hr>
            <h5>Identification & Bank Details</h5>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Aadhaar Number</label>
                    <input type="text" name="aadhaar_number" data-mask="aadhaar" class="form-control" value="<?= set_value('aadhaar_number', $member->aadhaar_number) ?>">
                    <?php if (!empty($member->aadhaar_doc)): ?>
                        <small class="form-text text-muted">Document: <a href="<?= base_url('members/uploads/' . $member->id . '/' . $member->aadhaar_doc) ?>" target="_blank">View</a></small>
                    <?php endif; ?>
                </div>
                <div class="form-group col-md-4">
                    <label>PAN Number</label>
                    <input type="text" name="pan_number" data-mask="pan" class="form-control" value="<?= set_value('pan_number', $member->pan_number) ?>">
                    <?php if (!empty($member->pan_doc)): ?>
                        <small class="form-text text-muted">Document: <a href="<?= base_url('members/uploads/' . $member->id . '/' . $member->pan_doc) ?>" target="_blank">View</a></small>
                    <?php endif; ?>
                </div>
                <div class="form-group col-md-4">
                    <label>Voter ID</label>
                    <input type="text" name="voter_id" class="form-control" value="<?= set_value('voter_id', $member->voter_id) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Aadhaar Document (jpg/png/pdf)</label>
                    <input type="file" name="aadhaar_doc" class="form-control-file">
                </div>
                <div class="form-group col-md-4">
                    <label>PAN Document (jpg/png/pdf)</label>
                    <input type="file" name="pan_doc" class="form-control-file">
                </div>
                <div class="form-group col-md-4">
                    <label>Address Proof (jpg/png/pdf)</label>
                    <input type="file" name="address_proof" class="form-control-file">
                    <?php if (!empty($member->address_proof_doc)): ?>
                        <small class="form-text text-muted">Document: <a href="<?= base_url('members/uploads/' . $member->id . '/' . $member->address_proof_doc) ?>" target="_blank">View</a></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Bank Name</label>
                    <input type="text" name="bank_name" class="form-control" value="<?= set_value('bank_name', $member->bank_name) ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>Bank Branch</label>
                    <input type="text" name="bank_branch" class="form-control" value="<?= set_value('bank_branch', $member->bank_branch) ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>Account Number</label>
                    <input type="text" name="account_number" class="form-control" value="<?= set_value('account_number', $member->account_number) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>IFSC</label>
                    <input type="text" name="ifsc_code" class="form-control" value="<?= set_value('ifsc_code', $member->ifsc_code) ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Account Holder</label>
                    <input type="text" name="account_holder_name" class="form-control" value="<?= set_value('account_holder_name', $member->account_holder_name) ?>">
                </div>
            </div>

            <hr>
            <h5>Nominee</h5>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Nominee Name</label>
                    <input type="text" name="nominee_name" class="form-control" value="<?= set_value('nominee_name', $member->nominee_name) ?>">
                </div>
                <div class="form-group col-md-3">
                    <label>Relation</label>
                    <input type="text" name="nominee_relation" class="form-control" value="<?= set_value('nominee_relation', $member->nominee_relation) ?>">
                </div>
                <div class="form-group col-md-3">
                    <label>Phone</label>
                    <input type="text" name="nominee_phone" class="form-control" value="<?= set_value('nominee_phone', $member->nominee_phone) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Nominee Aadhaar</label>
                <input type="text" name="nominee_aadhaar" data-mask="aadhaar" class="form-control" value="<?= set_value('nominee_aadhaar', $member->nominee_aadhaar) ?>">
            </div>

            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control"><?= set_value('notes', $member->notes) ?></textarea>
            </div>

        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-success">Save</button>
            <a href="<?= site_url('member/profile') ?>" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>