<?php
/**
 * Photographs for the page banners and the home page department cards.
 * Admin only.
 *
 * Every slot is optional. A slot with no photograph keeps the drawn artwork or
 * the icon it already has, so the site never looks half-finished while these
 * are being filled in one at a time.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../includes/uploads.php';
require_once __DIR__ . '/../includes/site-images.php';

$user   = require_login(true);
$errors = [];

if (is_post()) {
    require_csrf();

    $action = post('action');
    $slot   = post('slot');
    $slots  = site_image_slots();

    if (!isset($slots[$slot])) {
        flash('That is not a picture this site uses.', 'error');
        redirect('images.php');
    }

    $current = site_image($slot);

    if ($action === 'remove') {
        if ($current !== null) {
            delete_upload($current['file'], SITE_IMAGE_STORE);
            db()->prepare('DELETE FROM site_images WHERE slot = ?')->execute([$slot]);
            flash($slots[$slot] . ': photograph removed. It goes back to the drawn artwork.');
        }
        redirect('images.php#' . $slot);
    }

    if ($action === 'save') {
        $alt = post('alt');

        if (mb_strlen($alt) > 200) {
            $errors[$slot] = 'Please keep the description under 200 characters.';
        }

        [$file, $uploadError] = $errors
            ? [null, null]
            : store_upload($_FILES['image'] ?? [], SITE_IMAGE_STORE, $slot);

        if ($uploadError !== null) {
            $errors[$slot] = $uploadError;
        } elseif ($file === null && $current === null) {
            $errors[$slot] = 'Choose an image file to upload.';
        }

        if (!$errors) {
            if ($file !== null) {
                delete_upload($current['file'] ?? null, SITE_IMAGE_STORE);
            }

            db()->prepare(
                'INSERT INTO site_images (slot, file, alt) VALUES (?,?,?)
                 ON DUPLICATE KEY UPDATE file = VALUES(file), alt = VALUES(alt)'
            )->execute([$slot, $file ?? $current['file'], $alt]);

            site_images_forget();
            flash($slots[$slot] . ': saved.');
            redirect('images.php#' . $slot);
        }
    }
}

$adminTitle    = 'Pictures';
$adminSubtitle = 'Photographs for the page banners and the home page cards.';
$adminNav      = 'images';

require __DIR__ . '/_header.php';
?>

<div class="notice notice-info">
  <?= icon('info') ?>
  <p>
    <strong>Use the hospital's own photographs.</strong>
    Every picture here is shown to patients as a picture of Sarada Nursing Home.
    A photograph taken on a phone in good daylight is worth more than a stock
    picture of somewhere else — and a stock picture of another hospital's ward
    would tell patients something untrue. Landscape shots work best. Nothing is
    required: a slot left empty keeps its drawn artwork.
  </p>
</div>

<?php foreach (site_image_groups() as $key => $group): ?>
  <div class="panel">
    <div class="panel-head">
      <h2><?= e($group['label']) ?></h2>
    </div>
    <div class="panel-body">
      <p class="hint" style="margin-bottom:1.4rem"><?= e($group['hint']) ?></p>

      <div class="slot-grid">
        <?php foreach ($group['slots'] as $slot => $label): ?>
          <?php $image = site_image($slot); ?>
          <form class="slot" id="<?= e($slot) ?>" method="post" action="images.php"
                enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="slot" value="<?= e($slot) ?>">

            <div class="slot-preview">
              <?php if ($image !== null): ?>
                <img src="<?= e(site_image_url($slot, '../')) ?>"
                     alt="<?= e(site_image_alt($slot)) ?>" loading="lazy">
              <?php else: ?>
                <span class="slot-empty"><?= icon('image') ?> Drawn artwork</span>
              <?php endif; ?>
            </div>

            <h3 class="slot-title"><?= e($label) ?></h3>

            <div class="field">
              <label class="sr-only" for="file-<?= e($slot) ?>">
                Photograph for <?= e($label) ?>
              </label>
              <input type="file" id="file-<?= e($slot) ?>" name="image"
                     accept="image/jpeg,image/png,image/webp">
            </div>

            <div class="field">
              <label for="alt-<?= e($slot) ?>">Describe the picture</label>
              <span class="hint">Read aloud to patients using a screen reader.</span>
              <input type="text" id="alt-<?= e($slot) ?>" name="alt" maxlength="200"
                     value="<?= e($image['alt'] ?? '') ?>"
                     placeholder="e.g. The reception counter at Sarada Nursing Home">
            </div>

            <?php if (isset($errors[$slot])): ?>
              <span class="error"><?= e($errors[$slot]) ?></span>
            <?php endif; ?>

            <div class="slot-actions">
              <button class="btn btn-sm btn-primary" type="submit" name="action" value="save">
                <?= icon('check') ?> Save
              </button>
              <?php if ($image !== null): ?>
                <button class="btn btn-sm btn-outline" type="submit" name="action" value="remove"
                        formnovalidate
                        data-confirm="Remove this photograph? The page goes back to its drawn artwork.">
                  Remove
                </button>
              <?php endif; ?>
            </div>
          </form>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/_footer.php'; ?>
