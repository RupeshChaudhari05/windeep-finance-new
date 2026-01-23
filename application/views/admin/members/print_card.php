<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Card - <?= $member->member_code ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .card-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
        }
        
        .member-card {
            width: 85.6mm;
            height: 54mm;
            margin: 20px auto;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            padding: 15px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }
        
        .member-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .member-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid white;
            background: white;
            object-fit: cover;
        }
        
        .card-body {
            position: relative;
            z-index: 1;
        }
        
        .member-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .member-code {
            font-size: 14px;
            background: rgba(255,255,255,0.2);
            display: inline-block;
            padding: 3px 10px;
            border-radius: 5px;
            margin-bottom: 8px;
        }
        
        .member-details {
            font-size: 11px;
            line-height: 1.6;
        }
        
        .member-details div {
            margin-bottom: 2px;
        }
        
        .card-footer {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.3);
            font-size: 10px;
            display: flex;
            justify-content: space-between;
        }
        
        .print-actions {
            text-align: center;
            margin: 20px 0;
        }
        
        .btn {
            padding: 10px 30px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .print-actions {
                display: none;
            }
            
            .card-container {
                padding: 0;
            }
            
            .member-card {
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }
        }
        
        .back-card {
            background: #f8f9fa;
            color: #333;
            padding: 15px;
        }
        
        .back-card h3 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #667eea;
        }
        
        .back-card .info-row {
            font-size: 11px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        
        .back-card .info-label {
            font-weight: bold;
            color: #666;
        }
        
        .signature-section {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #ccc;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            width: 100px;
            margin: 20px auto 5px;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="print-actions">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Card
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
        
        <!-- Front Side -->
        <div class="member-card">
            <div class="card-header">
                <div class="company-name">Windeep Finance</div>
                <?php if (!empty($member->profile_image)): ?>
                    <img src="<?= base_url('uploads/profile_images/' . $member->profile_image) ?>" alt="Photo" class="member-photo">
                <?php else: ?>
                    <div class="member-photo" style="display: flex; align-items: center; justify-content: center; font-size: 20px; color: #667eea;">
                        <?= strtoupper(substr($member->first_name, 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-body">
                <div class="member-name"><?= $member->first_name ?> <?= $member->last_name ?></div>
                <div class="member-code">ID: <?= $member->member_code ?></div>
                
                <div class="member-details">
                    <div><strong>Phone:</strong> <?= $member->phone ?></div>
                    <?php if (!empty($member->email)): ?>
                    <div><strong>Email:</strong> <?= $member->email ?></div>
                    <?php endif; ?>
                    <div><strong>Address:</strong> <?= $member->address_line1 ?>, <?= $member->city ?></div>
                </div>
            </div>
            
            <div class="card-footer">
                <div>Member Since: <?= date('M Y', strtotime($member->join_date)) ?></div>
                <div>Status: <?= ucfirst($member->status) ?></div>
            </div>
        </div>
        
        <!-- Back Side -->
        <div class="member-card back-card">
            <h3>Member Information</h3>
            
            <div class="info-row">
                <span class="info-label">Member Code:</span>
                <span><?= $member->member_code ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Date of Birth:</span>
                <span><?= !empty($member->date_of_birth) ? date('d M Y', strtotime($member->date_of_birth)) : 'N/A' ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Gender:</span>
                <span><?= !empty($member->gender) ? ucfirst($member->gender) : 'N/A' ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Occupation:</span>
                <span><?= !empty($member->occupation) ? $member->occupation : 'N/A' ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Emergency Contact:</span>
                <span><?= !empty($member->emergency_contact) ? $member->emergency_contact : 'N/A' ?></span>
            </div>
            
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
            
            <div style="text-align: center; margin-top: 10px; font-size: 9px; color: #999;">
                This card is property of Windeep Finance. If found, please return.
            </div>
        </div>
    </div>
</body>
</html>
