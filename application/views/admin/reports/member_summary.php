<!-- Member Summary Report -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="fas fa-users mr-1"></i> Member Summary Report</h3>
        <div>
            <button onclick="sendReportEmail('member-summary')" class="btn btn-primary btn-sm mr-2">
                <i class="fas fa-envelope"></i> Email Report
            </button>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="memberSummaryTable">
                <thead class="thead-dark">
                    <tr>
                        <th>Member Code</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Savings A/C</th>
                        <th>Total Savings</th>
                        <th>Total Loans</th>
                        <th>Outstanding</th>
                        <th>Pending Fines</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report ?? [] as $member): ?>
                    <tr>
                        <td><?= $member->member_code ?></td>
                        <td><?= $member->first_name . ' ' . $member->last_name ?></td>
                        <td><?= $member->phone ?></td>
                        <td class="text-center"><?= $member->savings_accounts ?? 0 ?></td>
                        <td class="text-right"><?= format_amount($member->total_savings ?? 0, 0) ?></td>
                        <td class="text-center"><?= $member->total_loans ?? 0 ?></td>
                        <td class="text-right"><?= format_amount($member->outstanding_loans ?? 0, 0) ?></td>
                        <td class="text-right"><?= format_amount($member->pending_fines ?? 0, 0) ?></td>
                        <td>
                            <span class="badge badge-<?= $member->status == 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($member->status) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#memberSummaryTable').DataTable({
        "pageLength": 25,
        "order": [[0, "asc"]]
    });
});

function sendReportEmail(reportType) {
    // Show email modal or prompt for recipients
    var recipients = prompt("Enter recipient email addresses (comma-separated):");
    if (!recipients) return;

    var additionalMessage = prompt("Additional message (optional):");

    // Show loading
    var btn = $('button[onclick*="sendReportEmail"]');
    var originalText = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin"></i> Sending...').prop('disabled', true);

    // Send AJAX request
    $.ajax({
        url: '<?= base_url("admin/reports/send_email") ?>',
        type: 'POST',
        data: {
            report_type: reportType,
            recipients: recipients,
            additional_message: additionalMessage,
            '<?= $this->security->get_csrf_token_name() ?>': '<?= $this->security->get_csrf_hash() ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('Report sent successfully!');
            } else {
                alert('Failed to send report: ' + response.message);
            }
        },
        error: function() {
            alert('Error sending report. Please try again.');
        },
        complete: function() {
            btn.html(originalText).prop('disabled', false);
        }
    });
}
</script>