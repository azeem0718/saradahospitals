<?php
/**
 * What the hospital treats, and what it has. Admin only.
 *
 * The two condition lists also feed the structured data search engines read,
 * so adding a service here is how the hospital becomes findable for it.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/content.php';
require_once __DIR__ . '/../includes/icons.php';

$user       = require_login(true);
$allowed    = ['services.medicine', 'services.obg', 'facilities'];
$redirectTo = 'services.php';

require __DIR__ . '/_list-save.php';

$adminTitle    = 'Services & Facilities';
$adminSubtitle = 'The conditions treated, the procedures offered, and the facilities on site.';
$adminNav      = 'services';

require __DIR__ . '/_header.php';
require __DIR__ . '/_list-intro.php';

foreach ($allowed as $key) {
    require __DIR__ . '/_list-editor.php';
}
?>

<div class="btn-row">
  <a class="btn btn-outline" href="../services.php" target="_blank" rel="noopener">
    <?= icon('arrow-right') ?> View services page
  </a>
  <a class="btn btn-outline" href="../facilities.php" target="_blank" rel="noopener">
    <?= icon('arrow-right') ?> View facilities page
  </a>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
