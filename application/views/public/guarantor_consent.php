<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guarantor Consent - Windeep Finance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .consent-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .consent-card .logo-area {
            text-align: center;
            margin-bottom: 24px;
        }
        .consent-card .logo-area i {
            font-size: 48px;
            color: #667eea;
        }
        .consent-card h2 {
            color: #333;
            font-weight: 700;
            text-align: center;
            margin-bottom: 6px;
        }
        .consent-card .subtitle {
            color: #888;
            text-align: center;
            margin-bottom: 28px;
            font-size: 14px;
        }
        .detail-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .detail-box .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        .detail-box .detail-row:last-child {
            border-bottom: none;
        }
        .detail-box .detail-label {
            color: #666;
            font-weight: 500;
        }
        .detail-box .detail-value {
            color: #333;
            font-weight: 600;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 10px 14px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.2);
        }
        .btn-accept {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            margin-bottom: 10px;
        }
        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(40,167,69,0.4);
            color: white;
        }
        .btn-reject {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
        }
        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(220,53,69,0.4);
            color: white;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 18px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        .status-accepted { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .amount-highlight {
            font-size: 22px;
            font-weight: 700;
            color: #667eea;
        }
    </style>
</head>
<body>
<div class="consent-card">
    <div class="logo-area">
        <i class="fas fa-handshake"></i>
    </div>
    <h2>Guarantor Consent</h2>
    <p class="subtitle">Windeep Finance &mdash; Loan Guarantee Request</p>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success text-center" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
        </div>
        <p class="text-center text-muted mt-3" style="font-size:13px;">You may close this window.</p>
    <?php elseif (!empty($error_message)): ?>
        <div class="alert alert-danger text-center" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
        </div>
    <?php elseif ($already_responded): ?>
        <div class="text-center mb-3">
            <p class="text-muted mb-2">Your response has already been recorded:</p>
            <span class="status-badge status-<?= htmlspecialchars($guarantor->consent_status) ?>">
                <?= ucfirst(htmlspecialchars($guarantor->consent_status)) ?>
            </span>
        </div>
    <?php else: ?>
        <div class="detail-box">
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-file-alt me-1"></i> Application No.</span>
                <span class="detail-value"><?= htmlspecialchars($application->application_number) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-user me-1"></i> Applicant</span>
                <span class="detail-value"><?= htmlspecialchars($application->first_name . ' ' . $application->last_name) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-rupee-sign me-1"></i> Loan Amount</span>
                <span class="detail-value">&#8377;<?= number_format($application->amount_requested, 2) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-shield-alt me-1"></i> Guarantee Amount</span>
                <span class="detail-value amount-highlight">&#8377;<?= number_format($guarantor->guarantee_amount, 2) ?></span>
            </div>
        </div>

        <p class="text-muted mb-3" style="font-size:13px; text-align:center;">
            Please review the details above and accept or reject this guarantor request.
        </p>

        <form method="post" id="consentForm">
            <div class="mb-3">
                <label class="form-label fw-semibold">Remarks <span class="text-muted fw-normal">(optional)</span></label>
                <textarea name="remarks" class="form-control" rows="3" placeholder="Add any remarks if needed..."></textarea>
            </div>
            <button type="button" class="btn btn-accept" onclick="submitConsent('accept')">
                <i class="fas fa-check me-2"></i>Accept Guarantee Request
            </button>
            <button type="button" class="btn btn-reject mt-2" onclick="submitConsent('reject')">
                <i class="fas fa-times me-2"></i>Reject Guarantee Request
            </button>
            <input type="hidden" name="action" id="consent_action" value="">
        </form>
    <?php endif; ?>

    <p class="text-center mt-4 mb-0" style="font-size:12px; color:#aaa;">
        &copy; <?= date('Y') ?> Windeep Finance. Secure &amp; Confidential.
    </p>
</div>

<script>
function submitConsent(action) {
    var label = action === 'accept' ? 'Accept' : 'Reject';
    if (!confirm('Are you sure you want to ' + label + ' this guarantor request?')) return;
    document.getElementById('consent_action').value = action;
    document.getElementById('consentForm').submit();
}
</script>
</body>
</html>
