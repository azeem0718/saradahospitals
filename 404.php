<?php
declare(strict_types=1);

http_response_code(404);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = 'Page Not Found';
$pageDescription = 'The page you were looking for could not be found.';

/* BREADCRUMB-SEO */
$pageNoIndex     = true;
$activeNav       = '';

require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="wrap wrap-narrow text-center">
    <span class="eyebrow">Error 404</span>
    <h1>We could not find that page</h1>
    <p class="lede mb-3">
      The link may be out of date, or the address may have been typed slightly
      differently. Everything below is a good place to start again.
    </p>

    <div class="btn-row" style="justify-content:center">
      <a class="btn btn-primary btn-lg" href="index.php"><?= icon('arrow-right') ?> Go to the home page</a>
      <a class="btn btn-emergency btn-lg" href="tel:<?= e(HOSPITAL['mobile']) ?>">
        <?= icon('phone') ?> Call <?= e(HOSPITAL['mobile_display']) ?>
      </a>
    </div>

    <div class="grid grid-3 mt-3" style="text-align:left">
      <a class="card card-link" href="book.php">
        <span class="card-icon"><?= icon('ticket') ?></span>
        <h3>Book a Token</h3>
        <p>Reserve your OP consultation token online.</p>
      </a>
      <a class="card card-link" href="services.php">
        <span class="card-icon"><?= icon('stethoscope') ?></span>
        <h3>Our Services</h3>
        <p>See what we treat and which doctor to consult.</p>
      </a>
      <a class="card card-link" href="contact.php">
        <span class="card-icon"><?= icon('location') ?></span>
        <h3>Contact Us</h3>
        <p>Address, phone numbers and directions.</p>
      </a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
