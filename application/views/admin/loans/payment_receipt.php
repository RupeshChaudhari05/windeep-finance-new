<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Payment Receipt â€” <?= htmlspecialchars($payment->payment_code) ?></title>
<link rel="stylesheet" href="<?= base_url('assets/plugins/fontawesome-free/css/all.min.css') ?>">
<style>
/* â”€â”€â”€â”€â”€â”€â”€â”€â”€ RESET & BASE â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px;
    color: #1a1a2e;
    background: #eef1f7;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€ SCREEN WRAPPER â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.page-wrapper {
    max-width: 820px;
    margin: 24px auto 40px;
}
.toolbar {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-bottom: 14px;
}
.toolbar .btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
}
.btn-print  { background: #1a4fba; color: #fff; }
.btn-close  { background: #6c757d; color: #fff; }

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€ RECEIPT PAPER â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.receipt {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.12);
}

/* â”€â”€ TOP COLOUR BAR â”€â”€ */
.top-bar {
    height: 6px;
    background: linear-gradient(90deg, #1a4fba 0%, #0d9488 100%);
}

/* â”€â”€ HEADER â”€â”€ */
.receipt-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 28px 32px 20px;
    border-bottom: 1px solid #e8ecf4;
    gap: 20px;
}
.org-block { display: flex; align-items: center; gap: 16px; flex: 1; }
.org-logo {
    width: 60px; height: 60px;
    border-radius: 10px;
    object-fit: contain;
    background: #f0f4ff;
    padding: 4px;
    flex-shrink: 0;
}
.org-logo-placeholder {
    width: 60px; height: 60px;
    border-radius: 10px;
    background: linear-gradient(135deg,#1a4fba,#0d9488);
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; font-weight: 800; color: #fff;
    flex-shrink: 0;
    letter-spacing: 1px;
}
.org-name  { font-size: 20px; font-weight: 800; color: #1a1a2e; line-height: 1.2; }
.org-meta  { font-size: 11.5px; color: #64748b; margin-top: 3px; line-height: 1.6; }
.org-meta a { color: #1a4fba; text-decoration: none; }

.receipt-title-block { text-align: right; flex-shrink: 0; }
.receipt-title-block .label-receipt {
    font-size: 22px; font-weight: 800; color: #1a4fba;
    letter-spacing: 2px; text-transform: uppercase;
}
.receipt-code {
    font-size: 13px; font-weight: 700; color: #1a1a2e;
    background: #f0f4ff; border: 1px solid #cdd7f7;
    padding: 4px 12px; border-radius: 20px;
    display: inline-block; margin-top: 6px;
}
.receipt-date { font-size: 11.5px; color: #64748b; margin-top: 6px; }

/* â”€â”€ STATUS BANNER â”€â”€ */
.status-banner {
    background: linear-gradient(90deg, #065f46, #0d9488);
    color: #fff;
    padding: 10px 32px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 600;
}
.status-banner.type-part_payment { background: linear-gradient(90deg,#7c3aed,#a855f7); }
.status-banner.type-foreclosure  { background: linear-gradient(90deg,#b45309,#d97706); }
.status-banner.type-interest_only{ background: linear-gradient(90deg,#0369a1,#0ea5e9); }
.status-badge {
    background: rgba(255,255,255,.25);
    border-radius: 20px;
    padding: 2px 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
}

/* â”€â”€ BODY â”€â”€ */
.receipt-body { padding: 24px 32px; }

/* â”€â”€ AMOUNT HERO â”€â”€ */
.amount-hero {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1.5px solid #86efac;
    border-radius: 12px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}
.amount-hero-left .amount-label { font-size: 11.5px; color: #166534; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.amount-hero-left .amount-value {
    font-size: 38px; font-weight: 800; color: #166534; line-height: 1.1;
}
.amount-hero-left .amount-words { font-size: 11.5px; color: #15803d; margin-top: 4px; font-style: italic; }
.amount-hero-right { text-align: right; }
.check-mark {
    width: 52px; height: 52px;
    background: #16a34a;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 22px;
}

/* â”€â”€ TWO COLUMN INFO GRID â”€â”€ */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
.info-card {
    border: 1px solid #e8ecf4;
    border-radius: 8px;
    overflow: hidden;
}
.info-card-header {
    background: #f8faff;
    border-bottom: 1px solid #e8ecf4;
    padding: 8px 14px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 6px;
}
.info-card-header i { color: #1a4fba; }
.info-card-body { padding: 12px 14px; }
.info-row { display: flex; justify-content: space-between; align-items: baseline; padding: 4px 0; border-bottom: 1px dashed #f0f0f0; }
.info-row:last-child { border-bottom: none; }
.info-key   { color: #64748b; font-size: 11.5px; flex: 0 0 48%; }
.info-val   { color: #1a1a2e; font-size: 12px; font-weight: 600; text-align: right; word-break: break-word; flex: 0 0 50%; }

/* â”€â”€ BREAKDOWN TABLE â”€â”€ */
.breakdown-section { margin-bottom: 20px; }
.breakdown-table { width: 100%; border-collapse: collapse; border: 1px solid #e8ecf4; border-radius: 8px; overflow: hidden; }
.breakdown-table thead tr { background: #f8faff; }
.breakdown-table th { padding: 9px 14px; font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: #64748b; text-align: left; border-bottom: 1px solid #e8ecf4; }
.breakdown-table th:last-child, .breakdown-table td:last-child { text-align: right; }
.breakdown-table td { padding: 9px 14px; font-size: 12.5px; border-bottom: 1px solid #f0f4f8; color: #1a1a2e; }
.breakdown-table tr:last-child td { border-bottom: none; }
.breakdown-table .total-row td { background: #f0f4ff; font-weight: 700; font-size: 13px; color: #1a4fba; border-top: 2px solid #cdd7f7; }
.text-success { color: #16a34a !important; }
.text-danger  { color: #dc2626 !important; }
.text-muted   { color: #94a3b8 !important; }

/* â”€â”€ OUTSTANDING AFTER â”€â”€ */
.outstanding-bar {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 12px 16px;
    display: flex;
    gap: 32px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.outstanding-item .os-label { font-size: 10.5px; color: #0369a1; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
.outstanding-item .os-value { font-size: 16px; font-weight: 800; color: #0369a1; margin-top: 1px; }

/* â”€â”€ SIGNATURE â”€â”€ */
.signature-row {
    display: flex;
    justify-content: space-between;
    margin-top: 32px;
    padding-top: 20px;
    border-top: 1px dashed #cbd5e1;
}
.sig-box { text-align: center; width: 44%; }
.sig-line { border-bottom: 1.5px solid #334155; height: 44px; margin-bottom: 6px; }
.sig-label { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }

/* â”€â”€ FOOTER â”€â”€ */
.receipt-footer {
    background: #f8faff;
    border-top: 1px solid #e8ecf4;
    padding: 14px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}
.footer-note { font-size: 10.5px; color: #94a3b8; }
.footer-note strong { color: #64748b; }
.footer-brand { font-size: 10.5px; color: #94a3b8; }
.footer-brand span { color: #1a4fba; font-weight: 700; }

/* â”€â”€ BOTTOM ACCENT â”€â”€ */
.bottom-bar { height: 4px; background: linear-gradient(90deg,#1a4fba 0%,#0d9488 100%); }

/* â”€â”€ WATERMARK "PAID" â”€â”€ */
.receipt { position: relative; }
.watermark {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%,-50%) rotate(-30deg);
    font-size: 110px;
    font-weight: 900;
    color: rgba(22,163,74,.05);
    pointer-events: none;
    user-select: none;
    letter-spacing: 8px;
    z-index: 0;
    white-space: nowrap;
}
.receipt > *:not(.watermark) { position: relative; z-index: 1; }

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€ PRINT STYLES â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
@media print {
    body { background: #fff; }
    .page-wrapper { margin: 0; max-width: 100%; }
    .toolbar { display: none !important; }
    .receipt { box-shadow: none; border-radius: 0; }
    @page { margin: 12mm 14mm; size: A4; }
}
</style>
</head>
<body>
<?php
// â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function receipt_number_to_words($n) {
    $n = (int)$n;
    $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
             'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
             'Seventeen','Eighteen','Nineteen'];
    $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    if ($n < 0)    return 'Negative ' . receipt_number_to_words(-$n);
    if ($n === 0)  return 'Zero';
    if ($n < 20)   return $ones[$n];
    if ($n < 100)  return $tens[(int)($n/10)] . ($n%10 ? ' ' . $ones[$n%10] : '');
    if ($n < 1000) return $ones[(int)($n/100)] . ' Hundred' . ($n%100 ? ' ' . receipt_number_to_words($n%100) : '');
    if ($n < 1e5)  return receipt_number_to_words((int)($n/1000)) . ' Thousand' . ($n%1000 ? ' ' . receipt_number_to_words($n%1000) : '');
    if ($n < 1e7)  return receipt_number_to_words((int)($n/1e5))  . ' Lakh'    . ($n%1e5  ? ' ' . receipt_number_to_words($n%1e5)  : '');
    return receipt_number_to_words((int)($n/1e7)) . ' Crore' . ($n%1e7 ? ' ' . receipt_number_to_words($n%1e7) : '');
}
function receipt_amount_words($amount) {
    $rupees = (int)$amount;
    $paise  = (int)round(($amount - $rupees) * 100);
    $str = receipt_number_to_words($rupees) . ' Rupees';
    if ($paise > 0) $str .= ' and ' . receipt_number_to_words($paise) . ' Paise';
    return $str . ' Only';
}
$ptype_labels = [
    'emi'           => 'EMI Payment',
    'part_payment'  => 'Part Payment',
    'foreclosure'   => 'Foreclosure',
    'interest_only' => 'Interest Only',
    'regular'       => 'Regular Payment',
    'settlement'    => 'Full Settlement',
    'fine'          => 'Fine Payment',
    'advance'       => 'Advance Payment',
];
$ptype_icons = [
    'emi'           => 'fa-calendar-check',
    'part_payment'  => 'fa-hand-holding-usd',
    'foreclosure'   => 'fa-flag-checkered',
    'interest_only' => 'fa-percentage',
    'regular'       => 'fa-check-circle',
    'settlement'    => 'fa-lock',
    'fine'          => 'fa-exclamation-circle',
    'advance'       => 'fa-forward',
];
$pmode_labels = [
    'cash'          => '<i class="fas fa-money-bill-wave mr-1"></i>Cash',
    'upi'           => '<i class="fas fa-mobile-alt mr-1"></i>UPI',
    'bank_transfer' => '<i class="fas fa-university mr-1"></i>Bank Transfer',
    'cheque'        => '<i class="fas fa-money-check mr-1"></i>Cheque',
    'online'        => '<i class="fas fa-globe mr-1"></i>Online',
    'adjustment'    => '<i class="fas fa-sliders-h mr-1"></i>Adjustment',
    'neft'          => '<i class="fas fa-exchange-alt mr-1"></i>NEFT',
    'rtgs'          => '<i class="fas fa-exchange-alt mr-1"></i>RTGS',
    'imps'          => '<i class="fas fa-bolt mr-1"></i>IMPS',
];
$pt      = $payment->payment_type ?? 'emi';
$pt_label = $ptype_labels[$pt] ?? ucfirst(str_replace('_',' ',$pt));
$pt_icon  = $ptype_icons[$pt]  ?? 'fa-receipt';
$pm       = $payment->payment_mode ?? 'cash';
$pm_label = $pmode_labels[$pm] ?? ucfirst(str_replace('_',' ',$pm));
?>

<div class="page-wrapper">
    <!-- â”€â”€ Toolbar â”€â”€ -->
    <div class="toolbar no-print">
        <button class="btn btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print / Save PDF
        </button>
        <a href="<?= site_url('admin/loans/view/' . ($payment->loan_id ?? '')) ?>" class="btn btn-close">
            <i class="fas fa-arrow-left"></i> Back to Loan
        </a>
    </div>

    <div class="receipt">
        <div class="watermark">PAID</div>
        <div class="top-bar"></div>

        <!-- â•â• HEADER â•â• -->
        <div class="receipt-header">
            <div class="org-block">
                <?php
                $logo_path = FCPATH . 'assets/img/logo.svg';
                if (file_exists($logo_path)):
                ?>
                <img src="<?= base_url('assets/img/logo.svg') ?>" alt="<?= htmlspecialchars($company_name) ?>" class="org-logo">
                <?php else: ?>
                <div class="org-logo-placeholder"><?= htmlspecialchars(strtoupper(substr($company_short_name ?? 'WF', 0, 2))) ?></div>
                <?php endif; ?>
                <div>
                    <div class="org-name"><?= htmlspecialchars($company_name) ?></div>
                    <div class="org-meta">
                        <?php if (!empty($company_address)): ?>
                            <i class="fas fa-map-marker-alt" style="width:14px;color:#1a4fba"></i>
                            <?= htmlspecialchars($company_address) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($company_phone)): ?>
                            <i class="fas fa-phone" style="width:14px;color:#1a4fba"></i>
                            <?= htmlspecialchars($company_phone) ?>
                        <?php endif; ?>
                        <?php if (!empty($company_email)): ?>
                            &nbsp;&nbsp;
                            <i class="fas fa-envelope" style="width:14px;color:#1a4fba"></i>
                            <a href="mailto:<?= htmlspecialchars($company_email) ?>"><?= htmlspecialchars($company_email) ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="receipt-title-block">
                <div class="label-receipt"><i class="fas fa-receipt" style="font-size:18px"></i> Receipt</div>
                <div class="receipt-code"><?= htmlspecialchars($payment->payment_code) ?></div>
                <div class="receipt-date">
                    Issued: <strong><?= date('d M Y', strtotime($payment->created_at)) ?></strong>
                </div>
            </div>
        </div>

        <!-- â•â• STATUS BANNER â•â• -->
        <div class="status-banner type-<?= htmlspecialchars($pt) ?>">
            <i class="fas <?= $pt_icon ?>"></i>
            <span><?= $pt_label ?></span>
            <span class="status-badge"><i class="fas fa-check mr-1"></i>Payment Confirmed</span>
        </div>

        <!-- â•â• BODY â•â• -->
        <div class="receipt-body">

            <!-- AMOUNT HERO -->
            <div class="amount-hero">
                <div class="amount-hero-left">
                    <div class="amount-label">Amount Received</div>
                    <div class="amount-value">&#x20B9;<?= number_format((float)$payment->total_amount, 2) ?></div>
                    <div class="amount-words"><?= receipt_amount_words($payment->total_amount) ?></div>
                </div>
                <div class="amount-hero-right">
                    <div class="check-mark"><i class="fas fa-check"></i></div>
                </div>
            </div>

            <!-- INFO GRID -->
            <div class="info-grid">
                <!-- Member Details -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fas fa-user"></i> Member Details
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="info-key">Name</span>
                            <span class="info-val"><?= htmlspecialchars($payment->first_name . ' ' . $payment->last_name) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Member Code</span>
                            <span class="info-val"><?= htmlspecialchars($payment->member_code) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Phone</span>
                            <span class="info-val"><?= htmlspecialchars($payment->phone ?? 'â€”') ?></span>
                        </div>
                        <?php if (!empty($payment->email)): ?>
                        <div class="info-row">
                            <span class="info-key">Email</span>
                            <span class="info-val"><?= htmlspecialchars($payment->email) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($payment->member_address)): ?>
                        <div class="info-row">
                            <span class="info-key">Address</span>
                            <span class="info-val"><?= htmlspecialchars($payment->member_address) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Loan & Payment Details -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fas fa-file-contract"></i> Loan & Payment
                    </div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="info-key">Loan Number</span>
                            <span class="info-val"><?= htmlspecialchars($payment->loan_number) ?></span>
                        </div>
                        <?php if (!empty($payment->installment_number)): ?>
                        <div class="info-row">
                            <span class="info-key">Installment #</span>
                            <span class="info-val"><?= $payment->installment_number ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($payment->installment_due_date)): ?>
                        <div class="info-row">
                            <span class="info-key">Due Date</span>
                            <span class="info-val"><?= date('d M Y', strtotime($payment->installment_due_date)) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-key">Payment Date</span>
                            <span class="info-val"><?= date('d M Y', strtotime($payment->payment_date)) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Mode</span>
                            <span class="info-val"><?= $pm_label ?></span>
                        </div>
                        <?php if (!empty($payment->reference_number)): ?>
                        <div class="info-row">
                            <span class="info-key">Ref / UTR</span>
                            <span class="info-val"><?= htmlspecialchars($payment->reference_number) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-key">Interest Rate</span>
                            <span class="info-val"><?= htmlspecialchars($payment->interest_rate) ?>% p.a.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BREAKDOWN TABLE -->
            <div class="breakdown-section">
                <table class="breakdown-table">
                    <thead>
                        <tr>
                            <th>Particulars</th>
                            <th>Amount (&#x20B9;)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ((float)$payment->principal_component > 0): ?>
                        <tr>
                            <td><i class="fas fa-coins text-muted mr-1"></i> Principal</td>
                            <td class="text-success">&#x20B9;<?= number_format((float)$payment->principal_component, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ((float)$payment->interest_component > 0): ?>
                        <tr>
                            <td><i class="fas fa-percentage text-muted mr-1"></i> Interest</td>
                            <td>&#x20B9;<?= number_format((float)$payment->interest_component, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ((float)$payment->fine_component > 0): ?>
                        <tr>
                            <td><i class="fas fa-exclamation-circle text-danger mr-1"></i> Fine / Penalty</td>
                            <td class="text-danger">&#x20B9;<?= number_format((float)$payment->fine_component, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ((float)($payment->excess_amount ?? 0) > 0): ?>
                        <tr>
                            <td><i class="fas fa-piggy-bank text-muted mr-1"></i> Credited to Savings</td>
                            <td class="text-muted">&#x20B9;<?= number_format((float)$payment->excess_amount, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="total-row">
                            <td><i class="fas fa-check-circle mr-1"></i> Total Received</td>
                            <td>&#x20B9;<?= number_format((float)$payment->total_amount, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- OUTSTANDING AFTER -->
            <div class="outstanding-bar">
                <div class="outstanding-item">
                    <div class="os-label"><i class="fas fa-info-circle mr-1"></i>Outstanding Principal</div>
                    <div class="os-value">&#x20B9;<?= number_format((float)$payment->outstanding_principal_after, 2) ?></div>
                </div>
                <?php if ((float)$payment->outstanding_interest_after > 0): ?>
                <div class="outstanding-item">
                    <div class="os-label">Outstanding Interest</div>
                    <div class="os-value">&#x20B9;<?= number_format((float)$payment->outstanding_interest_after, 2) ?></div>
                </div>
                <?php endif; ?>
                <div class="outstanding-item">
                    <div class="os-label">Total Loan EMI</div>
                    <div class="os-value">&#x20B9;<?= number_format((float)$payment->loan_emi_amount, 2) ?></div>
                </div>
            </div>

            <!-- SIGNATURE -->
            <div class="signature-row">
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-label">Member Signature</div>
                </div>
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-label">Authorised Signatory</div>
                </div>
            </div>

        </div><!-- /receipt-body -->

        <!-- â•â• FOOTER â•â• -->
        <div class="receipt-footer">
            <div class="footer-note">
                <i class="fas fa-shield-alt mr-1" style="color:#1a4fba"></i>
                This is a system-generated receipt. Printed on <strong><?= date('d M Y, h:i A') ?></strong>.
            </div>
            <div class="footer-brand">
                Powered by <span><?= htmlspecialchars($company_name) ?></span>
            </div>
        </div>
        <div class="bottom-bar"></div>
    </div><!-- /receipt -->
</div><!-- /page-wrapper -->

<script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
<script>
// Auto-print if ?print=1 in URL
if (new URLSearchParams(window.location.search).get('print') === '1') {
    window.onload = function() { window.print(); };
}
</script>
</body>
</html>
