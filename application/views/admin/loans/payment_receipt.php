<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Payment Receipt - <?= $payment->payment_code ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= base_url('assets/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/dist/css/adminlte.min.css') ?>">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f6f9;
        }
        .receipt-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .receipt-header {
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .receipt-title {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            text-align: center;
            margin-bottom: 10px;
        }
        .receipt-code {
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-label {
            font-weight: bold;
            color: #333;
            display: inline-block;
            min-width: 150px;
        }
        .info-value {
            color: #666;
        }
        .amount-box {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 20px 0;
        }
        .amount-box .total-amount {
            font-size: 32px;
            font-weight: bold;
            color: #28a745;
        }
        .amount-breakdown {
            margin-top: 15px;
        }
        .amount-breakdown table {
            width: 100%;
        }
        .amount-breakdown td {
            padding: 5px;
        }
        .receipt-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
        }
        .signature-line {
            border-top: 2px solid #333;
            width: 200px;
            margin: 50px auto 10px;
        }
        @media print {
            body { background: white; }
            .receipt-container {
                box-shadow: none;
                margin: 0;
                padding: 20px;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Print Button -->
        <div class="text-right mb-3 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print mr-2"></i>Print Receipt
            </button>
            <button onclick="window.close()" class="btn btn-secondary ml-2">
                <i class="fas fa-times mr-2"></i>Close
            </button>
        </div>
        
        <!-- Receipt Header -->
        <div class="receipt-header">
            <div class="receipt-title">
                <i class="fas fa-receipt mr-2"></i>PAYMENT RECEIPT
            </div>
            <div class="receipt-code">
                Receipt No: <strong><?= $payment->payment_code ?></strong>
            </div>
        </div>
        
        <!-- Payment Information -->
        <div class="info-section">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-user mr-2"></i>Member Details</h5>
                    <p>
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?= $payment->first_name ?> <?= $payment->last_name ?></span>
                    </p>
                    <p>
                        <span class="info-label">Member Code:</span>
                        <span class="info-value"><?= $payment->member_code ?></span>
                    </p>
                    <p>
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?= $payment->phone ?></span>
                    </p>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="fas fa-file-contract mr-2"></i>Loan Details</h5>
                    <p>
                        <span class="info-label">Loan Number:</span>
                        <span class="info-value"><?= $payment->loan_number ?></span>
                    </p>
                    <p>
                        <span class="info-label">Payment Date:</span>
                        <span class="info-value"><?= format_date($payment->payment_date) ?></span>
                    </p>
                    <p>
                        <span class="info-label">Payment Type:</span>
                        <span class="info-value"><?= ucfirst(str_replace('_', ' ', $payment->payment_type)) ?></span>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Amount Box -->
        <div class="amount-box">
            <div class="text-center">
                <div style="font-size: 14px; color: #666; margin-bottom: 5px;">Total Amount Paid</div>
                <div class="total-amount"><?= format_amount($payment->total_amount) ?></div>
                <div style="font-size: 12px; color: #999; margin-top: 5px;">
                    (<?= ucwords(convert_number_to_words($payment->total_amount)) ?> Rupees Only)
                </div>
            </div>
            
            <div class="amount-breakdown">
                <table>
                    <tr>
                        <td class="info-label">Principal Amount:</td>
                        <td class="text-right info-value"><?= format_amount($payment->principal_component) ?></td>
                    </tr>
                    <tr>
                        <td class="info-label">Interest Amount:</td>
                        <td class="text-right info-value"><?= format_amount($payment->interest_component) ?></td>
                    </tr>
                    <?php if ($payment->fine_component > 0): ?>
                    <tr>
                        <td class="info-label">Fine/Penalty:</td>
                        <td class="text-right info-value text-danger"><?= format_amount($payment->fine_component) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($payment->excess_amount > 0): ?>
                    <tr>
                        <td class="info-label">Excess Amount:</td>
                        <td class="text-right info-value text-success"><?= format_amount($payment->excess_amount) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <!-- Payment Details -->
        <div class="info-section">
            <h5 class="mb-3"><i class="fas fa-credit-card mr-2"></i>Payment Details</h5>
            <div class="row">
                <div class="col-md-6">
                    <p>
                        <span class="info-label">Payment Mode:</span>
                        <span class="info-value"><?= ucfirst(str_replace('_', ' ', $payment->payment_mode)) ?></span>
                    </p>
                    <?php if ($payment->reference_number): ?>
                    <p>
                        <span class="info-label">Reference Number:</span>
                        <span class="info-value"><?= $payment->reference_number ?></span>
                    </p>
                    <?php endif; ?>
                    <?php if ($payment->cheque_number): ?>
                    <p>
                        <span class="info-label">Cheque Number:</span>
                        <span class="info-value"><?= $payment->cheque_number ?></span>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <?php if ($payment->bank_name): ?>
                    <p>
                        <span class="info-label">Bank Name:</span>
                        <span class="info-value"><?= $payment->bank_name ?></span>
                    </p>
                    <?php endif; ?>
                    <?php if ($payment->cheque_date): ?>
                    <p>
                        <span class="info-label">Cheque Date:</span>
                        <span class="info-value"><?= format_date($payment->cheque_date) ?></span>
                    </p>
                    <?php endif; ?>
                    <?php if ($payment->receipt_number): ?>
                    <p>
                        <span class="info-label">Receipt Number:</span>
                        <span class="info-value"><?= $payment->receipt_number ?></span>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($payment->narration): ?>
            <p>
                <span class="info-label">Narration:</span>
                <span class="info-value"><?= $payment->narration ?></span>
            </p>
            <?php endif; ?>
        </div>
        
        <!-- Outstanding Balance -->
        <div class="card card-info">
            <div class="card-body">
                <strong><i class="fas fa-info-circle mr-2"></i>Outstanding Balance After Payment:</strong>
                <div class="mt-2">
                    Principal: <?= format_amount($payment->outstanding_principal_after) ?> | 
                    Interest: <?= format_amount($payment->outstanding_interest_after) ?>
                </div>
            </div>
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div>Member Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div>Authorized Signature</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="receipt-footer">
            <p>This is a computer-generated receipt and does not require a physical signature.</p>
            <p>Printed on: <?= format_date_time(date('Y-m-d H:i:s')) ?></p>
            <p><strong>Thank you for your payment!</strong></p>
        </div>
    </div>
    
    <script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/dist/js/adminlte.min.js') ?>"></script>
</body>
</html>

<?php
// Helper function to convert number to words
function convert_number_to_words($number) {
    $amount = floor($number);
    $words = array(
        '0' => '', '1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four',
        '5' => 'five', '6' => 'six', '7' => 'seven', '8' => 'eight', '9' => 'nine',
        '10' => 'ten', '11' => 'eleven', '12' => 'twelve', '13' => 'thirteen',
        '14' => 'fourteen', '15' => 'fifteen', '16' => 'sixteen', '17' => 'seventeen',
        '18' => 'eighteen', '19' => 'nineteen', '20' => 'twenty', '30' => 'thirty',
        '40' => 'forty', '50' => 'fifty', '60' => 'sixty', '70' => 'seventy',
        '80' => 'eighty', '90' => 'ninety'
    );
    
    if ($amount == 0) return 'zero';
    
    $result = '';
    
    // Crores
    if ($amount >= 10000000) {
        $crore = floor($amount / 10000000);
        $result .= convert_number_to_words($crore) . ' crore ';
        $amount %= 10000000;
    }
    
    // Lakhs
    if ($amount >= 100000) {
        $lakh = floor($amount / 100000);
        $result .= convert_number_to_words($lakh) . ' lakh ';
        $amount %= 100000;
    }
    
    // Thousands
    if ($amount >= 1000) {
        $thousand = floor($amount / 1000);
        $result .= convert_number_to_words($thousand) . ' thousand ';
        $amount %= 1000;
    }
    
    // Hundreds
    if ($amount >= 100) {
        $hundred = floor($amount / 100);
        $result .= $words[$hundred] . ' hundred ';
        $amount %= 100;
    }
    
    // Tens and ones
    if ($amount > 0) {
        if ($amount < 20) {
            $result .= $words[$amount];
        } else {
            $tens = floor($amount / 10) * 10;
            $ones = $amount % 10;
            $result .= $words[$tens];
            if ($ones > 0) {
                $result .= ' ' . $words[$ones];
            }
        }
    }
    
    return trim($result);
}
?>
