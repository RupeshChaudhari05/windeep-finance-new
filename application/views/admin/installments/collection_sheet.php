<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>EMI Collection Sheet - <?= format_date($date) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; }
        .info { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background: #f0f0f0; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background: #f9f9f9; }
        .signature-section { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature-box { width: 200px; }
        .signature-line { border-top: 1px solid #000; margin-top: 40px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 10px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            🖨️ Print Collection Sheet
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            ✖️ Close
        </button>
    </div>
    
    <div class="header">
        <h2>WINDEEP FINANCE</h2>
        <h3>EMI Collection Sheet</h3>
        <p>Date: <strong><?= format_date($date, 'd M Y (l)') ?></strong></p>
    </div>
    
    <div class="info">
        <p><strong>Total Members:</strong> <?= count($installments) ?> | <strong>Expected Collection:</strong> <?= format_amount($total_expected) ?></p>
        <p><strong>Generated on:</strong> <?= format_date_time(date('Y-m-d H:i:s')) ?></p>
    </div>
    
    <?php if (empty($installments)): ?>
        <p style="text-align: center; padding: 50px;">No EMI due on this date</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th width="30">#</th>
                    <th>Member Code</th>
                    <th>Member Name</th>
                    <th>Loan Number</th>
                    <th class="text-center">EMI #</th>
                    <th class="text-right">EMI Amount</th>
                    <th class="text-right">Paid</th>
                    <th class="text-right">Balance</th>
                    <th>Phone</th>
                    <th width="80">Signature</th>
                </tr>
            </thead>
            <tbody>
                <?php $sr = 1; $total_paid = 0; foreach ($installments as $inst): ?>
                <?php 
                $balance = $inst->emi_amount - $inst->total_paid;
                $total_paid += $inst->total_paid;
                ?>
                <tr>
                    <td class="text-center"><?= $sr++ ?></td>
                    <td><?= $inst->member_code ?></td>
                    <td><?= $inst->first_name ?> <?= $inst->last_name ?></td>
                    <td><?= $inst->loan_number ?></td>
                    <td class="text-center">#<?= $inst->installment_number ?></td>
                    <td class="text-right"><?= format_amount($inst->emi_amount) ?></td>
                    <td class="text-right"><?= format_amount($inst->total_paid) ?></td>
                    <td class="text-right"><?= format_amount($balance) ?></td>
                    <td><?= $inst->phone ?></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-right">Total:</td>
                    <td class="text-right"><?= format_amount($total_expected) ?></td>
                    <td class="text-right"><?= format_amount($total_paid) ?></td>
                    <td class="text-right"><?= format_amount($total_expected - $total_paid) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
    
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <p class="text-center">Collected By</p>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <p class="text-center">Verified By</p>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <p class="text-center">Manager</p>
        </div>
    </div>
    
    <p style="margin-top: 30px; font-size: 10px; color: #666; text-align: center;">
        This is a computer-generated document. Verify all amounts before collection.
    </p>
</body>
</html>
