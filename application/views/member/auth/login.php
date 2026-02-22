<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Member Login' ?> - Windeep Finance</title>
    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE (includes Bootstrap) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        .login-page { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .login-box { margin-top: 50px; }
        .login-card-body { border-radius: 10px; }
    </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <a href="<?= base_url() ?>" style="color: white;">
            <b>Windeep</b> Finance<br>
            <small>Member Portal</small>
        </a>
    </div>
    
    <div class="card">
        <div class="card-body login-card-body">
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link active" href="#">Member</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= site_url('admin/login') ?>">Admin</a>
                </li>
            </ul>
            <p class="login-box-msg">Sign in to access your account</p>
            
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

            <?php if ($this->input->get('logged_out')): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                You have been logged out successfully.
            </div>
            <?php endif; ?>
            
            <form action="<?= site_url('member/auth/login') ?>" method="post" id="loginForm">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" name="identifier" placeholder="Enter your Member Code (e.g. WF001)" required autofocus data-toggle="tooltip" data-placement="top" title="Your unique Member Code provided during registration. Contact admin if you don't know your code.">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" class="form-control" name="password" id="loginPassword" placeholder="Password" required data-toggle="tooltip" data-placement="top" title="Enter your password. If logging in for the first time, use your Member Code as the default password.">
                    <div class="input-group-append" style="cursor:pointer" onclick="togglePassword()">
                        <div class="input-group-text">
                            <span class="fas fa-eye" id="togglePwdIcon"></span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember" data-toggle="tooltip" title="Keep you signed in on this device for 30 days">
                                Remember Me
                            </label>
                        </div>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block" id="loginBtn">Sign In</button>
                    </div>
                </div>
            </form>
            
            <p class="mt-3 mb-1">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> First time? Use your Member Code as password, then change it after login.
                </small>
            </p>
            <p class="mb-0">
                <a href="<?= site_url('admin/login') ?>" class="text-center">Admin Login</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
$(function() {
    $('[data-toggle="tooltip"]').tooltip();
});
function togglePassword() {
    var pwd = document.getElementById('loginPassword');
    var icon = document.getElementById('togglePwdIcon');
    if (pwd.type === 'password') { pwd.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash'); }
    else { pwd.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
}
$('#loginForm').on('submit', function() {
    $('#loginBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Signing in...');
});
</script>
</body>
</html>
