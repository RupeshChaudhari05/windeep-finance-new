<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - Windeep Finance</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <style>
        .login-page { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .login-box { width: 400px; }
        .login-logo a { color: #fff; }
        .card { border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%); }
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
                <i class="fas fa-key mr-1"></i> Forgot your password?
                <br><small class="text-muted">Enter your email address and we'll send you a link to reset your password.</small>
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
            
            <form action="<?= site_url('admin/auth/send_reset') ?>" method="post">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                
                <div class="input-group mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Enter your registered email" required autofocus
                           title="Enter the email address associated with your admin account">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-paper-plane mr-1"></i> Send Reset Link
                        </button>
                    </div>
                </div>
            </form>
            
            <p class="mt-3 mb-0 text-center">
                <a href="<?= site_url('admin/auth') ?>"><i class="fas fa-arrow-left mr-1"></i> Back to Login</a>
            </p>
        </div>
    </div>
    
    <div class="text-center mt-3">
        <small class="text-white">&copy; <?= date('Y') ?> Windeep Finance. All rights reserved.</small>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
