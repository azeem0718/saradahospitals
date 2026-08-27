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

$user       = require_login(true);
$allowed    = ['tariff.consultation', 'tariff.rooms', 'offers'];
$redirectTo = 'tariff.php';

require __DIR__ . '/_list-save.php';

$adminTitle    = 'Tariff & Offers';
$adminSubtitle = 'The charges and offers shown on the public pages.';
$adminNav      = 'tariff';

require __DIR__ . '/_header.php';
require __DIR__ . '/_list-intro.php';

foreach ($allowed as $key) {
    require __DIR__ . '/_list-editor.php';
}
?>

<div class="btn-row">
  <a class="btn btn-outline" href="../tariff.php" target="_blank" rel="noopener">
    <?= icon('arrow-right') ?> View tariff page
  </a>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
