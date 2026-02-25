<!-- View Loan Application -->
<div class="row">
    <div class="col-md-8">
        <!-- Application Details -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> Application #<?= $application->application_number ?></h3>
                <div class="card-tools">
                    <span class="badge badge-<?= 
                        $application->status == 'pending' ? 'warning' : 
                        ($application->status == 'approved' || $application->status == 'member_approved' ? 'success' : 
                        ($application->status == 'rejected' ? 'danger' : 'info')) 
                    ?> badge-lg">
                        <?= strtoupper(str_replace('_', ' ', $application->status)) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Loan Request</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="text-muted">Loan Product:</td>
                                <td><strong><?= $application->product_name ?? '<span class="text-muted">Not yet assigned</span>' ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Requested Amount:</td>
                                <td class="text-primary font-weight-bold">₹<?= number_format($application->requested_amount) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Requested Tenure:</td>
                                <td><?= $application->requested_tenure_months ?> months</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Purpose:</td>
                                <td><?= $application->purpose ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Application Date:</td>
                                <td><?= format_date_time($application->application_date, 'd M Y h:i A') ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <?php if ($application->status == 'approved' || $application->status == 'member_approved'): ?>
                        <h6 class="text-muted">Approved Terms</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="text-muted">Approved Amount:</td>
                                <td class="text-success font-weight-bold">₹<?= number_format($application->approved_amount) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Approved Tenure:</td>
                                <td><?= $application->approved_tenure_months ?> months</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Interest Rate:</td>
                                <td><?= $application->approved_interest_rate ?>% p.a.</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Approval Date:</td>
                                <td><?= format_date($application->admin_approved_at, 'd M Y') ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Approved By:</td>
                                <td><?= $application->approver_name ?? '-' ?></td>
                            </tr>
                        </table>
                        <?php elseif ($application->status == 'rejected'): ?>
                        <h6 class="text-danger">Rejection Details</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="text-muted">Rejection Date:</td>
                                <td><?= format_date($application->rejected_at ?? $application->updated_at) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Reason:</td>
                                <td class="text-danger"><?= $application->rejection_reason ?: 'Not specified' ?></td>
                            </tr>
                        </table>
                        <?php else: ?>
                        <h6 class="text-muted">Status Information</h6>
                        <div class="card card-warning">
                            <div class="card-body py-2">
                                <i class="fas fa-clock mr-1"></i> This application is pending review.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($application->remarks)): ?>
                <hr>
                <h6 class="text-muted">Applicant Remarks</h6>
                <p class="mb-0"><?= nl2br($application->remarks) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Member Details -->
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title flex-grow-1"><i class="fas fa-user mr-1"></i> Member Details</h3>
                <a href="<?= site_url('admin/members/edit/' . $member->id) ?>" class="btn btn-sm btn-warning ml-2">
                    <i class="fas fa-edit mr-1"></i> Edit Member
                </a>
                <a href="<?= site_url('admin/members/view/' . $member->id) ?>" class="btn btn-sm btn-outline-info ml-1">
                    <i class="fas fa-external-link-alt mr-1"></i> Full Profile
                </a>
            </div>
            <div class="card-body">
                <?php
                    // Member level / membership type badge helper
                    $ml = $member->member_level ?? $member->membership_type ?? '';
                    $mt = $member->membership_type ?? '';
                    $ml_labels = ['founding_member'=>'Founding Member','level2'=>'Level 2','level3'=>'Level 3'];
                    $mt_labels = ['founder'=>'Founder','premium'=>'Premium','regular'=>'Regular'];
                    $ml_colors = ['founding_member'=>'warning','level2'=>'info','level3'=>'secondary'];
                    $mt_colors = ['founder'=>'warning','premium'=>'primary','regular'=>'secondary'];
                ?>
                <!-- Top row: photo + identity -->
                <div class="row mb-2">
                    <div class="col-md-2 text-center">
                        <?php
                            $photo = $member->profile_photo ?? $member->photo ?? '';
                        ?>
                        <?php if (!empty($photo)): ?>
                        <img src="<?= base_url('uploads/profile_images/' . $photo) ?>" class="img-circle img-thumbnail" style="width: 90px; height: 90px; object-fit: cover;">
                        <?php else: ?>
                        <div class="img-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" style="width: 90px; height: 90px;">
                            <i class="fas fa-user fa-3x text-white"></i>
                        </div>
                        <?php endif; ?>
                        <div class="mt-1">
                            <span class="badge badge-<?= ($member->status ?? 'inactive') == 'active' ? 'success' : 'danger' ?>"><?= ucfirst($member->status ?? 'inactive') ?></span>
                        </div>
                        <?php if (!empty($ml)): ?>
                        <div class="mt-1">
                            <span class="badge badge-<?= $ml_colors[$ml] ?? 'secondary' ?>">
                                <i class="fas fa-star mr-1"></i><?= $ml_labels[$ml] ?? ucfirst(str_replace('_',' ',$ml)) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($mt) && $mt !== 'regular'): ?>
                        <div class="mt-1">
                            <span class="badge badge-<?= $mt_colors[$mt] ?? 'secondary' ?>">
                                <?= $mt_labels[$mt] ?? ucfirst($mt) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($member->kyc_verified)): ?>
                        <div class="mt-1"><span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>KYC Verified</span></div>
                        <?php else: ?>
                        <div class="mt-1"><span class="badge badge-secondary">KYC Pending</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-5">
                        <h6 class="text-muted border-bottom pb-1 mb-2"><i class="fas fa-id-card mr-1"></i> Identity</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr><td class="text-muted" style="width:45%">Member Code:</td><td><strong><?= $member->member_code ?></strong></td></tr>
                            <tr><td class="text-muted">Full Name:</td><td><strong><?= trim(($member->first_name ?? '') . ' ' . ($member->middle_name ?? '') . ' ' . ($member->last_name ?? '')) ?></strong></td></tr>
                            <tr><td class="text-muted">Father's Name:</td><td><?= $member->father_name ?: '<span class="text-muted">—</span>' ?></td></tr>
                            <tr><td class="text-muted">Date of Birth:</td><td><?= !empty($member->date_of_birth) ? date('d M Y', strtotime($member->date_of_birth)) : '—' ?></td></tr>
                            <tr><td class="text-muted">Gender:</td><td><?= !empty($member->gender) ? ucfirst($member->gender) : '—' ?></td></tr>
                            <tr><td class="text-muted">Join Date:</td><td><?= !empty($member->join_date) ? date('d M Y', strtotime($member->join_date)) : '—' ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-5">
                        <h6 class="text-muted border-bottom pb-1 mb-2"><i class="fas fa-phone mr-1"></i> Contact</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr><td class="text-muted" style="width:45%">Phone:</td><td><a href="tel:<?= $member->phone ?>"><?= $member->phone ?></a></td></tr>
                            <tr><td class="text-muted">Alt. Phone:</td><td><?= !empty($member->alternate_phone) ? '<a href="tel:'.$member->alternate_phone.'">'.$member->alternate_phone.'</a>' : '—' ?></td></tr>
                            <tr><td class="text-muted">Email:</td><td><?= !empty($member->email) ? '<a href="mailto:'.$member->email.'">'.$member->email.'</a>' : '—' ?></td></tr>
                            <tr><td class="text-muted">City / State:</td><td><?= implode(', ', array_filter([$member->city ?? '', $member->state ?? ''])) ?: '—' ?></td></tr>
                            <tr><td class="text-muted">PIN Code:</td><td><?= $member->pincode ?: '—' ?></td></tr>
                            <tr><td class="text-muted">Address:</td><td><?= implode(', ', array_filter([$member->address_line1 ?? '', $member->address_line2 ?? ''])) ?: '—' ?></td></tr>
                        </table>
                    </div>
                </div>

                <div class="row mt-2">
                    <!-- Financial -->
                    <div class="col-md-4">
                        <h6 class="text-muted border-bottom pb-1 mb-2"><i class="fas fa-chart-bar mr-1"></i> Financial Snapshot</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr><td class="text-muted" style="width:55%">Savings Balance:</td><td class="text-success font-weight-bold">₹<?= number_format($member->savings_summary->current_balance ?? 0, 2) ?></td></tr>
                            <tr><td class="text-muted">Active Loans:</td><td><?= $member->loan_summary->total_loans ?? 0 ?></td></tr>
                            <tr><td class="text-muted">Outstanding:</td><td class="text-danger">₹<?= number_format($member->loan_summary->outstanding_principal ?? 0, 2) ?></td></tr>
                            <tr><td class="text-muted">Occupation:</td><td><?= $member->occupation ?: '—' ?></td></tr>
                            <tr><td class="text-muted">Monthly Income:</td><td><?= !empty($member->monthly_income) ? '₹'.number_format($member->monthly_income, 2) : '—' ?></td></tr>
                        </table>
                    </div>
                    <!-- ID Proof -->
                    <div class="col-md-4">
                        <h6 class="text-muted border-bottom pb-1 mb-2"><i class="fas fa-fingerprint mr-1"></i> ID Proof</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <?php
                                $aadhaar = $member->aadhaar_number ?? '';
                                $pan     = $member->pan_number ?? '';
                                $masked_aadhaar = !empty($aadhaar) ? 'XXXX-XXXX-' . substr($aadhaar, -4) : '—';
                                $masked_pan     = !empty($pan)     ? substr($pan,0,3).'XXXXX'.substr($pan,-2) : '—';
                            ?>
                            <tr><td class="text-muted" style="width:50%">Aadhaar:</td><td><?= $masked_aadhaar ?></td></tr>
                            <tr><td class="text-muted">PAN:</td><td><?= $masked_pan ?></td></tr>
                            <tr><td class="text-muted">Voter ID:</td><td><?= !empty($member->voter_id) ? $member->voter_id : '—' ?></td></tr>
                            <tr><td class="text-muted">Max Guarantee:</td><td>₹<?= number_format($member->max_guarantee_amount ?? 100000, 0) ?></td></tr>
                            <tr><td class="text-muted">Max Guarantee#:</td><td><?= $member->max_guarantee_count ?? 3 ?></td></tr>
                        </table>
                    </div>
                    <!-- Bank & Nominee -->
                    <div class="col-md-4">
                        <h6 class="text-muted border-bottom pb-1 mb-2"><i class="fas fa-university mr-1"></i> Bank</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr><td class="text-muted" style="width:45%">Bank:</td><td><?= $member->bank_name ?: '—' ?></td></tr>
                            <tr><td class="text-muted">Account:</td><td><?= !empty($member->account_number) ? 'XXXX' . substr($member->account_number, -4) : '—' ?></td></tr>
                            <tr><td class="text-muted">IFSC:</td><td><?= $member->ifsc_code ?: '—' ?></td></tr>
                        </table>
                        <h6 class="text-muted border-bottom pb-1 mb-2 mt-2"><i class="fas fa-user-shield mr-1"></i> Nominee</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr><td class="text-muted" style="width:45%">Name:</td><td><?= $member->nominee_name ?: '—' ?></td></tr>
                            <tr><td class="text-muted">Relation:</td><td><?= !empty($member->nominee_relation) ? ucfirst($member->nominee_relation) : '—' ?></td></tr>
                            <tr><td class="text-muted">Phone:</td><td><?= $member->nominee_phone ?: '—' ?></td></tr>
                        </table>
                    </div>
                </div>
                <?php if (!empty($member->notes)): ?>
                <div class="card mt-2 mb-0"><div class="card-body py-2"><strong>Notes:</strong> <?= nl2br(htmlspecialchars($member->notes)) ?></div></div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Guarantors -->
        <?php if (!empty($guarantors)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-1"></i> Guarantors</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Guarantor</th>
                            <th>Phone</th>
                            <th class="text-right">Guarantee Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($guarantors as $guarantor): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td>
                                <?= $guarantor->member_code ?><br>
                                <small><?= $guarantor->first_name ?> <?= $guarantor->last_name ?></small>
                            </td>
                            <td><?= $guarantor->phone ?></td>
                            <td class="text-right">₹<?= number_format($guarantor->guarantee_amount) ?></td>
                            <td>
                                <span class="badge badge-<?= $guarantor->consent_status == 'accepted' ? 'success' : ($guarantor->consent_status == 'rejected' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($guarantor->consent_status ?? 'pending') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cogs mr-1"></i> Actions</h3>
            </div>
            <div class="card-body">
                <?php if ($application->status == 'pending' || $application->status == 'under_review' || $application->status == 'needs_revision'): ?>
                <a href="<?= site_url('admin/loans/approve/' . $application->id) ?>" class="btn btn-success btn-block mb-2">
                    <i class="fas fa-check mr-1"></i> Review & Approve
                </a>
                <button class="btn btn-warning btn-block mb-2" data-toggle="modal" data-target="#modifyModal">
                    <i class="fas fa-reply mr-1"></i> Request Modification
                </button>
                <button class="btn btn-danger btn-block mb-2" data-toggle="modal" data-target="#rejectModal">
                    <i class="fas fa-times mr-1"></i> Reject Application
                </button>
                <?php elseif ($application->status == 'guarantor_pending'): ?>
                <div class="card card-warning mb-2">
                    <div class="card-body py-2">
                        <i class="fas fa-user-friends mr-1"></i> <strong>Waiting for guarantors</strong><br>
                        <small>Guarantors have been notified. Once they accept, you can approve.</small>
                    </div>
                </div>
                <a href="<?= site_url('admin/loans/approve/' . $application->id) ?>" class="btn btn-success btn-block mb-2">
                    <i class="fas fa-check mr-1"></i> Review & Approve
                </a>
                <button class="btn btn-danger btn-block mb-2" data-toggle="modal" data-target="#rejectModal">
                    <i class="fas fa-times mr-1"></i> Reject Application
                </button>
                <?php elseif ($application->status == 'member_review'): ?>
                <div class="card card-info mb-2">
                    <div class="card-body py-2">
                        <i class="fas fa-clock mr-1"></i> <strong>Awaiting Member Acceptance</strong><br>
                        <small>The member has been notified to review and accept the approved loan terms.</small>
                    </div>
                </div>
                <?php elseif ($application->status == 'member_approved'): ?>
                <a href="<?= site_url('admin/loans/disburse/' . $application->id) ?>" class="btn btn-primary btn-block mb-2">
                    <i class="fas fa-paper-plane mr-1"></i> Proceed to Disburse
                </a>
                <?php elseif ($application->status == 'disbursed'): ?>
                <a href="<?= site_url('admin/loans/view/' . $application->loan_id) ?>" class="btn btn-info btn-block mb-2">
                    <i class="fas fa-eye mr-1"></i> View Loan Details
                </a>
                <?php endif; ?>
                
                <a href="<?= site_url('admin/members/view/' . $application->member_id) ?>" class="btn btn-outline-secondary btn-block mb-2">
                    <i class="fas fa-user mr-1"></i> View Member Profile
                </a>
                
                <a href="<?= site_url('admin/loans/applications') ?>" class="btn btn-default btn-block">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Applications
                </a>
            </div>
        </div>
        
        <!-- Timeline -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-1"></i> Application Timeline</h3>
            </div>
            <div class="card-body p-0">
                <div class="timeline timeline-sm px-3 py-2">
                    <div class="timeline-item">
                        <span class="timeline-point bg-info"></span>
                        <div class="timeline-event">
                            <strong>Application Submitted</strong>
                            <small class="text-muted d-block"><?= format_date_time($application->application_date, 'd M Y h:i A') ?></small>
                        </div>
                    </div>
                    
                    <?php if ($application->admin_approved_at): ?>
                    <div class="timeline-item">
                        <span class="timeline-point bg-success"></span>
                        <div class="timeline-event">
                            <strong>Admin Approved</strong>
                            <small class="text-muted d-block"><?= format_date_time($application->admin_approved_at, 'd M Y h:i A') ?></small>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($application->status == 'needs_revision'): ?>
                    <div class="timeline-item">
                        <span class="timeline-point bg-info"></span>
                        <div class="timeline-event">
                            <strong>Modification Requested</strong>
                            <small class="text-muted d-block"><?= nl2br(htmlspecialchars($application->revision_remarks)) ?></small>
                        </div>
                    </div>
                <?php endif; ?>
                    
                    <?php if ($application->status == 'member_approved'): ?>
                    <div class="timeline-item">
                        <span class="timeline-point bg-primary"></span>
                        <div class="timeline-event">
                            <strong>Member Confirmed</strong>
                            <small class="text-muted d-block">Ready for disbursement</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($application->status == 'rejected'): ?>
                    <div class="timeline-item">
                        <span class="timeline-point bg-danger"></span>
                        <div class="timeline-event">
                            <strong>Application Rejected</strong>
                            <small class="text-muted d-block"><?= format_date_time($application->updated_at, 'd M Y h:i A') ?></small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-times mr-1"></i> Reject Application</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Rejection Reason <span class="text-danger">*</span></label>
                    <textarea id="reject_reason" class="form-control" rows="3" placeholder="Enter detailed reason for rejection" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmReject">
                    <i class="fas fa-times mr-1"></i> Reject Application
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Modification Modal -->
<div class="modal fade" id="modifyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Request Modification</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Remarks (required)</label>
                    <textarea id="mod_remarks" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Proposed Amount (optional)</label>
                        <input type="number" id="mod_amount" class="form-control" step="0.01">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Proposed Tenure (months)</label>
                        <input type="number" id="mod_tenure" class="form-control">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Proposed Interest (%)</label>
                        <input type="number" id="mod_interest" class="form-control" step="0.01">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" id="confirmModify" class="btn btn-warning">Send to Member</button>
            </div>
        </div>
    </div>
</div>


<style>
.timeline-sm { position: relative; padding-left: 25px; }
.timeline-item { position: relative; padding-bottom: 15px; }
.timeline-item:last-child { padding-bottom: 0; }
.timeline-point { position: absolute; left: -25px; width: 12px; height: 12px; border-radius: 50%; }
.timeline-event { padding-left: 10px; }
.timeline-sm::before { content: ''; position: absolute; left: -20px; top: 6px; bottom: 6px; width: 2px; background: #dee2e6; }
</style>
