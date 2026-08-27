<?php
/**
 * The words on each public page. Admin only.
 *
 * Structure — which card links where, which photograph sits behind a banner —
 * stays in the templates and in Pictures. What this screen owns is the wording,
 * block by block, with every block falling back to what the site shipped.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/content.php';
require_once __DIR__ . '/../includes/icons.php';

$user  = require_login(true);
$pages = content_pages();

$slug = query('page');
if (!isset($pages[$slug])) {
    $slug = array_key_first($pages);
}
$page   = $pages[$slug];
$errors = [];

// A page's lists — its FAQ, its cards — are edited on the same screen as its
// wording, because to reception they are simply parts of the same page.
$allowed    = $page['lists'] ?? [];
$redirectTo = 'pages.php?page=' . urlencode($slug);
$listErrors = [];
require __DIR__ . '/_list-save.php';
$listErrors = $errors;
$errors     = [];

if (is_post() && post('page') !== '') {
    require_csrf();

    $target = post('page');
    if (!isset($pages[$target])) {
        flash('That page does not exist.', 'error');
        redirect('pages.php');
    }

    $values = [];
    foreach ($pages[$target]['blocks'] as $key => $spec) {
        $field = str_replace('.', '_', $key);
        if (!array_key_exists($field, $_POST)) {
            continue;
        }
        $value = trim((string) $_POST[$field]);
        $limit = ($spec['type'] ?? 'text') === 'area' ? 1200 : 300;
        if (mb_strlen($value) > $limit) {
            $errors[$key] = 'Please keep this under ' . $limit . ' characters.';
        }
        $values[$key] = $value;
    }

    if (!$errors) {
        content_save($values);
        flash($pages[$target]['label'] . ' page saved.');
        redirect('pages.php?page=' . urlencode($target));
    }
}

/** What to show in a field: the rejected attempt if there was one, else live. */
$shown = static function (string $key) use ($errors): string {
    $field = str_replace('.', '_', $key);
    return $errors && array_key_exists($field, $_POST)
        ? (string) $_POST[$field]
        : text($key);
};

$changed = 0;
foreach ($page['blocks'] as $key => $spec) {
    if (content_is_edited($key)) {
        $changed++;
    }
}

$adminTitle    = 'Page Text';
$adminSubtitle = $page['label'] . ' — ' . count($page['blocks']) . ' blocks, '
               . ($changed === 0 ? 'none changed from the original'
                                 : $changed . ' changed from the original');
$adminNav      = 'pages';

require __DIR__ . '/_header.php';
?>

<div class="range-row" role="group" aria-label="Choose a page">
  <?php foreach ($pages as $key => $p): ?>
    <a class="btn btn-sm <?= $key === $slug ? 'btn-primary' : 'btn-outline' ?>"
       href="pages.php?page=<?= e($key) ?>"
       <?= $key === $slug ? 'aria-current="true"' : '' ?>><?= e($p['label']) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($errors): ?>
  <div class="notice notice-emergency">
    <?= icon('alert') ?>
    <p><strong>Nothing was saved.</strong> Please shorten the fields marked below.</p>
  </div>
<?php endif; ?>

<?php if ($listErrors): ?>
  <div class="notice notice-emergency">
    <?= icon('alert') ?>
    <p><strong>Nothing was saved.</strong></p>
    <ul>
      <?php foreach ($listErrors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="notice notice-info">
  <?= icon('info') ?>
  <p>
    <strong>Clear a block to put it back to what it said originally.</strong>
    The grey text under each box is the original wording. Photographs for this
    page are managed under <a href="images.php">Pictures</a>.
  </p>
</div>

<form method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="page" value="<?= e($slug) ?>">

  <div class="panel">
    <div class="panel-head">
      <div>
        <h2><?= e($page['label']) ?></h2>
        <p>Every block of wording on this page.</p>
      </div>
      <a class="btn btn-outline btn-sm" href="../<?= e($page['url']) ?>" target="_blank" rel="noopener">
        <?= icon('arrow-right') ?> View page
      </a>
    </div>
    <div class="panel-body">
      <?php foreach ($page['blocks'] as $key => $spec): ?>
        <?php $field = str_replace('.', '_', $key); $type = $spec['type'] ?? 'text'; ?>
        <div class="field">
          <label for="<?= e($field) ?>">
            <?= e($spec['label']) ?>
            <?php if (content_is_edited($key)): ?>
              <span class="pill pill-gold">Changed</span>
            <?php endif; ?>
          </label>

          <?php if ($type === 'area'): ?>
            <textarea id="<?= e($field) ?>" name="<?= e($field) ?>" rows="3"
                      maxlength="1200"><?= e($shown($key)) ?></textarea>
          <?php else: ?>
            <input type="text" id="<?= e($field) ?>" name="<?= e($field) ?>"
                   maxlength="300" value="<?= e($shown($key)) ?>">
          <?php endif; ?>

          <?php if (isset($errors[$key])): ?>
            <p class="error"><?= e($errors[$key]) ?></p>
          <?php elseif (!empty($spec['hint'])): ?>
            <p class="hint"><?= e($spec['hint']) ?></p>
          <?php endif; ?>
          <p class="hint">Originally: <em><?= e(content_default($key)) ?></em></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="btn-row">
    <button class="btn btn-primary" type="submit"><?= icon('check') ?> Save page text</button>
  </div>
</form>

<?php foreach ($allowed as $key): ?>
  <?php require __DIR__ . '/_list-editor.php'; ?>
<?php endforeach; ?>

<?php require __DIR__ . '/_footer.php'; ?>
