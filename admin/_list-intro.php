<?php
/** The shared explanation and error block above a screen full of list editors. */
declare(strict_types=1);
/** @var list<string> $errors */
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
    <strong>Remove</strong> and save — or simply clear its name. The blank rows
    at the bottom are there to add to the list.
  </p>
</div>
