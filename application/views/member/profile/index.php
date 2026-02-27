<div class="card">
    <div class="card-header">
        <h3 class="card-title">Profile Information</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($member->photo)): ?>
            <div class="text-center mb-3">
                <img src="<?= base_url('members/uploads/' . $member->id . '/' . $member->photo) ?>" alt="Profile Photo" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
            </div>
        <?php endif; ?>

        <p><strong>Member Code:</strong> <?= $member->member_code ?></p>
        <p><strong>Name:</strong> <?= htmlspecialchars($member->first_name . ' ' . $member->last_name) ?></p>
        <p><strong>Father's Name:</strong> <?= htmlspecialchars($member->father_name ?: '-') ?></p>
        <p><strong>Date of Birth:</strong> <?= $member->date_of_birth ? format_date($member->date_of_birth) : '-' ?></p>
        <p><strong>Gender:</strong> <?= ucfirst($member->gender ?: '-') ?></p>
        <p><strong>Phone:</strong> <?= $member->phone ?><?php if ($member->alternate_phone): ?> / <?= $member->alternate_phone ?><?php endif; ?></p>
        <p><strong>Email:</strong> <?= $member->email ?: '-' ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars(($member->address_line1 ?? '') . ' ' . ($member->address_line2 ?? '')) ?><br>
           <?= htmlspecialchars(($member->city ?? '') . ', ' . ($member->state ?? '') . ' - ' . ($member->pincode ?? '')) ?></p>
        <p><strong>Aadhaar:</strong> <?= function_exists('mask_aadhaar') ? mask_aadhaar($member->aadhaar_number) : ($member->aadhaar_number ?: '-') ?>
            <?php if (!empty($member->aadhaar_doc)): ?>
                &nbsp; <a href="<?= base_url('members/uploads/' . $member->id . '/' . $member->aadhaar_doc) ?>" target="_blank">(Document)</a>
            <?php endif; ?>
        </p>
        <p><strong>PAN:</strong> <?= function_exists('mask_pan') ? mask_pan($member->pan_number) : ($member->pan_number ?: '-') ?>
            <?php if (!empty($member->pan_doc)): ?>
                &nbsp; <a href="<?= base_url('members/uploads/' . $member->id . '/' . $member->pan_doc) ?>" target="_blank">(Document)</a>
            <?php endif; ?>
        </p>
        <p><strong>Voter ID:</strong> <?= $member->voter_id ?: '-' ?></p>

        <?php if (!empty($member->bank_name)): ?>
        <p><strong>Bank Details:</strong><br>
           <?= htmlspecialchars($member->bank_name) ?> - <?= htmlspecialchars($member->bank_branch) ?><br>
           Account: <?= htmlspecialchars($member->account_number) ?><br>
           IFSC: <?= htmlspecialchars($member->ifsc_code) ?><br>
           Holder: <?= htmlspecialchars($member->account_holder_name) ?>
        </p>
        <?php endif; ?>

        <?php if (!empty($member->nominee_name)): ?>
        <p><strong>Nominee:</strong><br>
           Name: <?= htmlspecialchars($member->nominee_name) ?><br>
           Relation: <?= htmlspecialchars($member->nominee_relation) ?><br>
           Phone: <?= $member->nominee_phone ?><br>
           Aadhaar: <?= function_exists('mask_aadhaar') ? mask_aadhaar($member->nominee_aadhaar) : ($member->nominee_aadhaar ?: '-') ?>
        </p>
        <?php endif; ?>

        <?php if (!empty($member->notes)): ?>
        <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($member->notes)) ?></p>
        <?php endif; ?>

        <a href="<?= site_url('member/profile/edit') ?>" class="btn btn-primary">Edit Profile</a>
        <a href="<?= site_url('member/profile/change_password') ?>" class="btn btn-secondary">Change Password</a>
    </div>
</div>