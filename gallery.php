<?php
/**
 * Gallery. Reads whatever images exist in assets/img/gallery/ and shows a
 * placeholder grid until real photographs are added, so the page is never empty
 * and nothing has to be edited in code when photos arrive.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = 'Gallery';
$pageDescription = 'Photographs of Sarada Nursing Home, Kandukur — building, reception, ICU, wards and laboratory.';
$activeNav       = '';

$galleryDir = __DIR__ . '/assets/img/gallery';
$photos     = [];

if (is_dir($galleryDir)) {
    foreach (scandir($galleryDir) ?: [] as $file) {
        if (preg_match('/\.(jpe?g|png|webp)$/i', $file) === 1) {
            $photos[] = $file;
        }
    }
    sort($photos);
}

$placeholders = [
    ['Hospital Building',  'building'],
    ['Reception',          'users'],
    ['ICU',                'icu'],
    ['Patient Rooms',      'room'],
    ['Laboratory',         'lab'],
    ['Scanning Room',      'scan'],
];

require __DIR__ . '/includes/header.php';
page_hero(text('gallery.hero.title'), text('gallery.hero.lede'), 'Gallery', 'gallery');
?>

<section class="section">
  <div class="wrap">
    <?php if ($photos): ?>
      <div class="grid grid-3">
        <?php foreach ($photos as $photo): ?>
          <figure class="card" style="padding:0;overflow:hidden">
            <img src="assets/img/gallery/<?= e($photo) ?>"
                 alt="<?= e(ucwords(str_replace(['-', '_'], ' ', pathinfo($photo, PATHINFO_FILENAME)))) ?>"
                 loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover">
          </figure>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="notice notice-info">
        <?= icon('image') ?>
        <p>
          <strong>Photographs are being added.</strong>
          To fill this page, drop JPG or PNG files into
          <code>assets/img/gallery/</code> — they appear here automatically, with no
          code changes needed. Name each file after what it shows, for example
          <code>reception.jpg</code>.
        </p>
      </div>

      <div class="grid grid-3">
        <?php foreach ($placeholders as [$label, $ico]): ?>
          <div class="card text-center" style="padding:0;overflow:hidden">
            <div style="aspect-ratio:4/3;background:var(--navy-50);display:grid;place-items:center;color:var(--navy-600)">
              <span style="display:grid;gap:.6rem;justify-items:center">
                <span style="width:42px;height:42px;display:block"><?= icon($ico) ?></span>
                <span style="font-weight:600;font-size:.95rem;color:var(--navy-800)"><?= e($label) ?></span>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php cta_band(); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
