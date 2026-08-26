<?php
/**
 * Credits for the openly licensed photographs used on the site.
 *
 * Exists to satisfy the attribution terms of CC BY / CC BY-SA images. Linked
 * from the footer only while there is something to credit.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/photo-credits.php';

$credits = photo_credits();

$pageTitle       = 'Photo Credits';
$pageDescription = 'Attribution for the openly licensed photographs used on this website.';
$activeNav       = '';

require __DIR__ . '/includes/header.php';
page_hero('Photo Credits', 'The openly licensed photographs used on this website.', 'Photo Credits');
?>

<section class="section">
  <div class="wrap wrap-narrow">
    <?php if (!$credits): ?>
      <div class="notice notice-info mb-0">
        <?= icon('info') ?>
        <p>All photographs currently on this website are Sarada Nursing Home's own.</p>
      </div>
    <?php else: ?>
      <p class="lede mb-2">
        Alongside the hospital's own photographs, this website uses the following
        openly licensed images. We are grateful to their photographers.
      </p>
      <ul class="credit-list">
        <?php foreach ($credits as [$use, $title, $author, $licence, $url]): ?>
          <li>
            <strong><?= e($use) ?></strong>
            <span>
              &ldquo;<?= e($title) ?>&rdquo; by <?= e($author) ?>,
              <?= e($licence) ?> &middot;
              <a href="<?= e($url) ?>" rel="noopener nofollow">source</a>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
