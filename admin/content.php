<?php
/**
 * What has been changed from the original. Admin only.
 *
 * The site's defaults live in the code, so a field nobody has touched keeps
 * improving as the site does, while a field somebody has touched is frozen at
 * their wording. That is the behaviour we want, but no single editing screen
 * can show it: each one only knows about its own handful of fields. This is
 * the one place that answers "what have we changed, and what is still as it
 * came" — and lets any single change be put back.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/content.php';
require_once __DIR__ . '/../includes/site-images.php';
require_once __DIR__ . '/../includes/icons.php';

$user = require_login(true);

if (is_post()) {
    require_csrf();

    $kind = (string) post('kind');
    $key  = (string) post('key');

    if (content_revert($kind, $key)) {
        flash('Put back to the original.');
    } else {
        flash('That item does not exist.', 'error');
    }
    redirect('content.php');
}

$areas   = content_areas();
$total   = 0;
$changed = 0;
$touched = 0;
foreach ($areas as $area) {
    $total   += $area['total'];
    $changed += $area['changed'];
    $touched += $area['changed'] > 0 ? 1 : 0;
}

/* Areas grouped under the heading each belongs to, in the order they arrive. */
$sections = [];
foreach ($areas as $area) {
    $sections[$area['section']][] = $area;
}

$adminTitle    = 'Site Content';
$adminSubtitle = $changed === 0
    ? 'Everything is as the site shipped. ' . $total . ' things can be changed.'
    : $changed . ' of ' . $total . ' things changed, across '
      . $touched . ' area' . ($touched === 1 ? '' : 's') . '.';
$adminNav = 'content';

require __DIR__ . '/_header.php';
?>

<div class="stat-grid">
  <div class="stat">
    <span class="stat-label">Changed</span>
    <span class="stat-value"><?= $changed ?></span>
    <span class="stat-note">edited by us</span>
  </div>
  <div class="stat">
    <span class="stat-label">As shipped</span>
    <span class="stat-value"><?= $total - $changed ?></span>
    <span class="stat-note">still following the site</span>
  </div>
  <div class="stat">
    <span class="stat-label">Areas touched</span>
    <span class="stat-value"><?= $touched ?></span>
    <span class="stat-note">of <?= count($areas) ?></span>
  </div>
</div>

<div class="notice notice-info">
  <?= icon('info') ?>
  <p>
    Anything left alone follows the site: when the wording or the design is
    improved, an untouched field picks the improvement up. Once you edit
    something it keeps your version instead, until you put it back here.
  </p>
</div>

<?php foreach ($sections as $section => $group): ?>
  <div class="panel">
    <div class="panel-head">
      <div>
        <h2><?= e($section) ?></h2>
        <p>
          <?php
          $sc = 0;
          $st = 0;
          foreach ($group as $a) {
              $sc += $a['changed'];
              $st += $a['total'];
          }
          echo $sc === 0
              ? 'All ' . $st . ' as shipped.'
              : $sc . ' of ' . $st . ' changed.';
          ?>
        </p>
      </div>
    </div>
    <div class="panel-body flush">
      <?php foreach ($group as $area): ?>
        <div class="ov-area<?= $area['changed'] ? ' is-changed' : '' ?>">
          <div class="ov-area-head">
            <span class="ov-area-name"><?= e($area['label']) ?></span>
            <?php if ($area['changed']): ?>
              <span class="pill pill-gold"><?= $area['changed'] ?> changed</span>
            <?php else: ?>
              <span class="ov-quiet">as shipped</span>
            <?php endif; ?>
            <span class="ov-count"><?= $area['total'] ?> editable</span>
            <a class="btn btn-sm btn-outline" href="<?= e($area['url']) ?>">
              <?= icon('edit') ?> Edit
            </a>
          </div>

          <?php if ($area['items']): ?>
            <ul class="ov-items">
              <?php foreach ($area['items'] as $item): ?>
                <li>
                  <div class="ov-item-text">
                    <strong><?= e($item['label']) ?></strong>
                    <span class="ov-was">Was: <?= e($item['was']) ?></span>
                    <span class="ov-now">Now: <?= e($item['now']) ?></span>
                  </div>
                  <form method="post" action="content.php" class="ov-revert"
                        data-confirm="Put &quot;<?= e($item['label']) ?>&quot; back to the original?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="kind" value="<?= e($item['kind']) ?>">
                    <input type="hidden" name="key" value="<?= e($item['key']) ?>">
                    <button class="btn btn-sm btn-outline" type="submit">
                      <?= icon('undo') ?> Put back
                    </button>
                  </form>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/_footer.php'; ?>
