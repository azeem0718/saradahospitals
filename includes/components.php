<?php
/**
 * Reusable page blocks shared by several pages.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/site-images.php';
require_once __DIR__ . '/booking.php';

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
 * About the doctor, and only about the doctor.
 *
 * This card previously carried live booking state — today's sessions, their
 * clock times, and a meter counting tokens down out of thirty. It was accurate
 * and it was the wrong thing to put here: it made a hospital's "meet our
 * doctors" section read like the reception desk's own screen. A patient
 * arriving at this part of the page is deciding who to see, not yet when. The
 * booking page is where availability belongs, it is one tap away, and it is
 * the only place that can be trusted at the moment of booking anyway.
 *
 * What is here instead is what the hospital actually knows about each of them:
 * their qualification, what they treat, a line of their own background, and
 * the conditions patients come to them for. All of it real — the bio and the
 * conditions are the hospital's own words from the doctor's profile, and any
 * field left blank simply does not render rather than showing an empty row.
 *
 * The one piece of live state kept is a single quiet line saying whether the
 * doctor is consulting today. No times, no counts, no session names: just the
 * one fact worth knowing before choosing, and it still comes from
 * availability(), so it cannot contradict the booking page.
 */
function doctor_cards(array $doctors, bool $withLink = true): void
{
    ?>
    <div class="grid grid-2">
      <?php foreach ($doctors as $doc): ?>
        <?php
          $id    = (int) $doc['id'];
          $short = doctor_short_name((string) $doc['name']);
          $out   = doctor_outlook($id);

          /* Consulting today, or the next day they are. Nothing finer than a
             day: the moment this starts naming sessions and times it becomes
             the timetable again. */
          $today = $out['state'] === 'now' || $out['state'] === 'today';
          $avail = $today
              ? 'Consulting today'
              : ($out['next'] !== null ? 'Next consulting ' . strtolower($out['next']['when']) : '');

          $days = trim((string) ($doc['opd_timings'] ?? '')) !== ''
              ? ''                              /* a typed timings string belongs on the profile page */
              : doctor_days_phrase($id);

          /* The conditions patients actually come for. Four is enough to say
             what this doctor is for; the rest are on the profile. */
          $treats = array_slice(profile_lines((string) ($doc['services'] ?? '')), 0, 4);
          $more   = max(0, count(profile_lines((string) ($doc['services'] ?? ''))) - count($treats));

          $blurb = content_excerpt((string) ($doc['bio'] ?? ''), 155);
        ?>
        <article class="doctor-card">
          <div class="doctor-top">
            <div class="doctor-portrait">
              <?php if (!empty($doc['photo']) && is_file(__DIR__ . '/../assets/img/' . $doc['photo'])): ?>
                <img src="assets/img/<?= e($doc['photo']) ?>" alt="<?= e($doc['name']) ?>" loading="lazy"
                     width="104" height="104">
              <?php else: ?>
                <?= doctor_avatar('') ?>
              <?php endif; ?>
            </div>
            <div class="doctor-head">
              <h3><a href="doctor.php?slug=<?= e($doc['slug']) ?>"><?= e($doc['name']) ?></a></h3>
              <p class="doctor-quals"><?= e($doc['qualifications']) ?></p>
              <p class="doctor-spec">
                <?= e($doc['designation'] !== '' ? $doc['designation'] : $doc['speciality']) ?>
              </p>
            </div>
          </div>

          <?php
            /* Only what the hospital has actually entered. An empty row here
               would be worse than no row: it reads as missing data. */
            $facts = [];
            if (!empty($doc['experience_years'])) {
                $facts[] = ['award', (int) $doc['experience_years'] . ' years experience'];
            }
            if ($days !== '') {
                $facts[] = ['calendar', $days === 'Every day' ? 'Consults every day' : 'Consults ' . $days];
            }
            if (trim((string) $doc['languages']) !== '') {
                $facts[] = ['users', (string) $doc['languages']];
            }
            if (trim((string) $doc['reg_no']) !== '') {
                $facts[] = ['shield', 'Reg. ' . $doc['reg_no']];
            }
          ?>
          <?php if ($facts): ?>
            <ul class="doctor-facts">
              <?php foreach ($facts as [$ic, $label]): ?>
                <li><?= icon($ic) ?><span><?= e($label) ?></span></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if ($blurb !== ''): ?>
            <p class="doctor-bio"><?= e($blurb) ?></p>
          <?php endif; ?>

          <?php if ($treats): ?>
            <div class="doctor-treats">
              <h4>Treats</h4>
              <ul>
                <?php foreach ($treats as $t): ?>
                  <li><?= e($t) ?></li>
                <?php endforeach; ?>
                <?php if ($more > 0): ?>
                  <li class="is-more">+<?= $more ?> more</li>
                <?php endif; ?>
              </ul>
            </div>
          <?php endif; ?>

          <div class="doctor-foot">
            <?php if ($avail !== ''): ?>
              <p class="doctor-avail<?= $today ? ' is-today' : '' ?>">
                <span class="doctor-dot" aria-hidden="true"></span><?= e($avail) ?>
              </p>
            <?php endif; ?>
            <div class="doctor-actions">
              <?php if ($withLink): ?>
                <a class="btn btn-primary" href="book.php?doctor=<?= $id ?>">
                  <?= icon('ticket') ?> Book with <?= e($short) ?>
                </a>
              <?php endif; ?>
              <a class="btn btn-outline" href="doctor.php?slug=<?= e($doc['slug']) ?>">
                Full profile
              </a>
            </div>
          </div>
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

    // A photograph reception uploaded wins over the drawn artwork. Both are
    // optional; with neither, the banner is its plain gradient.
    if ($art !== '') {
        [$suffix, $style] = hero_art_attrs($art, $art);
        $classes .= $suffix;
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
