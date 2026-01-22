<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Windeep Finance</title>
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
        .reset-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            padding: 50px;
            max-width: 450px;
            width: 100%;
        }
        .reset-card h2 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        .reset-card .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.2);
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
            color: white;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
        .alert {
            border-radius: 8px;
        }
        .form-select {
            padding: 12px 15px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <h2><i class="fas fa-lock me-2"></i>Forgot Password</h2>
        <p class="subtitle">Enter your email to receive a password reset link</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?= $success ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= base_url('verify/process_forgot_password') ?>">
            <div class="mb-3">
                <label class="form-label">Account Type</label>
                <select name="user_type" class="form-select">
                    <option value="member">Member</option>
                    <option value="admin">Admin / Staff</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            
            <button type="submit" class="btn btn-reset">
                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
            </button>
        </form>
        
        <div class="back-link">
            <a href="<?= base_url('members/login') ?>"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>
