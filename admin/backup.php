<?php
/**
 * Database backups. Admin only.
 *
 * Lists what the nightly cron has produced, takes a backup on demand, and
 * streams a chosen file to the browser. The files themselves sit in
 * storage/backups/, which the web server refuses to serve directly — this
 * page is the only door, and it is behind the admin login.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../tools/backup.php';

$user  = require_login(true);
$error = '';

// Stream a backup. basename() means a stored name of "../config.php"
// fetches nothing.
$download = query('download');
if ($download !== '') {
    $file = backup_dir() . '/' . basename($download);
    if (basename($download) === $download
        && preg_match('/^backup-\d{8}-\d{6}\.sql\.gz$/', $download) === 1
        && is_file($file)) {
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: no-store');
        readfile($file);
        exit;
    }
    http_response_code(404);
    $error = 'That backup no longer exists.';
}

if (is_post()) {
    require_csrf();
    try {
        $name = backup_database();
        backup_prune();
        flash('Backup ' . $name . ' created.');
    } catch (Throwable $e) {
        error_log('Manual backup failed: ' . $e->getMessage());
        flash('The backup failed — see the server error log.', 'error');
    }
    redirect('backup.php');
}

$backups = backup_list();

$adminTitle    = 'Backups';
$adminSubtitle = count($backups) . ' backup' . (count($backups) === 1 ? '' : 's')
               . ' on the server · kept ' . BACKUP_KEEP_DAYS . ' days';
$adminNav      = 'backup';
$adminActions  = '<form method="post">' . csrf_field()
               . '<button class="btn btn-primary btn-sm" type="submit">'
               . icon('shield') . ' Back up now</button></form>';

require __DIR__ . '/_header.php';
?>

<?php if ($error !== ''): ?>
  <div class="notice notice-emergency"><?= icon('alert') ?><p><?= e($error) ?></p></div>
<?php endif; ?>

<div class="notice notice-info">
  <?= icon('info') ?>
  <p>
    <strong>Set the nightly cron once in hPanel</strong> (Advanced &rarr; Cron Jobs):
    run <code>php <?= e(dirname(__DIR__)) ?>/tools/backup.php</code> daily, ideally
    around 2&nbsp;am. Each run adds a backup here and removes those older than
    <?= BACKUP_KEEP_DAYS ?> days. Download one before any risky change.
  </p>
</div>

<div class="panel">
  <div class="panel-head">
    <div>
      <h2>Stored backups</h2>
      <p>Gzipped SQL — phpMyAdmin imports them as-is.</p>
    </div>
  </div>
  <div class="panel-body flush">
    <?php if (!$backups): ?>
      <div class="empty-state">
        <?= icon('shield') ?>
        <p>No backups yet. Use “Back up now”, or set up the nightly cron.</p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="admin-table" style="min-width:520px">
          <thead>
            <tr>
              <th scope="col">File</th>
              <th scope="col">Taken</th>
              <th scope="col">Size</th>
              <th scope="col" class="actions">Download</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($backups as $b): ?>
              <tr>
                <th scope="row"><code><?= e($b['name']) ?></code></th>
                <td><?= e(date('D, j M Y g:i a', $b['mtime'])) ?></td>
                <td><?= e(number_format($b['bytes'] / 1024, 1)) ?> KB</td>
                <td class="actions">
                  <a class="btn-icon good" href="backup.php?download=<?= e(rawurlencode($b['name'])) ?>"
                     title="Download <?= e($b['name']) ?>">
                    <?= icon('arrow-right') ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
