<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Savings Statement - <?= $savings->account_number ?></title>
    <style>
        @page { size: A4; margin: 15mm; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #1a5276; }
        .header h2 { margin: 5px 0; font-size: 16px; color: #666; }
        .header p { margin: 3px 0; font-size: 11px; color: #888; }
        .info-section { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-box { width: 48%; }
        .info-box h4 { margin: 0 0 10px 0; padding-bottom: 5px; border-bottom: 1px solid #ddd; color: #1a5276; }
        .info-box table { width: 100%; }
        .info-box td { padding: 3px 0; }
        .info-box td:first-child { color: #666; width: 40%; }
        .info-box td:last-child { font-weight: bold; }
        .summary-cards { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .summary-card { width: 23%; text-align: center; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .summary-card .value { font-size: 18px; font-weight: bold; color: #1a5276; }
        .summary-card .label { font-size: 10px; color: #666; }
        .transactions { margin-top: 20px; }
        .transactions h3 { margin-bottom: 10px; color: #1a5276; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        table.statement { width: 100%; border-collapse: collapse; }
        table.statement th { background: #1a5276; color: white; padding: 8px; text-align: left; font-size: 11px; }
        table.statement td { border-bottom: 1px solid #ddd; padding: 6px 8px; font-size: 11px; }
        table.statement tr:nth-child(even) { background: #f9f9f9; }
        .credit { color: #28a745; }
        .debit { color: #dc3545; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 10px; color: #888; text-align: center; }
        .signature-section { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature-box { width: 30%; text-align: center; }
        .signature-box .line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; }
        @media print { 
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 14px; cursor: pointer; background: #1a5276; color: white; border: none; border-radius: 5px;">
            🖨️ Print Statement
        </button>
        <button onclick="window.close()" style="padding: 10px 30px; font-size: 14px; cursor: pointer; background: #666; color: white; border: none; border-radius: 5px; margin-left: 10px;">
            ✕ Close
        </button>
    </div>
    
    <div class="header">
        <h1><?= $company_name ?? 'Windeep Finance' ?></h1>
        <h2>Savings Account Statement</h2>
        <p><?= $company_address ?? 'Financial Services' ?> | <?= $company_phone ?? '' ?></p>
    </div>
    
    <div class="info-section">
        <div class="info-box">
            <h4>Account Information</h4>
            <table>
                <tr>
                    <td>Account Number:</td>
                    <td><?= $savings->account_number ?></td>
                </tr>
                <tr>
                    <td>Scheme:</td>
                    <td><?= $savings->scheme_name ?></td>
                </tr>
                <tr>
                    <td>Interest Rate:</td>
                    <td><?= number_format($savings->interest_rate, 2) ?>% p.a.</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td><?= ucfirst($savings->status) ?></td>
                </tr>
            </table>
        </div>
        <div class="info-box">
            <h4>Member Information</h4>
            <table>
                <tr>
                    <td>Name:</td>
                    <td><?= $member->full_name ?></td>
                </tr>
                <tr>
                    <td>Member ID:</td>
                    <td><?= $member->member_number ?></td>
                </tr>
                <tr>
                    <td>Phone:</td>
                    <td><?= $member->phone ?></td>
                </tr>
                <tr>
                    <td>Email:</td>
                    <td><?= $member->email ?? '-' ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="info-section">
        <div class="info-box">
            <h4>Statement Period</h4>
            <table>
                <tr>
                    <td>From:</td>
                    <td><?= date('d M Y', strtotime($from_date ?? $savings->created_at)) ?></td>
                </tr>
                <tr>
                    <td>To:</td>
                    <td><?= date('d M Y', strtotime($to_date ?? 'now')) ?></td>
                </tr>
                <tr>
                    <td>Generated:</td>
                    <td><?= date('d M Y h:i A') ?></td>
                </tr>
            </table>
        </div>
        <div class="info-box">
            <h4>Account Summary</h4>
            <table>
                <tr>
                    <td>Opening Balance:</td>
                    <td><?= number_format($opening_balance ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <td>Total Deposits:</td>
                    <td class="credit">+ <?= number_format($total_deposits ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <td>Total Withdrawals:</td>
                    <td class="debit">- <?= number_format($total_withdrawals ?? 0, 2) ?></td>
                </tr>
                <tr>
                    <td>Interest Earned:</td>
                    <td class="credit">+ <?= number_format($interest_earned ?? 0, 2) ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="summary-cards">
        <div class="summary-card">
            <div class="value"><?= number_format($savings->current_balance, 2) ?></div>
            <div class="label">Current Balance</div>
        </div>
        <div class="summary-card">
            <div class="value"><?= $transaction_count ?? count($transactions) ?></div>
            <div class="label">Transactions</div>
        </div>
        <div class="summary-card">
            <div class="value"><?= number_format($total_deposits ?? 0, 2) ?></div>
            <div class="label">Total Deposits</div>
        </div>
        <div class="summary-card">
            <div class="value"><?= number_format($interest_earned ?? 0, 2) ?></div>
            <div class="label">Interest Earned</div>
        </div>
    </div>
    
    <div class="transactions">
        <h3>Transaction History</h3>
        <table class="statement">
            <thead>
                <tr>
                    <th width="80">Date</th>
                    <th>Description</th>
                    <th>Reference</th>
                    <th width="80">Debit</th>
                    <th width="80">Credit</th>
                    <th width="90">Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $running_balance = $opening_balance ?? 0;
                foreach ($transactions as $txn): 
                    if ($txn->type == 'deposit' || $txn->type == 'interest') {
                        $running_balance += $txn->amount;
                        $credit = $txn->amount;
                        $debit = 0;
                    } else {
                        $running_balance -= $txn->amount;
                        $credit = 0;
                        $debit = $txn->amount;
                    }
                ?>
                <tr>
                    <td><?= date('d M Y', strtotime($txn->transaction_date)) ?></td>
                    <td>
                        <?= ucfirst($txn->type) ?>
                        <?php if (isset($txn->description)): ?>
                        <br><small><?= $txn->description ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= $txn->reference ?? '-' ?></td>
                    <td class="debit"><?= $debit > 0 ? number_format($debit, 2) : '-' ?></td>
                    <td class="credit"><?= $credit > 0 ? number_format($credit, 2) : '-' ?></td>
                    <td><strong><?= number_format($running_balance, 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #1a5276; color: white;">
                    <td colspan="3"><strong>Closing Balance</strong></td>
                    <td><strong><?= number_format($total_withdrawals ?? 0, 2) ?></strong></td>
                    <td><strong><?= number_format(($total_deposits ?? 0) + ($interest_earned ?? 0), 2) ?></strong></td>
                    <td><strong><?= number_format($savings->current_balance, 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <div class="line">Member Signature</div>
        </div>
        <div class="signature-box">
            <div class="line">Authorized Signatory</div>
        </div>
        <div class="signature-box">
            <div class="line">Manager</div>
        </div>
    </div>
    
    <div class="footer">
        <p>This is a computer-generated statement and does not require a signature.</p>
        <p>For any queries, please contact us at <?= $company_phone ?? '' ?> or <?= $company_email ?? '' ?></p>
        <p>Generated on <?= date('d M Y h:i:s A') ?></p>
    </div>
</body>
</html>
