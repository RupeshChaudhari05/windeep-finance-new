<div class="card">
    <div class="card-header">
        <h3 class="card-title">Profile Information</h3>
    </div>
    <div class="card-body">
        <p><strong>Member Code:</strong> <?= $member->member_code ?></p>
        <p><strong>Name:</strong> <?= htmlspecialchars($member->first_name . ' ' . $member->last_name) ?></p>
        <p><strong>Phone:</strong> <?= $member->phone ?></p>
        <p><strong>Email:</strong> <?= $member->email ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars(($member->address_line1 ?? '') . ' ' . ($member->address_line2 ?? '')) ?></p>
        <p><strong>Aadhaar:</strong> <?= function_exists('mask_aadhaar') ? mask_aadhaar($member->aadhaar_number) : ($member->aadhaar_number ?: '-') ?>
            <?php if (!empty($member->aadhaar_doc)): ?>
                &nbsp; <a href="<?= base_url('uploads/members_docs/' . $member->id . '/' . $member->aadhaar_doc) ?>" target="_blank">(Document)</a>
            <?php endif; ?>
        </p>
        <p><strong>PAN:</strong> <?= function_exists('mask_pan') ? mask_pan($member->pan_number) : ($member->pan_number ?: '-') ?>
            <?php if (!empty($member->pan_doc)): ?>
                &nbsp; <a href="<?= base_url('uploads/members_docs/' . $member->id . '/' . $member->pan_doc) ?>" target="_blank">(Document)</a>
            <?php endif; ?>
        </p>
        <a href="<?= site_url('member/profile/edit') ?>" class="btn btn-primary">Edit Profile</a>
        <a href="<?= site_url('member/profile/change_password') ?>" class="btn btn-secondary">Change Password</a>
    </div>
</div>