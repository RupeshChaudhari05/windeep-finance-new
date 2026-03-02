<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#4f46e5">
    <meta name="description" content="<?= $meta_description ?? 'Windeep Finance - Enterprise-grade microfinance and banking solution.' ?>">
    <title><?= $title ?? 'Windeep Finance' ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/agency.css') ?>">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="container nav-inner">
            <a href="<?= base_url() ?>" class="logo">
                <div class="logo-icon"><i class="fas fa-landmark"></i></div>
                Windeep Finance
            </a>
            <div class="nav-menu" id="navMenu">
                <a href="<?= base_url('#features') ?>" class="nav-link">Platform</a>
                <a href="<?= base_url('#solutions') ?>" class="nav-link">Solutions</a>
                <a href="<?= base_url('#integrations') ?>" class="nav-link">Integrations</a>
                <a href="<?= base_url('about') ?>" class="nav-link">About Us</a>
                <div style="margin-left: 20px; display: flex; gap: 12px;">
                    <a href="<?= site_url('member/login') ?>" class="btn btn-white" style="padding: 10px 20px; font-size: 0.9rem;">Member Login</a>
                    <a href="<?= site_url('admin/login') ?>" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.9rem;">Admin Portal <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <button class="mobile-toggle" onclick="document.getElementById('navMenu').classList.toggle('active')">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>
