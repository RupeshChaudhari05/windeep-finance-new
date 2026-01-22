<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Member Ledger - <?= $member->member_code ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h2 {
            margin: 5px 0;
            color: #333;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section table {
            width: 100%;
        }
        .info-section td {
            padding: 5px;
        }
        .summary-box {
            background: #f0f0f0;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .summary-box table {
            width: 100%;
        }
        .summary-box td {
            padding: 8px;
            font-weight: bold;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .ledger-table th {
            background: #4CAF50;
            color: white;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .ledger-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .ledger-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-danger {
            color: #dc3545;
        }
        .text-success {
            color: #28a745;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #333;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        @media print {
            body {
                margin: 10px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2><?= $this->settings['company_name'] ?? 'Finance Company' ?></h2>
        <h3>Member Ledger Statement</h3>
        <p>Period: <?= $from_date ? date('d M Y', strtotime($from_date)) : 'Beginning' ?> to <?= $to_date ? date('d M Y', strtotime($to_date)) : date('d M Y') ?></p>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td width="25%"><strong>Member Code:</strong></td>
                <td width="25%"><?= $member->member_code ?></td>
                <td width="25%"><strong>Phone:</strong></td>
                <td width="25%"><?= $member->phone ?></td>
            </tr>
            <tr>
                <td><strong>Member Name:</strong></td>
                <td><?= $member->first_name ?> <?= $member->last_name ?></td>
                <td><strong>Status:</strong></td>
                <td><?= ucfirst($member->status) ?></td>
            </tr>
            <tr>
                <td><strong>Address:</strong></td>
                <td colspan="3"><?= $member->address_line1 ?>, <?= $member->city ?> - <?= $member->pincode ?></td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <table>
            <tr>
                <td width="25%">Opening Balance:</td>
                <td width="25%">₹<?= number_format($summary['opening_balance'], 2) ?></td>
                <td width="25%">Total Debit:</td>
                <td width="25%" class="text-danger">₹<?= number_format($summary['total_debit'], 2) ?></td>
            </tr>
            <tr>
                <td>Total Credit:</td>
                <td class="text-success">₹<?= number_format($summary['total_credit'], 2) ?></td>
                <td>Closing Balance:</td>
                <td class="<?= $summary['closing_balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                    ₹<?= number_format(abs($summary['closing_balance']), 2) ?> <?= $summary['closing_balance'] >= 0 ? 'Cr' : 'Dr' ?>
                </td>
            </tr>
        </table>
    </div>

    <table class="ledger-table">
        <thead>
            <tr>
                <th width="10%">Date</th>
                <th width="15%">Type</th>
                <th width="12%">Reference</th>
                <th width="28%">Narration</th>
                <th width="12%" class="text-right">Debit (Dr)</th>
                <th width="12%" class="text-right">Credit (Cr)</th>
                <th width="11%" class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ledger as $entry): ?>
            <tr>
                <td><?= date('d M Y', strtotime($entry->transaction_date)) ?></td>
                <td><?= ucfirst(str_replace('_', ' ', $entry->transaction_type)) ?></td>
                <td><?= $entry->reference_type ? ucfirst($entry->reference_type) . ' #' . $entry->reference_id : '-' ?></td>
                <td><?= $entry->narration ?: '-' ?></td>
                <td class="text-right text-danger">
                    <?= $entry->debit_amount > 0 ? '₹' . number_format($entry->debit_amount, 2) : '-' ?>
                </td>
                <td class="text-right text-success">
                    <?= $entry->credit_amount > 0 ? '₹' . number_format($entry->credit_amount, 2) : '-' ?>
                </td>
                <td class="text-right">
                    <span class="<?= $entry->balance_after >= 0 ? 'text-success' : 'text-danger' ?>">
                        ₹<?= number_format(abs($entry->balance_after), 2) ?> <?= $entry->balance_after >= 0 ? 'Cr' : 'Dr' ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">Total:</th>
                <th class="text-right text-danger">₹<?= number_format($summary['total_debit'], 2) ?></th>
                <th class="text-right text-success">₹<?= number_format($summary['total_credit'], 2) ?></th>
                <th class="text-right">₹<?= number_format($summary['closing_balance'], 2) ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Generated on: <?= date('d M Y h:i A') ?></p>
        <p>&copy; <?= date('Y') ?> <?= $this->settings['company_name'] ?? 'Finance Company' ?>. All rights reserved.</p>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer;">
            Print Statement
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #666; color: white; border: none; cursor: pointer; margin-left: 10px;">
            Close
        </button>
    </div>
</body>
</html>
