<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/components.php';

$pageTitle       = 'Contact Us';
$pageDescription = 'Contact Sarada Nursing Home, Kandukur — opposite ICICI Bank, near Thyagarajaswamy Temple, Pamuru Road. Phone 08598-222299, emergency 83412 54590.';
$activeNav       = 'contact';

require __DIR__ . '/includes/header.php';
page_hero(
    'Contact Us',
    'Call us any time, or come and see us on Pamuru Road, Kandukur.',
    'Contact',
    'contact'
);
?>

<section class="section">
  <div class="wrap">
    <div class="grid grid-split">
      <div>
        <span class="eyebrow">Get in touch</span>
        <h2>We are open 24 hours</h2>
        <p class="lede mb-3">
          For emergencies call the mobile number — it is answered around the clock.
          For appointments, tariff questions and general enquiries, the landline
          reaches our reception.
        </p>
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
          <h3>How to find us</h3>
          <p>
            We are on <strong>Pamuru Road</strong> in Kandukur, directly
            <strong>opposite ICICI Bank</strong> and close to
            <strong>Thyagarajaswamy Temple</strong>. Look for the Sarada Nursing
            Home board above the entrance.
          </p>
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
      <span class="eyebrow">Before you call</span>
      <h2>Common questions</h2>
    </div>

    <div class="card mb-2">
      <h3>Do I need an appointment?</h3>
      <p class="mb-0">
        Walk-in patients are always seen. Booking a token online simply saves you
        waiting — you get a number in advance rather than taking one at the desk.
      </p>
    </div>
    <div class="card mb-2">
      <h3>What are the OP timings?</h3>
      <p class="mb-0">
        Current session timings, and how many tokens are still free, are shown live
        on the <a href="book.php">booking page</a>. Emergency care runs 24 hours
        regardless of OP timings.
      </p>
    </div>
    <div class="card mb-2">
      <h3>How much does a consultation cost?</h3>
      <p class="mb-0">
        OP consultation is <?= money(200) ?>, and <?= money(400) ?> for emergency OP
        after 9 PM. Consultations are <strong>free every Friday</strong>. Full
        charges are on the <a href="tariff.php">tariff page</a>.
      </p>
    </div>
    <div class="card mb-0">
      <h3>Can I cancel a token I booked?</h3>
      <p class="mb-0">
        Yes — please call <a href="tel:<?= e(HOSPITAL['landline']) ?>"><?= e(HOSPITAL['landline_display']) ?></a>
        and give your reference number, so the token can go to another patient.
      </p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
