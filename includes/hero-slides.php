<?php
/**
 * Home hero: a crossfading slideshow of what the hospital actually treats.
 *
 * Deliberately not a carousel that slides. Sideways motion at the top of a
 * page fights the reader's own scrolling and is the usual reason these things
 * feel cheap; a slow crossfade with a drifting image reads calm and costs
 * nothing in layout. Nothing moves horizontally at any point.
 *
 * The whole first slide is rendered as normal markup — heading, text and the
 * booking bar — so a browser with no JavaScript, or a reader who prefers no
 * motion, gets a perfectly good static hero rather than a blank box. The
 * script only takes over the fading.
 *
 * Expects $doctors and $nextSlot in scope.
 */

declare(strict_types=1);

require_once __DIR__ . '/content.php';
require_once __DIR__ . '/site-images.php';

$heroSlides = list_shaped('hero.slides');
if (!$heroSlides) {
    require __DIR__ . '/hero-classic.php';
    return;
}
?>
<section class="hero hero-show" data-hero-show
         aria-roledescription="carousel" aria-label="What we treat">

  <!-- The pictures. One layer per slide, stacked; only opacity and a slow
       scale ever change, so nothing here can cause a reflow. -->
  <div class="hs-stage" aria-hidden="true">
    <?php foreach ($heroSlides as $i => $slide): ?>
      <?php
        $slot = 'hero-slide-' . ($i + 1);
        $full = site_image_css_url($slot);
        $small = site_image_sm_url($slot, true);
        if ($full === null) {
            continue;
        }
        $style = "--hs-img:url('" . e($full) . "')";
        if ($small !== null) {
            $style .= ";--hs-img-sm:url('" . e($small) . "')";
        }
      ?>
      <div class="hs-layer<?= $i === 0 ? ' is-on' : '' ?>"
           data-hs-layer="<?= $i ?>" style="<?= $style ?>"></div>
    <?php endforeach; ?>
    <span class="hs-scrim"></span>
  </div>

  <div class="wrap hs-inner">
    <p class="hero-place hs-place">
      <?= icon('location') ?>
      <?= e(text('home.hero.place')) ?>
    </p>

    <!-- The text block the slides rewrite. aria-live is off while the show
         rotates on its own: a screen reader being interrupted every six
         seconds by a banner nobody asked to hear is worse than silence. The
         script turns it polite the moment a reader uses the dots, because a
         change they asked for is one they should be told about. -->
    <!-- The text block the slides rewrite. aria-live is off while the show
         rotates on its own: a screen reader being interrupted every six
         seconds by a banner nobody asked to hear is worse than silence. The
         script turns it polite the moment a reader uses the dots, because a
         change they asked for is one they should be told about. -->
    <div class="hs-copy" data-hs-copy aria-live="off" aria-atomic="true">
      <?php foreach ($heroSlides as $i => $slide): ?>
        <div class="hs-text<?= $i === 0 ? ' is-on' : '' ?>" data-hs-text="<?= $i ?>"
             <?= $i === 0 ? '' : 'hidden' ?>>
          <span class="hs-mark" aria-hidden="true"><?= icon($slide['icon']) ?></span>
          <h1 class="hs-title"><?= e($slide['title']) ?></h1>
          <p class="hs-lede"><?= e($slide['text']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Two plain choices rather than a search form: book, or telephone. A
         hero is where somebody decides what to do, not where they fill
         anything in — the booking page asks for the details, and it asks for
         them on a page built to. -->
    <div class="hs-actions">
      <a class="btn btn-lg btn-hero-primary" href="book.php">
        <?= icon('ticket') ?> Book an OP Token
      </a>
      <a class="btn btn-lg btn-hero-ghost" href="tel:<?= e(HOSPITAL['mobile']) ?>">
        <?= icon('phone') ?> Emergency <?= e(HOSPITAL['mobile_display']) ?>
      </a>
    </div>

    <?php if ($nextSlot !== null): ?>
      <!-- Live, and worth keeping: it is the one line on the page that proves
           the booking system is a real queue rather than a contact form. -->
      <p class="hero-live hs-live">
        <span class="live-dot" aria-hidden="true"></span>
        <strong><?= e($nextSlot['when']) ?> &middot; <?= e($nextSlot['label']) ?></strong>
        session open &mdash; <?= $nextSlot['remaining'] ?> token<?= $nextSlot['remaining'] === 1 ? '' : 's' ?> left
      </p>
    <?php endif; ?>

    <!-- Manual control. Real buttons, so they are reachable by keyboard and
         announced properly; the auto-advance stops the moment one is used. -->
    <div class="hs-dots" role="group" aria-label="Choose a slide">
      <?php foreach ($heroSlides as $i => $slide): ?>
        <button type="button" class="hs-dot<?= $i === 0 ? ' is-on' : '' ?>"
                data-hs-dot="<?= $i ?>"
                aria-label="<?= e($slide['title']) ?>"
                <?= $i === 0 ? 'aria-current="true"' : '' ?>><span></span></button>
      <?php endforeach; ?>
    </div>
  </div>
</section>
