<?php
/**
 * One editable list, as a table of rows.
 *
 * Shared by the Tariff and Services screens because a tariff row, an offer and
 * a facility card are the same thing wearing different columns. The caller
 * sets $key before including this; everything else is read from the registry.
 *
 * Rows are ordered by a number rather than drag handles, which needs no
 * JavaScript and survives a reception desk on a slow tablet.
 */

declare(strict_types=1);

/** @var string $key */
$list  = content_lists()[$key];
$rows  = list_editable($key);
$blank = 3;
?>
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
        <table class="admin-table" style="min-width:<?= count($list['uses']) > 1 ? '640' : '420' ?>px">
          <thead>
            <tr>
              <th scope="col" style="width:5rem">Order</th>
              <th scope="col"><?= in_array('body', $list['uses'], true) ? 'Title' : 'Description' ?></th>
              <?php if (in_array('body', $list['uses'], true)): ?><th scope="col">Text</th><?php endif; ?>
              <?php if (in_array('icon', $list['uses'], true)): ?><th scope="col">Icon</th><?php endif; ?>
              <?php if (!empty($list['uses_tone'])): ?><th scope="col">Colour</th><?php endif; ?>
              <?php if (in_array('amount', $list['uses'], true)): ?><th scope="col" style="width:9rem">Amount (₹)</th><?php endif; ?>
              <?php if (in_array('unit', $list['uses'], true)): ?><th scope="col" style="width:9rem">Per</th><?php endif; ?>
              <th scope="col" style="width:5rem">Remove</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $all = $rows;
              for ($b = 0; $b < $blank; $b++) {
                  $all[] = ['title' => '', 'body' => '', 'icon' => '', 'tone' => '', 'amount' => null, 'unit' => ''];
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
                <?php if (!empty($list['uses_tone'])): ?>
                  <td>
                    <select name="<?= e($n) ?>[tone]" aria-label="Colour">
                      <?php foreach (content_tones() as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= ($row['tone'] ?? '') === $value ? ' selected' : '' ?>>
                          <?= e($label) ?>
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
