<!-- Send Email to Member -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-envelope mr-1"></i>
                    <?php if (!$bulk): ?>
                        Send Email to <?= $member->first_name . ' ' . $member->last_name ?> (<?= $member->member_code ?>)
                    <?php else: ?>
                        Send Bulk Email to Members
                    <?php endif; ?>
                </h3>
            </div>
            <div class="card-body">
                <form id="emailForm">
                    <?php if ($bulk): ?>
                        <div class="form-group">
                            <label>Recipients</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="recipient_type" id="all_members" value="all" checked>
                                <label class="form-check-label" for="all_members">
                                    All members with email addresses
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="recipient_type" id="selected_members" value="selected">
                                <label class="form-check-label" for="selected_members">
                                    Selected members
                                </label>
                            </div>
                        </div>
                        <div id="memberSelector" class="form-group" style="display: none;">
                            <label>Select Members</label>
                            <select class="form-control select2" name="member_ids[]" multiple>
                                <?php
                                $members = $this->Member_model->get_members_with_email();
                                foreach ($members as $m):
                                ?>
                                    <option value="<?= $m->id ?>"><?= $m->member_code ?> - <?= $m->first_name . ' ' . $m->last_name ?> (<?= $m->email ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="member_ids[]" value="<?= $member->id ?>">
                        <div class="alert alert-info">
                            <strong>Recipient:</strong> <?= $member->email ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea class="form-control" id="message" name="message" rows="10" required></textarea>
                        <small class="form-text text-muted">HTML formatting is supported.</small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" id="sendBtn">
                            <i class="fas fa-paper-plane"></i> Send Email
                        </button>
                        <a href="<?= base_url('admin/members') ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Email Templates</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('welcome')">
                        Welcome Message
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('payment_reminder')">
                        Payment Reminder
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('account_statement')">
                        Account Statement
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('loan_approved')">
                        Loan Approved
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="loadTemplate('general_announcement')">
                        General Announcement
                    </button>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Email Settings</h5>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>From:</strong> Windeep Finance</p>
                <p class="mb-0"><strong>Reply To:</strong> noreply@windeep.com</p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2 for member selection
    $('.select2').select2({
        placeholder: 'Select members...',
        allowClear: true
    });

    // Show/hide member selector based on recipient type
    $('input[name="recipient_type"]').change(function() {
        if ($(this).val() === 'selected') {
            $('#memberSelector').show();
        } else {
            $('#memberSelector').hide();
        }
    });

    // Handle form submission
    $('#emailForm').submit(function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>');

        $('#sendBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');

        $.ajax({
            url: '<?= base_url("admin/members/process_send_email") ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Emails sent successfully!\n\nSent: ' + response.sent + '\nFailed: ' + response.failed);
                    if (response.errors && response.errors.length > 0) {
                        console.log('Errors:', response.errors);
                    }
                    window.location.href = '<?= base_url("admin/members") ?>';
                } else {
                    alert('Failed to send emails: ' + response.message);
                }
            },
            error: function() {
                alert('Error sending emails. Please try again.');
            },
            complete: function() {
                $('#sendBtn').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send Email');
            }
        });
    });
});

function loadTemplate(template) {
    var templates = {
        'welcome': {
            subject: 'Welcome to Windeep Finance',
            message: `<h2>Welcome to Windeep Finance!</h2>
<p>Dear [Member Name],</p>
<p>Welcome to our cooperative society! Your membership has been successfully activated.</p>
<p><strong>Member Details:</strong></p>
<ul>
    <li>Member Code: [Member Code]</li>
    <li>Joining Date: [Join Date]</li>
</ul>
<p>You can now access all our services including savings accounts, loans, and other financial products.</p>
<p>For any queries, please contact our support team.</p>
<p>Best regards,<br>Windeep Finance Team</p>`
        },
        'payment_reminder': {
            subject: 'Payment Due Reminder',
            message: `<h2>Payment Due Reminder</h2>
<p>Dear [Member Name],</p>
<p>This is a reminder that your payment is due.</p>
<p><strong>Payment Details:</strong></p>
<ul>
    <li>Amount Due: <?= get_currency_symbol() ?>[Amount]</li>
    <li>Due Date: [Due Date]</li>
    <li>Reference: [Reference]</li>
</ul>
<p>Please ensure timely payment to avoid any late fees.</p>
<p>You can make payments through our online portal or by visiting our office.</p>
<p>Thank you for your continued support.</p>
<p>Best regards,<br>Windeep Finance Team</p>`
        },
        'account_statement': {
            subject: 'Your Account Statement',
            message: `<h2>Account Statement</h2>
<p>Dear [Member Name],</p>
<p>Please find your account statement for the period [Period].</p>
<p>You can view your detailed transaction history by logging into your account.</p>
<p><strong>Summary:</strong></p>
<ul>
    <li>Opening Balance: <?= get_currency_symbol() ?>[Opening Balance]</li>
    <li>Total Deposits: <?= get_currency_symbol() ?>[Deposits]</li>
    <li>Total Withdrawals: <?= get_currency_symbol() ?>[Withdrawals]</li>
    <li>Closing Balance: <?= get_currency_symbol() ?>[Closing Balance]</li>
</ul>
<p>If you have any questions about your statement, please contact us.</p>
<p>Best regards,<br>Windeep Finance Team</p>`
        },
        'loan_approved': {
            subject: 'Loan Application Approved',
            message: `<h2>Congratulations! Your Loan is Approved</h2>
<p>Dear [Member Name],</p>
<p>Great news! Your loan application has been approved.</p>
<p><strong>Loan Details:</strong></p>
<ul>
    <li>Loan Number: [Loan Number]</li>
    <li>Approved Amount: <?= get_currency_symbol() ?>[Amount]</li>
    <li>Interest Rate: [Rate]%</li>
    <li>Tenure: [Tenure] months</li>
    <li>EMI Amount: <?= get_currency_symbol() ?>[EMI]</li>
</ul>
<p>Your loan will be disbursed within 2-3 business days after completing the necessary documentation.</p>
<p>Please contact our loan officer for next steps.</p>
<p>Best regards,<br>Windeep Finance Team</p>`
        },
        'general_announcement': {
            subject: 'Important Announcement',
            message: `<h2>Important Announcement</h2>
<p>Dear Members,</p>
<p>We have an important update for you.</p>
<p>[Your announcement content here]</p>
<p>For more information, please contact our office or visit our website.</p>
<p>Thank you for your attention.</p>
<p>Best regards,<br>Windeep Finance Management</p>`
        }
    };

    if (templates[template]) {
        $('#subject').val(templates[template].subject);
        $('#message').val(templates[template].message);
    }
}
</script>