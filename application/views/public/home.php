<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#1a56db">
    <link rel="icon" type="image/x-icon" href="<?= base_url('assets/favicon.ico') ?>">
    <meta name="description" content="Windeep Finance – 100% genuine loans, savings schemes & investment plans. Apply online instantly. RBI compliant. App available on Android & iOS.">
    <meta name="keywords" content="personal loan, home loan, savings scheme, gold loan, microfinance, instant loan, loan app, windeep finance">
    <title><?= $title ?? 'Windeep Finance – Loans, Savings & Investments | 100% Genuine' ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --orange:      #d35400;
            --orange-dark: #a63900;
            --gold:        #f59e0b;
            --amber:       #f6ad55;
            --red:         #c2410c;
            --dark:        #3d1f0a;
            --gray:        #6b7280;
            --light:       #fff7ed;
            --border:      #f5e6d8;
            --white:       #ffffff;
            --fark:        #af5811;       
            --grad:        linear-gradient(135deg, #d35400 0%, #f59e0b 100%);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', sans-serif;
            color: var(--dark);
            background: var(--white);
            line-height: 1.65;
            overflow-x: hidden;
        }
        h1,h2,h3,h4,h5 { font-family: 'Nunito', sans-serif; font-weight: 800; line-height: 1.2; }
        a { text-decoration: none; transition: color 0.2s; }
        ul { list-style: none; }
        img { max-width: 100%; }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .section { padding: 80px 0; }
        .section-alt { background: var(--light); }

        .badge-pill {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(26,86,219,0.1); color: var(--blue);
            padding: 6px 16px; border-radius: 999px;
            font-size: 0.8rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.8px; margin-bottom: 16px;
        }

        /* ─── TOPBAR ─────────────────────────────────── */
        .topbar {
            background: var(--fark); color: #fff;
            text-align: center; padding: 8px 20px; font-size: 0.85rem;
        }
        .topbar a { color: #93c5fd; font-weight: 600; }

        /* ─── NAVBAR ─────────────────────────────────── */
        .navbar {
            position: sticky; top: 0; z-index: 999;
            background: #fff; border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .nav-inner {
            display: flex; align-items: center;
            justify-content: space-between; padding: 14px 20px;
            max-width: 1200px; margin: 0 auto;
        }
        .logo { display: flex; align-items: center; gap: 10px; font-family: 'Nunito', sans-serif; font-weight: 900; font-size: 1.5rem; color: var(--orange); }
        .logo-box {
            width: 38px; height: 38px; border-radius: 9px;
            background: var(--grad); display: flex; align-items: center;
            justify-content: center; color: #fff; font-size: 1.1rem;
        }
        .nav-links { display: flex; align-items: center; gap: 32px; }
        .nav-links a { color: var(--dark); font-weight: 600; font-size: 0.95rem; }
        .nav-links a:hover { color: var(--orange); }
        .nav-cta { display: flex; gap: 10px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 26px; border-radius: 10px; font-weight: 700;
            font-size: 0.95rem; cursor: pointer; border: none; transition: all 0.25s;
        }
        .btn-primary { background: var(--orange); color: #fff; }
        .btn-primary:hover { background: var(--orange-dark); transform: translateY(-1px); }
        .btn-outline { background: transparent; color: var(--orange); border: 2px solid var(--orange); }
        .btn-outline:hover { background: var(--orange); color: #fff; }
        .btn-lg { padding: 15px 36px; font-size: 1.05rem; border-radius: 12px; }
        .btn-white { background: #fff; color: var(--orange); font-weight: 700; }
        .btn-white:hover { background: #fff3e2; }

        .hamburger { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--dark); }
        .mobile-btns { display: none; }

        /* ─── HERO ───────────────────────────────────── */
        .hero {
            background: linear-gradient(160deg, #eff6ff 0%, #e0f2fe 50%, #f0fdf4 100%);
            padding: 90px 0 80px; position: relative; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute;
            top: -200px; right: -200px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(26,86,219,0.08) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
        .hero-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            background: #fff; border: 1px solid var(--border);
            padding: 8px 18px; border-radius: 999px; font-size: 0.875rem; font-weight: 600;
            color: var(--orange-dark); margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .hero-title { font-size: 3.2rem; color: var(--dark); margin-bottom: 20px; }
        .hero-title span { color: var(--blue); }
        .hero-sub { color: var(--gray); font-size: 1.1rem; margin-bottom: 36px; max-width: 520px; }
        .hero-btns { display: flex; gap: 14px; flex-wrap: wrap; }
        .trust-row { display: flex; align-items: center; gap: 24px; margin-top: 36px; flex-wrap: wrap; }
        .trust-item { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600; color: var(--dark); }
        .trust-item i { color: var(--green); }

        /* Hero Card Stack */
        .hero-visual { position: relative; }
        .card-stack { position: relative; }
        .stat-card {
            background: #fff; border-radius: 18px; padding: 22px 26px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 1px solid var(--border);
        }
        .stat-card .label { font-size: 0.8rem; color: var(--gray); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .stat-card .value { font-size: 2rem; font-weight: 900; font-family: 'Nunito', sans-serif; color: var(--dark); }
        .stat-card .sub { font-size: 0.82rem; color: var(--gold); font-weight: 600; margin-top: 4px; }
        .floating-badge {
            position: absolute; background: #fff; border-radius: 14px;
            padding: 12px 18px; box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 0.9rem;
            border: 1px solid var(--border);
        }
        .fb-top { top: -20px; right: -10px; animation: floatY 4s ease-in-out infinite; }
        .fb-bot { bottom: -20px; left: -10px; animation: floatY 4s ease-in-out infinite reverse; }
        @keyframes floatY { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
        .icon-circle {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }

        /* ─── LOAN TYPES ─────────────────────────────── */
        .loans-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .loan-card {
            background: #fff; border: 1px solid var(--border); border-radius: 18px;
            padding: 30px 24px; transition: all 0.3s; cursor: default;
            position: relative; overflow: hidden;
        }
        .loan-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: var(--grad); transform: scaleX(0); transform-origin: left; transition: transform 0.3s;
        }
        .loan-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); border-color: transparent; }
        .loan-card:hover::before { transform: scaleX(1); }
        .loan-icon {
            width: 56px; height: 56px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 20px;
        }
        .loan-card h3 { font-size: 1.15rem; margin-bottom: 10px; }
        .loan-card p { color: var(--gray); font-size: 0.9rem; margin-bottom: 16px; }
        .loan-meta { display: flex; gap: 12px; flex-wrap: wrap; }
        .loan-tag {
            background: var(--light); color: var(--orange); font-size: 0.78rem;
            font-weight: 700; padding: 4px 12px; border-radius: 999px; border: 1px solid var(--border);
        }

        /* ─── PROCESS ────────────────────────────────── */
        .process-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 30px; position: relative; }
        .process-grid::before {
            content: ''; position: absolute; top: 36px; left: 10%; right: 10%; height: 2px;
            background: linear-gradient(90deg, var(--orange) 0%, var(--gold) 100%);
            z-index: 0;
        }
        .step-card { text-align: center; position: relative; z-index: 1; }
        .step-num {
            width: 72px; height: 72px; border-radius: 50%; background: var(--grad);
            color: #fff; font-size: 1.4rem; font-weight: 900; font-family: 'Nunito', sans-serif;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px; box-shadow: 0 8px 20px rgba(26,86,219,0.3);
        }
        .step-card h4 { font-size: 1rem; margin-bottom: 8px; }
        .step-card p { color: var(--gray); font-size: 0.875rem; }

        /* ─── SAVINGS ────────────────────────────────── */
        .savings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center; }
        .savings-list li {
            display: flex; align-items: flex-start; gap: 14px;
            padding: 18px 0; border-bottom: 1px solid var(--border);
        }
        .savings-list li:last-child { border-bottom: none; }
        .s-icon {
            width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        }
        .s-text h4 { font-size: 1rem; margin-bottom: 4px; }
        .s-text p { color: var(--gray); font-size: 0.875rem; }

        /* ─── APP DOWNLOAD ───────────────────────────── */
        .app-section {
            background: var(--grad); color: #fff; padding: 80px 0;
        }
        .app-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
        .app-section h2 { color: #fff; font-size: 2.5rem; margin-bottom: 16px; }
        .app-section p { color: rgba(255,255,255,0.85); font-size: 1.05rem; margin-bottom: 32px; }
        .app-badges { display: flex; gap: 16px; flex-wrap: wrap; }
        .app-badge {
            display: flex; align-items: center; gap: 12px;
            background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25);
            padding: 12px 22px; border-radius: 12px; color: #fff; font-weight: 700;
            transition: background 0.2s;
        }
        .app-badge:hover { background: rgba(255,255,255,0.22); }
        .app-badge i { font-size: 1.6rem; }
        .app-badge .small { font-size: 0.7rem; font-weight: 400; display: block; opacity: 0.8; }
        .app-badge .big { font-size: 1rem; font-weight: 800; display: block; }
        .app-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; margin-top: 36px; }
        .app-stat { text-align: center; }
        .app-stat .num { font-size: 2rem; font-weight: 900; font-family: 'Nunito', sans-serif; color: #fff; }
        .app-stat .lbl { font-size: 0.8rem; color: rgba(255,255,255,0.7); }
        .phone-mockup {
            background: rgba(255,255,255,0.1); border-radius: 36px; padding: 20px;
            border: 2px solid rgba(255,255,255,0.2); text-align: center;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
        }
        .phone-screen { background: rgba(255,255,255,0.95); border-radius: 24px; padding: 24px; }

        /* ─── TESTIMONIALS ───────────────────────────── */
        .testi-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 24px; }
        .testi-card {
            background: #fff; border: 1px solid var(--border); border-radius: 18px;
            padding: 28px; transition: box-shadow 0.3s;
        }
        .testi-card:hover { box-shadow: 0 12px 30px rgba(0,0,0,0.07); }
        .stars { color: #f59e0b; font-size: 0.85rem; margin-bottom: 14px; letter-spacing: 2px; }
        .testi-text { color: var(--dark); font-size: 0.95rem; line-height: 1.7; margin-bottom: 20px; }
        .testi-author { display: flex; align-items: center; gap: 12px; }
        .avatar {
            width: 44px; height: 44px; border-radius: 50%; background: var(--grad);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 800; font-size: 1rem;
        }
        .author-name { font-weight: 700; font-size: 0.95rem; }
        .author-loc { font-size: 0.8rem; color: var(--gray); }

        /* ─── WHY GENUINE ────────────────────────────── */
        .genuine-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 24px; }
        .genuine-card { text-align: center; padding: 32px 20px; background: #fff; border-radius: 18px; border: 1px solid var(--border); }
        .genuine-card .g-icon {
            width: 64px; height: 64px; border-radius: 18px; margin: 0 auto 20px;
            display: flex; align-items: center; justify-content: center; font-size: 1.6rem;
        }
        .genuine-card h4 { font-size: 1rem; margin-bottom: 8px; }
        .genuine-card p { color: var(--gray); font-size: 0.875rem; }

        /* ─── CTA BANNER ─────────────────────────────── */
        .cta-banner {
            background: var(--dark); color: #fff;
            padding: 80px 0; text-align: center;
        }
        .cta-banner h2 { color: #fff; font-size: 2.4rem; margin-bottom: 16px; }
        .cta-banner p { color: rgba(255,255,255,0.7); font-size: 1.05rem; margin-bottom: 36px; }
        .cta-btns { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }

        /* ─── CONTENT / SEO SECTION ──────────────────── */
        .content-section { padding: 80px 0; background: var(--light); }
        .content-box { max-width: 900px; margin: 0 auto; }
        .content-box h2 { margin-bottom: 20px; }
        .content-box p { color: var(--gray); margin-bottom: 16px; line-height: 1.8; }
        .content-box a { color: var(--blue); font-weight: 600; }
        .content-box a:hover { text-decoration: underline; }

        /* ─── FOOTER ─────────────────────────────────── */
        .footer { background: var(--dark); color: rgba(255,255,255,0.7); padding: 60px 0 30px; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 48px; }
        .footer .logo { color: #fff; margin-bottom: 16px; display: inline-flex; }
        .footer-desc { font-size: 0.9rem; max-width: 280px; line-height: 1.7; }
        .footer h5 { color: #fff; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a { color: rgba(255,255,255,0.6); font-size: 0.9rem; }
        .footer-links a:hover { color: #fff; }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1); padding-top: 28px;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px; font-size: 0.85rem;
        }
        .footer-bottom a { color: rgba(255,255,255,0.5); }
        .footer-bottom a:hover { color: #fff; }

        /* ─── RESPONSIVE ─────────────────────────────── */
        @media (max-width: 1024px) {
            .loans-grid { grid-template-columns: repeat(2,1fr); }
            .genuine-grid { grid-template-columns: repeat(2,1fr); }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .hero-grid, .savings-grid, .app-grid { grid-template-columns: 1fr; }
            .hero-visual { display: none; }
            .hero-title { font-size: 2.2rem; }
            .loans-grid, .testi-grid { grid-template-columns: 1fr; }
            .process-grid { grid-template-columns: repeat(2,1fr); }
            .process-grid::before { display: none; }
            .nav-links, .nav-cta { display: none; }
            .hamburger { display: block; }
            .nav-links.open {
                display: flex; flex-direction: column; align-items: flex-start;
                position: absolute; top: 100%; left: 0; right: 0;
                background: #fff; border-bottom: 1px solid var(--border);
                padding: 20px; gap: 16px; box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            }
            .nav-links.open .mobile-btns {
                display: flex; flex-direction: column; gap: 10px; width: 100%; padding-top: 8px; border-top: 1px solid var(--border);
            }
            .nav-links.open .mobile-btns .btn { width: 100%; justify-content: center; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; text-align: center; }
            .app-stats { grid-template-columns: repeat(3,1fr); }
        }
    </style>
</head>
<body>

<!-- Top Announcement Bar -->
<div class="topbar">
    🎉 Instant Loan Approval in 24 Hours — No Hidden Charges. <a href="<?= site_url('admin/login') ?>">Apply Now &rarr;</a>
</div>

<!-- Navbar -->
<nav class="navbar">
    <div class="nav-inner">
        <a href="<?= base_url('home') ?>" class="logo">
            <div class="logo-box">
              <img src="<?= base_url('assets/logo-icon.png') ?>" alt="Windeep Logo" style="width:20px; height:20px;">
            </div>
            Windeep Finance
        </a>
        <div class="nav-links" id="navLinks">
            <a href="#loans">Loans</a>
            <a href="#savings">Savings</a>
            <a href="#process">How It Works</a>
            <a href="#app">App</a>
            <a href="#contact">Contact</a>
            <div class="mobile-btns">
                <a href="<?= site_url('member/login') ?>" class="btn btn-outline">Member Login</a>
                <a href="<?= site_url('admin/login') ?>" class="btn btn-primary">Apply Now</a>
            </div>
        </div>
        <div class="nav-cta">
            <a href="<?= site_url('member/login') ?>" class="btn btn-outline">Member Login</a>
            <a href="<?= site_url('admin/login') ?>" class="btn btn-primary">Apply Now</a>
        </div>
        <button class="hamburger" onclick="document.getElementById('navLinks').classList.toggle('open')">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>

<!-- ─── HERO ──────────────────────────────────────────── -->
<section class="hero">
    <div class="container">
        <div class="hero-grid">
            <div data-aos="fade-up">
                <div class="hero-eyebrow">
                    <i class="fas fa-check-circle" style="color:var(--green)"></i>
                    100% Genuine &amp; RBI Compliant Finance Company
                </div>
                <h1 class="hero-title">
                    Fast Loans,<br>Smart <span>Savings</span>,<br>Secure Future.
                </h1>
                <p class="hero-sub">
                    Windeep Finance offers personal loans, business loans, savings accounts and investment schemes — all managed through our secure platform and mobile app.
                </p>
                <div class="hero-btns">
                    <a href="#loans" class="btn btn-primary btn-lg">
                        <i class="fas fa-hand-holding-usd"></i> Explore Loans
                    </a>
                    <a href="#app" class="btn btn-outline btn-lg">
                        <i class="fas fa-mobile-alt"></i> Download App
                    </a>
                </div>
                <div class="trust-row">
                    <div class="trust-item"><i class="fas fa-shield-alt"></i> Bank-Grade Security</div>
                    <div class="trust-item"><i class="fas fa-bolt"></i> 24-hr Disbursal</div>
                    <div class="trust-item"><i class="fas fa-star"></i> 4.8 ★ Rated App</div>
                </div>
            </div>
            <div class="hero-visual" data-aos="fade-left">
                <div class="card-stack">
                    <div class="stat-card">
                        <div class="label">Total Disbursed This Year</div>
                        <div class="value">₹ 48.6 Cr</div>
                        <div class="sub"><i class="fas fa-arrow-up"></i> 22% growth over last year</div>
                        <div style="height:80px; margin-top:18px; background:linear-gradient(180deg,rgba(211,84,0,0.12) 0%,transparent 100%); border-bottom:2px solid var(--orange); border-radius:4px;"></div>
                    </div>
                    <div class="floating-badge fb-top" style="background:#f0fdf4; border-color:#bbf7d0;">
                        <div class="icon-circle" style="background:#ffedd5;"><i class="fas fa-check" style="color:var(--orange)"></i></div>
                        <div>
                            <div style="font-size:0.7rem;color:var(--gray);font-weight:600;">Loan Approved</div>
                            <div style="color:var(--green);">₹2,50,000 · MEMB001</div>
                        </div>
                    </div>
                    <div class="floating-badge fb-bot" style="background:#eff6ff; border-color:#bfdbfe;">
                        <div class="icon-circle" style="background:#dbeafe;"><i class="fas fa-piggy-bank" style="color:var(--blue)"></i></div>
                        <div>
                            <div style="font-size:0.7rem;color:var(--gray);font-weight:600;">Monthly Savings</div>
                            <div style="color:var(--blue);">₹5,000 · Auto-debit ON</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── LOAN PRODUCTS ──────────────────────────────────── -->
<section class="section" id="loans">
    <div class="container">
        <div style="text-align:center; margin-bottom:50px;" data-aos="fade-up">
            <span class="badge-pill"><i class="fas fa-hand-holding-usd"></i> Our Loan Products</span>
            <h2 style="font-size:2.2rem;">Loans For Every Need</h2>
            <p style="color:var(--gray); margin-top:12px; max-width:560px; margin-left:auto; margin-right:auto;">
                From personal emergencies to business growth — we have a loan product tailored for you with minimal documentation.
            </p>
        </div>
        <div class="loans-grid">
            <!-- Personal Loan -->
            <div class="loan-card" data-aos="fade-up" data-aos-delay="0">
                <div class="loan-icon" style="background:#eff6ff; color:var(--blue)"><i class="fas fa-user-tie"></i></div>
                <h3>Personal Loan</h3>
                <p>Meet medical, travel, or personal expenses instantly. Minimal paperwork, fast disbursement.</p>
                <div class="loan-meta">
                    <span class="loan-tag">Up to ₹5 Lakh</span>
                    <span class="loan-tag">@12% p.a.</span>
                    <span class="loan-tag">24-hr Disbursal</span>
                </div>
            </div>
            <!-- Business Loan -->
            <div class="loan-card" data-aos="fade-up" data-aos-delay="80">
                <div class="loan-icon" style="background:#f0fdf4; color:var(--green)"><i class="fas fa-store"></i></div>
                <h3>Business Loan</h3>
                <p>Expand your business, buy equipment, or manage working capital with flexible repayment.</p>
                <div class="loan-meta">
                    <span class="loan-tag">Up to ₹25 Lakh</span>
                    <span class="loan-tag">Flexible EMI</span>
                    <span class="loan-tag">No Collateral</span>
                </div>
            </div>
            <!-- Home Loan -->
            <div class="loan-card" data-aos="fade-up" data-aos-delay="160">
                <div class="loan-icon" style="background:#fff7ed; color:#ea580c"><i class="fas fa-home"></i></div>
                <h3>Home / Property Loan</h3>
                <p>Build your dream home or invest in property with long-tenure, low-interest home loans.</p>
                <div class="loan-meta">
                    <span class="loan-tag">Up to ₹50 Lakh</span>
                    <span class="loan-tag">Long Tenure</span>
                    <span class="loan-tag">Low Interest</span>
                </div>
            </div>
            <!-- Gold Loan -->
            <div class="loan-card" data-aos="fade-up" data-aos-delay="0">
                <div class="loan-icon" style="background:#fffbeb; color:#b45309"><i class="fas fa-coins"></i></div>
                <h3>Gold Loan</h3>
                <p>Get instant cash against your gold jewelry. Safe custody, easy release on repayment.</p>
                <div class="loan-meta">
                    <span class="loan-tag">Instant Cash</span>
                    <span class="loan-tag">Safe Custody</span>
                    <span class="loan-tag">@9% p.a.</span>
                </div>
            </div>
            <!-- Group Loan -->
            <div class="loan-card" data-aos="fade-up" data-aos-delay="80">
                <div class="loan-icon" style="background:#f5f3ff; color:#7c3aed"><i class="fas fa-users"></i></div>
                <h3>Group / SHG Loan</h3>
                <p>Joint-liability group loans for Self-Help Groups and women entrepreneurs. Low rates.</p>
                <div class="loan-meta">
                    <span class="loan-tag">SHG Friendly</span>
                    <span class="loan-tag">Low Collateral</span>
                    <span class="loan-tag">Quick Sanction</span>
                </div>
            </div>
            <!-- Emergency Loan -->
            <div class="loan-card" data-aos="fade-up" data-aos-delay="160">
                <div class="loan-icon" style="background:#fef2f2; color:var(--red)"><i class="fas fa-ambulance"></i></div>
                <h3>Emergency Loan</h3>
                <p>Medical or urgent financial needs? Get funds disbursed within hours, no questions asked.</p>
                <div class="loan-meta">
                    <span class="loan-tag">Same-Day</span>
                    <span class="loan-tag">No Guarantor</span>
                    <span class="loan-tag">Members Only</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── HOW IT WORKS ───────────────────────────────────── -->
<section class="section section-alt" id="process">
    <div class="container">
        <div style="text-align:center; margin-bottom:60px;" data-aos="fade-up">
            <span class="badge-pill"><i class="fas fa-route"></i> Simple Process</span>
            <h2 style="font-size:2.2rem;">Get a Loan in 4 Easy Steps</h2>
        </div>
        <div class="process-grid">
            <div class="step-card" data-aos="fade-up" data-aos-delay="0">
                <div class="step-num">1</div>
                <h4>Register / Login</h4>
                <p>Create your member account or login via our app or website in minutes.</p>
            </div>
            <div class="step-card" data-aos="fade-up" data-aos-delay="100">
                <div class="step-num">2</div>
                <h4>Choose Loan Type</h4>
                <p>Select the loan product that fits your need and fill in the simple application form.</p>
            </div>
            <div class="step-card" data-aos="fade-up" data-aos-delay="200">
                <div class="step-num">3</div>
                <h4>Document Upload</h4>
                <p>Upload Aadhaar, PAN & income proof digitally. No physical visit required.</p>
            </div>
            <div class="step-card" data-aos="fade-up" data-aos-delay="300">
                <div class="step-num">4</div>
                <h4>Disbursal</h4>
                <p>Approved funds hit your bank account directly within 24 hours of approval.</p>
            </div>
        </div>
    </div>
</section>

<!-- ─── SAVINGS & DEPOSITS ────────────────────────────── -->
<section class="section" id="savings">
    <div class="container">
        <div class="savings-grid">
            <div data-aos="fade-right">
                <span class="badge-pill"><i class="fas fa-piggy-bank"></i> Savings Schemes</span>
                <h2 style="font-size:2.2rem; margin-bottom:16px;">Grow Your Money Systematically</h2>
                <p style="color:var(--gray); margin-bottom:30px;">
                    Start saving as little as ₹500/month and watch your money grow with competitive interest rates. All deposits are fully secure and insured.
                </p>
                <ul class="savings-list">
                    <li>
                        <div class="s-icon" style="background:#eff6ff; color:var(--blue)"><i class="fas fa-calendar-check"></i></div>
                        <div class="s-text">
                            <h4>Monthly Recurring Deposit</h4>
                            <p>Save a fixed amount every month. Earn up to 8.5% p.a. interest.</p>
                        </div>
                    </li>
                    <li>
                        <div class="s-icon" style="background:#f0fdf4; color:var(--green)"><i class="fas fa-lock"></i></div>
                        <div class="s-text">
                            <h4>Fixed Deposit (FD)</h4>
                            <p>Invest a lump sum for 1–5 years. Interest up to 9% p.a. guaranteed.</p>
                        </div>
                    </li>
                    <li>
                        <div class="s-icon" style="background:#fffbeb; color:#b45309"><i class="fas fa-star"></i></div>
                        <div class="s-text">
                            <h4>Security Deposit Scheme</h4>
                            <p>One-time deposit that earns interest and also acts as loan collateral.</p>
                        </div>
                    </li>
                    <li>
                        <div class="s-icon" style="background:#f5f3ff; color:#7c3aed"><i class="fas fa-gift"></i></div>
                        <div class="s-text">
                            <h4>Bonus Savings Plan</h4>
                            <p>Annual bonus credited for consistent saving members. Up to ₹5,000 extra.</p>
                        </div>
                    </li>
                </ul>
            </div>
            <div data-aos="fade-left">
                <div style="background:linear-gradient(135deg,#eff6ff,#e0f2fe); border-radius:24px; padding:40px;">
                    <div style="text-align:center; margin-bottom:30px;">
                        <div style="font-size:3rem; font-weight:900; font-family:'Nunito',sans-serif; color:var(--blue);">₹48 Cr+</div>
                        <div style="color:var(--gray); font-weight:600;">Total Member Savings Managed</div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div style="background:#fff; border-radius:14px; padding:20px; text-align:center;">
                            <div style="font-size:1.6rem; font-weight:900; font-family:'Nunito',sans-serif; color:var(--green);">9%</div>
                            <div style="font-size:0.8rem; color:var(--gray);">Max FD Interest</div>
                        </div>
                        <div style="background:#fff; border-radius:14px; padding:20px; text-align:center;">
                            <div style="font-size:1.6rem; font-weight:900; font-family:'Nunito',sans-serif; color:var(--blue);">10K+</div>
                            <div style="font-size:0.8rem; color:var(--gray);">Active Savers</div>
                        </div>
                        <div style="background:#fff; border-radius:14px; padding:20px; text-align:center;">
                            <div style="font-size:1.6rem; font-weight:900; font-family:'Nunito',sans-serif; color:var(--amber);">₹500</div>
                            <div style="font-size:0.8rem; color:var(--gray);">Min. Monthly SIP</div>
                        </div>
                        <div style="background:#fff; border-radius:14px; padding:20px; text-align:center;">
                            <div style="font-size:1.6rem; font-weight:900; font-family:'Nunito',sans-serif; color:var(--red);">100%</div>
                            <div style="font-size:0.8rem; color:var(--gray);">Deposit Safety</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── 100% GENUINE / TRUST ───────────────────────────── -->
<section class="section section-alt">
    <div class="container">
        <div style="text-align:center; margin-bottom:50px;" data-aos="fade-up">
            <span class="badge-pill"><i class="fas fa-shield-alt"></i> Why Trust Us</span>
            <h2 style="font-size:2.2rem;">100% Genuine. No Hidden Charges. No Fraud.</h2>
            <p style="color:var(--gray); margin-top:12px; max-width:580px; margin-left:auto; margin-right:auto;">
                We believe finance should be transparent. Every transaction is audited, every charge is disclosed upfront.
            </p>
        </div>
        <div class="genuine-grid">
            <div class="genuine-card" data-aos="zoom-in" data-aos-delay="0">
                <div class="g-icon" style="background:#eff6ff; color:var(--blue)"><i class="fas fa-certificate"></i></div>
                <h4>RBI Registered</h4>
                <p>Fully licensed and regulated. Your deposits are protected under government norms.</p>
            </div>
            <div class="genuine-card" data-aos="zoom-in" data-aos-delay="80">
                <div class="g-icon" style="background:#f0fdf4; color:var(--green)"><i class="fas fa-lock"></i></div>
                <h4>Bank-Grade Encryption</h4>
                <p>256-bit SSL encryption protects all your financial data and transactions.</p>
            </div>
            <div class="genuine-card" data-aos="zoom-in" data-aos-delay="160">
                <div class="g-icon" style="background:#fffbeb; color:#b45309"><i class="fas fa-file-contract"></i></div>
                <h4>Zero Hidden Fees</h4>
                <p>All charges disclosed before you sign. Processing fee, interest — everything shown upfront.</p>
            </div>
            <div class="genuine-card" data-aos="zoom-in" data-aos-delay="240">
                <div class="g-icon" style="background:#fef2f2; color:var(--red)"><i class="fas fa-headset"></i></div>
                <h4>24/7 Support</h4>
                <p>Dedicated customer care via WhatsApp, call, and email. Always available.</p>
            </div>
        </div>
    </div>
</section>

<!-- ─── TESTIMONIALS ───────────────────────────────────── -->
<section class="section">
    <div class="container">
        <div style="text-align:center; margin-bottom:50px;" data-aos="fade-up">
            <span class="badge-pill"><i class="fas fa-quote-left"></i> Real Members</span>
            <h2 style="font-size:2.2rem;">What Our Members Say</h2>
        </div>
        <div class="testi-grid">
            <div class="testi-card" data-aos="fade-up" data-aos-delay="0">
                <div class="stars">★★★★★</div>
                <p class="testi-text">"I got my personal loan of ₹1.5 lakh approved within 6 hours. The entire process was online and completely transparent. No hidden charges at all!"</p>
                <div class="testi-author">
                    <div class="avatar">P</div>
                    <div>
                        <div class="author-name">Priya Sharma</div>
                        <div class="author-loc"><i class="fas fa-map-marker-alt"></i> Pune, Maharashtra</div>
                    </div>
                </div>
            </div>
            <div class="testi-card" data-aos="fade-up" data-aos-delay="100">
                <div class="stars">★★★★★</div>
                <p class="testi-text">"I've been saving with Windeep for 2 years. The interest is better than my bank FD and the app makes tracking super easy. Highly recommended!"</p>
                <div class="testi-author">
                    <div class="avatar">R</div>
                    <div>
                        <div class="author-name">Rahul Deshpande</div>
                        <div class="author-loc"><i class="fas fa-map-marker-alt"></i> Nashik, Maharashtra</div>
                    </div>
                </div>
            </div>
            <div class="testi-card" data-aos="fade-up" data-aos-delay="200">
                <div class="stars">★★★★☆</div>
                <p class="testi-text">"Our SHG group got a loan of ₹8 lakh to start a small business. The team was very supportive and the EMI is manageable. Genuine company."</p>
                <div class="testi-author">
                    <div class="avatar">S</div>
                    <div>
                        <div class="author-name">Sunita Bai Jagtap</div>
                        <div class="author-loc"><i class="fas fa-map-marker-alt"></i> Aurangabad, Maharashtra</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── APP DOWNLOAD ───────────────────────────────────── -->
<section class="app-section" id="app" data-aos="fade-up">
    <div class="container">
        <div class="app-grid">
            <div>
                <h2>Manage Everything<br>From Your Phone</h2>
                <p>Apply for loans, track EMIs, view savings balance, download statements — all from the Windeep Finance app. Available on Android &amp; iOS.</p>
                <div class="app-badges">
                    <a href="#" class="app-badge">
                        <i class="fab fa-google-play"></i>
                        <div>
                            <span class="small">Get it on</span>
                            <span class="big">Google Play</span>
                        </div>
                    </a>
                    <a href="#" class="app-badge">
                        <i class="fab fa-apple"></i>
                        <div>
                            <span class="small">Download on the</span>
                            <span class="big">App Store</span>
                        </div>
                    </a>
                </div>
                <div class="app-stats">
                    <div class="app-stat">
                        <div class="num">50K+</div>
                        <div class="lbl">Downloads</div>
                    </div>
                    <div class="app-stat">
                        <div class="num">4.8 ★</div>
                        <div class="lbl">App Rating</div>
                    </div>
                    <div class="app-stat">
                        <div class="num">10K+</div>
                        <div class="lbl">Active Users</div>
                    </div>
                </div>
            </div>
            <div class="phone-mockup">
                <div class="phone-screen">
                    <div style="font-family:'Nunito',sans-serif; font-weight:800; color:var(--blue); margin-bottom:16px; font-size:1rem;">Windeep Finance App</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                        <div style="background:#eff6ff; border-radius:12px; padding:14px; text-align:center;">
                            <i class="fas fa-hand-holding-usd" style="color:var(--blue); font-size:1.4rem;"></i>
                            <div style="font-size:0.75rem; font-weight:700; margin-top:6px; color:var(--dark);">Apply Loan</div>
                        </div>
                        <div style="background:#f0fdf4; border-radius:12px; padding:14px; text-align:center;">
                            <i class="fas fa-piggy-bank" style="color:var(--green); font-size:1.4rem;"></i>
                            <div style="font-size:0.75rem; font-weight:700; margin-top:6px; color:var(--dark);">My Savings</div>
                        </div>
                        <div style="background:#fffbeb; border-radius:12px; padding:14px; text-align:center;">
                            <i class="fas fa-receipt" style="color:#b45309; font-size:1.4rem;"></i>
                            <div style="font-size:0.75rem; font-weight:700; margin-top:6px; color:var(--dark);">Pay EMI</div>
                        </div>
                        <div style="background:#fef2f2; border-radius:12px; padding:14px; text-align:center;">
                            <i class="fas fa-file-alt" style="color:var(--red); font-size:1.4rem;"></i>
                            <div style="font-size:0.75rem; font-weight:700; margin-top:6px; color:var(--dark);">Statements</div>
                        </div>
                    </div>
                    <div style="background:#f9fafb; border-radius:10px; padding:12px; text-align:center; font-size:0.8rem; color:var(--gray);">
                        <i class="fas fa-lock" style="color:var(--green)"></i> 256-bit Secure &nbsp;|&nbsp; <i class="fab fa-android" style="color:var(--green)"></i> Android &nbsp;|&nbsp; <i class="fab fa-apple"></i> iOS
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── CONTEXTUAL CONTENT / SEO ──────────────────────── -->
<section class="content-section">
    <div class="container content-box">
        <span class="badge-pill"><i class="fas fa-info-circle"></i> About Windeep Finance</span>
        <h2>Your Trusted Financial Partner in Maharashtra</h2>
        <p>
            Windeep Finance is a leading microfinance and personal loan provider serving thousands of families and small businesses across Maharashtra. Since 2024, we have disbursed over ₹48 crore in loans and managed savings for more than 10,000 members — making us one of the fastest-growing community finance institutions in the region.
        </p>
        <p>
            Our mission is simple: make credit and savings accessible to everyone, regardless of income or background. Whether you need a small personal loan for a medical emergency, a business loan to expand your shop, or a secure place to grow your savings with high interest, Windeep Finance has a product built for you.
        </p>
        <p>
            Every loan application is processed transparently with zero hidden fees. Our digital-first approach means you can apply, track, and repay your loan entirely from our <strong>mobile app</strong> or web portal. We are <strong>RBI registered</strong> and follow strict compliance norms to ensure your deposits are always safe.
        </p>
        <p>
            We also believe in empowering members with smart financial tools. Just as specialized tools like a <a href="https://www.thefancytext.com" rel="dofollow" target="_blank">fancy text generator</a> help people express themselves creatively online, our financial platform gives you specialized tools — loan calculators, savings trackers, EMI schedulers, and digital passbooks — to help you make smarter money decisions every day.
        </p>
        <p>
            Ready to start? <a href="<?= site_url('admin/login') ?>">Apply for a loan today</a> or <a href="#savings">open a savings account</a> and take the first step towards a stronger financial future.
        </p>
    </div>
</section>

<!-- ─── CTA BANNER ────────────────────────────────────── -->
<section class="cta-banner" id="contact">
    <div class="container">
        <h2>Ready to Get Started?</h2>
        <p>Join 10,000+ members who trust Windeep Finance for their loans and savings needs.</p>
        <div class="cta-btns">
            <a href="<?= site_url('admin/login') ?>" class="btn btn-primary btn-lg">
                <i class="fas fa-rocket"></i> Apply for a Loan
            </a>
            <a href="tel:+919834752251" class="btn btn-white btn-lg">
                <i class="fas fa-phone-alt"></i> Call Us Now
            </a>
        </div>
        <div style="margin-top:36px; color:rgba(255,255,255,0.5); font-size:0.9rem;">
            📞 <a href="tel:+919834752251" style="color:rgba(255,255,255,0.7);">+91 98347 52251</a>
            &nbsp;&nbsp;✉️ <a href="mailto:windeepfinance@gmail.com" style="color:rgba(255,255,255,0.7);">windeepfinance@gmail.com</a>
        </div>
    </div>
</section>

<!-- ─── FOOTER ────────────────────────────────────────── -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a href="<?= base_url('home') ?>" class="logo">
                    <div class="logo-box" style="width:32px; height:32px; font-size:0.95rem;"><i class="fas fa-landmark"></i></div>
                    Windeep Finance
                </a>
                <p class="footer-desc">100% genuine loans, savings &amp; investment platform. Trusted by 10,000+ members across Maharashtra.</p>
            </div>
            <div>
                <h5>Products</h5>
                <ul class="footer-links">
                    <li><a href="#loans">Personal Loan</a></li>
                    <li><a href="#loans">Business Loan</a></li>
                    <li><a href="#loans">Gold Loan</a></li>
                    <li><a href="#savings">Savings Scheme</a></li>
                    <li><a href="#savings">Fixed Deposit</a></li>
                </ul>
            </div>
            <div>
                <h5>Company</h5>
                <ul class="footer-links">
                    <li><a href="<?= base_url() ?>">Home</a></li>
                    <li><a href="#process">How It Works</a></li>
                    <li><a href="#app">Mobile App</a></li>
                    <li><a href="<?= site_url('member/login') ?>">Member Portal</a></li>
                    <li><a href="<?= site_url('admin/login') ?>">Admin Login</a></li>
                </ul>
            </div>
            <div>
                <h5>Contact</h5>
                <ul class="footer-links">
                    <li><a href="tel:+919834752251"><i class="fas fa-phone-alt"></i> +91 98347 52251</a></li>
                    <li><a href="mailto:windeepfinance@gmail.com"><i class="fas fa-envelope"></i> windeepfinance@gmail.com</a></li>
                </ul>
                <div style="margin-top:20px; display:flex; gap:12px;">
                    <a href="#" style="color:rgba(255,255,255,0.5); font-size:1.2rem;"><i class="fab fa-facebook"></i></a>
                    <a href="#" style="color:rgba(255,255,255,0.5); font-size:1.2rem;"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="color:rgba(255,255,255,0.5); font-size:1.2rem;"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div>&copy; <?= date('Y') ?> Windeep Finance. All rights reserved.</div>
            <div>
                Developed by <a href="mailto:rupeshchaudhari05@gmail.com">Rupesh Chaudhari</a>
                &nbsp;&bull;&nbsp; <a href="tel:+919028581320">+91 90285 81320</a>
                &nbsp;|&nbsp; <a href="https://www.thefancytext.com" rel="dofollow" target="_blank">Fancy Text Generator</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 750, once: true, offset: 50 });
</script>
</body>
</html>
