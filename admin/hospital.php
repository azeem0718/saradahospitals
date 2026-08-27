<?php
/**
 * The hospital's own details — name, address, phone numbers, map. Admin only.
 *
 * These are the facts that change in real life and used to need a code
 * deployment: a new reception line, a corrected landmark, a moved pin. Every
 * field falls back to what the site shipped with, so clearing one restores the
 * original wording rather than leaving a blank.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/content.php';
require_once __DIR__ . '/../includes/icons.php';

$user   = require_login(true);
$groups = content_groups();
$errors = [];

if (is_post()) {
    require_csrf();

    $values = [];
    foreach (content_specs() as $key => $spec) {
        // Keys carry dots; a form field name cannot, so they travel underscored.
        $field = str_replace('.', '_', $key);
        if (!array_key_exists($field, $_POST)) {
            continue;
        }
        $value = trim((string) $_POST[$field]);

        if (mb_strlen($value) > 300) {
            $errors[$key] = 'Please keep this under 300 characters.';
        } elseif ($spec['type'] === 'tel' && $value !== ''
                  && preg_match('/^\+?[0-9][0-9 \-]{6,19}$/', $value) !== 1) {
            $errors[$key] = 'Use digits, optionally starting with +.';
        }

        $values[$key] = $value;
    }

    if (!$errors) {
        content_save($values);
        flash('Hospital details saved.');
        redirect('hospital.php');
    }
}

/** What the form should show: what was typed if it failed, else what is live. */
$shown = static function (string $key) use ($errors): string {
    $field = str_replace('.', '_', $key);
    return $errors && array_key_exists($field, $_POST)
        ? (string) $_POST[$field]
        : text($key);
};

$editedCount = 0;
foreach (content_specs() as $key => $spec) {
    if (content_is_edited($key)) {
        $editedCount++;
    }
}

$adminTitle    = 'Hospital Details';
$adminSubtitle = $editedCount === 0
    ? 'Everything is as the site shipped.'
    : $editedCount . ' field' . ($editedCount === 1 ? '' : 's') . ' changed from the original.';
$adminNav      = 'hospital';

require __DIR__ . '/_header.php';
?>

<div class="notice notice-info">
  <?= icon('info') ?>
  <p>
    These details appear across the whole website — the masthead, the footer,
    the contact page, the emergency bar and every printed token slip.
    <strong>Clear a field to put it back to what it said originally.</strong>
  </p>
</div>

<?php if ($errors): ?>
  <div class="notice notice-emergency">
    <?= icon('alert') ?>
    <p><strong>Nothing was saved.</strong> Please correct the fields marked below.</p>
  </div>
<?php endif; ?>

<form method="post">
  <?= csrf_field() ?>

  <?php foreach ($groups as $groupKey => $group): ?>
    <div class="panel">
      <div class="panel-head">
        <div>
          <h2><?= e($group['label']) ?></h2>
          <p><?= e($group['hint']) ?></p>
        </div>
      </div>
      <div class="panel-body">
        <div class="field-row cols-2">
          <?php foreach ($group['fields'] as $key => $spec): ?>
            <?php $field = str_replace('.', '_', $key); ?>
            <div class="field">
              <label for="<?= e($field) ?>">
                <?= e($spec['label']) ?>
                <?php if (content_is_edited($key)): ?>
                  <span class="pill pill-gold">Changed</span>
                <?php endif; ?>
              </label>
              <input type="text" id="<?= e($field) ?>" name="<?= e($field) ?>"
                     maxlength="300" value="<?= e($shown($key)) ?>"
                     <?= $spec['type'] === 'tel' ? 'inputmode="tel" ' : '' ?>
                     placeholder="<?= e(content_default($key)) ?>">
              <?php if (isset($errors[$key])): ?>
                <p class="error"><?= e($errors[$key]) ?></p>
              <?php elseif (!empty($spec['hint'])): ?>
                <p class="hint"><?= e($spec['hint']) ?></p>
              <?php endif; ?>
              <?php if (content_is_edited($key)): ?>
                <p class="hint">Originally: <em><?= e(content_default($key)) ?></em></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="btn-row">
    <button class="btn btn-primary" type="submit"><?= icon('check') ?> Save details</button>
    <a class="btn btn-outline" href="../contact.php" target="_blank" rel="noopener">
      <?= icon('arrow-right') ?> View contact page
    </a>
  </div>
</form>

<?php require __DIR__ . '/_footer.php'; ?>
