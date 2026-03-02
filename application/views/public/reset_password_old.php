<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Windeep Finance</title>
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
        .alert {
            border-radius: 8px;
        }
        .password-toggle {
            position: relative;
        }
        .password-toggle .toggle-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: #666;
            cursor: pointer;
        }
        .password-requirements {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        .requirement {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }
        .requirement i {
            margin-right: 8px;
            width: 16px;
        }
        .requirement.valid {
            color: #28a745;
        }
        .requirement.invalid {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <h2><i class="fas fa-key me-2"></i>Reset Password</h2>
        <p class="subtitle">Enter your new password below</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= base_url('verify/process_reset_password') ?>" id="resetForm">
            <input type="hidden" name="token" value="<?= $token ?>">
            
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <div class="password-toggle">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter new password" required minlength="8">
                    <button type="button" class="toggle-btn" onclick="togglePassword('password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-requirements" id="requirements">
                    <div class="requirement" data-req="length">
                        <i class="fas fa-circle"></i> At least 8 characters
                    </div>
                    <div class="requirement" data-req="upper">
                        <i class="fas fa-circle"></i> One uppercase letter
                    </div>
                    <div class="requirement" data-req="lower">
                        <i class="fas fa-circle"></i> One lowercase letter
                    </div>
                    <div class="requirement" data-req="number">
                        <i class="fas fa-circle"></i> One number
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <div class="password-toggle">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm new password" required>
                    <button type="button" class="toggle-btn" onclick="togglePassword('confirm_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="requirement mt-2" id="match-status">
                    <i class="fas fa-circle"></i> Passwords match
                </div>
            </div>
            
            <button type="submit" class="btn btn-reset" id="submitBtn" disabled>
                <i class="fas fa-save me-2"></i>Reset Password
            </button>
        </form>
    </div>
    
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        
        function checkRequirements() {
            const val = password.value;
            const checks = {
                length: val.length >= 8,
                upper: /[A-Z]/.test(val),
                lower: /[a-z]/.test(val),
                number: /[0-9]/.test(val)
            };
            
            let allValid = true;
            for (const [key, valid] of Object.entries(checks)) {
                const req = document.querySelector(`[data-req="${key}"]`);
                if (valid) {
                    req.classList.add('valid');
                    req.classList.remove('invalid');
                    req.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    req.classList.remove('valid');
                    req.classList.add('invalid');
                    req.querySelector('i').className = 'fas fa-times-circle';
                    allValid = false;
                }
            }
            
            const matching = val && confirm.value && val === confirm.value;
            const matchStatus = document.getElementById('match-status');
            if (matching) {
                matchStatus.classList.add('valid');
                matchStatus.classList.remove('invalid');
                matchStatus.querySelector('i').className = 'fas fa-check-circle';
            } else if (confirm.value) {
                matchStatus.classList.remove('valid');
                matchStatus.classList.add('invalid');
                matchStatus.querySelector('i').className = 'fas fa-times-circle';
            }
            
            submitBtn.disabled = !(allValid && matching);
        }
        
        password.addEventListener('input', checkRequirements);
        confirm.addEventListener('input', checkRequirements);
    </script>
</body>
</html>
