<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Message' ?> - <?= $company_name ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .message-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            padding: 50px;
            max-width: 500px;
            text-align: center;
        }
        .icon-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }
        .icon-circle.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .icon-circle.error {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
        }
        .icon-circle.warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        .icon-circle i {
            font-size: 48px;
            color: white;
        }
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }
        .message-text {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn-action {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
            color: white;
        }
        .company-logo {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="message-card">
        <div class="company-logo">
            <h4 style="color: #667eea; margin: 0;"><?= $company_name ?></h4>
        </div>
        
        <div class="icon-circle <?= $type ?>">
            <?php if ($type === 'success'): ?>
                <i class="fas fa-check"></i>
            <?php elseif ($type === 'error'): ?>
                <i class="fas fa-times"></i>
            <?php else: ?>
                <i class="fas fa-exclamation"></i>
            <?php endif; ?>
        </div>
        
        <h1><?= $title ?></h1>
        <p class="message-text"><?= $message ?></p>
        
        <?php if (!empty($redirect_url)): ?>
            <a href="<?= base_url($redirect_url) ?>" class="btn btn-action btn-primary-custom">
                <i class="fas fa-sign-in-alt me-2"></i> Go to Login
            </a>
        <?php else: ?>
            <a href="<?= base_url() ?>" class="btn btn-action btn-primary-custom">
                <i class="fas fa-home me-2"></i> Go to Home
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
