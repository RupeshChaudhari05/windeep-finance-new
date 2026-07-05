<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMI Receipt - <?= htmlspecialchars($payment->payment_code ?? ('INS-'.$installment->id)) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; background: #f4f4f4; color: #333; }

        .receipt-wrapper { max-width: 680px; margin: 30px auto; background: #fff; border: 1px solid #ddd; border-radius: 6px; overflow: hidden; }

        .receipt-header { background: linear-gradient(135deg, #1a3c6e, #2c5f9e); color: #fff; padding: 24px 28px; display: flex; justify-content: space-between; align-items: flex-start; }
        .receipt-header .company-name { font-size: 22px; font-weight: 700; letter-spacing: 0.5px; }
        .receipt-header .company-sub { font-size: 12px; opacity: 0.85; margin-top: 3px; }
        .receipt-header .receipt-label { text-align: right; }
        .receipt-header .receipt-label h4 { font-size: 16px; font-weight: 700; border: 2px solid rgba(255,255,255,0.6); border-radius: 4px; padding: 4px 10px; display: inline-block; }
        .receipt-header .receipt-label .code { font-size: 13px; margin-top: 5px; opacity: 0.9; }

        .status-bar { background: #28a745; color: #fff; text-align: center; padding: 8px; font-size: 13px; font-weight: 600; letter-spacing: 1px; }

        .receipt-body { padding: 24px 28px; }

        .section-title { font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; margin-top: 18px; border-bottom: 1px solid #eee; padding-bottom: 5px; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 20px; }
        .info-row { display: flex; flex-direction: column; }
        .info-row .label { font-size: 11px; color: #888; }
        .info-row .value { font-size: 13px; font-weight: 600; color: #222; }

        .amount-box { background: #f0f8f0; border: 2px solid #28a745; border-radius: 8px; padding: 16px 20px; margin: 18px 0; text-align: center; }
        .amount-box .label { font-size: 12px; color: #666; margin-bottom: 4px; }
        .amount-box .amount { font-size: 32px; font-weight: 700; color: #1a5c2a; }

        .breakdown-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
        .breakdown-table th { background: #f5f5f5; padding: 8px 10px; text-align: left; font-size: 11px; color: #555; text-transform: uppercase; }
        .breakdown-table td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; }
        .breakdown-table tr:last-child td { border-bottom: none; font-weight: 700; background: #fafafa; }
        .text-right { text-align: right; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }

        .receipt-footer { background: #f9f9f9; border-top: 1px dashed #ccc; padding: 16px 28px; text-align: center; }
        .receipt-footer .thank-you { font-size: 15px; font-weight: 700; color: #1a3c6e; margin-bottom: 4px; }
        .receipt-footer small { font-size: 11px; color: #999; }

        .watermark { text-align: center; color: #28a745; font-weight: 700; font-size: 11px; letter-spacing: 2px; padding: 8px; }

        .print-btn { display: block; text-align: center; margin: 20px auto; }
        .btn-print { background: #1a3c6e; color: #fff; border: none; padding: 10px 32px; font-size: 14px; border-radius: 4px; cursor: pointer; margin-right: 8px; }
        .btn-close-win { background: #6c757d; color: #fff; border: none; padding: 10px 24px; font-size: 14px; border-radius: 4px; cursor: pointer; }

        @media print {
            body { background: #fff; }
            .print-btn { display: none !important; }
            .receipt-wrapper { border: none; box-shadow: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="print-btn no-print">
    <button class="btn-print" onclick="window.print()">&#128438; Download / Print PDF</button>
    <button class="btn-close-win" onclick="window.close()">Close</button>
</div>

<div class="receipt-wrapper">

    <!-- Header -->
    <div class="receipt-header">
        <div>
            <div class="company-name"><?= htmlspecialchars(get_company_name()) ?></div>
            <div class="company-sub">EMI Payment Receipt</div>
        </div>
        <div class="receipt-label">
            <h4>PAYMENT RECEIPT</h4>
            <div class="code"><?= htmlspecialchars($payment->payment_code ?? ('INST-'.$installment->id)) ?></div>
            <div class="code" style="margin-top:3px; font-size:11px;"><?= date('d M Y, h:i A', strtotime($payment->created_at ?? date('Y-m-d'))) ?></div>
        </div>
    </div>

    <div class="status-bar">&#10003; PAYMENT SUCCESSFUL</div>

    <div class="receipt-body">

        <!-- Amount -->
        <div class="amount-box">
            <div class="label">Amount Paid</div>
            <div class="amount"><?= get_currency_symbol() ?><?= number_format((float)($payment->total_amount ?? $installment->emi_amount), 2) ?></div>
        </div>

        <!-- Member Details -->
        <div class="section-title">Member Information</div>
        <div class="info-grid">
            <div class="info-row">
                <span class="label">Member Name</span>
                <span class="value"><?= htmlspecialchars($member->full_name ?? ($member->first_name.' '.$member->last_name)) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Member Code</span>
                <span class="value"><?= htmlspecialchars($member->member_code ?? '-') ?></span>
            </div>
            <div class="info-row" style="margin-top:8px;">
                <span class="label">Mobile</span>
                <span class="value"><?= htmlspecialchars($member->mobile ?? '-') ?></span>
            </div>
        </div>

        <!-- Loan Details -->
        <div class="section-title">Loan Details</div>
        <div class="info-grid">
            <div class="info-row">
                <span class="label">Loan Number</span>
                <span class="value"><?= htmlspecialchars($loan->loan_number ?? '-') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Loan Product</span>
                <span class="value"><?= htmlspecialchars($loan->product_name ?? '-') ?></span>
            </div>
            <div class="info-row" style="margin-top:8px;">
                <span class="label">Installment #</span>
                <span class="value"><?= $installment->installment_number ?> of <?= $loan->tenure_months ?></span>
            </div>
            <div class="info-row" style="margin-top:8px;">
                <span class="label">Due Date</span>
                <span class="value"><?= date('d M Y', strtotime($installment->due_date)) ?></span>
            </div>
        </div>

        <!-- Payment Breakdown -->
        <div class="section-title">Payment Breakdown</div>
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Component</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Principal</td>
                    <td class="text-right"><?= get_currency_symbol() ?><?= number_format((float)($payment->principal_component ?? $installment->principal_amount), 2) ?></td>
                </tr>
                <tr>
                    <td>Interest</td>
                    <td class="text-right"><?= get_currency_symbol() ?><?= number_format((float)($payment->interest_component ?? $installment->interest_amount), 2) ?></td>
                </tr>
                <?php if (!empty($payment->fine_component) && $payment->fine_component > 0): ?>
                <tr>
                    <td>Fine / Late Charge</td>
                    <td class="text-right text-danger"><?= get_currency_symbol() ?><?= number_format((float)$payment->fine_component, 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Total Paid</td>
                    <td class="text-right text-success"><?= get_currency_symbol() ?><?= number_format((float)($payment->total_amount ?? $installment->emi_amount), 2) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Payment Info -->
        <div class="section-title">Payment Information</div>
        <div class="info-grid">
            <div class="info-row">
                <span class="label">Payment Date</span>
                <span class="value"><?= !empty($payment->payment_date) ? date('d M Y', strtotime($payment->payment_date)) : date('d M Y') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Payment Mode</span>
                <span class="value"><?= ucwords(str_replace('_', ' ', $payment->payment_mode ?? 'Cash')) ?></span>
            </div>
            <?php if (!empty($payment->reference_number)): ?>
            <div class="info-row" style="margin-top:8px;">
                <span class="label">Reference / Transaction ID</span>
                <span class="value"><?= htmlspecialchars($payment->reference_number) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row" style="margin-top:8px;">
                <span class="label">Outstanding After</span>
                <span class="value text-success"><?= get_currency_symbol() ?><?= number_format((float)($payment->outstanding_principal_after ?? 0), 2) ?></span>
            </div>
        </div>

    </div>

    <div class="watermark">&#10003; PAID</div>

    <div class="receipt-footer">
        <div class="thank-you">Thank you for your payment!</div>
        <small>This is a computer-generated receipt. No signature required.</small><br>
        <small>Generated on <?= date('d M Y, h:i A') ?> &nbsp;|&nbsp; <?= htmlspecialchars(get_company_name()) ?></small>
    </div>

</div>

<div class="print-btn no-print">
    <button class="btn-print" onclick="window.print()">&#128438; Download / Print PDF</button>
    <button class="btn-close-win" onclick="window.close()">Close</button>
</div>

</body>
</html>
