    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-cols">
                <div class="f-col">
                    <a href="<?= base_url() ?>" class="logo" style="margin-bottom:20px; display:inline-flex;">
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
                        <li><a href="<?= base_url('#features') ?>">Lending Engine</a></li>
                        <li><a href="<?= base_url('#features') ?>">Core Banking</a></li>
                        <li><a href="<?= base_url('#features') ?>">Accounting</a></li>
                        <li><a href="<?= site_url('member/login') ?>">Member App</a></li>
                    </ul>
                </div>
                <div class="f-col">
                    <h4>Agency</h4>
                    <ul class="f-links">
                        <li><a href="<?= base_url('about') ?>">About Us</a></li>
                        <li><a href="<?= base_url('case_studies') ?>">Case Studies</a></li>
                        <li><a href="<?= base_url('careers') ?>">Careers</a></li>
                        <li><a href="<?= base_url('contact') ?>">Contact Sales</a></li>
                    </ul>
                </div>
                <div class="f-col">
                    <h4>Legal</h4>
                    <ul class="f-links">
                        <li><a href="<?= base_url('privacy') ?>">Privacy Policy</a></li>
                        <li><a href="<?= base_url('terms') ?>">Terms of Service</a></li>
                        <li><a href="<?= base_url('security') ?>">Security</a></li>
                        <li><a href="<?= base_url('compliance') ?>">Compliance</a></li>
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
             if(nav) {
                 if(window.scrollY > 20) nav.classList.add('scrolled');
                 else nav.classList.remove('scrolled');
             }
        });
    </script>
</body>
</html>
