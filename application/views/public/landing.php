<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#4f46e5">
    <meta name="description" content="Windeep Finance - Enterprise-grade microfinance and banking solution for modern financial institutions.">
    <title><?= $title ?? 'Windeep Finance - Enterprise Financial Solutions' ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            /* Modern Agency Palette */
            --primary: #4f46e5;       /* Indigo 600 */
            --primary-dark: #4338ca;  /* Indigo 700 */
            --secondary: #0ea5e9;     /* Sky 500 */
            --accent: #f59e0b;        /* Amber 500 */
            --dark: #0f172a;          /* Slate 900 */
            --surface: #ffffff;
            --surface-alt: #f8fafc;   /* Slate 50 */
            --text-main: #334155;     /* Slate 700 */
            --text-light: #64748b;    /* Slate 500 */
            --border: #e2e8f0;
            --gradient: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; font-size: 16px; }
        body { 
            font-family: 'Inter', sans-serif; 
            color: var(--text-main); 
            line-height: 1.6; 
            background: var(--surface);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 { 
            font-family: 'Urbanist', sans-serif; 
            color: var(--dark); 
            line-height: 1.2; 
            font-weight: 700;
        }

        a { text-decoration: none; transition: all 0.2s ease; }
        ul { list-style: none; }

        /* ── Components ── */
        .container { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
        .text-gradient {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px 32px; border-radius: 12px; font-weight: 600; font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; border: none;
        }
        .btn-primary { 
            background: var(--primary); color: #fff; 
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.1), 0 2px 4px -1px rgba(79, 70, 229, 0.06);
        }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.2); }
        
        .btn-white { background: #fff; color: var(--primary); border: 1px solid var(--border); }
        .btn-white:hover { border-color: var(--primary); background: #f8fafc; }

        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 16px; border-radius: 50px;
            font-size: 0.85rem; font-weight: 600;
            background: rgba(79, 70, 229, 0.1); color: var(--primary);
            margin-bottom: 24px; text-transform: uppercase; letter-spacing: 0.5px;
        }

        /* ── Navbar ── */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            padding: 20px 0; background: rgba(255,255,255,0.9);
            backdrop-filter: blur(12px); border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .navbar.scrolled { padding: 12px 0; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; }
        
        .logo { display: flex; align-items: center; gap: 12px; font-size: 1.4rem; font-weight: 800; color: var(--dark); }
        .logo-icon {
            width: 40px; height: 40px; border-radius: 10px;
            background: var(--gradient); display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.2rem; transform: rotate(-5deg);
        }

        .nav-menu { display: flex; gap: 40px; align-items: center; }
        .nav-link { color: var(--text-main); font-weight: 500; font-size: 0.95rem; }
        .nav-link:hover { color: var(--primary); }

        .mobile-toggle { display: none; font-size: 1.5rem; background: none; border: none; color: var(--dark); cursor: pointer; }

        /* ── Hero Section ── */
        .hero {
            padding: 180px 0 100px;
            background: radial-gradient(circle at 10% 20%, rgba(79, 70, 229, 0.05) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(14, 165, 233, 0.05) 0%, transparent 40%);
            position: relative; overflow: hidden;
        }
        .hero-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 60px; align-items: center; }
        .hero-title {
            font-size: 3.75rem; line-height: 1.1; margin-bottom: 24px; letter-spacing: -0.02em;
        }
        .hero-desc {
            font-size: 1.25rem; color: var(--text-light); margin-bottom: 40px; max-width: 560px;
        }
        .hero-visual { position: relative; }
        .dashboard-mockup {
            background: #fff; border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            padding: 20px; border: 1px solid var(--border);
            position: relative; overflow: hidden;
            animation: float 6s ease-in-out infinite;
        }
        .floating-card {
            position: absolute; background: #fff; padding: 16px; 
            border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            display: flex; align-items: center; gap: 12px;
            z-index: 2; animation: float 5s ease-in-out infinite reverse;
        }
        .fc-1 { bottom: 40px; left: -20px; }
        .fc-2 { top: 40px; right: -20px; }

        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }

        /* ── New: Features Strip ── */
        .features-strip { padding: 40px 0; border-bottom: 1px solid var(--border); background: #fff; }
        .strip-grid { display: flex; justify-content: space-around; flex-wrap: wrap; gap: 30px; text-align: center; }
        .strip-item { display: flex; align-items: center; gap: 10px; color: var(--text-light); font-weight: 500; font-size: 0.95rem; }
        .strip-item i { color: var(--primary); }

        /* ── Agency Intro Section ── */
        .agency-intro { padding: 100px 0; background: #fff; }
        .section-header { text-align: center; max-width: 700px; margin: 0 auto 60px; }
        .section-subtitle { color: var(--primary); font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 12px; font-size: 0.85rem; letter-spacing: 1px; }
        .intro-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
        .intro-card {
            padding: 32px; border-radius: 16px; background: var(--surface-alt);
            transition: all 0.3s; border: 1px solid transparent;
        }
        .intro-card:hover { transform: translateY(-5px); background: #fff; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); border-color: var(--border); }
        .ic-icon {
            width: 56px; height: 56px; border-radius: 12px; background: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 24px; color: var(--primary);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* ── Comprehensive Features (Alternating) ── */
        .deep-dive { padding: 100px 0; background: var(--surface-alt); }
        .feature-block { 
            display: grid; grid-template-columns: 1fr 1fr; gap: 80px; 
            align-items: center; margin-bottom: 120px; 
        }
        .feature-block.reverse { direction: rtl; }
        .feature-block.reverse .feature-text { direction: ltr; }
        .feature-img {
            background: #fff; border-radius: 24px; padding: 30px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); border: 1px solid var(--border);
            position: relative;
        }
        .feature-img::before {
            content: ''; position: absolute; inset: -15px; border-radius: 32px; z-index: -1;
            background: radial-gradient(circle at 50% 50%, rgba(79, 70, 229, 0.1), transparent 70%);
        }
        .feature-list li {
            display: flex; align-items: flex-start; gap: 12px;
            margin-bottom: 16px; color: var(--text-main);
        }
        .feature-list i { color: var(--primary); margin-top: 4px; }

        /* ── New: Notifications & Integrations ── */
        .integrations { padding: 100px 0; background: var(--dark); color: #fff; overflow: hidden; }
        .int-header h2 { color: #fff; }
        .int-header p { color: rgba(255,255,255,0.7); }
        .int-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px; margin-top: 60px; }
        .int-card {
            background: rgba(255,255,255,0.05); padding: 24px; border-radius: 16px;
            text-align: center; border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }
        .int-card:hover { background: rgba(255,255,255,0.1); transform: translateY(-5px); border-color: var(--primary); }
        .int-card i { font-size: 2rem; margin-bottom: 16px; color: var(--secondary); }
        .int-card h4 { color: #fff; margin-bottom: 8px; font-size: 1.1rem; }
        .int-card p { color: rgba(255,255,255,0.6); font-size: 0.9rem; }

        /* ── Agency Services (Why Us) ── */
        .why-us { padding: 100px 0; background: #fff; }
        .services-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; }
        .service-item { display: flex; gap: 20px; }
        .s-number {
            font-size: 2.5rem; font-weight: 800; color: rgba(79, 70, 229, 0.1);
            line-height: 1; flex-shrink: 0;
        }
        .s-content h4 { margin-bottom: 10px; font-size: 1.25rem; }
        .s-content p { color: var(--text-light); }

        /* ── Footer ── */
        .footer { background: #f8fafc; padding: 80px 0 30px; border-top: 1px solid var(--border); }
        .footer-cols { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; }
        .f-col h4 { margin-bottom: 24px; font-size: 1.1rem; }
        .f-links li { margin-bottom: 12px; }
        .f-links a { color: var(--text-light); font-size: 0.95rem; }
        .f-links a:hover { color: var(--primary); }
        .copyright { margin-top: 60px; pt-30px; border-top: 1px solid var(--border); text-align: center; padding-top: 30px; color: var(--text-light); font-size: 0.9rem; }

        /* ── Responsive ── */
        @media (max-width: 992px) {
            .hero-grid { grid-template-columns: 1fr; text-align: center; }
            .hero-desc { margin: 0 auto 40px; }
            .hero-visual { display: none; }
            .feature-block, .feature-block.reverse { grid-template-columns: 1fr; direction: ltr; }
            .intro-grid, .int-grid, .services-grid, .footer-cols { grid-template-columns: 1fr; }
            .nav-menu { display: none; }
            .mobile-toggle { display: block; }
            .hero-title { font-size: 2.5rem; }
        }

        /* Menu Dropdown Mobile */
        .nav-menu.active {
            display: flex; flex-direction: column; position: absolute;
            top: 100%; left: 0; right: 0; background: #fff;
            padding: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-bottom: 1px solid var(--border);
        }
    </style>
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
                <a href="#features" class="nav-link">Platform</a>
                <a href="#solutions" class="nav-link">Solutions</a>
                <a href="#integrations" class="nav-link">Integrations</a>
                <a href="<?= base_url('about') ?>" class="nav-link">About Us</a>
                <a href="#services" class="nav-link">Services</a>
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

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <div class="hero-grid">
                <div data-aos="fade-up">
                    <span class="badge"><i class="fas fa-bolt"></i> Enterprise Edition 2.0</span>
                    <h1 class="hero-title">The Operating System for <span class="text-gradient">Modern Finance</span>.</h1>
                    <p class="hero-desc">
                        A complete agency-grade platform for managing microfinance, loans, savings, and investments. Secure, scalable, and built for growth.
                    </p>
                    <div class="hero-buttons">
                         <a href="<?= site_url('admin/login') ?>" class="btn btn-primary">
                            Get Started Now
                        </a>
                        <a href="#features" class="btn btn-white">
                            View Live Demo
                        </a>
                    </div>
                </div>
                <div class="hero-visual" data-aos="fade-left">
                    <div class="dashboard-mockup">
                        <!-- Simulated UI -->
                        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                            <div style="font-weight:700; color:#334155;">Dashboard Overview</div>
                            <div style="color:#64748b; font-size:0.9rem;">Period: This Month <i class="fas fa-chevron-down"></i></div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
                            <div style="background:#f1f5f9; padding:16px; border-radius:12px;">
                                <div style="color:#64748b; font-size:0.8rem; margin-bottom:4px;">Total Disbursements</div>
                                <div style="font-size:1.5rem; font-weight:700; color:#0f172a;">₹ 12.4M</div>
                                <div style="color:#10b981; font-size:0.8rem; margin-top:4px;"><i class="fas fa-arrow-up"></i> 12% vs last month</div>
                            </div>
                            <div style="background:#f1f5f9; padding:16px; border-radius:12px;">
                                <div style="color:#64748b; font-size:0.8rem; margin-bottom:4px;">Active Members</div>
                                <div style="font-size:1.5rem; font-weight:700; color:#0f172a;">8,540</div>
                                <div style="color:#10b981; font-size:0.8rem; margin-top:4px;"><i class="fas fa-arrow-up"></i> 54 New today</div>
                            </div>
                        </div>
                        <!-- Chart placeholder -->
                        <div style="height:140px; background:linear-gradient(180deg, rgba(79,70,229,0.05) 0%, rgba(255,255,255,0) 100%); border-bottom:2px solid var(--primary); position:relative;">
                            <div style="position:absolute; bottom:0; left:0; width:100%; height:60%; background:url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA1MDAgMTUwIiBwcmVzZXJ2ZUFzcGVjdHJhdGlvPSJub25lIj48cGF0aCBkPSJNMCAxMDBDMTUwIDAgMzUwIDE1MCA1MDAgNTUiIHN0cm9rZT0iIzRmNDZlNSIgc3Ryb2tlLXdpZHRoPSIzIiBmaWxsPSJub25lIi8+PC9zdmc+'); background-size:cover;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trusted Strip -->
    <div class="features-strip">
        <div class="container strip-grid">
            <div class="strip-item"><i class="fas fa-shield-alt"></i> ISO 27001 Certified Security</div>
            <div class="strip-item"><i class="fas fa-server"></i> 99.99% Cloud Uptime</div>
            <div class="strip-item"><i class="fas fa-headset"></i> 24/7 Priority Support</div>
            <div class="strip-item"><i class="fas fa-code"></i> API First Architecture</div>
        </div>
    </div>

    <!-- Core Pillars (Agency Style) -->
    <section class="agency-intro" id="solutions">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <span class="section-subtitle">Comprehensive Solutions</span>
                <h2>Trusted by Leading Financial Agencies</h2>
                <p style="color:var(--text-light); margin-top:16px;">
                    We provide the infrastructure that powers thorough financial operations, from lending to complex accounting.
                </p>
            </div>
            
            <div class="intro-grid">
                <!-- User Requested: Personalized Financial Solutions -->
                <div class="intro-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="ic-icon"><i class="fas fa-chart-pie"></i></div>
                    <h3>Personalized Financial Solutions</h3>
                    <p style="color:var(--text-light); margin-top:12px;">From tailored investment plans to instant loans, enjoy cutting-edge services designed to meet user needs.</p>
                </div>

                <!-- User Requested: Innovative Financial Experiences -->
                <div class="intro-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="ic-icon"><i class="fas fa-clock"></i></div>
                    <h3>Innovative Experiences</h3>
                    <p style="color:var(--text-light); margin-top:12px;">Step into the future with our user-friendly platform offering unparalleled convenience and 24/7 flexibility.</p>
                </div>

                <!-- User Requested: Exclusive Offers -->
                <div class="intro-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="ic-icon"><i class="fas fa-crown"></i></div>
                    <h3>Exclusive Benefits</h3>
                    <p style="color:var(--text-light); margin-top:12px;">Unlock access to special offers and premium benefits designed to maximize financial potential for members.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Deep Dive Features (Based on Codebase Analysis) -->
    <section class="deep-dive" id="features">
        <div class="container">
            
            <!-- Feature 1: Lending -->
            <div class="feature-block" data-aos="fade-right">
                <div class="feature-img">
                    <div style="background:#f8fafc; height:250px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#cbd5e1;">
                         <i class="fas fa-hand-holding-usd" style="font-size:5rem; opacity:0.2;"></i>
                    </div>
                </div>
                <div class="feature-text">
                    <span class="badge">Lending Engine</span>
                    <h2>Simplified Loan Management</h2>
                    <p style="color:var(--text-light); margin:20px 0;">
                        Manage the entire loan lifecycle from application to disbursement and closure with our automated workflows.
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Flexible Products (Flat/Reducing Interest)</li>
                        <li><i class="fas fa-check-circle"></i> Automated EMA & Amortization Schedules</li>
                        <li><i class="fas fa-check-circle"></i> Guarantor Management & Verification</li>
                        <li><i class="fas fa-check-circle"></i> Penalty & Foreclosure Handling</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 2: Savings (Reverse) -->
            <div class="feature-block reverse" data-aos="fade-left">
                <div class="feature-img">
                    <div style="background:#f8fafc; height:250px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#cbd5e1;">
                         <i class="fas fa-piggy-bank" style="font-size:5rem; opacity:0.2;"></i>
                    </div>
                </div>
                <div class="feature-text">
                    <span class="badge">Savings & Deposits</span>
                    <h2>Secure Daily Deposits</h2>
                    <p style="color:var(--text-light); margin:20px 0;">
                        Empower members with flexible saving schemes. Track every rupee with our verified ledger system.
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Multiple Saving Schemes Support</li>
                        <li><i class="fas fa-check-circle"></i> Auto-Interest Calculation</li>
                        <li><i class="fas fa-check-circle"></i> Digital Passbook for Members</li>
                        <li><i class="fas fa-check-circle"></i> Instant Withdrawal Processing</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 3: Accounting -->
            <div class="feature-block" data-aos="fade-right">
                <div class="feature-img">
                    <div style="background:#f8fafc; height:250px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#cbd5e1;">
                         <i class="fas fa-file-invoice-dollar" style="font-size:5rem; opacity:0.2;"></i>
                    </div>
                </div>
                <div class="feature-text">
                    <span class="badge">Core Accounting</span>
                    <h2>Automated Reconciliation</h2>
                    <p style="color:var(--text-light); margin:20px 0;">
                        Stop manual data entry. Import bank statements and let our system map transactions automatically.
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Bank Statement Import & Mapping</li>
                        <li><i class="fas fa-check-circle"></i> Real-time Balance Sheet & P&L</li>
                        <li><i class="fas fa-check-circle"></i> Expense Tracking & Categorization</li>
                        <li><i class="fas fa-check-circle"></i> Audit Logs & Transaction History</li>
                    </ul>
                </div>
            </div>

        </div>
    </section>

    <!-- Agency Services -->
    <section class="why-us" id="services">
        <div class="container">
            <div class="section-header">
                <span class="section-subtitle">Our Agency Promise</span>
                <h2>Why Choose Windeep As Your Partner?</h2>
            </div>
            <div class="services-grid">
                <div class="service-item" data-aos="fade-up">
                    <div class="s-number">01</div>
                    <div class="s-content">
                        <h4>Tailored Customization</h4>
                        <p>We don't believe in one-size-fits-all. Our team customizes the platform to fit your specific workflow rules.</p>
                    </div>
                </div>
                <div class="service-item" data-aos="fade-up" data-aos-delay="100">
                    <div class="s-number">02</div>
                    <div class="s-content">
                        <h4>Data Migration</h4>
                        <p>Moving from a legacy system? Our experts handle the safe migration of all your historical member and loan data.</p>
                    </div>
                </div>
                <div class="service-item" data-aos="fade-up" data-aos-delay="200">
                    <div class="s-number">03</div>
                    <div class="s-content">
                        <h4>Dedicated Support</h4>
                        <p>Get a dedicated account manager and technical support team to ensure your operations never stop.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Integrations / Tech -->
    <section class="integrations" id="integrations">
        <div class="container">
            <div class="section-header int-header">
                <span class="section-subtitle" style="color:var(--secondary);">Connected Ecosystem</span>
                <h2>Seamless Integrations</h2>
                <p>Connected with the tools you use every day.</p>
            </div>
            <div class="int-grid">
                <div class="int-card" data-aos="zoom-in">
                    <i class="fab fa-whatsapp"></i>
                    <h4>WhatsApp</h4>
                    <p>Automated payment reminders & receipts.</p>
                </div>
                <div class="int-card" data-aos="zoom-in" data-aos-delay="100">
                    <i class="fas fa-sms"></i>
                    <h4>SMS Gateway</h4>
                    <p>Instant OTPs and transactional alerts.</p>
                </div>
                <div class="int-card" data-aos="zoom-in" data-aos-delay="200">
                    <i class="fas fa-university"></i>
                    <h4>Net Banking</h4>
                    <p>Direct integration for statement fetching.</p>
                </div>
                <div class="int-card" data-aos="zoom-in" data-aos-delay="300">
                    <i class="fas fa-envelope"></i>
                    <h4>Email Engine</h4>
                    <p>Report delivery and newsletters.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-cols">
                <div class="f-col">
                    <a href="#" class="logo" style="margin-bottom:20px; display:inline-flex;">
                        <div class="logo-icon" style="width:32px; height:32px; font-size:1rem;"><i class="fas fa-landmark"></i></div>
                         Windeep Finance
                    </a>
                    <p style="color:var(--text-light); max-width:300px; font-size:0.95rem;">
                        The leading agency-grade financial operating system. Empowering institutions with technology since 2024.
                    </p>
                </div>
                <div class="f-col">
                    <h4>Product</h4>
                    <ul class="f-links">
                        <li><a href="#">Lending Engine</a></li>
                        <li><a href="#">Core Banking</a></li>
                        <li><a href="#">Accounting</a></li>
                        <li><a href="#">Member App</a></li>
                    </ul>
                </div>
                <div class="f-col">
                    <h4>Agency</h4>
                    <ul class="f-links">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Case Studies</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Contact Sales</a></li>
                    </ul>
                </div>
                <div class="f-col">
                    <h4>Legal</h4>
                    <ul class="f-links">
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Security</a></li>
                        <li><a href="#">Compliance</a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                &copy; <?= date('Y') ?> Windeep Finance. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true, offset: 60 });
        
        // Sticky Navbar Effect
        window.addEventListener('scroll', () => {
             const nav = document.getElementById('navbar');
             if(window.scrollY > 20) nav.classList.add('scrolled');
             else nav.classList.remove('scrolled');
        });
    </script>
</body>
</html>
