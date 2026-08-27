<?php
/**
 * The original home hero: headline, booking bar and the drawn illustration.
 *
 * Kept intact and working when the slideshow replaced it, so switching back is
 * a setting rather than an archaeology exercise. Admin -> Settings chooses
 * which hero the home page uses. Expects $doctors and $nextSlot in scope.
 */
declare(strict_types=1);
?>
<section class="hero">
  <div class="wrap">
    <div class="hero-grid">

      <div class="hero-copy">
        <p class="hero-place">
          <?= icon('location') ?>
          <?= e(text('home.hero.place')) ?>
        </p>

        <h1><?= text_html('home.hero.title') ?></h1>

        <p class="hero-lede"><?= e(text('home.hero.lede')) ?></p>

        <?php require __DIR__ . '/hero-findbar.php'; ?>
      </div>

      <div class="hero-art" aria-hidden="false">
        <span class="hero-art-disc" aria-hidden="true"></span>
        <?= hospital_illustration() ?>
      </div>

    </div>
  </div>
</section>