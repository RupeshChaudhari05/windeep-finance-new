<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Payment Receipt - <?= htmlspecialchars($payment->payment_code ?? $payment->transaction_code ?? $payment->receipt_number ?? $payment->id) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= base_url('assets/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/dist/css/adminlte.min.css') ?>">
    <style>
        body { font-family: 'Arial', sans-serif; background: #f4f6f9; }
        .receipt-container { max-width: 800px; margin: 20px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .receipt-header { border-bottom: 3px solid #007bff; padding-bottom: 20px; margin-bottom: 30px; }
        .receipt-title { font-size: 28px; font-weight: bold; color: #007bff; text-align: center; margin-bottom: 10px; }
        .receipt-code { text-align: center; font-size: 14px; color: #666; }
        .info-label { font-weight: bold; color: #333; display: inline-block; min-width: 150px; }
        .info-value { color: #666; }
        .amount-box { background: #f8f9fa; border-left: 4px solid #28a745; padding: 20px; margin: 20px 0; }
        .amount-box .total-amount { font-size: 32px; font-weight: bold; color: #28a745; }
        .receipt-footer { margin-top: 40px; padding-top: 20px; border-top: 1px dashed #ccc; text-align: center; color: #999; font-size: 12px; }
        @media print { body { background: white; } .receipt-container { box-shadow: none; margin: 0; padding: 20px; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="text-right mb-3 no-print">
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print mr-2"></i>Print Receipt</button>
            <button onclick="window.close()" class="btn btn-secondary ml-2"><i class="fas fa-times mr-2"></i>Close</button>
        </div>

        <div class="receipt-header">
            <div class="receipt-title"><i class="fas fa-receipt mr-2"></i>PAYMENT RECEIPT</div>
            <div class="receipt-code">Receipt No: <strong><?= htmlspecialchars($payment->payment_code ?? $payment->transaction_code ?? $payment->receipt_number ?? $payment->id) ?></strong></div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-3"><i class="fas fa-user mr-2"></i>Member Details</h5>
                <p><span class="info-label">Name:</span> <span class="info-value"><?= htmlspecialchars(($payment->first_name ?? '') . ' ' . ($payment->last_name ?? '')) ?></span></p>
                <p><span class="info-label">Member Code:</span> <span class="info-value"><?= htmlspecialchars($payment->member_code ?? '') ?></span></p>
                <p><span class="info-label">Phone:</span> <span class="info-value"><?= htmlspecialchars($payment->phone ?? '') ?></span></p>
                <?php if (!empty($payment->address)): ?><p><span class="info-label">Address:</span> <span class="info-value"><?= htmlspecialchars($payment->address) ?></span></p><?php endif; ?>
            </div>
            <div class="col-md-6">
                <h5 class="mb-3"><i class="fas fa-file-contract mr-2"></i>Payment Details</h5>
                <?php if ($type === 'loan' || isset($payment->loan_number)): ?>
                    <p><span class="info-label">Loan Number:</span> <span class="info-value"><?= htmlspecialchars($payment->loan_number ?? '') ?></span></p>
                <?php elseif ($type === 'savings' || isset($payment->account_number)): ?>
                    <p><span class="info-label">Account Number:</span> <span class="info-value"><?= htmlspecialchars($payment->account_number ?? '') ?></span></p>
                <?php elseif ($type === 'fine' || isset($payment->fine_code)): ?>
                    <p><span class="info-label">Fine Code:</span> <span class="info-value"><?= htmlspecialchars($payment->fine_code ?? '') ?></span></p>
                <?php endif; ?>

                <p><span class="info-label">Payment Date:</span> <span class="info-value"><?= htmlspecialchars(date('d/m/Y', strtotime($payment->payment_date ?? $payment->created_at ?? date('Y-m-d')))) ?></span></p>
                <p><span class="info-label">Payment Mode:</span> <span class="info-value"><?= htmlspecialchars($payment->payment_mode ?? ($payment->payment_type ?? '')) ?></span></p>
            </div>
        </div>

        <div class="amount-box">
            <div class="text-center">
                <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Total Amount Paid</div>
                <div class="total-amount">₹<?= number_format($payment->total_amount ?? $payment->amount ?? 0, 2) ?></div>
            </div>
        </div>

        <div class="receipt-footer">This is a system generated receipt. For queries, contact support.</div>
    </div>
</body>
</html>