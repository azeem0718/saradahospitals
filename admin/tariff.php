<?php
/**
 * Tariff rows and the standing offers. Admin only.
 *
 * These are the numbers patients plan around, and they used to be compiled
 * into the site. Each list falls back to what shipped until reception saves
 * their own version; "Restore original" puts it back.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/content.php';
require_once __DIR__ . '/../includes/icons.php';

$user   = require_login(true);
$lists  = content_lists();
$errors = [];

/** Blank rows offered at the bottom of each list, so adding needs no button. */
const BLANK_ROWS = 3;

if (is_post()) {
    require_csrf();

    $key = post('list_key');
    if (!isset($lists[$key])) {
        flash('That list does not exist.', 'error');
        redirect('tariff.php');
    }

    if (post('action') === 'reset') {
        list_reset($key);
        flash($lists[$key]['label'] . ' restored to the original.');
        redirect('tariff.php');
    }

    $uses    = $lists[$key]['uses'];
    $posted  = $_POST['rows'] ?? [];
    $cleaned = [];

    foreach (is_array($posted) ? $posted : [] as $row) {
        if (!is_array($row) || !empty($row['remove'])) {
            continue;
        }
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') {
            continue;   // a blank line is how you leave a spare row unused
        }

        if (in_array('amount', $uses, true)) {
            $amount = trim((string) ($row['amount'] ?? ''));
            if ($amount === '' || !ctype_digit($amount) || (int) $amount > 9999999) {
                $errors[] = 'Give "' . $title . '" a whole-rupee amount, digits only.';
                continue;
            }
            $row['amount'] = (int) $amount;
        }
        if (in_array('icon', $uses, true)
            && ($row['icon'] ?? '') !== ''
            && !isset(icon_feature_set()[$row['icon']])) {
            $errors[] = 'Choose a listed icon for "' . $title . '".';
            continue;
        }

        $cleaned[] = [
            'order'  => (int) ($row['order'] ?? 0),
            'title'  => $title,
            'body'   => (string) ($row['body'] ?? ''),
            'icon'   => (string) ($row['icon'] ?? ''),
            'amount' => $row['amount'] ?? null,
            'unit'   => (string) ($row['unit'] ?? ''),
        ];
    }

    if (!$errors) {
        usort($cleaned, static fn ($a, $b) => $a['order'] <=> $b['order']);
        list_save($key, $cleaned);
        flash($lists[$key]['label'] . ' saved.');
        redirect('tariff.php');
    }
}

$adminTitle    = 'Tariff & Offers';
$adminSubtitle = 'The charges and offers shown on the public pages.';
$adminNav      = 'tariff';

require __DIR__ . '/_header.php';
?>

<?php if ($errors): ?>
  <div class="notice notice-emergency">
    <?= icon('alert') ?>
    <p><strong>Nothing was saved.</strong></p>
    <ul>
      <?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="notice notice-info">
  <?= icon('info') ?>
  <p>
    Rows appear in the order you number them. To remove one, tick
    <strong>Remove</strong> and save — or simply clear its name. Blank rows at
    the bottom are there to add to the list.
  </p>
</div>

<?php foreach ($lists as $key => $list): ?>
  <?php $rows = list_editable($key); ?>
  <div class="panel">
    <div class="panel-head">
      <div>
        <h2><?= e($list['label']) ?></h2>
        <p><?= e($list['hint']) ?></p>
      </div>
      <?php if (list_is_edited($key)): ?>
        <span class="pill pill-gold">Changed from original</span>
      <?php endif; ?>
    </div>

    <div class="panel-body">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="list_key" value="<?= e($key) ?>">

        <div class="table-wrap">
          <table class="admin-table" style="min-width:640px">
            <thead>
              <tr>
                <th scope="col" style="width:5rem">Order</th>
                <th scope="col"><?= in_array('body', $list['uses'], true) ? 'Title' : 'Description' ?></th>
                <?php if (in_array('body', $list['uses'], true)): ?><th scope="col">Text</th><?php endif; ?>
                <?php if (in_array('icon', $list['uses'], true)): ?><th scope="col">Icon</th><?php endif; ?>
                <?php if (in_array('amount', $list['uses'], true)): ?><th scope="col" style="width:9rem">Amount (₹)</th><?php endif; ?>
                <?php if (in_array('unit', $list['uses'], true)): ?><th scope="col" style="width:9rem">Per</th><?php endif; ?>
                <th scope="col" style="width:5rem">Remove</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $all = $rows;
                for ($b = 0; $b < BLANK_ROWS; $b++) {
                    $all[] = ['title' => '', 'body' => '', 'icon' => '', 'amount' => null, 'unit' => ''];
                }
              ?>
              <?php foreach ($all as $i => $row): $n = 'rows[' . $i . ']'; ?>
                <tr>
                  <td>
                    <input type="number" name="<?= e($n) ?>[order]" value="<?= $i ?>"
                           min="0" max="999" style="width:4.5rem" aria-label="Order">
                  </td>
                  <td>
                    <input type="text" name="<?= e($n) ?>[title]" maxlength="160"
                           value="<?= e($row['title']) ?>" aria-label="Name"
                           placeholder="<?= $i >= count($rows) ? 'Add a new row…' : '' ?>">
                  </td>
                  <?php if (in_array('body', $list['uses'], true)): ?>
                    <td><input type="text" name="<?= e($n) ?>[body]" maxlength="400"
                               value="<?= e($row['body']) ?>" aria-label="Text"></td>
                  <?php endif; ?>
                  <?php if (in_array('icon', $list['uses'], true)): ?>
                    <td>
                      <select name="<?= e($n) ?>[icon]" aria-label="Icon">
                        <?php foreach (array_keys(icon_feature_set()) as $name): ?>
                          <option value="<?= e($name) ?>"<?= $row['icon'] === $name ? ' selected' : '' ?>>
                            <?= e($name) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                  <?php endif; ?>
                  <?php if (in_array('amount', $list['uses'], true)): ?>
                    <td><input type="number" name="<?= e($n) ?>[amount]" min="0" max="9999999"
                               value="<?= $row['amount'] === null ? '' : (int) $row['amount'] ?>"
                               aria-label="Amount"></td>
                  <?php endif; ?>
                  <?php if (in_array('unit', $list['uses'], true)): ?>
                    <td><input type="text" name="<?= e($n) ?>[unit]" maxlength="40"
                               value="<?= e($row['unit']) ?>" aria-label="Per"
                               placeholder="per day"></td>
                  <?php endif; ?>
                  <td style="text-align:center">
                    <?php if ($i < count($rows)): ?>
                      <input type="checkbox" name="<?= e($n) ?>[remove]" value="1"
                             aria-label="Remove this row">
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="btn-row mt-2">
          <button class="btn btn-primary btn-sm" type="submit"><?= icon('check') ?> Save</button>
          <?php if (list_is_edited($key)): ?>
            <button class="btn btn-outline btn-sm" type="submit" name="action" value="reset"
                    formnovalidate
                    onclick="return confirm('Put this list back to what the site shipped with?')">
              <?= icon('undo') ?> Restore original
            </button>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
<?php endforeach; ?>

<div class="btn-row">
  <a class="btn btn-outline" href="../tariff.php" target="_blank" rel="noopener">
    <?= icon('arrow-right') ?> View tariff page
  </a>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
