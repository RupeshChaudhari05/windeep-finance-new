<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Statement - <?= $loan->loan_number ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.5; color: #333; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { color: #666; }
        .logo { width: 80px; height: 80px; margin-bottom: 10px; }
        
        .section { margin-bottom: 20px; }
        .section-title { font-size: 14px; font-weight: bold; background: #f5f5f5; padding: 8px 12px; margin-bottom: 10px; border-left: 3px solid #333; }
        
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 6px 10px; vertical-align: top; }
        .info-table .label { width: 40%; color: #666; }
        .info-table .value { font-weight: 500; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; }
        .data-table th { background: #f5f5f5; font-weight: bold; }
        .data-table .text-right { text-align: right; }
        .data-table tfoot { font-weight: bold; background: #f9f9f9; }
        
        .summary-box { display: flex; justify-content: space-between; margin-top: 20px; }
        .summary-item { text-align: center; padding: 15px; border: 1px solid #ddd; flex: 1; margin: 0 5px; }
        .summary-item:first-child { margin-left: 0; }
        .summary-item:last-child { margin-right: 0; }
        .summary-item .amount { font-size: 18px; font-weight: bold; color: #333; }
        .summary-item .label { font-size: 10px; color: #666; text-transform: uppercase; }
        
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 10px; color: #666; }
        .footer-row { display: flex; justify-content: space-between; }
        
        .signature-section { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature { text-align: center; width: 200px; }
        .signature-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .container { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 14px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px;">
            🖨️ Print Statement
        </button>
        <button onclick="window.close()" style="padding: 10px 30px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            ✖️ Close
        </button>
    </div>
    
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>WINDEEP FINANCE</h1>
            <p>Loan Account Statement</p>
            <p style="margin-top: 5px;">Statement Date: <?= format_date(date('Y-m-d')) ?></p>
        </div>
        
        <!-- Loan Details -->
        <div class="section">
            <div class="section-title">Loan Account Details</div>
            <table class="info-table">
                <tr>
                    <td class="label">Loan Number:</td>
                    <td class="value"><?= $loan->loan_number ?></td>
                    <td class="label">Loan Status:</td>
                    <td class="value">
                        <span class="badge badge-<?= $loan->status == 'active' ? 'success' : ($loan->status == 'closed' ? 'info' : 'warning') ?>">
                            <?= strtoupper($loan->status) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="label">Loan Product:</td>
                    <td class="value"><?= $loan->product_name ?? 'N/A' ?></td>
                    <td class="label">Disbursement Date:</td>
                    <td class="value"><?= format_date($loan->disbursement_date) ?></td>
                </tr>
                <tr>
                    <td class="label">Principal Amount:</td>
                    <td class="value"><?= format_amount($loan->principal_amount) ?></td>
                    <td class="label">Interest Rate:</td>
                    <td class="value"><?= $loan->interest_rate ?>% p.a.</td>
                </tr>
                <tr>
                    <td class="label">Monthly EMI:</td>
                    <td class="value"><?= format_amount($loan->emi_amount) ?></td>
                    <td class="label">Tenure:</td>
                    <td class="value"><?= $loan->tenure_months ?> Months</td>
                </tr>
            </table>
        </div>
        
        <!-- Member Details -->
        <div class="section">
            <div class="section-title">Borrower Details</div>
            <table class="info-table">
                <tr>
                    <td class="label">Member Code:</td>
                    <td class="value"><?= $member->member_code ?></td>
                    <td class="label">Phone:</td>
                    <td class="value"><?= $member->phone ?></td>
                </tr>
                <tr>
                    <td class="label">Name:</td>
                    <td class="value"><?= $member->first_name ?> <?= $member->last_name ?></td>
                    <td class="label">Email:</td>
                    <td class="value"><?= $member->email ?: '-' ?></td>
                </tr>
                <tr>
                    <td class="label">Address:</td>
                    <td class="value" colspan="3">
                        <?php
                        $address_parts = [];
                        if (!empty($member->address_line1)) $address_parts[] = $member->address_line1;
                        if (!empty($member->address_line2)) $address_parts[] = $member->address_line2;
                        if (!empty($member->city)) $address_parts[] = $member->city;
                        if (!empty($member->state)) $address_parts[] = $member->state;
                        if (!empty($member->pincode)) $address_parts[] = $member->pincode;
                        echo !empty($address_parts) ? implode(', ', $address_parts) : '-';
                        ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Summary -->
        <div class="summary-box">
            <div class="summary-item">
                <div class="amount"><?= format_amount($loan->principal_amount) ?></div>
                <div class="label">Principal Amount</div>
            </div>
            <div class="summary-item">
                <div class="amount"><?= format_amount($loan->total_interest ?? 0) ?></div>
                <div class="label">Total Interest</div>
            </div>
            <div class="summary-item">
                <div class="amount"><?= format_amount($loan->total_amount_paid ?? 0) ?></div>
                <div class="label">Total Paid</div>
            </div>
            <div class="summary-item">
                <div class="amount" style="color: <?= ($loan->outstanding_principal ?? 0) > 0 ? '#dc3545' : '#28a745' ?>">
                    <?= format_amount($loan->outstanding_principal ?? 0) ?>
                </div>
                <div class="label">Outstanding Balance</div>
            </div>
        </div>
        
        <!-- EMI Schedule -->
        <div class="section" style="margin-top: 30px;">
            <div class="section-title">EMI Schedule</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>EMI #</th>
                        <th>Due Date</th>
                        <th class="text-right">EMI Amount</th>
                        <th class="text-right">Principal</th>
                        <th class="text-right">Interest</th>
                        <th class="text-right">Paid</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $CI =& get_instance();
                    $installments = $CI->db->where('loan_id', $loan->id)
                                           ->order_by('installment_number', 'ASC')
                                           ->get('loan_installments')
                                           ->result();
                    
                    $total_emi = 0;
                    $total_principal = 0;
                    $total_interest = 0;
                    $total_paid = 0;
                    
                    foreach ($installments as $inst): 
                        $total_emi += $inst->emi_amount;
                        $total_principal += $inst->principal_amount;
                        $total_interest += $inst->interest_amount;
                        $total_paid += $inst->total_paid ?? 0;
                    ?>
                    <tr>
                        <td><?= $inst->installment_number ?></td>
                        <td><?= format_date($inst->due_date) ?></td>
                        <td class="text-right"><?= format_amount($inst->emi_amount) ?></td>
                        <td class="text-right"><?= format_amount($inst->principal_amount) ?></td>
                        <td class="text-right"><?= format_amount($inst->interest_amount) ?></td>
                        <td class="text-right"><?= format_amount($inst->total_paid ?? 0) ?></td>
                        <td>
                            <span class="badge badge-<?= 
                                $inst->status == 'paid' ? 'success' : 
                                ($inst->status == 'partial' ? 'warning' : 
                                ($inst->status == 'overdue' || (safe_timestamp($inst->due_date) < time() && $inst->status == 'pending') ? 'danger' : 'info'))
                            ?>">
                                <?= strtoupper($inst->status) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Total</th>
                        <th class="text-right"><?= format_amount($total_emi) ?></th>
                        <th class="text-right"><?= format_amount($total_principal) ?></th>
                        <th class="text-right"><?= format_amount($total_interest) ?></th>
                        <th class="text-right"><?= format_amount($total_paid) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Payment History -->
        <div class="section" style="margin-top: 30px;">
            <div class="section-title">Payment History</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Receipt #</th>
                        <th class="text-right">Amount</th>
                        <th>Mode</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $payments = $CI->db->where('loan_id', $loan->id)
                                       ->where('is_reversed', 0)
                                       ->order_by('payment_date', 'ASC')
                                       ->get('loan_payments')
                                       ->result();
                    
                    if (empty($payments)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #666;">No payments recorded</td>
                    </tr>
                    <?php else: 
                        foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= format_date($payment->payment_date) ?></td>
                        <td><?= $payment->receipt_number ?? '-' ?></td>
                        <td class="text-right"><?= format_amount($payment->total_amount) ?></td>
                        <td><?= ucfirst($payment->payment_mode) ?></td>
                        <td><?= $payment->reference_number ?: '-' ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature">
                <div class="signature-line">Borrower Signature</div>
            </div>
            <div class="signature">
                <div class="signature-line">Authorized Signatory</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-row">
                <span>Generated on: <?= format_date_time(date('Y-m-d H:i:s')) ?></span>
                <span>This is a computer-generated document. No signature required.</span>
            </div>
            <p style="text-align: center; margin-top: 10px;">
                Windeep Finance | Contact: support@windeepfinance.com | www.windeepfinance.com
            </p>
        </div>
    </div>
</body>
</html>
