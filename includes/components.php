<?php
/**
 * Reusable page blocks shared by several pages.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/icons.php';

/** Google Maps embed centred on the hospital, plus a directions link. */
function map_block(): void
{
    $lat = HOSPITAL['map']['lat'];
    $lng = HOSPITAL['map']['lng'];
    $q   = rawurlencode(HOSPITAL['name'] . ', ' . HOSPITAL['address']['line2'] . ', Kandukur');
    $src = "https://maps.google.com/maps?q={$q}&ll={$lat},{$lng}&z=16&output=embed";
    ?>
    <div class="map-embed">
      <iframe src="<?= e($src) ?>"
              title="Map showing <?= e(HOSPITAL['name']) ?>, Kandukur"
              loading="lazy" referrerpolicy="no-referrer-when-downgrade"
              allowfullscreen></iframe>
    </div>
    <?php
}

/** Address, phones and directions as a definition-style list. */
function contact_details(): void
{
    ?>
    <ul class="contact-list">
      <li>
        <span class="card-icon"><?= icon('location') ?></span>
        <span>
          <strong>Our Address</strong>
          <span>
            <?= e(HOSPITAL['address']['line1']) ?><br>
            <?= e(HOSPITAL['address']['line2']) ?><br>
            <?= e(HOSPITAL['address']['district']) ?>
          </span>
        </span>
      </li>
      <li>
        <span class="card-icon red"><?= icon('phone') ?></span>
        <span>
          <strong>24/7 Emergency</strong>
          <a href="tel:<?= e(HOSPITAL['mobile']) ?>"><?= e(HOSPITAL['mobile_display']) ?></a>
        </span>
      </li>
      <li>
        <span class="card-icon"><?= icon('phone') ?></span>
        <span>
          <strong>General Enquiries</strong>
          <a href="tel:<?= e(HOSPITAL['landline']) ?>"><?= e(HOSPITAL['landline_display']) ?></a>
        </span>
      </li>
      <li>
        <span class="card-icon green"><?= icon('whatsapp') ?></span>
        <span>
          <strong>WhatsApp</strong>
          <a href="https://wa.me/<?= e(HOSPITAL['whatsapp']) ?>" target="_blank" rel="noopener">
            <?= e(HOSPITAL['mobile_display']) ?>
          </a>
        </span>
      </li>
      <li>
        <span class="card-icon gold"><?= icon('clock') ?></span>
        <span>
          <strong>Hospital Hours</strong>
          <span>Emergency care 24 hours, every day.<br>
                OP consultation timings are shown on the booking page.</span>
        </span>
      </li>
    </ul>
    <?php
}

/** The two standing offers from the hospital letterhead. */
function offers_strip(): void
{
    ?>
    <section class="offer-strip">
      <div class="wrap">
        <div class="grid grid-2">
          <?php foreach (OFFERS as $offer): ?>
            <div class="offer-item">
              <span class="card-icon gold"><?= icon($offer['icon']) ?></span>
              <div>
                <h3><?= e($offer['title']) ?></h3>
                <p><?= e($offer['text']) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php
}

/**
 * Doctor cards.
 *
 * A circular portrait beside the name, rather than a wide image well. Until
 * real photographs are supplied an empty well looks like a broken image,
 * whereas a framed portrait reads as a deliberate placeholder.
 */
function doctor_cards(array $doctors, bool $withLink = true): void
{
    ?>
    <div class="grid grid-2">
      <?php foreach ($doctors as $doc): ?>
        <article class="doctor-card">
          <div class="doctor-top">
            <div class="doctor-portrait">
              <?php if (!empty($doc['photo']) && is_file(__DIR__ . '/../assets/img/' . $doc['photo'])): ?>
                <img src="assets/img/<?= e($doc['photo']) ?>" alt="<?= e($doc['name']) ?>" loading="lazy"
                     width="88" height="88">
              <?php else: ?>
                <?= doctor_avatar('') ?>
              <?php endif; ?>
            </div>
            <div class="doctor-head">
              <h3><?= e($doc['name']) ?></h3>
              <p class="doctor-quals"><?= e($doc['qualifications']) ?></p>
            </div>
          </div>

          <p class="doctor-spec"><?= e($doc['speciality']) ?></p>

          <?php if ($withLink): ?>
            <a class="btn btn-outline btn-block" href="book.php?doctor=<?= (int) $doc['id'] ?>">
              <?= icon('ticket') ?> Book a Token
            </a>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
    <?php
}

/** Standard emergency warning shown above booking forms. */
function emergency_notice(): void
{
    ?>
    <div class="notice notice-emergency">
      <?= icon('alert') ?>
      <p>
        <strong>In an emergency, do not book online.</strong>
        Come straight to the hospital or call
        <a href="tel:<?= e(HOSPITAL['mobile']) ?>"><?= e(HOSPITAL['mobile_display']) ?></a>.
        Our emergency department is open 24 hours.
      </p>
    </div>
    <?php
}

/** Closing call-to-action band used at the bottom of content pages. */
function cta_band(string $heading = 'Need to see a doctor?', string $text = ''): void
{
    if ($text === '') {
        $text = 'Book a token online in under a minute, or call us and our reception will help you.';
    }
    ?>
    <section class="section section-navy">
      <div class="wrap">
        <div class="section-head center mb-2">
          <h2><?= e($heading) ?></h2>
          <p class="lede"><?= e($text) ?></p>
        </div>
        <div class="btn-row center">
          <a class="btn btn-lg btn-emergency" href="tel:<?= e(HOSPITAL['mobile']) ?>">
            <?= icon('phone') ?> <?= e(HOSPITAL['mobile_display']) ?>
          </a>
          <a class="btn btn-lg btn-ghost-light" href="book.php">
            <?= icon('ticket') ?> Book a Token
          </a>
        </div>
      </div>
    </section>
    <?php
}

/** Compact page hero used by every inner page. */
function page_hero(string $title, string $subtitle = '', string $crumb = '', string $art = '', string $tone = ''): void
{
    $classes = 'page-hero';
    $style   = '';
    if ($art !== '') {
        $file = 'assets/img/hero/' . $art . '.svg';
        if (is_file(__DIR__ . '/../' . $file)) {
            $classes .= ' has-art';
            $style = ' style="--hero-art:url(\'' . e(asset_url($file)) . '\')"';
        }
    }
    if ($tone !== '') {
        $classes .= ' page-hero-' . $tone;
    }
    ?>
    <section class="<?= e($classes) ?>"<?= $style ?>>
      <div class="wrap">
        <?php if ($crumb !== ''): ?>
          <p class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="sep" aria-hidden="true">/</span>
            <span><?= e($crumb) ?></span>
          </p>
        <?php endif; ?>
        <h1><?= e($title) ?></h1>
        <?php if ($subtitle !== ''): ?><p><?= e($subtitle) ?></p><?php endif; ?>
      </div>
    </section>
    <?php
}
