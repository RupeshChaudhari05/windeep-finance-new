<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#667eea">
    <title>Forgot Password - Windeep Finance</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 16px; margin: 0;
        }
        .fp-wrapper { width: 100%; max-width: 420px; }
        .brand { text-align: center; margin-bottom: 24px; }
        .brand-icon {
            width: 64px; height: 64px;
            background: rgba(255,255,255,0.2);
            border-radius: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 12px; backdrop-filter: blur(10px);
        }
        .brand-icon i { font-size: 28px; color: #fff; }
        .brand h1 { color: #fff; font-size: 1.5rem; font-weight: 700; margin: 0; letter-spacing: -0.5px; }
        .fp-card {
            background: #fff; border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden;
        }
        .fp-body { padding: 32px 24px 24px; }
        .fp-body h2 { font-size: 1.25rem; font-weight: 600; color: #212529; margin-bottom: 4px; text-align: center; }
        .fp-body .subtitle { color: #868e96; font-size: 0.85rem; margin-bottom: 24px; text-align: center; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 500; color: #495057; margin-bottom: 6px; }
        .input-icon-wrap { position: relative; }
        .input-icon-wrap input,
        .input-icon-wrap select {
            width: 100%; padding: 12px 14px 12px 42px; height: 48px;
            border-radius: 10px; border: 2px solid #e9ecef;
            font-size: 0.95rem; font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            -webkit-appearance: none; appearance: none;
            background: #fff;
        }
        .input-icon-wrap input:focus,
        .input-icon-wrap select:focus {
            border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
            outline: none;
        }
        .input-icon-wrap .input-icon {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%); color: #adb5bd; font-size: 1rem;
        }
        .btn-submit {
            width: 100%; height: 48px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none; border-radius: 10px; color: #fff;
            font-size: 1rem; font-weight: 600; cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: transform 0.15s, box-shadow 0.15s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(102,126,234,0.35); }
        .btn-submit:disabled { opacity: 0.7; transform: none; }
        .alert { border-radius: 10px; font-size: 0.85rem; border: none; padding: 12px 16px; margin-bottom: 16px; }
        .alert-danger { background: #fff5f5; color: #c92a2a; }
        .alert-success { background: #f0fff4; color: #2b8a3e; }
        .fp-footer { padding: 0 24px 20px; text-align: center; }
        .fp-footer a { color: #667eea; font-size: 0.85rem; text-decoration: none; font-weight: 500; }
        .fp-footer a:hover { text-decoration: underline; }
        .copyright { text-align: center; margin-top: 16px; color: rgba(255,255,255,0.5); font-size: 0.75rem; }
        @media (max-width: 480px) {
            body { padding: 12px; align-items: flex-start; padding-top: 40px; }
            .brand { margin-bottom: 16px; }
            .fp-body { padding: 24px 18px 18px; }
            .input-icon-wrap input,
            .input-icon-wrap select { height: 44px; font-size: 16px; }
            .btn-submit { height: 44px; }
        }
    </style>
</head>
<body>
<div class="fp-wrapper">
    <div class="brand">
        <div class="brand-icon"><i class="fas fa-landmark"></i></div>
        <h1>Windeep Finance</h1>
    </div>
    
    <div class="fp-card">
        <div class="fp-body">
            <h2><i class="fas fa-lock mr-2"></i>Forgot Password</h2>
            <p class="subtitle">Enter your email to receive a password reset link</p>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i><?= $error ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle mr-1"></i><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?= site_url('verify/process_forgot_password') ?>" id="fpForm">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                
                <div class="form-group">
                    <label>Account Type</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-user-tag input-icon"></i>
                        <select name="user_type">
                            <option value="member">Member</option>
                            <option value="admin">Admin / Staff</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" placeholder="Enter your registered email" required autofocus>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit" id="fpBtn">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
        </div>
        
        <div class="fp-footer">
            <a href="<?= site_url('admin/login') ?>"><i class="fas fa-arrow-left mr-1"></i>Back to Login</a>
        </div>
    </div>
    
    <div class="copyright">&copy; <?= date('Y') ?> Windeep Finance. All rights reserved.</div>
</div>

<script>
document.getElementById('fpForm').addEventListener('submit', function() {
    var btn = document.getElementById('fpBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
});
</script>
</body>
</html>
