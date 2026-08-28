<?php
/**
 * Home hero: the words on one side, the photograph on the other.
 *
 * Nothing is laid over the picture — no tint, no scrim, no text. Putting
 * words on a photograph always ends the same way: either the photograph gets
 * darkened until it is wallpaper, or the words sit at a contrast nobody can
 * read. This hospital's hero carries an emergency number, so unreadable was
 * never an option, which left darkening the picture — and a darkened picture
 * is not really being shown at all. Giving each its own half settles it: the
 * photograph is seen exactly as taken, and the words sit on the site's own
 * paper where they are simply black on cream.
 *
 * Deliberately not a carousel that slides. Sideways motion at the top of a
 * page fights the reader's own scrolling and is the usual reason these things
 * feel cheap. The pictures crossfade in place, and the slow drift lives on the
 * frame rather than on each picture, so every layer shares one movement and
 * two pictures mid-crossfade can never be at different sizes.
 *
 * The whole first slide is rendered as normal markup, so a browser with no
 * JavaScript, or a reader who prefers no motion, gets a perfectly good static
 * hero rather than a blank box. The script only takes over the fading.
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

  <!-- The photograph. Its own half of the hero, with nothing on top of it —
       the swipe target on a phone, and the only thing that ever moves. -->
  <div class="hs-frame" data-hs-frame aria-hidden="true">
    <div class="hs-stage">
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
    </div>
  </div>

  <div class="hs-words">
    <p class="hero-place hs-place">
      <?= icon('location') ?>
      <?= e(text('home.hero.place')) ?>
    </p>

    <!-- The text block the slides rewrite. aria-live is off while the show
         rotates on its own: a screen reader being interrupted every three and
         a half seconds by a banner nobody asked to hear is worse than silence.
         The script turns it polite the moment a reader takes control, because
         a change they asked for is one they should be told about. -->
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
      <?php
        /*
         * The corner brackets are fixed-size SVGs pinned to each corner rather
         * than one frame stretched around the button: a stretched rounded rect
         * turns its corners into ellipses at any width but the one it was drawn
         * for. Each bracket draws itself in on hover by running its dash offset
         * to zero, which is why the paths carry pathLength="1" — the maths then
         * holds whatever the button's real size turns out to be.
         */
        $bracket = static function (string $where): string {
            // Inset by a pixel and drawn to an 11px arc: the button clips its own
            // overflow at a 15px radius, so a bracket sitting flush to the edge
            // loses half its stroke to the corner. This one sits inside it.
            $d = $where === 'tl' ? 'M39 1 H12 A11 11 0 0 0 1 12 V39'
                                 : 'M1 39 H28 A11 11 0 0 0 39 28 V1';
            return '<svg class="hbtn-corner hbtn-corner-' . $where . '" viewBox="0 0 40 40"'
                 . ' aria-hidden="true" focusable="false"><path d="' . $d . '" pathLength="1"/></svg>';
        };
      ?>

      <a class="hbtn hbtn-primary" href="book.php">
        <?= $bracket('tl') ?><?= $bracket('br') ?>
        <span class="hbtn-sheen" aria-hidden="true"></span>
        <span class="hbtn-label"><?= icon('ticket') ?><span>Book an OP Token</span></span>
        <span class="hbtn-go" aria-hidden="true"><?= icon('arrow-right') ?></span>
      </a>

      <a class="hbtn hbtn-ghost" href="tel:<?= e(HOSPITAL['mobile']) ?>">
        <?= $bracket('tl') ?><?= $bracket('br') ?>
        <span class="hbtn-sheen" aria-hidden="true"></span>
        <span class="hbtn-label"><?= icon('phone') ?><span>Emergency <?= e(HOSPITAL['mobile_display']) ?></span></span>
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
         announced properly; the auto-advance stops the moment one is used.
         The active one fills over the length of its turn, so the wait is
         visible rather than a picture changing out of nowhere. -->
    <div class="hs-dots" role="group" aria-label="Choose a slide">
      <?php foreach ($heroSlides as $i => $slide): ?>
        <button type="button" class="hs-dot<?= $i === 0 ? ' is-on' : '' ?>"
                data-hs-dot="<?= $i ?>"
                aria-label="<?= e($slide['title']) ?>"
                <?= $i === 0 ? 'aria-current="true"' : '' ?>><span><i></i></span></button>
      <?php endforeach; ?>
    </div>
  </div>
</section>
