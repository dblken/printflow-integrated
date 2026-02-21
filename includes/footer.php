</main>

    <!-- Footer: layout and design (self-contained so it always displays correctly) -->
    <style>
        .ft-footer { width: 100%; background: #1e293b; color: #e2e8f0; margin-top: 2.5rem; box-sizing: border-box; }
        .ft-wrap { max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem; box-sizing: border-box; }
        .ft-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; }
        @media (min-width: 768px) { .ft-grid { grid-template-columns: repeat(4, 1fr); gap: 2.5rem; } }
        .ft-brand { font-size: 1.25rem; font-weight: 700; color: #a5b4fc; margin: 0 0 0.5rem 0; }
        .ft-desc { font-size: 0.875rem; color: #94a3b8; line-height: 1.55; margin: 0; max-width: 260px; }
        .ft-title { font-size: 0.9375rem; font-weight: 700; color: #ffffff; margin: 0 0 1rem 0; text-transform: uppercase; letter-spacing: 0.03em; }
        .ft-list { list-style: none; padding: 0; margin: 0; }
        .ft-list li { margin-bottom: 0.5rem; }
        .ft-list a { font-size: 0.875rem; color: #94a3b8; text-decoration: none; }
        .ft-list a:hover { color: #c7d2fe; }
        .ft-list-item { font-size: 0.875rem; color: #94a3b8; display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.5rem; }
        .ft-list-item .ft-ico { flex-shrink: 0; width: 1em; font-size: 0.875rem; color: #a5b4fc; }
        .ft-social { display: flex; gap: 0.75rem; margin-top: 1rem; flex-wrap: wrap; }
        .ft-social a { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; background: rgba(255,255,255,0.08); color: #e2e8f0; border-radius: 50%; text-decoration: none; transition: background 0.2s, color 0.2s; }
        .ft-social a:hover { background: #4f46e5; color: #fff; }
        .ft-social svg { width: 18px; height: 18px; display: block; }
        .ft-hr { border: 0; border-top: 1px solid rgba(255,255,255,0.12); margin: 2rem 0 1.25rem 0; }
        .ft-bottom { display: flex; flex-direction: column; gap: 0.5rem; text-align: center; font-size: 0.8125rem; color: #94a3b8; }
        @media (min-width: 768px) { .ft-bottom { flex-direction: row; justify-content: space-between; align-items: center; text-align: left; } }
    </style>
    <footer class="ft-footer">
        <div class="ft-wrap">
            <div class="ft-grid">
                <div>
                    <h3 class="ft-brand">Mr. and Mrs. Prints</h3>
                    <p class="ft-desc">Your trusted printing shop for tarpaulins, t-shirts, stickers, and custom designs. Quality prints, delivered on time.</p>
                    <div class="ft-social">
                        <a href="https://www.facebook.com/MrandMrsPrints?mibextid=wwXIfr&rdid=WrRCAfHYvppSaNCE&share_url=https%3A%2F%2Fwww.facebook.com%2Fshare%2F18BUFaan9j%2F%3Fmibextid%3DwwXIfr" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
                    </div>
                </div>
                <div>
                    <h3 class="ft-title">Quick Links</h3>
                    <ul class="ft-list">
                        <li><a href="<?php echo $url_products; ?>">Products</a></li>
                        <li><a href="<?php echo $url_faq; ?>">FAQ</a></li>
                        <?php if (!$is_logged_in): ?>
                        <li><a href="#" data-auth-modal="login">Login</a></li>
                        <li><a href="#" data-auth-modal="register">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div>
                    <h3 class="ft-title">Our Services</h3>
                    <ul class="ft-list">
                        <li class="ft-list-item"><span class="ft-ico">✓</span> Tarpaulin Printing</li>
                        <li class="ft-list-item"><span class="ft-ico">✓</span> T-shirt Printing</li>
                        <li class="ft-list-item"><span class="ft-ico">✓</span> Stickers & Decals</li>
                        <li class="ft-list-item"><span class="ft-ico">✓</span> Sintraboard Standees</li>
                        <li class="ft-list-item"><span class="ft-ico">✓</span> Custom Layouts</li>
                    </ul>
                </div>
                <div>
                    <h3 class="ft-title">Contact</h3>
                    <ul class="ft-list">
                        <li class="ft-list-item"><span class="ft-ico">✉</span> <a href="mailto:mrandmrsprints@gmail.com">mrandmrsprints@gmail.com</a></li>
                        <li class="ft-list-item"><span class="ft-ico">☎</span> <a href="tel:+639212122293">0921 212 2293</a></li>
                        <li class="ft-list-item"><span class="ft-ico">⌖</span> #240 corner M.L. Quezon St., Cabuyao, Philippines 4025</li>
                    </ul>
                </div>
            </div>
            <hr class="ft-hr">
            <div class="ft-bottom">
                <p>&copy; <?php echo date('Y'); ?> Mr. and Mrs. Prints. All rights reserved.</p>
                <p>Made with ♥ for quality printing</p>
            </div>
        </div>
    </footer>

    <?php if (is_logged_in()): ?>
    <?php include __DIR__ . '/logout_modal.php'; ?>
    <?php include __DIR__ . '/success_modal.php'; ?>
    <?php if (is_customer()): ?>
        <?php include __DIR__ . '/profile_modal.php'; ?>
    <?php endif; ?>
    <?php endif; ?>
    <?php if (!$is_logged_in): ?>
    <?php
    require_once __DIR__ . '/google-oauth-config.php';
    $google_client_id = defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '' ? GOOGLE_CLIENT_ID : null;
    require_once __DIR__ . '/auth-modals.php';
    ?>
    <?php endif; ?>

    <!-- Alpine.js for dropdowns -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <?php if (!empty($use_landing_css)): ?>
    <!-- Hero nav: hide header on scroll; scroll hint at bottom; scroll-to-top lower right -->
    <script>
    (function() {
        var header = document.getElementById('main-header');
        var hint = document.getElementById('lp-scroll-hint');
        var scrollTopBtn = document.getElementById('lp-scroll-top');
        var hideThreshold = 120;
        var showThreshold = 50;
        var scrollTopShowAt = 200;
        function update() {
            var y = window.scrollY;
            if (header && header.classList.contains('lp-hero-nav')) {
                if (y > hideThreshold) header.classList.add('lp-header-hidden');
                else if (y <= showThreshold) header.classList.remove('lp-header-hidden');
            }
            if (hint) {
                if (y > 80) hint.classList.add('lp-scroll-hint-hidden');
                else hint.classList.remove('lp-scroll-hint-hidden');
            }
            if (scrollTopBtn) {
                if (y > scrollTopShowAt) scrollTopBtn.classList.remove('lp-scroll-top-hidden');
                else scrollTopBtn.classList.add('lp-scroll-top-hidden');
            }
        }
        if (scrollTopBtn) {
            scrollTopBtn.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
        window.addEventListener('scroll', update, { passive: true });
        update();
    })();
    </script>
    <?php endif; ?>

    <!-- PWA -->
    <script src="<?php echo $base_url; ?>/public/assets/js/pwa.js"></script>
</body>
</html>
