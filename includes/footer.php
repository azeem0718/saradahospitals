</main>

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-grid">

      <div class="footer-brand">
        <div class="brand">
          <?= logo_mark() ?>
          <span class="brand-text">
            <span class="brand-name"><?= brand_name_html() ?></span>
            <span class="brand-tag"><?= e(HOSPITAL['tagline']) ?></span>
          </span>
        </div>
        <p>
          A 24-hour nursing home in Kandukur providing General Medicine,
          Diabetology and Obstetrics &amp; Gynaecology care, with ICU,
          an in-house laboratory and air-conditioned rooms.
        </p>
      </div>

      <div>
        <h4>Care</h4>
        <ul class="footer-links">
          <li><a href="services.php">All Services</a></li>
          <li><a href="diabetic-centre.php">Diabetic Centre</a></li>
          <li><a href="maternity.php">Maternity Care</a></li>
          <li><a href="emergency.php">Emergency &amp; ICU</a></li>
          <li><a href="facilities.php">Facilities</a></li>
        </ul>
      </div>

      <div>
        <h4>Hospital</h4>
        <ul class="footer-links">
          <li><a href="about.php">About Us</a></li>
          <li><a href="doctors.php">Our Doctors</a></li>
          <li><a href="tariff.php">Tariff</a></li>
          <li><a href="gallery.php">Gallery</a></li>
          <li><a href="book.php">Book a Token</a></li>
          <li><a href="queue.php">Live Queue</a></li>
        </ul>
      </div>

      <div>
        <h4>Reach Us</h4>
        <ul class="footer-links">
          <li>
            <a href="<?= e(HOSPITAL['map']['link']) ?>" target="_blank" rel="noopener">
              <?= e(HOSPITAL['address']['line1']) ?>,<br>
              <?= e(HOSPITAL['address']['line2']) ?>,<br>
              <?= e(HOSPITAL['address']['district']) ?>
            </a>
          </li>
          <li><a href="tel:<?= e(HOSPITAL['mobile']) ?>">Emergency: <?= e(HOSPITAL['mobile_display']) ?></a></li>
          <li><a href="tel:<?= e(HOSPITAL['landline']) ?>">Enquiries: <?= e(HOSPITAL['landline_display']) ?></a></li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      <span>
        &copy; <?= date('Y') ?> <?= e(HOSPITAL['name']) ?>, Kandukur. All rights reserved.<?php
        require_once __DIR__ . '/photo-credits.php';
        if (photo_credits()): ?> &middot; <a href="credits.php">Photo credits</a><?php endif; ?>
      </span>
      <span>Open 24 hours &middot; 7 days a week</span>
    </div>
  </div>
</footer>

<div class="mobile-bar" aria-label="Quick actions">
  <a class="fab fab-call" href="tel:<?= e(HOSPITAL['mobile']) ?>" aria-label="Call the hospital">
    <?= icon('phone') ?>
  </a>
  <a class="fab fab-wa"
     href="https://wa.me/<?= e(HOSPITAL['whatsapp']) ?>?text=<?= rawurlencode('Hello, I would like to enquire about an appointment at Sarada Nursing Home.') ?>"
     target="_blank" rel="noopener" aria-label="Message us on WhatsApp">
    <?= icon('whatsapp') ?>
  </a>
  <a class="fab fab-token" href="book.php">
    <?= icon('ticket') ?><span>Token</span>
  </a>
</div>

<?php if (($heroSlides ?? null) !== null): ?>
<script src="<?= e(asset('assets/js/hero.js')) ?>" defer></script>
<?php endif; ?>
<script src="<?= e(asset('assets/js/select.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/reveal.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
</body>
</html>
