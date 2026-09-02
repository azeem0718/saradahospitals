<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';
/* Needed before the header, because this page hands the head its own
   structured data rather than taking only the site-wide graph. */
require_once __DIR__ . '/includes/seo.php';

$pageTitle       = 'Contact Us';
$pageDescription = 'Contact Sarada Nursing Home, Kandukur — opposite ICICI Bank, near Thyagarajaswamy Temple, Pamuru Road. Phone 08598-222299, emergency 83412 54590.';

/* BREADCRUMB-SEO */
$breadcrumb      = [['Contact Us', null]];
$pageType        = 'ContactPage';

/* Loaded here rather than in the template below because the same questions
   feed the FAQPage markup in the head, and the head is written first. This is
   the most valuable structured data on the site: these are the things people
   actually type into Google about a hospital, already answered, already
   editable in the panel. */
$faqs            = list_shaped('contact.faq');
$faqSchema       = seo_faq($faqs, canonical_url());
$pageSchema      = $faqSchema ? [$faqSchema] : [];
$activeNav       = 'contact';

require __DIR__ . '/includes/header.php';
page_hero(
    text('contact.hero.title'),
    text('contact.hero.lede'),
    'Contact',
    'contact'
);
?>

<section class="section">
  <div class="wrap">
    <div class="grid grid-split">
      <div>
        <span class="eyebrow"><?= e(text('contact.touch.eyebrow')) ?></span>
        <h2><?= e(text('contact.touch.title')) ?></h2>
        <p class="lede mb-3"><?= text_rich('contact.touch.lede') ?></p>
        <?php contact_details(); ?>

        <div class="btn-row mt-3">
          <a class="btn btn-emergency" href="tel:<?= e(HOSPITAL['mobile']) ?>">
            <?= icon('phone') ?> Emergency Call
          </a>
          <a class="btn btn-whatsapp"
             href="https://wa.me/<?= e(HOSPITAL['whatsapp']) ?>?text=<?= rawurlencode('Hello, I would like to enquire about an appointment at Sarada Nursing Home.') ?>"
             target="_blank" rel="noopener">
            <?= icon('whatsapp') ?> WhatsApp
          </a>
        </div>
      </div>

      <div>
        <?php map_block(); ?>
        <div class="card mt-2">
          <span class="card-icon"><?= icon('location') ?></span>
          <h3><?= e(text('contact.find.title')) ?></h3>
          <p><?= text_rich('contact.find.body') ?></p>
          <a class="btn btn-primary btn-block" href="<?= e(HOSPITAL['map']['link']) ?>" target="_blank" rel="noopener">
            <?= icon('arrow-right') ?> Get Directions
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section-paper">
  <div class="wrap wrap-narrow">
    <div class="section-head center">
      <span class="eyebrow"><?= e(text('contact.faq.eyebrow')) ?></span>
      <h2><?= e(text('contact.faq.title')) ?></h2>
    </div>

    <?php foreach ($faqs as $i => $faq): ?>
      <div class="card <?= $i === count($faqs) - 1 ? 'mb-0' : 'mb-2' ?>">
        <h3><?= e($faq['title']) ?></h3>
        <p class="mb-0"><?= content_inline($faq['text']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
