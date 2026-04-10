<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Receipt &#x2013; <?= htmlspecialchars($payment->receipt_number ?? $payment->payment_code ?? $payment->id) ?></title>
<link rel="stylesheet" href="<?= base_url('assets/plugins/fontawesome-free/css/all.min.css') ?>">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1a1a2e; background: #eef1f7; }
a { color: inherit; text-decoration: none; }
.page-wrapper { max-width: 820px; margin: 24px auto; padding: 0 12px 40px; }
.toolbar { display: flex; gap: 10px; margin-bottom: 16px; justify-content: flex-end; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 6px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; }
.btn-print { background: #1a4fba; color: #fff; }
.btn-back  { background: #6c757d; color: #fff; }
.receipt { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.10); overflow: hidden; position: relative; }
.top-bar    { height: 7px; background: linear-gradient(90deg,#1a4fba 0%,#2ecc71 100%); }
.bottom-bar { height: 5px; background: linear-gradient(90deg,#2ecc71 0%,#1a4fba 100%); }
.watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%) rotate(-30deg); font-size: 110px; font-weight: 900; color: rgba(22,163,74,.05); pointer-events: none; user-select: none; letter-spacing: 8px; z-index: 0; white-space: nowrap; }
.receipt > *:not(.watermark) { position: relative; z-index: 1; }
.receipt-header { display: flex; justify-content: space-between; align-items: flex-start; padding: 24px 30px 20px; border-bottom: 1px solid #e8ecf4; flex-wrap: wrap; gap: 16px; }
.org-block { display: flex; align-items: center; gap: 14px; }
.org-logo  { height: 56px; width: auto; }
.org-logo-placeholder { width: 56px; height: 56px; border-radius: 10px; background: linear-gradient(135deg,#1a4fba,#2ecc71); color: #fff; font-size: 22px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
.org-name { font-size: 20px; font-weight: 800; color: #1a1a2e; }
.org-meta  { font-size: 11.5px; color: #6c7899; margin-top: 3px; line-height: 1.7; }
.receipt-title-block { text-align: right; }
.label-receipt { font-size: 22px; font-weight: 800; color: #1a4fba; letter-spacing: 1px; }
.receipt-code  { font-size: 13px; font-weight: 600; color: #444; margin-top: 4px; }
.receipt-date  { font-size: 12px; color: #999; margin-top: 2px; }
.status-banner { display: flex; align-items: center; gap: 10px; padding: 10px 30px; font-size: 13px; font-weight: 600; flex-wrap: wrap; }
.status-banner .status-badge { margin-left: auto; font-size: 11px; font-weight: 700; padding: 3px 12px; border-radius: 999px; letter-spacing: .5px; }
.status-banner.type-deposit    { background:#d1fae5; color:#065f46; }
.status-banner.type-deposit .status-badge { background:#065f46; color:#d1fae5; }
.status-banner.type-withdrawal { background:#fee2e2; color:#7f1d1d; }
.status-banner.type-withdrawal .status-badge { background:#7f1d1d; color:#fee2e2; }
.status-banner.type-interest   { background:#dbeafe; color:#1e3a8a; }
.status-banner.type-interest .status-badge { background:#1e3a8a; color:#dbeafe; }
.status-banner.type-fine       { background:#fef3c7; color:#92400e; }
.status-banner.type-fine .status-badge { background:#92400e; color:#fef3c7; }
.status-banner.type-emi        { background:#d1fae5; color:#065f46; }
.status-banner.type-emi .status-badge { background:#065f46; color:#d1fae5; }
.status-banner.type-part_payment { background:#ede9fe; color:#4c1d95; }
.status-banner.type-part_payment .status-badge { background:#4c1d95; color:#ede9fe; }
.status-banner.type-foreclosure { background:#fef3c7; color:#92400e; }
.status-banner.type-foreclosure .status-badge { background:#92400e; color:#fef3c7; }
.status-banner.type-default    { background:#e0e7ff; color:#1e3a8a; }
.status-banner.type-default .status-badge { background:#1e3a8a; color:#e0e7ff; }
.receipt-body { padding: 24px 30px; }
.amount-hero { display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg,#052e16 0%,#166534 100%); border-radius: 10px; padding: 22px 28px; color: #fff; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
.amount-label  { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; opacity: .75; }
.amount-value  { font-size: 38px; font-weight: 800; color: #fff; line-height: 1.1; }
.amount-words  { font-size: 12px; opacity: .75; margin-top: 5px; }
.check-mark { width: 52px; height: 52px; border-radius: 50%; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 26px; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
@media (max-width: 540px) { .info-grid { grid-template-columns: 1fr; } }
.info-card { border: 1px solid #e8ecf4; border-radius: 8px; overflow: hidden; }
.info-card-header { background: #f5f7fb; padding: 9px 14px; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #1a4fba; border-bottom: 1px solid #e8ecf4; }
.info-card-body { padding: 10px 14px; }
.info-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #f0f2f8; gap: 8px; }
.info-row:last-child { border-bottom: none; }
.info-key { color: #6c7899; font-size: 12px; }
.info-val { font-weight: 600; font-size: 12px; text-align: right; }
.breakdown-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #1a4fba; margin-bottom: 8px; }
.breakdown-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.breakdown-table th { background: #f5f7fb; padding: 8px 12px; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6c7899; border-bottom: 2px solid #e8ecf4; }
.breakdown-table td { padding: 9px 12px; border-bottom: 1px solid #f0f2f8; font-size: 12.5px; }
.breakdown-table tr:last-child td { border-bottom: none; font-weight: 700; background: #f5f7fb; }
.text-right { text-align: right; }
.outstanding-bar { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px 20px; display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; align-items: center; }
.os-block { flex: 1; min-width: 120px; }
.os-label { font-size: 11px; color: #6c7899; text-transform: uppercase; letter-spacing: .5px; }
.os-value { font-size: 16px; font-weight: 700; color: #1e3a8a; }
.signature-row { display: flex; justify-content: space-between; margin-top: 36px; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
.sig-box { text-align: center; flex: 1; min-width: 120px; }
.sig-line { border-top: 2px solid #333; width: 160px; margin: 42px auto 8px; }
.sig-label { font-size: 11.5px; color: #6c7899; }
.receipt-footer { border-top: 1px dashed #d1d5db; padding: 14px 30px; text-align: center; font-size: 11px; color: #9ca3af; line-height: 1.7; }
@media print {
    body { background: #fff; }
    .page-wrapper { margin: 0; max-width: 100%; padding: 0; }
    .toolbar { display: none !important; }
    .receipt { box-shadow: none; border-radius: 0; }
    @page { margin: 12mm 14mm; size: A4; }
}
</style>
</head>
<body>
<?php
function savings_n2w($n) {
    $n = (int)$n;
    $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    if ($n < 0)   return 'Negative '.savings_n2w(-$n);
    if ($n === 0) return 'Zero';
    if ($n < 20)  return $ones[$n];
    if ($n < 100) return $tens[(int)($n/10)].($n%10 ? ' '.$ones[$n%10] : '');
    if ($n < 1000)return $ones[(int)($n/100)].' Hundred'.($n%100 ? ' '.savings_n2w($n%100) : '');
    if ($n < 1e5) return savings_n2w((int)($n/1000)).' Thousand'.($n%1000 ? ' '.savings_n2w($n%1000) : '');
    if ($n < 1e7) return savings_n2w((int)($n/1e5)).' Lakh'.($n%1e5 ? ' '.savings_n2w($n%1e5) : '');
    return savings_n2w((int)($n/1e7)).' Crore'.($n%1e7 ? ' '.savings_n2w($n%1e7) : '');
}
function savings_amount_words($amount) {
    $r = (int)$amount; $p = (int)round(($amount - $r)*100);
    return savings_n2w($r).' Rupees'.($p > 0 ? ' and '.savings_n2w($p).' Paise' : '').' Only';
}

$receipt_code = $payment->receipt_number ?? $payment->payment_code ?? $payment->fine_code ?? ('TXN#'.$payment->id);
$pay_amount = 0;
if ($type === 'savings') {
    $pay_amount = (float)($payment->credit_amount ?? $payment->debit_amount ?? $payment->amount ?? 0);
} elseif ($type === 'loan') {
    $pay_amount = (float)($payment->total_amount ?? 0);
} elseif ($type === 'fine') {
    $pay_amount = (float)($payment->amount ?? $payment->total_amount ?? 0);
}

if ($type === 'savings') {
    $tt = $payment->transaction_type ?? 'deposit';
    $tt_label = ucfirst($tt).' &mdash; Security Deposit';
    $tt_icon  = ($tt === 'withdrawal') ? 'fa-hand-holding-usd' : (($tt === 'interest') ? 'fa-percentage' : 'fa-piggy-bank');
} elseif ($type === 'fine') {
    $tt = 'fine'; $tt_label = 'Fine Payment'; $tt_icon = 'fa-exclamation-circle';
} else {
    $tt = $payment->payment_type ?? 'emi';
    $labels = ['emi'=>'EMI Payment','part_payment'=>'Part Payment','foreclosure'=>'Foreclosure','interest_only'=>'Interest Only','regular'=>'Regular Payment','settlement'=>'Full Settlement','fine'=>'Fine Payment','advance'=>'Advance Payment'];
    $icons  = ['emi'=>'fa-calendar-check','part_payment'=>'fa-hand-holding-usd','foreclosure'=>'fa-flag-checkered','interest_only'=>'fa-percentage'];
    $tt_label = $labels[$tt] ?? ucfirst(str_replace('_',' ',$tt));
    $tt_icon  = $icons[$tt] ?? 'fa-receipt';
}

$pmode_icons = ['cash'=>'fa-money-bill-wave','upi'=>'fa-mobile-alt','neft'=>'fa-university','rtgs'=>'fa-university','imps'=>'fa-exchange-alt','cheque'=>'fa-money-check','bank_transfer'=>'fa-university','online'=>'fa-globe'];
$pm = $payment->payment_mode ?? 'cash';
$pm_label = '<i class="fas '.($pmode_icons[$pm] ?? 'fa-money-bill-wave').' mr-1"></i>'.ucfirst(str_replace('_',' ',$pm));

if ($type === 'savings' && !empty($payment->savings_account_id))      $back_url = site_url('admin/savings/view/'.$payment->savings_account_id);
elseif ($type === 'loan' && !empty($payment->loan_id))                 $back_url = site_url('admin/loans/view/'.$payment->loan_id);
elseif ($type === 'fine' && !empty($payment->fine_id))                 $back_url = site_url('admin/fines/view/'.$payment->fine_id);
else $back_url = site_url('admin/payments/history');

$issued_at = $payment->created_at ?? $payment->transaction_date ?? $payment->payment_date ?? date('Y-m-d H:i:s');
?>
<div class="page-wrapper">
    <div class="toolbar no-print">
        <button class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print / Save PDF</button>
        <a href="<?= $back_url ?>" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="receipt">
        <div class="watermark">PAID</div>
        <div class="top-bar"></div>

        <div class="receipt-header">
            <div class="org-block">
                <?php if (file_exists(FCPATH.'assets/img/logo.svg')): ?>
                <img src="<?= base_url('assets/img/logo.svg') ?>" alt="<?= htmlspecialchars($company_name) ?>" class="org-logo">
                <?php else: ?>
                <div class="org-logo-placeholder"><?= htmlspecialchars(strtoupper(substr($company_short_name ?? 'WF',0,2))) ?></div>
                <?php endif; ?>
                <div>
                    <div class="org-name"><?= htmlspecialchars($company_name) ?></div>
                    <div class="org-meta">
                        <?php if (!empty($company_address)): ?><i class="fas fa-map-marker-alt" style="width:14px;color:#1a4fba"></i> <?= htmlspecialchars($company_address) ?><br><?php endif; ?>
                        <?php if (!empty($company_phone)): ?><i class="fas fa-phone" style="width:14px;color:#1a4fba"></i> <?= htmlspecialchars($company_phone) ?><?php endif; ?>
                        <?php if (!empty($company_email)): ?> &nbsp;&nbsp;<i class="fas fa-envelope" style="width:14px;color:#1a4fba"></i> <?= htmlspecialchars($company_email) ?><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="receipt-title-block">
                <div class="label-receipt"><i class="fas fa-receipt" style="font-size:18px"></i> Receipt</div>
                <div class="receipt-code"><?= htmlspecialchars($receipt_code) ?></div>
                <div class="receipt-date">Issued: <strong><?= date('d M Y', strtotime($issued_at)) ?></strong></div>
            </div>
        </div>

        <div class="status-banner type-<?= htmlspecialchars($tt) ?>">
            <i class="fas <?= $tt_icon ?>"></i>
            <span><?= $tt_label ?></span>
            <span class="status-badge"><i class="fas fa-check mr-1"></i>Confirmed</span>
        </div>

        <div class="receipt-body">
            <div class="amount-hero">
                <div>
                    <div class="amount-label">Amount <?= ($type==='savings' && ($payment->transaction_type??'')===  'withdrawal') ? 'Withdrawn' : 'Received' ?></div>
                    <div class="amount-value">&#x20B9;<?= number_format($pay_amount, 2) ?></div>
                    <div class="amount-words"><?= savings_amount_words($pay_amount) ?></div>
                </div>
                <div class="check-mark"><i class="fas fa-check"></i></div>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <div class="info-card-header"><i class="fas fa-user"></i> Member Details</div>
                    <div class="info-card-body">
                        <div class="info-row"><span class="info-key">Name</span><span class="info-val"><?= htmlspecialchars(trim(($payment->first_name??'').' '.($payment->last_name??''))) ?></span></div>
                        <div class="info-row"><span class="info-key">Member Code</span><span class="info-val"><?= htmlspecialchars($payment->member_code ?? '&#x2014;') ?></span></div>
                        <div class="info-row"><span class="info-key">Phone</span><span class="info-val"><?= htmlspecialchars($payment->phone ?? '&#x2014;') ?></span></div>
                        <?php if (!empty($payment->address)): ?>
                        <div class="info-row"><span class="info-key">Address</span><span class="info-val" style="max-width:160px"><?= htmlspecialchars($payment->address) ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-card-header">
                        <?php if ($type==='savings'): ?><i class="fas fa-piggy-bank"></i> Deposit Details
                        <?php elseif($type==='fine'): ?><i class="fas fa-exclamation-circle"></i> Fine Details
                        <?php else: ?><i class="fas fa-file-contract"></i> Loan Details<?php endif; ?>
                    </div>
                    <div class="info-card-body">
                        <?php if ($type==='savings'): ?>
                        <div class="info-row"><span class="info-key">Account No</span><span class="info-val"><?= htmlspecialchars($payment->account_number ?? '&#x2014;') ?></span></div>
                        <div class="info-row"><span class="info-key">Transaction Date</span><span class="info-val"><?= format_date($payment->transaction_date ?? $payment->created_at ?? date('Y-m-d')) ?></span></div>
                        <?php if (!empty($payment->for_month)): ?>
                        <div class="info-row"><span class="info-key">For Month</span><span class="info-val"><?= htmlspecialchars($payment->for_month) ?></span></div>
                        <?php endif; ?>
                        <div class="info-row"><span class="info-key">Balance After</span><span class="info-val">&#x20B9;<?= number_format((float)($payment->balance_after ?? $payment->running_balance ?? 0),2) ?></span></div>
                        <?php elseif($type==='fine'): ?>
                        <div class="info-row"><span class="info-key">Fine Code</span><span class="info-val"><?= htmlspecialchars($payment->fine_code ?? '&#x2014;') ?></span></div>
                        <div class="info-row"><span class="info-key">Payment Date</span><span class="info-val"><?= format_date($payment->payment_date ?? date('Y-m-d')) ?></span></div>
                        <?php else: ?>
                        <div class="info-row"><span class="info-key">Loan Number</span><span class="info-val"><?= htmlspecialchars($payment->loan_number ?? '&#x2014;') ?></span></div>
                        <div class="info-row"><span class="info-key">Payment Date</span><span class="info-val"><?= format_date($payment->payment_date ?? date('Y-m-d')) ?></span></div>
                        <?php if (!empty($payment->payment_type)): ?>
                        <div class="info-row"><span class="info-key">Type</span><span class="info-val"><?= htmlspecialchars(ucfirst(str_replace('_',' ',$payment->payment_type))) ?></span></div>
                        <?php endif; ?>
                        <?php endif; ?>
                        <div class="info-row"><span class="info-key">Mode</span><span class="info-val"><?= $pm_label ?></span></div>
                        <?php if (!empty($payment->reference_number)): ?>
                        <div class="info-row"><span class="info-key">Ref / UTR</span><span class="info-val"><?= htmlspecialchars($payment->reference_number) ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($payment->cheque_number)): ?>
                        <div class="info-row"><span class="info-key">Cheque No</span><span class="info-val"><?= htmlspecialchars($payment->cheque_number) ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($type === 'loan'): ?>
            <div class="breakdown-title"><i class="fas fa-table mr-1"></i> Payment Breakdown</div>
            <table class="breakdown-table">
                <thead><tr><th>Component</th><th class="text-right">Amount (&#x20B9;)</th></tr></thead>
                <tbody>
                    <tr><td>Principal</td><td class="text-right" style="color:#16a34a">&#x20B9;<?= number_format((float)($payment->principal_component??0),2) ?></td></tr>
                    <tr><td>Interest</td><td class="text-right">&#x20B9;<?= number_format((float)($payment->interest_component??0),2) ?></td></tr>
                    <?php if (!empty($payment->fine_component) && $payment->fine_component>0): ?>
                    <tr><td>Fine / Penalty</td><td class="text-right" style="color:#dc2626">&#x20B9;<?= number_format((float)$payment->fine_component,2) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($payment->excess_amount) && $payment->excess_amount>0): ?>
                    <tr><td>Credited to Savings</td><td class="text-right" style="color:#6c7899">&#x20B9;<?= number_format((float)$payment->excess_amount,2) ?></td></tr>
                    <?php endif; ?>
                    <tr><td><strong>Total Paid</strong></td><td class="text-right"><strong>&#x20B9;<?= number_format($pay_amount,2) ?></strong></td></tr>
                </tbody>
            </table>
            <?php if (!empty($payment->outstanding_principal_after)): ?>
            <div class="outstanding-bar">
                <div class="os-block"><div class="os-label">Outstanding Principal</div><div class="os-value">&#x20B9;<?= number_format((float)$payment->outstanding_principal_after,2) ?></div></div>
                <div class="os-block"><div class="os-label">Outstanding Interest</div><div class="os-value">&#x20B9;<?= number_format((float)($payment->outstanding_interest_after??0),2) ?></div></div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php $narration = $payment->narration ?? $payment->remarks ?? null; ?>
            <?php if (!empty($narration)): ?>
            <div style="background:#f9fafb;border:1px solid #e8ecf4;border-radius:6px;padding:10px 14px;margin-bottom:20px;font-size:12px;color:#6c7899">
                <strong>Remarks:</strong> <?= htmlspecialchars($narration) ?>
            </div>
            <?php endif; ?>

            <div class="signature-row">
                <div class="sig-box"><div class="sig-line"></div><div class="sig-label">Member Signature</div></div>
                <div class="sig-box"><div class="sig-line"></div><div class="sig-label">Authorised Signatory</div></div>
            </div>
        </div>

        <div class="receipt-footer">
            This is a system-generated receipt and does not require a physical signature.<br>
            Printed: <?= date('d M Y, h:i A') ?> &nbsp;|&nbsp; Powered by <?= htmlspecialchars($company_name) ?>
        </div>
        <div class="bottom-bar"></div>
    </div>
</div>
<script>
if (new URLSearchParams(window.location.search).get('print') === '1') {
    window.onload = function() { window.print(); };
}
</script>
</body>
</html>