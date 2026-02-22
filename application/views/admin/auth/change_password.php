<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password - Windeep Finance</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <style>
        .login-page { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .login-box { width: 420px; }
        .login-logo a { color: #fff; }
        .card { border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%); }
        .password-requirements { font-size: 0.8rem; color: #6c757d; }
        .password-requirements li.valid { color: #28a745; }
    </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <a href="#"><b>Windeep</b> Finance</a>
    </div>
    
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">
                <i class="fas fa-shield-alt mr-1"></i> Change Your Password
                <br><small class="text-muted">For security, please update your password regularly.</small>
            </p>
            
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('error') ?>
                </div>
            <?php endif; ?>
            
            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?= $this->session->flashdata('success') ?>
                </div>
            <?php endif; ?>
            
            <form action="<?= site_url('admin/auth/change_password') ?>" method="post" id="changePwdForm">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                
                <div class="input-group mb-3">
                    <input type="password" name="current_password" id="current_password" class="form-control" 
                           placeholder="Current Password" required
                           title="Enter your current password to verify your identity">
                    <div class="input-group-append">
                        <div class="input-group-text" style="cursor:pointer" onclick="togglePassword('current_password')">
                            <span class="fas fa-eye" id="current_password-eye"></span>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="input-group mb-3">
                    <input type="password" name="new_password" id="new_password" class="form-control" 
                           placeholder="New Password" required minlength="6"
                           title="Choose a strong password with at least 6 characters">
                    <div class="input-group-append">
                        <div class="input-group-text" style="cursor:pointer" onclick="togglePassword('new_password')">
                            <span class="fas fa-eye" id="new_password-eye"></span>
                        </div>
                    </div>
                </div>
                
                <ul class="password-requirements list-unstyled mb-2">
                    <li id="req-length"><i class="fas fa-circle fa-xs mr-1"></i> At least 6 characters</li>
                </ul>
                
                <div class="input-group mb-3">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                           placeholder="Confirm New Password" required
                           title="Re-enter the same new password for confirmation">
                    <div class="input-group-append">
                        <div class="input-group-text" style="cursor:pointer" onclick="togglePassword('confirm_password')">
                            <span class="fas fa-eye" id="confirm_password-eye"></span>
                        </div>
                    </div>
                </div>
                
                <div id="match-error" class="text-danger small mb-2" style="display:none;">
                    <i class="fas fa-exclamation-circle"></i> Passwords do not match
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <a href="<?= site_url('admin/dashboard') ?>" class="btn btn-secondary btn-block">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </a>
                    </div>
                    <div class="col-6">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save mr-1"></i> Update
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="text-center mt-3">
        <small class="text-white">&copy; <?= date('Y') ?> Windeep Finance. All rights reserved.</small>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
function togglePassword(fieldId) {
    var field = document.getElementById(fieldId);
    var eye = document.getElementById(fieldId + '-eye');
    if (field.type === 'password') {
        field.type = 'text';
        eye.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        eye.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.getElementById('new_password').addEventListener('input', function() {
    var req = document.getElementById('req-length');
    if (this.value.length >= 6) {
        req.classList.add('valid');
        req.querySelector('i').classList.replace('fa-circle', 'fa-check-circle');
    } else {
        req.classList.remove('valid');
        req.querySelector('i').classList.replace('fa-check-circle', 'fa-circle');
    }
});

document.getElementById('confirm_password').addEventListener('input', function() {
    var pwd = document.getElementById('new_password').value;
    var matchErr = document.getElementById('match-error');
    matchErr.style.display = (this.value && this.value !== pwd) ? 'block' : 'none';
});

document.getElementById('changePwdForm').addEventListener('submit', function(e) {
    var pwd = document.getElementById('new_password').value;
    var confirm = document.getElementById('confirm_password').value;
    if (pwd !== confirm) {
        e.preventDefault();
        document.getElementById('match-error').style.display = 'block';
    }
});
</script>
</body>
</html>
