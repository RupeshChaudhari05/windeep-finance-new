<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savings Receipt - <?= htmlspecialchars($transaction->transaction_code ?? 'TXN') ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; background: #f4f4f4; color: #333; }

        .receipt-wrapper { max-width: 680px; margin: 30px auto; background: #fff; border: 1px solid #ddd; border-radius: 6px; overflow: hidden; }

        .receipt-header { background: linear-gradient(135deg, #145a32, #1e8449); color: #fff; padding: 24px 28px; display: flex; justify-content: space-between; align-items: flex-start; }
        .receipt-header .company-name { font-size: 22px; font-weight: 700; letter-spacing: 0.5px; }
        .receipt-header .company-sub { font-size: 12px; opacity: 0.85; margin-top: 3px; }
        .receipt-header .receipt-label { text-align: right; }
        .receipt-header .receipt-label h4 { font-size: 16px; font-weight: 700; border: 2px solid rgba(255,255,255,0.6); border-radius: 4px; padding: 4px 10px; display: inline-block; }
        .receipt-header .receipt-label .code { font-size: 13px; margin-top: 5px; opacity: 0.9; }

        <?php
        $txn_type = $transaction->transaction_type ?? 'deposit';
        $is_credit = in_array($txn_type, ['deposit', 'interest', 'interest_credit']);
        $status_color = $is_credit ? '#28a745' : '#dc3545';
        $status_label = $is_credit ? '✓ DEPOSIT CONFIRMED' : '✓ TRANSACTION CONFIRMED';
        ?>
        .status-bar { background: <?= $status_color ?>; color: #fff; text-align: center; padding: 8px; font-size: 13px; font-weight: 600; letter-spacing: 1px; }

        .receipt-body { padding: 24px 28px; }

        .section-title { font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; margin-top: 18px; border-bottom: 1px solid #eee; padding-bottom: 5px; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 20px; }
        .info-row { display: flex; flex-direction: column; }
        .info-row .label { font-size: 11px; color: #888; }
        .info-row .value { font-size: 13px; font-weight: 600; color: #222; }

        .amount-box { background: #f0fff4; border: 2px solid <?= $status_color ?>; border-radius: 8px; padding: 16px 20px; margin: 18px 0; text-align: center; }
        .amount-box .label { font-size: 12px; color: #666; margin-bottom: 4px; }
        .amount-box .amount { font-size: 32px; font-weight: 700; color: <?= $status_color ?>; }
        .amount-box .type-badge { display: inline-block; background: <?= $status_color ?>; color: #fff; font-size: 11px; padding: 2px 10px; border-radius: 20px; margin-top: 6px; }

        .balance-box { display: flex; justify-content: space-between; background: #f8f9fa; border-radius: 6px; padding: 12px 16px; margin-top: 14px; }
        .balance-box .bal-item { text-align: center; }
        .balance-box .bal-label { font-size: 11px; color: #888; }
        .balance-box .bal-value { font-size: 16px; font-weight: 700; color: #145a32; }
        .balance-box .divider { border-left: 1px solid #dee2e6; }

        .receipt-footer { background: #f9f9f9; border-top: 1px dashed #ccc; padding: 16px 28px; text-align: center; }
        .receipt-footer .thank-you { font-size: 15px; font-weight: 700; color: #145a32; margin-bottom: 4px; }
        .receipt-footer small { font-size: 11px; color: #999; }

        .watermark { text-align: center; color: <?= $status_color ?>; font-weight: 700; font-size: 11px; letter-spacing: 2px; padding: 8px; }

        .print-btn { display: block; text-align: center; margin: 20px auto; }
        .btn-print { background: #145a32; color: #fff; border: none; padding: 10px 32px; font-size: 14px; border-radius: 4px; cursor: pointer; margin-right: 8px; }
        .btn-close-win { background: #6c757d; color: #fff; border: none; padding: 10px 24px; font-size: 14px; border-radius: 4px; cursor: pointer; }

        @media print {
            body { background: #fff; }
            .print-btn { display: none !important; }
            .receipt-wrapper { border: none; box-shadow: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="print-btn">
    <button class="btn-print" onclick="window.print()">&#128438; Download / Print PDF</button>
    <button class="btn-close-win" onclick="window.close()">Close</button>
</div>

<div class="receipt-wrapper">

    <!-- Header -->
    <div class="receipt-header">
        <div>
            <div class="company-name"><?= htmlspecialchars(get_company_name()) ?></div>
            <div class="company-sub">Security / Savings Transaction Receipt</div>
        </div>
        <div class="receipt-label">
            <h4>TRANSACTION RECEIPT</h4>
            <div class="code"><?= htmlspecialchars($transaction->transaction_code ?? '-') ?></div>
            <div class="code" style="margin-top:3px; font-size:11px;"><?= date('d M Y, h:i A', strtotime($transaction->created_at ?? date('Y-m-d'))) ?></div>
        </div>
    </div>

    <div class="status-bar"><?= $status_label ?></div>

    <div class="receipt-body">

        <!-- Amount -->
        <div class="amount-box">
            <div class="label"><?= $is_credit ? 'Amount Deposited' : 'Amount' ?></div>
            <div class="amount"><?= $is_credit ? '+' : '-' ?><?= get_currency_symbol() ?><?= number_format((float)$transaction->amount, 2) ?></div>
            <div class="type-badge"><?= ucwords(str_replace('_', ' ', $txn_type)) ?></div>
        </div>

        <!-- Balance Summary -->
        <div class="balance-box">
            <div class="bal-item">
                <div class="bal-label">Balance Before</div>
                <div class="bal-value"><?= get_currency_symbol() ?><?= number_format(max(0, (float)($transaction->balance_after_computed ?? $transaction->balance_after ?? 0) - (float)($is_credit ? $transaction->amount : -$transaction->amount)), 2) ?></div>
            </div>
            <div class="divider"></div>
            <div class="bal-item">
                <div class="bal-label" style="color:<?= $status_color ?>"><?= $is_credit ? '+ Deposited' : '- Withdrawn' ?></div>
                <div class="bal-value" style="color:<?= $status_color ?>"><?= get_currency_symbol() ?><?= number_format((float)$transaction->amount, 2) ?></div>
            </div>
            <div class="divider"></div>
            <div class="bal-item">
                <div class="bal-label">Balance After</div>
                <div class="bal-value"><?= get_currency_symbol() ?><?= number_format((float)($transaction->balance_after_computed ?? $transaction->balance_after ?? 0), 2) ?></div>
            </div>
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
        </div>

        <!-- Account Details -->
        <div class="section-title">Account Details</div>
        <div class="info-grid">
            <div class="info-row">
                <span class="label">Account Number</span>
                <span class="value"><?= htmlspecialchars($account->account_number ?? '-') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Scheme / Plan</span>
                <span class="value"><?= htmlspecialchars($account->scheme_name ?? '-') ?></span>
            </div>
            <div class="info-row" style="margin-top:8px;">
                <span class="label">Account Status</span>
                <span class="value"><?= ucfirst($account->status ?? '-') ?></span>
            </div>
            <?php if (!empty($transaction->for_month)): ?>
            <div class="info-row" style="margin-top:8px;">
                <span class="label">For Month</span>
                <span class="value"><?= date('F Y', strtotime($transaction->for_month)) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Payment Info -->
        <div class="section-title">Transaction Information</div>
        <div class="info-grid">
            <div class="info-row">
                <span class="label">Transaction Date</span>
                <span class="value"><?= date('d M Y', strtotime($transaction->transaction_date)) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Payment Mode</span>
                <span class="value"><?= ucwords(str_replace('_', ' ', $transaction->payment_mode ?? 'Cash')) ?></span>
            </div>
            <?php if (!empty($transaction->reference_number)): ?>
            <div class="info-row" style="margin-top:8px;">
                <span class="label">Reference / UTR No.</span>
                <span class="value"><?= htmlspecialchars($transaction->reference_number) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($transaction->narration)): ?>
            <div class="info-row" style="margin-top:8px; grid-column: 1 / -1;">
                <span class="label">Narration</span>
                <span class="value"><?= htmlspecialchars($transaction->narration) ?></span>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="watermark">&#10003; VERIFIED</div>

    <div class="receipt-footer">
        <div class="thank-you">Thank you for your contribution!</div>
        <small>This is a computer-generated receipt. No signature required.</small><br>
        <small>Generated on <?= date('d M Y, h:i A') ?> &nbsp;|&nbsp; <?= htmlspecialchars(get_company_name()) ?></small>
    </div>

</div>

<div class="print-btn">
    <button class="btn-print" onclick="window.print()">&#128438; Download / Print PDF</button>
    <button class="btn-close-win" onclick="window.close()">Close</button>
</div>

</body>
</html>
