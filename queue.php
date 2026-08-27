<?php
/**
 * Live queue status.
 *
 * Answers the question every waiting patient actually has — "how far away is
 * my turn?" — without them standing at reception to ask. With a booking
 * reference the page shows their own token against the number now being
 * served; without one it still shows today's queue for each doctor. The page
 * refreshes itself, so it can be left open on the way to the hospital.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/booking.php';

$activeNav = '';
$today     = date('Y-m-d');
$ref       = strtoupper(trim(query('ref')));
$booking   = null;
$notFound  = false;

if ($ref !== '') {
    if (preg_match('/^SN[2-9BCDFGHJKLMNPQRSTVWXZ]{6}$/', $ref) === 1) {
        $booking = get_booking_by_reference($ref);
    }
    $notFound = $booking === null;
}

$mine = null;
if ($booking !== null) {
    $counts = queue_counts(
        (int) $booking['doctor_id'],
        (string) $booking['booking_date'],
        (string) $booking['session']
    );
    $mine = [
        'today'       => $booking['booking_date'] === $today,
        'live'        => in_array($booking['status'], ['booked', 'arrived'], true),
        'now_serving' => $counts['now_serving'],
        'ahead'       => queue_position($booking),
    ];
}

$board = [];
foreach (get_doctors() as $doctor) {
    $queue = doctor_queue_today((int) $doctor['id']);
    if ($queue !== null) {
        $board[] = ['doctor' => $doctor, 'queue' => $queue];
    }
}

$pageTitle       = 'Live Queue';
$pageDescription = 'See which token is being served right now at Sarada Nursing Home, '
                 . 'Kandukur, and how many patients are ahead of you.';

require __DIR__ . '/includes/header.php';
page_hero(
    'Live Queue',
    'The token now being served for each doctor, updated as reception calls patients in.',
    'Live Queue'
);
?>

<section class="section" id="queue-root"
         data-ref="<?= e($booking !== null ? $booking['reference'] : '') ?>"
         data-status="<?= e($booking !== null ? $booking['status'] : '') ?>">
  <div class="wrap wrap-narrow">

  <?php if ($notFound): ?>
    <div class="notice notice-emergency">
      <?= icon('alert') ?>
      <p>
        <strong>We could not find that reference.</strong>
        Please check the letters and numbers and try again, or call
        <a href="tel:<?= e(HOSPITAL['landline']) ?>"><?= e(HOSPITAL['landline_display']) ?></a>.
      </p>
    </div>
  <?php endif; ?>

  <?php if ($booking !== null): ?>

    <?php if ($booking['status'] === 'cancelled'): ?>
      <div class="notice notice-emergency">
        <?= icon('alert') ?>
        <p><strong>This booking has been cancelled.</strong>
           To see the doctor, please <a href="book.php">book a new token</a>.</p>
      </div>
    <?php elseif ($booking['booking_date'] < $today): ?>
      <div class="notice notice-info">
        <?= icon('info') ?>
        <p><strong>This token was for <?= e(format_date($booking['booking_date'])) ?>.</strong>
           To see the doctor again, please <a href="book.php">book a new token</a>.</p>
      </div>
    <?php elseif (!$mine['today']): ?>
      <div class="notice notice-info">
        <?= icon('info') ?>
        <p><strong>Your token is for <?= e(format_date($booking['booking_date'])) ?>,
           <?= e(session_label($booking['session'])) ?> session.</strong>
           Come back to this page that day to follow the queue live.</p>
      </div>
    <?php endif; ?>

    <div class="queue-mine<?= $mine['today'] && $mine['live'] ? '' : ' is-muted' ?>">
      <div class="queue-mine-head">
        <span class="q-doctor"><?= e($booking['doctor_name']) ?></span>
        <span class="q-session"><?= e(format_date($booking['booking_date'])) ?> ·
          <?= e(session_label($booking['session'])) ?> session</span>
      </div>

      <div class="queue-mine-grid">
        <div class="q-stat">
          <span class="q-label">Now serving</span>
          <span class="q-num" data-q="now-serving"><?= $mine['now_serving'] > 0 ? (int) $mine['now_serving'] : '—' ?></span>
        </div>
        <div class="q-stat q-stat-you">
          <span class="q-label">Your token</span>
          <span class="q-num"><?= (int) $booking['token_no'] ?></span>
        </div>
        <div class="q-stat">
          <span class="q-label">Ahead of you</span>
          <span class="q-num" data-q="ahead"><?= $mine['live'] ? (int) $mine['ahead'] : '—' ?></span>
        </div>
      </div>

      <p class="queue-mine-foot">
        <span class="pill <?= $booking['status'] === 'completed' ? 'pill-green' : 'pill-navy' ?>" data-q="status">
          <?= e(status_label($booking['status'])) ?>
        </span>
        <span data-q="hint">
          <?php if (!$mine['live']): ?>
            &nbsp;
          <?php elseif ($mine['ahead'] === 0): ?>
            You are next — please be near the consultation room.
          <?php elseif ($mine['ahead'] <= 3): ?>
            Almost your turn — please be at the hospital.
          <?php else: ?>
            Reference <strong><?= e($booking['reference']) ?></strong> ·
            this page updates itself every few seconds.
          <?php endif; ?>
        </span>
      </p>
    </div>

  <?php endif; ?>

  <div class="section-head<?= $booking !== null ? ' mt-4' : '' ?>">
    <h<?= $booking !== null ? '2' : '1' ?>>Today at the hospital</h<?= $booking !== null ? '2' : '1' ?>>
    <p class="lede">
      <?= e(format_date($today)) ?> ·
      updated <span data-q="updated"><?= e(date('g:i a')) ?></span>
    </p>
  </div>

  <div class="queue-board" id="queue-board">
    <?php if (!$board): ?>
      <div class="notice notice-info mb-0">
        <?= icon('info') ?>
        <p>No outpatient sessions are scheduled today.
           For emergencies we are open around the clock — call
           <a href="tel:<?= e(HOSPITAL['mobile']) ?>"><?= e(HOSPITAL['mobile_display']) ?></a>.</p>
      </div>
    <?php else: ?>
      <?php foreach ($board as $row): $q = $row['queue']; ?>
        <article class="queue-card is-<?= e($q['state']) ?>">
          <div class="queue-card-head">
            <span class="q-doctor"><?= e($row['doctor']['name']) ?></span>
            <span class="q-session"><?= e($q['label']) ?> · <?= e($q['timing']) ?></span>
          </div>
          <div class="queue-card-body">
            <div class="q-stat">
              <span class="q-label">Now serving</span>
              <span class="q-num"><?= $q['now_serving'] > 0 ? (int) $q['now_serving'] : '—' ?></span>
            </div>
            <div class="q-side">
              <span class="pill <?= $q['state'] === 'upcoming' ? 'pill-gold'
                                  : ($q['state'] === 'ended' ? 'pill-grey' : 'pill-green') ?>">
                <?= $q['state'] === 'upcoming' ? 'Starts soon'
                    : ($q['state'] === 'ended' ? 'Session over' : 'In progress') ?>
              </span>
              <span class="q-waiting"><?= (int) $q['waiting'] ?> waiting</span>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if ($booking === null): ?>
    <form class="form-card mt-4" method="get" action="queue.php">
      <div class="field">
        <label for="ref">Have a token? Enter your booking reference</label>
        <input type="text" id="ref" name="ref" required
               maxlength="10" autocomplete="off" spellcheck="false"
               style="text-transform:uppercase;letter-spacing:.12em;font-weight:600"
               placeholder="SN4KQ2TB" value="<?= e($ref) ?>">
      </div>
      <button class="btn btn-primary btn-block" type="submit">
        <?= icon('search') ?> Show My Place in the Queue
      </button>
    </form>
  <?php else: ?>
    <div class="btn-row mt-4" style="justify-content:center">
      <a class="btn btn-outline" href="booking.php?ref=<?= e($booking['reference']) ?>">
        <?= icon('ticket') ?> View token slip
      </a>
      <a class="btn btn-outline" href="queue.php"><?= icon('users') ?> Full queue board</a>
    </div>
  <?php endif; ?>

  </div>
</section>

<script src="<?= e(asset('assets/js/queue.js')) ?>" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
