<header class="page-header">
    <div class="container">
        <h1 class="page-title">Contact Us</h1>
        <p class="page-subtitle">We are here to help. Get in touch with our team.</p>
    </div>
</header>
<main class="page-content">
    <div class="container">
        
        <div class="content-block" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; text-align: left;">
            <div>
                <h3>Address</h3>
                <p>123, Financial District,<br>
                Mumbai, Maharashtra, India - 400051</p>

                <h3>Email</h3>
                <p><a href="mailto:support@windeepfinance.com" style="color:var(--primary);">support@windeepfinance.com</a></p>

                <h3>Phone</h3>
                <p>+91 (22) 1234 5678</p>

                <h3>Business Hours</h3>
                <p>Monday - Friday: 9:00 AM - 6:00 PM<br>
                Saturday: 10:00 AM - 2:00 PM</p>
            </div>
            
            <form action="#" method="post" style="background: #f8fafc; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <div style="margin-bottom: 20px;">
                    <label for="name" style="display: block; margin-bottom: 8px; font-weight: 500;">Your Name</label>
                    <input type="text" id="name" name="name" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px;" required>
                </div>
                <div style="margin-bottom: 20px;">
                    <label for="email" style="display: block; margin-bottom: 8px; font-weight: 500;">Your Email</label>
                    <input type="email" id="email" name="email" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px;" required>
                </div>
                <div style="margin-bottom: 20px;">
                    <label for="subject" style="display: block; margin-bottom: 8px; font-weight: 500;">Subject</label>
                    <select id="subject" name="subject" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px;">
                        <option value="General Inquiry">General Inquiry</option>
                        <option value="Loan Application">Loan Application Help</option>
                        <option value="Partnership">Partnership</option>
                        <option value="Technical Support">Technical Support</option>
                    </select>
                </div>
                <div style="margin-bottom: 20px;">
                    <label for="message" style="display: block; margin-bottom: 8px; font-weight: 500;">Message</label>
                    <textarea id="message" name="message" rows="4" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px;" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;" onclick="event.preventDefault(); alert('Thank you! This is a demo form.');">Send Message</button>
            </form>
        </div>

        <div class="content-block" style="text-align: center; margin-top: 60px;">
            <h3>Follow Us</h3>
            <div style="display: flex; gap: 20px; justify-content: center; font-size: 1.5rem; color: var(--primary);">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>

    </div>
</main>
