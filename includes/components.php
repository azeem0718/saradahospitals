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
 * A circular portrait beside the name, rather than a wide image well. Until
 * real photographs are supplied an empty well looks like a broken image,
 * whereas a framed portrait reads as a deliberate placeholder.
 *
 * The card carries live state, and that is the point of it. A doctor card that
 * lists a name, a degree and a speciality tells a patient nothing they cannot
 * guess from the sign outside; what they actually came to find out is whether
 * this doctor is sitting right now, whether there is still a token left today,
 * and if not, when the next one is. All three come from availability(), which
 * is the same function the booking page answers with — so the card cannot
 * promise a session that book.php would then refuse.
 *
 * Everything optional degrades to nothing. The hospital has not filled in
 * years of experience, languages or a registration number, so those lines are
 * simply absent rather than rendered empty; the day they are entered in the
 * admin panel they appear.
 */
function doctor_cards(array $doctors, bool $withLink = true): void
{
    ?>
    <div class="grid grid-2">
      <?php foreach ($doctors as $doc): ?>
        <?php
          $id      = (int) $doc['id'];
          $out     = doctor_outlook($id);
          $short   = doctor_short_name((string) $doc['name']);
          /* The sessions table below already prints today's times, so this
             line carries only what that table cannot: which days he sits. A
             hospital that has typed its own timings string wins, as always. */
          $week = '';
          if (trim((string) ($doc['opd_timings'] ?? '')) !== '') {
              $week = implode(' &middot; ', array_map('e', profile_lines((string) $doc['opd_timings'])));
          } elseif (($days = doctor_days_phrase($id)) !== '') {
              $week = e($days === 'Every day' ? 'Consults every day' : 'Consults ' . $days);
          }

          /* One line, in the patient's own terms. "In OP now" is a claim about
             the published schedule and is only made when the session is one
             the doctor is actually taking; the rest name a bookable session. */
          $state = $out['state'];
          $status = match ($state) {
              'now'   => 'In OP now &middot; ' . e($out['now']['label'])
                         . ' until ' . e(format_time($out['now']['end_time'])),
              'today' => 'Next today &middot; ' . e($out['next']['label'])
                         . ' &middot; ' . e($out['next']['timing']),
              'later' => 'Next: ' . e($out['next']['when'])
                         . ' &middot; ' . e($out['next']['label']),
              default => 'No online tokens in the next few days',
          };
        ?>
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
              <h3><a href="doctor.php?slug=<?= e($doc['slug']) ?>"><?= e($doc['name']) ?></a></h3>
              <p class="doctor-quals"><?= e($doc['qualifications']) ?></p>
            </div>
          </div>

          <p class="doctor-spec">
            <?= e($doc['designation'] !== '' ? $doc['designation'] : $doc['speciality']) ?>
          </p>

          <?php
            /* Only what the hospital has actually entered. An empty row here
               would be worse than no row: it reads as missing data. */
            $facts = [];
            if (!empty($doc['experience_years'])) {
                $facts[] = ['award', (int) $doc['experience_years'] . ' years experience'];
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

          <p class="doctor-state doctor-state-<?= e($state) ?>">
            <span class="doctor-dot" aria-hidden="true"></span>
            <span><?= $status ?></span>
          </p>

          <?php if ($out['today']): ?>
            <ul class="doctor-sessions">
              <?php foreach ($out['today'] as $slot): ?>
                <?php
                  /* The bar draws what is LEFT, not what is gone. Drawn the
                     other way it says the opposite of the number beside it —
                     an untouched session with all thirty tokens free showed a
                     full-width empty track, which reads as "full".
                     The colour is the same reading again: comfortable, going,
                     nearly gone. Both are backed by the sentence beside them,
                     so neither the length nor the hue is load-bearing. */
                  $pct  = $slot['cap'] > 0
                      ? max(0, min(100, (int) round($slot['remaining'] / $slot['cap'] * 100)))
                      : 0;
                  $band = $pct >= 50 ? 'ample' : ($pct >= 20 ? 'going' : 'last');
                ?>
                <li class="<?= $slot['available'] ? 'is-open' : 'is-shut' ?>">
                  <span class="ds-name"><?= e($slot['label']) ?></span>
                  <span class="ds-time"><?= e($slot['timing']) ?></span>
                  <?php if ($slot['available']): ?>
                    <span class="ds-meter ds-<?= $band ?>" aria-hidden="true">
                      <i style="width: <?= $pct ?>%"></i>
                    </span>
                    <span class="ds-left">
                      <strong><?= (int) $slot['remaining'] ?></strong> of
                      <?= (int) $slot['cap'] ?> left
                    </span>
                  <?php else: ?>
                    <span class="ds-why"><?= e($slot['reason'] ?? 'Not available') ?></span>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if ($week !== ''): ?>
            <p class="doctor-week">
              <?= icon('calendar') ?>
              <span><?= $week ?></span>
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
