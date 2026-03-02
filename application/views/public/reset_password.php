<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#667eea">
    <title>Reset Password - Windeep Finance</title>
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
        .rp-wrapper { width: 100%; max-width: 420px; }
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
        .rp-card {
            background: #fff; border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15); overflow: hidden;
        }
        .rp-body { padding: 32px 24px 24px; }
        .rp-body h2 { font-size: 1.25rem; font-weight: 600; color: #212529; margin-bottom: 4px; text-align: center; }
        .rp-body .subtitle { color: #868e96; font-size: 0.85rem; margin-bottom: 24px; text-align: center; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 500; color: #495057; margin-bottom: 6px; }
        .input-icon-wrap { position: relative; }
        .input-icon-wrap input {
            width: 100%; padding: 12px 42px 12px 42px; height: 48px;
            border-radius: 10px; border: 2px solid #e9ecef;
            font-size: 0.95rem; font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-icon-wrap input:focus {
            border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
            outline: none;
        }
        .input-icon-wrap .input-icon {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%); color: #adb5bd; font-size: 1rem;
        }
        .input-icon-wrap .toggle-pw {
            position: absolute; right: 14px; top: 50%;
            transform: translateY(-50%); color: #adb5bd;
            cursor: pointer; padding: 4px; background: none; border: none; z-index: 2;
        }
        .input-icon-wrap .toggle-pw:hover { color: #667eea; }
        .btn-submit {
            width: 100%; height: 48px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none; border-radius: 10px; color: #fff;
            font-size: 1rem; font-weight: 600; cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: transform 0.15s, box-shadow 0.15s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 20px;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(102,126,234,0.35); }
        .btn-submit:disabled { opacity: 0.5; transform: none; cursor: not-allowed; }
        .alert { border-radius: 10px; font-size: 0.85rem; border: none; padding: 12px 16px; margin-bottom: 16px; }
        .alert-danger { background: #fff5f5; color: #c92a2a; }
        .pw-reqs { margin-top: 10px; }
        .pw-req {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.8rem; color: #868e96; padding: 3px 0;
        }
        .pw-req i { width: 14px; text-align: center; }
        .pw-req.valid { color: #2b8a3e; }
        .pw-req.valid i { color: #2b8a3e; }
        .pw-req.invalid { color: #c92a2a; }
        .pw-req.invalid i { color: #c92a2a; }
        .rp-footer { padding: 0 24px 20px; text-align: center; }
        .rp-footer a { color: #667eea; font-size: 0.85rem; text-decoration: none; font-weight: 500; }
        .rp-footer a:hover { text-decoration: underline; }
        .copyright { text-align: center; margin-top: 16px; color: rgba(255,255,255,0.5); font-size: 0.75rem; }
        @media (max-width: 480px) {
            body { padding: 12px; align-items: flex-start; padding-top: 40px; }
            .brand { margin-bottom: 16px; }
            .rp-body { padding: 24px 18px 18px; }
            .input-icon-wrap input { height: 44px; font-size: 16px; }
            .btn-submit { height: 44px; }
        }
    </style>
</head>
<body>
<div class="rp-wrapper">
    <div class="brand">
        <div class="brand-icon"><i class="fas fa-landmark"></i></div>
        <h1>Windeep Finance</h1>
    </div>
    
    <div class="rp-card">
        <div class="rp-body">
            <h2><i class="fas fa-key mr-2"></i>Reset Password</h2>
            <p class="subtitle">Enter your new password below</p>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?= site_url('verify/process_reset_password') ?>" id="resetForm">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="token" value="<?= $token ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Enter new password" required minlength="8">
                        <button type="button" class="toggle-pw" onclick="togglePw('password', this)"><i class="fas fa-eye"></i></button>
                    </div>
                    <div class="pw-reqs" id="pw-reqs">
                        <div class="pw-req" data-req="length"><i class="fas fa-circle" style="font-size:6px"></i> At least 8 characters</div>
                        <div class="pw-req" data-req="upper"><i class="fas fa-circle" style="font-size:6px"></i> One uppercase letter</div>
                        <div class="pw-req" data-req="lower"><i class="fas fa-circle" style="font-size:6px"></i> One lowercase letter</div>
                        <div class="pw-req" data-req="number"><i class="fas fa-circle" style="font-size:6px"></i> One number</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)"><i class="fas fa-eye"></i></button>
                    </div>
                    <div class="pw-req mt-2" id="match-status"><i class="fas fa-circle" style="font-size:6px"></i> Passwords match</div>
                </div>
                
                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>
        </div>
        
        <div class="rp-footer">
            <a href="<?= site_url('admin/login') ?>"><i class="fas fa-arrow-left mr-1"></i>Back to Login</a>
        </div>
    </div>
    
    <div class="copyright">&copy; <?= date('Y') ?> Windeep Finance. All rights reserved.</div>
</div>

<script>
function togglePw(id, btn) {
    var inp = document.getElementById(id), icon = btn.querySelector('i');
    if (inp.type === 'password') { inp.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash'); }
    else { inp.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
}

var pw = document.getElementById('password');
var cpw = document.getElementById('confirm_password');
var submitBtn = document.getElementById('submitBtn');

function checkReqs() {
    var val = pw.value;
    var checks = {
        length: val.length >= 8,
        upper: /[A-Z]/.test(val),
        lower: /[a-z]/.test(val),
        number: /[0-9]/.test(val)
    };
    var allValid = true;
    for (var key in checks) {
        var el = document.querySelector('[data-req="' + key + '"]');
        if (checks[key]) {
            el.className = 'pw-req valid';
            el.querySelector('i').className = 'fas fa-check-circle';
        } else {
            el.className = 'pw-req invalid';
            el.querySelector('i').className = 'fas fa-times-circle';
            allValid = false;
        }
    }
    var matching = val && cpw.value && val === cpw.value;
    var ms = document.getElementById('match-status');
    if (matching) {
        ms.className = 'pw-req mt-2 valid';
        ms.querySelector('i').className = 'fas fa-check-circle';
    } else if (cpw.value) {
        ms.className = 'pw-req mt-2 invalid';
        ms.querySelector('i').className = 'fas fa-times-circle';
    }
    submitBtn.disabled = !(allValid && matching);
}

pw.addEventListener('input', checkReqs);
cpw.addEventListener('input', checkReqs);
</script>
</body>
</html>
