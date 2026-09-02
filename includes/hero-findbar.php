<?php
/**
 * The booking bar: doctor, day, session, and the live token count beneath it.
 *
 * Its own partial because it is the site's main booking path and both hero
 * styles carry it — a redesign of the hero should not be able to drop it by
 * accident. Expects $doctors and $nextSlot to be in scope.
 */
declare(strict_types=1);
?>
        <form class="findbar" method="get" action="book.php">
          <div class="findbar-field cs" data-cs>
            <label class="cs-label" for="fb-doctor"><?= icon('stethoscope') ?> Doctor</label>
            <select id="fb-doctor" name="doctor">
              <?php foreach ($doctors as $doc): ?>
                <option value="<?= (int) $doc['id'] ?>"><?= e(doctor_short_name($doc['name'])) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <span class="findbar-sep" aria-hidden="true"></span>

          <div class="findbar-field cs" data-cs>
            <label class="cs-label" for="fb-date"><?= icon('calendar') ?> Day</label>
            <select id="fb-date" name="date">
              <?php foreach (bookable_dates() as $i => $d):
                $dt  = new DateTimeImmutable($d);
                $lbl = $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : $dt->format('D, j M'));
                if (is_free_op_day($d)) { $lbl .= ' · Free OP'; }
              ?>
                <option value="<?= e($d) ?>"<?= ($nextSlot && $nextSlot['date'] === $d) ? ' selected' : '' ?>><?= e($lbl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <span class="findbar-sep" aria-hidden="true"></span>

          <div class="findbar-field cs" data-cs>
            <label class="cs-label" for="fb-session"><?= icon('clock') ?> Session</label>
            <select id="fb-session" name="session">
              <option value="morning"<?= ($nextSlot && $nextSlot['session'] === 'morning') ? ' selected' : '' ?>>Morning</option>
              <option value="evening"<?= ($nextSlot && $nextSlot['session'] === 'evening') ? ' selected' : '' ?>>Evening</option>
            </select>
          </div>

          <button class="findbar-go" type="submit">
            <?= icon('search') ?><span>Search</span>
          </button>
        </form>

        <?php if ($nextSlot !== null): ?>
          <p class="hero-live">
            <span class="live-dot" aria-hidden="true"></span>
            <strong><?= e($nextSlot['when']) ?> &middot; <?= e($nextSlot['label']) ?></strong>
            session open &mdash; <?= $nextSlot['remaining'] ?> token<?= $nextSlot['remaining'] === 1 ? '' : 's' ?> left
          </p>
        <?php endif; ?>
