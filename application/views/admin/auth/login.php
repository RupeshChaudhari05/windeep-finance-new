<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#667eea">
    <title>Admin Login - Windeep Finance</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            margin: 0;
        }
        .login-wrapper { width: 100%; max-width: 420px; }
        .brand { text-align: center; margin-bottom: 24px; }
        .brand-icon {
            width: 64px; height: 64px;
            background: rgba(255,255,255,0.2);
            border-radius: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
            backdrop-filter: blur(10px);
        }
        .brand-icon i { font-size: 28px; color: #fff; }
        .brand h1 { color: #fff; font-size: 1.5rem; font-weight: 700; margin: 0; letter-spacing: -0.5px; }
        .brand p { color: rgba(255,255,255,0.7); font-size: 0.85rem; margin: 4px 0 0; }
        .login-card {
            background: #fff; border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden;
        }
        .login-tabs { display: flex; border-bottom: 1px solid #e9ecef; }
        .login-tabs a {
            flex: 1; text-align: center; padding: 14px;
            font-size: 0.9rem; font-weight: 600; color: #868e96;
            text-decoration: none; position: relative; transition: all 0.2s;
        }
        .login-tabs a.active { color: #667eea; }
        .login-tabs a.active::after {
            content: ''; position: absolute; bottom: -1px; left: 20%; right: 20%;
            height: 3px; background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 3px 3px 0 0;
        }
        .login-tabs a:hover { color: #667eea; }
        .login-body { padding: 28px 24px 24px; }
        .login-body h2 { font-size: 1.25rem; font-weight: 600; color: #212529; margin-bottom: 4px; }
        .login-body .subtitle { color: #868e96; font-size: 0.85rem; margin-bottom: 24px; }
        .form-group label { font-size: 0.8rem; font-weight: 500; color: #495057; margin-bottom: 6px; }
        .input-icon-wrap { position: relative; }
        .input-icon-wrap .form-control {
            padding-left: 42px; height: 48px; border-radius: 10px;
            border: 2px solid #e9ecef; font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-icon-wrap .form-control:focus {
            border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
        }
        .input-icon-wrap .input-icon {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%); color: #adb5bd; font-size: 1rem;
        }
        .input-icon-wrap .toggle-pw {
            position: absolute; right: 14px; top: 50%;
            transform: translateY(-50%); color: #adb5bd;
            cursor: pointer; padding: 4px; z-index: 2;
        }
        .input-icon-wrap .toggle-pw:hover { color: #667eea; }
        .form-options {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; flex-wrap: wrap; gap: 8px;
        }
        .form-options .custom-checkbox label { font-size: 0.85rem; color: #495057; cursor: pointer; }
        .form-options a { font-size: 0.85rem; color: #667eea; text-decoration: none; font-weight: 500; }
        .form-options a:hover { text-decoration: underline; }
        .btn-login {
            width: 100%; height: 48px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none; border-radius: 10px; color: #fff;
            font-size: 1rem; font-weight: 600; cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(102,126,234,0.35); color: #fff; }
        .btn-login:active { transform: translateY(0); }
        .btn-login:disabled { opacity: 0.7; transform: none; }
        .alert { border-radius: 10px; font-size: 0.85rem; border: none; padding: 12px 16px; }
        .alert-danger { background: #fff5f5; color: #c92a2a; }
        .alert-success { background: #f0fff4; color: #2b8a3e; }
        .login-footer { padding: 0 24px 20px; text-align: center; }
        .login-footer a { color: #667eea; font-size: 0.85rem; text-decoration: none; font-weight: 500; }
        .login-footer a:hover { text-decoration: underline; }
        .copyright { text-align: center; margin-top: 16px; color: rgba(255,255,255,0.5); font-size: 0.75rem; }
        
        @media (max-width: 480px) {
            body { padding: 12px; align-items: flex-start; padding-top: 40px; }
            .brand { margin-bottom: 16px; }
            .brand-icon { width: 52px; height: 52px; }
            .brand-icon i { font-size: 22px; }
            .brand h1 { font-size: 1.3rem; }
            .login-body { padding: 20px 18px 18px; }
            .login-body h2 { font-size: 1.1rem; }
            .input-icon-wrap .form-control { height: 44px; font-size: 16px; }
            .btn-login { height: 44px; }
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="brand">
        <div class="brand-icon"><i class="fas fa-landmark"></i></div>
        <h1>Windeep Finance</h1>
        <p>Administration Panel</p>
    </div>
    
    <div class="login-card">
        <div class="login-tabs">
            <a href="#" class="active"><i class="fas fa-user-shield mr-1"></i>Admin</a>
            <a href="<?= site_url('member/login') ?>"><i class="fas fa-user mr-1"></i>Member</a>
        </div>
        
        <div class="login-body">
            <h2>Welcome back</h2>
            <p class="subtitle">Sign in to manage your organization</p>
            
            <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-1"></i>
                <?= $this->session->flashdata('error') ?>
            </div>
            <?php endif; ?>
            
            <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-1"></i>
                <?= $this->session->flashdata('success') ?>
            </div>
            <?php endif; ?>
            
            <form action="<?= site_url('admin/auth/login') ?>" method="post" id="loginForm">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required autofocus autocomplete="email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                        <span class="toggle-pw" onclick="togglePw()"><i class="fas fa-eye" id="pwIcon"></i></span>
                    </div>
                </div>
                
                <div class="form-options">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                        <label class="custom-control-label" for="remember">Remember me</label>
                    </div>
                    <a href="<?= site_url('verify/forgot_password') ?>">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            <a href="<?= base_url() ?>"><i class="fas fa-home mr-1"></i>Back to Home</a>
        </div>
    </div>
    
    <div class="copyright">&copy; <?= date('Y') ?> Windeep Finance. All rights reserved.</div>
</div>

<script>
function togglePw() {
    var pw = document.getElementById('password'), icon = document.getElementById('pwIcon');
    if (pw.type === 'password') { pw.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash'); }
    else { pw.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
}
document.getElementById('loginForm').addEventListener('submit', function() {
    var btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
});
</script>
</body>
</html>
